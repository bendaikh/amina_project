<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactureLocale extends Model
{
    protected $table = 'factures_locales';

    public const STATUTS = ['brouillon', 'validee', 'payee', 'annulee'];

    protected $fillable = [
        'numero', 'date_facture', 'client_id', 'commande_id', 'bon_livraison_id',
        'reference_client', 'numero_bl', 'numero_commande',
        'devise', 'conditions_paiement', 'mode_paiement', 'echeance', 'statut',
        'total_ht', 'total_tva', 'total_ttc', 'observations', 'created_by',
    ];

    protected $casts = [
        'date_facture' => 'date',
        'echeance' => 'date',
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function bonLivraison(): BelongsTo
    {
        return $this->belongsTo(BonLivraison::class, 'bon_livraison_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(FactureLocaleLigne::class, 'facture_locale_id')->orderBy('ordre');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(ReglementLigne::class, 'facture_id')
            ->where('reglement_lignes.facture_type', ReglementLigne::LOCALE);
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "FL-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing('lignes');
        $this->total_ht = round((float) $this->lignes->sum('montant_ht'), 2);
        $this->total_tva = round((float) $this->lignes->sum('montant_tva'), 2);
        $this->total_ttc = round((float) $this->lignes->sum('montant_ttc'), 2);
        $this->save();
    }

    public static function computeLineAmounts(array $ligne): array
    {
        $qte = (float) ($ligne['quantite'] ?? 0);
        $prix = (float) ($ligne['prix_unitaire'] ?? 0);
        $tva = (float) ($ligne['tva_taux'] ?? 20);
        $ht = round($qte * $prix, 2);
        $montantTva = round($ht * ($tva / 100), 2);

        return [
            'montant_ht' => $ht,
            'montant_tva' => $montantTva,
            'montant_ttc' => round($ht + $montantTva, 2),
        ];
    }
}
