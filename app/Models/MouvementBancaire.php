<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MouvementBancaire extends Model
{
    protected $table = 'mouvements_bancaires';

    public const TYPES = [
        'virement_recu' => 'Virement reçu',
        'virement_emis' => 'Virement émis',
        'cheque' => 'Chèque',
        'carte' => 'Carte bancaire',
        'prelevement' => 'Prélèvement',
        'frais' => 'Frais bancaires',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'compte_bancaire_id', 'date_operation', 'description', 'reference',
        'debit', 'credit', 'type', 'source',
    ];

    protected $casts = [
        'date_operation' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    protected $appends = ['type_label'];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class, 'compte_bancaire_id');
    }

    public function rapprochements(): HasMany
    {
        return $this->hasMany(RapprochementLigne::class, 'mouvement_bancaire_id');
    }
}
