<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonReceptionLigne extends Model
{
    protected $table = 'bon_reception_lignes';

    public const CONTROLES = ['ok', 'quarantaine', 'refuse', 'endommage'];

    protected $fillable = [
        'bon_reception_id', 'article_id', 'designation',
        'quantite_commandee', 'quantite_recue', 'unite', 'lot',
        'date_production', 'date_peremption', 'stock_location_id',
        'controle_qualite', 'observations',
    ];

    protected $casts = [
        'quantite_commandee' => 'decimal:3',
        'quantite_recue' => 'decimal:3',
        'date_production' => 'date',
        'date_peremption' => 'date',
    ];

    public function bonReception(): BelongsTo
    {
        return $this->belongsTo(BonReception::class, 'bon_reception_id');
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
