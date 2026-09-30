<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMouvement extends Model
{
    protected $table = 'stock_movements';

    public const TYPES = [
        'entree_achat',
        'sortie_vente',
        'sortie_production',
        'entree_production',
        'retour',
        'transfert',
        'ajustement',
        'inventaire',
        'mise_au_rebut',
        'reservation',
        'liberation',
    ];

    public const TYPE_LABELS = [
        'entree_achat' => 'Entrée achat',
        'sortie_vente' => 'Sortie vente',
        'sortie_production' => 'Sortie production',
        'entree_production' => 'Entrée production',
        'retour' => 'Retour',
        'transfert' => 'Transfert',
        'ajustement' => 'Ajustement',
        'inventaire' => 'Inventaire',
        'mise_au_rebut' => 'Mise au rebut',
        'reservation' => 'Réservation',
        'liberation' => 'Libération',
    ];

    protected $fillable = [
        'date_mouvement', 'type', 'article_id', 'quantite', 'unite', 'lot',
        'stock_location_id', 'from_location_id', 'to_location_id',
        'document_type', 'document_ref', 'document_id',
        'user_id', 'commentaire', 'annule',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
        'quantite' => 'decimal:3',
        'annule' => 'boolean',
    ];

    protected $appends = ['type_label'];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
