<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Achat extends Model
{
    public const STATUTS = [
        'demande',
        'commande',
        'reception',
        'facture',
        'echeance',
        'regle',
    ];

    public const TYPES = ['stockable', 'non_stockable'];

    public const CATEGORIES_STOCKABLE = [
        'matieres',
        'produits',
        'emballages',
        'cartons',
        'futs',
        'palettes',
        'consommables_stockes',
        'pieces',
    ];

    public const CATEGORIES_NON_STOCKABLE = [
        'transport',
        'honoraires',
        'electricite',
        'assurance',
        'telecoms',
        'services',
        'frais_bancaires',
        'divers',
    ];

    protected $fillable = [
        'numero', 'date_achat', 'fournisseur_id', 'reference_commande', 'reference_facture',
        'date_facture', 'devise', 'conditions', 'mode_paiement', 'echeance', 'acheteur',
        'observations', 'type', 'categorie', 'statut', 'genere_entree_stock',
        'total_ht', 'total_tva', 'total_ttc', 'created_by',
    ];

    protected $casts = [
        'date_achat' => 'date',
        'date_facture' => 'date',
        'echeance' => 'date',
        'genere_entree_stock' => 'boolean',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(AchatLigne::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(AchatReception::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(FactureFournisseur::class);
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
        $this->total_ht = round((float) $this->lignes()->sum('montant_ht'), 2);
        $this->total_tva = round((float) $this->lignes()->sum('montant_tva'), 2);
        $this->total_ttc = round((float) $this->lignes()->sum('montant_ttc'), 2);
        $this->save();
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "ACH-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
