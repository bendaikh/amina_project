<?php

namespace App\Http\Controllers;

use App\Models\LettrageHistorique;
use App\Models\Reglement;
use App\Models\ReglementLigne;
use App\Services\FinanceService;
use Illuminate\Http\Request;

class FinanceLettrageController extends Controller
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'sens' => 'required|in:client,fournisseur',
            'client_id' => 'nullable|integer',
            'fournisseur_id' => 'nullable|integer',
        ]);
        $partyId = $data['sens'] === 'client' ? ($data['client_id'] ?? null) : ($data['fournisseur_id'] ?? null);
        if (!$partyId) {
            return response()->json([
                'factures' => [],
                'reglements' => [],
                'solde' => null,
                'historique' => [],
            ]);
        }

        $reglements = Reglement::with('lignes')
            ->where('sens', $data['sens'])
            ->where($data['sens'] === 'client' ? 'client_id' : 'fournisseur_id', $partyId)
            ->orderBy('date_reglement')
            ->get()
            ->map(fn (Reglement $r) => $this->finance->presentReglement($r));

        return response()->json([
            'factures' => $this->finance->openInvoices($data['sens'], (int) $partyId, null, true),
            'reglements' => $reglements,
            'solde' => $this->finance->partySnapshot($data['sens'], (int) $partyId),
            'historique' => $this->history($data['sens'], (int) $partyId),
        ]);
    }

    public function auto(Request $request)
    {
        $data = $request->validate([
            'sens' => 'required|in:client,fournisseur',
            'client_id' => 'nullable|integer',
            'fournisseur_id' => 'nullable|integer',
        ]);
        $partyId = $data['sens'] === 'client' ? ($data['client_id'] ?? null) : ($data['fournisseur_id'] ?? null);
        if (!$partyId) {
            return response()->json(['message' => 'Choisissez un tiers.'], 422);
        }
        $created = $this->finance->lettrageAuto($data['sens'], (int) $partyId, $request->user()?->id);

        return response()->json([
            'message' => count($created) ? count($created) . ' affectation(s) créée(s).' : 'Aucun lettrage automatique possible.',
            'affectations' => $created,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reglement_id' => 'required|exists:reglements,id',
            'facture_type' => 'required|in:locale,export,fournisseur',
            'facture_id' => 'required|integer',
            'montant' => 'required|numeric|min:0.01',
        ]);
        $reglement = Reglement::findOrFail($data['reglement_id']);
        $this->finance->lettrageManuel(
            $reglement,
            $data['facture_type'],
            (int) $data['facture_id'],
            (float) $data['montant'],
            $request->user()?->id
        );

        return response()->json(['message' => 'Lettrage enregistré.']);
    }

    public function destroy(ReglementLigne $ligne)
    {
        $this->finance->delettrage($ligne, request()->user()?->id);

        return response()->json(['message' => 'Lettrage annulé.']);
    }

    private function history(string $sens, int $partyId)
    {
        return LettrageHistorique::query()
            ->where('sens', $sens)
            ->where($sens === 'client' ? 'client_id' : 'fournisseur_id', $partyId)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }
}
