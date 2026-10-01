<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivraisonConteneur extends Model
{
    protected $table = 'livraison_conteneurs';

    protected $fillable = [
        'livraison_id',
        'ordre',
        'numero_conteneur',
        'tare_conteneur',
        'numero_plomb',
        'changement_plomb',
        'raison_changement_plomb',
        'nouveau_plomb',
        'matricule_camion',
        'chauffeur',
        'transporteur',
        'cin_chauffeur',
        'cin_chauffeur_scan',
    ];

    protected $casts = [
        'changement_plomb' => 'boolean',
        'ordre' => 'integer',
    ];

    public function livraison(): BelongsTo
    {
        return $this->belongsTo(Livraison::class);
    }
}
