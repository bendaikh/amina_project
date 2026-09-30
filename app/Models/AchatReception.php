<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AchatReception extends Model
{
    protected $fillable = [
        'achat_id', 'numero', 'date_reception', 'statut',
        'observations', 'entree_stock_generee', 'created_by',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'entree_stock_generee' => 'boolean',
    ];

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function factures(): BelongsToMany
    {
        return $this->belongsToMany(
            FactureFournisseur::class,
            'facture_reception',
            'achat_reception_id',
            'facture_fournisseur_id'
        )->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bonReception()
    {
        return $this->hasOne(BonReception::class, 'achat_reception_id');
    }

    public static function nextNumero(): string
    {
        // Keep sequence aligned with bons_reception
        return BonReception::nextNumero();
    }
}
