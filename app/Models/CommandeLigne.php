<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeLigne extends Model
{
    protected $fillable = [
        'commande_id', 'article_id', 'designation',
        'calibre', 'type_emballage_primaire', 'reference_emballage',
        'type_emballage_secondaire', 'unites_par_colis', 'colis_par_palette',
        'nombre_total_par_palette', 'poids_net_egoutte',
        'quantite', 'unite', 'prix',
        'remise', 'tva_taux', 'lot', 'date_production',
        'montant_ht', 'montant_tva', 'montant_ttc',
        'cout_unitaire', 'quantite_disponible', 'quantite_reservee',
        'quantite_a_produire', 'quantite_livree',
    ];

    protected $casts = [
        'quantite' => 'decimal:3',
        'prix' => 'decimal:4',
        'remise' => 'decimal:2',
        'tva_taux' => 'decimal:2',
        'date_production' => 'date',
        'montant_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'cout_unitaire' => 'decimal:4',
        'quantite_disponible' => 'decimal:3',
        'quantite_reservee' => 'decimal:3',
        'quantite_a_produire' => 'decimal:3',
        'quantite_livree' => 'decimal:3',
        'unites_par_colis' => 'integer',
        'colis_par_palette' => 'integer',
        'nombre_total_par_palette' => 'integer',
        'poids_net_egoutte' => 'decimal:3',
    ];

    protected $appends = ['quantite_restante'];

    public function getQuantiteRestanteAttribute(): float
    {
        return max(0, round((float) $this->quantite - (float) $this->quantite_livree, 3));
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public static function computeAmounts(array $ligne): array
    {
        $qte = (float) ($ligne['quantite'] ?? 0);
        $prix = (float) ($ligne['prix'] ?? 0);
        $remise = (float) ($ligne['remise'] ?? 0);
        $tva = (float) ($ligne['tva_taux'] ?? 20);

        $brut = $qte * $prix;
        $ht = round($brut * (1 - $remise / 100), 2);
        $montantTva = round($ht * ($tva / 100), 2);
        $ttc = round($ht + $montantTva, 2);

        return [
            'montant_ht' => $ht,
            'montant_tva' => $montantTva,
            'montant_ttc' => $ttc,
        ];
    }
}
