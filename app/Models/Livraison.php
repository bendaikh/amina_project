<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Livraison extends Model
{
    public const STATUTS = [
        'a_preparer',
        'en_preparation',
        'pret',
        'charge',
        'livre',
        'expedie',
        'annule',
    ];

    public const TYPES = ['locale', 'export', 'express'];

    protected $fillable = [
        'numero', 'commande_id', 'reservation_booking', 'type_livraison',
        'date_prevue', 'date_preparation', 'date_chargement', 'date_cutoff', 'date_livraison',
        'transporteur', 'chauffeur', 'cin_chauffeur', 'cin_chauffeur_scan',
        'vehicule', 'matricule_camion',
        'compagnie_maritime', 'numero_reservation', 'numero_booking', 'numero_bl_swb', 'navire',
        'port_depart', 'port_arrivee', 'eta', 'etd',
        'numero_conteneur', 'tare_conteneur', 'numero_plomb',
        'changement_plomb', 'raison_changement_plomb', 'nouveau_plomb',
        'adresse',
        'quantite_a_livrer', 'quantite_preparee', 'quantite_chargee',
        'statut', 'observations', 'documents', 'created_by',
    ];

    protected $casts = [
        'reservation_booking' => 'boolean',
        'changement_plomb' => 'boolean',
        'date_prevue' => 'date',
        'date_preparation' => 'date',
        'date_chargement' => 'date',
        'date_cutoff' => 'date',
        'date_livraison' => 'date',
        'eta' => 'date',
        'etd' => 'date',
        'quantite_a_livrer' => 'decimal:3',
        'quantite_preparee' => 'decimal:3',
        'quantite_chargee' => 'decimal:3',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function conteneurs(): HasMany
    {
        return $this->hasMany(LivraisonConteneur::class)->orderBy('ordre')->orderBy('id');
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "LIV-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
