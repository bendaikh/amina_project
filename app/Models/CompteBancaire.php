<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompteBancaire extends Model
{
    protected $table = 'comptes_bancaires';

    protected $fillable = [
        'nom_banque', 'nom_compte', 'rib', 'iban', 'devise',
        'solde_ouverture', 'actif', 'notes',
    ];

    protected $casts = [
        'solde_ouverture' => 'decimal:2',
        'actif' => 'boolean',
    ];

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementBancaire::class, 'compte_bancaire_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(Reglement::class, 'compte_bancaire_id');
    }
}
