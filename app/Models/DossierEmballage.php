<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DossierEmballage extends Model
{
    protected $table = 'dossiers_emballages';

    protected $fillable = [
        'exportation_id', 'client_id', 'type_emballage', 'type_fut', 'reference_fut',
        'quantite_exportee', 'dum_52', 'date_dum', 'date_export',
        'date_limite_reimportation', 'facture_concernee', 'conteneur', 'destination',
        'statut', 'observations',
    ];

    protected $casts = [
        'quantite_exportee' => 'decimal:3',
        'date_dum' => 'date',
        'date_export' => 'date',
        'date_limite_reimportation' => 'date',
    ];

    protected $appends = [
        'quantite_reimporte', 'quantite_restante', 'jours_restants', 'alerte',
    ];

    public function exportation(): BelongsTo
    {
        return $this->belongsTo(Exportation::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function retours(): HasMany
    {
        return $this->hasMany(RetourEmballage::class, 'dossier_emballage_id');
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function getQuantiteReimporteAttribute(): float
    {
        return (float) $this->retours->sum('quantite');
    }

    public function getQuantiteRestanteAttribute(): float
    {
        return max(0, (float) $this->quantite_exportee - $this->quantite_reimporte);
    }

    public function getJoursRestantsAttribute(): ?int
    {
        if (!$this->date_limite_reimportation) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->date_limite_reimportation->startOfDay(), false);
    }

    public function getAlerteAttribute(): ?string
    {
        if ($this->statut === 'solde') {
            return 'soldee';
        }

        $restante = $this->quantite_restante;
        if ($restante <= 0) {
            return null;
        }

        $jours = $this->jours_restants;
        if ($jours === null) {
            return null;
        }

        if ($jours < 0) {
            return 'depassee';
        }
        if ($jours <= 7) {
            return '7_jours';
        }
        if ($jours <= 30) {
            return '30_jours';
        }
        if ($jours <= 90) {
            return '90_jours';
        }

        return null;
    }

    public function recalculerStatut(): void
    {
        $exporte = (float) $this->quantite_exportee;
        $retourne = (float) $this->retours()->sum('quantite');

        if ($retourne > $exporte) {
            $this->statut = 'anomalie';
        } elseif ($retourne <= 0) {
            $jours = $this->jours_restants;
            $this->statut = ($jours !== null && $jours < 0) ? 'en_depassement' : 'ouvert';
        } elseif ($retourne < $exporte) {
            $jours = $this->jours_restants;
            $this->statut = ($jours !== null && $jours < 0) ? 'en_depassement' : 'partiellement_solde';
        } else {
            $this->statut = 'solde';
        }

        $this->save();
    }
}
