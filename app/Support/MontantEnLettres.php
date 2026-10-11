<?php

namespace App\Support;

class MontantEnLettres
{
    public static function format(float $montant, string $devise = 'MAD'): string
    {
        $montant = round($montant, 2);
        $entier = (int) floor($montant);
        $centimes = (int) round(($montant - $entier) * 100);
        if ($centimes === 100) {
            $entier++;
            $centimes = 0;
        }

        $devise = strtoupper($devise);
        $unite = $devise === 'MAD' ? ($entier > 1 ? 'dirhams' : 'dirham') : $devise;
        $texte = self::nombre($entier) . ' ' . $unite;

        if ($centimes > 0) {
            $centimeLabel = $centimes > 1 ? 'centimes' : 'centime';
            $texte .= ' et ' . self::nombre($centimes) . ' ' . $centimeLabel;
        }

        return ucfirst($texte);
    }

    public static function nombre(int $n): string
    {
        if ($n < 0) {
            return 'moins ' . self::nombre(abs($n));
        }
        if ($n === 0) {
            return 'zéro';
        }
        if ($n < 17) {
            return ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize'][$n];
        }
        if ($n < 20) {
            return 'dix-' . self::nombre($n - 10);
        }
        if ($n < 100) {
            $dizaines = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante', 8 => 'quatre-vingt'];
            $d = intdiv($n, 10);
            $r = $n % 10;
            if ($d === 7 || $d === 9) {
                $base = $d === 7 ? 'soixante' : 'quatre-vingt';
                if ($r === 1) {
                    return $base . ($d === 7 ? ' et onze' : '-onze');
                }

                return $base . '-' . self::nombre(10 + $r);
            }
            $mot = $dizaines[$d];
            if ($r === 0) {
                return $d === 8 ? 'quatre-vingts' : $mot;
            }
            if ($r === 1 && $d !== 8) {
                return $mot . ' et un';
            }

            return $mot . '-' . self::nombre($r);
        }
        if ($n < 1000) {
            $c = intdiv($n, 100);
            $r = $n % 100;
            $cent = $c === 1 ? 'cent' : self::nombre($c) . ' cent';
            if ($r === 0) {
                return $c > 1 ? $cent . 's' : $cent;
            }

            return $cent . ' ' . self::nombre($r);
        }
        if ($n < 1000000) {
            $m = intdiv($n, 1000);
            $r = $n % 1000;
            $mille = $m === 1 ? 'mille' : self::nombre($m) . ' mille';

            return $r === 0 ? $mille : $mille . ' ' . self::nombre($r);
        }
        if ($n < 1000000000) {
            $m = intdiv($n, 1000000);
            $r = $n % 1000000;
            $million = $m === 1 ? 'un million' : self::nombre($m) . ' millions';

            return $r === 0 ? $million : $million . ' ' . self::nombre($r);
        }

        return (string) $n;
    }
}
