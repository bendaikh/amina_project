<?php

namespace App\Http\Controllers;

use App\Models\Exportateur;
use Illuminate\Http\Request;

class ExportateurController extends Controller
{
    public function index(Request $request)
    {
        $query = Exportateur::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('ref_foodex', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
                  ->orWhere('adresse', 'like', "%{$search}%")
                  ->orWhere('web', 'like', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        $exportateurs = $query
            ->orderByDesc('est_defaut')
            ->orderBy('nom')
            ->paginate(15);

        return response()->json($exportateurs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'nom_societe' => 'nullable|string|max:255',
            'ref_foodex' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:1000',
            'telephone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence' => 'nullable|string|max:255',
            'beneficiaire' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:64',
            'swift' => 'nullable|string|max:32',
            'rib' => 'nullable|string|max:64',
            'actif' => 'nullable|boolean',
            'est_defaut' => 'nullable|boolean',
        ]);

        $validated['actif'] = $validated['actif'] ?? true;
        $validated['est_defaut'] = $validated['est_defaut'] ?? false;

        if (!empty($validated['est_defaut'])) {
            Exportateur::where('est_defaut', true)->update(['est_defaut' => false]);
        }

        $exportateur = Exportateur::create($validated);

        return response()->json([
            'message' => 'Exportateur créé avec succès',
            'exportateur' => $exportateur,
        ], 201);
    }

    public function show(Exportateur $exportateur)
    {
        return response()->json($exportateur);
    }

    public function update(Request $request, Exportateur $exportateur)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'nom_societe' => 'nullable|string|max:255',
            'ref_foodex' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:1000',
            'telephone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence' => 'nullable|string|max:255',
            'beneficiaire' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:64',
            'swift' => 'nullable|string|max:32',
            'rib' => 'nullable|string|max:64',
            'actif' => 'nullable|boolean',
            'est_defaut' => 'nullable|boolean',
        ]);

        if (!empty($validated['est_defaut'])) {
            Exportateur::where('id', '!=', $exportateur->id)->where('est_defaut', true)->update(['est_defaut' => false]);
        }

        $exportateur->update($validated);

        return response()->json([
            'message' => 'Exportateur mis à jour avec succès',
            'exportateur' => $exportateur,
        ]);
    }

    public function destroy(Exportateur $exportateur)
    {
        $exportateur->delete();

        return response()->json([
            'message' => 'Exportateur supprimé avec succès',
        ]);
    }

    public function toggleActive(Exportateur $exportateur)
    {
        $exportateur->actif = !$exportateur->actif;
        $exportateur->save();

        return response()->json([
            'message' => 'Statut mis à jour',
            'exportateur' => $exportateur,
        ]);
    }

    /**
     * Marque cet exportateur comme primaire (exportateur de la société).
     * Un seul exportateur peut être primaire à la fois.
     */
    public function setPrimaire(Exportateur $exportateur)
    {
        Exportateur::where('est_defaut', true)->update(['est_defaut' => false]);

        $exportateur->est_defaut = true;
        $exportateur->actif = true;
        $exportateur->save();

        return response()->json([
            'message' => 'Exportateur primaire mis à jour',
            'exportateur' => $exportateur,
        ]);
    }
}
