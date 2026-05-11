<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'code_article',
        'designation',
        'famille',
        'sous_famille',
        'hs_code',
        'code_barres',
        'actif',
        'photo',
        'marque',
        'calibre',
        'total_colis_palet',
        'total_colis_carton',
        'lot',
        'date_production',
        'emballage_type',
        'emballage_ref',
        'emballage_tare',
        't_colis',
        't_emb',
        'poids_brut_unitaire',
        'poids_brut_total',
        'poids_net_unitaire',
        'poids_net_total',
        'poids_net_egoutte_unitaire',
        'poids_net_egoutte_total',
        'poids_brut',
        'poids_net',
        'poids_net_egoutte',
        'ph',
        'taux_sel',
        'type_emballage',
        'type_emballage_primaire',
        'type_emballage_secondaire',
        'unites_par_carton',
        'unites_par_colis',
        'cartons_par_palette',
        'colis_par_palette',
        'nombre_total_par_palette',
        'type_palette',
        'dimension_carton_l',
        'dimension_carton_w',
        'dimension_carton_h',
        'dimension_colis_l',
        'dimension_colis_w',
        'dimension_colis_h',
        'poids_carton',
        'poids_colis',
        'poids_palette',
        'dlc_dluo',
        'prix_vente',
        'unite_facturation',
        'devise',
        'taux_tva',
        'marche',
        'minimum_commande',
        'code_interne',
        'observations',
    ];

    protected $casts = [
        'date_production' => 'date',
        'actif' => 'boolean',
        'total_colis_palet' => 'integer',
        'total_colis_carton' => 'integer',
        'emballage_tare' => 'decimal:2',
        't_colis' => 'decimal:2',
        't_emb' => 'decimal:2',
        'poids_brut_unitaire' => 'decimal:2',
        'poids_brut_total' => 'decimal:2',
        'poids_net_unitaire' => 'decimal:2',
        'poids_net_total' => 'decimal:2',
        'poids_net_egoutte_unitaire' => 'decimal:2',
        'poids_net_egoutte_total' => 'decimal:2',
        'poids_brut' => 'decimal:2',
        'poids_net' => 'decimal:2',
        'poids_net_egoutte' => 'decimal:2',
        'ph' => 'decimal:2',
        'taux_sel' => 'decimal:2',
        'unites_par_carton' => 'integer',
        'unites_par_colis' => 'integer',
        'cartons_par_palette' => 'integer',
        'colis_par_palette' => 'integer',
        'nombre_total_par_palette' => 'integer',
        'dimension_carton_l' => 'decimal:2',
        'dimension_carton_w' => 'decimal:2',
        'dimension_carton_h' => 'decimal:2',
        'dimension_colis_l' => 'decimal:2',
        'dimension_colis_w' => 'decimal:2',
        'dimension_colis_h' => 'decimal:2',
        'poids_carton' => 'decimal:2',
        'poids_colis' => 'decimal:2',
        'poids_palette' => 'decimal:2',
        'dlc_dluo' => 'integer',
        'prix_vente' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'minimum_commande' => 'integer',
    ];

    public function getTotalColisAttribute()
    {
        return $this->total_colis_palet + $this->total_colis_carton;
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'article_client')
            ->withPivot('prix_negocie')
            ->withTimestamps();
    }
}
