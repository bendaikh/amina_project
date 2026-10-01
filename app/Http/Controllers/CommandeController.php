<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\DocumentGenere;
use App\Models\Exportation;
use App\Models\LivraisonConteneur;
use App\Models\StockBalance;
use App\Services\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $query = Commande::with(['client', 'lignes'])
            ->orderByDesc('created_at');

        // Les commandes transformées en BL n'apparaissent plus dans la liste
        if (!$request->boolean('include_bl')) {
            $query->whereDoesntHave('bonLivraison');
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference_client', 'like', "%{$search}%")
                    ->orWhere('commercial', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"));
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
            'statuts' => Commande::STATUTS,
            'types' => Commande::TYPES,
            'priorites' => Commande::PRIORITES,
            'processus' => [
                'brouillon' => 'Brouillon',
                'en_attente' => 'En attente',
                'confirmee' => 'Confirmée',
                'en_preparation' => 'En préparation',
                'en_production' => 'En production',
                'partiellement_livree' => 'Partiellement livrée',
                'livree' => 'Livrée',
                'cloturee' => 'Clôturée',
            ],
            'incoterms' => ['EXW', 'FCA', 'FOB', 'CIF', 'CFR', 'DAP', 'DDP', 'CPT', 'CIP'],
        ]);
    }

    public function show(Commande $commande)
    {
        $commande->load($this->detailRelations());

        return response()->json($commande);
    }

    public function store(Request $request)
    {
        $data = $this->validateCommande($request);
        $lignes = $request->input('lignes', []);

        $commande = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? Commande::nextNumero();
            $data['date_commande'] = $data['date_commande'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;
            $data['statut'] = $data['statut'] ?? 'brouillon';

            $commande = Commande::create($data);
            $this->syncLignes($commande, $lignes);
            $commande->recalculateTotals();

            AuditLog::record($commande, 'create', null, null, $commande->numero, $request->user()?->id);

            return $commande;
        });

        return response()->json($commande->load(['client', 'lignes']), 201);
    }

    public function update(Request $request, Commande $commande)
    {
        $data = $this->validateCommande($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($commande, $data, $lignes, $request) {
            $commande->update($data);

            if (is_array($lignes)) {
                $commande->lignes()->delete();
                $this->syncLignes($commande, $lignes);
                $commande->recalculateTotals();
            }

            AuditLog::record($commande, 'update', null, null, $commande->numero, $request->user()?->id);
        });

        return response()->json($commande->fresh()->load($this->detailRelations()));
    }

    public function destroy(Commande $commande)
    {
        $commande->delete();

        return response()->json(['message' => 'Commande supprimée']);
    }

    public function changeStatut(Request $request, Commande $commande)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', Commande::STATUTS),
        ]);

        $old = $commande->statut;
        $commande->update(['statut' => $request->statut]);
        AuditLog::record($commande, 'update', 'statut', $old, $request->statut, $request->user()?->id);

        return response()->json($commande->fresh()->load($this->detailRelations()));
    }

    public function marquerAFacturer(Request $request, Commande $commande)
    {
        $commande->loadMissing('lignes');
        $total = (float) ($commande->total_ttc ?: $commande->lignes->sum('montant_ttc'));
        $commande->update(['montant_facture' => $total]);
        AuditLog::record($commande, 'update', 'montant_facture', null, (string) $total, $request->user()?->id);

        return response()->json($commande->fresh()->load($this->detailRelations()));
    }

    public function listDocuments(Commande $commande)
    {
        $exportIds = $commande->exportations()->pluck('id');
        $docs = DocumentGenere::whereIn('exportation_id', $exportIds)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $docs]);
    }

    public function generateDocument(Request $request, Commande $commande, DocumentGenerationService $service)
    {
        $request->validate([
            'type' => 'required|in:solas_vgm,fiche_chauffeur,attestation_conditionnement,instructions_bl',
            'conteneur_id' => 'nullable|integer|exists:livraison_conteneurs,id',
        ]);

        try {
            $commande->load(['client', 'lignes.article', 'livraisons.conteneurs', 'exportations']);
            $export = $this->resolveOrCreateExportation($commande, $request->user()?->id);
            $this->syncExportFromCommandeLogistique($export, $commande, $request->input('conteneur_id'));

            if ($request->type === 'solas_vgm') {
                $this->ensureVgmReady($export, $commande, $request->input('conteneur_id'));
            }

            $doc = $service->generate($export->fresh(), $request->type, $request->user()?->id);

            return response()->json($doc, 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function uploadPiece(Request $request, Commande $commande)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/commandes/{$commande->numero}", 'local');

        $piece = $commande->piecesJointes()->create([
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

    private function detailRelations(): array
    {
        return [
            'client',
            'lignes.article',
            'productions.article',
            'livraisons.conteneurs',
            'exportations',
            'bonLivraison',
            'piecesJointes',
        ];
    }

    private function resolveOrCreateExportation(Commande $commande, ?int $userId = null): Exportation
    {
        $existing = $commande->exportations()->orderByDesc('id')->first();
        if ($existing) {
            return $existing->loadMissing(['client', 'exportateur', 'lignes']);
        }

        /** @var ExportationController $exportCtrl */
        $exportCtrl = app(ExportationController::class);
        $response = $exportCtrl->fromCommande(request(), $commande);
        $payload = $response->getData(true);

        return Exportation::with(['client', 'exportateur', 'lignes'])->findOrFail($payload['id']);
    }

    private function syncExportFromCommandeLogistique(Exportation $export, Commande $commande, $conteneurId = null): void
    {
        $liv = $commande->livraisons->sortByDesc('id')->first();
        if (!$liv) {
            return;
        }

        $conteneur = null;
        if ($conteneurId) {
            $conteneur = LivraisonConteneur::where('id', $conteneurId)
                ->where('livraison_id', $liv->id)
                ->first();
        }
        if (!$conteneur) {
            $conteneur = $liv->relationLoaded('conteneurs')
                ? $liv->conteneurs->sortBy('ordre')->first()
                : $liv->conteneurs()->orderBy('ordre')->orderBy('id')->first();
        }

        $updates = array_filter([
            'booking' => $liv->numero_booking ?: $liv->numero_reservation,
            'numero_swb_bl' => $liv->numero_bl_swb,
            'compagnie_maritime' => $liv->compagnie_maritime,
            'navire' => $liv->navire,
            'port_chargement' => $liv->port_depart,
            'port_destination' => $liv->port_arrivee,
            'etd' => optional($liv->etd)->format('Y-m-d'),
            'eta' => optional($liv->eta)->format('Y-m-d'),
            'conteneur' => $conteneur?->numero_conteneur ?: $liv->numero_conteneur,
            'plomb_scelle' => $conteneur?->numero_plomb ?: $liv->numero_plomb,
            'transporteur' => $conteneur?->transporteur ?: $liv->transporteur,
            'chauffeur' => $conteneur?->chauffeur ?: $liv->chauffeur,
            'chauffeur_cin' => $conteneur?->cin_chauffeur ?: $liv->cin_chauffeur,
            'immatriculation' => $conteneur?->matricule_camion ?: $liv->matricule_camion,
            'mode_transport' => $commande->type === 'export' ? 'maritime' : $export->mode_transport,
        ], fn ($v) => $v !== null && $v !== '');

        if (!empty($updates)) {
            $export->update($updates);
        }
    }

    private function ensureVgmReady(Exportation $export, Commande $commande, $conteneurId = null): void
    {
        if ($export->vgm_valide) {
            return;
        }

        $liv = $commande->livraisons->sortByDesc('id')->first();
        $conteneur = null;
        if ($liv && $conteneurId) {
            $conteneur = $liv->conteneurs->firstWhere('id', (int) $conteneurId);
        }
        if ($liv && !$conteneur) {
            $conteneur = $liv->conteneurs->sortBy('ordre')->first();
        }

        $tare = $conteneur?->tare_conteneur ?: $liv?->tare_conteneur;
        $hasConteneur = filled($conteneur?->numero_conteneur ?: $liv?->numero_conteneur ?: $export->conteneur);
        $poids = $export->vgm_poids;
        if ((!$poids || (float) $poids <= 0) && is_numeric($tare)) {
            $poids = (float) $tare;
        }

        if (!$hasConteneur && (!$poids || (float) $poids <= 0)) {
            throw new \RuntimeException('VGM nécessite un N° de conteneur ou une tare/poids renseigné en logistique.');
        }

        $export->update([
            'vgm_valide' => true,
            'vgm_poids' => $poids ?: $export->vgm_poids ?: 0,
        ]);
    }

    private function validateCommande(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_commande' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'reference_client' => 'nullable|string|max:100',
            'commercial' => 'nullable|string|max:100',
            'type' => 'nullable|in:local,export',
            'devise' => 'nullable|string|max:10',
            'mode_paiement' => 'nullable|string|max:100',
            'incoterm' => 'nullable|string|max:20',
            'destination' => 'nullable|string|max:255',
            'adresse' => 'nullable|string',
            'date_souhaitee' => 'nullable|date',
            'priorite' => 'nullable|in:basse,normale,haute,urgente',
            'observations' => 'nullable|string',
            'statut' => 'nullable|in:' . implode(',', Commande::STATUTS),
            'montant_facture' => 'nullable|numeric',
            'montant_regle' => 'nullable|numeric',
            'lignes' => 'nullable|array',
        ]);
    }

    private function syncLignes(Commande $commande, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            if (empty($ligne['article_id']) && empty($ligne['designation'])) {
                continue;
            }

            $amounts = CommandeLigne::computeAmounts($ligne);
            $designation = $ligne['designation'] ?? null;
            $cout = (float) ($ligne['cout_unitaire'] ?? 0);
            $disponible = (float) ($ligne['quantite_disponible'] ?? 0);
            $article = null;

            if (!empty($ligne['article_id'])) {
                $article = Article::find($ligne['article_id']);
                if (!$designation) {
                    $designation = $article?->designation;
                }
                if ($disponible <= 0) {
                    $disponible = (float) StockBalance::where('article_id', $ligne['article_id'])
                        ->get()
                        ->sum(fn ($b) => $b->stock_disponible);
                }
            }

            $qte = (float) ($ligne['quantite'] ?? 0);
            $reservee = (float) ($ligne['quantite_reservee'] ?? min($disponible, $qte));
            $aProduire = (float) ($ligne['quantite_a_produire'] ?? max(0, $qte - $reservee));

            $commande->lignes()->create([
                'article_id' => $ligne['article_id'] ?? null,
                'designation' => $designation,
                'calibre' => $ligne['calibre'] ?? ($article?->calibre),
                'type_emballage_primaire' => $ligne['type_emballage_primaire'] ?? ($article?->type_emballage_primaire),
                'reference_emballage' => $ligne['reference_emballage'] ?? ($article?->type_palette),
                'type_emballage_secondaire' => $ligne['type_emballage_secondaire'] ?? ($article?->type_emballage_secondaire),
                'unites_par_colis' => $ligne['unites_par_colis'] ?? ($article?->unites_par_colis),
                'colis_par_palette' => $ligne['colis_par_palette'] ?? ($article?->colis_par_palette),
                'nombre_total_par_palette' => $ligne['nombre_total_par_palette'] ?? ($article?->nombre_total_par_palette),
                'poids_net_egoutte' => $ligne['poids_net_egoutte'] ?? ($article?->poids_net_egoutte),
                'quantite' => $qte,
                'unite' => $ligne['unite'] ?? null,
                'prix' => $ligne['prix'] ?? 0,
                'remise' => $ligne['remise'] ?? 0,
                'tva_taux' => $ligne['tva_taux'] ?? 20,
                'lot' => $ligne['lot'] ?? null,
                'date_production' => $ligne['date_production'] ?? null,
                'montant_ht' => $amounts['montant_ht'],
                'montant_tva' => $amounts['montant_tva'],
                'montant_ttc' => $amounts['montant_ttc'],
                'cout_unitaire' => $cout,
                'quantite_disponible' => $disponible,
                'quantite_reservee' => $reservee,
                'quantite_a_produire' => $aProduire,
                'quantite_livree' => $ligne['quantite_livree'] ?? 0,
            ]);
        }
    }
}
