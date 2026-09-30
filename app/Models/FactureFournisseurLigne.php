<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureFournisseurLigne extends Model
{
    protected $fillable = [
        'facture_fournisseur_id', 'article_id', 'designation', 'quantite', 'unite',
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

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_fournisseur_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
