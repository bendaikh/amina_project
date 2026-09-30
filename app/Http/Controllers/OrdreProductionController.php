<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\OrdreProduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdreProductionController extends Controller
{
    public function index(Request $request)
    {
        $query = OrdreProduction::with(['commande.client', 'article'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('responsable', 'like', "%{$search}%")
                    ->orWhere('equipe', 'like', "%{$search}%")
                    ->orWhereHas('commande', fn ($c) => $c->where('numero', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($commandeId = $request->get('commande_id')) {
            $query->where('commande_id', $commandeId);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => OrdreProduction::STATUTS,
            'labels' => [
                'a_planifier' => 'À planifier',
                'planifiee' => 'Planifiée',
                'en_cours' => 'En cours',
                'partiellement_terminee' => 'Partiellement terminée',
                'terminee' => 'Terminée',
                'bloquee' => 'Bloquée',
                'annulee' => 'Annulée',
            ],
        ]);
    }

    public function show(OrdreProduction $ordreProduction)
    {
        $ordreProduction->load(['commande.client', 'article', 'ligne', 'piecesJointes']);

        return response()->json($ordreProduction);
    }

    public function store(Request $request)
    {
        $data = $this->validateOrdre($request);

        $ordre = DB::transaction(function () use ($data, $request) {
            $data['numero'] = $data['numero'] ?? OrdreProduction::nextNumero();
            $data['created_by'] = $request->user()?->id;
            $data['statut'] = $data['statut'] ?? 'a_planifier';

            if (!empty($data['commande_ligne_id'])) {
                $ligne = CommandeLigne::with('article')->find($data['commande_ligne_id']);
                if ($ligne) {
                    $data['commande_id'] = $data['commande_id'] ?? $ligne->commande_id;
                    $data['article_id'] = $data['article_id'] ?? $ligne->article_id;
                    $data['designation'] = $data['designation'] ?? $ligne->designation;
                    $data['quantite_commandee'] = $data['quantite_commandee'] ?? $ligne->quantite;
                    $data['quantite_a_produire'] = $data['quantite_a_produire'] ?? $ligne->quantite_a_produire;
                }
            } elseif (!empty($data['article_id']) && empty($data['designation'])) {
                $data['designation'] = Article::find($data['article_id'])?->designation;
            }

            $ordre = OrdreProduction::create($data);

            if ($ordre->commande_id) {
                $cmd = Commande::find($ordre->commande_id);
                if ($cmd && in_array($cmd->statut, ['brouillon', 'en_attente', 'confirmee', 'en_preparation'], true)) {
                    $cmd->update(['statut' => 'en_production']);
                }
            }

            AuditLog::record($ordre, 'create', null, null, $ordre->numero, $request->user()?->id);

            return $ordre;
        });

        return response()->json($ordre->load(['commande.client', 'article']), 201);
    }

    public function update(Request $request, OrdreProduction $ordreProduction)
    {
        $data = $this->validateOrdre($request, false);

        DB::transaction(function () use ($ordreProduction, $data, $request) {
            $ordreProduction->update($data);
            AuditLog::record($ordreProduction, 'update', null, null, $ordreProduction->numero, $request->user()?->id);
        });

        return response()->json($ordreProduction->fresh()->load(['commande.client', 'article', 'ligne']));
    }

    public function destroy(OrdreProduction $ordreProduction)
    {
        $ordreProduction->delete();

        return response()->json(['message' => 'Ordre de production supprimé']);
    }

    public function changeStatut(Request $request, OrdreProduction $ordreProduction)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', OrdreProduction::STATUTS),
        ]);

        $old = $ordreProduction->statut;
        $ordreProduction->update(['statut' => $request->statut]);
        AuditLog::record($ordreProduction, 'update', 'statut', $old, $request->statut, $request->user()?->id);

        return response()->json($ordreProduction->fresh());
    }

    private function validateOrdre(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'commande_id' => 'nullable|exists:commandes,id',
            'commande_ligne_id' => 'nullable|exists:commande_lignes,id',
            'article_id' => 'nullable|exists:articles,id',
            'designation' => 'nullable|string|max:255',
            'quantite_commandee' => 'nullable|numeric',
            'quantite_a_produire' => 'nullable|numeric',
            'quantite_produite' => 'nullable|numeric',
            'date_planifiee' => 'nullable|date',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'responsable' => 'nullable|string|max:100',
            'equipe' => 'nullable|string|max:100',
            'statut' => 'nullable|in:' . implode(',', OrdreProduction::STATUTS),
            'observations' => 'nullable|string',
            'effet_sortie_matieres' => 'nullable|boolean',
            'effet_entree_pf' => 'nullable|boolean',
            'effet_sous_produits' => 'nullable|boolean',
            'effet_dechets' => 'nullable|boolean',
        ]);
    }
}
