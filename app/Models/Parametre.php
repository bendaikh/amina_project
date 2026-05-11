<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parametre extends Model
{
    protected $fillable = [
        'type',
        'valeur',
        'code',
        'description',
        'ordre',
        'actif',
        'parent_id',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    // Parameter types constants
    const TYPE_FAMILLE = 'famille';
    const TYPE_SOUS_FAMILLE = 'sous_famille';
    const TYPE_CALIBRE = 'calibre';
    const TYPE_EMBALLAGE_PRIMAIRE = 'type_emballage_primaire';
    const TYPE_EMBALLAGE_SECONDAIRE = 'type_emballage_secondaire';
    const TYPE_PALETTE = 'type_palette';
    const TYPE_UNITE_FACTURATION = 'unite_facturation';
    const TYPE_DEVISE = 'devise';
    const TYPE_TAUX_TVA = 'taux_tva';
    const TYPE_MARCHE = 'marche';
    const TYPE_SECTEUR_ACTIVITE = 'secteur_activite';
    const TYPE_GROUPE_CATEGORIE = 'groupe_categorie';
    const TYPE_INCOTERM = 'incoterm';
    const TYPE_DELAI_PAIEMENT = 'delai_paiement';

    public static function getTypes(): array
    {
        return [
            self::TYPE_FAMILLE => 'Familles',
            self::TYPE_SOUS_FAMILLE => 'Sous-familles',
            self::TYPE_CALIBRE => 'Calibres',
            self::TYPE_EMBALLAGE_PRIMAIRE => 'Types emballage primaire',
            self::TYPE_EMBALLAGE_SECONDAIRE => 'Types emballage secondaire',
            self::TYPE_PALETTE => 'Types de palette',
            self::TYPE_UNITE_FACTURATION => 'Unités de facturation',
            self::TYPE_DEVISE => 'Devises',
            self::TYPE_TAUX_TVA => 'Taux TVA',
            self::TYPE_MARCHE => 'Marchés',
            self::TYPE_SECTEUR_ACTIVITE => 'Secteurs d\'activité',
            self::TYPE_GROUPE_CATEGORIE => 'Groupes / Catégories',
            self::TYPE_INCOTERM => 'Incoterms',
            self::TYPE_DELAI_PAIEMENT => 'Délais de paiement',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Parametre::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Parametre::class, 'parent_id')->orderBy('ordre');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }

    public static function getByType(string $type, bool $activeOnly = true)
    {
        $query = self::ofType($type)->ordered();
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->get();
    }

    public static function getSousFamillesByFamille(int $familleId)
    {
        return self::ofType(self::TYPE_SOUS_FAMILLE)
            ->where('parent_id', $familleId)
            ->active()
            ->ordered()
            ->get();
    }
}
