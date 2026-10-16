<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Commande;
use App\Models\Livraison;
use App\Models\LivraisonConteneur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LivraisonController extends Controller
{
    public function index(Request $request)
    {
        $query = Livraison::with(['commande.client', 'conteneurs'])
            ->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('transporteur', 'like', "%{$search}%")
                    ->orWhere('chauffeur', 'like', "%{$search}%")
                    ->orWhere('vehicule', 'like', "%{$search}%")
                    ->orWhereHas('commande', fn ($c) => $c->where('numero', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($cl) => $cl->where('nom', 'like', "%{$search}%")));
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($type = $request->get('type_livraison')) {
            $query->where('type_livraison', $type);
        }

        if ($commandeId = $request->get('commande_id')) {
            $query->where('commande_id', $commandeId);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function meta()
    {
        return response()->json([
            'statuts' => Livraison::STATUTS,
            'types' => Livraison::TYPES,
            'labels' => [
                'a_preparer' => 'À préparer',
                'en_preparation' => 'En préparation',
                'pret' => 'Prêt',
                'charge' => 'Chargé',
                'livre' => 'Livré',
                'expedie' => 'Expédié',
                'annule' => 'Annulé',
            ],
            'type_labels' => [
                'locale' => 'Locale',
                'export' => 'Export',
                'express' => 'Express',
            ],
            'types_conteneur' => ['20', '40', '45'],
            // Paramètres > Types de conteneur (valeur + capacité en palettes)
            'types_conteneur_params' => CommandeConteneurController::types(),
            'transporteurs' => $this->distinctTransporteurs(),
            'chauffeurs' => $this->knownChauffeurs(),
            'lieux_chargement' => $this->distinctLieux(),
        ]);
    }

    public function show(Livraison $livraison)
    {
        $livraison->load(['commande.client', 'commande.lignes', 'piecesJointes', 'conteneurs']);

        return response()->json($livraison);
    }

    public function store(Request $request)
    {
        $this->normalizeBooleanFields($request);

        $data = $this->validateLivraison($request);
        $reservations = $request->has('reservations') ? $this->extractReservations($request) : null;
        $conteneurs = is_array($reservations) ? [] : $this->extractConteneurs($request);
        $warnings = [];

        $livraison = DB::transaction(function () use ($data, $request, $conteneurs, $reservations, &$warnings) {
            $data['numero'] = $data['numero'] ?? Livraison::nextNumero();
            $data['created_by'] = $request->user()?->id;
            $data['statut'] = $data['statut'] ?? 'a_preparer';

            $this->applyLegacyAliases($data);
            $this->mirrorFirstConteneur($data, $conteneurs);

            $cmd = !empty($data['commande_id']) ? Commande::find($data['commande_id']) : null;

            if ($cmd && empty($data['adresse'])) {
                $data['adresse'] = $cmd->adresse;
                if (empty($data['quantite_a_livrer'])) {
                    $data['quantite_a_livrer'] = max(0, (float) $cmd->lignes()->sum('quantite') - (float) $cmd->quantite_livree);
                }
            }

            // Date de livraison prévue = date souhaitée de la commande (automatique)
            if ($cmd?->date_souhaitee) {
                $data['date_prevue'] = $cmd->date_souhaitee->format('Y-m-d');
            }

            $livraison = Livraison::create($data);
            if (is_array($reservations)) {
                $warnings = $this->syncReservations($livraison, $reservations);
            } else {
                $this->syncConteneurs($livraison, $conteneurs);
            }

            if ($livraison->commande_id) {
                $cmd = Commande::find($livraison->commande_id);
                if ($cmd && in_array($cmd->statut, ['brouillon', 'en_attente', 'confirmee', 'en_production'], true)) {
                    $cmd->update(['statut' => 'en_preparation']);
                }
            }

            AuditLog::record($livraison, 'create', null, null, $livraison->numero, $request->user()?->id);

            return $livraison;
        });

        $livraison->load(['commande.client', 'conteneurs']);
        $payload = $livraison->toArray();
        $payload['warnings'] = $warnings;

        return response()->json($payload, 201);
    }

    public function update(Request $request, Livraison $livraison)
    {
        $this->normalizeBooleanFields($request);

        $data = $this->validateLivraison($request, false);
        $reservations = $request->has('reservations') ? $this->extractReservations($request) : null;
        $conteneurs = (!is_array($reservations) && $request->has('conteneurs')) ? $this->extractConteneurs($request) : null;
        $warnings = [];

        $this->applyLegacyAliases($data);
        if (is_array($conteneurs)) {
            $this->mirrorFirstConteneur($data, $conteneurs);
        }

        // Date de livraison prévue = date souhaitée de la commande (automatique)
        $commandeId = $data['commande_id'] ?? $livraison->commande_id;
        if ($commandeId) {
            $cmd = Commande::find($commandeId);
            if ($cmd?->date_souhaitee) {
                $data['date_prevue'] = $cmd->date_souhaitee->format('Y-m-d');
            }
        }

        DB::transaction(function () use ($livraison, $data, $request, $conteneurs, $reservations, &$warnings) {
            $oldStatut = $livraison->statut;
            $livraison->update($data);

            if (is_array($reservations)) {
                $warnings = $this->syncReservations($livraison, $reservations);
            } elseif (is_array($conteneurs)) {
                $this->syncConteneurs($livraison, $conteneurs);
            }

            if (isset($data['statut']) && in_array($data['statut'], ['livre', 'expedie'], true)
                && $livraison->commande_id
                && $oldStatut !== $data['statut']
            ) {
                $this->syncCommandeLivraison($livraison);
            }

            AuditLog::record($livraison, 'update', null, null, $livraison->numero, $request->user()?->id);
        });

        $payload = $livraison->fresh()->load(['commande.client', 'piecesJointes', 'conteneurs'])->toArray();
        $payload['warnings'] = $warnings;

        return response()->json($payload);
    }

    public function destroy(Livraison $livraison)
    {
        $livraison->delete();

        return response()->json(['message' => 'Livraison supprimée']);
    }

    public function destroyConteneur(Livraison $livraison, LivraisonConteneur $conteneur)
    {
        if ((int) $conteneur->livraison_id !== (int) $livraison->id) {
            return response()->json(['message' => 'Conteneur introuvable pour cette livraison'], 404);
        }

        if ($conteneur->cin_chauffeur_scan) {
            Storage::disk('public')->delete($conteneur->cin_chauffeur_scan);
        }

        $conteneur->delete();

        $first = $livraison->conteneurs()->orderBy('ordre')->orderBy('id')->first();
        if ($first) {
            $data = [];
            $this->mirrorFirstConteneur($data, [[
                'numero_conteneur' => $first->numero_conteneur,
                'tare_conteneur' => $first->tare_conteneur,
                'numero_plomb' => $first->numero_plomb,
                'changement_plomb' => $first->changement_plomb,
                'raison_changement_plomb' => $first->raison_changement_plomb,
                'nouveau_plomb' => $first->nouveau_plomb,
                'matricule_camion' => $first->matricule_camion,
                'chauffeur' => $first->chauffeur,
                'transporteur' => $first->transporteur,
                'cin_chauffeur' => $first->cin_chauffeur,
                'cin_chauffeur_scan' => $first->cin_chauffeur_scan,
            ]]);
            $livraison->update($data);
        } else {
            $livraison->update([
                'numero_conteneur' => null,
                'tare_conteneur' => null,
                'numero_plomb' => null,
                'changement_plomb' => false,
                'raison_changement_plomb' => null,
                'nouveau_plomb' => null,
            ]);
        }

        return response()->json([
            'message' => 'Conteneur supprimé',
            'conteneurs' => $livraison->fresh()->load('conteneurs')->conteneurs,
        ]);
    }

    public function changeStatut(Request $request, Livraison $livraison)
    {
        $request->validate([
            'statut' => 'required|in:' . implode(',', Livraison::STATUTS),
        ]);

        $old = $livraison->statut;
        $livraison->update(['statut' => $request->statut]);

        if (in_array($request->statut, ['livre', 'expedie'], true)) {
            $this->syncCommandeLivraison($livraison);
        }

        AuditLog::record($livraison, 'update', 'statut', $old, $request->statut, $request->user()?->id);

        return response()->json($livraison->fresh()->load(['commande.client']));
    }

    public function uploadPiece(Request $request, Livraison $livraison)
    {
        $request->validate([
            'fichier' => 'required|file|max:10240',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('fichier');
        $path = $file->store("pieces/livraisons/{$livraison->numero}", 'local');

        $piece = $livraison->piecesJointes()->create([
            'type' => $request->type ?? 'document',
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin' => $path,
            'mime' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return response()->json($piece, 201);
    }

    public function uploadCinScan(Request $request, Livraison $livraison)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:jpeg,jpg,png,pdf,webp|max:5120',
            'conteneur_id' => 'nullable|integer|exists:livraison_conteneurs,id',
        ]);

        $conteneur = null;
        if ($request->filled('conteneur_id')) {
            $conteneur = $livraison->conteneurs()->where('id', $request->conteneur_id)->firstOrFail();
        }

        $target = $conteneur ?: $livraison;
        if ($target->cin_chauffeur_scan) {
            Storage::disk('public')->delete($target->cin_chauffeur_scan);
        }

        $path = $request->file('fichier')->store("livraisons/cin/{$livraison->numero}", 'public');
        $target->update(['cin_chauffeur_scan' => $path]);

        // Garder le premier conteneur synchronisé sur la livraison
        if ($conteneur && (int) $livraison->conteneurs()->orderBy('ordre')->orderBy('id')->value('id') === (int) $conteneur->id) {
            $livraison->update(['cin_chauffeur_scan' => $path]);
        } elseif (!$conteneur) {
            $first = $livraison->conteneurs()->orderBy('ordre')->orderBy('id')->first();
            if ($first) {
                $first->update(['cin_chauffeur_scan' => $path]);
            }
        }

        return response()->json([
            'message' => 'Scan CIN enregistré',
            'cin_chauffeur_scan' => $path,
            'conteneur_id' => $conteneur?->id,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function downloadPiece($pieceId)
    {
        $piece = \App\Models\PieceJointe::findOrFail($pieceId);

        if (!$piece->chemin || !Storage::disk('local')->exists($piece->chemin)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        return Storage::disk('local')->download($piece->chemin, $piece->nom_fichier);
    }

    private function syncCommandeLivraison(Livraison $livraison): void
    {
        $cmd = $livraison->commande;
        if (!$cmd) {
            return;
        }

        $qte = (float) ($livraison->quantite_chargee ?: $livraison->quantite_a_livrer);
        if ($qte > 0) {
            $cmd->quantite_livree = round((float) $cmd->quantite_livree + $qte, 3);
            $totalCmd = (float) $cmd->lignes()->sum('quantite');
            if ($cmd->quantite_livree >= $totalCmd && $totalCmd > 0) {
                $cmd->statut = 'livree';
            } else {
                $cmd->statut = 'partiellement_livree';
            }
            $cmd->save();
        }
    }

    private function validateLivraison(Request $request, bool $creating = true): array
    {
        $data = $request->validate([
            'numero' => ($creating ? 'nullable' : 'sometimes') . '|string|max:50',
            'commande_id' => 'nullable|exists:commandes,id',
            'reservation_booking' => 'nullable|boolean',
            'type_livraison' => 'nullable|in:locale,export,express',
            'date_prevue' => 'nullable|date',
            'date_preparation' => 'nullable|date',
            'date_chargement' => 'nullable|date',
            'date_cutoff' => 'nullable|date',
            'date_livraison' => 'nullable|date',
            'transporteur' => 'nullable|string|max:150',
            'chauffeur' => 'nullable|string|max:100',
            'cin_chauffeur' => 'nullable|string|max:100',
            'telephone_chauffeur' => 'nullable|string|max:50',
            'cin_chauffeur_scan' => 'nullable|string|max:255',
            'vehicule' => 'nullable|string|max:100',
            'matricule_camion' => 'nullable|string|max:100',
            'compagnie_maritime' => 'nullable|string|max:150',
            'numero_reservation' => 'nullable|string|max:100',
            'numero_booking' => 'nullable|string|max:100',
            'numero_bl_swb' => 'nullable|string|max:100',
            'navire' => 'nullable|string|max:150',
            'voyage' => 'nullable|string|max:100',
            'port_depart' => 'nullable|string|max:150',
            'terminal' => 'nullable|string|max:150',
            'port_arrivee' => 'nullable|string|max:150',
            'eta' => 'nullable|date',
            'etd' => 'nullable|date',
            'numero_conteneur' => 'nullable|string|max:100',
            'tare_conteneur' => 'nullable|string|max:100',
            'numero_plomb' => 'nullable|string|max:100',
            'changement_plomb' => 'nullable|boolean',
            'raison_changement_plomb' => 'nullable|string',
            'nouveau_plomb' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'lieu_chargement' => 'nullable|string|max:255',
            'arrivee_usine' => 'nullable|date',
            'depart_usine' => 'nullable|date',
            'quantite_a_livrer' => 'nullable|numeric',
            'quantite_preparee' => 'nullable|numeric',
            'quantite_chargee' => 'nullable|numeric',
            'statut' => 'nullable|in:' . implode(',', Livraison::STATUTS),
            'observations' => 'nullable|string',
            'documents' => 'nullable|string',
            'conteneurs' => 'nullable|array',
            'conteneurs.*.id' => 'nullable|integer',
            'conteneurs.*.numero_conteneur' => 'nullable|string|max:100',
            'conteneurs.*.type_conteneur' => 'nullable|string|max:50',
            'reservations' => 'nullable|array',
            'reservations.*.type_conteneur' => 'required|string|max:50',
            'reservations.*.nombre' => 'required|integer|min:0|max:100',
            'conteneurs.*.tare_conteneur' => 'nullable|string|max:100',
            'conteneurs.*.vgm_methode' => 'nullable|in:1,2',
            'conteneurs.*.poids_brut_marchandise' => 'nullable|numeric|min:0',
            'conteneurs.*.vgm_poids' => 'nullable|numeric|min:0',
            'conteneurs.*.numero_plomb' => 'nullable|string|max:100',
            'conteneurs.*.changement_plomb' => 'nullable|boolean',
            'conteneurs.*.raison_changement_plomb' => 'nullable|string',
            'conteneurs.*.nouveau_plomb' => 'nullable|string|max:100',
            'conteneurs.*.matricule_camion' => 'nullable|string|max:100',
            'conteneurs.*.chauffeur' => 'nullable|string|max:100',
            'conteneurs.*.transporteur' => 'nullable|string|max:150',
            'conteneurs.*.cin_chauffeur' => 'nullable|string|max:100',
            'conteneurs.*.telephone_chauffeur' => 'nullable|string|max:50',
            'conteneurs.*.cin_chauffeur_scan' => 'nullable|string|max:255',
            'conteneurs.*.lieu_chargement' => 'nullable|string|max:255',
            'conteneurs.*.arrivee_usine' => 'nullable|date',
            'conteneurs.*.depart_usine' => 'nullable|date',
            'conteneurs.*.ctrl_conteneur' => 'nullable|boolean',
            'conteneurs.*.ctrl_plomb' => 'nullable|boolean',
            'conteneurs.*.ctrl_documents' => 'nullable|boolean',
            'conteneurs.*.observations_controle' => 'nullable|string',
        ]);

        unset($data['conteneurs'], $data['reservations']);

        return $data;
    }

    private function normalizeBooleanFields(Request $request): void
    {
        foreach (['reservation_booking', 'changement_plomb'] as $boolField) {
            if ($request->has($boolField)) {
                $request->merge([
                    $boolField => filter_var($request->input($boolField), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }

        $conteneurs = $request->input('conteneurs');
        if (!is_array($conteneurs)) {
            return;
        }

        foreach ($conteneurs as $i => $c) {
            if (!is_array($c)) {
                continue;
            }
            foreach (['changement_plomb', 'ctrl_conteneur', 'ctrl_plomb', 'ctrl_documents'] as $boolField) {
                if (!array_key_exists($boolField, $c)) {
                    continue;
                }
                $conteneurs[$i][$boolField] = filter_var($c[$boolField], FILTER_VALIDATE_BOOLEAN);
            }
        }
        $request->merge(['conteneurs' => $conteneurs]);
    }

    private function extractConteneurs(Request $request): array
    {
        $conteneurs = $request->input('conteneurs', []);
        if (!is_array($conteneurs)) {
            return [];
        }

        return array_values(array_map(function ($c, $index) {
            $c = is_array($c) ? $c : [];

            $methode = in_array((string) ($c['vgm_methode'] ?? ''), ['1', '2'], true) ? (string) $c['vgm_methode'] : '2';

            return [
                'id' => isset($c['id']) ? (int) $c['id'] : null,
                'ordre' => $index,
                'numero_conteneur' => $c['numero_conteneur'] ?? null,
                'type_conteneur' => $c['type_conteneur'] ?? null,
                'tare_conteneur' => $c['tare_conteneur'] ?? null,
                'vgm_methode' => $methode,
                'poids_brut_marchandise' => $c['poids_brut_marchandise'] ?? null,
                'vgm_poids' => $c['vgm_poids'] ?? null,
                'numero_plomb' => $c['numero_plomb'] ?? null,
                'changement_plomb' => !empty($c['changement_plomb']),
                'raison_changement_plomb' => $c['raison_changement_plomb'] ?? null,
                'nouveau_plomb' => $c['nouveau_plomb'] ?? null,
                'matricule_camion' => $c['matricule_camion'] ?? null,
                'chauffeur' => $c['chauffeur'] ?? null,
                'transporteur' => $c['transporteur'] ?? null,
                'cin_chauffeur' => $c['cin_chauffeur'] ?? null,
                'telephone_chauffeur' => $c['telephone_chauffeur'] ?? null,
                'cin_chauffeur_scan' => $c['cin_chauffeur_scan'] ?? null,
                'lieu_chargement' => $c['lieu_chargement'] ?? null,
                'arrivee_usine' => $c['arrivee_usine'] ?? null,
                'depart_usine' => $c['depart_usine'] ?? null,
                'ctrl_conteneur' => !empty($c['ctrl_conteneur']),
                'ctrl_plomb' => !empty($c['ctrl_plomb']),
                'ctrl_documents' => !empty($c['ctrl_documents']),
                'observations_controle' => $c['observations_controle'] ?? null,
            ];
        }, $conteneurs, array_keys($conteneurs)));
    }

    private function applyLegacyAliases(array &$data): void
    {
        if (!empty($data['matricule_camion']) && empty($data['vehicule'])) {
            $data['vehicule'] = $data['matricule_camion'];
        }
        if (!empty($data['numero_booking']) && empty($data['numero_reservation'])) {
            $data['numero_reservation'] = $data['numero_booking'];
        }
    }

    private function mirrorFirstConteneur(array &$data, array $conteneurs): void
    {
        $first = $conteneurs[0] ?? null;
        if (!$first) {
            return;
        }

        $fields = [
            'numero_conteneur', 'tare_conteneur', 'numero_plomb',
            'changement_plomb', 'raison_changement_plomb', 'nouveau_plomb',
            'matricule_camion', 'chauffeur', 'transporteur',
            'cin_chauffeur', 'telephone_chauffeur', 'cin_chauffeur_scan',
            'lieu_chargement', 'arrivee_usine', 'depart_usine',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $first)) {
                $data[$field] = $first[$field];
            }
        }

        if (!empty($data['matricule_camion']) && empty($data['vehicule'])) {
            $data['vehicule'] = $data['matricule_camion'];
        }
    }

    private function syncConteneurs(Livraison $livraison, array $conteneurs): void
    {
        $keepIds = [];

        foreach ($conteneurs as $payload) {
            $id = $payload['id'] ?? null;
            unset($payload['id']);

            if ($id) {
                $existing = $livraison->conteneurs()->where('id', $id)->first();
                if ($existing) {
                    if (empty($payload['arrivee_usine'])) {
                        $payload['arrivee_usine'] = $existing->arrivee_usine ?: $existing->created_at;
                    }
                    $existing->update($payload);
                    $keepIds[] = $existing->id;
                    continue;
                }
            }

            if (empty($payload['arrivee_usine'])) {
                $payload['arrivee_usine'] = now();
            }

            $created = $livraison->conteneurs()->create($payload);
            $keepIds[] = $created->id;
        }

        // Ne jamais supprimer ici les conteneurs absents du formulaire
        // (ils peuvent avoir été créés/remplis depuis la fiche commande > Chargement).
        // La suppression passe uniquement par destroyConteneur.
    }

    private function extractReservations(Request $request): array
    {
        $out = [];
        foreach ((array) $request->input('reservations', []) as $r) {
            if (!is_array($r) || !isset($r['type_conteneur']) || $r['type_conteneur'] === '') {
                continue;
            }
            $type = (string) $r['type_conteneur'];
            $out[$type] = ($out[$type] ?? 0) + max(0, (int) ($r['nombre'] ?? 0));
        }
        return $out;
    }

    /** Conteneur vide = placeholder (aucun numéro, plomb, transport ni article). */
    public static function isPlaceholder(LivraisonConteneur $c): bool
    {
        foreach (['numero_conteneur', 'numero_plomb', 'tare_conteneur', 'transporteur', 'matricule_camion', 'chauffeur', 'cin_chauffeur', 'cin_chauffeur_scan'] as $f) {
            if (!empty($c->{$f})) {
                return false;
            }
        }
        return !$c->articles()->exists();
    }

    /**
     * Réservations (type => nombre) : crée les conteneurs manquants comme placeholders ;
     * en cas de baisse, ne supprime que des placeholders vides, sinon avertit et ne supprime rien.
     * Les types absents des réservations ne sont jamais touchés si leurs conteneurs sont remplis.
     */
    private function syncReservations(Livraison $livraison, array $reservations): array
    {
        $warnings = [];
        $existing = $livraison->conteneurs()->orderBy('ordre')->orderBy('id')->get();
        $types = array_unique(array_merge(array_keys($reservations), $existing->pluck('type_conteneur')->filter()->map(fn ($t) => (string) $t)->all()));
        $ordre = (int) $existing->max('ordre');

        foreach ($types as $type) {
            $wanted = (int) ($reservations[$type] ?? 0);
            $ofType = $existing->filter(fn ($c) => (string) $c->type_conteneur === (string) $type)->values();
            $have = $ofType->count();

            if ($wanted > $have) {
                for ($i = $have; $i < $wanted; $i++) {
                    $livraison->conteneurs()->create(['type_conteneur' => $type, 'ordre' => ++$ordre]);
                }
            } elseif ($wanted < $have) {
                $toRemove = $have - $wanted;
                $placeholders = $ofType->reverse()->filter(fn ($c) => self::isPlaceholder($c))->values();
                if ($placeholders->count() < $toRemove) {
                    $warnings[] = "Impossible de réduire les conteneurs $type à $wanted : seuls {$placeholders->count()} conteneur(s) vide(s) sur $have peuvent être retirés (les autres contiennent des données). Aucun conteneur $type supprimé.";
                    continue;
                }
                $placeholders->take($toRemove)->each(fn ($c) => $c->delete());
            }
        }

        return $warnings;
    }

    private function distinctTransporteurs(): array
    {
        if (!Schema::hasTable('livraison_conteneurs')) {
            return [];
        }

        return LivraisonConteneur::query()
            ->whereNotNull('transporteur')
            ->where('transporteur', '!=', '')
            ->orderBy('transporteur')
            ->distinct()
            ->pluck('transporteur')
            ->merge(
                Livraison::query()->whereNotNull('transporteur')->where('transporteur', '!=', '')->distinct()->pluck('transporteur')
            )
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->sort()
            ->values()
            ->all();
    }

    private function distinctLieux(): array
    {
        $values = collect();
        if (Schema::hasTable('livraison_conteneurs') && Schema::hasColumn('livraison_conteneurs', 'lieu_chargement')) {
            $values = $values->merge(
                LivraisonConteneur::query()->whereNotNull('lieu_chargement')->where('lieu_chargement', '!=', '')->pluck('lieu_chargement')
            );
        }
        if (Schema::hasColumn('livraisons', 'lieu_chargement')) {
            $values = $values->merge(
                Livraison::query()->whereNotNull('lieu_chargement')->where('lieu_chargement', '!=', '')->pluck('lieu_chargement')
            );
        }

        return $values
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->sort()
            ->values()
            ->all();
    }

    private function knownChauffeurs(): array
    {
        $rows = collect();
        if (Schema::hasTable('livraison_conteneurs')) {
            $columns = ['chauffeur', 'cin_chauffeur', 'matricule_camion'];
            if (Schema::hasColumn('livraison_conteneurs', 'telephone_chauffeur')) {
                $columns[] = 'telephone_chauffeur';
            }
            $rows = $rows->merge(LivraisonConteneur::query()->orderByDesc('id')->get($columns));
        }
        $rows = $rows->merge(Livraison::query()->orderByDesc('id')->get([
            'chauffeur', 'cin_chauffeur', 'matricule_camion',
        ]));

        $byName = [];
        foreach ($rows as $row) {
            $nom = trim((string) $row->chauffeur);
            if ($nom === '') {
                continue;
            }
            $key = mb_strtolower($nom);
            if (!isset($byName[$key])) {
                $byName[$key] = [
                    'nom' => $nom,
                    'telephone' => $row->telephone_chauffeur ?? null,
                    'cin' => $row->cin_chauffeur,
                    'immatriculation' => $row->matricule_camion,
                ];
                continue;
            }
            $byName[$key]['telephone'] = $byName[$key]['telephone'] ?: ($row->telephone_chauffeur ?? null);
            $byName[$key]['cin'] = $byName[$key]['cin'] ?: $row->cin_chauffeur;
            $byName[$key]['immatriculation'] = $byName[$key]['immatriculation'] ?: $row->matricule_camion;
        }

        return array_values($byName);
    }
}
