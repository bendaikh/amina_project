<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BonReception extends Model
{
    protected $table = 'bons_reception';

    public const STATUTS = ['brouillon', 'valide', 'annule'];

    protected $fillable = [
        'numero', 'date_reception', 'fournisseur_id', 'achat_id', 'achat_reception_id',
        'reference_achat', 'reference_commande', 'reference_facture',
        'statut', 'observations', 'entree_stock_generee',
        'valide_at', 'created_by', 'valide_by',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'valide_at' => 'datetime',
        'entree_stock_generee' => 'boolean',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function achatReception(): BelongsTo
    {
        return $this->belongsTo(AchatReception::class, 'achat_reception_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BonReceptionLigne::class, 'bon_reception_id');
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
        $prefix = "BR-{$year}-";

        $lastBon = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $lastAchat = \App\Models\AchatReception::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');

        $seqBon = $lastBon ? ((int) substr($lastBon, -4)) : 0;
        $seqAchat = $lastAchat ? ((int) substr($lastAchat, -4)) : 0;
        $seq = max($seqBon, $seqAchat) + 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
