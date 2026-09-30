<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\AchatLigne;
use App\Models\AchatReception;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\FactureFournisseur;
use App\Models\FactureFournisseurLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FactureFournisseurController extends Controller
{
    public function index(Request $request)
    {
        $query = FactureFournisseur::with(['fournisseur', 'achat', 'receptions'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($origine = $request->get('origine')) {
            $query->where('origine', $origine);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function show(FactureFournisseur $facture)
    {
        $facture->load([
            'fournisseur',
            'achat.lignes',
            'lignes.article',
            'receptions',
            'piecesJointes',
        ]);

        return response()->json($facture);
    }

    public function store(Request $request)
    {
        $data = $this->validateFacture($request);
        $lignes = $request->input('lignes', []);
        $receptionIds = $request->input('reception_ids', []);

        $facture = DB::transaction(function () use ($data, $lignes, $receptionIds, $request) {
            $data['numero'] = $data['numero'] ?? FactureFournisseur::nextNumero();
            $data['date_facture'] = $data['date_facture'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;
            $data['origine'] = $data['origine'] ?? 'directe';

            // Prefill from achat if creating from commande
            if (!empty($data['achat_id']) && empty($lignes)) {
                $achat = Achat::with('lignes')->find($data['achat_id']);
                if ($achat) {
                    $data['fournisseur_id'] = $data['fournisseur_id'] ?? $achat->fournisseur_id;
                    $data['devise'] = $data['devise'] ?? $achat->devise;
                    $data['conditions'] = $data['conditions'] ?? $achat->conditions;
                    $data['mode_paiement'] = $data['mode_paiement'] ?? $achat->mode_paiement;
                    $data['echeance'] = $data['echeance'] ?? $achat->echeance?->toDateString();
                    $data['reference'] = $data['reference'] ?? $achat->reference_commande;
                    $lignes = $achat->lignes->map(fn ($l) => [
                        'article_id' => $l->article_id,
                        'designation' => $l->designation,
                        'quantite' => $l->quantite,
                        'unite' => $l->unite,
                        'prix' => $l->prix,
                        'remise' => $l->remise,
                        'tva_taux' => $l->tva_taux,
                    ])->all();
                }
            }

            // Prefill from receptions
            if ($data['origine'] === 'reception' && !empty($receptionIds) && empty($lignes)) {
                $receptions = AchatReception::with('achat.lignes')->whereIn('id', $receptionIds)->get();
                $first = $receptions->first();
                if ($first?->achat) {
                    $achat = $first->achat;
                    $data['achat_id'] = $data['achat_id'] ?? $achat->id;
                    $data['fournisseur_id'] = $data['fournisseur_id'] ?? $achat->fournisseur_id;
                    $data['devise'] = $data['devise'] ?? $achat->devise;
                    $lignes = $achat->lignes->map(fn ($l) => [
                        'article_id' => $l->article_id,
                        'designation' => $l->designation,
                        'quantite' => $l->quantite,
                        'unite' => $l->unite,
                        'prix' => $l->prix,
                        'remise' => $l->remise,
                        'tva_taux' => $l->tva_taux,
                    ])->all();
                }
            }

            $facture = FactureFournisseur::create($data);
            $this->syncLignes($facture, $lignes);
            $facture->recalculateTotals();

            if (!empty($receptionIds)) {
                $facture->receptions()->sync($receptionIds);
            }

            if ($facture->achat_id) {
                $achat = Achat::find($facture->achat_id);
                if ($achat && in_array($achat->statut, ['demande', 'commande', 'reception'], true)) {
                    $achat->update([
                        'statut' => 'facture',
                        'reference_facture' => $facture->numero,
                        'date_facture' => $facture->date_facture,
                    ]);
                }
            }

            AuditLog::record($facture, 'create', null, null, $facture->numero, $request->user()?->id);

            return $facture;
        });

        return response()->json($facture->load(['fournisseur', 'lignes', 'receptions', 'achat']), 201);
    }

    public function update(Request $request, FactureFournisseur $facture)
    {
        $data = $this->validateFacture($request, false);
        $lignes = $request->input('lignes');
        $receptionIds = $request->input('reception_ids');

        DB::transaction(function () use ($facture, $data, $lignes, $receptionIds, $request) {
            $facture->update($data);

            if (is_array($lignes)) {
                $facture->lignes()->delete();
                $this->syncLignes($facture, $lignes);
                $facture->recalculateTotals();
            }

            if (is_array($receptionIds)) {
                $facture->receptions()->sync($receptionIds);
            }

            AuditLog::record($facture, 'update', null, null, $facture->numero, $request->user()?->id);
        });

        return response()->json(
            $facture->fresh()->load(['fournisseur', 'lignes.article', 'receptions', 'achat', 'piecesJointes'])
        );
    }

    public function destroy(FactureFournisseur $facture)
    {
        $facture->delete();

        return response()->json(['message' => 'Facture fournisseur supprimée']);
    }

    public function updateSuivi(Request $request, FactureFournisseur $facture)
    {
        $data = $request->validate([
            'suivi_facture' => 'nullable|boolean',
            'suivi_tva' => 'nullable|boolean',
            'suivi_justificatifs' => 'nullable|boolean',
            'suivi_docs_fiscaux' => 'nullable|boolean',
            'suivi_import' => 'nullable|boolean',
            'suivi_transport' => 'nullable|boolean',
            'suivi_certificats' => 'nullable|boolean',
            'suivi_echeance' => 'nullable|boolean',
            'suivi_pieces_jointes' => 'nullable|boolean',
        ]);

        $facture->update($data);

        return response()->json($facture->fresh()->load(['fournisseur', 'piecesJointes']));
    }

    public function suiviReglementaire(Request $request)
    {
        $query = FactureFournisseur::with(['fournisseur', 'achat', 'piecesJointes'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($request->boolean('incomplets')) {
            $query->where(function ($q) {
                $q->where('suivi_tva', false)
                    ->orWhere('suivi_justificatifs', false)
                    ->orWhere('suivi_docs_fiscaux', false)
                    ->orWhere('suivi_import', false)
                    ->orWhere('suivi_transport', false)
                    ->orWhere('suivi_certificats', false)
                    ->orWhere('suivi_echeance', false)
                    ->orWhere('suivi_pieces_jointes', false);
            });
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function uploadPiece(Request $request, FactureFournisseur $facture)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/factures/{$facture->numero}", 'local');

        $piece = $facture->piecesJointes()->create([
            'type' => $request->type ?? 'justificatif',
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin' => $path,
            'mime' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        $facture->update(['suivi_pieces_jointes' => true]);

        return response()->json($piece, 201);
    }

    private function validateFacture(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_facture' => 'nullable|date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'achat_id' => 'nullable|exists:achats,id',
            'reference' => 'nullable|string|max:100',
            'devise' => 'nullable|string|max:10',
            'conditions' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:100',
            'echeance' => 'nullable|date',
            'origine' => 'nullable|in:commande,reception,directe',
            'statut' => 'nullable|in:' . implode(',', FactureFournisseur::STATUTS),
            'observations' => 'nullable|string',
            'reception_ids' => 'nullable|array',
            'reception_ids.*' => 'exists:achat_receptions,id',
            'lignes' => 'nullable|array',
        ]);
    }

    private function syncLignes(FactureFournisseur $facture, array $lignes): void
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

            $facture->lignes()->create([
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
