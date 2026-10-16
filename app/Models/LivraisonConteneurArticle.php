<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivraisonConteneurArticle extends Model
{
    protected $table = 'livraison_conteneur_articles';

    protected $fillable = ['livraison_conteneur_id', 'commande_ligne_id', 'article_id', 'cartons', 'palettes'];

    protected $casts = ['cartons' => 'integer', 'palettes' => 'integer'];

    public function conteneur(): BelongsTo
    {
        return $this->belongsTo(LivraisonConteneur::class, 'livraison_conteneur_id');
    }

    public function commandeLigne(): BelongsTo
    {
        return $this->belongsTo(CommandeLigne::class);
    }
}
