<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteCreditLigne extends Model
{
    protected $table = 'note_credit_lignes';

    protected $fillable = [
        'note_credit_id', 'article_id', 'designation', 'quantite', 'unite',
        'prix', 'remise', 'tva_taux', 'montant_ht', 'montant_tva', 'montant_ttc',
    ];

    protected $casts = [
        'quantite' => 'decimal:3',
        'prix' => 'decimal:4',
        'remise' => 'decimal:2',
        'tva_taux' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
    ];

    public function noteCredit(): BelongsTo
    {
        return $this->belongsTo(NoteCredit::class);
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

        $ht = round($qte * $prix * (1 - $remise / 100), 2);
        $montantTva = round($ht * ($tva / 100), 2);
        $ttc = round($ht + $montantTva, 2);

        return [
            'montant_ht' => $ht,
            'montant_tva' => $montantTva,
            'montant_ttc' => $ttc,
        ];
    }
}
