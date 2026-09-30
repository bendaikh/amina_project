<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\AchatLigne;
use App\Models\AchatReception;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\BonReception;
use App\Models\Fournisseur;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AchatController extends Controller
{
    public function index(Request $request)
    {
        $query = Achat::with(['fournisseur', 'lignes'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference_commande', 'like', "%{$search}%")
                    ->orWhere('reference_facture', 'like', "%{$search}%")
                    ->orWhere('acheteur', 'like', "%{$search}%")
                    ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => Achat::STATUTS,
            'types' => Achat::TYPES,
            'categories_stockable' => Achat::CATEGORIES_STOCKABLE,
            'categories_non_stockable' => Achat::CATEGORIES_NON_STOCKABLE,
            'processus' => [
                'demande' => 'Demande d\'achat',
                'commande' => 'Commande fournisseur',
                'reception' => 'Réception',
                'facture' => 'Facture fournisseur',
                'echeance' => 'Échéance',
                'regle' => 'Règlement',
            ],
        ]);
    }

    public function show(Achat $achat)
    {
        $achat->load([
            'fournisseur',
            'lignes.article',
            'receptions',
            'factures.receptions',
            'piecesJointes',
        ]);

        return response()->json($achat);
    }

    public function store(Request $request)
    {
        $data = $this->validateAchat($request);
        $lignes = $request->input('lignes', []);

        $achat = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? Achat::nextNumero();
            $data['date_achat'] = $data['date_achat'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;
            $data['genere_entree_stock'] = ($data['type'] ?? 'stockable') === 'stockable'
                ? (bool) ($data['genere_entree_stock'] ?? true)
                : false;

            $achat = Achat::create($data);
            $this->syncLignes($achat, $lignes);
            $achat->recalculateTotals();

            AuditLog::record($achat, 'create', null, null, $achat->numero, $request->user()?->id);

            return $achat;
        });

        return response()->json($achat->load(['fournisseur', 'lignes']), 201);
    }

    public function update(Request $request, Achat $achat)
    {
        $data = $this->validateAchat($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($achat, $data, $lignes, $request) {
            if (isset($data['type']) && $data['type'] === 'non_stockable') {
                $data['genere_entree_stock'] = false;
            }

            $achat->update($data);

            if (is_array($lignes)) {
                $achat->lignes()->delete();
                $this->syncLignes($achat, $lignes);
                $achat->recalculateTotals();
            }

            AuditLog::record($achat, 'update', null, null, $achat->numero, $request->user()?->id);
        });

        return response()->json($achat->fresh()->load(['fournisseur', 'lignes.article', 'receptions', 'factures']));
    }

    public function destroy(Achat $achat)
    {
        $achat->delete();

        return response()->json(['message' => 'Achat supprimé']);
    }

    public function changeStatut(Request $request, Achat $achat)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', Achat::STATUTS),
        ]);

        $old = $achat->statut;
        $achat->update(['statut' => $request->statut]);
        AuditLog::record($achat, 'update', 'statut', $old, $request->statut, $request->user()?->id);

        return response()->json($achat->fresh());
    }

    public function storeReception(Request $request, Achat $achat)
    {
        $data = $request->validate([
            'date_reception' => 'nullable|date',
            'observations' => 'nullable|string',
            'valider_stock' => 'nullable|boolean',
            'lignes' => 'nullable|array',
        ]);

        $stock = app(StockService::class);
        $entreeStock = $achat->type === 'stockable' && $achat->genere_entree_stock;
        $valider = $request->boolean('valider_stock', $entreeStock);

        $result = DB::transaction(function () use ($achat, $data, $request, $stock, $entreeStock, $valider) {
            $numero = BonReception::nextNumero();

            $reception = $achat->receptions()->create([
                'numero' => $numero,
                'date_reception' => $data['date_reception'] ?? now()->toDateString(),
                'observations' => $data['observations'] ?? null,
                'statut' => 'recue',
                'entree_stock_generee' => false,
                'created_by' => $request->user()?->id,
            ]);

            $br = BonReception::create([
                'numero' => $numero,
                'date_reception' => $reception->date_reception,
                'fournisseur_id' => $achat->fournisseur_id,
                'achat_id' => $achat->id,
                'achat_reception_id' => $reception->id,
                'reference_achat' => $achat->numero,
                'reference_commande' => $achat->reference_commande,
                'reference_facture' => $achat->reference_facture,
                'statut' => 'brouillon',
                'observations' => $data['observations'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $defaultLoc = $stock->defaultDepotId();
            $lignesInput = $data['lignes'] ?? null;

            if (is_array($lignesInput) && count($lignesInput)) {
                foreach ($lignesInput as $l) {
                    $br->lignes()->create([
                        'article_id' => $l['article_id'] ?? null,
                        'designation' => $l['designation'] ?? null,
                        'quantite_commandee' => $l['quantite_commandee'] ?? $l['quantite'] ?? 0,
                        'quantite_recue' => $l['quantite_recue'] ?? $l['quantite'] ?? 0,
                        'unite' => $l['unite'] ?? null,
                        'lot' => $l['lot'] ?? null,
                        'date_production' => $l['date_production'] ?? null,
                        'date_peremption' => $l['date_peremption'] ?? null,
                        'stock_location_id' => $l['stock_location_id'] ?? $defaultLoc,
                        'controle_qualite' => $l['controle_qualite'] ?? 'ok',
                        'observations' => $l['observations'] ?? null,
                    ]);
                }
            } else {
                $achat->load('lignes');
                foreach ($achat->lignes as $al) {
                    $br->lignes()->create([
                        'article_id' => $al->article_id,
                        'designation' => $al->designation,
                        'quantite_commandee' => $al->quantite,
                        'quantite_recue' => $al->quantite,
                        'unite' => $al->unite,
                        'lot' => $al->lot,
                        'stock_location_id' => $defaultLoc,
                        'controle_qualite' => 'ok',
                    ]);
                }
            }

            if ($valider && $entreeStock) {
                $br = $stock->validerBonReception($br, $request->user()?->id, true);
                $reception->update([
                    'entree_stock_generee' => true,
                    'statut' => 'valide',
                ]);
            }

            if ($achat->statut === 'demande' || $achat->statut === 'commande') {
                $achat->update(['statut' => 'reception']);
            }

            return $reception->fresh()->load([]);
        });

        // Attach bon for response convenience
        $bon = BonReception::with(['lignes.article', 'lignes.location'])
            ->where('achat_reception_id', $result->id)
            ->first();

        return response()->json([
            ...$result->toArray(),
            'bon_reception' => $bon,
        ], 201);
    }

    public function uploadPiece(Request $request, Achat $achat)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/achats/{$achat->numero}", 'local');

        $piece = $achat->piecesJointes()->create([
            'type' => $request->type ?? 'autre',
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin' => $path,
            'mime' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json($piece, 201);
    }

    public function downloadPiece($pieceId)
    {
        $piece = \App\Models\PieceJointe::findOrFail($pieceId);

        if (!$piece->chemin || !Storage::disk('local')->exists($piece->chemin)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        return Storage::disk('local')->download($piece->chemin, $piece->nom_fichier);
    }

    private function validateAchat(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_achat' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'reference_commande' => 'nullable|string|max:100',
            'reference_facture' => 'nullable|string|max:100',
            'date_facture' => 'nullable|date',
            'devise' => 'nullable|string|max:10',
            'conditions' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:100',
            'echeance' => 'nullable|date',
            'acheteur' => 'nullable|string|max:100',
            'observations' => 'nullable|string',
            'type' => 'nullable|in:stockable,non_stockable',
            'categorie' => 'nullable|string|max:100',
            'statut' => 'nullable|in:' . implode(',', Achat::STATUTS),
            'genere_entree_stock' => 'nullable|boolean',
            'lignes' => 'nullable|array',
        ]);
    }

    private function syncLignes(Achat $achat, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            if (empty($ligne['article_id']) && empty($ligne['designation'])) {
                continue;
            }

            $amounts = AchatLigne::computeAmounts($ligne);
            $designation = $ligne['designation'] ?? null;

            if (!$designation && !empty($ligne['article_id'])) {
                $article = Article::find($ligne['article_id']);
                $designation = $article?->designation;
            }

            $achat->lignes()->create([
                'article_id' => $ligne['article_id'] ?? null,
                'designation' => $designation,
                'quantite' => $ligne['quantite'] ?? 0,
                'unite' => $ligne['unite'] ?? null,
                'prix' => $ligne['prix'] ?? 0,
                'remise' => $ligne['remise'] ?? 0,
                'tva_taux' => $ligne['tva_taux'] ?? 20,
                'montant_ht' => $amounts['montant_ht'],
                'montant_tva' => $amounts['montant_tva'],
                'montant_ttc' => $amounts['montant_ttc'],
                'lot' => $ligne['lot'] ?? null,
                'date_prevue' => $ligne['date_prevue'] ?? null,
            ]);
        }
    }
}
