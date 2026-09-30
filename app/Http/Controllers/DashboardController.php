<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BonLivraison;
use App\Models\Client;
use App\Models\DocumentGenere;
use App\Models\DossierEmballage;
use App\Models\Exportation;
use App\Models\ExportationArticle;
use App\Models\FactureFournisseur;
use App\Models\FactureLocale;
use App\Models\Fournisseur;
use App\Models\InventaireLigne;
use App\Models\StockBalance;
use App\Models\StockMouvement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function kpis(Request $request)
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

        $clientId = $request->integer('client_id') ?: null;
        $fournisseurId = $request->integer('fournisseur_id') ?: null;
        $filtreCommercial = trim((string) $request->get('commercial', '')) ?: null;
        $pays = trim((string) $request->get('pays', '')) ?: null;
        $devise = trim((string) $request->get('devise', '')) ?: null;
        $statut = trim((string) $request->get('statut', '')) ?: null;
        $articleId = $request->integer('article_id') ?: null;
        $typeVente = trim((string) $request->get('type_vente', '')) ?: null; // local|export|null

        $exportsQ = Exportation::query()
            ->when($from, fn ($q) => $q->whereDate('date_creation', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_creation', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->when($filtreCommercial, fn ($q) => $q->where('commercial', $filtreCommercial))
            ->when($pays, fn ($q) => $q->where('pays', $pays))
            ->when($devise, fn ($q) => $q->where('devise', $devise))
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->when($articleId, function ($q) use ($articleId) {
                $q->whereHas('lignes', fn ($l) => $l->where('article_id', $articleId));
            });

        // type_vente: only export exists today
        if ($typeVente === 'local') {
            $exportsQ->whereRaw('1 = 0');
        }

        $exportIds = (clone $exportsQ)->pluck('id');

        $dossiers = DossierEmballage::with('retours')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->get();

        $futsARetourner = $dossiers->filter(fn ($d) => $d->quantite_restante > 0)->count();
        $qteAReimporter = $dossiers->sum(fn ($d) => $d->quantite_restante);
        $dumNonSoldees = $dossiers->whereNotIn('statut', ['solde'])->count();

        $dumProches = DossierEmballage::with(['client', 'exportation'])
            ->where('statut', '!=', 'solde')
            ->whereNotNull('date_limite_reimportation')
            ->where('date_limite_reimportation', '<=', now()->addDays(90))
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->orderBy('date_limite_reimportation')
            ->limit(20)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'dum_52' => $d->dum_52,
                'client' => $d->client?->nom,
                'export' => $d->exportation?->numero,
                'echeance' => $d->date_limite_reimportation?->toDateString(),
                'restante' => $d->quantite_restante,
                'statut' => $d->statut,
                'alerte' => $d->alerte,
                'jours_restants' => $d->jours_restants,
            ]);

        $statutsPrep = ['commande', 'preparation', 'production', 'emballage', 'reservation'];
        $statutsChargement = ['chargement', 'documents'];

        $statutCounts = (clone $exportsQ)
            ->select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $caExport = (float) ExportationArticle::whereIn('exportation_id', $exportIds)->sum('montant');
        $caJour = $this->caExportForRange(now()->startOfDay(), now()->endOfDay(), $clientId, $filtreCommercial, $pays, $devise, $articleId);
        $caMois = $this->caExportForRange(now()->startOfMonth(), now()->endOfMonth(), $clientId, $filtreCommercial, $pays, $devise, $articleId);
        $caAnnee = $this->caExportForRange(now()->startOfYear(), now()->endOfYear(), $clientId, $filtreCommercial, $pays, $devise, $articleId);

        $enCours = (clone $exportsQ)->whereNotIn('statut', ['arrivee', 'cloturee'])->count();
        $enRetard = (clone $exportsQ)
            ->whereNotIn('statut', ['arrivee', 'cloturee'])
            ->whereNotNull('etd')
            ->whereDate('etd', '<', now())
            ->count();

        $ventesParClient = ExportationArticle::query()
            ->join('exportations', 'exportations.id', '=', 'exportation_articles.exportation_id')
            ->join('clients', 'clients.id', '=', 'exportations.client_id')
            ->when($from, fn ($q) => $q->whereDate('exportations.date_creation', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('exportations.date_creation', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('exportations.client_id', $clientId))
            ->when($filtreCommercial, fn ($q) => $q->where('exportations.commercial', $filtreCommercial))
            ->when($pays, fn ($q) => $q->where('exportations.pays', $pays))
            ->when($devise, fn ($q) => $q->where('exportations.devise', $devise))
            ->select('clients.nom as label', DB::raw('SUM(exportation_articles.montant) as total'))
            ->groupBy('clients.id', 'clients.nom')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $ventesParCommercial = ExportationArticle::query()
            ->join('exportations', 'exportations.id', '=', 'exportation_articles.exportation_id')
            ->when($from, fn ($q) => $q->whereDate('exportations.date_creation', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('exportations.date_creation', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('exportations.client_id', $clientId))
            ->when($pays, fn ($q) => $q->where('exportations.pays', $pays))
            ->when($devise, fn ($q) => $q->where('exportations.devise', $devise))
            ->whereNotNull('exportations.commercial')
            ->where('exportations.commercial', '!=', '')
            ->select('exportations.commercial as label', DB::raw('SUM(exportation_articles.montant) as total'))
            ->groupBy('exportations.commercial')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $ventesParPays = ExportationArticle::query()
            ->join('exportations', 'exportations.id', '=', 'exportation_articles.exportation_id')
            ->when($from, fn ($q) => $q->whereDate('exportations.date_creation', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('exportations.date_creation', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('exportations.client_id', $clientId))
            ->when($filtreCommercial, fn ($q) => $q->where('exportations.commercial', $filtreCommercial))
            ->when($devise, fn ($q) => $q->where('exportations.devise', $devise))
            ->whereNotNull('exportations.pays')
            ->where('exportations.pays', '!=', '')
            ->select('exportations.pays as label', DB::raw('SUM(exportation_articles.montant) as total'))
            ->groupBy('exportations.pays')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Finance — fournisseurs (réel) ; clients (stub jusqu'au module)
        $facturesImpayees = FactureFournisseur::query()
            ->whereIn('statut', ['validee', 'echeance'])
            ->when($fournisseurId, fn ($q) => $q->where('fournisseur_id', $fournisseurId))
            ->when($devise, fn ($q) => $q->where('devise', $devise));
        $dettesFournisseurs = (float) (clone $facturesImpayees)->sum('total_ttc');
        $facturesEchues = FactureFournisseur::query()
            ->where('statut', '!=', 'payee')
            ->whereNotNull('echeance')
            ->whereDate('echeance', '<', now())
            ->when($fournisseurId, fn ($q) => $q->where('fournisseur_id', $fournisseurId))
            ->when($devise, fn ($q) => $q->where('devise', $devise));
        $nbFacturesEchues = (clone $facturesEchues)->count();
        $montantFacturesEchues = (float) (clone $facturesEchues)->sum('total_ttc');
        $echeancesFournisseursProches = FactureFournisseur::query()
            ->where('statut', '!=', 'payee')
            ->whereNotNull('echeance')
            ->whereDate('echeance', '>=', now())
            ->whereDate('echeance', '<=', now()->addDays(30))
            ->when($fournisseurId, fn ($q) => $q->where('fournisseur_id', $fournisseurId))
            ->count();

        // Stock
        $stockRow = StockBalance::query()
            ->selectRaw('
                COALESCE(SUM(stock_theorique),0) as stock_theorique,
                COALESCE(SUM(stock_reserve),0) as stock_reserve,
                COALESCE(SUM(quarantaine),0) as quarantaine,
                COALESCE(SUM(endommage),0) as endommage,
                COALESCE(SUM(transit),0) as transit,
                COALESCE(SUM(entrees),0) as entrees,
                COALESCE(SUM(sorties),0) as sorties
            ')
            ->when($articleId, fn ($q) => $q->where('article_id', $articleId))
            ->first();

        $theorique = (float) ($stockRow->stock_theorique ?? 0);
        $reserve = (float) ($stockRow->stock_reserve ?? 0);

        $mouvementsPeriode = StockMouvement::query()
            ->where('annule', false)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($articleId, fn ($q) => $q->where('article_id', $articleId));

        $entreesPeriode = (float) (clone $mouvementsPeriode)->whereIn('type', ['entree_achat', 'entree_production', 'retour'])->sum('quantite');
        $sortiesPeriode = (float) (clone $mouvementsPeriode)->whereIn('type', ['sortie_vente', 'sortie_production', 'mise_au_rebut'])->sum('quantite');
        $retoursPeriode = (float) (clone $mouvementsPeriode)->where('type', 'retour')->sum('quantite');

        $ecartsInventaire = (float) InventaireLigne::query()
            ->where('ecart', '!=', 0)
            ->when($articleId, fn ($q) => $q->where('article_id', $articleId))
            ->sum(DB::raw('ABS(ecart)'));

        $rupture = StockBalance::query()
            ->when($articleId, fn ($q) => $q->where('article_id', $articleId))
            ->whereRaw('(stock_theorique - stock_reserve) <= 0')
            ->where(function ($q) {
                $q->where('stock_theorique', '!=', 0)
                    ->orWhere('stock_reserve', '!=', 0)
                    ->orWhere('entrees', '!=', 0);
            })
            ->select('article_id')
            ->distinct()
            ->count();

        // Documents manquants (packing list + facture commerciale sur exports actifs)
        $docsRequis = ['facture_commerciale', 'packing_list'];
        $exportsActifs = (clone $exportsQ)->whereNotIn('statut', ['cloturee'])->pluck('id');
        $docsManquants = 0;
        if ($exportsActifs->isNotEmpty()) {
            $present = DocumentGenere::whereIn('exportation_id', $exportsActifs)
                ->whereIn('type', $docsRequis)
                ->select('exportation_id', 'type')
                ->distinct()
                ->get()
                ->groupBy('exportation_id');
            foreach ($exportsActifs as $eid) {
                $types = ($present[$eid] ?? collect())->pluck('type')->unique();
                foreach ($docsRequis as $t) {
                    if (!$types->contains($t)) {
                        $docsManquants++;
                    }
                }
            }
        }

        $conteneursEnCours = (clone $exportsQ)
            ->whereIn('statut', ['chargement', 'documents', 'expedition'])
            ->whereNotNull('conteneur')
            ->where('conteneur', '!=', '')
            ->count();

        $qteProduite = (float) ExportationArticle::whereIn(
            'exportation_id',
            (clone $exportsQ)->whereIn('statut', ['emballage', 'reservation', 'chargement', 'documents', 'expedition', 'arrivee', 'cloturee'])->pluck('id')
        )->sum('quantite');

        $qteAProduire = (float) ExportationArticle::whereIn(
            'exportation_id',
            (clone $exportsQ)->whereIn('statut', ['commande', 'preparation', 'production'])->pluck('id')
        )->sum('quantite');

        $blNonFactures = BonLivraison::whereDoesntHave('factureLocale')
            ->where('statut', '!=', 'annule')
            ->count();

        $caLocales = (float) FactureLocale::query()
            ->when($from, fn ($q) => $q->whereDate('date_facture', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_facture', '<=', $to))
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->whereIn('statut', ['validee', 'payee', 'brouillon'])
            ->sum('total_ttc');

        $facturesLocalesNonReglees = FactureLocale::whereIn('statut', ['brouillon', 'validee'])->count();

        $commercial = [
            'ca_jour' => round($caJour, 2),
            'ca_mois' => round($caMois, 2),
            'ca_annee' => round($caAnnee, 2),
            'ca_periode' => round($caExport + $caLocales, 2),
            'ventes_export' => round($caExport, 2),
            'ventes_locales' => round($caLocales, 2),
            'ventes_locales_disponible' => true,
            'commandes_en_cours' => $enCours,
            'commandes_en_retard' => $enRetard,
            'commandes_partiellement_livrees' => 0,
            'commandes_partiellement_livrees_disponible' => false,
            'bl_non_factures' => $blNonFactures,
            'bl_non_factures_disponible' => true,
            'factures_non_reglees' => $facturesLocalesNonReglees,
            'factures_non_reglees_disponible' => true,
            'par_client' => $ventesParClient,
            'par_commercial' => $ventesParCommercial,
            'par_pays' => $ventesParPays,
        ];

        $financier = [
            'creances_clients' => 0,
            'creances_clients_disponible' => false,
            'dettes_fournisseurs' => round($dettesFournisseurs, 2),
            'reglements_a_recevoir' => 0,
            'reglements_a_recevoir_disponible' => false,
            'reglements_a_effectuer' => round($dettesFournisseurs, 2),
            'factures_echues' => $nbFacturesEchues,
            'factures_echues_montant' => round($montantFacturesEchues, 2),
            'factures_partiellement_reglees' => 0,
            'factures_partiellement_reglees_disponible' => false,
            'notes_credit_attente' => 0,
            'notes_credit_attente_disponible' => false,
            'solde_bancaire' => 0,
            'solde_bancaire_disponible' => false,
            'encaissements' => 0,
            'encaissements_disponible' => false,
            'decaissements' => 0,
            'decaissements_disponible' => false,
            'tresorerie_previsionnelle' => 0,
            'tresorerie_previsionnelle_disponible' => false,
        ];

        $stock = [
            'total' => round($theorique, 3),
            'disponible' => round($theorique - $reserve, 3),
            'reserve' => round($reserve, 3),
            'sous_seuil' => 0,
            'sous_seuil_disponible' => false,
            'en_rupture' => $rupture,
            'rotation_lente' => 0,
            'rotation_lente_disponible' => false,
            'entrees' => round($entreesPeriode, 3),
            'sorties' => round($sortiesPeriode, 3),
            'retours' => round($retoursPeriode, 3),
            'ecarts_inventaire' => round($ecartsInventaire, 3),
            'quarantaine' => round((float) ($stockRow->quarantaine ?? 0), 3),
            'endommage' => round((float) ($stockRow->endommage ?? 0), 3),
            'transit' => round((float) ($stockRow->transit ?? 0), 3),
        ];

        $production = [
            'ordres_a_planifier' => (int) ($statutCounts['commande'] ?? 0) + (int) ($statutCounts['preparation'] ?? 0),
            'ordres_en_cours' => (int) ($statutCounts['production'] ?? 0) + (int) ($statutCounts['emballage'] ?? 0),
            'ordres_en_retard' => $enRetard,
            'qte_a_produire' => round($qteAProduire, 3),
            'qte_produite' => round($qteProduite, 3),
            'commandes_bloquees_stock' => 0,
            'commandes_bloquees_stock_disponible' => false,
            'ordres_termines' => (int) ($statutCounts['cloturee'] ?? 0) + (int) ($statutCounts['arrivee'] ?? 0),
            'source' => 'exportations',
        ];

        $export = [
            'en_preparation' => (clone $exportsQ)->whereIn('statut', $statutsPrep)->count(),
            'pretes' => (clone $exportsQ)->whereIn('statut', $statutsChargement)->count(),
            'chargees' => (clone $exportsQ)->where('statut', 'chargement')->whereNotNull('conteneur')->where('conteneur', '!=', '')->count(),
            'expediees' => (int) ($statutCounts['expedition'] ?? 0),
            'conteneurs_en_cours' => $conteneursEnCours,
            'documents_manquants' => $docsManquants,
            'dum_52_non_soldees' => $dumNonSoldees,
            'emballages_a_retourner' => $futsARetourner,
            'qte_a_reimporter' => round($qteAReimporter, 2),
            'echeances_douanieres_proches' => $dumProches->count(),
            'arrivees' => (int) ($statutCounts['arrivee'] ?? 0),
            'cloturees' => (int) ($statutCounts['cloturee'] ?? 0),
        ];

        $alertes = [
            ['key' => 'echeances_fournisseurs', 'label' => 'Échéances fournisseurs (30 j)', 'count' => $echeancesFournisseursProches, 'severity' => $echeancesFournisseursProches > 0 ? 'amber' : 'ok', 'path' => '/achats/factures', 'disponible' => true],
            ['key' => 'echeances_clients', 'label' => 'Échéances clients', 'count' => 0, 'severity' => 'muted', 'path' => '/finance/echeances', 'disponible' => false],
            ['key' => 'factures_impayees', 'label' => 'Factures fournisseurs impayées', 'count' => (clone $facturesImpayees)->count(), 'severity' => 'red', 'path' => '/achats/factures', 'disponible' => true],
            ['key' => 'stock_minimum', 'label' => 'Stock minimum', 'count' => 0, 'severity' => 'muted', 'path' => '/stock/situation', 'disponible' => false],
            ['key' => 'commandes_retard', 'label' => 'Commandes / exports en retard', 'count' => $enRetard, 'severity' => $enRetard > 0 ? 'red' : 'ok', 'path' => '/ventes/export/colisage', 'disponible' => true],
            ['key' => 'docs_export', 'label' => 'Documents export manquants', 'count' => $docsManquants, 'severity' => $docsManquants > 0 ? 'amber' : 'ok', 'path' => '/ventes/export/documents', 'disponible' => true],
            ['key' => 'reclamations', 'label' => 'Réclamations ouvertes', 'count' => 0, 'severity' => 'muted', 'path' => '/ventes/reclamations', 'disponible' => false],
            ['key' => 'notes_credit', 'label' => 'Notes de crédit à traiter', 'count' => 0, 'severity' => 'muted', 'path' => '/ventes/notes-credit', 'disponible' => false],
            ['key' => 'dum_echeance', 'label' => 'DUM 52 arrivant à échéance', 'count' => $dumProches->count(), 'severity' => $dumProches->count() > 0 ? 'amber' : 'ok', 'path' => '/ventes/export/emballages', 'disponible' => true],
            ['key' => 'emballages', 'label' => 'Emballages temporaires non retournés', 'count' => $futsARetourner, 'severity' => $futsARetourner > 0 ? 'red' : 'ok', 'path' => '/ventes/export/emballages', 'disponible' => true],
            ['key' => 'bl_non_factures', 'label' => 'Bons de livraison non facturés', 'count' => $blNonFactures, 'severity' => $blNonFactures > 0 ? 'amber' : 'ok', 'path' => '/ventes/locales/bons-livraison', 'disponible' => true],
            ['key' => 'dum_depassement', 'label' => 'DUM en dépassement', 'count' => $dossiers->where('statut', 'en_depassement')->count(), 'severity' => 'red', 'path' => '/ventes/export/emballages', 'disponible' => true],
        ];

        return response()->json([
            'period' => [
                'key' => $period,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'filters_meta' => [
                'clients' => Client::where('statut', 'client')->orderBy('nom')->get(['id', 'nom', 'pays', 'devise', 'commercial_charge']),
                'fournisseurs' => Fournisseur::orderBy('nom')->get(['id', 'nom', 'pays', 'devise']),
                'articles' => Article::where('actif', true)->orderBy('code_article')->limit(500)->get(['id', 'code_article', 'designation']),
                'commerciaux' => Exportation::whereNotNull('commercial')->where('commercial', '!=', '')->distinct()->orderBy('commercial')->pluck('commercial'),
                'pays' => Exportation::whereNotNull('pays')->where('pays', '!=', '')->distinct()->orderBy('pays')->pluck('pays'),
                'devises' => collect()
                    ->merge(Exportation::whereNotNull('devise')->distinct()->pluck('devise'))
                    ->merge(FactureFournisseur::whereNotNull('devise')->distinct()->pluck('devise'))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),
                'statuts_export' => Exportation::STATUTS,
                'types_vente' => [
                    ['value' => '', 'label' => 'Tous'],
                    ['value' => 'export', 'label' => 'Export'],
                    ['value' => 'local', 'label' => 'Local (à venir)'],
                ],
                'periodes' => [
                    ['value' => 'aujourdhui', 'label' => "Aujourd'hui"],
                    ['value' => 'semaine', 'label' => 'Semaine'],
                    ['value' => 'mois', 'label' => 'Mois'],
                    ['value' => 'trimestre', 'label' => 'Trimestre'],
                    ['value' => 'annee', 'label' => 'Année'],
                    ['value' => 'perso', 'label' => 'Période personnalisée'],
                ],
            ],
            'commercial' => $commercial,
            'financier' => $financier,
            'stock' => $stock,
            'production' => $production,
            'export' => $export,
            'alertes' => $alertes,
            'dum_proches' => $dumProches,
            'statuts' => $statutCounts,
            // rétrocompat anciennes clés KPI export
            'kpis' => $export,
            'alertes_dum' => [
                'partiellement_soldee' => $dossiers->where('statut', 'partiellement_solde')->count(),
                'futs_non_retournes' => $futsARetourner,
                'en_depassement' => $dossiers->where('statut', 'en_depassement')->count(),
                'dum_soldee' => $dossiers->where('statut', 'solde')->count(),
            ],
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'mois');
        $now = now();

        return match ($period) {
            'aujourdhui' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), $period],
            'semaine' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), $period],
            'trimestre' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter(), $period],
            'annee' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), $period],
            'perso' => [
                $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : null,
                $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : null,
                $period,
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'mois'],
        };
    }

    private function caExportForRange($from, $to, $clientId, $commercial, $pays, $devise, $articleId): float
    {
        $q = ExportationArticle::query()
            ->join('exportations', 'exportations.id', '=', 'exportation_articles.exportation_id')
            ->whereDate('exportations.date_creation', '>=', $from)
            ->whereDate('exportations.date_creation', '<=', $to)
            ->when($clientId, fn ($q) => $q->where('exportations.client_id', $clientId))
            ->when($commercial, fn ($q) => $q->where('exportations.commercial', $commercial))
            ->when($pays, fn ($q) => $q->where('exportations.pays', $pays))
            ->when($devise, fn ($q) => $q->where('exportations.devise', $devise))
            ->when($articleId, fn ($q) => $q->where('exportation_articles.article_id', $articleId));

        return (float) $q->sum('exportation_articles.montant');
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = "%{$q}%";
        $results = [];

        foreach (Exportation::with('client')->where(function ($query) use ($like) {
            $query->where('numero', 'like', $like)
                ->orWhere('conteneur', 'like', $like)
                ->orWhere('booking', 'like', $like)
                ->orWhere('reference_commande_client', 'like', $like);
        })->limit(10)->get() as $e) {
            $results[] = [
                'type' => 'exportation',
                'id' => $e->id,
                'label' => $e->numero,
                'meta' => trim(($e->client?->nom ?? '') . ' · ' . ($e->conteneur ?? '') . ' · ' . $e->statut),
                'path' => '/exportations/' . $e->id,
            ];
        }

        foreach (Client::where('nom', 'like', $like)->orWhere('code_client', 'like', $like)->limit(8)->get() as $c) {
            $results[] = [
                'type' => 'client',
                'id' => $c->id,
                'label' => $c->nom,
                'meta' => ($c->code_client ?? '') . ' · ' . ($c->pays ?? ''),
                'path' => '/clients-prospects',
            ];
        }

        foreach (Article::where('code_article', 'like', $like)->orWhere('designation', 'like', $like)->limit(8)->get() as $a) {
            $results[] = [
                'type' => 'article',
                'id' => $a->id,
                'label' => $a->code_article . ' — ' . $a->designation,
                'meta' => $a->hs_code,
                'path' => '/articles',
            ];
        }

        foreach (DossierEmballage::with('client')->where(function ($query) use ($like) {
            $query->where('dum_52', 'like', $like)
                ->orWhere('reference_fut', 'like', $like)
                ->orWhere('conteneur', 'like', $like);
        })->limit(10)->get() as $d) {
            $results[] = [
                'type' => 'dum52',
                'id' => $d->id,
                'label' => $d->dum_52 ?: ('Dossier #' . $d->id),
                'meta' => ($d->client?->nom ?? '') . ' · solde ' . $d->quantite_restante,
                'path' => '/emballages',
            ];
        }

        foreach (DocumentGenere::where('titre', 'like', $like)->orWhere('type', 'like', $like)->limit(5)->get() as $doc) {
            $results[] = [
                'type' => 'document',
                'id' => $doc->id,
                'label' => $doc->titre,
                'meta' => $doc->type . ' v' . $doc->version,
                'path' => $doc->exportation_id ? '/exportations/' . $doc->exportation_id : '/emballages',
            ];
        }

        return response()->json(['results' => $results]);
    }
}
