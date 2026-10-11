<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Commande;
use App\Models\DocumentGenere;
use App\Models\Exportateur;
use App\Models\Exportation;
use App\Models\Livraison;
use App\Models\LivraisonConteneur;
use Illuminate\Support\Facades\Storage;

class DocumentGenerationService
{
    public const TYPES = [
        'facture_commerciale' => ['titre' => 'Facture commerciale', 'categorie' => '01_Facture', 'needs_srr' => false],
        'packing_list' => ['titre' => 'Packing List', 'categorie' => '02_Packing_List', 'needs_srr' => false],
        'facture_srr' => ['titre' => 'Facture sous réserve de retour', 'categorie' => '01_Facture', 'needs_srr' => true],
        'fiche_production' => ['titre' => 'Fiche production', 'categorie' => '05_Interne', 'needs_srr' => false],
        'fiche_preparation' => ['titre' => 'Fiche préparation', 'categorie' => '05_Interne', 'needs_srr' => false],
        'fiche_chargement' => ['titre' => 'Fiche contrôle chargement', 'categorie' => '03_Logistique', 'needs_srr' => false],
        'fiche_chauffeur' => ['titre' => 'Fiche chauffeur', 'categorie' => '03_Logistique', 'needs_srr' => false],
        'solas_vgm' => ['titre' => 'SOLAS / VGM', 'categorie' => '03_Logistique', 'needs_srr' => false, 'needs_vgm' => true],
        'instructions_bl' => ['titre' => 'Instructions BL', 'categorie' => '03_Logistique', 'needs_srr' => false],
        'attestation_conditionnement' => ['titre' => 'Attestation de conditionnement', 'categorie' => '05_Interne', 'needs_srr' => false],
        'fiche_booking' => ['titre' => 'Fiche booking', 'categorie' => '03_Logistique', 'needs_srr' => false],
        'situation_dum52' => ['titre' => 'Situation DUM 52', 'categorie' => '04_Douane', 'needs_srr' => true],
        'etat_retour' => ['titre' => 'État de retour des fûts', 'categorie' => '04_Douane', 'needs_srr' => true],
    ];

    public function generate(Exportation $export, string $type, ?int $userId = null, array $context = []): DocumentGenere
    {
        $meta = self::TYPES[$type] ?? null;
        if (!$meta) {
            throw new \InvalidArgumentException("Type de document inconnu: {$type}");
        }

        if (!empty($meta['needs_srr']) && !$export->emballages_temporaires) {
            throw new \RuntimeException('Document disponible uniquement si emballages temporaires = Oui.');
        }

        $export->loadMissing([
            'client',
            'exportateur',
            'lignes',
            'dossierEmballage.retours',
            'commande.livraisons.conteneurs',
        ]);

        $sheet = $context['sheet'] ?? null;
        if (in_array($type, ['solas_vgm', 'fiche_chauffeur', 'attestation_conditionnement'], true) && $sheet === null) {
            $sheet = $export->commande
                ? ($this->sheetsForCommande($export, $export->commande)[0] ?? $this->sheetFromExport($export))
                : $this->sheetFromExport($export);
        }

        if (!empty($meta['needs_vgm']) && !$export->vgm_valide && $sheet === null) {
            throw new \RuntimeException('SOLAS/VGM nécessite la validation du poids vérifié.');
        }

        $reference = $context['reference'] ?? ($sheet['reference'] ?? null);
        $titre = $meta['titre'];
        if (!empty($sheet['numero_conteneur']) && in_array($type, ['solas_vgm', 'fiche_chauffeur', 'attestation_conditionnement'], true)) {
            $titre .= ' — ' . $sheet['numero_conteneur'];
        }

        $versionQuery = DocumentGenere::where('exportation_id', $export->id)->where('type', $type);
        if ($reference) {
            $versionQuery->where('reference', $reference);
        }
        $lastVersion = $versionQuery->max('version') ?? 0;

        $viewData = [
            'export' => $export,
            'type' => $type,
            'meta' => array_merge($meta, ['titre' => $titre]),
            'dossier' => $export->dossierEmballage,
            'sheet' => $sheet,
        ];
        $viewName = 'documents.export';
        if ($type === 'instructions_bl') {
            $viewName = 'documents.instructions_bl';
            $viewData['bl'] = $this->instructionsBl($export);
        }
        if ($type === 'attestation_conditionnement') {
            $viewData['attestation'] = $this->attestationData($export, $sheet);
        }

        $html = view($viewName, $viewData)->render();

        $dir = "documents/{$export->numero}/{$meta['categorie']}";
        $filename = sprintf('%s_v%d_%s.html', $type, $lastVersion + 1, now()->format('YmdHis'));
        $path = "{$dir}/{$filename}";
        Storage::disk('local')->put($path, $html);

        $doc = DocumentGenere::create([
            'exportation_id' => $export->id,
            'dossier_emballage_id' => $export->dossierEmballage?->id,
            'type' => $type,
            'reference' => $reference,
            'titre' => $titre,
            'version' => $lastVersion + 1,
            'chemin' => $path,
            'categorie' => $meta['categorie'],
            'generated_by' => $userId,
            'generated_at' => now(),
        ]);

        AuditLog::record($export, 'generate_document', 'document', null, $type, $userId);

        return $doc;
    }

