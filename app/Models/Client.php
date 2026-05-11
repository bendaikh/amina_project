<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_client',
        'ice_cin',
        'nom',
        'email',
        'telephone',
        'adresse',
        'ville',
        'pays',
        'categorie',
        'devise',
        'statut',
        'date_creation',
        'secteur_activite',
        'groupe_categorie',
        'commercial_charge',
        'site_web',
        'contact_nom',
        'contact_fonction',
        'contact_telephone',
        'contact_email',
        'numero_tva',
        'numero_rc',
        'eori',
        'pays_eori',
        'incoterm',
        'langue',
        'delai_paiement',
        'plafond_credit',
        'solde_actuel',
        'mode_transport',
        'adresse_livraison',
        'transitaire',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date_creation' => 'date',
        'delai_paiement' => 'integer',
        'plafond_credit' => 'decimal:2',
        'solde_actuel' => 'decimal:2',
    ];

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_client')
            ->withPivot('prix_negocie')
            ->withTimestamps();
    }
}
