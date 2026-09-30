<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockSituationController extends Controller
{
    public function index(Request $request)
    {
        $query = StockBalance::with(['article', 'location'])
            ->orderBy('article_id');

        if ($search = $request->get('search')) {
            $query->whereHas('article', function ($a) use ($search) {
                $a->where('code_article', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($locationId = $request->get('stock_location_id')) {
            $query->where('stock_location_id', $locationId);
        }

        if ($articleId = $request->get('article_id')) {
            $query->where('article_id', $articleId);
        }

        // Hide empty rows optionally
        if ($request->boolean('non_vide', true)) {
            $query->where(function ($q) {
                $q->where('stock_theorique', '!=', 0)
                    ->orWhere('stock_reserve', '!=', 0)
                    ->orWhere('quarantaine', '!=', 0)
                    ->orWhere('endommage', '!=', 0)
                    ->orWhere('transit', '!=', 0);
            });
        }

        $paginator = $query->paginate($request->get('per_page', 50));

        return response()->json($paginator);
    }

    public function resume(Request $request)
    {
        $rows = StockBalance::query()
            ->selectRaw('
                COALESCE(SUM(stock_initial),0) as stock_initial,
                COALESCE(SUM(entrees),0) as entrees,
                COALESCE(SUM(sorties),0) as sorties,
                COALESCE(SUM(stock_theorique),0) as stock_theorique,
                COALESCE(SUM(stock_reserve),0) as stock_reserve,
                COALESCE(SUM(quarantaine),0) as quarantaine,
                COALESCE(SUM(endommage),0) as endommage,
                COALESCE(SUM(transit),0) as transit
            ')
            ->first();

        $theorique = (float) ($rows->stock_theorique ?? 0);
        $reserve = (float) ($rows->stock_reserve ?? 0);

        return response()->json([
            'stock_initial' => (float) ($rows->stock_initial ?? 0),
            'entrees' => (float) ($rows->entrees ?? 0),
            'sorties' => (float) ($rows->sorties ?? 0),
            'stock_theorique' => $theorique,
            'stock_reserve' => $reserve,
            'stock_disponible' => $theorique - $reserve,
            'quarantaine' => (float) ($rows->quarantaine ?? 0),
            'endommage' => (float) ($rows->endommage ?? 0),
            'transit' => (float) ($rows->transit ?? 0),
            'formule' => 'Stock disponible = Stock théorique − Stock réservé',
        ]);
    }

    public function meta()
    {
        return response()->json([
            'emplacements' => StockLocation::where('actif', true)->orderBy('nom')->get(),
            'formule' => 'Stock disponible = Stock théorique − Stock réservé',
        ]);
    }

    public function byArticle($articleId)
    {
        $balances = StockBalance::with('location')
            ->where('article_id', $articleId)
            ->get();

        return response()->json($balances);
    }
}
