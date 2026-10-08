<?php

namespace App\Services;

use App\Models\CompteBancaire;
use App\Models\MouvementBancaire;
use App\Models\RapprochementLigne;
use App\Models\Reglement;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class BankService
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function accountPayload(CompteBancaire $compte): array
    {
        return [
            'id' => $compte->id,
            'nom_banque' => $compte->nom_banque,
            'nom_compte' => $compte->nom_compte,
            'rib' => $compte->rib,
            'iban' => $compte->iban,
            'devise' => $compte->devise ?: 'MAD',
            'solde_ouverture' => (float) $compte->solde_ouverture,
            'solde_actuel' => $this->finance->bankBalance($compte),
            'solde_erp' => $this->finance->erpBalance($compte),
            'actif' => (bool) $compte->actif,
            'notes' => $compte->notes,
        ];
    }

    public function runningBalances(CompteBancaire $compte): array
    {
        $rows = $compte->mouvements()->orderBy('date_operation')->orderBy('id')->get();
        $balance = (float) $compte->solde_ouverture;
        $out = [];
        foreach ($rows as $row) {
            $balance = round($balance + (float) $row->credit - (float) $row->debit, 2);
            $matched = round((float) $row->rapprochements()->sum('montant'), 2);
            $amount = round((float) $row->debit + (float) $row->credit, 2);
            $out[] = [
                'id' => $row->id,
                'date_operation' => $row->date_operation?->toDateString(),
                'description' => $row->description,
                'reference' => $row->reference,
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'solde' => $balance,
                'type' => $row->type,
                'type_label' => $row->type_label,
                'source' => $row->source,
                'montant_rapproche' => $matched,
                'reste' => round(max(0, $amount - $matched), 2),
                'rapproche' => $amount > 0 && $matched + 0.009 >= $amount,
            ];
        }

        return $out;
    }

    public function import(CompteBancaire $compte, UploadedFile $file): array
    {
        $rows = $this->readTabular($file->getRealPath(), $file->getClientOriginalExtension());
        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'fichier' => 'Le fichier ne contient aucune opération.',
            ]);
        }

        $header = array_map(fn ($h) => $this->normHeader((string) $h), $rows[0]);
        $map = $this->mapHeaders($header);
        if (!isset($map['date'])) {
            throw ValidationException::withMessages([
                'fichier' => 'Colonne date introuvable. Attendues : date, libellé, référence, débit, crédit.',
            ]);
        }

        $created = 0;
        $skipped = 0;
        DB::transaction(function () use ($rows, $map, $compte, &$created, &$skipped) {
            foreach (array_slice($rows, 1) as $row) {
                if ($this->rowEmpty($row)) {
                    continue;
                }
                $date = $this->parseDate($row[$map['date']] ?? null);
                if (!$date) {
                    $skipped++;
                    continue;
                }
                $description = trim((string) ($this->cell($row, $map, 'description') ?? ''));
                $reference = trim((string) ($this->cell($row, $map, 'reference') ?? ''));
                $debit = $this->parseAmount($this->cell($row, $map, 'debit'));
                $credit = $this->parseAmount($this->cell($row, $map, 'credit'));
                if (isset($map['montant']) && $debit == 0.0 && $credit == 0.0) {
                    $signed = $this->parseAmount($this->cell($row, $map, 'montant'), true);
                    if ($signed >= 0) {
                        $credit = $signed;
                    } else {
                        $debit = abs($signed);
                    }
                }
                if ($description === '') {
                    $description = $reference !== '' ? $reference : 'Opération importée';
                }
                if ($debit <= 0 && $credit <= 0) {
                    $skipped++;
                    continue;
                }
                $exists = MouvementBancaire::query()
                    ->where('compte_bancaire_id', $compte->id)
                    ->whereDate('date_operation', $date)
                    ->where('description', $description)
                    ->where('reference', $reference !== '' ? $reference : null)
                    ->where('debit', $debit)
                    ->where('credit', $credit)
                    ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }
                MouvementBancaire::create([
                    'compte_bancaire_id' => $compte->id,
                    'date_operation' => $date,
                    'description' => mb_substr($description, 0, 255),
                    'reference' => $reference !== '' ? mb_substr($reference, 0, 255) : null,
                    'debit' => $debit,
                    'credit' => $credit,
                    'type' => $credit > 0 ? 'virement_recu' : 'virement_emis',
                    'source' => 'import',
                ]);
                $created++;
            }
        });

        return ['importes' => $created, 'ignores' => $skipped];
    }

    public function reconciliation(CompteBancaire $compte, ?string $from = null, ?string $to = null): array
    {
        $movements = collect($this->runningBalances($compte));
        if ($from) {
            $movements = $movements->filter(fn ($m) => $m['date_operation'] >= $from);
        }
        if ($to) {
            $movements = $movements->filter(fn ($m) => $m['date_operation'] <= $to);
        }

        $reglements = Reglement::with(['client', 'fournisseur'])
            ->where('compte_bancaire_id', $compte->id)
            ->when($from, fn ($q) => $q->whereDate('date_reglement', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_reglement', '<=', $to))
            ->orderBy('date_reglement')
            ->get();

        $matchedByReglement = RapprochementLigne::query()
            ->where('compte_bancaire_id', $compte->id)
            ->selectRaw('reglement_id, SUM(montant) as montant')
            ->groupBy('reglement_id')
            ->pluck('montant', 'reglement_id');

        $erp = $reglements->map(function (Reglement $r) use ($matchedByReglement) {
            $matched = round((float) ($matchedByReglement[$r->id] ?? 0), 2);
            $reste = round(max(0, (float) $r->montant - $matched), 2);

            return [
                'id' => $r->id,
                'numero' => $r->numero,
                'date' => $r->date_reglement?->toDateString(),
                'sens' => $r->sens,
                'partie' => $r->sens === Reglement::SENS_CLIENT ? $r->client?->nom : $r->fournisseur?->nom,
                'mode' => $this->finance->modeLabel($r->mode_paiement),
                'reference' => $r->reference,
                'montant' => (float) $r->montant,
                'montant_rapproche' => $matched,
                'reste' => $reste,
                'rapproche' => $reste <= 0.009,
                'sens_banque' => $r->sens === Reglement::SENS_CLIENT ? 'credit' : 'debit',
            ];
        })->values();

        $links = RapprochementLigne::with(['mouvement', 'reglement.client', 'reglement.fournisseur'])
            ->where('compte_bancaire_id', $compte->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (RapprochementLigne $l) {
                return [
                    'id' => $l->id,
                    'montant' => (float) $l->montant,
                    'methode' => $l->methode,
                    'mouvement_id' => $l->mouvement_bancaire_id,
                    'reglement_id' => $l->reglement_id,
                    'date_banque' => $l->mouvement?->date_operation?->toDateString(),
                    'libelle' => $l->mouvement?->description,
                    'reference_banque' => $l->mouvement?->reference,
                    'reglement' => $l->reglement?->numero,
                    'date_reglement' => $l->reglement?->date_reglement?->toDateString(),
                    'created_at' => $l->created_at?->toDateTimeString(),
                ];
            });

        $soldeBanque = $this->finance->bankBalance($compte);
        $soldeErp = $this->finance->erpBalance($compte);
        $rapproche = round((float) RapprochementLigne::where('compte_bancaire_id', $compte->id)->sum('montant'), 2);
        $nonBanque = round($movements->sum('reste'), 2);
        $nonErp = round($erp->sum('reste'), 2);

        return [
            'compte' => $this->accountPayload($compte),
            'solde_banque' => $soldeBanque,
            'solde_erp' => $soldeErp,
            'ecart' => round($soldeBanque - $soldeErp, 2),
            'montant_rapproche' => $rapproche,
            'montant_non_rapproche' => round($nonBanque + $nonErp, 2),
            'mouvements' => $movements->values(),
            'mouvements_non_rapproches' => $movements->filter(fn ($m) => !$m['rapproche'] && ($m['debit'] > 0 || $m['credit'] > 0))->values(),
            'reglements' => $erp,
            'reglements_non_rapproches' => $erp->filter(fn ($r) => !$r['rapproche'])->values(),
            'lignes' => $links,
        ];
    }

    public function autoMatch(CompteBancaire $compte, ?int $userId = null): array
    {
        $state = $this->reconciliation($compte);
        $banks = collect($state['mouvements_non_rapproches'])->keyBy('id');
        $usedBanks = [];
        $created = [];

        foreach ($state['reglements_non_rapproches'] as $reglement) {
            $target = (float) $reglement['reste'];
            if ($target <= 0.009) {
                continue;
            }
            $best = null;
            foreach ($banks as $bank) {
                if (isset($usedBanks[$bank['id']])) {
                    continue;
                }
                $bankReste = (float) $bank['reste'];
                if (abs($bankReste - $target) > 0.009) {
                    continue;
                }
                $directionOk = $reglement['sens_banque'] === 'credit'
                    ? (float) $bank['credit'] > 0
                    : (float) $bank['debit'] > 0;
                if (!$directionOk) {
                    continue;
                }
                $ref = mb_strtolower((string) ($reglement['reference'] ?? ''));
                $hay = mb_strtolower(($bank['reference'] ?? '') . ' ' . ($bank['description'] ?? '') . ' ' . ($reglement['numero'] ?? ''));
                $refHit = $ref !== '' && str_contains($hay, $ref);
                $dateGap = abs(Carbon::parse($bank['date_operation'])->diffInDays(Carbon::parse($reglement['date'])));
                if (!$refHit && $dateGap > 5) {
                    continue;
                }
                $score = ($refHit ? 0 : 10) + $dateGap;
                if ($best === null || $score < $best['score']) {
                    $best = ['id' => $bank['id'], 'score' => $score, 'montant' => $target];
                }
            }
            if (!$best) {
                continue;
            }
            $usedBanks[$best['id']] = true;
            $ligne = RapprochementLigne::create([
                'compte_bancaire_id' => $compte->id,
                'mouvement_bancaire_id' => $best['id'],
                'reglement_id' => $reglement['id'],
                'montant' => $best['montant'],
                'methode' => 'auto',
                'created_by' => $userId,
            ]);
            $created[] = $ligne->id;
        }

        return $created;
    }

    public function manualMatch(CompteBancaire $compte, int $mouvementId, int $reglementId, float $montant, ?int $userId = null): RapprochementLigne
    {
        $mouvement = MouvementBancaire::where('compte_bancaire_id', $compte->id)->find($mouvementId);
        $reglement = Reglement::where('compte_bancaire_id', $compte->id)->find($reglementId);
        if (!$mouvement || !$reglement) {
            throw ValidationException::withMessages([
                'rapprochement' => 'Le mouvement ou le règlement n’appartient pas à ce compte.',
            ]);
        }
        $montant = round($montant, 2);
        if ($montant <= 0) {
            throw ValidationException::withMessages(['montant' => 'Le montant rapproché doit être positif.']);
        }
        $bankAmount = round((float) $mouvement->debit + (float) $mouvement->credit, 2);
        $bankUsed = round((float) $mouvement->rapprochements()->sum('montant'), 2);
        $regUsed = round((float) $reglement->rapprochements()->sum('montant'), 2);
        if ($montant - ($bankAmount - $bankUsed) > 0.009) {
            throw ValidationException::withMessages(['montant' => 'Le montant dépasse le reste du mouvement bancaire.']);
        }
        if ($montant - ((float) $reglement->montant - $regUsed) > 0.009) {
            throw ValidationException::withMessages(['montant' => 'Le montant dépasse le reste du règlement.']);
        }
        $sensOk = $reglement->sens === Reglement::SENS_CLIENT
            ? (float) $mouvement->credit > 0
            : (float) $mouvement->debit > 0;
        if (!$sensOk) {
            throw ValidationException::withMessages([
                'rapprochement' => 'Le sens ne correspond pas : un encaissement client se rapproche d’un crédit, un règlement fournisseur d’un débit.',
            ]);
        }

        return RapprochementLigne::create([
            'compte_bancaire_id' => $compte->id,
            'mouvement_bancaire_id' => $mouvement->id,
            'reglement_id' => $reglement->id,
            'montant' => $montant,
            'methode' => 'manuel',
            'created_by' => $userId,
        ]);
    }

    private function readTabular(string $path, string $extension): array
    {
        $extension = strtolower($extension);
        if (in_array($extension, ['xlsx', 'xlsm'], true)) {
            return $this->readXlsx($path);
        }

        return $this->readCsv($path);
    }

    private function readCsv(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $sample = substr($raw, 0, 2000);
        $delimiter = substr_count($sample, ';') >= substr_count($sample, ',') ? ';' : ',';
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['fichier' => 'Fichier Excel illisible.']);
        }
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $shared[] = (string) $si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $shared[] = $text;
                    }
                }
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheet) {
            throw ValidationException::withMessages(['fichier' => 'La première feuille Excel est vide.']);
        }
        $xml = simplexml_load_string($sheet);
        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $line = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/\d+/', '', $ref);
                $idx = $this->columnIndex($col);
                $type = (string) $c['t'];
                $value = isset($c->v) ? (string) $c->v : '';
                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($c->is->t ?? '');
                }
                $line[$idx] = $value;
            }
            if ($line) {
                $max = max(array_keys($line));
                $filled = [];
                for ($i = 0; $i <= $max; $i++) {
                    $filled[] = $line[$i] ?? '';
                }
                $rows[] = $filled;
            }
        }

        return $rows;
    }

    private function columnIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    private function normHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'ù' => 'u', 'ô' => 'o', 'î' => 'i', 'ï' => 'i', 'ç' => 'c']);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function mapHeaders(array $header): array
    {
        $aliases = [
            'date' => ['date', 'dateoperation', 'dateoperation', 'datevaleur', 'datecomptable'],
            'description' => ['description', 'libelle', 'libellé', 'designation', 'label'],
            'reference' => ['reference', 'ref', 'piece', 'numero'],
            'debit' => ['debit', 'debits'],
            'credit' => ['credit', 'credits'],
            'montant' => ['montant', 'amount', 'valeur'],
        ];
        $map = [];
        foreach ($header as $index => $name) {
            foreach ($aliases as $key => $list) {
                if (in_array($name, $list, true) && !isset($map[$key])) {
                    $map[$key] = $index;
                }
            }
        }

        return $map;
    }

    private function cell(array $row, array $map, string $key): mixed
    {
        if (!isset($map[$key])) {
            return null;
        }

        return $row[$map[$key]] ?? null;
    }

    private function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) round((float) $value))->toDateString();
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'm/d/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseAmount(mixed $value, bool $keepSign = false): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }
        $negative = str_contains($raw, '-') || str_contains($raw, '(');
        $raw = str_replace(["\xc2\xa0", ' ', '(', ')'], '', $raw);
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = strrpos($raw, ',') > strrpos($raw, '.')
                ? str_replace('.', '', $raw)
                : str_replace(',', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);
        $raw = preg_replace('/[^0-9.\-]/', '', $raw) ?? '0';
        $amount = round(abs((float) $raw), 2);

        return $keepSign && $negative ? -$amount : $amount;
    }
}
