<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FournisseurController extends Controller
{
    /**
     * Display a listing of the fournisseurs.
     */
    public function index(Request $request)
    {
        $query = Fournisseur::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('ice', 'like', "%{$search}%")
                  ->orWhere('eori', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('categorie') && $request->categorie) {
            $query->where('categorie', $request->categorie);
        }

        // Filter by pays
        if ($request->has('pays') && $request->pays) {
            $query->where('pays', $request->pays);
        }

        $fournisseurs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($fournisseurs);
    }

    /**
     * Store a newly created fournisseur in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ice' => 'required|string|max:255|unique:fournisseurs,ice',
            'eori' => 'nullable|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:500',
            'pays' => 'nullable|string|max:100',
            'categorie' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
        ]);

        $fournisseur = Fournisseur::create($validated);

        return response()->json([
            'message' => 'Fournisseur créé avec succès',
            'fournisseur' => $fournisseur
        ], 201);
    }

    /**
     * Display the specified fournisseur.
     */
    public function show(Fournisseur $fournisseur)
    {
        return response()->json($fournisseur);
    }

    /**
     * Update the specified fournisseur in storage.
     */
    public function update(Request $request, Fournisseur $fournisseur)
    {
        $validated = $request->validate([
            'ice' => ['required', 'string', 'max:255', Rule::unique('fournisseurs')->ignore($fournisseur->id)],
            'eori' => 'nullable|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string|max:500',
            'pays' => 'nullable|string|max:100',
            'categorie' => 'nullable|string|max:50',
            'devise' => 'nullable|string|max:10',
        ]);

        $fournisseur->update($validated);

        return response()->json([
            'message' => 'Fournisseur mis à jour avec succès',
            'fournisseur' => $fournisseur
        ]);
    }

    /**
     * Remove the specified fournisseur from storage.
     */
    public function destroy(Fournisseur $fournisseur)
    {
        $fournisseur->delete();

        return response()->json([
            'message' => 'Fournisseur supprimé avec succès'
        ]);
    }
}
