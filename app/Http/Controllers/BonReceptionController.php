<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\AuditLog;
use App\Models\BonReception;
use App\Models\BonReceptionLigne;
use App\Models\StockLocation;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BonReceptionController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request)
    {
        $query = BonReception::with(['fournisseur', 'achat', 'lignes'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference_commande', 'like', "%{$search}%")
                    ->orWhere('reference_facture', 'like', "%{$search}%")
                    ->orWhere('reference_achat', 'like', "%{$search}%")
                    ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'like', "%{$search}%"));
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
            'statuts' => BonReception::STATUTS,
            'controles' => BonReceptionLigne::CONTROLES,
            'emplacements' => StockLocation::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function show(BonReception $reception)
    {
        $reception->load([
            'fournisseur',
            'achat.lignes.article',
            'lignes.article',
            'lignes.location',
            'piecesJointes',
            'creator',
        ]);

        return response()->json($reception);
    }

    public function store(Request $request)
    {
        $data = $this->validateHeader($request);
        $lignes = $request->input('lignes', []);

        $br = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? BonReception::nextNumero();
            $data['date_reception'] = $data['date_reception'] ?? now()->toDateString();
            $data['statut'] = $data['statut'] ?? 'brouillon';
            $data['created_by'] = $request->user()?->id;

            if (!empty($data['achat_id'])) {
                $achat = Achat::with('fournisseur')->find($data['achat_id']);
                if ($achat) {
                    $data['fournisseur_id'] = $data['fournisseur_id'] ?? $achat->fournisseur_id;
                    $data['reference_achat'] = $data['reference_achat'] ?? $achat->numero;
                    $data['reference_commande'] = $data['reference_commande'] ?? $achat->reference_commande;
                    $data['reference_facture'] = $data['reference_facture'] ?? $achat->reference_facture;
                }
            }

            $br = BonReception::create($data);
            $this->syncLignes($br, $lignes);
            AuditLog::record($br, 'create', null, null, $br->numero, $request->user()?->id);

            return $br;
        });

        return response()->json($br->load(['fournisseur', 'lignes.article', 'lignes.location']), 201);
    }

    public function update(Request $request, BonReception $reception)
    {
        if ($reception->statut === 'valide') {
            return response()->json(['message' => 'Bon de réception déjà validé'], 422);
        }

        $data = $this->validateHeader($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($reception, $data, $lignes, $request) {
            $reception->update($data);
            if (is_array($lignes)) {
                $reception->lignes()->delete();
                $this->syncLignes($reception, $lignes);
            }
            AuditLog::record($reception, 'update', null, null, $reception->numero, $request->user()?->id);
        });

        return response()->json($reception->fresh()->load(['fournisseur', 'lignes.article', 'lignes.location', 'piecesJointes']));
    }

    public function destroy(BonReception $reception)
    {
        if ($reception->statut === 'valide' && $reception->entree_stock_generee) {
            return response()->json(['message' => 'Impossible de supprimer un BR validé avec entrée stock'], 422);
        }

        $reception->delete();

        return response()->json(['message' => 'Bon de réception supprimé']);
    }

    public function valider(Request $request, BonReception $reception)
    {
        if ($reception->statut === 'valide') {
            return response()->json(['message' => 'Déjà validé'], 422);
        }

        if ($reception->lignes()->count() === 0) {
            return response()->json(['message' => 'Aucune ligne à valider'], 422);
        }

        $generer = $request->boolean('generer_entree_stock', true);
        // Principe: seuls les articles réellement stockés → if linked achat is non_stockable, skip
        if ($reception->achat && $reception->achat->type === 'non_stockable') {
            $generer = false;
        }
        if ($reception->achat && !$reception->achat->genere_entree_stock) {
            $generer = false;
        }

        $br = $this->stock->validerBonReception($reception, $request->user()?->id, $generer);
        AuditLog::record($br, 'validate', 'statut', 'brouillon', 'valide', $request->user()?->id);

        return response()->json($br);
    }

    public function uploadPiece(Request $request, BonReception $reception)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/receptions/{$reception->numero}", 'local');

        $piece = $reception->piecesJointes()->create([
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

    private function validateHeader(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_reception' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'achat_id' => 'nullable|exists:achats,id',
            'reference_achat' => 'nullable|string|max:100',
            'reference_commande' => 'nullable|string|max:100',
            'reference_facture' => 'nullable|string|max:100',
            'observations' => 'nullable|string',
            'statut' => 'nullable|in:' . implode(',', BonReception::STATUTS),
        ]);
    }

    private function syncLignes(BonReception $br, array $lignes): void
    {
        $defaultLoc = $this->stock->defaultDepotId();

        foreach ($lignes as $l) {
            if (empty($l['article_id']) && empty($l['designation'])) {
                continue;
            }
            $br->lignes()->create([
                'article_id' => $l['article_id'] ?? null,
                'designation' => $l['designation'] ?? null,
                'quantite_commandee' => $l['quantite_commandee'] ?? 0,
                'quantite_recue' => $l['quantite_recue'] ?? 0,
                'unite' => $l['unite'] ?? null,
                'lot' => $l['lot'] ?? null,
                'date_production' => $l['date_production'] ?? null,
                'date_peremption' => $l['date_peremption'] ?? null,
                'stock_location_id' => $l['stock_location_id'] ?? $defaultLoc,
                'controle_qualite' => $l['controle_qualite'] ?? 'ok',
                'observations' => $l['observations'] ?? null,
            ]);
        }
    }
}
