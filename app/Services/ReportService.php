<?php

namespace App\Services;

use App\Models\CompteBancaire;
use App\Models\Exportation;
use App\Models\FactureFournisseur;
use App\Models\FactureLocale;
use App\Models\MouvementBancaire;
use App\Models\NoteCredit;
use App\Models\Reglement;
use App\Models\ReglementLigne;
use App\Models\StockBalance;
use App\Models\StockMouvement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function ventes(array $filters): array
    {
        $docs = $this->salesDocuments($filters);
        $active = array_values(array_filter($docs, fn ($d) => !$d['annulee']));
        $scoped = $this->scopeLines($active, $filters);
        $kpis = $this->sumDocs($scoped);
        $cancelled = array_values(array_filter($docs, fn ($d) => $d['annulee']));
        $cancelledSum = $this->sumDocs($cancelled);
        $paid = 0.0;
        $unpaid = 0.0;
        $partialCount = 0;
        $paidCount = 0;
        $unpaidCount = 0;
        foreach ($scoped as $doc) {
            if ($doc['statut_paiement'] === 'payee') {
                $paid += $doc['ttc'];
                $paidCount++;
            } elseif ($doc['statut_paiement'] === 'partielle') {
                $paid += $doc['paid'];
                $unpaid += $doc['reste'];
                $partialCount++;
            } else {
                $unpaid += $doc['reste'];
                $unpaidCount++;
            }
        }
        $count = count($scoped);
        $avoirs = $this->avoirs($filters);

        return [
            'title' => 'Rapport des ventes',
            'filters' => $filters,
            'kpis' => [
                'total_ht' => $kpis['ht'],
                'total_tva' => $kpis['tva'],
                'total_ttc' => $kpis['ttc'],
                'total_regle' => round($paid, 2),
                'total_impaye' => round($unpaid, 2),
                'nombre' => $count,
                'moyenne' => $count > 0 ? round($kpis['ttc'] / $count, 2) : 0,
                'annulees' => count($cancelled),
                'annulees_ttc' => $cancelledSum['ttc'],
                'payees' => $paidCount,
                'partielles' => $partialCount,
                'impayees' => $unpaidCount,
                'avoirs_ttc' => $avoirs,
            ],
            'series' => $this->series($scoped, $filters['regroupement'] ?? 'month'),
            'par_client' => $this->group($scoped, 'client'),
            'par_produit' => $this->groupLines($scoped, 'designation'),
            'par_categorie' => $this->groupLines($scoped, 'categorie'),
            'par_mode' => $this->group($scoped, 'mode_label'),
            'par_reglement' => $this->encaissements($filters, Reglement::SENS_CLIENT),
            'factures' => array_map(fn ($d) => $this->publicDoc($d), $docs),
            'taux' => $this->tvaBreakdown($scoped),
        ];
    }

    public function achats(array $filters): array
    {
        $docs = $this->purchaseDocuments($filters);
        $scoped = $this->scopeLines($docs, $filters);
        $kpis = $this->sumDocs($scoped);
        $paid = 0.0;
        $unpaid = 0.0;
        $partial = 0;
        $paidCount = 0;
        $unpaidCount = 0;
        foreach ($scoped as $doc) {
            if ($doc['statut_paiement'] === 'payee') {
                $paid += $doc['ttc'];
                $paidCount++;
            } elseif ($doc['statut_paiement'] === 'partielle') {
                $paid += $doc['paid'];
                $unpaid += $doc['reste'];
                $partial++;
            } else {
                $unpaid += $doc['reste'];
                $unpaidCount++;
            }
        }
        $count = count($scoped);

        return [
            'title' => 'Rapport des achats',
            'filters' => $filters,
            'kpis' => [
                'total_ht' => $kpis['ht'],
                'total_tva' => $kpis['tva'],
                'total_ttc' => $kpis['ttc'],
                'total_regle' => round($paid, 2),
                'total_impaye' => round($unpaid, 2),
                'nombre' => $count,
                'payees' => $paidCount,
                'partielles' => $partial,
                'impayees' => $unpaidCount,
            ],
            'series' => $this->series($scoped, $filters['regroupement'] ?? 'month'),
            'par_fournisseur' => $this->group($scoped, 'partie'),
            'par_produit' => $this->groupLines($scoped, 'designation'),
            'par_categorie' => $this->groupLines($scoped, 'categorie'),
            'par_mode' => $this->group($scoped, 'mode_label'),
            'par_reglement' => $this->encaissements($filters, Reglement::SENS_FOURNISSEUR),
            'factures' => array_map(fn ($d) => $this->publicDoc($d), $docs),
            'taux' => $this->tvaBreakdown($scoped),
        ];
    }

    public function stock(array $filters): array
    {
        $costs = $this->unitCosts();
        $balances = StockBalance::with(['article', 'location'])->get();
        $rows = [];
        foreach ($balances as $balance) {
            $article = $balance->article;
            if (!$article) {
                continue;
            }
            if (!empty($filters['article_id']) && (int) $article->id !== (int) $filters['article_id']) {
                continue;
            }
            if (!empty($filters['location_id']) && (int) $balance->stock_location_id !== (int) $filters['location_id']) {
                continue;
            }
            $famille = $article->famille ?: 'Non classée';
            if (!empty($filters['categorie']) && $famille !== $filters['categorie']) {
                continue;
            }
            if (!empty($filters['search'])) {
                $hay = mb_strtolower($article->code_article . ' ' . $article->designation . ' ' . $balance->lot);
                if (!str_contains($hay, mb_strtolower($filters['search']))) {
                    continue;
                }
            }
            $qty = (float) $balance->stock_theorique;
            $cost = $costs[$article->id] ?? null;
            $unit = $cost['prix'] ?? (float) $article->prix_vente;
            $source = $cost['source'] ?? 'prix_vente';
            $rows[] = [
                'article_id' => $article->id,
                'code' => $article->code_article,
                'designation' => $article->designation,
                'categorie' => $famille,
                'emplacement' => $balance->location?->nom,
                'emplacement_id' => $balance->stock_location_id,
                'lot' => $balance->lot,
                'stock_theorique' => $qty,
                'stock_reserve' => (float) $balance->stock_reserve,
                'stock_disponible' => (float) $balance->stock_disponible,
                'entrees' => (float) $balance->entrees,
                'sorties' => (float) $balance->sorties,
                'prix' => round((float) $unit, 4),
                'valeur' => round($qty * (float) $unit, 2),
                'source_prix' => $source,
                'minimum' => (int) ($article->minimum_commande ?? 0),
            ];
        }

        $byArticle = [];
        foreach ($rows as $row) {
            $id = $row['article_id'];
            if (!isset($byArticle[$id])) {
                $byArticle[$id] = $row;
                $byArticle[$id]['stock_theorique'] = 0;
                $byArticle[$id]['valeur'] = 0;
                $byArticle[$id]['emplacements'] = [];
            }
            $byArticle[$id]['stock_theorique'] += $row['stock_theorique'];
            $byArticle[$id]['valeur'] += $row['valeur'];
            $byArticle[$id]['emplacements'][] = $row['emplacement'];
        }
        $faibles = 0;
        $ruptures = 0;
        foreach ($byArticle as $article) {
            if ($article['stock_theorique'] <= 0) {
                $ruptures++;
            } elseif ($article['minimum'] > 0 && $article['stock_theorique'] <= $article['minimum']) {
                $faibles++;
            }
        }

        $situation = $filters['situation'] ?? '';
        $visible = $rows;
        if ($situation === 'rupture') {
            $ids = collect($byArticle)->filter(fn ($a) => $a['stock_theorique'] <= 0)->keys()->all();
            $visible = array_values(array_filter($rows, fn ($r) => in_array($r['article_id'], $ids, true)));
        } elseif ($situation === 'faible') {
            $ids = collect($byArticle)->filter(fn ($a) => $a['stock_theorique'] > 0 && $a['minimum'] > 0 && $a['stock_theorique'] <= $a['minimum'])->keys()->all();
            $visible = array_values(array_filter($rows, fn ($r) => in_array($r['article_id'], $ids, true)));
        } elseif ($situation === 'disponible') {
            $visible = array_values(array_filter($rows, fn ($r) => $r['stock_disponible'] > 0));
        }

        $movements = $this->stockMovements($filters);

        return [
            'title' => 'Rapport de stock',
            'filters' => $filters,
            'valorisation' => 'Quantité théorique (stock existant) × dernier prix d’achat connu, sinon prix de vente de l’article.',
            'alerte' => 'Rupture : stock théorique ≤ 0. Stock faible : stock théorique > 0 et ≤ minimum de commande de l’article, lorsqu’il est renseigné.',
            'kpis' => [
                'produits' => count($byArticle),
                'quantite' => round(array_sum(array_column($rows, 'stock_theorique')), 3),
                'valeur' => round(array_sum(array_column($rows, 'valeur')), 2),
                'faibles' => $faibles,
                'ruptures' => $ruptures,
            ],
            'situation' => $visible,
            'par_produit' => array_values(array_map(function ($a) {
                $a['stock_theorique'] = round($a['stock_theorique'], 3);
                $a['valeur'] = round($a['valeur'], 2);
                $a['emplacement'] = implode(', ', array_unique(array_filter($a['emplacements'])));
                unset($a['emplacements']);

                return $a;
            }, $byArticle)),
            'par_emplacement' => $this->groupStock($rows, 'emplacement'),
            'par_categorie' => $this->groupStock($rows, 'categorie'),
            'mouvements' => $movements['rows'],
            'entrees' => $movements['entrees'],
            'sorties' => $movements['sorties'],
        ];
    }

    public function finance(array $filters): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $sales = $this->ventes($filters);
        $purchases = $this->achats($filters);

        $clientNets = $this->partyNets(Reglement::SENS_CLIENT);
        $supplierNets = $this->partyNets(Reglement::SENS_FOURNISSEUR);
        $receivables = round(collect($clientNets)->sum(fn ($n) => max(0, $n['MAD'] ?? 0)), 2);
        $payables = round(collect($supplierNets)->sum(fn ($n) => max(0, $n['MAD'] ?? 0)), 2);
        $receivablesDevises = $this->sumPositive($clientNets);
        $payablesDevises = $this->sumPositive($supplierNets);

        $collected = (float) $this->paymentQuery(Reglement::SENS_CLIENT, $from, $to)->sum('montant');
        $paidOut = (float) $this->paymentQuery(Reglement::SENS_FOURNISSEUR, $from, $to)->sum('montant');
        $caDevises = $this->totalsByDevise($sales['factures'] ?? []);
        $achatDevises = $this->totalsByDevise($purchases['factures'] ?? []);
        $deviseCa = count($caDevises) === 1 ? array_key_first($caDevises) : 'MAD';
        $deviseAchats = count($achatDevises) === 1 ? array_key_first($achatDevises) : 'MAD';

        $cashIn = (float) $this->paymentQuery(Reglement::SENS_CLIENT, null, null)->where('mode_paiement', 'especes')->sum('montant');
        $cashOut = (float) $this->paymentQuery(Reglement::SENS_FOURNISSEUR, null, null)->where('mode_paiement', 'especes')->sum('montant');
        $cash = round($cashIn - $cashOut, 2);

        $bank = 0.0;
        $accounts = [];
        foreach (CompteBancaire::where('actif', true)->get() as $compte) {
            $solde = $this->finance->bankBalance($compte);
            $bank += $solde;
            $accounts[] = [
                'id' => $compte->id,
                'nom' => $compte->nom_banque . ' — ' . $compte->nom_compte,
                'devise' => $compte->devise,
                'solde' => $solde,
            ];
        }

        $overdueClients = $this->overdueTotal(Reglement::SENS_CLIENT);
        $overdueSuppliers = $this->overdueTotal(Reglement::SENS_FOURNISSEUR);

        $history = Reglement::with(['client', 'fournisseur'])
            ->when($from, fn ($q) => $q->whereDate('date_reglement', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_reglement', '<=', $to))
            ->orderByDesc('date_reglement')
            ->limit(200)
            ->get()
            ->map(fn (Reglement $r) => [
                'date' => $r->date_reglement?->toDateString(),
                'numero' => $r->numero,
                'sens' => $r->sens,
                'partie' => $r->sens === Reglement::SENS_CLIENT ? $r->client?->nom : $r->fournisseur?->nom,
                'mode' => $this->finance->modeLabel($r->mode_paiement),
                'montant' => (float) $r->montant,
                'devise' => $r->devise,
                'reference' => $r->reference,
            ]);

        $cashMoves = Reglement::with(['client', 'fournisseur'])
            ->where('mode_paiement', 'especes')
            ->when($from, fn ($q) => $q->whereDate('date_reglement', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_reglement', '<=', $to))
            ->orderByDesc('date_reglement')
            ->limit(200)
            ->get()
            ->map(fn (Reglement $r) => [
                'date' => $r->date_reglement?->toDateString(),
                'sens' => $r->sens === Reglement::SENS_CLIENT ? 'Encaissement' : 'Décaissement',
                'partie' => $r->sens === Reglement::SENS_CLIENT ? $r->client?->nom : $r->fournisseur?->nom,
                'montant' => (float) $r->montant,
                'reference' => $r->reference,
                'numero' => $r->numero,
            ]);

        $bankMoves = DB::table('mouvements_bancaires as m')
            ->join('comptes_bancaires as c', 'c.id', '=', 'm.compte_bancaire_id')
            ->when($from, fn ($q) => $q->whereDate('m.date_operation', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('m.date_operation', '<=', $to))
            ->orderByDesc('m.date_operation')
            ->limit(200)
            ->get(['m.date_operation', 'm.description', 'm.reference', 'm.debit', 'm.credit', 'm.type', 'c.nom_compte', 'c.nom_banque'])
            ->map(fn ($m) => [
                'date' => substr((string) $m->date_operation, 0, 10),
                'compte' => $m->nom_banque . ' — ' . $m->nom_compte,
                'description' => $m->description,
                'reference' => $m->reference,
                'debit' => (float) $m->debit,
                'credit' => (float) $m->credit,
                'type' => MouvementBancaire::TYPES[$m->type] ?? $m->type,
            ]);

        return [
            'title' => 'Tableau de bord finance',
            'filters' => $filters,
            'kpis' => [
                'chiffre_affaires' => $deviseCa === 'MAD' && count($caDevises) > 1 ? ($caDevises['MAD'] ?? 0) : ($caDevises[$deviseCa] ?? 0),
                'achats' => $deviseAchats === 'MAD' && count($achatDevises) > 1 ? ($achatDevises['MAD'] ?? 0) : ($achatDevises[$deviseAchats] ?? 0),
                'creances' => $receivables,
                'dettes' => $payables,
                'caisse' => $cash,
                'banque' => round($bank, 2),
                'retard_clients' => $overdueClients,
                'retard_fournisseurs' => $overdueSuppliers,
                'encaisse' => round($collected, 2),
                'decaisse' => round($paidOut, 2),
            ],
            'creances_devises' => $receivablesDevises,
            'dettes_devises' => $payablesDevises,
            'ca_devises' => $caDevises,
            'achats_devises' => $achatDevises,
            'devise_ca' => $deviseCa,
            'devise_achats' => $deviseAchats,
            'comptes' => $accounts,
            'historique' => $history,
            'caisse_mouvements' => $cashMoves,
            'banque_mouvements' => $bankMoves,
            'tva' => $this->tvaSummary($sales, $purchases),
            'charts' => [
                'ventes' => $sales['series'],
                'achats' => $purchases['series'],
                'creances_dettes' => [
                    ['label' => 'Créances clients', 'value' => $receivables],
                    ['label' => 'Dettes fournisseurs', 'value' => $payables],
                ],
                'tresorerie' => $this->cashflowSeries($filters),
                'modes' => $sales['par_reglement'],
            ],
        ];
    }

    public function document(string $report, array $data): array
    {
        $kpis = [];
        foreach (($data['kpis'] ?? []) as $key => $value) {
            $kpis[] = ['label' => $this->kpiLabel($key), 'value' => is_numeric($value) ? $value : $value];
        }
        $sections = match ($report) {
            'ventes' => [
                $this->section('Évolution', ['Période', 'HT', 'TVA', 'TTC', 'Factures'], $data['series'] ?? [], ['periode', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Par client', ['Client', 'HT', 'TVA', 'TTC', 'Factures'], $data['par_client'] ?? [], ['label', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Par produit', ['Produit', 'Quantité', 'HT', 'TVA', 'TTC'], $data['par_produit'] ?? [], ['label', 'quantite', 'ht', 'tva', 'ttc']),
                $this->section('Par catégorie', ['Catégorie', 'HT', 'TVA', 'TTC'], $data['par_categorie'] ?? [], ['label', 'ht', 'tva', 'ttc']),
                $this->section('Par mode de paiement (factures)', ['Mode', 'HT', 'TVA', 'TTC', 'Factures'], $data['par_mode'] ?? [], ['label', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Encaissements', ['Mode', 'Montant', 'Nombre'], $data['par_reglement'] ?? [], ['label', 'montant', 'nombre']),
                $this->section('Factures', ['Numéro', 'Date', 'Client', 'Statut', 'Règlement', 'HT', 'TVA', 'TTC', 'Réglé', 'Reste', 'Devise'], $data['factures'] ?? [], ['numero', 'date', 'partie', 'statut', 'statut_paiement', 'ht', 'tva', 'ttc', 'paid', 'reste', 'devise']),
            ],
            'achats' => [
                $this->section('Évolution', ['Période', 'HT', 'TVA', 'TTC', 'Factures'], $data['series'] ?? [], ['periode', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Par fournisseur', ['Fournisseur', 'HT', 'TVA', 'TTC', 'Factures'], $data['par_fournisseur'] ?? [], ['label', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Par produit', ['Produit', 'Quantité', 'HT', 'TVA', 'TTC'], $data['par_produit'] ?? [], ['label', 'quantite', 'ht', 'tva', 'ttc']),
                $this->section('Par catégorie', ['Catégorie', 'HT', 'TVA', 'TTC'], $data['par_categorie'] ?? [], ['label', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Par mode de paiement', ['Mode', 'HT', 'TVA', 'TTC', 'Factures'], $data['par_mode'] ?? [], ['label', 'ht', 'tva', 'ttc', 'nombre']),
                $this->section('Décaissements', ['Mode', 'Montant', 'Nombre'], $data['par_reglement'] ?? [], ['label', 'montant', 'nombre']),
                $this->section('Factures', ['Numéro', 'Date', 'Fournisseur', 'Statut', 'Règlement', 'HT', 'TVA', 'TTC', 'Réglé', 'Reste', 'Devise'], $data['factures'] ?? [], ['numero', 'date', 'partie', 'statut', 'statut_paiement', 'ht', 'tva', 'ttc', 'paid', 'reste', 'devise']),
            ],
            'stock' => [
                $this->section('Situation', ['Code', 'Produit', 'Catégorie', 'Emplacement', 'Lot', 'Théorique', 'Réservé', 'Disponible', 'Prix', 'Valeur'], $data['situation'] ?? [], ['code', 'designation', 'categorie', 'emplacement', 'lot', 'stock_theorique', 'stock_reserve', 'stock_disponible', 'prix', 'valeur']),
                $this->section('Par produit', ['Code', 'Produit', 'Catégorie', 'Quantité', 'Valeur'], $data['par_produit'] ?? [], ['code', 'designation', 'categorie', 'stock_theorique', 'valeur']),
                $this->section('Par emplacement', ['Emplacement', 'Quantité', 'Valeur'], $data['par_emplacement'] ?? [], ['label', 'quantite', 'valeur']),
                $this->section('Par catégorie', ['Catégorie', 'Quantité', 'Valeur'], $data['par_categorie'] ?? [], ['label', 'quantite', 'valeur']),
                $this->section('Mouvements', ['Date', 'Type', 'Produit', 'Quantité', 'Emplacement', 'Document', 'Lot'], $data['mouvements'] ?? [], ['date', 'type', 'produit', 'quantite', 'emplacement', 'document', 'lot']),
            ],
            default => [
                $this->section('Créances / dettes', ['Indicateur', 'Montant'], $data['charts']['creances_dettes'] ?? [], ['label', 'value']),
                $this->section('Comptes bancaires', ['Compte', 'Devise', 'Solde'], $data['comptes'] ?? [], ['nom', 'devise', 'solde']),
                $this->section('TVA', ['Taux', 'Collectée', 'Déductible', 'Solde'], $data['tva']['lignes'] ?? [], ['taux', 'collectee', 'deductible', 'solde']),
                $this->section('Historique des règlements', ['Date', 'Numéro', 'Sens', 'Tiers', 'Mode', 'Montant', 'Devise', 'Référence'], $data['historique'] ?? [], ['date', 'numero', 'sens', 'partie', 'mode', 'montant', 'devise', 'reference']),
                $this->section('Mouvements de caisse', ['Date', 'Sens', 'Tiers', 'Montant', 'Référence', 'Numéro'], $data['caisse_mouvements'] ?? [], ['date', 'sens', 'partie', 'montant', 'reference', 'numero']),
                $this->section('Mouvements bancaires', ['Date', 'Compte', 'Description', 'Référence', 'Débit', 'Crédit', 'Type'], $data['banque_mouvements'] ?? [], ['date', 'compte', 'description', 'reference', 'debit', 'credit', 'type']),
            ],
        };

        return [
            'title' => $data['title'] ?? 'Rapport',
            'generated_at' => now()->format('d/m/Y H:i'),
            'company' => $this->finance->company(),
            'kpis' => $kpis,
            'sections' => $sections,
            'note' => $data['valorisation'] ?? ($data['alerte'] ?? null),
        ];
    }

    private function salesDocuments(array $filters): array
    {
        $paidMap = $this->paidMap();
        $docs = [];
        $query = FactureLocale::with(['client', 'lignes.article']);
        $this->applyDocFilters($query, $filters, 'date_facture', 'client_id');
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        } else {
            $query->where('statut', '!=', 'brouillon');
        }
        foreach ($query->orderBy('date_facture')->get() as $facture) {
            $docs[] = $this->mapLocale($facture, $paidMap);
        }

        if (empty($filters['statut']) || $filters['statut'] === 'validee') {
            $exports = Exportation::with(['client', 'lignes.article'])
                ->whereNotNull('numero_facture')
                ->where('numero_facture', '!=', '');
            if (!empty($filters['client_id'])) {
                $exports->where('client_id', $filters['client_id']);
            }
            if (!empty($filters['from'])) {
                $exports->whereDate('date_creation', '>=', $filters['from']);
            }
            if (!empty($filters['to'])) {
                $exports->whereDate('date_creation', '<=', $filters['to']);
            }
            foreach ($exports->orderBy('date_creation')->get() as $export) {
                $docs[] = $this->mapExport($export, $paidMap);
            }
        }

        return $this->filterPayment($docs, $filters);
    }

    private function purchaseDocuments(array $filters): array
    {
        $paidMap = $this->paidMap();
        $query = FactureFournisseur::with(['fournisseur', 'lignes.article']);
        $this->applyDocFilters($query, $filters, 'date_facture', 'fournisseur_id', 'fournisseur_id');
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        } else {
            $query->where('statut', '!=', 'brouillon');
        }
        $docs = [];
        foreach ($query->orderBy('date_facture')->get() as $facture) {
            $docs[] = $this->mapFournisseur($facture, $paidMap);
        }

        return $this->filterPayment($docs, $filters);
    }

    private function mapLocale(FactureLocale $facture, array $paidMap): array
    {
        $paid = (float) ($paidMap[ReglementLigne::LOCALE . ':' . $facture->id] ?? 0);
        $lines = [];
        foreach ($facture->lignes as $ligne) {
            $lines[] = $this->line(
                $ligne->article_id,
                $ligne->designation ?: $ligne->article?->designation,
                $ligne->article?->famille,
                (float) $ligne->quantite,
                (float) $ligne->montant_ht,
                (float) $ligne->montant_tva,
                (float) $ligne->montant_ttc,
                (float) $ligne->tva_taux
            );
        }

        return $this->doc('locale', $facture->id, $facture->numero, $facture->date_facture?->toDateString(), $facture->client_id, $facture->client?->nom, (float) $facture->total_ht, (float) $facture->total_tva, (float) $facture->total_ttc, $paid, $facture->statut, $facture->statut === 'annulee', $facture->mode_paiement, $facture->devise ?: 'MAD', $lines);
    }

    private function mapExport(Exportation $export, array $paidMap): array
    {
        $paid = (float) ($paidMap[ReglementLigne::EXPORT . ':' . $export->id] ?? 0);
        $lines = [];
        $ht = 0.0;
        foreach ($export->lignes as $ligne) {
            $amount = (float) $ligne->montant;
            $ht += $amount;
            $lines[] = $this->line($ligne->article_id, $ligne->designation, $ligne->article?->famille, (float) $ligne->quantite, $amount, 0, $amount, 0);
        }
        if ($export->transport_applique && (float) $export->montant_transport > 0) {
            $transport = (float) $export->montant_transport;
            $ht += $transport;
            $lines[] = $this->line(null, 'Transport', 'Transport', 1, $transport, 0, $transport, 0);
        }

        return $this->doc('export', $export->id, $export->numero_facture, $export->date_creation?->toDateString(), $export->client_id, $export->client?->nom, $ht, 0, $ht, $paid, $export->statut, false, $export->conditions_paiement, $export->devise ?: 'MAD', $lines);
    }

    private function mapFournisseur(FactureFournisseur $facture, array $paidMap): array
    {
        $paid = (float) ($paidMap[ReglementLigne::FOURNISSEUR . ':' . $facture->id] ?? 0);
        $lines = [];
        foreach ($facture->lignes as $ligne) {
            $lines[] = $this->line($ligne->article_id, $ligne->designation ?: $ligne->article?->designation, $ligne->article?->famille, (float) $ligne->quantite, (float) $ligne->montant_ht, (float) $ligne->montant_tva, (float) $ligne->montant_ttc, (float) $ligne->tva_taux);
        }

        return $this->doc('fournisseur', $facture->id, $facture->numero, $facture->date_facture?->toDateString(), $facture->fournisseur_id, $facture->fournisseur?->nom, (float) $facture->total_ht, (float) $facture->total_tva, (float) $facture->total_ttc, $paid, $facture->statut, false, $facture->mode_paiement, $facture->devise ?: 'MAD', $lines);
    }

    private function doc(string $type, int $id, ?string $numero, ?string $date, ?int $partyId, ?string $party, float $ht, float $tva, float $ttc, float $paid, ?string $statut, bool $annulee, ?string $mode, string $devise, array $lines): array
    {
        $paid = round(min($paid, $ttc), 2);
        $reste = round(max(0, $ttc - $paid), 2);
        $paiement = 'impayee';
        if ($annulee) {
            $paiement = 'annulee';
        } elseif ($ttc > 0 && $reste <= 0.009) {
            $paiement = 'payee';
        } elseif ($paid > 0.009) {
            $paiement = 'partielle';
        }

        return [
            'type' => $type,
            'id' => $id,
            'numero' => $numero,
            'date' => $date,
            'party_id' => $partyId,
            'partie' => $party ?: '—',
            'client' => $party ?: '—',
            'ht' => round($ht, 2),
            'tva' => round($tva, 2),
            'ttc' => round($ttc, 2),
            'paid' => $paid,
            'reste' => $reste,
            'statut' => $statut,
            'annulee' => $annulee,
            'statut_paiement' => $paiement,
            'mode' => $mode,
            'mode_label' => $mode ? $this->finance->modeLabel($mode) : 'Non précisé',
            'devise' => strtoupper($devise ?: 'MAD'),
            'lignes' => $lines,
        ];
    }

    private function line($articleId, ?string $designation, ?string $famille, float $qty, float $ht, float $tva, float $ttc, float $taux): array
    {
        return [
            'article_id' => $articleId,
            'designation' => $designation ?: 'Sans désignation',
            'categorie' => $famille ?: 'Non classée',
            'quantite' => $qty,
            'ht' => round($ht, 2),
            'tva' => round($tva, 2),
            'ttc' => round($ttc, 2),
            'taux' => round($taux, 2),
        ];
    }

    private function filterPayment(array $docs, array $filters): array
    {
        return array_values(array_filter($docs, function ($doc) use ($filters) {
            if (!empty($filters['devise']) && strtoupper($filters['devise']) !== $doc['devise']) {
                return false;
            }
            if (!empty($filters['paiement']) && $doc['statut_paiement'] !== $filters['paiement']) {
                return false;
            }
            if (!empty($filters['mode_paiement'])) {
                $mode = $filters['mode_paiement'];
                $label = $this->finance->modeLabel($mode);
                if ($doc['mode'] !== $mode && $doc['mode'] !== $label && $doc['mode_label'] !== $label && $doc['mode_label'] !== $mode) {
                    return false;
                }
            }
            if (!empty($filters['article_id']) || !empty($filters['categorie'])) {
                $hit = false;
                foreach ($doc['lignes'] as $ligne) {
                    if (!empty($filters['article_id']) && (int) $ligne['article_id'] !== (int) $filters['article_id']) {
                        continue;
                    }
                    if (!empty($filters['categorie']) && $ligne['categorie'] !== $filters['categorie']) {
                        continue;
                    }
                    $hit = true;
                    break;
                }
                if (!$hit) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function scopeLines(array $docs, array $filters): array
    {
        if (empty($filters['article_id']) && empty($filters['categorie'])) {
            return $docs;
        }
        $scoped = [];
        foreach ($docs as $doc) {
            $lines = array_values(array_filter($doc['lignes'], function ($ligne) use ($filters) {
                if (!empty($filters['article_id']) && (int) $ligne['article_id'] !== (int) $filters['article_id']) {
                    return false;
                }
                if (!empty($filters['categorie']) && $ligne['categorie'] !== $filters['categorie']) {
                    return false;
                }

                return true;
            }));
            $doc['lignes'] = $lines;
            $doc['ht'] = round(array_sum(array_column($lines, 'ht')), 2);
            $doc['tva'] = round(array_sum(array_column($lines, 'tva')), 2);
            $doc['ttc'] = round(array_sum(array_column($lines, 'ttc')), 2);
            $ratio = $doc['ttc'] > 0 ? min(1, $doc['paid'] / max($doc['ttc'], 0.01)) : 0;
            if ($doc['paid'] > $doc['ttc']) {
                $doc['paid'] = $doc['ttc'];
            }
            $doc['reste'] = round(max(0, $doc['ttc'] - min($doc['paid'], $doc['ttc'])), 2);
            unset($ratio);
            $scoped[] = $doc;
        }

        return $scoped;
    }

    private function sumDocs(array $docs): array
    {
        return [
            'ht' => round(array_sum(array_column($docs, 'ht')), 2),
            'tva' => round(array_sum(array_column($docs, 'tva')), 2),
            'ttc' => round(array_sum(array_column($docs, 'ttc')), 2),
        ];
    }

    private function series(array $docs, string $group): array
    {
        $bucket = [];
        foreach ($docs as $doc) {
            $date = $doc['date'] ? Carbon::parse($doc['date']) : null;
            $key = match ($group) {
                'day' => $date?->toDateString() ?? '—',
                'year' => $date?->format('Y') ?? '—',
                default => $date?->format('Y-m') ?? '—',
            };
            $key .= '|' . $doc['devise'];
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['periode' => explode('|', $key)[0], 'devise' => $doc['devise'], 'ht' => 0, 'tva' => 0, 'ttc' => 0, 'nombre' => 0];
            }
            $bucket[$key]['ht'] += $doc['ht'];
            $bucket[$key]['tva'] += $doc['tva'];
            $bucket[$key]['ttc'] += $doc['ttc'];
            $bucket[$key]['nombre']++;
        }
        ksort($bucket);

        return array_values(array_map(function ($row) {
            $row['ht'] = round($row['ht'], 2);
            $row['tva'] = round($row['tva'], 2);
            $row['ttc'] = round($row['ttc'], 2);

            return $row;
        }, $bucket));
    }

    private function group(array $docs, string $field): array
    {
        $bucket = [];
        foreach ($docs as $doc) {
            $label = $doc[$field] ?: '—';
            if (!isset($bucket[$label])) {
                $bucket[$label] = ['label' => $label, 'ht' => 0, 'tva' => 0, 'ttc' => 0, 'nombre' => 0];
            }
            $bucket[$label]['ht'] += $doc['ht'];
            $bucket[$label]['tva'] += $doc['tva'];
            $bucket[$label]['ttc'] += $doc['ttc'];
            $bucket[$label]['nombre']++;
        }
        $rows = array_values($bucket);
        usort($rows, fn ($a, $b) => $b['ttc'] <=> $a['ttc']);

        return array_map(function ($row) {
            $row['ht'] = round($row['ht'], 2);
            $row['tva'] = round($row['tva'], 2);
            $row['ttc'] = round($row['ttc'], 2);

            return $row;
        }, $rows);
    }

    private function groupLines(array $docs, string $field): array
    {
        $bucket = [];
        foreach ($docs as $doc) {
            foreach ($doc['lignes'] as $ligne) {
                $label = $ligne[$field] ?: '—';
                if (!isset($bucket[$label])) {
                    $bucket[$label] = ['label' => $label, 'quantite' => 0, 'ht' => 0, 'tva' => 0, 'ttc' => 0];
                }
                $bucket[$label]['quantite'] += $ligne['quantite'];
                $bucket[$label]['ht'] += $ligne['ht'];
                $bucket[$label]['tva'] += $ligne['tva'];
                $bucket[$label]['ttc'] += $ligne['ttc'];
            }
        }
        $rows = array_values($bucket);
        usort($rows, fn ($a, $b) => $b['ttc'] <=> $a['ttc']);

        return array_map(function ($row) {
            $row['quantite'] = round($row['quantite'], 3);
            $row['ht'] = round($row['ht'], 2);
            $row['tva'] = round($row['tva'], 2);
            $row['ttc'] = round($row['ttc'], 2);

            return $row;
        }, $rows);
    }

    private function tvaBreakdown(array $docs): array
    {
        $bucket = [];
        foreach ($this->finance->tauxTva() as $taux) {
            $bucket[(string) $taux] = ['taux' => (float) $taux, 'ht' => 0, 'tva' => 0, 'ttc' => 0];
        }
        foreach ($docs as $doc) {
            foreach ($doc['lignes'] as $ligne) {
                $key = (string) $ligne['taux'];
                if (!isset($bucket[$key])) {
                    $bucket[$key] = ['taux' => $ligne['taux'], 'ht' => 0, 'tva' => 0, 'ttc' => 0];
                }
                $bucket[$key]['ht'] += $ligne['ht'];
                $bucket[$key]['tva'] += $ligne['tva'];
                $bucket[$key]['ttc'] += $ligne['ttc'];
            }
        }
        ksort($bucket, SORT_NUMERIC);

        return array_values(array_map(function ($row) {
            $row['ht'] = round($row['ht'], 2);
            $row['tva'] = round($row['tva'], 2);
            $row['ttc'] = round($row['ttc'], 2);

            return $row;
        }, $bucket));
    }

    private function encaissements(array $filters, string $sens): array
    {
        $query = $this->paymentQuery($sens, $filters['from'] ?? null, $filters['to'] ?? null);
        if ($sens === Reglement::SENS_CLIENT && !empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if ($sens === Reglement::SENS_FOURNISSEUR && !empty($filters['fournisseur_id'])) {
            $query->where('fournisseur_id', $filters['fournisseur_id']);
        }
        $bucket = [];
        foreach ($query->get(['mode_paiement', 'montant']) as $payment) {
            $label = $this->finance->modeLabel($payment->mode_paiement);
            if (!isset($bucket[$label])) {
                $bucket[$label] = ['label' => $label, 'montant' => 0, 'nombre' => 0];
            }
            $bucket[$label]['montant'] += (float) $payment->montant;
            $bucket[$label]['nombre']++;
        }

        return array_values(array_map(function ($row) {
            $row['montant'] = round($row['montant'], 2);

            return $row;
        }, $bucket));
    }

    private function avoirs(array $filters): float
    {
        $query = NoteCredit::query()->whereIn('statut', ['validee', 'appliquee']);
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('date_note', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('date_note', '<=', $filters['to']);
        }
        if (!empty($filters['devise'])) {
            $query->where('devise', $filters['devise']);
        }

        return round((float) $query->sum('total_ttc'), 2);
    }

    private function paidMap(): array
    {
        $map = [];
        $rows = ReglementLigne::query()
            ->selectRaw('facture_type, facture_id, SUM(montant) as paid')
            ->groupBy('facture_type', 'facture_id')
            ->get();
        foreach ($rows as $row) {
            $map[$row->facture_type . ':' . $row->facture_id] = (float) $row->paid;
        }

        return $map;
    }

    private function applyDocFilters($query, array $filters, string $dateColumn, string $partyColumn, ?string $filterKey = null): void
    {
        $key = $filterKey ?: ($partyColumn === 'client_id' ? 'client_id' : 'fournisseur_id');
        if (!empty($filters[$key])) {
            $query->where($partyColumn, $filters[$key]);
        }
        if (!empty($filters['from'])) {
            $query->whereDate($dateColumn, '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate($dateColumn, '<=', $filters['to']);
        }
    }

    private function publicDoc(array $doc): array
    {
        unset($doc['lignes']);

        return $doc;
    }

    private function unitCosts(): array
    {
        $costs = [];
        if (DB::getSchemaBuilder()->hasTable('facture_fournisseur_lignes')) {
            $lines = DB::table('facture_fournisseur_lignes as l')
                ->join('facture_fournisseurs as f', 'f.id', '=', 'l.facture_fournisseur_id')
                ->whereNotNull('l.article_id')
                ->where('f.statut', '!=', 'brouillon')
                ->orderBy('f.date_facture')
                ->orderBy('l.id')
                ->get(['l.article_id', 'l.prix']);
            foreach ($lines as $line) {
                $costs[(int) $line->article_id] = ['prix' => (float) $line->prix, 'source' => 'achat'];
            }
        }
        if (DB::getSchemaBuilder()->hasTable('achat_lignes')) {
            $lines = DB::table('achat_lignes as l')
                ->join('achats as a', 'a.id', '=', 'l.achat_id')
                ->whereNotNull('l.article_id')
                ->orderBy('a.date_achat')
                ->orderBy('l.id')
                ->get(['l.article_id', 'l.prix']);
            foreach ($lines as $line) {
                $id = (int) $line->article_id;
                if (!isset($costs[$id])) {
                    $costs[$id] = ['prix' => (float) $line->prix, 'source' => 'achat'];
                }
            }
        }

        return $costs;
    }

    private function groupStock(array $rows, string $field): array
    {
        $bucket = [];
        foreach ($rows as $row) {
            $label = $row[$field] ?: '—';
            if (!isset($bucket[$label])) {
                $bucket[$label] = ['label' => $label, 'quantite' => 0, 'valeur' => 0];
            }
            $bucket[$label]['quantite'] += $row['stock_theorique'];
            $bucket[$label]['valeur'] += $row['valeur'];
        }

        return array_values(array_map(function ($row) {
            $row['quantite'] = round($row['quantite'], 3);
            $row['valeur'] = round($row['valeur'], 2);

            return $row;
        }, $bucket));
    }

    private function stockMovements(array $filters): array
    {
        $query = StockMouvement::with(['article', 'location'])
            ->where('annule', false)
            ->orderByDesc('date_mouvement');
        if (!empty($filters['from'])) {
            $query->whereDate('date_mouvement', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('date_mouvement', '<=', $filters['to']);
        }
        if (!empty($filters['article_id'])) {
            $query->where('article_id', $filters['article_id']);
        }
        if (!empty($filters['location_id'])) {
            $query->where('stock_location_id', $filters['location_id']);
        }
        $entries = ['entree_achat', 'entree_production', 'retour'];
        $exits = ['sortie_vente', 'sortie_production', 'mise_au_rebut'];
        $rows = [];
        $in = 0.0;
        $out = 0.0;
        foreach ($query->limit(500)->get() as $move) {
            if (!empty($filters['categorie']) && ($move->article?->famille ?: 'Non classée') !== $filters['categorie']) {
                continue;
            }
            $qty = (float) $move->quantite;
            if (in_array($move->type, $entries, true)) {
                $in += $qty;
            }
            if (in_array($move->type, $exits, true)) {
                $out += $qty;
            }
            $rows[] = [
                'date' => $move->date_mouvement?->toDateString(),
                'type' => $move->type_label,
                'type_code' => $move->type,
                'produit' => $move->article?->designation,
                'quantite' => $qty,
                'emplacement' => $move->location?->nom,
                'document' => $move->document_ref,
                'lot' => $move->lot,
                'sens' => in_array($move->type, $entries, true) ? 'entree' : (in_array($move->type, $exits, true) ? 'sortie' : 'autre'),
            ];
        }

        return ['rows' => $rows, 'entrees' => round($in, 3), 'sorties' => round($out, 3)];
    }

    private function partyNets(string $sens): array
    {
        $nets = [];
        $invoices = $sens === Reglement::SENS_CLIENT
            ? $this->salesDocuments([])
            : $this->purchaseDocuments([]);
        foreach ($invoices as $doc) {
            if ($doc['annulee'] || !$doc['party_id']) {
                continue;
            }
            $nets[$doc['party_id']][$doc['devise']] = round(($nets[$doc['party_id']][$doc['devise']] ?? 0) + $doc['reste'], 2);
        }
        $payments = Reglement::query()
            ->where('sens', $sens)
            ->withSum('lignes as affecte', 'montant')
            ->get();
        foreach ($payments as $payment) {
            $id = $sens === Reglement::SENS_CLIENT ? $payment->client_id : $payment->fournisseur_id;
            if (!$id) {
                continue;
            }
            $left = round((float) $payment->montant - (float) ($payment->affecte ?? 0), 2);
            if ($left <= 0) {
                continue;
            }
            $devise = strtoupper($payment->devise ?: 'MAD');
            $nets[$id][$devise] = round(($nets[$id][$devise] ?? 0) - $left, 2);
        }
        if ($sens === Reglement::SENS_CLIENT) {
            $notes = NoteCredit::query()->whereIn('statut', ['validee', 'appliquee'])->get(['client_id', 'devise', 'total_ttc']);
            foreach ($notes as $note) {
                $devise = strtoupper($note->devise ?: 'MAD');
                $nets[$note->client_id][$devise] = round(($nets[$note->client_id][$devise] ?? 0) - (float) $note->total_ttc, 2);
            }
        }

        return $nets;
    }

    private function totalsByDevise(array $docs): array
    {
        $out = [];
        foreach ($docs as $doc) {
            if (!empty($doc['annulee'])) {
                continue;
            }
            $devise = $doc['devise'] ?: 'MAD';
            $out[$devise] = round(($out[$devise] ?? 0) + (float) $doc['ttc'], 2);
        }

        return $out;
    }

    private function sumPositive(array $nets): array
    {
        $out = [];
        foreach ($nets as $byDevise) {
            foreach ($byDevise as $devise => $amount) {
                if ($amount > 0) {
                    $out[$devise] = round(($out[$devise] ?? 0) + $amount, 2);
                }
            }
        }

        return $out;
    }

    private function overdueTotal(string $sens): float
    {
        $total = 0.0;
        if ($sens === Reglement::SENS_CLIENT) {
            $factures = FactureLocale::with('client')->whereNotIn('statut', ['brouillon', 'annulee', 'payee'])->get();
            foreach ($factures as $facture) {
                $invoice = $this->finance->invoice(ReglementLigne::LOCALE, $facture->id);
                if (!$invoice) {
                    continue;
                }
                $row = $this->finance->decorateDeadline($invoice);
                if ($row['en_retard']) {
                    $total += $row['reste'];
                }
            }
            $exports = Exportation::query()->whereNotNull('numero_facture')->where('numero_facture', '!=', '')->pluck('id');
            foreach ($exports as $id) {
                $invoice = $this->finance->invoice(ReglementLigne::EXPORT, (int) $id);
                if (!$invoice) {
                    continue;
                }
                $row = $this->finance->decorateDeadline($invoice);
                if ($row['en_retard']) {
                    $total += $row['reste'];
                }
            }
        } else {
            $factures = FactureFournisseur::whereNotIn('statut', ['brouillon', 'payee'])->get();
            foreach ($factures as $facture) {
                $invoice = $this->finance->invoice(ReglementLigne::FOURNISSEUR, $facture->id);
                if (!$invoice) {
                    continue;
                }
                $row = $this->finance->decorateDeadline($invoice);
                if ($row['en_retard']) {
                    $total += $row['reste'];
                }
            }
        }

        return round($total, 2);
    }

    private function paymentQuery(string $sens, ?string $from, ?string $to)
    {
        return Reglement::query()
            ->where('sens', $sens)
            ->when($from, fn ($q) => $q->whereDate('date_reglement', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_reglement', '<=', $to));
    }

    private function cashflowSeries(array $filters): array
    {
        $from = $filters['from'] ?? now()->startOfYear()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $rows = Reglement::query()
            ->whereDate('date_reglement', '>=', $from)
            ->whereDate('date_reglement', '<=', $to)
            ->get(['date_reglement', 'sens', 'montant']);
        $bucket = [];
        foreach ($rows as $row) {
            $key = $row->date_reglement?->format('Y-m') ?? '—';
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['periode' => $key, 'entrees' => 0, 'sorties' => 0, 'solde' => 0];
            }
            if ($row->sens === Reglement::SENS_CLIENT) {
                $bucket[$key]['entrees'] += (float) $row->montant;
            } else {
                $bucket[$key]['sorties'] += (float) $row->montant;
            }
        }
        ksort($bucket);

        return array_values(array_map(function ($row) {
            $row['entrees'] = round($row['entrees'], 2);
            $row['sorties'] = round($row['sorties'], 2);
            $row['solde'] = round($row['entrees'] - $row['sorties'], 2);

            return $row;
        }, $bucket));
    }

    private function tvaSummary(array $sales, array $purchases): array
    {
        $rates = [];
        foreach (array_merge($sales['taux'] ?? [], $purchases['taux'] ?? []) as $row) {
            $rates[(string) $row['taux']] = (float) $row['taux'];
        }
        foreach ($this->finance->tauxTva() as $taux) {
            $rates[(string) $taux] = (float) $taux;
        }
        ksort($rates, SORT_NUMERIC);
        $lignes = [];
        $collectee = 0.0;
        $deductible = 0.0;
        $salesBy = collect($sales['taux'] ?? [])->keyBy(fn ($r) => (string) $r['taux']);
        $purchaseBy = collect($purchases['taux'] ?? [])->keyBy(fn ($r) => (string) $r['taux']);
        foreach ($rates as $key => $taux) {
            $c = (float) ($salesBy[$key]['tva'] ?? 0);
            $d = (float) ($purchaseBy[$key]['tva'] ?? 0);
            $collectee += $c;
            $deductible += $d;
            $lignes[] = [
                'taux' => $taux,
                'collectee' => round($c, 2),
                'deductible' => round($d, 2),
                'solde' => round($c - $d, 2),
            ];
        }

        return [
            'lignes' => $lignes,
            'collectee' => round($collectee, 2),
            'deductible' => round($deductible, 2),
            'due' => round($collectee - $deductible, 2),
        ];
    }

    private function section(string $title, array $headers, array $rows, array $keys): array
    {
        $body = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $key) {
                $line[] = is_array($row) ? ($row[$key] ?? '') : ($row->{$key} ?? '');
            }
            $body[] = $line;
        }

        return ['title' => $title, 'headers' => $headers, 'rows' => $body];
    }

    private function kpiLabel(string $key): string
    {
        return [
            'total_ht' => 'Total HT',
            'total_tva' => 'Total TVA',
            'total_ttc' => 'Total TTC',
            'total_regle' => 'Total réglé',
            'total_impaye' => 'Total impayé',
            'nombre' => 'Nombre de factures',
            'moyenne' => 'Facture moyenne',
            'annulees' => 'Factures annulées',
            'annulees_ttc' => 'Montant annulé',
            'payees' => 'Factures payées',
            'partielles' => 'Factures partielles',
            'impayees' => 'Factures impayées',
            'avoirs_ttc' => 'Avoirs TTC',
            'produits' => 'Produits',
            'quantite' => 'Quantité totale',
            'valeur' => 'Valeur du stock',
            'faibles' => 'Produits en stock faible',
            'ruptures' => 'Produits en rupture',
            'chiffre_affaires' => 'Chiffre d’affaires TTC',
            'achats' => 'Achats TTC',
            'creances' => 'Créances clients (MAD)',
            'dettes' => 'Dettes fournisseurs (MAD)',
            'caisse' => 'Solde de caisse',
            'banque' => 'Solde bancaire',
            'retard_clients' => 'Retards clients',
            'retard_fournisseurs' => 'Retards fournisseurs',
            'encaisse' => 'Total encaissé',
            'decaisse' => 'Total décaissé',
        ][$key] ?? $key;
    }
}
