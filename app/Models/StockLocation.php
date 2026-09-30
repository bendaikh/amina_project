<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    protected $table = 'stock_locations';

    protected $fillable = ['code', 'nom', 'type', 'actif'];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'stock_location_id');
    }
}
