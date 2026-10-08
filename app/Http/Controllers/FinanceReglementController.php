<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompteBancaire;
use App\Models\Fournisseur;
use App\Models\Reglement;
use App\Services\FinanceService;
use App\Services\TabularExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceReglementController extends Controller
{
    public function __construct(private FinanceService $finance, private TabularExport $export)
    {
    }

    public function meta()
    {
        return response()->json([
            'modes' => $this->finance->modes(),
            'devises' => $this->finance->devises(),
            'taux_tva' => $this->finance->tauxTva(),
            'comptes' => CompteBancaire::where('actif', true)->orderBy('nom_banque')->get(),
            'societe' => $this->finance->company(),
        ]);
    }

    public function index(Request $request)
    {
        $sens = $this->sens($request);
        $query = Reglement::with(['client', 'fournisseur', 'compte', 'lignes'])
            ->where('sens', $sens)
            ->orderByDesc('date_reglement')
            ->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"))
                    ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'like', "%{$search}%"));
            });
        }
        if ($mode = $request->get('mode_paiement')) {
            $query->where('mode_paiement', $mode);
        }
        if ($request->filled('from')) {
            $query->whereDate('date_reglement', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date_reglement', '<=', $request->get('to'));
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->integer('fournisseur_id'));
        }

        $all = $query->get();
        $rows = $all->map(fn (Reglement $r) => $this->finance->presentReglement($r));
        if ($etat = $request->get('etat')) {
            $rows = $rows->filter(function ($row) use ($etat) {
                return match ($etat) {
                    'partiel' => $row['montant_affecte'] > 0.009 && $row['reste'] > 0.009,
                    'solde' => $row['reste'] <= 0.009,
                    'non_affecte' => $row['montant_affecte'] <= 0.009,
                    default => true,
                };
            })->values();
        }

        if ($request->filled('format')) {
            return $this->download($request, $sens, $rows->all());
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(100, max(5, (int) $request->get('per_page', 20)));
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $slice,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($rows->count() / $perPage)),
            'total' => $rows->count(),
            'totaux' => [
                'montant' => round($rows->sum('montant'), 2),
                'affecte' => round($rows->sum('montant_affecte'), 2),
                'reste' => round($rows->sum('reste'), 2),
            ],
        ]);
    }

    public function show(Reglement $reglement)
    {
        return response()->json($this->finance->presentReglement($reglement));
    }

    public function facturesOuvertes(Request $request)
    {
        $data = $request->validate([
            'sens' => 'required|in:client,fournisseur',
            'client_id' => 'nullable|integer',
            'fournisseur_id' => 'nullable|integer',
            'reglement_id' => 'nullable|integer',
        ]);
        $partyId = $data['sens'] === 'client' ? ($data['client_id'] ?? null) : ($data['fournisseur_id'] ?? null);
        if (!$partyId) {
            return response()->json(['factures' => [], 'solde' => null]);
        }

        return response()->json([
            'factures' => $this->finance->openInvoices($data['sens'], (int) $partyId, $data['reglement_id'] ?? null),
            'solde' => $this->finance->partySnapshot($data['sens'], (int) $partyId),
        ]);
    }

    public function store(Request $request)
    {
        $reglement = DB::transaction(function () use ($request) {
            $data = $this->validateReglement($request);
            $data['numero'] = $this->finance->nextNumero($data['sens']);
            $data['created_by'] = $request->user()?->id;
            $reglement = Reglement::create($data);
            $this->finance->syncAllocations($reglement, $request->input('lignes', []), 'manuel', $request->user()?->id);
            $this->finance->refreshBalance($reglement->sens, $reglement->sens === 'client' ? $reglement->client_id : $reglement->fournisseur_id);

            return $reglement;
        });

        return response()->json($this->finance->presentReglement($reglement->fresh()), 201);
    }

    public function update(Request $request, Reglement $reglement)
    {
        $previousParty = [$reglement->sens, $reglement->sens === 'client' ? $reglement->client_id : $reglement->fournisseur_id];
        DB::transaction(function () use ($request, $reglement) {
            $data = $this->validateReglement($request, $reglement);
            $reconciled = (float) $reglement->rapprochements()->sum('montant');
            if ((float) $data['montant'] + 0.009 < $reconciled) {
                throw ValidationException::withMessages([
                    'montant' => 'Le montant est inférieur au montant déjà rapproché en banque.',
                ]);
            }
            $reglement->update($data);
            $this->finance->syncAllocations($reglement, $request->input('lignes', []), 'manuel', $request->user()?->id);
        });
        $reglement->refresh();
        $this->finance->refreshBalance($reglement->sens, $reglement->sens === 'client' ? $reglement->client_id : $reglement->fournisseur_id);
        if ($previousParty[0] !== $reglement->sens || $previousParty[1] !== ($reglement->sens === 'client' ? $reglement->client_id : $reglement->fournisseur_id)) {
            $this->finance->refreshBalance($previousParty[0], $previousParty[1]);
        }

        return response()->json($this->finance->presentReglement($reglement));
    }

    public function destroy(Reglement $reglement)
    {
        if ($reglement->rapprochements()->exists()) {
            return response()->json([
                'message' => 'Dérapprochez ce règlement avant de le supprimer.',
            ], 422);
        }
        $sens = $reglement->sens;
        $partyId = $sens === 'client' ? $reglement->client_id : $reglement->fournisseur_id;
        $links = $reglement->lignes()->get();
        DB::transaction(function () use ($reglement, $links) {
            foreach ($links as $link) {
                $this->finance->delettrage($link, request()->user()?->id);
            }
            $reglement->delete();
        });
        $this->finance->refreshBalance($sens, $partyId);

        return response()->json(['message' => 'Règlement supprimé.']);
    }

    public function tiers(Request $request)
    {
        $sens = $request->get('sens', 'client');
        if ($sens === 'fournisseur') {
            $rows = Fournisseur::orderBy('nom')->get(['id', 'nom', 'devise', 'solde_actuel', 'ice']);
        } else {
            $rows = Client::orderBy('nom')->get(['id', 'nom', 'devise', 'solde_actuel', 'ice_cin', 'delai_paiement']);
        }

        return response()->json($rows);
    }

    private function validateReglement(Request $request, ?Reglement $reglement = null): array
    {
        $modes = collect($this->finance->modes())->pluck('code')->all();
        $data = $request->validate([
            'sens' => 'required|in:client,fournisseur',
            'client_id' => 'nullable|exists:clients,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'compte_bancaire_id' => 'nullable|exists:comptes_bancaires,id',
            'date_reglement' => 'required|date',
            'mode_paiement' => ['required', Rule::in($modes)],
            'montant' => 'required|numeric|min:0.01',
            'devise' => 'nullable|string|max:10',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lignes' => 'array',
            'lignes.*.facture_type' => 'required_with:lignes|string',
            'lignes.*.facture_id' => 'required_with:lignes|integer',
            'lignes.*.montant' => 'required_with:lignes|numeric|min:0',
        ]);
        if ($data['sens'] === 'client' && empty($data['client_id'])) {
            throw ValidationException::withMessages(['client_id' => 'Le client est obligatoire.']);
        }
        if ($data['sens'] === 'fournisseur' && empty($data['fournisseur_id'])) {
            throw ValidationException::withMessages(['fournisseur_id' => 'Le fournisseur est obligatoire.']);
        }
        if (in_array($data['mode_paiement'], ['virement', 'cheque', 'carte'], true) && empty($data['compte_bancaire_id'])) {
            throw ValidationException::withMessages(['compte_bancaire_id' => 'Sélectionnez le compte bancaire pour ce mode de paiement.']);
        }
        $data['devise'] = strtoupper($data['devise'] ?? 'MAD');
        $data['montant'] = round((float) $data['montant'], 2);
        if ($data['sens'] === 'client') {
            $data['fournisseur_id'] = null;
        } else {
            $data['client_id'] = null;
        }
        if ($data['mode_paiement'] === 'especes') {
            $data['compte_bancaire_id'] = null;
        }
        unset($data['lignes']);

        return $data;
    }

    private function sens(Request $request): string
    {
        return $request->get('sens') === 'fournisseur' ? 'fournisseur' : 'client';
    }

    private function download(Request $request, string $sens, array $rows)
    {
        $body = [];
        foreach ($rows as $row) {
            $body[] = [
                $row['numero'],
                $row['date_reglement'],
                $row['partie'],
                $row['mode_label'],
                $row['reference'],
                $row['montant'],
                $row['montant_affecte'],
                $row['reste'],
                collect($row['factures'])->pluck('numero')->implode(', '),
                $row['devise'],
                $row['solde_tiers']['solde'] ?? '',
            ];
        }
        $title = $sens === 'fournisseur' ? 'Règlements fournisseurs' : 'Règlements clients';

        return $this->export->download($request->get('format', 'csv'), $title, [
            'title' => $title,
            'generated_at' => now()->format('d/m/Y H:i'),
            'company' => $this->finance->company(),
            'kpis' => [],
            'sections' => [[
                'title' => $title,
                'headers' => ['N°', 'Date', 'Tiers', 'Mode', 'Référence', 'Montant', 'Affecté', 'Reste', 'Factures', 'Devise', 'Solde tiers'],
                'rows' => $body,
            ]],
        ]);
    }
}
