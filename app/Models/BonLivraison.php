<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonLivraison extends Model
{
    protected $table = 'bons_livraison';

    public const STATUTS = ['brouillon', 'valide', 'annule'];

    protected $fillable = [
        'numero', 'date_creation', 'commande_id', 'client_id', 'reference_client',
        'date_heure_livraison', 'informations_additionnelles', 'statut',
        'total_colis', 'total_poids_net', 'total_poids_total', 'created_by',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'date_heure_livraison' => 'datetime',
        'total_colis' => 'decimal:3',
        'total_poids_net' => 'decimal:3',
        'total_poids_total' => 'decimal:3',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BonLivraisonLigne::class, 'bon_livraison_id')->orderBy('ordre');
    }

    public function factureLocale(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FactureLocale::class, 'bon_livraison_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotals(): void
    {
        $this->load('lignes');
        $this->total_colis = round((float) $this->lignes->sum('total_colis'), 3);
        $this->total_poids_net = round((float) $this->lignes->sum('total_poids_net'), 3);
        $this->total_poids_total = round((float) $this->lignes->sum('total_poids_total'), 3);
        $this->save();
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "BL-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Build BL line payload from a commande ligne (+ article snapshot).
     */
    public static function ligneFromCommandeLigne(CommandeLigne $ligne, int $ordre = 0): array
    {
        $article = $ligne->relationLoaded('article') ? $ligne->article : $ligne->article()->first();

        $colis = (float) ($ligne->colis_par_palette ?? $article?->colis_par_palette ?? 0);
        $poidsNetEg = (float) ($ligne->poids_net_egoutte ?? $article?->poids_net_egoutte ?? 0);
        $poidsNet = (float) ($article?->poids_net ?? 0);
        $poidsBrut = (float) ($article?->poids_brut ?? 0);
        $nombreTotal = (float) ($ligne->nombre_total_par_palette ?? $article?->nombre_total_par_palette ?? 0);

        // Prefer stored quantity (nombre_total × poids_net_ég.) as total net weight
        $totalPoidsNet = (float) $ligne->quantite;
        if ($totalPoidsNet <= 0 && $nombreTotal > 0 && $poidsNetEg > 0) {
            $totalPoidsNet = round($nombreTotal * $poidsNetEg, 3);
        } elseif ($totalPoidsNet <= 0 && $nombreTotal > 0 && $poidsNet > 0) {
            $totalPoidsNet = round($nombreTotal * $poidsNet, 3);
        }

        $totalPoidsTotal = $nombreTotal > 0 && $poidsBrut > 0
            ? round($nombreTotal * $poidsBrut, 3)
            : round($totalPoidsNet + ($nombreTotal * (float) ($article?->tare ?? 0)), 3);

        $emballageParts = array_filter([
            $ligne->type_emballage_primaire ?? $article?->type_emballage_primaire,
            $ligne->reference_emballage ?? $article?->type_palette,
            $ligne->type_emballage_secondaire ?? $article?->type_emballage_secondaire,
        ]);

        return [
            'commande_ligne_id' => $ligne->id,
            'article_id' => $ligne->article_id,
            'ref_article' => $article?->code_article,
            'designation' => $ligne->designation ?: ($article?->designation),
            'calibre' => $ligne->calibre ?: ($article?->calibre),
            'emballage' => implode(' / ', $emballageParts) ?: null,
            'numero_lot' => $article?->lot,
            'total_colis' => $colis,
            'poids_net_eg_unitaire' => $poidsNetEg ?: null,
            'total_poids_net' => $totalPoidsNet,
            'total_poids_total' => $totalPoidsTotal,
            'ordre' => $ordre,
        ];
    }
}
