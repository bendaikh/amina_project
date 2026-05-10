<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                  ->orWhere('hs_code', 'like', "%{$search}%")
                  ->orWhere('lot', 'like', "%{$search}%")
                  ->orWhere('marque', 'like', "%{$search}%");
            });
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($articles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_article' => 'nullable|string|max:255',
            'designation' => 'required|string|max:255',
            'famille' => 'nullable|string|max:255',
            'sous_famille' => 'nullable|string|max:255',
            'hs_code' => 'nullable|string|max:50',
            'code_barres' => 'nullable|string|max:255',
            'actif' => 'nullable|boolean',
            'photo' => 'nullable|string|max:255',
            'marque' => 'nullable|string|max:100',
            'calibre' => 'nullable|string|max:50',
            'total_colis_palet' => 'nullable|integer|min:0',
            'total_colis_carton' => 'nullable|integer|min:0',
            'lot' => 'nullable|string|max:50',
            'date_production' => 'nullable|date',
            'emballage_type' => 'nullable|string|max:50',
            'emballage_ref' => 'nullable|string|max:50',
            'emballage_tare' => 'nullable|numeric|min:0',
            't_colis' => 'nullable|numeric|min:0',
            't_emb' => 'nullable|numeric|min:0',
            'poids_brut_unitaire' => 'nullable|numeric|min:0',
            'poids_brut_total' => 'nullable|numeric|min:0',
            'poids_net_unitaire' => 'nullable|numeric|min:0',
            'poids_net_total' => 'nullable|numeric|min:0',
            'poids_net_egoutte_unitaire' => 'nullable|numeric|min:0',
            'poids_net_egoutte_total' => 'nullable|numeric|min:0',
            'poids_brut' => 'nullable|numeric|min:0',
            'poids_net' => 'nullable|numeric|min:0',
            'poids_net_egoutte' => 'nullable|numeric|min:0',
            'ph' => 'nullable|numeric|min:0|max:14',
            'taux_sel' => 'nullable|numeric|min:0',
            'type_emballage' => 'nullable|string|max:100',
            'unites_par_carton' => 'nullable|integer|min:0',
            'cartons_par_palette' => 'nullable|integer|min:0',
            'nombre_total_par_palette' => 'nullable|integer|min:0',
            'type_palette' => 'nullable|string|max:100',
            'dimension_carton_l' => 'nullable|numeric|min:0',
            'dimension_carton_w' => 'nullable|numeric|min:0',
            'dimension_carton_h' => 'nullable|numeric|min:0',
            'poids_carton' => 'nullable|numeric|min:0',
            'poids_palette' => 'nullable|numeric|min:0',
            'dlc_dluo' => 'nullable|integer|min:0',
            'prix_vente' => 'nullable|numeric|min:0',
            'unite_facturation' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
            'taux_tva' => 'nullable|numeric|min:0',
            'marche' => 'nullable|string|max:100',
            'minimum_commande' => 'nullable|integer|min:0',
            'code_interne' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        $article = Article::create($validated);

        return response()->json([
            'message' => 'Article créé avec succès',
            'article' => $article
        ], 201);
    }

    public function show(Article $article)
    {
        return response()->json($article);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'code_article' => 'nullable|string|max:255',
            'designation' => 'required|string|max:255',
            'famille' => 'nullable|string|max:255',
            'sous_famille' => 'nullable|string|max:255',
            'hs_code' => 'nullable|string|max:50',
            'code_barres' => 'nullable|string|max:255',
            'actif' => 'nullable|boolean',
            'photo' => 'nullable|string|max:255',
            'marque' => 'nullable|string|max:100',
            'calibre' => 'nullable|string|max:50',
            'total_colis_palet' => 'nullable|integer|min:0',
            'total_colis_carton' => 'nullable|integer|min:0',
            'lot' => 'nullable|string|max:50',
            'date_production' => 'nullable|date',
            'emballage_type' => 'nullable|string|max:50',
            'emballage_ref' => 'nullable|string|max:50',
            'emballage_tare' => 'nullable|numeric|min:0',
            't_colis' => 'nullable|numeric|min:0',
            't_emb' => 'nullable|numeric|min:0',
            'poids_brut_unitaire' => 'nullable|numeric|min:0',
            'poids_brut_total' => 'nullable|numeric|min:0',
            'poids_net_unitaire' => 'nullable|numeric|min:0',
            'poids_net_total' => 'nullable|numeric|min:0',
            'poids_net_egoutte_unitaire' => 'nullable|numeric|min:0',
            'poids_net_egoutte_total' => 'nullable|numeric|min:0',
            'poids_brut' => 'nullable|numeric|min:0',
            'poids_net' => 'nullable|numeric|min:0',
            'poids_net_egoutte' => 'nullable|numeric|min:0',
            'ph' => 'nullable|numeric|min:0|max:14',
            'taux_sel' => 'nullable|numeric|min:0',
            'type_emballage' => 'nullable|string|max:100',
            'unites_par_carton' => 'nullable|integer|min:0',
            'cartons_par_palette' => 'nullable|integer|min:0',
            'nombre_total_par_palette' => 'nullable|integer|min:0',
            'type_palette' => 'nullable|string|max:100',
            'dimension_carton_l' => 'nullable|numeric|min:0',
            'dimension_carton_w' => 'nullable|numeric|min:0',
            'dimension_carton_h' => 'nullable|numeric|min:0',
            'poids_carton' => 'nullable|numeric|min:0',
            'poids_palette' => 'nullable|numeric|min:0',
            'dlc_dluo' => 'nullable|integer|min:0',
            'prix_vente' => 'nullable|numeric|min:0',
            'unite_facturation' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
            'taux_tva' => 'nullable|numeric|min:0',
            'marche' => 'nullable|string|max:100',
            'minimum_commande' => 'nullable|integer|min:0',
            'code_interne' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        $article->update($validated);

        return response()->json([
            'message' => 'Article mis à jour avec succès',
            'article' => $article
        ]);
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return response()->json([
            'message' => 'Article supprimé avec succès'
        ]);
    }

    public function stats()
    {
        $total = Article::count();
        $totalPoidsBrut = Article::sum('poids_brut_total');
        $totalPoidsNet = Article::sum('poids_net_total');

        return response()->json([
            'total_articles' => $total,
            'total_poids_brut' => $totalPoidsBrut,
            'total_poids_net' => $totalPoidsNet,
        ]);
    }
}
