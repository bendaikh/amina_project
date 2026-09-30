<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DossierEmballage;
use App\Models\RetourEmballage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DossierEmballageController extends Controller
{
    public function index(Request $request)
    {
        $query = DossierEmballage::with(['client', 'exportation', 'retours'])
            ->orderBy('date_limite_reimportation');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('dum_52', 'like', "%{$search}%")
                    ->orWhere('conteneur', 'like', "%{$search}%")
                    ->orWhere('reference_fut', 'like', "%{$search}%")
                    ->orWhere('type_fut', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($request->boolean('alertes_only')) {
            $query->where('statut', '!=', 'solde')
                ->whereNotNull('date_limite_reimportation')
                ->where('date_limite_reimportation', '<=', now()->addDays(90));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function parClient(Request $request)
    {
        $dossiers = DossierEmballage::with(['client', 'retours'])
            ->when($request->get('client_id'), fn ($q, $id) => $q->where('client_id', $id))
            ->get();

        $grouped = $dossiers->groupBy('client_id')->map(function ($items, $clientId) {
            $client = $items->first()->client;
            $exporte = (float) $items->sum('quantite_exportee');
            $retourne = (float) $items->sum(fn ($d) => $d->quantite_reimporte);

            return [
                'client_id' => $clientId,
                'client' => $client ? ['id' => $client->id, 'nom' => $client->nom, 'code_client' => $client->code_client] : null,
                'dossiers' => $items->count(),
                'total_expedie' => $exporte,
                'total_retourne' => $retourne,
                'total_restant' => max(0, $exporte - $retourne),
            ];
        })->values()->sortByDesc('total_restant')->values();

        return response()->json($grouped);
    }

    public function show(DossierEmballage $dossierEmballage)
    {
        $dossierEmballage->load(['client', 'exportation.lignes', 'retours', 'piecesJointes']);

        return response()->json($dossierEmballage);
    }

    public function update(Request $request, DossierEmballage $dossierEmballage)
    {
        $data = $request->validate([
            'type_emballage' => 'nullable|string|max:100',
            'type_fut' => 'nullable|string|max:100',
            'reference_fut' => 'nullable|string|max:100',
            'quantite_exportee' => 'nullable|numeric',
            'dum_52' => 'nullable|string|max:100',
            'date_dum' => 'nullable|date',
            'date_export' => 'nullable|date',
            'date_limite_reimportation' => 'nullable|date',
            'facture_concernee' => 'nullable|string|max:100',
            'conteneur' => 'nullable|string|max:50',
            'destination' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            if ($dossierEmballage->{$key} != $value) {
                AuditLog::record($dossierEmballage, 'update', $key, $dossierEmballage->{$key}, $value, $request->user()?->id);
            }
        }

        $dossierEmballage->update($data);
        $dossierEmballage->recalculerStatut();

        return response()->json($dossierEmballage->fresh()->load(['retours', 'client', 'exportation']));
    }

    public function storeRetour(Request $request, DossierEmballage $dossierEmballage)
    {
        $data = $request->validate([
            'date_reimportation' => 'nullable|date',
            'dum_reimportation' => 'nullable|string|max:100',
            'quantite' => 'required|numeric|min:0.001',
            'type_emballage' => 'nullable|string|max:100',
            'observations' => 'nullable|string',
            'justificatif' => 'nullable|file|max:10240',
        ]);

        $retour = DB::transaction(function () use ($request, $dossierEmballage, $data) {
            $path = null;
            if ($request->hasFile('justificatif')) {
                $path = $request->file('justificatif')->store("retours/{$dossierEmballage->id}", 'local');
            }

            $retour = RetourEmballage::create([
                'dossier_emballage_id' => $dossierEmballage->id,
                'date_reimportation' => $data['date_reimportation'] ?? now()->toDateString(),
                'dum_reimportation' => $data['dum_reimportation'] ?? null,
                'quantite' => $data['quantite'],
                'type_emballage' => $data['type_emballage'] ?? $dossierEmballage->type_emballage,
                'observations' => $data['observations'] ?? null,
                'justificatif_path' => $path,
                'created_by' => $request->user()?->id,
            ]);

            $dossierEmballage->recalculerStatut();
            AuditLog::record($dossierEmballage, 'retour', 'quantite', null, $data['quantite'], $request->user()?->id);

            return $retour;
        });

        return response()->json([
            'retour' => $retour,
            'dossier' => $dossierEmballage->fresh()->load(['retours', 'client']),
        ], 201);
    }

    public function destroyRetour(RetourEmballage $retour)
    {
        $dossier = $retour->dossier;
        $retour->delete();
        $dossier?->recalculerStatut();

        return response()->json([
            'message' => 'Retour supprimé',
            'dossier' => $dossier?->fresh()->load('retours'),
        ]);
    }
}
