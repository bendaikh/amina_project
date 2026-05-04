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
            'ice_cin' => 'required|string|max:255|unique:clients,ice_cin',
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:500',
            'pays' => 'nullable|string|max:100',
            'categorie' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
            'statut' => 'nullable|in:client,prospect',
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
        return response()->json($client);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'ice_cin' => ['required', 'string', 'max:255', Rule::unique('clients')->ignore($client->id)],
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:500',
            'pays' => 'nullable|string|max:100',
            'categorie' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
            'statut' => 'nullable|in:client,prospect',
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
}
