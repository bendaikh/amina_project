<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DocumentGenere;
use App\Models\Exportation;
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

    public function generate(Exportation $export, string $type, ?int $userId = null): DocumentGenere
    {
        $meta = self::TYPES[$type] ?? null;
        if (!$meta) {
            throw new \InvalidArgumentException("Type de document inconnu: {$type}");
        }

        if (!empty($meta['needs_srr']) && !$export->emballages_temporaires) {
            throw new \RuntimeException('Document disponible uniquement si emballages temporaires = Oui.');
        }

        if (!empty($meta['needs_vgm']) && !$export->vgm_valide) {
            throw new \RuntimeException('SOLAS/VGM nécessite la validation du poids vérifié.');
        }

        $export->loadMissing(['client', 'exportateur', 'lignes', 'dossierEmballage.retours']);

        $lastVersion = DocumentGenere::where('exportation_id', $export->id)
            ->where('type', $type)
            ->max('version') ?? 0;

        $html = view('documents.export', [
            'export' => $export,
            'type' => $type,
            'meta' => $meta,
            'dossier' => $export->dossierEmballage,
        ])->render();

        $dir = "documents/{$export->numero}/{$meta['categorie']}";
        $filename = sprintf('%s_v%d_%s.html', $type, $lastVersion + 1, now()->format('YmdHis'));
        $path = "{$dir}/{$filename}";
        Storage::disk('local')->put($path, $html);

        $doc = DocumentGenere::create([
            'exportation_id' => $export->id,
            'dossier_emballage_id' => $export->dossierEmballage?->id,
            'type' => $type,
            'titre' => $meta['titre'],
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
}
