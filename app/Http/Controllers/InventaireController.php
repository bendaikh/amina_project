<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Inventaire;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventaireController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request)
    {
        $query = Inventaire::with(['location', 'lignes'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%");
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
            'statuts' => Inventaire::STATUTS,
            'emplacements' => StockLocation::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function show(Inventaire $inventaire)
    {
        $inventaire->load(['location', 'lignes.article', 'lignes.location', 'creator']);

        return response()->json($inventaire);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle' => 'nullable|string|max:255',
            'date_inventaire' => 'nullable|date',
            'stock_location_id' => 'nullable|exists:stock_locations,id',
            'observations' => 'nullable|string',
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'exists:articles,id',
            'inclure_tous' => 'nullable|boolean',
        ]);

        $inv = DB::transaction(function () use ($data, $request) {
            $inv = Inventaire::create([
                'numero' => Inventaire::nextNumero(),
                'libelle' => $data['libelle'] ?? null,
                'date_inventaire' => $data['date_inventaire'] ?? now()->toDateString(),
                'statut' => 'en_cours',
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'observations' => $data['observations'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $this->populateLignes(
                $inv,
                $data['inclure_tous'] ?? false,
                $data['article_ids'] ?? [],
                $data['stock_location_id'] ?? null
            );

            AuditLog::record($inv, 'create', null, null, $inv->numero, $request->user()?->id);

            return $inv;
        });

        return response()->json($inv->load(['location', 'lignes.article', 'lignes.location']), 201);
    }

    public function update(Request $request, Inventaire $inventaire)
    {
        if ($inventaire->statut === 'valide') {
            return response()->json(['message' => 'Inventaire déjà validé'], 422);
        }

        $data = $request->validate([
            'libelle' => 'nullable|string|max:255',
            'date_inventaire' => 'nullable|date',
            'observations' => 'nullable|string',
            'lignes' => 'nullable|array',
            'lignes.*.id' => 'required|exists:inventaire_lignes,id',
            'lignes.*.quantite_physique' => 'nullable|numeric',
            'lignes.*.observations' => 'nullable|string',
        ]);

        DB::transaction(function () use ($inventaire, $data, $request) {
            $inventaire->update(collect($data)->only(['libelle', 'date_inventaire', 'observations'])->filter(fn ($v) => $v !== null)->all());

            foreach ($data['lignes'] ?? [] as $l) {
                $ligne = $inventaire->lignes()->where('id', $l['id'])->first();
                if (!$ligne) {
                    continue;
                }
                if (array_key_exists('quantite_physique', $l)) {
                    $ligne->quantite_physique = $l['quantite_physique'];
                    $ligne->ecart = $l['quantite_physique'] !== null
                        ? (float) $l['quantite_physique'] - (float) $ligne->quantite_theorique
                        : 0;
                }
                if (array_key_exists('observations', $l)) {
                    $ligne->observations = $l['observations'];
                }
                $ligne->save();
            }

            AuditLog::record($inventaire, 'update', null, null, $inventaire->numero, $request->user()?->id);
        });

        return response()->json($inventaire->fresh()->load(['location', 'lignes.article', 'lignes.location']));
    }

    public function valider(Request $request, Inventaire $inventaire)
    {
        if ($inventaire->statut === 'valide') {
            return response()->json(['message' => 'Déjà validé'], 422);
        }

        $inv = $this->stock->validerInventaire($inventaire, $request->user()?->id);
        AuditLog::record($inv, 'validate', 'statut', 'en_cours', 'valide', $request->user()?->id);

        return response()->json($inv);
    }

    public function destroy(Inventaire $inventaire)
    {
        if ($inventaire->statut === 'valide') {
            return response()->json(['message' => 'Impossible de supprimer un inventaire validé'], 422);
        }

        $inventaire->delete();

        return response()->json(['message' => 'Inventaire supprimé']);
    }

    private function populateLignes(Inventaire $inv, bool $inclureTous, array $articleIds, ?int $locationId): void
    {
        $balancesQuery = StockBalance::query();
        if ($locationId) {
            $balancesQuery->where('stock_location_id', $locationId);
        }

        if (!$inclureTous && !empty($articleIds)) {
            $balancesQuery->whereIn('article_id', $articleIds);
        } elseif (!$inclureTous && empty($articleIds)) {
            // default: all articles with stock or all active articles
            $balancesQuery->where(function ($q) {
                $q->where('stock_theorique', '!=', 0)
                    ->orWhere('stock_reserve', '!=', 0);
            });
        }

        $balances = $balancesQuery->get();
        $seen = [];

        foreach ($balances as $b) {
            $key = $b->article_id . '|' . ($b->stock_location_id ?? '') . '|' . ($b->lot ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $inv->lignes()->create([
                'article_id' => $b->article_id,
                'stock_location_id' => $b->stock_location_id,
                'lot' => $b->lot ?: null,
                'quantite_theorique' => $b->stock_theorique,
                'quantite_physique' => null,
                'ecart' => 0,
            ]);
        }

        // If specific articles requested with no balance yet, add zero lines
        if (!empty($articleIds)) {
            foreach ($articleIds as $aid) {
                $exists = $inv->lignes()->where('article_id', $aid)->exists();
                if ($exists) {
                    continue;
                }
                if (!Article::where('id', $aid)->exists()) {
                    continue;
                }
                $inv->lignes()->create([
                    'article_id' => $aid,
                    'stock_location_id' => $locationId ?? $this->stock->defaultDepotId(),
                    'lot' => null,
                    'quantite_theorique' => 0,
                    'quantite_physique' => null,
                    'ecart' => 0,
                ]);
            }
        }
    }
}
