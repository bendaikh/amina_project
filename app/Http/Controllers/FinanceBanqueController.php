<?php

namespace App\Http\Controllers;

use App\Models\CompteBancaire;
use App\Models\MouvementBancaire;
use App\Models\RapprochementLigne;
use App\Services\BankService;
use App\Services\FinanceService;
use App\Services\TabularExport;
use Illuminate\Http\Request;

class FinanceBanqueController extends Controller
{
    public function __construct(
        private BankService $banks,
        private FinanceService $finance,
        private TabularExport $export,
    ) {
    }

    public function index()
    {
        $comptes = CompteBancaire::orderBy('nom_banque')->get()
            ->map(fn (CompteBancaire $c) => $this->banks->accountPayload($c));

        return response()->json($comptes);
    }

    public function store(Request $request)
    {
        $data = $this->validateCompte($request);
        $compte = CompteBancaire::create($data);

        return response()->json($this->banks->accountPayload($compte), 201);
    }

    public function show(CompteBancaire $compte)
    {
        return response()->json([
            'compte' => $this->banks->accountPayload($compte),
            'mouvements' => $this->banks->runningBalances($compte),
            'types' => MouvementBancaire::TYPES,
        ]);
    }

    public function update(Request $request, CompteBancaire $compte)
    {
        $compte->update($this->validateCompte($request));

        return response()->json($this->banks->accountPayload($compte));
    }

    public function destroy(CompteBancaire $compte)
    {
        if ($compte->mouvements()->exists() || $compte->reglements()->exists()) {
            return response()->json(['message' => 'Ce compte a des mouvements ou des règlements. Désactivez-le plutôt que de le supprimer.'], 422);
        }
        $compte->delete();

        return response()->json(['message' => 'Compte supprimé.']);
    }

