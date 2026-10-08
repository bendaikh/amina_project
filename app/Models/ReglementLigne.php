<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementLigne extends Model
{
    protected $table = 'reglement_lignes';

    public const LOCALE = 'locale';
    public const EXPORT = 'export';
    public const FOURNISSEUR = 'fournisseur';

    protected $fillable = [
        'reglement_id', 'facture_type', 'facture_id', 'montant',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(Reglement::class);
    }
}
