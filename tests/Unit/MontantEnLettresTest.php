<?php

namespace Tests\Unit;

use App\Support\MontantEnLettres;
use PHPUnit\Framework\TestCase;

class MontantEnLettresTest extends TestCase
{
    public function test_french_amounts(): void
    {
        $this->assertSame('zéro', MontantEnLettres::nombre(0));
        $this->assertSame('vingt et un', MontantEnLettres::nombre(21));
        $this->assertSame('soixante-dix', MontantEnLettres::nombre(70));
        $this->assertSame('soixante et onze', MontantEnLettres::nombre(71));
        $this->assertSame('quatre-vingts', MontantEnLettres::nombre(80));
        $this->assertSame('quatre-vingt-un', MontantEnLettres::nombre(81));
        $this->assertSame('quatre-vingt-onze', MontantEnLettres::nombre(91));
        $this->assertSame('cent', MontantEnLettres::nombre(100));
        $this->assertSame('deux cents', MontantEnLettres::nombre(200));
        $this->assertSame('deux cent un', MontantEnLettres::nombre(201));
        $this->assertSame('mille', MontantEnLettres::nombre(1000));
        $this->assertSame('mille deux cent cinquante', MontantEnLettres::nombre(1250));
        $this->assertSame(
            'Mille deux cents dirhams et cinquante centimes',
            MontantEnLettres::format(1200.50, 'MAD')
        );
    }
}