    public function generatePack(Exportation $export, ?int $userId = null): array
    {
        $created = [];
        foreach (self::TYPES as $type => $meta) {
            if (!empty($meta['needs_srr']) && !$export->emballages_temporaires) {
                continue;
            }
            if (!empty($meta['needs_vgm']) && !$export->vgm_valide) {
                continue;
            }
            try {
                $created[] = $this->generate($export, $type, $userId);
            } catch (\Throwable $e) {
                // skip unavailable docs in pack
            }
        }
        return $created;
    }

    /**
     * Une fiche par conteneur. Le poids marchandise vient du conteneur,
     * ou de la liste de colisage lorsqu'il n'y a qu'un seul conteneur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sheetsForCommande(Exportation $export, Commande $commande): array
    {
        $commande->loadMissing(['livraisons.conteneurs', 'client', 'lignes.article']);
        $export->loadMissing(['lignes', 'exportateur', 'client']);

        $pairs = [];
        foreach ($commande->livraisons->sortBy('id') as $liv) {
            if ($liv->conteneurs->isNotEmpty()) {
                foreach ($liv->conteneurs as $conteneur) {
                    $pairs[] = [$liv, $conteneur];
                }
                continue;
            }
            if (filled($liv->numero_conteneur) || filled($liv->chauffeur) || filled($liv->transporteur)) {
                $pairs[] = [$liv, null];
            }
        }

        if (!$pairs) {
            return [$this->sheetFromExport($export)];
        }

        $cargoTotal = $this->cargoWeight($export, $commande);
        $count = count($pairs);
        $sheets = [];
        foreach ($pairs as [$liv, $conteneur]) {
            $sheets[] = $this->makeSheet($export, $commande, $liv, $conteneur, $count, $cargoTotal);
        }

        return $sheets;
    }

    public function sheetFromExport(Exportation $export): array
    {
        $export->loadMissing(['lignes', 'exportateur', 'client']);
        $cargo = (float) $export->lignes->sum('poids_brut');
        $type = $this->normalizeContainerType($export->type_conteneur);

        return $this->blankSheet([
            'reference' => 'export-' . $export->id,
            'numero_conteneur' => $export->conteneur,
            'type_conteneur' => $type,
            'plomb' => $export->plomb_scelle,
            'booking' => $export->booking,
            'compagnie' => $export->compagnie_maritime,
            'navire_voyage' => $this->joinNavire($export->navire, $export->voyage),
            'terminal' => $export->port_chargement,
            'poids_marchandise' => $cargo > 0 ? $cargo : null,
            'tare' => null,
            'vgm' => $export->vgm_poids ? (float) $export->vgm_poids : null,
            'methode' => '2',
            'date' => now(),
            'societe' => $this->societe($export),
            'facture' => $export->numero_facture,
            'transporteur' => $export->transporteur,
            'chauffeur' => $export->chauffeur,
            'cin' => $export->chauffeur_cin,
            'immatriculation' => $export->immatriculation,
            'poids_declare' => $export->vgm_poids ? (float) $export->vgm_poids : ($cargo > 0 ? $cargo : null),
            'lieu_chargement' => $export->exp_adresse ?: $export->exportateur?->adresse,
            'destination' => $export->destination ?: $export->port_destination ?: $export->pays,
            'observations' => $export->observations,
        ]);
    }

    private function makeSheet(
        Exportation $export,
        Commande $commande,
        Livraison $liv,
        ?LivraisonConteneur $conteneur,
        int $containerCount,
        ?float $cargoTotal
    ): array {
        $type = $this->normalizeContainerType($conteneur?->type_conteneur ?: $export->type_conteneur);
        $cargo = $this->toFloat($conteneur?->poids_brut_marchandise);
        if ($cargo === null && $containerCount === 1 && $cargoTotal !== null && $cargoTotal > 0) {
            $cargo = $cargoTotal;
        }

        $tare = $this->toFloat($conteneur?->tare_conteneur ?: $liv->tare_conteneur);
        $methode = in_array((string) $conteneur?->vgm_methode, ['1', '2'], true) ? (string) $conteneur->vgm_methode : '2';
        $vgmSaisi = $this->toFloat($conteneur?->vgm_poids);
        $vgm = null;
        if ($methode === '1' && $vgmSaisi !== null) {
            $vgm = $vgmSaisi;
        } elseif ($cargo !== null && $tare !== null) {
            $vgm = round($cargo + $tare, 3);
            $methode = $conteneur?->vgm_methode === '1' ? '1' : '2';
        } elseif ($vgmSaisi !== null) {
            $vgm = $vgmSaisi;
        }

        $plomb = $conteneur?->nouveau_plomb ?: $conteneur?->numero_plomb ?: $liv->nouveau_plomb ?: $liv->numero_plomb ?: $export->plomb_scelle;
        $arrivee = $conteneur?->arrivee_usine ?: $liv->arrivee_usine ?: $conteneur?->created_at ?: $liv->created_at;
        $depart = $conteneur?->depart_usine ?: $liv->depart_usine;
        $declare = $vgm ?? $cargo;

        return $this->blankSheet([
            'reference' => $conteneur ? 'c-' . $conteneur->id : 'l-' . $liv->id,
            'conteneur_id' => $conteneur?->id,
            'numero_conteneur' => $conteneur?->numero_conteneur ?: $liv->numero_conteneur ?: $export->conteneur,
            'type_conteneur' => $type,
            'plomb' => $plomb,
            'booking' => $liv->numero_booking ?: $liv->numero_reservation ?: $export->booking,
            'compagnie' => $liv->compagnie_maritime ?: $export->compagnie_maritime,
            'navire_voyage' => $this->joinNavire($liv->navire ?: $export->navire, $liv->voyage ?: $export->voyage),
            'terminal' => $liv->terminal ?: $liv->port_depart ?: $export->port_chargement,
            'poids_marchandise' => $cargo,
            'tare' => $tare,
            'vgm' => $vgm,
            'methode' => $methode,
            'date' => $liv->date_chargement ?: now(),
            'societe' => $this->societe($export),
            'facture' => $export->numero_facture,
            'transporteur' => $conteneur?->transporteur ?: $liv->transporteur ?: $export->transporteur,
            'chauffeur' => $conteneur?->chauffeur ?: $liv->chauffeur ?: $export->chauffeur,
            'telephone' => $conteneur?->telephone_chauffeur ?: $liv->telephone_chauffeur,
            'cin' => $conteneur?->cin_chauffeur ?: $liv->cin_chauffeur ?: $export->chauffeur_cin,
            'immatriculation' => $conteneur?->matricule_camion ?: $liv->matricule_camion ?: $export->immatriculation,
            'poids_declare' => $declare,
            'lieu_chargement' => $conteneur?->lieu_chargement ?: $liv->lieu_chargement ?: $export->exp_adresse ?: $export->exportateur?->adresse,
            'destination' => $export->destination ?: $liv->port_arrivee ?: $export->port_destination ?: $commande->destination ?: $export->pays,
            'arrivee' => $arrivee,
            'depart' => $depart,
            'ctrl_conteneur' => (bool) ($conteneur?->ctrl_conteneur),
            'ctrl_plomb' => (bool) ($conteneur?->ctrl_plomb),
            'ctrl_documents' => (bool) ($conteneur?->ctrl_documents),
            'observations' => $conteneur?->observations_controle ?: $liv->observations,
        ]);
    }

    private function blankSheet(array $overrides): array
    {
        return array_merge([
            'reference' => null,
            'conteneur_id' => null,
            'numero_conteneur' => null,
            'type_conteneur' => null,
            'plomb' => null,
            'booking' => null,
            'compagnie' => null,
            'navire_voyage' => null,
            'terminal' => null,
            'poids_marchandise' => null,
            'tare' => null,
            'vgm' => null,
            'methode' => '2',
            'date' => null,
            'societe' => null,
            'facture' => null,
            'transporteur' => null,
            'chauffeur' => null,
            'telephone' => null,
            'cin' => null,
            'immatriculation' => null,
            'poids_declare' => null,
            'lieu_chargement' => null,
            'destination' => null,
            'arrivee' => null,
            'depart' => null,
            'ctrl_conteneur' => false,
            'ctrl_plomb' => false,
            'ctrl_documents' => false,
            'observations' => null,
        ], $overrides);
    }

    private function cargoWeight(Exportation $export, Commande $commande): ?float
    {
        $fromLines = (float) $export->lignes->sum('poids_brut');
        if ($fromLines > 0) {
            return round($fromLines, 3);
        }

        $fromOrder = 0.0;
        foreach ($commande->lignes as $ligne) {
            $unit = (float) ($ligne->article?->poids_brut ?? 0);
            $fromOrder += $unit * (float) $ligne->quantite;
        }

        return $fromOrder > 0 ? round($fromOrder, 3) : null;
    }

    private function societe(Exportation $export): ?string
    {
        return $export->exp_nom_societe
            ?: $export->exp_nom
            ?: $export->exportateur?->nom_societe
            ?: $export->exportateur?->nom
            ?: config('app.name');
    }

    private function joinNavire(?string $navire, ?string $voyage): ?string
    {
        $parts = array_values(array_filter([$navire, $voyage], fn ($v) => filled($v)));

        return $parts ? implode(' / ', $parts) : null;
    }

    private function normalizeContainerType(?string $value): ?string
    {
        if (!filled($value)) {
            return null;
        }
        if (preg_match('/45/', $value)) {
            return '45';
        }
        if (preg_match('/20/', $value)) {
            return '20';
        }
        if (preg_match('/40/', $value)) {
            return '40';
        }

        return null;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace([' ', ','], ['', '.'], $value);
        }
        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 3);
    }

    public function attestationData(Exportation $export, ?array $sheet = null): array
    {
        $export->loadMissing(['lignes', 'exportateur', 'client', 'commande.lignes.article']);
        $sheet = $sheet ?: $this->sheetFromExport($export);
        $societe = Exportateur::defaut() ?: $export->exportateur;
        $exportateur = $export->exportateur ?: $societe;

        $rows = [];
        foreach ($export->lignes as $ligne) {
            $rows[] = $this->attestationRow(
                $ligne->designation,
                $ligne->calibre,
                $ligne->numero_lot,
                $ligne->date_production,
                $ligne->conditionnement,
                $ligne->total_emballage ?: ($ligne->nb_colis ?: $ligne->cartons),
                $ligne->poids_net_unitaire,
                $ligne->poids_net,
                $ligne->quantite
            );
        }

        if (!$rows && $export->commande) {
            foreach ($export->commande->lignes as $ligne) {
                $article = $ligne->article;
                $unites = (int) ($ligne->unites_par_colis ?? $article?->unites_par_colis ?? 0);
                $nb = $unites > 0 ? (int) ceil((float) $ligne->quantite / $unites) : (int) ceil((float) $ligne->quantite);
                $unitNet = $article?->poids_net_unitaire ?? $article?->poids_net;
                $rows[] = $this->attestationRow(
                    $ligne->designation ?: $article?->designation,
                    $ligne->calibre ?: $article?->calibre,
                    $ligne->lot,
                    $ligne->date_production,
                    $ligne->type_emballage_primaire ?: $article?->type_emballage_primaire,
                    $nb,
                    $unitNet,
                    $unitNet !== null ? round((float) $unitNet * (float) $ligne->quantite, 3) : null,
                    $ligne->quantite
                );
            }
        }

        $names = collect($rows)->pluck('produit')->filter()->unique()->values();
        $marchandise = $names->isEmpty()
            ? 'la marchandise décrite ci-dessous'
            : $names->take(4)->implode(', ');

        $type = $sheet['type_conteneur'] ?? null;
        $typeLabel = $type ? $type . "'" : null;

        return [
            'numero' => $export->numero,
            'date' => $sheet['date'] ?? now(),
            'marchandise' => $marchandise,
            'fabricant' => $this->partyBlock($societe, 'fabricant de conserves alimentaires'),
            'exportateur' => $this->partyBlock($exportateur, 'exportateur'),
            'lignes' => $rows,
            'total_emb' => array_sum(array_column($rows, 'emb')),
            'total_poids' => round(array_sum(array_column($rows, 'poids_total')), 3),
            'conteneur' => $sheet['numero_conteneur'] ?? null,
            'type_conteneur' => $typeLabel,
            'plomb' => $sheet['plomb'] ?? null,
            'lieu' => $societe?->ville ?: null,
            'pied' => $this->partyBlock($societe, null),
        ];
    }

    private function attestationRow($produit, $calibre, $lot, $date, $conditionnement, $emb, $poidsUnit, $poidsTotal, $quantite): array
    {
        $unit = $this->toFloat($poidsUnit);
        $total = $this->toFloat($poidsTotal);
        $qty = $this->toFloat($quantite);
        if (($total === null || $total <= 0) && $unit !== null && $qty !== null) {
            $total = round($unit * $qty, 3);
        }

        return [
            'produit' => $produit,
            'calibre' => $calibre,
            'lot' => $lot,
            'date' => $date,
            'conditionnement' => $conditionnement,
            'emb' => (int) $emb,
            'poids_unit' => $unit,
            'poids_total' => $total,
        ];
    }

    private function partyBlock(?Exportateur $party, ?string $qualite): array
    {
        if (!$party) {
            return [
                'societe' => null,
                'adresse' => null,
                'eacce' => null,
                'qualite' => $qualite,
                'forme' => null,
                'capital' => null,
                'ville' => null,
                'pays' => null,
                'ice' => null,
                'if' => null,
                'rc' => null,
                'patente' => null,
                'telephone' => null,
                'email' => null,
            ];
        }

        return [
            'societe' => $party->nom_societe ?: $party->nom,
            'adresse' => $party->adresse,
            'eacce' => $party->agrement_eacce,
            'qualite' => $qualite,
            'forme' => $party->forme_juridique,
            'capital' => $party->capital,
            'ville' => $party->ville,
            'pays' => $party->pays,
            'ice' => $party->ice,
            'if' => $party->identifiant_fiscal,
            'rc' => $party->rc,
            'patente' => $party->patente,
            'telephone' => $party->telephone,
            'email' => $party->email,
        ];
    }

    public static function nextExportNumber(): string
    {
        $year = now()->format('Y');
        $last = Exportation::where('numero', 'like', "EXP-{$year}-%")
            ->orderByDesc('id')
            ->value('numero');

        $seq = 1;
        if ($last && preg_match('/EXP-\d{4}-(\d+)/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('EXP-%s-%04d', $year, $seq);
    }

    public static function nextInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $last = Exportation::where('numero_facture', 'like', "FAC-{$year}-%")
            ->orderByDesc('id')
            ->value('numero_facture');

        $seq = 1;
        if ($last && preg_match('/FAC-\d{4}-(\d+)/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('FAC-%s-%04d', $year, $seq);
    }

    /**
     * Données du formulaire « Instructions pour établissement du connaissement ».
     * Les marchandises sont regroupées par code HS, puis par conditionnement.
     */
    public function instructionsBl(Exportation $export): array
    {
        $export->loadMissing(['client', 'exportateur', 'lignes', 'commande.livraisons.conteneurs']);

        $shipperName = $export->exp_nom_societe
            ?: $export->exp_nom
            ?: $export->exportateur?->nom_societe
            ?: $export->exportateur?->nom
            ?: '';
        $shipperAdresse = $export->exp_adresse ?: $export->exportateur?->adresse ?: '';

        $consigneeName = $export->dest_societe
            ?: $export->dest_nom
            ?: $export->client?->nomination
            ?: $export->client?->nom
            ?: '';
        $consigneeAdresse = $export->dest_adresse
            ?: ($export->client?->adresse_livraison ?: $export->client?->adresse)
            ?: '';
        $eori = $export->dest_eori ?: $export->client?->eori ?: '';

        [$navire, $voyage] = $this->splitNavireVoyage(
            (string) ($export->navire ?? ''),
            (string) ($export->voyage ?? '')
        );

        return [
            'shipper_name' => $shipperName,
            'shipper_lines' => $this->addressLines($shipperAdresse),
            'consignee_name' => $consigneeName,
            'consignee_lines' => $this->addressLines($consigneeAdresse),
            'eori' => $eori,
            'notify_lines' => $this->addressLines($export->transitaire ?: $export->client?->transitaire ?: ''),
            'navire' => $navire,
            'voyage' => $voyage,
            'booking' => $export->booking ?: '',
            'compagnie' => $export->compagnie_maritime ?: '',
            'port_chargement' => $export->port_chargement ?: '',
            'port_dechargement' => $export->port_destination ?: '',
            'conteneurs' => $this->conteneursForBl($export),
            'marchandises' => self::groupMarchandisesBl($export->lignes),
            'frais' => [
                "Frais d'embarquement",
                'Transport origine',
                'Fret maritime',
                'Frais de débarquement',
                'Transport destination',
            ],
        ];
    }

