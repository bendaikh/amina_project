<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $fillable = [
        'article_id', 'stock_location_id', 'lot',
        'stock_initial', 'entrees', 'sorties',
        'stock_theorique', 'stock_reserve',
        'quarantaine', 'endommage', 'transit',
    ];

    protected $casts = [
        'stock_initial' => 'decimal:3',
        'entrees' => 'decimal:3',
        'sorties' => 'decimal:3',
        'stock_theorique' => 'decimal:3',
        'stock_reserve' => 'decimal:3',
        'quarantaine' => 'decimal:3',
        'endommage' => 'decimal:3',
        'transit' => 'decimal:3',
    ];

    protected $appends = ['stock_disponible'];

    public function getStockDisponibleAttribute(): float
    {
        return (float) $this->stock_theorique - (float) $this->stock_reserve;
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
