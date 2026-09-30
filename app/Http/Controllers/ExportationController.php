<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Commande;
use App\Models\DocumentGenere;
use App\Models\DossierEmballage;
use App\Models\Exportateur;
use App\Models\Exportation;
use App\Models\ExportationArticle;
use App\Models\Livraison;
use App\Services\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportationController extends Controller
{
    public function index(Request $request)
    {
        $query = Exportation::with(['client', 'exportateur', 'commande', 'lignes', 'dossierEmballage', 'documents'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('numero_facture', 'like', "%{$search}%")
                    ->orWhere('conteneur', 'like', "%{$search}%")
                    ->orWhere('booking', 'like', "%{$search}%")
                    ->orWhere('numero_swb_bl', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('reference_commande_client', 'like', "%{$search}%")
                    ->orWhere('numero_commande', 'like', "%{$search}%")
                    ->orWhere('exp_nom', 'like', "%{$search}%")
                    ->orWhere('dest_nom', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%")
                        ->orWhere('code_client', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function show(Exportation $exportation)
    {
        $exportation->load([
            'client',
            'exportateur',
            'commande.lignes.article',
            'lignes.article',
            'dossierEmballage.retours',
            'documents',
            'piecesJointes',
        ]);

        return response()->json($exportation);
    }

    public function store(Request $request)
    {
        $data = $this->validateExport($request);
        $lignes = $request->input('lignes', []);

        $export = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? DocumentGenerationService::nextExportNumber();
            $data['numero_facture'] = $data['numero_facture'] ?? DocumentGenerationService::nextInvoiceNumber();
            $data['date_creation'] = $data['date_creation'] ?? now()->toDateString();
            $data['created_by'] = $request->user()?->id;

            if (empty($data['exportateur_id'])) {
                $defaut = Exportateur::defaut();
                if ($defaut) {
                    $data['exportateur_id'] = $defaut->id;
                }
            }

            $data = $this->applyPartyDefaults($data);
            $data['numero_liste_colisage'] = $data['numero_liste_colisage'] ?? $data['numero'];

            if (!empty($data['commande_id']) && empty($lignes)) {
                $lignes = $this->lignesFromCommande((int) $data['commande_id']);
                $data = array_merge($this->metaFromCommande((int) $data['commande_id']), $data);
                $commande = Commande::with('livraisons')->find((int) $data['commande_id']);
                if ($commande) {
                    $data = array_merge($this->logistiqueFromCommande($commande), $data);
                }
            }

            $export = Exportation::create($data);
            $this->syncLignes($export, $lignes);
            $this->syncEmballageSuivi($export, $request->input('dossier_emballage', []));

            AuditLog::record($export, 'create', null, null, $export->numero, $request->user()?->id);

            return $export;
        });

        return response()->json(
            $export->load(['client', 'exportateur', 'commande', 'lignes.article', 'dossierEmballage']),
            201
        );
    }

    public function update(Request $request, Exportation $exportation)
    {
        $data = $this->validateExport($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($exportation, $data, $lignes, $request) {
            foreach ($data as $key => $value) {
                if ($exportation->{$key} != $value) {
                    AuditLog::record($exportation, 'update', $key, $exportation->{$key}, $value, $request->user()?->id);
                }
            }

            $exportation->update($data);

            if (is_array($lignes)) {
                $exportation->lignes()->delete();
                $this->syncLignes($exportation, $lignes);
            }

            $exportation->load('lignes.article');
            $this->syncEmballageSuivi($exportation->fresh(), $request->input('dossier_emballage', []));
        });

        return response()->json(
            $exportation->fresh()->load([
                'client', 'exportateur', 'commande', 'lignes.article',
                'dossierEmballage.retours', 'documents',
            ])
        );
    }

    public function destroy(Exportation $exportation)
    {
        $exportation->delete();

        return response()->json(['message' => 'Exportation supprimée']);
    }

    public function changeStatut(Request $request, Exportation $exportation)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', Exportation::STATUTS),
        ]);

        $old = $exportation->statut;
        $exportation->update(['statut' => $request->statut]);
        AuditLog::record($exportation, 'update', 'statut', $old, $request->statut, $request->user()?->id);

        return response()->json($exportation->fresh());
    }

    public function fromCommande(Request $request, Commande $commande)
    {
        $commande->load(['client', 'lignes.article', 'livraisons']);

        $exportateur = Exportateur::defaut();
        $data = array_merge(
            Exportation::fillFromExportateur($exportateur),
            Exportation::fillFromClient($commande->client),
            $this->metaFromCommande($commande->id),
            $this->logistiqueFromCommande($commande),
            [
                'commande_id' => $commande->id,
                'numero' => DocumentGenerationService::nextExportNumber(),
                'numero_facture' => DocumentGenerationService::nextInvoiceNumber(),
                'date_creation' => now()->toDateString(),
                'created_by' => $request->user()?->id,
                'origine_marchandise' => 'Maroc',
            ]
        );
        $data['numero_liste_colisage'] = $data['numero'];

        $lignes = $this->lignesFromCommande($commande->id);

        $export = DB::transaction(function () use ($data, $lignes, $request) {
            $export = Exportation::create($data);
            $this->syncLignes($export, $lignes);
            $export->load('lignes.article');
            $this->syncEmballageSuivi($export, []);
            AuditLog::record($export, 'create_from_commande', null, null, $export->numero, $request->user()?->id);

            return $export;
        });

        return response()->json(
            $export->load(['client', 'exportateur', 'commande', 'lignes.article', 'dossierEmballage']),
            201
        );
    }

    /**
     * Prépare une liste de colisage comme facture commerciale export
     * (n° facture + coordonnées bancaires / transitaire / compagnie maritime).
     */
    public function toFacture(Request $request, Exportation $exportation)
    {
        $exportation->load(['client', 'exportateur', 'commande.livraisons']);

        $updates = $this->factureAutoFillPayload($exportation);

        if (empty($exportation->numero_facture)) {
            $updates['numero_facture'] = DocumentGenerationService::nextInvoiceNumber();
        }
        if (empty($exportation->numero_liste_colisage)) {
            $updates['numero_liste_colisage'] = $exportation->numero;
        }
        if (empty($exportation->origine_marchandise)) {
            $updates['origine_marchandise'] = 'Maroc';
        }

        if (!empty($updates)) {
            $exportation->update($updates);
            AuditLog::record(
                $exportation,
                'to_facture',
                null,
                null,
                $exportation->numero_facture ?: $exportation->numero,
                $request->user()?->id
            );
        }

        return response()->json(
            $exportation->fresh()->load([
                'client', 'exportateur', 'commande', 'lignes.article',
                'dossierEmballage', 'documents',
            ])
        );
    }

    public function generateDocument(Request $request, Exportation $exportation, DocumentGenerationService $service)
    {
        $request->validate(['type' => 'required|string']);

        try {
            if ($request->type === 'pack') {
                $docs = $service->generatePack($exportation, $request->user()?->id);

                return response()->json(['documents' => $docs, 'count' => count($docs)]);
            }

            $doc = $service->generate($exportation, $request->type, $request->user()?->id);

            return response()->json($doc, 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function documentTypes()
    {
        return response()->json(DocumentGenerationService::TYPES);
    }

    public function meta()
    {
        return response()->json([
            'assurances' => Exportation::ASSURANCES,
            'types_emballage_doc' => Exportation::TYPES_EMBALLAGE_DOC,
            'envois_documents' => Exportation::ENVOIS_DOCUMENTS,
            'statuts' => Exportation::STATUTS,
            'exportateur_defaut' => Exportateur::defaut(),
        ]);
    }

    public function downloadDocument(Request $request, DocumentGenere $document)
    {
        if (!$document->chemin || !Storage::disk('local')->exists($document->chemin)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $html = Storage::disk('local')->get($document->chemin);
        $baseName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $document->titre ?: $document->type) ?: 'document';
        $format = strtolower((string) $request->get('format', 'pdf'));

        if ($format === 'html') {
            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="' . $baseName . '.html"',
            ]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        return $pdf->download($baseName . '.pdf');
    }

    public function uploadPiece(Request $request, Exportation $exportation)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/{$exportation->numero}", 'local');

        $piece = $exportation->piecesJointes()->create([
            'type' => $request->type ?? 'autre',
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin' => $path,
            'mime' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json($piece, 201);
    }

    private function applyPartyDefaults(array $data): array
    {
        if (!empty($data['exportateur_id'])) {
            $exportateur = Exportateur::find($data['exportateur_id']);
            if ($exportateur) {
                $fromExp = Exportation::fillFromExportateur($exportateur);
                foreach ($fromExp as $key => $value) {
                    if ($key === 'exportateur_id') {
                        continue;
                    }
                    if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                        $data[$key] = $value;
                    }
                }
            }
        }

        if (!empty($data['client_id'])) {
            $client = Client::find($data['client_id']);
            if ($client) {
                $fromClient = Exportation::fillFromClient($client);
                foreach ($fromClient as $key => $value) {
                    if ($key === 'client_id') {
                        continue;
                    }
                    if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                        $data[$key] = $value;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Remplit banque (exportateur), transitaire (client), compagnie maritime (logistique).
     */
    private function factureAutoFillPayload(Exportation $exportation): array
    {
        $updates = [];

        $exportateur = $exportation->exportateur;
        if (!$exportateur && $exportation->exportateur_id) {
            $exportateur = Exportateur::find($exportation->exportateur_id);
        }
        if (!$exportateur) {
            $exportateur = Exportateur::defaut();
        }
        if ($exportateur) {
            $fromExp = Exportation::fillFromExportateur($exportateur);
            foreach ($fromExp as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $current = $exportation->{$key} ?? null;
                if ($current === null || $current === '') {
                    $updates[$key] = $value;
                }
            }
        }

        $client = $exportation->client;
        if (!$client && $exportation->client_id) {
            $client = Client::find($exportation->client_id);
        }
        if ($client) {
            if (($exportation->transitaire === null || $exportation->transitaire === '') && $client->transitaire) {
                $updates['transitaire'] = $client->transitaire;
            }
        }

        $commande = $exportation->commande;
        if (!$commande && $exportation->commande_id) {
            $commande = Commande::with('livraisons')->find($exportation->commande_id);
        }
        if ($commande) {
            $log = $this->logistiqueFromCommande($commande);
            foreach ($log as $key => $value) {
                $current = $exportation->{$key} ?? null;
                if (($current === null || $current === '') && $value !== null && $value !== '') {
                    $updates[$key] = $value;
                }
            }
        }

        return $updates;
    }

    private function validateExport(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'numero_facture' => 'nullable|string|max:50',
            'date_creation' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'exportateur_id' => 'nullable|exists:exportateurs,id',
            'commande_id' => 'nullable|exists:commandes,id',
            'pays' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:255',
            'adresse_livraison' => 'nullable|string',
            'commercial' => 'nullable|string|max:150',
            'reference_commande_client' => 'nullable|string|max:150',
            'numero_commande' => 'nullable|string|max:150',
            'numero_liste_colisage' => 'nullable|string|max:150',
            'date_commande' => 'nullable|date',
            'incoterm' => 'nullable|string|max:20',
            'devise' => 'nullable|string|max:10',
            'transport_applique' => 'nullable|boolean',
            'montant_transport' => 'nullable|numeric|min:0',
            'conditions_paiement' => 'nullable|string|max:255',
            'assurance' => 'nullable|string|max:100',
            'type_emballage_doc' => 'nullable|in:perdu,sous_reserve,les_deux',
            'envoi_documents' => 'nullable|string|max:100',
            'origine_marchandise' => 'nullable|string|max:150',
            'transitaire' => 'nullable|string|max:255',
            'mode_transport' => 'nullable|in:maritime,routier,aerien',
            'booking' => 'nullable|string|max:100',
            'numero_swb_bl' => 'nullable|string|max:100',
            'compagnie_maritime' => 'nullable|string|max:150',
            'navire' => 'nullable|string|max:150',
            'voyage' => 'nullable|string|max:100',
            'port_chargement' => 'nullable|string|max:150',
            'port_destination' => 'nullable|string|max:150',
            'etd' => 'nullable|date',
            'eta' => 'nullable|date',
            'conteneur' => 'nullable|string|max:50',
            'type_conteneur' => 'nullable|string|max:50',
            'plomb_scelle' => 'nullable|string|max:50',
            'transporteur' => 'nullable|string|max:150',
            'chauffeur' => 'nullable|string|max:150',
            'chauffeur_cin' => 'nullable|string|max:50',
            'immatriculation' => 'nullable|string|max:50',
            'vgm_poids' => 'nullable|numeric',
            'vgm_valide' => 'nullable|boolean',
            'statut' => 'nullable|in:' . implode(',', Exportation::STATUTS),
            'emballages_temporaires' => 'nullable|boolean',
            'observations' => 'nullable|string',
            'exp_nom' => 'nullable|string|max:255',
            'exp_nom_societe' => 'nullable|string|max:255',
            'exp_ref_foodex' => 'nullable|string|max:255',
            'exp_adresse' => 'nullable|string',
            'exp_web' => 'nullable|string|max:255',
            'exp_telephone' => 'nullable|string|max:50',
            'exp_email' => 'nullable|email|max:255',
            'dest_nom' => 'nullable|string|max:255',
            'dest_societe' => 'nullable|string|max:255',
            'dest_adresse' => 'nullable|string',
            'dest_tva' => 'nullable|string|max:100',
            'dest_eori' => 'nullable|string|max:100',
            'dest_contact' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence' => 'nullable|string|max:255',
            'beneficiaire' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:64',
            'swift' => 'nullable|string|max:32',
            'rib' => 'nullable|string|max:64',
            'lignes' => 'nullable|array',
            'dossier_emballage' => 'nullable|array',
        ]);
    }

    private function syncLignes(Exportation $export, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            $article = !empty($ligne['article_id']) ? Article::find($ligne['article_id']) : null;

            $qty = (float) ($ligne['quantite'] ?? 0);
            $prix = (float) ($ligne['prix_unitaire'] ?? ($article->prix_vente ?? 0));
            $cartons = (int) ($ligne['cartons'] ?? 0);
            $palettes = (int) ($ligne['palettes'] ?? 0);
            $nbColis = (int) ($ligne['nb_colis'] ?? 0);
            $nombreParColis = (int) ($ligne['nombre_par_colis'] ?? ($article->unites_par_colis ?? $article->unites_par_carton ?? 0));

            if ($article && $qty > 0) {
                $unitesParCarton = (int) ($article->unites_par_carton ?? $article->unites_par_colis ?? 0);
                $cartonsParPalette = (int) ($article->cartons_par_palette ?? $article->colis_par_palette ?? 0);
                if ($cartons === 0 && $unitesParCarton > 0) {
                    $cartons = (int) ceil($qty / $unitesParCarton);
                }
                if ($palettes === 0 && $cartonsParPalette > 0 && $cartons > 0) {
                    $palettes = (int) ceil($cartons / $cartonsParPalette);
                }
            }

            if ($nbColis === 0) {
                $nbColis = $cartons > 0 ? $cartons : (int) $qty;
            }

            $totalEmballage = (int) ($ligne['total_emballage'] ?? 0);
            if ($totalEmballage === 0) {
                $totalEmballage = $nbColis * max(1, $nombreParColis > 0 ? 1 : 1);
                // total emballage = nombre de colis (unités d'emballage expédiées)
                $totalEmballage = $nbColis;
            }

            $brutUn = (float) ($ligne['poids_brut_unitaire'] ?? 0);
            $netUn = (float) ($ligne['poids_net_unitaire'] ?? 0);
            $egUn = (float) ($ligne['poids_net_egoutte_unitaire'] ?? 0);

            if ($article) {
                if ($brutUn == 0) {
                    $brutUn = (float) ($article->poids_brut_unitaire ?? $article->poids_brut ?? 0);
                }
                if ($netUn == 0) {
                    $netUn = (float) ($article->poids_net_unitaire ?? $article->poids_net ?? 0);
                }
                if ($egUn == 0) {
                    $egUn = (float) ($article->poids_net_egoutte_unitaire ?? $article->poids_net_egoutte ?? 0);
                }
            }

            $poidsBrut = (float) ($ligne['poids_brut'] ?? 0);
            $poidsNet = (float) ($ligne['poids_net'] ?? 0);
            $poidsEgoutte = (float) ($ligne['poids_egoutte'] ?? 0);

            if ($poidsBrut == 0) {
                $poidsBrut = round($brutUn * $nbColis, 3);
            }
            if ($poidsNet == 0) {
                $poidsNet = round($netUn * $nbColis, 3);
            }
            if ($poidsEgoutte == 0) {
                $poidsEgoutte = round($egUn * $nbColis, 3);
            }

            $dateProd = $ligne['date_production'] ?? $article?->date_production;
            if ($dateProd instanceof \DateTimeInterface) {
                $dateProd = $dateProd->format('Y-m-d');
            }

            $qtyFacture = $qty > 0 ? $qty : $nbColis;

            ExportationArticle::create([
                'exportation_id' => $export->id,
                'article_id' => $article?->id ?? ($ligne['article_id'] ?? null),
                'code_article' => $ligne['code_article'] ?? $article?->code_article,
                'designation' => $ligne['designation'] ?? $article?->designation,
                'hs_code' => $ligne['hs_code'] ?? $article?->hs_code,
                'calibre' => $ligne['calibre'] ?? $article?->calibre,
                'numero_lot' => $ligne['numero_lot'] ?? $article?->lot,
                'date_production' => $dateProd,
                'nb_colis' => $nbColis,
                'nombre_par_colis' => $nombreParColis ?: null,
                'total_emballage' => $totalEmballage,
                'conditionnement' => $ligne['conditionnement'] ?? $article?->type_emballage_primaire ?? $article?->type_emballage,
                'reference_emballage' => $ligne['reference_emballage'] ?? $article?->type_palette ?? $article?->emballage_ref,
                'quantite' => $qtyFacture,
                'cartons' => $cartons > 0 ? $cartons : $nbColis,
                'palettes' => $palettes,
                'poids_brut_unitaire' => $brutUn,
                'poids_brut' => $poidsBrut,
                'poids_net_unitaire' => $netUn,
                'poids_net' => $poidsNet,
                'poids_net_egoutte_unitaire' => $egUn,
                'poids_egoutte' => $poidsEgoutte,
                'prix_unitaire' => $prix,
                'devise' => $ligne['devise'] ?? $export->devise ?? $article?->devise,
                'montant' => round($qtyFacture * $prix, 2),
                'origine' => $ligne['origine'] ?? $article?->origine,
            ]);
        }
    }

    private function syncEmballageSuivi(Exportation $export, array $dossierData): void
    {
        $export->loadMissing('lignes.article');

        $needs = $export->needsSuiviEmballage() || (bool) $export->emballages_temporaires;
        if (!$needs) {
            return;
        }

        $qty = (float) $export->lignes
            ->filter(fn ($l) => (bool) ($l->article?->sous_reserve_retour)
                || in_array($export->type_emballage_doc, ['sous_reserve', 'les_deux'], true))
            ->sum(fn ($l) => $l->total_emballage ?: $l->nb_colis ?: $l->cartons);

        if ($qty <= 0) {
            $qty = (float) $export->total_emballage;
        }

        $payload = array_merge([
            'client_id' => $export->client_id,
            'conteneur' => $export->conteneur,
            'destination' => $export->destination,
            'date_export' => $export->etd ?? $export->date_creation,
            'facture_concernee' => $export->numero_facture ?: $export->numero,
            'quantite_exportee' => $qty,
            'type_emballage' => $dossierData['type_emballage']
                ?? $export->lignes->first()?->reference_emballage
                ?? $export->lignes->first()?->conditionnement,
        ], $dossierData);

        if (empty($payload['quantite_exportee'])) {
            $payload['quantite_exportee'] = $qty;
        }

        $export->update(['emballages_temporaires' => true]);
        $this->ensureDossierEmballage($export, $payload);
    }

    private function ensureDossierEmballage(Exportation $export, array $data): DossierEmballage
    {
        $payload = array_merge([
            'client_id' => $export->client_id,
            'conteneur' => $export->conteneur,
            'destination' => $export->destination,
            'date_export' => $export->etd ?? $export->date_creation,
            'facture_concernee' => $data['facture_concernee'] ?? $export->numero_facture ?? $export->numero,
        ], $data);

        if (!empty($payload['date_dum']) && empty($payload['date_limite_reimportation'])) {
            $payload['date_limite_reimportation'] = date('Y-m-d', strtotime($payload['date_dum'] . ' +365 days'));
        }

        $dossier = $export->dossierEmballage;
        if ($dossier) {
            $dossier->update($payload);
            $dossier->recalculerStatut();

            return $dossier->fresh();
        }

        $dossier = $export->dossierEmballage()->create($payload);
        $dossier->recalculerStatut();

        return $dossier;
    }

    private function metaFromCommande(int $commandeId): array
    {
        $commande = Commande::find($commandeId);
        if (!$commande) {
            return [];
        }

        return [
            'commande_id' => $commande->id,
            'numero_commande' => $commande->numero,
            'reference_commande_client' => $commande->reference_client,
            'date_commande' => optional($commande->date_commande)->format('Y-m-d'),
            'incoterm' => $commande->incoterm,
            'devise' => $commande->devise,
            'destination' => $commande->destination,
            'adresse_livraison' => $commande->adresse,
            'commercial' => $commande->commercial,
            'conditions_paiement' => $commande->mode_paiement,
        ];
    }

    private function logistiqueFromCommande(Commande $commande): array
    {
        /** @var Livraison|null $liv */
        $liv = $commande->livraisons()->orderByDesc('id')->first();
        if (!$liv) {
            return [];
        }

        return array_filter([
            'booking' => $liv->numero_booking ?: $liv->numero_reservation,
            'numero_swb_bl' => $liv->numero_bl_swb,
            'compagnie_maritime' => $liv->compagnie_maritime,
            'navire' => $liv->navire,
            'port_chargement' => $liv->port_depart,
            'port_destination' => $liv->port_arrivee,
            'etd' => optional($liv->etd)->format('Y-m-d'),
            'eta' => optional($liv->eta)->format('Y-m-d'),
            'conteneur' => $liv->numero_conteneur,
            'plomb_scelle' => $liv->numero_plomb,
            'transporteur' => $liv->transporteur,
            'chauffeur' => $liv->chauffeur,
            'chauffeur_cin' => $liv->cin_chauffeur,
            'immatriculation' => $liv->matricule_camion,
            'mode_transport' => $commande->type === 'export' ? 'maritime' : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function lignesFromCommande(int $commandeId): array
    {
        $commande = Commande::with('lignes.article')->find($commandeId);
        if (!$commande) {
            return [];
        }

        $out = [];
        foreach ($commande->lignes as $l) {
            $article = $l->article;
            $unites = (int) ($l->unites_par_colis ?? $article?->unites_par_colis ?? 0);
            $nbColis = $unites > 0 ? (int) ceil((float) $l->quantite / $unites) : (int) ceil((float) $l->quantite);

            $out[] = [
                'article_id' => $l->article_id,
                'code_article' => $article?->code_article,
                'designation' => $l->designation ?: $article?->designation,
                'hs_code' => $article?->hs_code,
                'calibre' => $l->calibre ?: $article?->calibre,
                'numero_lot' => $l->lot,
                'date_production' => optional($l->date_production)->format('Y-m-d'),
                'nb_colis' => max(1, $nbColis),
                'nombre_par_colis' => $unites ?: null,
                'total_emballage' => max(1, $nbColis),
                'conditionnement' => $l->type_emballage_primaire ?? $article?->type_emballage_primaire,
                'reference_emballage' => $l->reference_emballage ?? $article?->type_palette,
                'quantite' => $l->quantite,
                'cartons' => max(1, $nbColis),
                'palettes' => $l->colis_par_palette && $nbColis
                    ? (int) ceil($nbColis / max(1, (int) $l->colis_par_palette))
                    : 0,
                'prix_unitaire' => $l->prix_unitaire,
                'poids_net_egoutte_unitaire' => $l->poids_net_egoutte ?? $article?->poids_net_egoutte_unitaire,
                'poids_net_unitaire' => $article?->poids_net_unitaire ?? $article?->poids_net,
                'poids_brut_unitaire' => $article?->poids_brut_unitaire ?? $article?->poids_brut,
                'origine' => $article?->origine,
            ];
        }

        return $out;
    }
}
