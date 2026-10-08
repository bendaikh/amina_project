<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FactureFournisseur extends Model
{
    protected $table = 'facture_fournisseurs';

    public const ORIGINES = ['commande', 'reception', 'directe'];

    public const STATUTS = ['brouillon', 'validee', 'echeance', 'payee'];

    protected $fillable = [
        'numero', 'date_facture', 'fournisseur_id', 'achat_id', 'reference',
        'devise', 'conditions', 'mode_paiement', 'echeance', 'origine', 'statut',
        'total_ht', 'total_tva', 'total_ttc', 'observations',
        'suivi_facture', 'suivi_tva', 'suivi_justificatifs', 'suivi_docs_fiscaux',
        'suivi_import', 'suivi_transport', 'suivi_certificats', 'suivi_echeance',
        'suivi_pieces_jointes', 'created_by',
    ];

    protected $casts = [
        'date_facture' => 'date',
        'echeance' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
        'suivi_facture' => 'boolean',
        'suivi_tva' => 'boolean',
        'suivi_justificatifs' => 'boolean',
        'suivi_docs_fiscaux' => 'boolean',
        'suivi_import' => 'boolean',
        'suivi_transport' => 'boolean',
        'suivi_certificats' => 'boolean',
        'suivi_echeance' => 'boolean',
        'suivi_pieces_jointes' => 'boolean',
    ];

    protected $appends = ['suivi_complet', 'suivi_progress'];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(FactureFournisseurLigne::class);
    }

    public function receptions(): BelongsToMany
    {
        return $this->belongsToMany(
            AchatReception::class,
            'facture_reception',
            'facture_fournisseur_id',
            'achat_reception_id'
        )->withTimestamps();
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(ReglementLigne::class, 'facture_id')
            ->where('reglement_lignes.facture_type', ReglementLigne::FOURNISSEUR);
    }

    public function getSuiviCompletAttribute(): bool
    {
        return $this->suivi_facture
            && $this->suivi_tva
            && $this->suivi_justificatifs
            && $this->suivi_docs_fiscaux
            && $this->suivi_import
            && $this->suivi_transport
            && $this->suivi_certificats
            && $this->suivi_echeance
            && $this->suivi_pieces_jointes;
    }

    public function getSuiviProgressAttribute(): int
    {
        $flags = [
            $this->suivi_facture, $this->suivi_tva, $this->suivi_justificatifs,
            $this->suivi_docs_fiscaux, $this->suivi_import, $this->suivi_transport,
            $this->suivi_certificats, $this->suivi_echeance, $this->suivi_pieces_jointes,
        ];
        $done = count(array_filter($flags));

        return (int) round(($done / count($flags)) * 100);
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
        $prefix = "FF-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
