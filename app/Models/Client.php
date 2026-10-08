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
        'statut_prospection',
        'actif',
        'marque',
        'nomination',
        'date_creation',
        'secteur_activite',
        'groupe_categorie',
        'commercial_charge',
        'source',
        'produits_interesses',
        'premier_contact',
        'dernier_contact',
        'prochaine_action',
        'prochaine_action_date',
        'converti_at',
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
        'delai_paiement_type',
        'plafond_credit',
        'solde_actuel',
        'mode_transport',
        'adresse_livraison',
        'transitaire',
        'port_chargement',
    ];

    public const STATUTS_PROSPECTION = [
        'Nouveau',
        'En prospection',
        'Contact établi',
        'Échantillon envoyé',
        'Offre envoyée',
        'En négociation',
        'Converti en client',
        'Perdu',
        'À relancer',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date_creation' => 'date',
        'premier_contact' => 'date',
        'dernier_contact' => 'date',
        'prochaine_action_date' => 'date',
        'converti_at' => 'datetime',
        'delai_paiement' => 'integer',
        'plafond_credit' => 'decimal:2',
        'solde_actuel' => 'decimal:2',
        'actif' => 'boolean',
        'marque' => 'boolean',
    ];

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_client')
            ->withPivot('prix_negocie', 'code_barres', 'marque')
            ->withTimestamps();
    }

    public function exportations()
    {
        return $this->hasMany(Exportation::class);
    }

    public function dossiersEmballages()
    {
        return $this->hasMany(DossierEmballage::class);
    }

    public function reglements()
    {
        return $this->hasMany(Reglement::class);
    }
}
