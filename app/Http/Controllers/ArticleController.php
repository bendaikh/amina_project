<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $this->normalizeBooleanFields($request);

        $validated = $request->validate([
            'code_article' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'code_article')->whereNotNull('code_article')
            ],
            'designation' => 'required|string|max:255',
            'famille' => 'nullable|string|max:255',
            'sous_famille' => 'nullable|string|max:255',
            'hs_code' => 'nullable|string|max:50',
            'code_barres' => 'nullable|string|max:255',
            'actif' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
            'tare' => 'nullable|numeric|min:0',
            'poids_net' => 'nullable|numeric|min:0',
            'poids_net_egoutte' => 'nullable|numeric|min:0',
            'ph' => 'nullable|numeric|min:0|max:14',
            'taux_sel' => 'nullable|numeric|min:0',
            'type_emballage' => 'nullable|string|max:100',
            'type_emballage_primaire' => 'nullable|string|max:100',
            'type_emballage_secondaire' => 'nullable|string|max:100',
            'unites_par_carton' => 'nullable|integer|min:0',
            'unites_par_colis' => 'nullable|integer|min:0',
            'cartons_par_palette' => 'nullable|integer|min:0',
            'colis_par_palette' => 'nullable|integer|min:0',
            'nombre_total_par_palette' => 'nullable|integer|min:0',
            'type_palette' => 'nullable|string|max:100',
            'sous_reserve_retour' => 'nullable|boolean',
            'dimension_carton_l' => 'nullable|numeric|min:0',
            'dimension_carton_w' => 'nullable|numeric|min:0',
            'dimension_carton_h' => 'nullable|numeric|min:0',
            'dimension_colis_l' => 'nullable|numeric|min:0',
            'dimension_colis_w' => 'nullable|numeric|min:0',
            'dimension_colis_h' => 'nullable|numeric|min:0',
            'poids_carton' => 'nullable|numeric|min:0',
            'poids_colis' => 'nullable|numeric|min:0',
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

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('articles', 'public');
            $validated['photo'] = $path;
        }

        if (empty($validated['code_article'])) {
            $validated['code_article'] = Article::nextCodeArticle();
        }

        $validated['actif'] = $validated['actif'] ?? true;
        $validated['sous_reserve_retour'] = $validated['sous_reserve_retour'] ?? false;

        if (isset($validated['poids_brut']) || isset($validated['tare'])) {
            $brut = (float) ($validated['poids_brut'] ?? 0);
            $tare = (float) ($validated['tare'] ?? 0);
            if ($tare > $brut) {
                $tare = $brut;
                $validated['tare'] = $tare;
            }
            $validated['poids_net'] = round(max(0, $brut - $tare), 3);
        }

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
        $this->normalizeBooleanFields($request);

        $validated = $request->validate([
            'code_article' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'code_article')->ignore($article->id)->whereNotNull('code_article')
            ],
            'designation' => 'required|string|max:255',
            'famille' => 'nullable|string|max:255',
            'sous_famille' => 'nullable|string|max:255',
            'hs_code' => 'nullable|string|max:50',
            'code_barres' => 'nullable|string|max:255',
            'actif' => 'nullable|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
            'tare' => 'nullable|numeric|min:0',
            'poids_net' => 'nullable|numeric|min:0',
            'poids_net_egoutte' => 'nullable|numeric|min:0',
            'ph' => 'nullable|numeric|min:0|max:14',
            'taux_sel' => 'nullable|numeric|min:0',
            'type_emballage' => 'nullable|string|max:100',
            'type_emballage_primaire' => 'nullable|string|max:100',
            'type_emballage_secondaire' => 'nullable|string|max:100',
            'unites_par_carton' => 'nullable|integer|min:0',
            'unites_par_colis' => 'nullable|integer|min:0',
            'cartons_par_palette' => 'nullable|integer|min:0',
            'colis_par_palette' => 'nullable|integer|min:0',
            'nombre_total_par_palette' => 'nullable|integer|min:0',
            'type_palette' => 'nullable|string|max:100',
            'sous_reserve_retour' => 'nullable|boolean',
            'dimension_carton_l' => 'nullable|numeric|min:0',
            'dimension_carton_w' => 'nullable|numeric|min:0',
            'dimension_carton_h' => 'nullable|numeric|min:0',
            'dimension_colis_l' => 'nullable|numeric|min:0',
            'dimension_colis_w' => 'nullable|numeric|min:0',
            'dimension_colis_h' => 'nullable|numeric|min:0',
            'poids_carton' => 'nullable|numeric|min:0',
            'poids_colis' => 'nullable|numeric|min:0',
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

        if ($request->hasFile('photo')) {
            if ($article->photo) {
                Storage::disk('public')->delete($article->photo);
            }
            $path = $request->file('photo')->store('articles', 'public');
            $validated['photo'] = $path;
        }

        if (isset($validated['poids_brut']) || isset($validated['tare'])) {
            $brut = (float) ($validated['poids_brut'] ?? $article->poids_brut ?? 0);
            $tare = (float) ($validated['tare'] ?? $article->tare ?? 0);
            if ($tare > $brut) {
                $tare = $brut;
                $validated['tare'] = $tare;
            }
            $validated['poids_net'] = round(max(0, $brut - $tare), 3);
        }

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
        $totalActifs = Article::where('actif', true)->count();
        $totalPoidsBrut = Article::sum('poids_brut_total');
        $totalPoidsNet = Article::sum('poids_net_total');

        return response()->json([
            'total_articles' => $total,
            'total_actifs' => $totalActifs,
            'total_poids_brut' => $totalPoidsBrut,
            'total_poids_net' => $totalPoidsNet,
        ]);
    }

    public function duplicate(Article $article)
    {
        $newArticle = $article->replicate();
        $newArticle->code_article = Article::nextCodeArticle();
        $newArticle->designation = $article->designation . ' (Copie)';
        $newArticle->created_at = now();
        $newArticle->updated_at = now();
        
        // Don't copy the photo - user can add a new one
        $newArticle->photo = null;
        
        $newArticle->save();

        return response()->json([
            'message' => 'Article dupliqué avec succès',
            'article' => $newArticle
        ], 201);
    }

    public function toggleActive(Article $article)
    {
        $article->actif = !$article->actif;
        $article->save();

        return response()->json([
            'message' => $article->actif ? 'Article activé' : 'Article désactivé',
            'article' => $article
        ]);
    }

    /**
     * Normalize boolean fields from FormData / JSON (e.g. "true", "false", "on", "1", "0").
     * Empty string (unchecked / blank) becomes false. Absent fields are left untouched.
     */
    private function normalizeBooleanFields(Request $request, array $fields = ['actif', 'sous_reserve_retour']): void
    {
        $merged = [];

        foreach ($fields as $field) {
            if (!$request->exists($field)) {
                continue;
            }

            $value = $request->input($field);
            if ($value === '' || $value === null) {
                $merged[$field] = false;
            } else {
                $merged[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
        }

        if ($merged !== []) {
            $request->merge($merged);
        }
    }
}
