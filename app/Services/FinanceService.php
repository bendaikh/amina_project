<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\Exportateur;
use App\Models\Exportation;
use App\Models\FactureFournisseur;
use App\Models\FactureLocale;
use App\Models\Fournisseur;
use App\Models\LettrageHistorique;
use App\Models\NoteCredit;
use App\Models\Parametre;
use App\Models\Reglement;
use App\Models\ReglementLigne;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public const MODES = [
        'especes' => 'Espèces',
        'cheque' => 'Chèque',
        'virement' => 'Virement bancaire',
        'carte' => 'Carte bancaire',
        'autre' => 'Autre',
    ];

    public function modes(): array
    {
        $modes = [];
        foreach (self::MODES as $code => $label) {
            $modes[$code] = ['code' => $code, 'label' => $label];
        }

        if (Schema::hasTable('parametres')) {
            $extra = Parametre::query()
                ->where('type', 'mode_paiement')
                ->where('actif', true)
                ->orderBy('ordre')
                ->get();
            foreach ($extra as $param) {
                $code = $param->code ?: str()->slug((string) $param->valeur, '_');
                if ($code === '') {
                    continue;
                }
                $modes[$code] = ['code' => $code, 'label' => $param->valeur];
            }
        }

        return array_values($modes);
    }

    public function modeLabel(?string $code): string
    {
        if (!$code) {
            return '—';
        }
        foreach ($this->modes() as $mode) {
            if ($mode['code'] === $code || $mode['label'] === $code) {
                return $mode['label'];
            }
        }

        return $code;
    }

    public function devises(): array
    {
        $values = [];
        if (Schema::hasTable('parametres')) {
            $values = Parametre::getByType(Parametre::TYPE_DEVISE)
                ->pluck('valeur')
                ->filter()
                ->values()
                ->all();
        }
        if (!in_array('MAD', $values, true)) {
            array_unshift($values, 'MAD');
        }

        return array_values(array_unique($values));
    }

    /**
     * Configured VAT rates. Amounts always come from the documents, never from a fixed rate.
     */
    public function tauxTva(): array
    {
        if (!Schema::hasTable('parametres')) {
            return [];
        }

        return Parametre::getByType(Parametre::TYPE_TAUX_TVA)
            ->map(function (Parametre $p) {
                $raw = str_replace(['%', ' ', ','], ['', '', '.'], (string) ($p->code ?: $p->valeur));
                if (!is_numeric($raw)) {
                    $raw = str_replace(['%', ' ', ','], ['', '', '.'], (string) $p->valeur);
                }

                return is_numeric($raw) ? round((float) $raw, 2) : null;
            })
            ->filter(fn ($v) => $v !== null)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function company(): array
    {
        $exportateur = Schema::hasTable('exportateurs') ? Exportateur::defaut() : null;
        $read = function (string $column) use ($exportateur) {
            if (!$exportateur || !Schema::hasColumn('exportateurs', $column)) {
                return null;
            }
            $value = $exportateur->getAttribute($column);

            return $value !== null && $value !== '' ? $value : null;
        };

        return [
            'nom' => $read('nom_societe') ?: $read('nom') ?: config('app.name'),
            'adresse' => $read('adresse'),
            'telephone' => $read('telephone'),
            'email' => $read('email'),
            'web' => $read('web'),
            'ice' => $read('ice') ?: $read('ice_cin'),
            'if' => $read('identifiant_fiscal') ?: $read('if') ?: $read('numero_if'),
            'rc' => $read('rc') ?: $read('registre_commerce') ?: $read('numero_rc'),
            'banque' => $read('banque'),
            'agence' => $read('agence'),
            'rib' => $read('rib'),
            'iban' => $read('iban'),
            'swift' => $read('swift'),
            'devise' => 'MAD',
        ];
    }

    public function currencyLabel(?string $devise): string
    {
        $devise = strtoupper((string) ($devise ?: 'MAD'));

        return $devise === 'MAD' ? 'DH' : $devise;
    }

    public function nextNumero(string $sens): string
    {
        $prefix = $sens === Reglement::SENS_FOURNISSEUR ? 'RF' : 'RC';
        $year = now()->format('Y');
        $like = "{$prefix}-{$year}-";
        $last = Reglement::where('numero', 'like', $like . '%')->orderByDesc('numero')->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $like . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function paidFor(string $type, int $id, ?int $exceptReglementId = null): float
    {
        $query = ReglementLigne::query()
            ->where('facture_type', $type)
            ->where('facture_id', $id);
        if ($exceptReglementId) {
            $query->where('reglement_id', '!=', $exceptReglementId);
        }

        return $this->money((float) $query->sum('montant'));
    }

    public function invoice(string $type, int $id): ?array
    {
        if ($type === ReglementLigne::LOCALE) {
            $f = FactureLocale::with('client')->find($id);
            if (!$f) {
                return null;
            }
            $echeance = $f->echeance?->toDateString();
            if (!$echeance && $f->date_facture) {
                $days = (int) ($f->client?->delai_paiement ?? 0);
                $echeance = $f->date_facture->copy()->addDays($days)->toDateString();
            }

            return $this->normalizeInvoice([
                'facture_type' => ReglementLigne::LOCALE,
                'facture_id' => $f->id,
                'numero' => $f->numero,
                'date' => $f->date_facture?->toDateString(),
                'echeance' => $echeance,
                'party_id' => $f->client_id,
                'party_nom' => $f->client?->nom,
                'total_ht' => (float) $f->total_ht,
                'total_tva' => (float) $f->total_tva,
                'total' => (float) $f->total_ttc,
                'devise' => $f->devise ?: 'MAD',
                'statut' => $f->statut,
                'mode_paiement' => $f->mode_paiement,
                'payable' => !in_array($f->statut, ['brouillon', 'annulee'], true),
                'annulee' => $f->statut === 'annulee',
                'source' => 'Facture locale',
            ]);
        }

        if ($type === ReglementLigne::EXPORT) {
            $e = Exportation::with('client')->withSum('lignes as somme_lignes', 'montant')->find($id);
            if (!$e || !$e->numero_facture) {
                return null;
            }
            $ht = (float) ($e->somme_lignes ?? 0);
            $ttc = $ht + ($e->transport_applique ? (float) $e->montant_transport : 0);
            $days = (int) ($e->client?->delai_paiement ?? 0);
            $date = $e->date_creation?->toDateString();
            $echeance = $date ? Carbon::parse($date)->addDays($days)->toDateString() : null;

            return $this->normalizeInvoice([
                'facture_type' => ReglementLigne::EXPORT,
                'facture_id' => $e->id,
                'numero' => $e->numero_facture,
                'date' => $date,
                'echeance' => $echeance,
                'party_id' => $e->client_id,
                'party_nom' => $e->client?->nom,
                'total_ht' => $ht,
                'total_tva' => 0,
                'total' => $ttc,
                'devise' => $e->devise ?: 'MAD',
                'statut' => $e->statut,
                'mode_paiement' => $e->conditions_paiement,
                'payable' => true,
                'annulee' => false,
                'source' => 'Facture export',
            ]);
        }

        if ($type === ReglementLigne::FOURNISSEUR) {
            $f = FactureFournisseur::with('fournisseur')->find($id);
            if (!$f) {
                return null;
            }

            return $this->normalizeInvoice([
                'facture_type' => ReglementLigne::FOURNISSEUR,
                'facture_id' => $f->id,
                'numero' => $f->numero,
                'date' => $f->date_facture?->toDateString(),
                'echeance' => ($f->echeance ?: $f->date_facture)?->toDateString(),
                'party_id' => $f->fournisseur_id,
                'party_nom' => $f->fournisseur?->nom,
                'total_ht' => (float) $f->total_ht,
                'total_tva' => (float) $f->total_tva,
                'total' => (float) $f->total_ttc,
                'devise' => $f->devise ?: 'MAD',
                'statut' => $f->statut,
                'mode_paiement' => $f->mode_paiement,
                'payable' => $f->statut !== 'brouillon',
                'annulee' => false,
                'source' => 'Facture fournisseur',
            ]);
        }

        return null;
    }

    public function openInvoices(string $sens, int $partyId, ?int $reglementId = null, bool $includeSettled = false): array
    {
        $rows = [];
        if ($sens === Reglement::SENS_CLIENT) {
            $locales = FactureLocale::with('client')
                ->where('client_id', $partyId)
                ->whereNotIn('statut', ['brouillon', 'annulee'])
                ->orderBy('date_facture')
                ->get();
            foreach ($locales as $f) {
                $rows[] = $this->presentPayable($this->invoice(ReglementLigne::LOCALE, $f->id), $reglementId);
            }
            $exports = Exportation::with('client')
                ->where('client_id', $partyId)
                ->whereNotNull('numero_facture')
                ->where('numero_facture', '!=', '')
                ->orderBy('date_creation')
                ->get();
            foreach ($exports as $e) {
                $rows[] = $this->presentPayable($this->invoice(ReglementLigne::EXPORT, $e->id), $reglementId);
            }
        } else {
            $factures = FactureFournisseur::with('fournisseur')
                ->where('fournisseur_id', $partyId)
                ->where('statut', '!=', 'brouillon')
                ->orderBy('date_facture')
                ->get();
            foreach ($factures as $f) {
                $rows[] = $this->presentPayable($this->invoice(ReglementLigne::FOURNISSEUR, $f->id), $reglementId);
            }
        }

        $rows = array_values(array_filter($rows));
        if (!$includeSettled) {
            $rows = array_values(array_filter($rows, function ($row) use ($reglementId) {
                return $row['reste'] > 0.009 || ($reglementId && $row['montant_sur_reglement'] > 0);
            }));
        }

        return $rows;
    }

    public function syncAllocations(Reglement $reglement, array $lines, string $action = 'manuel', ?int $userId = null): void
    {
        $clean = $this->validateAllocations($reglement, $lines);
        $previous = $reglement->lignes()->get();
        $prevMap = [];
        foreach ($previous as $row) {
            $prevMap[$row->facture_type . ':' . $row->facture_id] = $row;
        }

        DB::transaction(function () use ($reglement, $clean, $action, $userId, $prevMap) {
            $reglement->lignes()->delete();
            $kept = [];
            foreach ($clean as $line) {
                $key = $line['facture_type'] . ':' . $line['facture_id'];
                $kept[$key] = true;
                ReglementLigne::create([
                    'reglement_id' => $reglement->id,
                    'facture_type' => $line['facture_type'],
                    'facture_id' => $line['facture_id'],
                    'montant' => $line['montant'],
                ]);
                $this->refreshInvoiceStatus($line['facture_type'], $line['facture_id']);
                $old = isset($prevMap[$key]) ? (float) $prevMap[$key]->montant : 0.0;
                if (abs($old - $line['montant']) > 0.009) {
                    $this->logLettrage($reglement, $line, $this->allocationAction($reglement, $line, $action), $userId);
                }
            }
            foreach ($prevMap as $key => $old) {
                if (isset($kept[$key])) {
                    continue;
                }
                $this->refreshInvoiceStatus($old->facture_type, (int) $old->facture_id);
                $invoice = $this->invoice($old->facture_type, (int) $old->facture_id);
                $this->logLettrage($reglement, [
                    'facture_type' => $old->facture_type,
                    'facture_id' => (int) $old->facture_id,
                    'montant' => (float) $old->montant,
                    'numero' => $invoice['numero'] ?? null,
                    'total' => $invoice['total'] ?? 0,
                ], 'delettrage', $userId, 'Affectation retirée');
            }
        });
    }

    private function allocationAction(Reglement $reglement, array $line, string $requested): string
    {
        if ($requested === 'auto') {
            return 'auto';
        }
        $reste = $this->money(($line['total'] ?? 0) - $this->paidFor($line['facture_type'], (int) $line['facture_id']));
        $unallocated = $this->money((float) $reglement->montant - (float) $reglement->lignes()->sum('montant'));

        return ($reste > 0.009 || $unallocated > 0.009) ? 'partiel' : 'manuel';
    }

    public function validateAllocations(Reglement $reglement, array $lines): array
    {
        $clean = [];
        $sum = 0.0;
        $seen = [];
        foreach ($lines as $line) {
            $montant = $this->money((float) ($line['montant'] ?? 0));
            if ($montant <= 0) {
                continue;
            }
            $type = (string) ($line['facture_type'] ?? '');
            $id = (int) ($line['facture_id'] ?? 0);
            $key = $type . ':' . $id;
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'lignes' => 'Une facture ne peut être affectée qu’une seule fois sur le même règlement.',
                ]);
            }
            $seen[$key] = true;
            $invoice = $this->invoice($type, $id);
            if (!$invoice) {
                throw ValidationException::withMessages(['lignes' => 'Facture introuvable.']);
            }
            $expected = $reglement->sens === Reglement::SENS_CLIENT
                ? [ReglementLigne::LOCALE, ReglementLigne::EXPORT]
                : [ReglementLigne::FOURNISSEUR];
            if (!in_array($type, $expected, true)) {
                throw ValidationException::withMessages(['lignes' => 'Ce type de facture ne correspond pas au règlement.']);
            }
            if ((int) $invoice['party_id'] !== (int) ($reglement->sens === Reglement::SENS_CLIENT ? $reglement->client_id : $reglement->fournisseur_id)) {
                throw ValidationException::withMessages(['lignes' => "La facture {$invoice['numero']} n’appartient pas au tiers du règlement."]);
            }
            if (!$invoice['payable']) {
                throw ValidationException::withMessages(['lignes' => "La facture {$invoice['numero']} n’est pas réglable (brouillon ou annulée)."]);
            }
            if (strtoupper($invoice['devise']) !== strtoupper((string) $reglement->devise)) {
                throw ValidationException::withMessages([
                    'lignes' => "La devise de la facture {$invoice['numero']} ({$invoice['devise']}) est différente de celle du règlement ({$reglement->devise}).",
                ]);
            }
            $paidElsewhere = $this->paidFor($type, $id, $reglement->id);
            $reste = $this->money($invoice['total'] - $paidElsewhere);
            if ($montant - $reste > 0.009) {
                throw ValidationException::withMessages([
                    'lignes' => "Le montant affecté dépasse le reste à payer de la facture {$invoice['numero']} ({$this->formatMoney($reste)} {$this->currencyLabel($invoice['devise'])}).",
                ]);
            }
            $sum += $montant;
            $clean[] = [
                'facture_type' => $type,
                'facture_id' => $id,
                'montant' => $montant,
                'numero' => $invoice['numero'],
                'total' => $invoice['total'],
            ];
        }

        if ($sum - (float) $reglement->montant > 0.009) {
            throw ValidationException::withMessages([
                'lignes' => 'Le total affecté dépasse le montant du règlement.',
            ]);
        }

        return $clean;
    }

    public function refreshInvoiceStatus(string $type, int $id): void
    {
        $paid = $this->paidFor($type, $id);
        if ($type === ReglementLigne::LOCALE) {
            $f = FactureLocale::find($id);
            if (!$f || in_array($f->statut, ['brouillon', 'annulee'], true)) {
                return;
            }
            $total = (float) $f->total_ttc;
            if ($total > 0 && $paid + 0.009 >= $total) {
                if ($f->statut !== 'payee') {
                    $f->statut = 'payee';
                    $f->save();
                }
            } elseif ($f->statut === 'payee') {
                $f->statut = 'validee';
                $f->save();
            }

            return;
        }

        if ($type === ReglementLigne::FOURNISSEUR) {
            $f = FactureFournisseur::find($id);
            if (!$f || $f->statut === 'brouillon') {
                return;
            }
            $total = (float) $f->total_ttc;
            if ($total > 0 && $paid + 0.009 >= $total) {
                if ($f->statut !== 'payee') {
                    $f->statut = 'payee';
                    $f->save();
                }
            } elseif ($f->statut === 'payee') {
                $f->statut = 'validee';
                $f->save();
            }
        }
    }

    public function refreshBalance(string $sens, ?int $partyId): array
    {
        if (!$partyId) {
            return ['solde' => 0, 'par_devise' => [], 'avoirs' => 0];
        }

        $snapshot = $this->partySnapshot($sens, $partyId);
        if ($sens === Reglement::SENS_CLIENT) {
            Client::where('id', $partyId)->update(['solde_actuel' => $snapshot['solde']]);
        } else {
            Fournisseur::where('id', $partyId)->update(['solde_actuel' => $snapshot['solde']]);
        }

        return $snapshot;
    }

    public function partySnapshot(string $sens, int $partyId): array
    {
        $invoices = $this->openInvoices($sens, $partyId, null, true);
        $nets = [];
        foreach ($invoices as $inv) {
            if ($inv['annulee']) {
                continue;
            }
            $devise = $inv['devise'] ?: 'MAD';
            $nets[$devise] = $this->money(($nets[$devise] ?? 0) + $inv['reste']);
        }

        $payments = Reglement::query()
            ->where($sens === Reglement::SENS_CLIENT ? 'client_id' : 'fournisseur_id', $partyId)
            ->withSum('lignes as affecte', 'montant')
            ->get();
        foreach ($payments as $payment) {
            $unallocated = $this->money((float) $payment->montant - (float) ($payment->affecte ?? 0));
            if ($unallocated <= 0) {
                continue;
            }
            $devise = $payment->devise ?: 'MAD';
            $nets[$devise] = $this->money(($nets[$devise] ?? 0) - $unallocated);
        }

        $avoirs = 0.0;
        if ($sens === Reglement::SENS_CLIENT) {
            $notes = NoteCredit::query()
                ->where('client_id', $partyId)
                ->whereIn('statut', ['validee', 'appliquee'])
                ->get(['devise', 'total_ttc']);
            foreach ($notes as $note) {
                $devise = $note->devise ?: 'MAD';
                $amount = (float) $note->total_ttc;
                $avoirs += $amount;
                $nets[$devise] = $this->money(($nets[$devise] ?? 0) - $amount);
            }
        }

        $party = $sens === Reglement::SENS_CLIENT ? Client::find($partyId) : Fournisseur::find($partyId);
        $primary = strtoupper((string) ($party->devise ?? 'MAD')) ?: 'MAD';
        if (!isset($nets[$primary]) && count($nets) === 1) {
            $solde = (float) reset($nets);
        } else {
            $solde = (float) ($nets[$primary] ?? $nets['MAD'] ?? 0);
        }

        return [
            'solde' => $this->money($solde),
            'par_devise' => $nets,
            'avoirs' => $this->money($avoirs),
            'devise' => $party->devise ?: 'MAD',
            'nom' => $party->nom ?? null,
            'delai_paiement' => $sens === Reglement::SENS_CLIENT ? (int) ($party->delai_paiement ?? 0) : null,
        ];
    }

    public function lettrageAuto(string $sens, int $partyId, ?int $userId = null): array
    {
        $created = [];
        DB::transaction(function () use ($sens, $partyId, $userId, &$created) {
            $invoices = $this->openInvoices($sens, $partyId);
            usort($invoices, function ($a, $b) {
                return strcmp((string) ($a['echeance'] ?: $a['date']), (string) ($b['echeance'] ?: $b['date']));
            });
            $payments = Reglement::query()
                ->where('sens', $sens)
                ->where($sens === Reglement::SENS_CLIENT ? 'client_id' : 'fournisseur_id', $partyId)
                ->withSum('lignes as affecte', 'montant')
                ->orderBy('date_reglement')
                ->orderBy('id')
                ->get();

            foreach ($payments as $payment) {
                $available = $this->money((float) $payment->montant - (float) ($payment->affecte ?? 0));
                if ($available <= 0.009) {
                    continue;
                }
                foreach ($invoices as &$invoice) {
                    if ($available <= 0.009) {
                        break;
                    }
                    if (($invoice['reste'] ?? 0) <= 0.009) {
                        continue;
                    }
                    if (strtoupper($invoice['devise']) !== strtoupper((string) $payment->devise)) {
                        continue;
                    }
                    $take = $this->money(min($available, $invoice['reste']));
                    $partial = $take + 0.009 < $invoice['reste'] || $take + 0.009 < $available;
                    ReglementLigne::create([
                        'reglement_id' => $payment->id,
                        'facture_type' => $invoice['facture_type'],
                        'facture_id' => $invoice['facture_id'],
                        'montant' => $take,
                    ]);
                    $invoice['reste'] = $this->money($invoice['reste'] - $take);
                    $available = $this->money($available - $take);
                    $payment->affecte = (float) ($payment->affecte ?? 0) + $take;
                    $this->refreshInvoiceStatus($invoice['facture_type'], $invoice['facture_id']);
                    $this->logLettrage($payment, [
                        'facture_type' => $invoice['facture_type'],
                        'facture_id' => $invoice['facture_id'],
                        'montant' => $take,
                        'numero' => $invoice['numero'],
                        'total' => $invoice['total'],
                    ], $partial ? 'partiel' : 'auto', $userId);
                    $created[] = [
                        'reglement' => $payment->numero,
                        'facture' => $invoice['numero'],
                        'montant' => $take,
                    ];
                }
                unset($invoice);
            }
        });

        $this->refreshBalance($sens, $partyId);

        return $created;
    }

    public function lettrageManuel(Reglement $reglement, string $type, int $factureId, float $montant, ?int $userId = null): ReglementLigne
    {
        $existing = $reglement->lignes()
            ->where('facture_type', $type)
            ->where('facture_id', $factureId)
            ->first();
        $lines = $reglement->lignes()->get()->map(fn ($l) => [
            'facture_type' => $l->facture_type,
            'facture_id' => $l->facture_id,
            'montant' => (float) $l->montant,
        ])->all();

        $found = false;
        foreach ($lines as &$line) {
            if ($line['facture_type'] === $type && (int) $line['facture_id'] === $factureId) {
                $line['montant'] = $this->money((float) $line['montant'] + $montant);
                $found = true;
            }
        }
        unset($line);
        if (!$found) {
            $lines[] = ['facture_type' => $type, 'facture_id' => $factureId, 'montant' => $this->money($montant)];
        }

        $invoice = $this->invoice($type, $factureId);
        $resteAvant = $invoice ? $this->money($invoice['total'] - $this->paidFor($type, $factureId)) : 0;
        $action = $montant + 0.009 < $resteAvant ? 'partiel' : 'manuel';
        $this->syncAllocations($reglement, $lines, $action, $userId);
        $this->refreshBalance($reglement->sens, $reglement->sens === Reglement::SENS_CLIENT ? $reglement->client_id : $reglement->fournisseur_id);

        return $reglement->lignes()->where('facture_type', $type)->where('facture_id', $factureId)->first()
            ?? $existing;
    }

    public function delettrage(ReglementLigne $ligne, ?int $userId = null): void
    {
        $reglement = $ligne->reglement()->first();
        $type = $ligne->facture_type;
        $factureId = (int) $ligne->facture_id;
        $montant = (float) $ligne->montant;
        $invoice = $this->invoice($type, $factureId);
        $ligne->delete();
        $this->refreshInvoiceStatus($type, $factureId);
        if ($reglement && $invoice) {
            $this->logLettrage($reglement, [
                'facture_type' => $type,
                'facture_id' => $factureId,
                'montant' => $montant,
                'numero' => $invoice['numero'],
                'total' => $invoice['total'],
            ], 'delettrage', $userId, 'Lettrage annulé');
            $this->refreshBalance(
                $reglement->sens,
                $reglement->sens === Reglement::SENS_CLIENT ? $reglement->client_id : $reglement->fournisseur_id
            );
        }
    }

    public function presentReglement(Reglement $reglement): array
    {
        $reglement->loadMissing(['client', 'fournisseur', 'compte', 'lignes']);
        $affecte = $this->money((float) $reglement->lignes->sum('montant'));
        $factures = [];
        foreach ($reglement->lignes as $ligne) {
            $invoice = $this->invoice($ligne->facture_type, (int) $ligne->facture_id);
            $factures[] = [
                'id' => $ligne->id,
                'facture_type' => $ligne->facture_type,
                'facture_id' => $ligne->facture_id,
                'numero' => $invoice['numero'] ?? ('#' . $ligne->facture_id),
                'montant' => (float) $ligne->montant,
                'total' => $invoice['total'] ?? null,
                'reste_facture' => $invoice ? $this->money($invoice['total'] - $this->paidFor($ligne->facture_type, (int) $ligne->facture_id)) : null,
            ];
        }
        $partyId = $reglement->sens === Reglement::SENS_CLIENT ? $reglement->client_id : $reglement->fournisseur_id;

        return [
            'id' => $reglement->id,
            'numero' => $reglement->numero,
            'sens' => $reglement->sens,
            'date_reglement' => $reglement->date_reglement?->toDateString(),
            'mode_paiement' => $reglement->mode_paiement,
            'mode_label' => $this->modeLabel($reglement->mode_paiement),
            'montant' => (float) $reglement->montant,
            'montant_affecte' => $affecte,
            'reste' => $this->money((float) $reglement->montant - $affecte),
            'devise' => $reglement->devise ?: 'MAD',
            'reference' => $reglement->reference,
            'notes' => $reglement->notes,
            'client_id' => $reglement->client_id,
            'fournisseur_id' => $reglement->fournisseur_id,
            'partie' => $reglement->sens === Reglement::SENS_CLIENT ? $reglement->client?->nom : $reglement->fournisseur?->nom,
            'compte_bancaire_id' => $reglement->compte_bancaire_id,
            'compte' => $reglement->compte?->nom_compte,
            'factures' => $factures,
            'solde_tiers' => $partyId ? $this->partySnapshot($reglement->sens, (int) $partyId) : null,
            'created_at' => $reglement->created_at?->toDateTimeString(),
        ];
    }

    public function agingBucket(int $joursRetard, bool $settled): string
    {
        if ($settled) {
            return 'reglee';
        }
        if ($joursRetard <= 0) {
            return 'a_echeance';
        }
        if ($joursRetard <= 30) {
            return '1_30';
        }
        if ($joursRetard <= 60) {
            return '31_60';
        }
        if ($joursRetard <= 90) {
            return '61_90';
        }

        return '90_plus';
    }

    public function agingLabels(): array
    {
        return [
            'a_echeance' => 'À échéance',
            '1_30' => '1–30 jours',
            '31_60' => '31–60 jours',
            '61_90' => '61–90 jours',
            '90_plus' => '90+ jours',
            'reglee' => 'Réglée',
        ];
    }

    public function decorateDeadline(array $invoice, int $horizon = 7): array
    {
        $paid = $this->paidFor($invoice['facture_type'], $invoice['facture_id']);
        $reste = $this->money(max(0, $invoice['total'] - $paid));
        $settled = $reste <= 0.009 && $invoice['total'] > 0;
        $echeance = $invoice['echeance'] ? Carbon::parse($invoice['echeance'])->startOfDay() : null;
        $today = now()->startOfDay();
        $jours = 0;
        if (!$settled && $echeance && $echeance->lt($today)) {
            $jours = (int) $echeance->diffInDays($today);
        }
        $bucket = $this->agingBucket($jours, $settled);
        $statutPaiement = 'impayee';
        if ($invoice['annulee']) {
            $statutPaiement = 'annulee';
        } elseif ($settled) {
            $statutPaiement = 'payee';
        } elseif ($paid > 0.009) {
            $statutPaiement = 'partielle';
        }
        $bientot = !$settled && $echeance && $echeance->gte($today) && $echeance->lte($today->copy()->addDays($horizon));

        return array_merge($invoice, [
            'montant_regle' => $paid,
            'reste' => $reste,
            'jours_retard' => $jours,
            'aging' => $bucket,
            'aging_label' => $this->agingLabels()[$bucket] ?? $bucket,
            'statut_paiement' => $statutPaiement,
            'echeance_proche' => $bientot,
            'en_retard' => $jours > 0,
        ]);
    }

    private function logLettrage(Reglement $reglement, array $line, string $action, ?int $userId, ?string $comment = null): void
    {
        $paid = $this->paidFor($line['facture_type'], (int) $line['facture_id']);
        $reste = $this->money(($line['total'] ?? 0) - $paid);
        $affecte = $this->money((float) $reglement->lignes()->sum('montant'));
        $ecart = $this->money((float) $reglement->montant - $affecte);

        LettrageHistorique::create([
            'sens' => $reglement->sens,
            'client_id' => $reglement->client_id,
            'fournisseur_id' => $reglement->fournisseur_id,
            'action' => $action,
            'reglement_id' => $reglement->id,
            'facture_type' => $line['facture_type'],
            'facture_id' => $line['facture_id'],
            'facture_numero' => $line['numero'] ?? null,
            'reglement_numero' => $reglement->numero,
            'montant_facture' => $line['total'] ?? 0,
            'montant_reglement' => $reglement->montant,
            'montant_lettre' => $line['montant'],
            'reste' => max(0, $reste),
            'ecart' => $ecart,
            'commentaire' => $comment,
            'created_by' => $userId,
        ]);
    }

    private function presentPayable(?array $invoice, ?int $reglementId): ?array
    {
        if (!$invoice) {
            return null;
        }
        $paidElsewhere = $this->paidFor($invoice['facture_type'], $invoice['facture_id'], $reglementId);
        $onThis = 0.0;
        if ($reglementId) {
            $onThis = $this->money((float) ReglementLigne::query()
                ->where('reglement_id', $reglementId)
                ->where('facture_type', $invoice['facture_type'])
                ->where('facture_id', $invoice['facture_id'])
                ->sum('montant'));
        }
        $disponible = $this->money(max(0, $invoice['total'] - $paidElsewhere));
        $reste = $this->money(max(0, $disponible - $onThis));

        return array_merge($invoice, [
            'montant_regle' => $this->money($paidElsewhere + $onThis),
            'montant_sur_reglement' => $onThis,
            'disponible' => $disponible,
            'reste' => $reste,
        ]);
    }

    private function normalizeInvoice(array $invoice): array
    {
        $invoice['total_ht'] = $this->money($invoice['total_ht']);
        $invoice['total_tva'] = $this->money($invoice['total_tva']);
        $invoice['total'] = $this->money($invoice['total']);
        $invoice['devise'] = strtoupper($invoice['devise'] ?: 'MAD');

        return $invoice;
    }

    private function money(float $n): float
    {
        return round($n, 2);
    }

    public function formatMoney(float $n): string
    {
        return number_format($n, 2, ',', ' ');
    }

    public function bankBalance(CompteBancaire $compte): float
    {
        $credit = (float) $compte->mouvements()->sum('credit');
        $debit = (float) $compte->mouvements()->sum('debit');

        return $this->money((float) $compte->solde_ouverture + $credit - $debit);
    }

    public function erpBalance(CompteBancaire $compte): float
    {
        $in = (float) Reglement::query()
            ->where('compte_bancaire_id', $compte->id)
            ->where('sens', Reglement::SENS_CLIENT)
            ->where('devise', $compte->devise ?: 'MAD')
            ->sum('montant');
        $out = (float) Reglement::query()
            ->where('compte_bancaire_id', $compte->id)
            ->where('sens', Reglement::SENS_FOURNISSEUR)
            ->where('devise', $compte->devise ?: 'MAD')
            ->sum('montant');

        return $this->money((float) $compte->solde_ouverture + $in - $out);
    }
}
