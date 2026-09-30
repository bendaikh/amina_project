<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BonLivraison;
use App\Models\Client;
use App\Models\Commande;
use App\Models\FactureLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FactureLocaleController extends Controller
{
    public function index(Request $request)
    {
        $query = FactureLocale::with(['client', 'commande', 'bonLivraison', 'lignes'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('reference_client', 'like', "%{$search}%")
                    ->orWhere('numero_bl', 'like', "%{$search}%")
                    ->orWhere('numero_commande', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => FactureLocale::STATUTS,
            'labels' => [
                'brouillon' => 'Brouillon',
                'validee' => 'Validée',
                'payee' => 'Payée',
                'annulee' => 'Annulée',
            ],
        ]);
    }

    public function show(FactureLocale $factureLocale)
    {
        $factureLocale->load(['client', 'commande', 'bonLivraison', 'lignes.article']);

        return response()->json($factureLocale);
    }

    public function store(Request $request)
    {
        $data = $this->validateFacture($request);
        $lignes = $request->input('lignes', []);

        $facture = DB::transaction(function () use ($data, $lignes, $request) {
            $data['numero'] = $data['numero'] ?? FactureLocale::nextNumero();
            $data['date_facture'] = $data['date_facture'] ?? now()->toDateString();
            $data['statut'] = $data['statut'] ?? 'brouillon';
            $data['devise'] = $data['devise'] ?? 'MAD';
            $data['created_by'] = $request->user()?->id;

            if (!empty($data['client_id'])) {
                $client = Client::find($data['client_id']);
                if ($client) {
                    $data['reference_client'] = $data['reference_client'] ?? null;
                    $data['devise'] = $data['devise'] ?: ($client->devise ?: 'MAD');
                    if (empty($data['conditions_paiement'])) {
                        $cond = trim(($client->delai_paiement ?? '') . ' ' . ($client->delai_paiement_type ?? ''));
                        $data['conditions_paiement'] = $cond ?: null;
                    }
                }
            }

            if (!empty($data['commande_id'])) {
                $cmd = Commande::find($data['commande_id']);
                if ($cmd) {
                    $data['client_id'] = $data['client_id'] ?? $cmd->client_id;
                    $data['reference_client'] = $data['reference_client'] ?? $cmd->reference_client;
                    $data['numero_commande'] = $data['numero_commande'] ?? $cmd->numero;
                    $data['devise'] = $data['devise'] ?: ($cmd->devise ?: 'MAD');
                    $data['mode_paiement'] = $data['mode_paiement'] ?? $cmd->mode_paiement;
                }
            }

            $facture = FactureLocale::create($data);
            $this->syncLignes($facture, $lignes);
            $facture->recalculateTotals();

            AuditLog::record($facture, 'create', null, null, $facture->numero, $request->user()?->id);

            return $facture;
        });

        return response()->json(
            $facture->load(['client', 'commande', 'bonLivraison', 'lignes.article']),
            201
        );
    }

    public function update(Request $request, FactureLocale $factureLocale)
    {
        $data = $this->validateFacture($request, false);
        $lignes = $request->input('lignes');

        DB::transaction(function () use ($factureLocale, $data, $lignes, $request) {
            $factureLocale->update($data);

            if (is_array($lignes)) {
                $factureLocale->lignes()->delete();
                $this->syncLignes($factureLocale, $lignes);
                $factureLocale->recalculateTotals();
            }

            AuditLog::record($factureLocale, 'update', null, null, $factureLocale->numero, $request->user()?->id);
        });

        return response()->json(
            $factureLocale->fresh()->load(['client', 'commande', 'bonLivraison', 'lignes.article'])
        );
    }

    public function destroy(FactureLocale $factureLocale)
    {
        $factureLocale->delete();

        return response()->json(['message' => 'Facture locale supprimée']);
    }

    /**
     * Crée une facture locale depuis un bon de livraison.
     */
    public function fromBonLivraison(Request $request, BonLivraison $bonLivraison)
    {
        $bonLivraison->load(['client', 'commande.lignes.article', 'lignes.article', 'lignes.commandeLigne.article']);

        if ($existing = FactureLocale::where('bon_livraison_id', $bonLivraison->id)->first()) {
            return response()->json([
                'message' => 'Ce bon de livraison a déjà une facture locale',
                'facture' => $existing->load(['client', 'commande', 'bonLivraison', 'lignes.article']),
            ], 422);
        }

        if ($bonLivraison->lignes->isEmpty()) {
            return response()->json(['message' => 'Ce bon de livraison ne contient aucune ligne'], 422);
        }

        $facture = DB::transaction(function () use ($bonLivraison, $request) {
            $client = $bonLivraison->client;
            $commande = $bonLivraison->commande;

            $conditions = null;
            if ($client) {
                $conditions = trim(($client->delai_paiement ?? '') . ' ' . ($client->delai_paiement_type ?? '')) ?: null;
            }

            $echeance = null;
            if ($client?->delai_paiement) {
                $echeance = now()->addDays((int) $client->delai_paiement)->toDateString();
            }

            $facture = FactureLocale::create([
                'numero' => FactureLocale::nextNumero(),
                'date_facture' => now()->toDateString(),
                'client_id' => $bonLivraison->client_id,
                'commande_id' => $bonLivraison->commande_id,
                'bon_livraison_id' => $bonLivraison->id,
                'reference_client' => $bonLivraison->reference_client,
                'numero_bl' => $bonLivraison->numero,
                'numero_commande' => $commande?->numero,
                'devise' => $commande?->devise ?: ($client?->devise ?: 'MAD'),
                'conditions_paiement' => $conditions,
                'mode_paiement' => $commande?->mode_paiement,
                'echeance' => $echeance,
                'statut' => 'brouillon',
                'observations' => $bonLivraison->informations_additionnelles,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($bonLivraison->lignes->values() as $i => $ligne) {
                $payload = $this->ligneFromBlLigne($ligne, $i);
                $amounts = FactureLocale::computeLineAmounts($payload);
                $facture->lignes()->create(array_merge($payload, $amounts));
            }

            $facture->recalculateTotals();

            if ($commande && (float) $commande->montant_facture <= 0) {
                $commande->update(['montant_facture' => $facture->total_ttc]);
            }

            AuditLog::record($facture, 'create_from_bl', null, null, $facture->numero, $request->user()?->id);
            AuditLog::record($bonLivraison, 'to_facture_locale', null, null, $facture->numero, $request->user()?->id);

            return $facture;
        });

        return response()->json(
            $facture->load(['client', 'commande', 'bonLivraison', 'lignes.article']),
            201
        );
    }

    private function validateFacture(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'date_facture' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'commande_id' => 'nullable|exists:commandes,id',
            'bon_livraison_id' => 'nullable|exists:bons_livraison,id',
            'reference_client' => 'nullable|string|max:150',
            'numero_bl' => 'nullable|string|max:100',
            'numero_commande' => 'nullable|string|max:100',
            'devise' => 'nullable|string|max:10',
            'conditions_paiement' => 'nullable|string|max:255',
            'mode_paiement' => 'nullable|string|max:100',
            'echeance' => 'nullable|date',
            'statut' => 'nullable|in:' . implode(',', FactureLocale::STATUTS),
            'observations' => 'nullable|string',
            'lignes' => 'nullable|array',
            'lignes.*.article_id' => 'nullable|exists:articles,id',
            'lignes.*.ref_article' => 'nullable|string|max:100',
            'lignes.*.designation' => 'nullable|string|max:255',
            'lignes.*.calibre' => 'nullable|string|max:100',
            'lignes.*.emballage' => 'nullable|string|max:255',
            'lignes.*.numero_lot' => 'nullable|string|max:100',
            'lignes.*.quantite' => 'nullable|numeric',
            'lignes.*.unite' => 'nullable|string|max:50',
            'lignes.*.prix_unitaire' => 'nullable|numeric',
            'lignes.*.tva_taux' => 'nullable|numeric',
        ]);
    }

    private function syncLignes(FactureLocale $facture, array $lignes): void
    {
        foreach (array_values($lignes) as $i => $ligne) {
            if (
                empty($ligne['article_id'])
                && empty($ligne['ref_article'])
                && empty($ligne['designation'])
            ) {
                continue;
            }

            $payload = [
                'article_id' => $ligne['article_id'] ?? null,
                'bon_livraison_ligne_id' => $ligne['bon_livraison_ligne_id'] ?? null,
                'commande_ligne_id' => $ligne['commande_ligne_id'] ?? null,
                'ref_article' => $ligne['ref_article'] ?? null,
                'designation' => $ligne['designation'] ?? null,
                'calibre' => $ligne['calibre'] ?? null,
                'emballage' => $ligne['emballage'] ?? null,
                'numero_lot' => $ligne['numero_lot'] ?? null,
                'quantite' => $ligne['quantite'] ?? 0,
                'unite' => $ligne['unite'] ?? null,
                'prix_unitaire' => $ligne['prix_unitaire'] ?? 0,
                'tva_taux' => $ligne['tva_taux'] ?? 20,
                'ordre' => $ligne['ordre'] ?? $i,
            ];

            $amounts = FactureLocale::computeLineAmounts($payload);
            $facture->lignes()->create(array_merge($payload, $amounts));
        }
    }

    private function ligneFromBlLigne($ligne, int $ordre): array
    {
        $article = $ligne->article;
        $cmdLigne = $ligne->commandeLigne;

        $prix = (float) ($cmdLigne?->prix ?? $article?->prix_vente ?? 0);
        $tva = (float) ($cmdLigne?->tva_taux ?? $article?->taux_tva ?? 20);
        $qty = (float) ($ligne->total_poids_net ?: 0);
        if ($qty <= 0) {
            $qty = (float) ($cmdLigne?->quantite ?? $ligne->total_colis ?: 1);
        }

        return [
            'article_id' => $ligne->article_id,
            'bon_livraison_ligne_id' => $ligne->id,
            'commande_ligne_id' => $ligne->commande_ligne_id,
            'ref_article' => $ligne->ref_article ?: $article?->code_article,
            'designation' => $ligne->designation ?: $article?->designation,
            'calibre' => $ligne->calibre ?: $article?->calibre,
            'emballage' => $ligne->emballage,
            'numero_lot' => $ligne->numero_lot ?: $article?->lot,
            'quantite' => $qty,
            'unite' => $cmdLigne?->unite ?: 'kg',
            'prix_unitaire' => $prix,
            'tva_taux' => $tva > 0 ? $tva : 20,
            'ordre' => $ordre,
        ];
    }
}
