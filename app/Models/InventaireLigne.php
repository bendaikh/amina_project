<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventaireLigne extends Model
{
    protected $fillable = [
        'inventaire_id', 'article_id', 'stock_location_id', 'lot',
        'quantite_theorique', 'quantite_physique', 'ecart', 'observations',
    ];

    protected $casts = [
        'quantite_theorique' => 'decimal:3',
        'quantite_physique' => 'decimal:3',
        'ecart' => 'decimal:3',
    ];

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
