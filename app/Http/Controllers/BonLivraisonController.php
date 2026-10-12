<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BonLivraison;
use App\Models\Commande;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BonLivraisonController extends Controller
{
    public function index(Request $request)
    {
        $query = BonLivraison::with(['client', 'commande', 'lignes', 'factureLocale'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference_client', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"))
                    ->orWhereHas('commande', fn ($c) => $c->where('numero', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => BonLivraison::STATUTS,
            'labels' => [
                'brouillon' => 'Brouillon',
                'valide' => 'Validé',
                'annule' => 'Annulé',
            ],
        ]);
    }

    public function show(BonLivraison $bonLivraison)
    {
        $bonLivraison->load(['client', 'commande.lignes.article', 'lignes.article', 'factureLocale']);

        return response()->json($bonLivraison);
    }

    public function downloadPdf(BonLivraison $bonLivraison, FinanceService $finance)
    {
        $bonLivraison->load(['client', 'commande', 'lignes']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.bon_livraison', [
            'bl' => $bonLivraison,
            'company' => $finance->company(),
        ])->setPaper('a4', 'landscape');

        $name = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) $bonLivraison->numero) ?: 'bon-de-livraison';

        return $pdf->download($name . '.pdf');
    }

    /**
     * Transforme une commande en bon de livraison.
     * La commande disparaît ensuite de la liste des commandes.
     */
    public function fromCommande(Request $request, Commande $commande)
    {
        if ($commande->bonLivraison) {
            return response()->json([
                'message' => 'Cette commande a déjà été transformée en bon de livraison',
                'bon_livraison' => $commande->bonLivraison->load(['client', 'lignes']),
            ], 422);
        }

        $commande->load(['client', 'lignes.article']);

        if ($commande->lignes->isEmpty()) {
            return response()->json(['message' => 'La commande ne contient aucune ligne'], 422);
        }

        $extra = $request->validate([
            'date_heure_livraison' => 'nullable|date',
            'informations_additionnelles' => 'nullable|string',
            'statut' => 'nullable|in:' . implode(',', BonLivraison::STATUTS),
        ]);

        $bl = DB::transaction(function () use ($commande, $extra, $request) {
            $dateHeure = $extra['date_heure_livraison'] ?? null;
            if (!$dateHeure && $commande->date_souhaitee) {
                $dateHeure = $commande->date_souhaitee->format('Y-m-d') . ' 00:00:00';
            }

            $bl = BonLivraison::create([
                'numero' => BonLivraison::nextNumero(),
                'date_creation' => now()->toDateString(),
                'commande_id' => $commande->id,
                'client_id' => $commande->client_id,
                'reference_client' => $commande->reference_client,
                'date_heure_livraison' => $dateHeure,
                'informations_additionnelles' => $extra['informations_additionnelles']
                    ?? $commande->observations,
                'statut' => $extra['statut'] ?? 'brouillon',
                'created_by' => $request->user()?->id,
            ]);

            foreach ($commande->lignes->values() as $i => $ligne) {
                $bl->lignes()->create(BonLivraison::ligneFromCommandeLigne($ligne, $i));
            }

            $bl->recalculateTotals();

            if (!in_array($commande->statut, ['livree', 'cloturee'], true)) {
                $oldStatut = $commande->statut;
                $commande->update(['statut' => 'livree']);
                AuditLog::record($commande, 'update', 'statut', $oldStatut, 'livree', $request->user()?->id);
            }

            AuditLog::record($bl, 'create', null, null, $bl->numero, $request->user()?->id);

            return $bl;
        });

        return response()->json($bl->load(['client', 'commande', 'lignes.article']), 201);
    }

    /**
     * Convertit un bon de livraison en facture vente locale.
     */
    public function toFacture(Request $request, BonLivraison $bonLivraison)
    {
        return app(FactureLocaleController::class)->fromBonLivraison($request, $bonLivraison);
    }

    public function store(Request $request)
    {
        $data = $this->validateBl($request);
        $lignes = $request->input('lignes', []);

        if (!empty($data['commande_id'])) {
            $existing = BonLivraison::where('commande_id', $data['commande_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Cette commande a déjà un bon de livraison',
                    'bon_livraison' => $existing,
                ], 422);
            }
        }

        $bl = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? BonLivraison::nextNumero();
            $data['date_creation'] = $data['date_creation'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;
            $data['statut'] = $data['statut'] ?? 'brouillon';

            if (!empty($data['commande_id'])) {
                $cmd = Commande::with(['client', 'lignes.article'])->find($data['commande_id']);
                if ($cmd) {
                    $data['client_id'] = $data['client_id'] ?? $cmd->client_id;
                    $data['reference_client'] = $data['reference_client'] ?? $cmd->reference_client;
                    if (empty($lignes)) {
                        $lignes = $cmd->lignes->values()->map(
                            fn ($l, $i) => BonLivraison::ligneFromCommandeLigne($l, $i)
                        )->all();
                    }
                    if (!in_array($cmd->statut, ['livree', 'cloturee'], true)) {
                        $cmd->update(['statut' => 'livree']);
                    }
                }
            }

            $bl = BonLivraison::create($data);
            $this->syncLignes($bl, $lignes);
            $bl->recalculateTotals();

            AuditLog::record($bl, 'create', null, null, $bl->numero, $request->user()?->id);

            return $bl;
        });

        return response()->json($bl->load(['client', 'commande', 'lignes']), 201);
    }

    public function update(Request $request, BonLivraison $bonLivraison)
    {
        $data = $this->validateBl($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($bonLivraison, $data, $lignes, $request) {
            $bonLivraison->update($data);

            if (is_array($lignes)) {
                $bonLivraison->lignes()->delete();
                $this->syncLignes($bonLivraison, $lignes);
                $bonLivraison->recalculateTotals();
            }

            AuditLog::record($bonLivraison, 'update', null, null, $bonLivraison->numero, $request->user()?->id);
        });

        return response()->json($bonLivraison->fresh()->load(['client', 'commande', 'lignes.article']));
    }

    /**
     * Suppression d'un bon de livraison.
     *  - mode=remettre_commande : supprime le BL (et ses lignes) ; la commande d'origine
     *    réapparaît dans la liste des commandes avec son statut d'avant transformation.
     *  - mode=definitif : supprime le BL (et ses lignes) ET la commande d'origine (et ses lignes).
     * Refusé si le BL a déjà été facturé (supprimer d'abord la facture).
     */
    public function destroy(Request $request, BonLivraison $bonLivraison)
    {
        $data = $request->validate([
            'mode' => 'required|in:definitif,remettre_commande',
        ]);

        if ($facture = $bonLivraison->factureLocale()->first()) {
            return response()->json([
                'message' => "Ce bon de livraison est facturé ({$facture->numero}). Supprimez d'abord la facture.",
            ], 422);
        }

        $userId = $request->user()?->id;

        if ($data['mode'] === 'remettre_commande' && !$bonLivraison->commande_id) {
            return response()->json([
                'message' => "Ce bon de livraison n'a pas été créé depuis une commande : utilisez la suppression définitive.",
            ], 422);
        }

        $message = DB::transaction(function () use ($bonLivraison, $data, $userId) {
            $commande = $bonLivraison->commande_id ? Commande::lockForUpdate()->find($bonLivraison->commande_id) : null;

            AuditLog::record($bonLivraison, $data['mode'] === 'definitif' ? 'delete' : 'delete_revert_commande', null, $bonLivraison->numero, $commande?->numero, $userId);
            $bonLivraison->lignes()->delete();
            $bonLivraison->delete();

            if (!$commande) {
                return 'Bon de livraison supprimé définitivement';
            }

            if ($data['mode'] === 'definitif') {
                AuditLog::record($commande, 'delete', null, $commande->numero, null, $userId);
                $commande->delete(); // lignes supprimées en cascade
                return "Bon de livraison et commande {$commande->numero} supprimés définitivement";
            }

            // Remettre en commande : restaurer le statut d'avant la transformation
            if ($commande->statut === 'livree') {
                $previous = \App\Models\AuditLog::where('entity_type', 'Commande')
                    ->where('entity_id', $commande->id)
                    ->where('action', 'update')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn ($l) => is_array($l->details) ? $l->details : json_decode((string) $l->details, true))
                    ->first(fn ($d) => ($d['champ'] ?? null) === 'statut' && ($d['nouvelle_valeur'] ?? null) === 'livree');
                $restore = $previous['ancienne_valeur'] ?? 'confirmee';
                if (!in_array($restore, Commande::STATUTS, true) || $restore === 'livree') {
                    $restore = 'confirmee';
                }
                $commande->update(['statut' => $restore]);
                AuditLog::record($commande, 'update', 'statut', 'livree', $restore, $userId);
            }

            return "Bon de livraison supprimé — la commande {$commande->numero} est revenue dans la liste des commandes";
        });

        return response()->json(['message' => $message]);
    }

    private function validateBl(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_creation' => 'nullable|date',
            'commande_id' => 'nullable|exists:commandes,id',
            'client_id' => 'nullable|exists:clients,id',
            'reference_client' => 'nullable|string|max:100',
            'date_heure_livraison' => 'nullable|date',
            'informations_additionnelles' => 'nullable|string',
            'statut' => 'nullable|in:' . implode(',', BonLivraison::STATUTS),
            'lignes' => 'nullable|array',
            'lignes.*.ref_article' => 'nullable|string|max:100',
            'lignes.*.designation' => 'nullable|string|max:255',
            'lignes.*.calibre' => 'nullable|string|max:100',
            'lignes.*.emballage' => 'nullable|string|max:255',
            'lignes.*.numero_lot' => 'nullable|string|max:100',
            'lignes.*.total_colis' => 'nullable|numeric',
            'lignes.*.poids_net_eg_unitaire' => 'nullable|numeric',
            'lignes.*.total_poids_net' => 'nullable|numeric',
            'lignes.*.total_poids_total' => 'nullable|numeric',
            'lignes.*.article_id' => 'nullable|exists:articles,id',
            'lignes.*.commande_ligne_id' => 'nullable|exists:commande_lignes,id',
        ]);
    }

    private function syncLignes(BonLivraison $bl, array $lignes): void
    {
        foreach (array_values($lignes) as $i => $ligne) {
            if (
                empty($ligne['article_id'])
                && empty($ligne['ref_article'])
                && empty($ligne['designation'])
            ) {
                continue;
            }

            $bl->lignes()->create([
                'commande_ligne_id' => $ligne['commande_ligne_id'] ?? null,
                'article_id' => $ligne['article_id'] ?? null,
                'ref_article' => $ligne['ref_article'] ?? null,
                'designation' => $ligne['designation'] ?? null,
                'calibre' => $ligne['calibre'] ?? null,
                'emballage' => $ligne['emballage'] ?? null,
                'numero_lot' => $ligne['numero_lot'] ?? null,
                'total_colis' => $ligne['total_colis'] ?? 0,
                'poids_net_eg_unitaire' => $ligne['poids_net_eg_unitaire'] ?? null,
                'total_poids_net' => $ligne['total_poids_net'] ?? 0,
                'total_poids_total' => $ligne['total_poids_total'] ?? 0,
                'ordre' => $ligne['ordre'] ?? $i,
            ]);
        }
    }
}
