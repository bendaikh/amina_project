<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonLivraisonLigne extends Model
{
    protected $table = 'bon_livraison_lignes';

    protected $fillable = [
        'bon_livraison_id', 'commande_ligne_id', 'article_id',
        'ref_article', 'designation', 'calibre', 'emballage', 'numero_lot',
        'total_colis', 'poids_net_eg_unitaire', 'total_poids_net', 'total_poids_total',
        'ordre',
    ];

    protected $casts = [
        'total_colis' => 'decimal:3',
        'poids_net_eg_unitaire' => 'decimal:3',
        'total_poids_net' => 'decimal:3',
        'total_poids_total' => 'decimal:3',
        'ordre' => 'integer',
    ];

    public function bonLivraison(): BelongsTo
    {
        return $this->belongsTo(BonLivraison::class, 'bon_livraison_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function commandeLigne(): BelongsTo
    {
        return $this->belongsTo(CommandeLigne::class);
    }
}
