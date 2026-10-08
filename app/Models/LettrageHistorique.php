<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LettrageHistorique extends Model
{
    protected $table = 'lettrage_historiques';

    protected $fillable = [
        'sens', 'client_id', 'fournisseur_id', 'action', 'reglement_id',
        'facture_type', 'facture_id', 'facture_numero', 'reglement_numero',
        'montant_facture', 'montant_reglement', 'montant_lettre', 'reste',
        'ecart', 'commentaire', 'created_by',
    ];

    protected $casts = [
        'montant_facture' => 'decimal:2',
        'montant_reglement' => 'decimal:2',
        'montant_lettre' => 'decimal:2',
        'reste' => 'decimal:2',
        'ecart' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(Reglement::class);
    }
}
