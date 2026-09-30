<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Exportation extends Model
{
    public const STATUTS = [
        'commande',
        'preparation',
        'production',
        'emballage',
        'reservation',
        'chargement',
        'documents',
        'expedition',
        'arrivee',
        'cloturee',
    ];

    public const ASSURANCES = ['Aucune', 'CIF', 'Client', 'Exportateur'];

    public const TYPES_EMBALLAGE_DOC = [
        'perdu' => 'Perdu',
        'sous_reserve' => 'Sous réserve de retour',
        'les_deux' => 'Perdu + sous réserve de retour',
    ];

    public const ENVOIS_DOCUMENTS = [
        'Original', 'Copie', 'Scan email', 'Courier', 'Transitaire', 'Banque',
    ];

    protected $fillable = [
        'numero', 'numero_facture', 'date_creation', 'client_id', 'exportateur_id', 'commande_id',
        'pays', 'destination', 'adresse_livraison',
        'commercial', 'reference_commande_client', 'numero_commande', 'numero_liste_colisage',
        'date_commande', 'incoterm', 'devise',
        'transport_applique', 'montant_transport',
        'conditions_paiement', 'assurance', 'type_emballage_doc', 'envoi_documents',
        'origine_marchandise', 'transitaire',
        'mode_transport', 'booking', 'numero_swb_bl', 'compagnie_maritime', 'navire',
        'voyage', 'port_chargement', 'port_destination', 'etd', 'eta', 'conteneur',
        'type_conteneur', 'plomb_scelle', 'transporteur', 'chauffeur', 'chauffeur_cin',
        'immatriculation', 'vgm_poids', 'vgm_valide', 'statut', 'emballages_temporaires',
        'observations', 'created_by',
        'exp_nom', 'exp_nom_societe', 'exp_ref_foodex', 'exp_adresse', 'exp_web', 'exp_telephone', 'exp_email',
        'dest_nom', 'dest_societe', 'dest_adresse', 'dest_tva', 'dest_eori', 'dest_contact',
        'banque', 'agence', 'beneficiaire', 'iban', 'swift', 'rib',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'date_commande' => 'date',
        'etd' => 'date',
        'eta' => 'date',
        'vgm_poids' => 'decimal:3',
        'vgm_valide' => 'boolean',
        'emballages_temporaires' => 'boolean',
        'transport_applique' => 'boolean',
        'montant_transport' => 'decimal:2',
    ];

    protected $appends = [
        'total_quantite', 'total_cartons', 'total_palettes', 'total_packages',
        'total_emballage', 'total_poids_net', 'total_poids_brut', 'total_poids_egoutte',
        'total_valeur', 'total_fob', 'total_cfr',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function exportateur(): BelongsTo
    {
        return $this->belongsTo(Exportateur::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(ExportationArticle::class);
    }

    public function dossierEmballage(): HasOne
    {
        return $this->hasOne(DossierEmballage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentGenere::class);
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalQuantiteAttribute(): float
    {
        return (float) $this->lignes->sum('quantite');
    }

    public function getTotalCartonsAttribute(): int
    {
        return (int) $this->lignes->sum('cartons');
    }

    public function getTotalPalettesAttribute(): int
    {
        return (int) $this->lignes->sum('palettes');
    }

    public function getTotalPackagesAttribute(): int
    {
        $fromColis = (int) $this->lignes->sum('nb_colis');
        return $fromColis > 0 ? $fromColis : (int) $this->lignes->sum('cartons');
    }

    public function getTotalEmballageAttribute(): int
    {
        $fromLines = (int) $this->lignes->sum('total_emballage');
        return $fromLines > 0 ? $fromLines : $this->total_packages;
    }

    public function getTotalPoidsNetAttribute(): float
    {
        return (float) $this->lignes->sum('poids_net');
    }

    public function getTotalPoidsBrutAttribute(): float
    {
        return (float) $this->lignes->sum('poids_brut');
    }

    public function getTotalPoidsEgoutteAttribute(): float
    {
        return (float) $this->lignes->sum('poids_egoutte');
    }

    public function getTotalValeurAttribute(): float
    {
        return (float) $this->lignes->sum('montant');
    }

    public function getTotalFobAttribute(): float
    {
        return round($this->total_valeur, 2);
    }

    public function getTotalCfrAttribute(): float
    {
        $fob = $this->total_fob;
        if (!$this->transport_applique) {
            return $fob;
        }

        return round($fob + (float) $this->montant_transport, 2);
    }

    public function needsSuiviEmballage(): bool
    {
        $type = $this->type_emballage_doc;
        if (in_array($type, ['sous_reserve', 'les_deux'], true)) {
            return true;
        }

        return $this->lignes->contains(function (ExportationArticle $ligne) {
            return (bool) ($ligne->article?->sous_reserve_retour);
        });
    }

    public static function fillFromExportateur(?Exportateur $exportateur): array
    {
        if (!$exportateur) {
            return [];
        }

        return [
            'exportateur_id' => $exportateur->id,
            'exp_nom' => $exportateur->nom,
            'exp_nom_societe' => $exportateur->nom_societe ?: $exportateur->nom,
            'exp_ref_foodex' => $exportateur->ref_foodex,
            'exp_adresse' => $exportateur->adresse,
            'exp_web' => $exportateur->web,
            'exp_telephone' => $exportateur->telephone,
            'exp_email' => $exportateur->email,
            'banque' => $exportateur->banque,
            'agence' => $exportateur->agence,
            'beneficiaire' => $exportateur->beneficiaire ?: ($exportateur->nom_societe ?: $exportateur->nom),
            'iban' => $exportateur->iban,
            'swift' => $exportateur->swift,
            'rib' => $exportateur->rib,
        ];
    }

    public static function fillFromClient(?Client $client): array
    {
        if (!$client) {
            return [];
        }

        $adresse = trim(implode(', ', array_filter([
            $client->adresse_livraison ?: $client->adresse,
            $client->ville,
            $client->pays,
        ])));

        $conditions = trim(($client->delai_paiement ?? '') . ' ' . ($client->delai_paiement_type ?? ''));

        $data = [
            'client_id' => $client->id,
            'dest_nom' => $client->nom,
            'dest_societe' => $client->nomination ?: $client->nom,
            'dest_adresse' => $adresse,
            'dest_tva' => $client->numero_tva,
            'dest_eori' => $client->eori,
            'dest_contact' => $client->contact_nom,
            'pays' => $client->pays,
            'destination' => $client->pays,
            'adresse_livraison' => $client->adresse_livraison ?: $client->adresse,
            'incoterm' => $client->incoterm,
            'devise' => $client->devise,
            'commercial' => $client->commercial_charge,
            'transitaire' => $client->transitaire,
            'port_chargement' => $client->port_chargement,
            'conditions_paiement' => $conditions ?: null,
        ];

        $mode = strtolower((string) ($client->mode_transport ?? ''));
        if (in_array($mode, ['maritime', 'routier', 'aerien'], true)) {
            $data['mode_transport'] = $mode;
        }

        return $data;
    }
}
