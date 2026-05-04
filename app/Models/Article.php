<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'designation',
        'hs_code',
        'total_colis_palet',
        'total_colis_carton',
        'lot',
        'calibre',
        'date_production',
        'marque',
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
    ];

    protected $casts = [
        'date_production' => 'date',
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
    ];

    public function getTotalColisAttribute()
    {
        return $this->total_colis_palet + $this->total_colis_carton;
    }
}
