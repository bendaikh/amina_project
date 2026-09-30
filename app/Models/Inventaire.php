<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventaire extends Model
{
    public const STATUTS = ['brouillon', 'en_cours', 'valide', 'annule'];

    protected $fillable = [
        'numero', 'libelle', 'date_inventaire', 'statut',
        'stock_location_id', 'observations',
        'valide_at', 'created_by', 'valide_by',
    ];

    protected $casts = [
        'date_inventaire' => 'date',
        'valide_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(InventaireLigne::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "INV-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
