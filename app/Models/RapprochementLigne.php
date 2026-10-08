<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RapprochementLigne extends Model
{
    protected $table = 'rapprochement_lignes';

    protected $fillable = [
        'compte_bancaire_id', 'mouvement_bancaire_id', 'reglement_id',
        'montant', 'methode', 'created_by',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class, 'compte_bancaire_id');
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(MouvementBancaire::class, 'mouvement_bancaire_id');
    }

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(Reglement::class);
    }
}
