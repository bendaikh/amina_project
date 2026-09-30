<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\NoteCredit;
use App\Models\Reclamation;
use Illuminate\Http\Request;

class SuiviVentesController extends Controller
{
    public function index(Request $request)
    {
        $query = Commande::with([
            'client',
            'lignes',
            'productions',
            'livraisons',
        ])->orderByDesc('date_commande')->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('commercial', 'like', "%{$search}%")
                    ->orWhere('reference_client', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        $paginator = $query->paginate($request->get('per_page', 30));

        $commandeIds = collect($paginator->items())->pluck('id')->all();

        $reclamationsByCommande = Reclamation::whereIn('commande_id', $commandeIds)
            ->get()
            ->groupBy('commande_id');

        $notesByCommande = NoteCredit::whereIn('commande_id', $commandeIds)
            ->get()
            ->groupBy('commande_id');

        $rows = collect($paginator->items())->map(function (Commande $commande) use ($reclamationsByCommande, $notesByCommande) {
            $qteCommandee = (float) $commande->lignes->sum('quantite');
            $qteProduite = (float) $commande->productions->sum('quantite_produite');
            $qtePreparee = (float) $commande->livraisons->sum('quantite_preparee');
            $qteChargee = (float) $commande->livraisons->sum('quantite_chargee');
            $qteLivree = (float) $commande->quantite_livree;
            $qteExpediee = (float) $commande->livraisons
                ->whereIn('statut', ['expedie', 'livre'])
                ->sum(fn ($l) => max((float) $l->quantite_chargee, (float) $l->quantite_preparee));

            if ($qteExpediee <= 0 && in_array($commande->statut, ['livree', 'cloturee'], true)) {
                $qteExpediee = $qteLivree;
            }

            $qteRestante = max(0, round($qteCommandee - $qteLivree, 3));

            $recs = $reclamationsByCommande->get($commande->id, collect());
            $notes = $notesByCommande->get($commande->id, collect());

            $facturation = $this->facturationStatus($commande);
            $reglement = $this->reglementStatus($commande);
            $docsManquants = $this->docsManquants($commande, $qteCommandee, $qteLivree, $facturation);

            return [
                'id' => $commande->id,
                'numero' => $commande->numero,
                'date_commande' => $commande->date_commande?->toDateString(),
                'client' => $commande->client,
                'type' => $commande->type,
                'commercial' => $commande->commercial,
                'montant' => (float) $commande->total_ttc,
                'devise' => $commande->devise,
                'quantites' => [
                    'commandee' => $qteCommandee,
                    'produite' => $qteProduite,
                    'preparee' => $qtePreparee,
                    'livree' => $qteLivree,
                    'expediee' => $qteExpediee,
                    'restante' => $qteRestante,
                ],
                'facturation' => $facturation,
                'reglement' => $reglement,
                'reclamation' => [
                    'count' => $recs->count(),
                    'ouverts' => $recs->whereNotIn('statut', ['resolue', 'cloturee', 'rejetee'])->count(),
                    'label' => $recs->isEmpty()
                        ? 'Aucune'
                        : ($recs->whereNotIn('statut', ['resolue', 'cloturee', 'rejetee'])->count()
                            ? $recs->whereNotIn('statut', ['resolue', 'cloturee', 'rejetee'])->count() . ' ouverte(s)'
                            : $recs->count() . ' clôturée(s)'),
                ],
                'note_credit' => [
                    'count' => $notes->count(),
                    'montant' => round((float) $notes->sum('total_ttc'), 2),
                    'label' => $notes->isEmpty()
                        ? 'Aucune'
                        : $notes->count() . ' · ' . number_format((float) $notes->sum('total_ttc'), 2, ',', ' ') . ' ' . $commande->devise,
                ],
                'statut' => $commande->statut,
                'etat_export' => $this->etatExport($commande),
                'docs_manquants' => $docsManquants,
            ];
        });

        return response()->json([
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function meta()
    {
        return response()->json([
            'statuts' => Commande::STATUTS,
            'types' => Commande::TYPES,
            'etats_export' => [
                'brouillon' => 'Brouillon',
                'confirmee' => 'Commande confirmée',
                'en_preparation' => 'En préparation',
                'en_production' => 'En production',
                'pret_chargement' => 'Prêt au chargement',
                'charge' => 'Chargé',
                'expedie' => 'Expédié',
                'arrive' => 'Arrivé',
                'cloture' => 'Clôturé',
            ],
            'labels' => [
                'statuts' => [
                    'brouillon' => 'Brouillon',
                    'en_attente' => 'En attente',
                    'confirmee' => 'Confirmée',
                    'en_preparation' => 'En préparation',
                    'en_production' => 'En production',
                    'partiellement_livree' => 'Partiellement livrée',
                    'livree' => 'Livrée',
                    'cloturee' => 'Clôturée',
                ],
            ],
        ]);
    }

    private function facturationStatus(Commande $commande): array
    {
        $facture = (float) $commande->montant_facture;
        $ttc = (float) $commande->total_ttc;

        if ($facture <= 0) {
            return ['code' => 'non', 'label' => 'Non facturée'];
        }
        if ($ttc > 0 && $facture + 0.01 < $ttc) {
            return ['code' => 'partielle', 'label' => 'Partielle'];
        }

        return ['code' => 'complete', 'label' => 'Facturée'];
    }

    private function reglementStatus(Commande $commande): array
    {
        $regle = (float) $commande->montant_regle;
        $facture = (float) $commande->montant_facture;

        if ($facture <= 0 && $regle <= 0) {
            return ['code' => 'na', 'label' => '—'];
        }
        if ($regle <= 0) {
            return ['code' => 'impaye', 'label' => 'Impayé'];
        }
        if ($regle + 0.01 < $facture) {
            return ['code' => 'partiel', 'label' => 'Partiel'];
        }

        return ['code' => 'solde', 'label' => 'Soldé'];
    }

    private function etatExport(Commande $commande): string
    {
        if ($commande->type !== 'export') {
            return $commande->statut;
        }

        $map = [
            'brouillon' => 'brouillon',
            'en_attente' => 'brouillon',
            'confirmee' => 'confirmee',
            'en_preparation' => 'en_preparation',
            'en_production' => 'en_production',
            'partiellement_livree' => 'charge',
            'livree' => 'expedie',
            'cloturee' => 'cloture',
        ];

        $liv = $commande->livraisons;
        if ($liv->contains(fn ($l) => in_array($l->statut, ['expedie', 'livre'], true))) {
            return $commande->statut === 'cloturee' ? 'cloture' : 'expedie';
        }
        if ($liv->contains(fn ($l) => $l->statut === 'charge')) {
            return 'charge';
        }
        if ($liv->contains(fn ($l) => in_array($l->statut, ['pret', 'en_preparation'], true))) {
            return 'pret_chargement';
        }

        return $map[$commande->statut] ?? $commande->statut;
    }

    private function docsManquants(Commande $commande, float $qteCommandee, float $qteLivree, array $facturation): array
    {
        $missing = [];

        if ($qteCommandee > 0 && $qteLivree <= 0) {
            $missing[] = 'BL';
        }
        if ($facturation['code'] === 'non') {
            $missing[] = 'Facture';
        }
        if ($commande->type === 'export' && $commande->livraisons->isEmpty()) {
            $missing[] = 'Docs export';
        }

        return $missing;
    }
}
