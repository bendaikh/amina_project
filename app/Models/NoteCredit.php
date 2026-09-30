<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NoteCredit extends Model
{
    protected $table = 'notes_credit';

    public const STATUTS = ['brouillon', 'validee', 'appliquee', 'annulee'];

    public const ORIGINES = [
        'facture',
        'retour',
        'reclamation',
        'correction_commerciale',
        'erreur_prix',
        'annulation_partielle',
        'directe',
    ];

    public const MOTIFS = [
        'retour',
        'erreur_facturation',
        'remise',
        'non_conformite',
        'annulation',
        'correction_prix',
        'geste_commercial',
        'autre',
    ];

    protected $fillable = [
        'numero', 'date_note', 'client_id', 'origine', 'facture_origine',
        'reclamation_id', 'commande_id', 'retour_ref', 'motif', 'devise',
        'observations', 'statut', 'total_ht', 'total_tva', 'total_ttc', 'created_by',
    ];

    protected $casts = [
        'date_note' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(NoteCreditLigne::class);
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
        $this->save();
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "NC-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
