<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Commande extends Model
{
    public const STATUTS = [
        'brouillon',
        'en_attente',
        'confirmee',
        'en_preparation',
        'en_production',
        'partiellement_livree',
        'livree',
        'cloturee',
    ];

    public const TYPES = ['local', 'export'];

    public const PRIORITES = ['basse', 'normale', 'haute', 'urgente'];

    protected $fillable = [
        'numero', 'date_commande', 'client_id', 'reference_client', 'commercial',
        'type', 'devise', 'mode_paiement', 'incoterm', 'destination', 'adresse',
        'date_souhaitee', 'priorite', 'observations', 'statut',
        'total_ht', 'total_tva', 'total_ttc', 'marge_estimee',
        'montant_facture', 'montant_regle', 'quantite_livree', 'created_by',
    ];

    protected $casts = [
        'date_commande' => 'date',
        'date_souhaitee' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
        'marge_estimee' => 'decimal:2',
        'montant_facture' => 'decimal:2',
        'montant_regle' => 'decimal:2',
        'quantite_livree' => 'decimal:3',
    ];

    protected $appends = ['solde', 'quantite_restante'];

    public function getSoldeAttribute(): float
    {
        return round((float) $this->montant_facture - (float) $this->montant_regle, 2);
    }

    public function getQuantiteRestanteAttribute(): float
    {
        $commandee = $this->relationLoaded('lignes')
            ? (float) $this->lignes->sum('quantite')
            : (float) $this->lignes()->sum('quantite');

        return max(0, round($commandee - (float) $this->quantite_livree, 3));
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CommandeLigne::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(OrdreProduction::class);
    }

    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class);
    }

    public function bonLivraison(): HasOne
    {
        return $this->hasOne(BonLivraison::class);
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotals(): void
    {
        $this->load('lignes');
        $this->total_ht = round((float) $this->lignes->sum('montant_ht'), 2);
        $this->total_tva = round((float) $this->lignes->sum('montant_tva'), 2);
        $this->total_ttc = round((float) $this->lignes->sum('montant_ttc'), 2);

        $marge = 0;
        foreach ($this->lignes as $l) {
            $cout = (float) $l->cout_unitaire * (float) $l->quantite;
            $marge += (float) $l->montant_ht - $cout;
        }
        $this->marge_estimee = round($marge, 2);
        $this->quantite_livree = round((float) $this->lignes->sum('quantite_livree'), 3);
        $this->save();
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "CMD-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
