<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\NoteCredit;
use App\Models\NoteCreditLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NoteCreditController extends Controller
{
    public function index(Request $request)
    {
        $query = NoteCredit::with(['client', 'reclamation', 'lignes'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('facture_origine', 'like', "%{$search}%")
                    ->orWhere('retour_ref', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($motif = $request->get('motif')) {
            $query->where('motif', $motif);
        }

        if ($origine = $request->get('origine')) {
            $query->where('origine', $origine);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => NoteCredit::STATUTS,
            'origines' => NoteCredit::ORIGINES,
            'motifs' => NoteCredit::MOTIFS,
            'labels' => [
                'statuts' => [
                    'brouillon' => 'Brouillon',
                    'validee' => 'Validée',
                    'appliquee' => 'Appliquée',
                    'annulee' => 'Annulée',
                ],
                'origines' => [
                    'facture' => 'Depuis facture',
                    'retour' => 'Depuis retour',
                    'reclamation' => 'Depuis réclamation',
                    'correction_commerciale' => 'Correction commerciale',
                    'erreur_prix' => 'Erreur de prix',
                    'annulation_partielle' => 'Annulation partielle',
                    'directe' => 'Saisie directe',
                ],
                'motifs' => [
                    'retour' => 'Retour',
                    'erreur_facturation' => 'Erreur de facturation',
                    'remise' => 'Remise',
                    'non_conformite' => 'Non-conformité',
                    'annulation' => 'Annulation',
                    'correction_prix' => 'Correction de prix',
                    'geste_commercial' => 'Geste commercial',
                    'autre' => 'Autre',
                ],
            ],
        ]);
    }

    public function show(NoteCredit $noteCredit)
    {
        $noteCredit->load(['client', 'reclamation', 'commande', 'lignes.article', 'piecesJointes']);

        return response()->json($noteCredit);
    }

    public function store(Request $request)
    {
        $data = $this->validateNote($request);
        $lignes = $request->input('lignes', []);

        $note = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? NoteCredit::nextNumero();
            $data['date_note'] = $data['date_note'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;
            $data['statut'] = $data['statut'] ?? 'brouillon';
            $data['devise'] = $data['devise'] ?? 'MAD';

            $note = NoteCredit::create($data);
            $this->syncLignes($note, $lignes);
            $note->recalculateTotals();

            AuditLog::record($note, 'create', null, null, $note->numero, $request->user()?->id);

            return $note;
        });

        return response()->json($note->load(['client', 'lignes', 'reclamation']), 201);
    }

    public function update(Request $request, NoteCredit $noteCredit)
    {
        $data = $this->validateNote($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($noteCredit, $data, $lignes, $request) {
            $noteCredit->update($data);

            if (is_array($lignes)) {
                $noteCredit->lignes()->delete();
                $this->syncLignes($noteCredit, $lignes);
                $noteCredit->recalculateTotals();
            }

            AuditLog::record($noteCredit, 'update', null, null, $noteCredit->numero, $request->user()?->id);
        });

        return response()->json($noteCredit->fresh()->load([
            'client', 'reclamation', 'commande', 'lignes.article', 'piecesJointes',
        ]));
    }

    public function destroy(NoteCredit $noteCredit)
    {
        $noteCredit->delete();

        return response()->json(['message' => 'Note de crédit supprimée']);
    }

    public function uploadPiece(Request $request, NoteCredit $noteCredit)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/notes-credit/{$noteCredit->numero}", 'local');

        $piece = $noteCredit->piecesJointes()->create([
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

    private function validateNote(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_note' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'origine' => 'nullable|in:' . implode(',', NoteCredit::ORIGINES),
            'facture_origine' => 'nullable|string|max:150',
            'reclamation_id' => 'nullable|exists:reclamations,id',
            'commande_id' => 'nullable|exists:commandes,id',
            'retour_ref' => 'nullable|string|max:150',
            'motif' => 'nullable|in:' . implode(',', NoteCredit::MOTIFS),
            'devise' => 'nullable|string|max:10',
            'observations' => 'nullable|string',
            'statut' => 'nullable|in:' . implode(',', NoteCredit::STATUTS),
            'lignes' => 'nullable|array',
        ]);
    }

    private function syncLignes(NoteCredit $note, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            if (empty($ligne['designation']) && empty($ligne['article_id'])) {
                continue;
            }

            $amounts = NoteCreditLigne::computeAmounts($ligne);
            $designation = $ligne['designation'] ?? null;

            if (!$designation && !empty($ligne['article_id'])) {
                $article = Article::find($ligne['article_id']);
                $designation = $article?->designation ?? $article?->nom ?? $article?->reference;
            }

            $note->lignes()->create([
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
            ]);
        }
    }
}