    public function storeMouvement(Request $request, CompteBancaire $compte)
    {
        $data = $request->validate([
            'date_operation' => 'required|date',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'type' => 'required|string|max:40',
        ]);
        $debit = round((float) ($data['debit'] ?? 0), 2);
        $credit = round((float) ($data['credit'] ?? 0), 2);
        if ($debit <= 0 && $credit <= 0) {
            return response()->json(['message' => 'Saisissez un débit ou un crédit.'], 422);
        }
        if ($debit > 0 && $credit > 0) {
            return response()->json(['message' => 'Une opération est soit un débit, soit un crédit.'], 422);
        }
        $mouvement = $compte->mouvements()->create([
            'date_operation' => $data['date_operation'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'debit' => $debit,
            'credit' => $credit,
            'type' => $data['type'],
            'source' => 'manuel',
        ]);

        return response()->json($mouvement, 201);
    }

    public function destroyMouvement(MouvementBancaire $mouvement)
    {
        if ($mouvement->rapprochements()->exists()) {
            return response()->json(['message' => 'Dérapprochez cette opération avant de la supprimer.'], 422);
        }
        $mouvement->delete();

        return response()->json(['message' => 'Opération supprimée.']);
    }

    public function import(Request $request, CompteBancaire $compte)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
        ]);
        $result = $this->banks->import($compte, $request->file('fichier'));

        return response()->json($result);
    }

    public function exportMouvements(Request $request, CompteBancaire $compte)
    {
        $rows = [];
        foreach ($this->banks->runningBalances($compte) as $row) {
            $rows[] = [$row['date_operation'], $row['description'], $row['reference'], $row['debit'], $row['credit'], $row['solde'], $row['type_label']];
        }
        $payload = $this->banks->accountPayload($compte);

        return $this->export->download($request->get('format', 'csv'), 'banque-' . $compte->id, [
            'title' => 'Relevé ' . $payload['nom_banque'] . ' — ' . $payload['nom_compte'],
            'generated_at' => now()->format('d/m/Y H:i'),
            'company' => $this->finance->company(),
            'kpis' => [
                ['label' => 'Solde d’ouverture', 'value' => $payload['solde_ouverture']],
                ['label' => 'Solde actuel', 'value' => $payload['solde_actuel']],
                ['label' => 'Devise', 'value' => $payload['devise']],
                ['label' => 'RIB', 'value' => $payload['rib']],
                ['label' => 'IBAN', 'value' => $payload['iban']],
            ],
            'sections' => [[
                'title' => 'Opérations',
                'headers' => ['Date', 'Description', 'Référence', 'Débit', 'Crédit', 'Solde', 'Type'],
                'rows' => $rows,
            ]],
        ]);
    }

    public function rapprochement(Request $request, CompteBancaire $compte)
    {
        $state = $this->banks->reconciliation($compte, $request->get('from'), $request->get('to'));
        if ($request->filled('format')) {
            return $this->exportRapprochement($request, $state);
        }

        return response()->json($state);
    }

    public function autoRapprochement(Request $request, CompteBancaire $compte)
    {
        $ids = $this->banks->autoMatch($compte, $request->user()?->id);

        return response()->json([
            'message' => count($ids) ? count($ids) . ' rapprochement(s) automatique(s).' : 'Aucune correspondance automatique.',
            'ids' => $ids,
        ]);
    }

    public function storeRapprochement(Request $request, CompteBancaire $compte)
    {
        $data = $request->validate([
            'mouvement_bancaire_id' => 'required|integer',
            'reglement_id' => 'required|integer',
            'montant' => 'required|numeric|min:0.01',
        ]);
        $ligne = $this->banks->manualMatch(
            $compte,
            (int) $data['mouvement_bancaire_id'],
            (int) $data['reglement_id'],
            (float) $data['montant'],
            $request->user()?->id
        );

        return response()->json($ligne, 201);
    }

    public function destroyRapprochement(RapprochementLigne $ligne)
    {
        $ligne->delete();

        return response()->json(['message' => 'Rapprochement annulé.']);
    }

    private function exportRapprochement(Request $request, array $state)
    {
        $compte = $state['compte'];

        return $this->export->download($request->get('format', 'pdf'), 'rapprochement-' . $compte['id'], [
            'title' => 'Rapprochement bancaire — ' . $compte['nom_banque'] . ' ' . $compte['nom_compte'],
            'generated_at' => now()->format('d/m/Y H:i'),
            'company' => $this->finance->company(),
            'kpis' => [
                ['label' => 'Solde banque', 'value' => $state['solde_banque']],
                ['label' => 'Solde ERP', 'value' => $state['solde_erp']],
                ['label' => 'Écart', 'value' => $state['ecart']],
                ['label' => 'Montant rapproché', 'value' => $state['montant_rapproche']],
                ['label' => 'Non rapproché', 'value' => $state['montant_non_rapproche']],
            ],
            'sections' => [
                [
                    'title' => 'Écritures rapprochées',
                    'headers' => ['Date banque', 'Libellé', 'Règlement', 'Montant', 'Méthode'],
                    'rows' => collect($state['lignes'])->map(fn ($l) => [$l['date_banque'], $l['libelle'], $l['reglement'], $l['montant'], $l['methode']])->all(),
                ],
                [
                    'title' => 'Banque non rapprochée',
                    'headers' => ['Date', 'Description', 'Référence', 'Débit', 'Crédit', 'Reste'],
                    'rows' => collect($state['mouvements_non_rapproches'])->map(fn ($m) => [$m['date_operation'], $m['description'], $m['reference'], $m['debit'], $m['credit'], $m['reste']])->all(),
                ],
                [
                    'title' => 'ERP non rapproché',
                    'headers' => ['Date', 'Règlement', 'Tiers', 'Montant', 'Reste'],
                    'rows' => collect($state['reglements_non_rapproches'])->map(fn ($r) => [$r['date'], $r['numero'], $r['partie'], $r['montant'], $r['reste']])->all(),
                ],
            ],
        ]);
    }

    private function validateCompte(Request $request): array
    {
        $data = $request->validate([
            'nom_banque' => 'required|string|max:255',
            'nom_compte' => 'required|string|max:255',
            'rib' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'devise' => 'nullable|string|max:10',
            'solde_ouverture' => 'nullable|numeric',
            'actif' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
        $data['devise'] = strtoupper($data['devise'] ?? 'MAD');
        $data['solde_ouverture'] = round((float) ($data['solde_ouverture'] ?? 0), 2);
        $data['actif'] = array_key_exists('actif', $data) ? (bool) $data['actif'] : true;

        return $data;
    }
}
