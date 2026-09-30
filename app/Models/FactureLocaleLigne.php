<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactureLocaleLigne extends Model
{
    protected $table = 'facture_locale_lignes';

    protected $fillable = [
        'facture_locale_id', 'article_id', 'bon_livraison_ligne_id', 'commande_ligne_id',
        'ref_article', 'designation', 'calibre', 'emballage', 'numero_lot',
        'quantite', 'unite', 'prix_unitaire', 'tva_taux',
        'montant_ht', 'montant_tva', 'montant_ttc', 'ordre',
    ];

    protected $casts = [
        'quantite' => 'decimal:3',
        'prix_unitaire' => 'decimal:4',
        'tva_taux' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'ordre' => 'integer',
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureLocale::class, 'facture_locale_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function bonLivraisonLigne(): BelongsTo
    {
        return $this->belongsTo(BonLivraisonLigne::class, 'bon_livraison_ligne_id');
    }

    public function commandeLigne(): BelongsTo
    {
        return $this->belongsTo(CommandeLigne::class);
    }
}
