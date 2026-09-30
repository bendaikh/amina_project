<?php

namespace App\Services;

use App\Models\BonReception;
use App\Models\BonReceptionLigne;
use App\Models\Inventaire;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\StockMouvement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    /**
     * Record a stock movement and update balances.
     * Only articles with a real quantity change should call this (principe stockable).
     */
    public function move(array $data, ?int $userId = null): StockMouvement
    {
        return DB::transaction(function () use ($data, $userId) {
            $type = $data['type'];
            if (!in_array($type, StockMouvement::TYPES, true)) {
                throw new InvalidArgumentException("Type de mouvement invalide: {$type}");
            }

            $qty = (float) ($data['quantite'] ?? 0);
            if ($qty == 0.0 && !in_array($type, ['reservation', 'liberation'], true)) {
                throw new InvalidArgumentException('Quantité invalide');
            }

            $mouvement = StockMouvement::create([
                'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
                'type' => $type,
                'article_id' => $data['article_id'],
                'quantite' => abs($qty),
                'unite' => $data['unite'] ?? null,
                'lot' => $data['lot'] ?? null,
                'stock_location_id' => $data['stock_location_id'] ?? null,
                'from_location_id' => $data['from_location_id'] ?? null,
                'to_location_id' => $data['to_location_id'] ?? null,
                'document_type' => $data['document_type'] ?? null,
                'document_ref' => $data['document_ref'] ?? null,
                'document_id' => $data['document_id'] ?? null,
                'user_id' => $userId ?? ($data['user_id'] ?? null),
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            $this->applyToBalance($mouvement);

            return $mouvement->fresh(['article', 'location', 'fromLocation', 'toLocation', 'user']);
        });
    }

    public function applyToBalance(StockMouvement $m): void
    {
        $qty = (float) $m->quantite;

        switch ($m->type) {
            case 'entree_achat':
            case 'entree_production':
            case 'retour':
                $this->adjust($m->article_id, $m->stock_location_id, $m->lot, [
                    'entrees' => $qty,
                    'stock_theorique' => $qty,
                ]);
                break;

            case 'sortie_vente':
            case 'sortie_production':
            case 'mise_au_rebut':
                $this->adjust($m->article_id, $m->stock_location_id ?? $m->from_location_id, $m->lot, [
                    'sorties' => $qty,
                    'stock_theorique' => -$qty,
                ]);
                break;

            case 'transfert':
                $this->adjust($m->article_id, $m->from_location_id, $m->lot, [
                    'sorties' => $qty,
                    'stock_theorique' => -$qty,
                ]);
                $this->adjust($m->article_id, $m->to_location_id, $m->lot, [
                    'entrees' => $qty,
                    'stock_theorique' => $qty,
                ]);
                break;

            case 'ajustement':
            case 'inventaire':
                // signed via commentaire convention: positive = entrée, negative stored as positive with type
                $signed = isset($m->commentaire) && str_starts_with((string) ($m->getRawOriginal('commentaire') ?? ''), 'SIGNE:-')
                    ? -$qty
                    : $qty;
                // Prefer explicit signed_quantite in attributes via document
                $delta = (float) ($m->quantite);
                // For inventaire/ajustement we pass signed qty in quantite field as absolute and use document_ref sign
                // Caller should use applyAdjustment for clarity
                if ($delta >= 0) {
                    $this->adjust($m->article_id, $m->stock_location_id, $m->lot, [
                        'entrees' => $delta,
                        'stock_theorique' => $delta,
                    ]);
                }
                break;

            case 'reservation':
                $this->adjust($m->article_id, $m->stock_location_id, $m->lot, [
                    'stock_reserve' => $qty,
                ]);
                break;

            case 'liberation':
                $this->adjust($m->article_id, $m->stock_location_id, $m->lot, [
                    'stock_reserve' => -$qty,
                ]);
                break;
        }
    }

    /**
     * Apply a signed adjustment (can be negative).
     */
    public function applyAdjustment(
        int $articleId,
        float $delta,
        ?int $locationId,
        ?string $lot,
        string $type,
        array $meta = [],
        ?int $userId = null
    ): StockMouvement {
        return DB::transaction(function () use ($articleId, $delta, $locationId, $lot, $type, $meta, $userId) {
            $mouvement = StockMouvement::create([
                'date_mouvement' => $meta['date_mouvement'] ?? now()->toDateString(),
                'type' => $type,
                'article_id' => $articleId,
                'quantite' => abs($delta),
                'unite' => $meta['unite'] ?? null,
                'lot' => $lot,
                'stock_location_id' => $locationId,
                'document_type' => $meta['document_type'] ?? null,
                'document_ref' => $meta['document_ref'] ?? null,
                'document_id' => $meta['document_id'] ?? null,
                'user_id' => $userId,
                'commentaire' => ($delta < 0 ? 'SIGNE:-|' : '') . ($meta['commentaire'] ?? ''),
            ]);

            if ($delta > 0) {
                $this->adjust($articleId, $locationId, $lot, [
                    'entrees' => $delta,
                    'stock_theorique' => $delta,
                ]);
            } elseif ($delta < 0) {
                $this->adjust($articleId, $locationId, $lot, [
                    'sorties' => abs($delta),
                    'stock_theorique' => $delta,
                ]);
            }

            return $mouvement->fresh(['article', 'location', 'user']);
        });
    }

    public function adjust(
        int $articleId,
        ?int $locationId,
        ?string $lot,
        array $deltas,
        array $absolute = []
    ): StockBalance {
        // Empty string instead of null so UNIQUE works on MySQL
        $lotKey = trim((string) ($lot ?? ''));
        $locationId = $locationId ?: $this->defaultDepotId();

        if (!$locationId) {
            throw new InvalidArgumentException('Aucun emplacement de stock défini');
        }

        $balance = StockBalance::firstOrCreate(
            [
                'article_id' => $articleId,
                'stock_location_id' => $locationId,
                'lot' => $lotKey,
            ],
            [
                'stock_initial' => 0,
                'entrees' => 0,
                'sorties' => 0,
                'stock_theorique' => 0,
                'stock_reserve' => 0,
                'quarantaine' => 0,
                'endommage' => 0,
                'transit' => 0,
            ]
        );

        if (!$balance->wasRecentlyCreated) {
            $balance->refresh();
        }

        foreach ($deltas as $field => $value) {
            $balance->{$field} = (float) $balance->{$field} + (float) $value;
        }
        foreach ($absolute as $field => $value) {
            $balance->{$field} = $value;
        }

        // Clamp reserve
        if ((float) $balance->stock_reserve < 0) {
            $balance->stock_reserve = 0;
        }

        $balance->save();

        return $balance;
    }

    /**
     * Validate a bon de réception → entrée stock (cahier: stock disponible += qté reçue).
     */
    public function validerBonReception(BonReception $br, ?int $userId = null, bool $genererEntree = true): BonReception
    {
        if ($br->statut === 'valide') {
            return $br->load(['lignes.article', 'lignes.location', 'fournisseur', 'achat']);
        }

        return DB::transaction(function () use ($br, $userId, $genererEntree) {
            $br->load('lignes');

            if ($genererEntree && !$br->entree_stock_generee) {
                $defaultLocation = StockLocation::where('code', 'depot')->value('id')
                    ?? StockLocation::where('actif', true)->value('id');

                foreach ($br->lignes as $ligne) {
                    /** @var BonReceptionLigne $ligne */
                    if (!$ligne->article_id || (float) $ligne->quantite_recue <= 0) {
                        continue;
                    }
                    if ($ligne->controle_qualite === 'refuse') {
                        continue;
                    }

                    $locationId = $ligne->stock_location_id ?? $defaultLocation;
                    $bucket = [];

                    if ($ligne->controle_qualite === 'quarantaine') {
                        $quarantaineLoc = StockLocation::where('code', 'quarantaine')->value('id') ?? $locationId;
                        $locationId = $quarantaineLoc;
                        $this->move([
                            'type' => 'entree_achat',
                            'article_id' => $ligne->article_id,
                            'quantite' => $ligne->quantite_recue,
                            'unite' => $ligne->unite,
                            'lot' => $ligne->lot,
                            'stock_location_id' => $locationId,
                            'date_mouvement' => $br->date_reception?->toDateString() ?? now()->toDateString(),
                            'document_type' => 'BonReception',
                            'document_ref' => $br->numero,
                            'document_id' => $br->id,
                            'commentaire' => 'Réception — contrôle quarantaine',
                        ], $userId);
                        $this->adjust($ligne->article_id, $locationId, $ligne->lot, [
                            'quarantaine' => (float) $ligne->quantite_recue,
                        ]);
                        continue;
                    }

                    if ($ligne->controle_qualite === 'endommage') {
                        $endomLoc = StockLocation::where('code', 'endommage')->value('id') ?? $locationId;
                        $locationId = $endomLoc;
                        $this->move([
                            'type' => 'entree_achat',
                            'article_id' => $ligne->article_id,
                            'quantite' => $ligne->quantite_recue,
                            'unite' => $ligne->unite,
                            'lot' => $ligne->lot,
                            'stock_location_id' => $locationId,
                            'date_mouvement' => $br->date_reception?->toDateString() ?? now()->toDateString(),
                            'document_type' => 'BonReception',
                            'document_ref' => $br->numero,
                            'document_id' => $br->id,
                            'commentaire' => 'Réception — endommagé',
                        ], $userId);
                        $this->adjust($ligne->article_id, $locationId, $ligne->lot, [
                            'endommage' => (float) $ligne->quantite_recue,
                        ]);
                        continue;
                    }

                    $this->move([
                        'type' => 'entree_achat',
                        'article_id' => $ligne->article_id,
                        'quantite' => $ligne->quantite_recue,
                        'unite' => $ligne->unite,
                        'lot' => $ligne->lot,
                        'stock_location_id' => $locationId,
                        'date_mouvement' => $br->date_reception?->toDateString() ?? now()->toDateString(),
                        'document_type' => 'BonReception',
                        'document_ref' => $br->numero,
                        'document_id' => $br->id,
                        'commentaire' => $ligne->observations,
                    ], $userId);
                }

                $br->entree_stock_generee = true;
            }

            $br->statut = 'valide';
            $br->valide_at = now();
            $br->valide_by = $userId;
            $br->save();

            if ($br->achatReception) {
                $br->achatReception->update(['entree_stock_generee' => $br->entree_stock_generee, 'statut' => 'valide']);
            }

            return $br->fresh()->load(['lignes.article', 'lignes.location', 'fournisseur', 'achat', 'piecesJointes']);
        });
    }

    public function validerInventaire(Inventaire $inv, ?int $userId = null): Inventaire
    {
        if ($inv->statut === 'valide') {
            return $inv->load(['lignes.article', 'lignes.location', 'location']);
        }

        return DB::transaction(function () use ($inv, $userId) {
            $inv->load('lignes');

            foreach ($inv->lignes as $ligne) {
                if ($ligne->quantite_physique === null) {
                    continue;
                }
                $ecart = (float) $ligne->quantite_physique - (float) $ligne->quantite_theorique;
                $ligne->ecart = $ecart;
                $ligne->save();

                if ($ecart == 0.0) {
                    continue;
                }

                $this->applyAdjustment(
                    $ligne->article_id,
                    $ecart,
                    $ligne->stock_location_id ?? $inv->stock_location_id,
                    $ligne->lot,
                    'inventaire',
                    [
                        'date_mouvement' => $inv->date_inventaire?->toDateString() ?? now()->toDateString(),
                        'document_type' => 'Inventaire',
                        'document_ref' => $inv->numero,
                        'document_id' => $inv->id,
                        'commentaire' => "Écart inventaire {$inv->numero}",
                    ],
                    $userId
                );
            }

            $inv->update([
                'statut' => 'valide',
                'valide_at' => now(),
                'valide_by' => $userId,
            ]);

            return $inv->fresh()->load(['lignes.article', 'lignes.location', 'location']);
        });
    }

    public function defaultDepotId(): ?int
    {
        return StockLocation::where('code', 'depot')->value('id')
            ?? StockLocation::where('actif', true)->value('id');
    }
}