    /**
     * Regroupe les lignes par code HS, et à l'intérieur par conditionnement.
     * Un même HS avec deux emballages (fûts / seaux) reste en deux lignes.
     *
     * @param  iterable<int, object>  $lignes
     * @return array{groupes: array<int, array{hs_code: string, lignes: array<int, array{libelle: string, poids: string}>}>, total_colis: int, total_colis_label: string, poids_brut_label: string}
     */
    public static function groupMarchandisesBl(iterable $lignes): array
    {
        $buckets = [];
        $hsOrder = [];

        foreach ($lignes as $ligne) {
            $hs = trim((string) ($ligne->hs_code ?? ''));
            if ($hs === '') {
                $hs = '—';
            }

            $pack = self::packagingLabel($ligne);
            $designation = mb_strtoupper(trim((string) ($ligne->designation ?? '')));
            $qty = self::colisCount($ligne);
            $poids = (float) ($ligne->poids_brut ?? 0);
            $key = $hs . "\n" . $pack . "\n" . $designation;

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'hs' => $hs,
                    'pack' => $pack,
                    'designation' => $designation,
                    'qty' => 0,
                    'poids' => 0.0,
                ];
                $hsOrder['hs:' . $hs][] = $key;
            }

            $buckets[$key]['qty'] += $qty;
            $buckets[$key]['poids'] += $poids;
        }

        $groupes = [];
        $totalColis = 0;
        $totalPoids = 0.0;

        foreach ($hsOrder as $hsKey => $keys) {
            $hs = substr($hsKey, 3);
            $rows = [];
            foreach ($keys as $key) {
                $row = $buckets[$key];
                $libelle = self::formatMarchandiseLibelle($row['qty'], $row['pack'], $row['designation']);
                $rows[] = [
                    'libelle' => $libelle,
                    'poids' => self::formatKg($row['poids']),
                ];
                $totalColis += $row['qty'];
                $totalPoids += $row['poids'];
            }
            $groupes[] = [
                'hs_code' => $hs,
                'lignes' => $rows,
            ];
        }

        return [
            'groupes' => $groupes,
            'total_colis' => $totalColis,
            'total_colis_label' => self::formatNombre($totalColis),
            'poids_brut_label' => self::formatKg($totalPoids),
        ];
    }

    private function conteneursForBl(Exportation $export): array
    {
        $rows = [];
        $livraisons = $export->commande?->livraisons ?? collect();

        foreach ($livraisons as $livraison) {
            $conteneurs = $livraison->relationLoaded('conteneurs')
                ? $livraison->conteneurs
                : $livraison->conteneurs()->orderBy('ordre')->orderBy('id')->get();

            if ($conteneurs->isEmpty() && filled($livraison->numero_conteneur)) {
                $rows[] = [
                    'numero' => $livraison->numero_conteneur,
                    'tare' => $this->formatTare($livraison->tare_conteneur),
                    'plomb' => (string) ($livraison->numero_plomb ?? ''),
                ];
                continue;
            }

            foreach ($conteneurs as $conteneur) {
                $plomb = $conteneur->changement_plomb && filled($conteneur->nouveau_plomb)
                    ? $conteneur->nouveau_plomb
                    : $conteneur->numero_plomb;
                $rows[] = [
                    'numero' => (string) ($conteneur->numero_conteneur ?? ''),
                    'tare' => $this->formatTare($conteneur->tare_conteneur),
                    'plomb' => (string) ($plomb ?? ''),
                ];
            }
        }

        if ($rows === [] && filled($export->conteneur)) {
            $rows[] = [
                'numero' => (string) $export->conteneur,
                'tare' => '',
                'plomb' => (string) ($export->plomb_scelle ?? ''),
            ];
        }

        return $rows;
    }

    private function splitNavireVoyage(string $navire, string $voyage): array
    {
        $navire = trim($navire);
        $voyage = trim($voyage);
        if ($voyage === '' && str_contains($navire, '/')) {
            [$navire, $voyage] = array_map('trim', explode('/', $navire, 2));
        }

        return [$navire, $voyage];
    }

    private function addressLines(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($text === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($line) => $line !== ''));
    }

    private function formatTare(?string $tare): string
    {
        $tare = trim((string) $tare);
        if ($tare === '') {
            return '';
        }
        if (preg_match('/kg/i', $tare)) {
            return $tare;
        }
        if (preg_match('/^[\d\s.,]+$/', $tare)) {
            return $tare . ' KG';
        }

        return $tare;
    }

    private static function packagingLabel(object $ligne): string
    {
        $raw = trim((string) ($ligne->conditionnement ?? ''));
        if ($raw === '') {
            $raw = trim((string) ($ligne->reference_emballage ?? ''));
        }

        return $raw === '' ? 'COLIS' : mb_strtoupper($raw);
    }

    private static function colisCount(object $ligne): int
    {
        $total = (int) ($ligne->total_emballage ?? 0);
        if ($total > 0) {
            return $total;
        }
        $colis = (int) ($ligne->nb_colis ?? 0);
        if ($colis > 0) {
            return $colis;
        }

        return (int) ($ligne->cartons ?? 0);
    }

    private static function formatMarchandiseLibelle(int $qty, string $pack, string $designation): string
    {
        $qtyLabel = self::formatNombre($qty);
        $designationStartsWithPack = $designation !== ''
            && mb_stripos($designation, $pack) === 0;

        $parts = [$qtyLabel];
        if (!$designationStartsWithPack) {
            $parts[] = $pack;
        }
        if ($designation !== '') {
            $parts[] = $designation;
        }

        return implode(' ', $parts);
    }

    private static function formatKg(float $value): string
    {
        $rounded = round($value, 3);
        if (abs($rounded - round($rounded)) < 0.0005) {
            return self::formatNombre((int) round($rounded));
        }

        $formatted = number_format($rounded, 3, ',', ' ');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    private static function formatNombre(int $value): string
    {
        return number_format($value, 0, ',', ' ');
    }
}
