<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     */
    public function index(Request $request)
    {
        $query = Client::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('ice_cin', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        // Filter by category
        if ($request->has('categorie') && $request->categorie) {
            $query->where('categorie', $request->categorie);
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($clients);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_client' => 'nullable|string|max:255',
            'ice_cin' => 'nullable|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string',
            'ville' => 'nullable|string|max:255',
            'pays' => 'nullable|string|max:100',
            'categorie' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
            'statut' => 'nullable|in:client,prospect',
            'date_creation' => 'nullable|date',
            'secteur_activite' => 'nullable|string|max:255',
            'groupe_categorie' => 'nullable|string|max:255',
            'commercial_charge' => 'nullable|string|max:255',
            'site_web' => 'nullable|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'contact_fonction' => 'nullable|string|max:255',
            'contact_telephone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'numero_tva' => 'nullable|string|max:255',
            'numero_rc' => 'nullable|string|max:255',
            'eori' => 'nullable|string|max:255',
            'pays_eori' => 'nullable|string|max:255',
            'incoterm' => 'nullable|string|max:50',
            'langue' => 'nullable|string|max:50',
            'delai_paiement' => 'nullable|integer|min:0',
            'plafond_credit' => 'nullable|numeric|min:0',
            'solde_actuel' => 'nullable|numeric',
            'mode_transport' => 'nullable|string|max:50',
            'adresse_livraison' => 'nullable|string',
            'transitaire' => 'nullable|string',
        ]);

        $client = Client::create($validated);

        return response()->json([
            'message' => 'Client créé avec succès',
            'client' => $client
        ], 201);
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        return response()->json($client->load('articles'));
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'code_client' => 'nullable|string|max:255',
            'ice_cin' => 'nullable|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string',
            'ville' => 'nullable|string|max:255',
            'pays' => 'nullable|string|max:100',
            'categorie' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
            'statut' => 'nullable|in:client,prospect',
            'date_creation' => 'nullable|date',
            'secteur_activite' => 'nullable|string|max:255',
            'groupe_categorie' => 'nullable|string|max:255',
            'commercial_charge' => 'nullable|string|max:255',
            'site_web' => 'nullable|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'contact_fonction' => 'nullable|string|max:255',
            'contact_telephone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'numero_tva' => 'nullable|string|max:255',
            'numero_rc' => 'nullable|string|max:255',
            'eori' => 'nullable|string|max:255',
            'pays_eori' => 'nullable|string|max:255',
            'incoterm' => 'nullable|string|max:50',
            'langue' => 'nullable|string|max:50',
            'delai_paiement' => 'nullable|integer|min:0',
            'plafond_credit' => 'nullable|numeric|min:0',
            'solde_actuel' => 'nullable|numeric',
            'mode_transport' => 'nullable|string|max:50',
            'adresse_livraison' => 'nullable|string',
            'transitaire' => 'nullable|string',
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Client mis à jour avec succès',
            'client' => $client
        ]);
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }

    /**
     * Assign articles to a client.
     */
    public function assignArticles(Request $request, Client $client)
    {
        $validated = $request->validate([
            'articles' => 'required|array',
            'articles.*.article_id' => 'required|exists:articles,id',
            'articles.*.prix_negocie' => 'nullable|numeric|min:0',
        ]);

        $syncData = [];
        foreach ($validated['articles'] as $article) {
            $syncData[$article['article_id']] = [
                'prix_negocie' => $article['prix_negocie'] ?? null
            ];
        }

        $client->articles()->syncWithoutDetaching($syncData);

        return response()->json([
            'message' => count($validated['articles']) . ' article(s) affecté(s) avec succès',
            'client' => $client->load('articles')
        ]);
    }

    /**
     * Get articles assigned to a client.
     */
    public function getArticles(Client $client)
    {
        return response()->json($client->articles);
    }
}
