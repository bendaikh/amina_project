<?php

namespace Tests\Unit;

use App\Services\DocumentGenerationService;
use PHPUnit\Framework\TestCase;

class BlInstructionsGroupingTest extends TestCase
{
    public function test_groups_same_hs_by_packaging_and_sums_quantities(): void
    {
        $lignes = [
            (object) [
                'hs_code' => '2005700081',
                'conditionnement' => 'Fûts',
                'designation' => 'Olives vertes',
                'total_emballage' => 40,
                'poids_brut' => 10400,
            ],
            (object) [
                'hs_code' => '2005700081',
                'conditionnement' => 'Seaux',
                'designation' => 'Olives vertes',
                'total_emballage' => 20,
                'poids_brut' => 600,
            ],
            (object) [
                'hs_code' => '2005700081',
                'conditionnement' => 'Fûts',
                'designation' => 'Olives vertes',
                'total_emballage' => 10,
                'poids_brut' => 2600,
            ],
            (object) [
                'hs_code' => '2005700083',
                'conditionnement' => 'Fûts',
                'designation' => 'Olives noires',
                'total_emballage' => 36,
                'poids_brut' => 7560,
            ],
        ];

        $result = DocumentGenerationService::groupMarchandisesBl($lignes);

        $this->assertSame('2005700081', $result['groupes'][0]['hs_code']);
        $this->assertSame('50 FÛTS OLIVES VERTES', $result['groupes'][0]['lignes'][0]['libelle']);
        $this->assertSame('13 000', $result['groupes'][0]['lignes'][0]['poids']);
        $this->assertSame('20 SEAUX OLIVES VERTES', $result['groupes'][0]['lignes'][1]['libelle']);
        $this->assertSame('600', $result['groupes'][0]['lignes'][1]['poids']);
        $this->assertSame('2005700083', $result['groupes'][1]['hs_code']);
        $this->assertSame(106, $result['total_colis']);
        $this->assertSame('21 160', $result['poids_brut_label']);
    }
}
