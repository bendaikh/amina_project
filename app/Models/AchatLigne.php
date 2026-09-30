<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchatLigne extends Model
{
    protected $fillable = [
        'achat_id', 'article_id', 'designation', 'quantite', 'unite', 'prix',
        'remise', 'tva_taux', 'montant_ht', 'montant_tva', 'montant_ttc',
        'lot', 'date_prevue',
    ];

    protected $casts = [
        'quantite' => 'decimal:3',
        'prix' => 'decimal:4',
        'remise' => 'decimal:2',
        'tva_taux' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'date_prevue' => 'date',
    ];

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public static function computeAmounts(array $ligne): array
    {
        $qte = (float) ($ligne['quantite'] ?? 0);
        $prix = (float) ($ligne['prix'] ?? 0);
        $remise = (float) ($ligne['remise'] ?? 0);
        $tva = (float) ($ligne['tva_taux'] ?? 20);

        $brut = $qte * $prix;
        $ht = round($brut * (1 - $remise / 100), 2);
        $montantTva = round($ht * ($tva / 100), 2);
        $ttc = round($ht + $montantTva, 2);

        return [
            'montant_ht' => $ht,
            'montant_tva' => $montantTva,
            'montant_ttc' => $ttc,
        ];
    }
}
