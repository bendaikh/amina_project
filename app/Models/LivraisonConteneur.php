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
        'type_conteneur',
        'tare_conteneur',
        'vgm_methode',
        'poids_brut_marchandise',
        'vgm_poids',
        'numero_plomb',
        'changement_plomb',
        'raison_changement_plomb',
        'nouveau_plomb',
        'matricule_camion',
        'chauffeur',
        'transporteur',
        'cin_chauffeur',
        'telephone_chauffeur',
        'cin_chauffeur_scan',
        'lieu_chargement',
        'arrivee_usine',
        'depart_usine',
        'ctrl_conteneur',
        'ctrl_plomb',
        'ctrl_documents',
        'observations_controle',
    ];

    protected $casts = [
        'changement_plomb' => 'boolean',
        'ctrl_conteneur' => 'boolean',
        'ctrl_plomb' => 'boolean',
        'ctrl_documents' => 'boolean',
        'ordre' => 'integer',
        'poids_brut_marchandise' => 'decimal:3',
        'vgm_poids' => 'decimal:3',
        'arrivee_usine' => 'datetime',
        'depart_usine' => 'datetime',
    ];

    public function livraison(): BelongsTo
    {
        return $this->belongsTo(Livraison::class);
    }
}
