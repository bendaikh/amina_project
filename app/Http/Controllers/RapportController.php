<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\StockLocation;
use App\Services\FinanceService;
use App\Services\ReportService;
use App\Services\TabularExport;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private TabularExport $export,
        private FinanceService $finance,
    ) {
    }

    public function meta()
    {
        return response()->json([
            'modes' => $this->finance->modes(),
            'devises' => $this->finance->devises(),
            'taux_tva' => $this->finance->tauxTva(),
            'societe' => $this->finance->company(),
            'clients' => Client::orderBy('nom')->get(['id', 'nom']),
            'fournisseurs' => Fournisseur::orderBy('nom')->get(['id', 'nom']),
            'articles' => Article::orderBy('designation')->get(['id', 'code_article', 'designation', 'famille']),
            'categories' => Article::query()->whereNotNull('famille')->where('famille', '!=', '')->distinct()->orderBy('famille')->pluck('famille'),
            'emplacements' => StockLocation::where('actif', true)->orderBy('nom')->get(['id', 'nom']),
            'catalogue' => [
                ['key' => 'ventes', 'label' => 'Ventes', 'description' => 'Factures locales et factures export, règlements et TVA.'],
                ['key' => 'achats', 'label' => 'Achats', 'description' => 'Factures fournisseurs, règlements et TVA déductible.'],
                ['key' => 'stock', 'label' => 'Stock', 'description' => 'Situation, valorisation et mouvements du stock existant.'],
                ['key' => 'finance', 'label' => 'Finance', 'description' => 'Créances, dettes, caisse, banque et TVA.'],
            ],
        ]);
    }

    public function ventes(Request $request)
    {
        return $this->respond($request, 'ventes', $this->reports->ventes($this->filters($request)));
    }

    public function achats(Request $request)
    {
        return $this->respond($request, 'achats', $this->reports->achats($this->filters($request)));
    }

    public function stock(Request $request)
    {
        return $this->respond($request, 'stock', $this->reports->stock($this->filters($request)));
    }

    public function finance(Request $request)
    {
        return $this->respond($request, 'finance', $this->reports->finance($this->filters($request)));
    }

    private function respond(Request $request, string $report, array $data)
    {
        if (!$request->filled('format')) {
            return response()->json($data);
        }
        $document = $this->reports->document($report, $data);

        return $this->export->download($request->get('format'), 'rapport-' . $report, $document);
    }

    private function filters(Request $request): array
    {
        return [
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'client_id' => $request->get('client_id'),
            'fournisseur_id' => $request->get('fournisseur_id'),
            'article_id' => $request->get('article_id'),
            'categorie' => $request->get('categorie'),
            'statut' => $request->get('statut'),
            'paiement' => $request->get('paiement'),
            'mode_paiement' => $request->get('mode_paiement'),
            'devise' => $request->get('devise'),
            'regroupement' => $request->get('regroupement', 'month'),
            'location_id' => $request->get('location_id'),
            'situation' => $request->get('situation'),
            'search' => $request->get('search'),
        ];
    }
}
