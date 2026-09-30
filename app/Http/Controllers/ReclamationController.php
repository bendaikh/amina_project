<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReclamationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reclamation::with(['client', 'article', 'commande'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('document_origine', 'like', "%{$search}%")
                    ->orWhere('motif', 'like', "%{$search}%")
                    ->orWhere('article_libelle', 'like', "%{$search}%")
                    ->orWhere('responsable', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($priorite = $request->get('priorite')) {
            $query->where('priorite', $priorite);
        }

        if ($origine = $request->get('origine')) {
            $query->where('origine', $origine);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => Reclamation::STATUTS,
            'origines' => Reclamation::ORIGINES,
            'priorites' => Reclamation::PRIORITES,
            'actions' => Reclamation::ACTIONS,
            'labels' => [
                'statuts' => [
                    'nouvelle' => 'Nouvelle',
                    'en_analyse' => "En cours d'analyse",
                    'action_en_cours' => 'Action en cours',
                    'attente_client' => 'En attente du client',
                    'resolue' => 'Résolue',
                    'cloturee' => 'Clôturée',
                    'rejetee' => 'Rejetée',
                ],
                'origines' => [
                    'commande' => 'Commande',
                    'bl' => 'Bon de livraison',
                    'facture' => 'Facture',
                    'retour' => 'Retour',
                    'vente_locale' => 'Vente locale',
                    'vente_export' => 'Vente export',
                    'article' => 'Article',
                    'client' => 'Fiche client',
                    'directe' => 'Saisie directe',
                ],
                'priorites' => [
                    'basse' => 'Basse',
                    'normale' => 'Normale',
                    'haute' => 'Haute',
                    'urgente' => 'Urgente',
                ],
                'actions' => [
                    'remplacement' => 'Remplacement',
                    'retour' => 'Retour',
                    'avoir' => 'Note de crédit / avoir',
                    'remboursement' => 'Remboursement',
                    'analyse_qualite' => 'Analyse qualité',
                    'correction_logistique' => 'Correction logistique',
                    'correction_facturation' => 'Correction facturation',
                    'information_client' => 'Information client',
                ],
            ],
        ]);
    }

    public function stats()
    {
        $base = Reclamation::query();

        return response()->json([
            'total' => (clone $base)->count(),
            'nouvelles' => (clone $base)->where('statut', 'nouvelle')->count(),
            'en_cours' => (clone $base)->whereIn('statut', ['en_analyse', 'action_en_cours', 'attente_client'])->count(),
            'resolues' => (clone $base)->whereIn('statut', ['resolue', 'cloturee'])->count(),
        ]);
    }

    public function show(Reclamation $reclamation)
    {
        $reclamation->load(['client', 'article', 'commande', 'piecesJointes', 'notesCredit']);

        return response()->json($reclamation);
    }

    public function store(Request $request)
    {
        $data = $this->validateReclamation($request);

        $reclamation = DB::transaction(function () use ($data, $request) {
            $data['numero'] = $data['numero'] ?? Reclamation::nextNumero();
            $data['date_reclamation'] = $data['date_reclamation'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;
            $data['statut'] = $data['statut'] ?? 'nouvelle';
            $data['priorite'] = $data['priorite'] ?? 'normale';

            $reclamation = Reclamation::create($data);
            AuditLog::record($reclamation, 'create', null, null, $reclamation->numero, $request->user()?->id);

            return $reclamation;
        });

        return response()->json($reclamation->load(['client', 'article', 'commande']), 201);
    }

    public function update(Request $request, Reclamation $reclamation)
    {
        $data = $this->validateReclamation($request, false);

        DB::transaction(function () use ($reclamation, $data, $request) {
            if (($data['statut'] ?? null) === 'resolue' && empty($data['date_resolution']) && !$reclamation->date_resolution) {
                $data['date_resolution'] = now()->toDateString();
            }
            if (($data['statut'] ?? null) === 'cloturee' && empty($data['date_cloture']) && !$reclamation->date_cloture) {
                $data['date_cloture'] = now()->toDateString();
            }

            $reclamation->update($data);
            AuditLog::record($reclamation, 'update', null, null, $reclamation->numero, $request->user()?->id);
        });

        return response()->json($reclamation->fresh()->load(['client', 'article', 'commande', 'piecesJointes']));
    }

    public function destroy(Reclamation $reclamation)
    {
        $reclamation->delete();

        return response()->json(['message' => 'Réclamation supprimée']);
    }

    public function changeStatut(Request $request, Reclamation $reclamation)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', Reclamation::STATUTS),
        ]);

        $old = $reclamation->statut;
        $updates = ['statut' => $request->statut];

        if ($request->statut === 'resolue' && !$reclamation->date_resolution) {
            $updates['date_resolution'] = now()->toDateString();
        }
        if ($request->statut === 'cloturee' && !$reclamation->date_cloture) {
            $updates['date_cloture'] = now()->toDateString();
        }

        $reclamation->update($updates);
        AuditLog::record($reclamation, 'update', 'statut', $old, $request->statut, $request->user()?->id);

        return response()->json($reclamation->fresh());
    }

    public function uploadPiece(Request $request, Reclamation $reclamation)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/reclamations/{$reclamation->numero}", 'local');

        $piece = $reclamation->piecesJointes()->create([
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

    private function validateReclamation(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_reclamation' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'origine' => 'nullable|in:' . implode(',', Reclamation::ORIGINES),
            'document_origine' => 'nullable|string|max:150',
            'commande_id' => 'nullable|exists:commandes,id',
            'article_id' => 'nullable|exists:articles,id',
            'article_libelle' => 'nullable|string|max:255',
            'quantite' => 'nullable|numeric',
            'numero_lot' => 'nullable|string|max:100',
            'motif' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'priorite' => 'nullable|in:' . implode(',', Reclamation::PRIORITES),
            'responsable' => 'nullable|string|max:150',
            'date_limite' => 'nullable|date',
            'action_prevue' => 'nullable|in:' . implode(',', Reclamation::ACTIONS),
            'action_corrective' => 'nullable|string',
            'reponse' => 'nullable|string',
            'statut' => 'nullable|in:' . implode(',', Reclamation::STATUTS),
            'date_resolution' => 'nullable|date',
            'date_cloture' => 'nullable|date',
        ]);
    }
}
