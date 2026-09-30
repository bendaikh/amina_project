<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportationArticle extends Model
{
    protected $fillable = [
        'exportation_id', 'article_id', 'code_article', 'designation', 'hs_code',
        'calibre', 'numero_lot', 'date_production', 'nb_colis',
        'nombre_par_colis', 'total_emballage',
        'conditionnement', 'reference_emballage', 'quantite', 'cartons', 'palettes',
        'poids_brut_unitaire', 'poids_brut',
        'poids_net_unitaire', 'poids_net',
        'poids_net_egoutte_unitaire', 'poids_egoutte',
        'prix_unitaire', 'devise', 'montant', 'origine',
    ];

    protected $casts = [
        'date_production' => 'date',
        'quantite' => 'decimal:3',
        'cartons' => 'integer',
        'palettes' => 'integer',
        'nb_colis' => 'integer',
        'nombre_par_colis' => 'integer',
        'total_emballage' => 'integer',
        'poids_brut_unitaire' => 'decimal:3',
        'poids_brut' => 'decimal:3',
        'poids_net_unitaire' => 'decimal:3',
        'poids_net' => 'decimal:3',
        'poids_net_egoutte_unitaire' => 'decimal:3',
        'poids_egoutte' => 'decimal:3',
        'prix_unitaire' => 'decimal:4',
        'montant' => 'decimal:2',
    ];

    public function exportation(): BelongsTo
    {
        return $this->belongsTo(Exportation::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
