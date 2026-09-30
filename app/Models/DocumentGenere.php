<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentGenere extends Model
{
    protected $table = 'documents_generes';

    protected $fillable = [
        'exportation_id', 'dossier_emballage_id', 'type', 'titre', 'version',
        'chemin', 'categorie', 'generated_by', 'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'version' => 'integer',
    ];

    public function exportation(): BelongsTo
    {
        return $this->belongsTo(Exportation::class);
    }

    public function dossierEmballage(): BelongsTo
    {
        return $this->belongsTo(DossierEmballage::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
