<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reglement extends Model
{
    protected $table = 'reglements';

    public const SENS_CLIENT = 'client';
    public const SENS_FOURNISSEUR = 'fournisseur';

    protected $fillable = [
        'numero', 'sens', 'client_id', 'fournisseur_id', 'compte_bancaire_id',
        'date_reglement', 'mode_paiement', 'montant', 'devise', 'reference',
        'notes', 'created_by',
    ];

    protected $casts = [
        'date_reglement' => 'date',
        'montant' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class, 'compte_bancaire_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(ReglementLigne::class);
    }

    public function rapprochements(): HasMany
    {
        return $this->hasMany(RapprochementLigne::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
