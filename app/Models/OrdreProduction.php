<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OrdreProduction extends Model
{
    protected $table = 'ordres_production';

    public const STATUTS = [
        'a_planifier',
        'planifiee',
        'en_cours',
        'partiellement_terminee',
        'terminee',
        'bloquee',
        'annulee',
    ];

    protected $fillable = [
        'numero', 'commande_id', 'commande_ligne_id', 'article_id', 'designation',
        'quantite_commandee', 'quantite_a_produire', 'quantite_produite',
        'date_planifiee', 'date_debut', 'date_fin', 'responsable', 'equipe',
        'statut', 'observations',
        'effet_sortie_matieres', 'effet_entree_pf', 'effet_sous_produits', 'effet_dechets',
        'created_by',
    ];

    protected $casts = [
        'quantite_commandee' => 'decimal:3',
        'quantite_a_produire' => 'decimal:3',
        'quantite_produite' => 'decimal:3',
        'date_planifiee' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'effet_sortie_matieres' => 'boolean',
        'effet_entree_pf' => 'boolean',
        'effet_sous_produits' => 'boolean',
        'effet_dechets' => 'boolean',
    ];

    protected $appends = ['quantite_restante'];

    public function getQuantiteRestanteAttribute(): float
    {
        return max(0, round((float) $this->quantite_a_produire - (float) $this->quantite_produite, 3));
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(CommandeLigne::class, 'commande_ligne_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "OF-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
