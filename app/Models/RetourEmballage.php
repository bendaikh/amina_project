<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetourEmballage extends Model
{
    protected $table = 'retours_emballages';

    protected $fillable = [
        'dossier_emballage_id', 'date_reimportation', 'dum_reimportation',
        'quantite', 'type_emballage', 'observations', 'justificatif_path', 'created_by',
    ];

    protected $casts = [
        'date_reimportation' => 'date',
        'quantite' => 'decimal:3',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierEmballage::class, 'dossier_emballage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
