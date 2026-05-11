<?php

namespace App\Http\Controllers;

use App\Models\Parametre;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParametreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $activeOnly = $request->boolean('active_only', true);
        
        $query = Parametre::query()->ordered();
        
        if ($type) {
            $query->ofType($type);
        }
        
        if ($activeOnly) {
            $query->active();
        }
        
        $parametres = $query->get();
        
        return response()->json($parametres);
    }

    public function getTypes(): JsonResponse
    {
        return response()->json(Parametre::getTypes());
    }

    public function getByType(string $type): JsonResponse
    {
        $parametres = Parametre::getByType($type);
        return response()->json($parametres);
    }

    public function getSousFamilles(int $familleId): JsonResponse
    {
        $sousFamilles = Parametre::getSousFamillesByFamille($familleId);
        return response()->json($sousFamilles);
    }

    public function getAllForForms(): JsonResponse
    {
        $data = [];
        $types = Parametre::getTypes();
        
        foreach (array_keys($types) as $type) {
            $data[$type] = Parametre::getByType($type);
        }
        
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'valeur' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'ordre' => 'nullable|integer',
            'actif' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:parametres,id',
        ]);
        
        $validated['actif'] = $validated['actif'] ?? true;
        $validated['ordre'] = $validated['ordre'] ?? Parametre::where('type', $validated['type'])->max('ordre') + 1;
        
        $parametre = Parametre::create($validated);
        
        return response()->json([
            'message' => 'Paramètre créé avec succès',
            'parametre' => $parametre
        ], 201);
    }

    public function update(Request $request, Parametre $parametre): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string',
            'valeur' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'ordre' => 'nullable|integer',
            'actif' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:parametres,id',
        ]);
        
        $parametre->update($validated);
        
        return response()->json([
            'message' => 'Paramètre mis à jour avec succès',
            'parametre' => $parametre
        ]);
    }

    public function destroy(Parametre $parametre): JsonResponse
    {
        $parametre->delete();
        
        return response()->json([
            'message' => 'Paramètre supprimé avec succès'
        ]);
    }

    public function toggleActive(Parametre $parametre): JsonResponse
    {
        $parametre->actif = !$parametre->actif;
        $parametre->save();
        
        return response()->json([
            'message' => 'Statut mis à jour',
            'parametre' => $parametre
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:parametres,id',
            'items.*.ordre' => 'required|integer',
        ]);
        
        foreach ($request->items as $item) {
            Parametre::where('id', $item['id'])->update(['ordre' => $item['ordre']]);
        }
        
        return response()->json([
            'message' => 'Ordre mis à jour avec succès'
        ]);
    }
}
