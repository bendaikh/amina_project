<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\Livraison;
use App\Models\LivraisonConteneurArticle;
use App\Models\Parametre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Ajouter un conteneur" depuis la fiche commande (onglet Chargement).
 * Booking / navire / ports / ETD / ETA / statut = livraison de la commande (partagée par ses conteneurs).
 */
class CommandeConteneurController extends Controller
{
    /** Types de conteneur actifs (Paramètres > Types de conteneur ; code = capacité en palettes). */
    public static function types(): array
    {
        return Parametre::ofType(Parametre::TYPE_CONTENEUR)->where('actif', true)->orderBy('ordre')->get()
            ->map(fn ($p) => [
                'valeur' => $p->valeur,
                'capacite' => is_numeric($p->code) ? (int) $p->code : null,
            ])->values()->all();
    }

    public static function capacite(?string $type): ?int
    {
        foreach (self::types() as $t) {
            if ((string) $t['valeur'] === (string) $type) {
                return $t['capacite'];
            }
        }
        return null;
    }

    /** Colis (cartons) commandés pour une ligne. */
    public static function colisCommandes(CommandeLigne $l): int
    {
        $q = (float) $l->quantite;
        $pne = (float) ($l->poids_net_egoutte ?? 0);
        $unite = strtolower((string) $l->unite);
        if ($q > 0 && $pne > 0 && in_array($unite, ['kg', 'kgs', 'kilo', 'kilogramme'], true)) {
            return (int) round($q / $pne);
        }
        if ($q > 0 && !in_array($unite, ['kg', 'kgs', 'kilo', 'kilogramme', 'l', 'litre'], true)) {
            return (int) round($q);
        }
        return (int) ($l->nombre_total_par_palette ?? 0);
    }

    /** Données réelles pour le formulaire "Ajouter un conteneur". */
    public function meta()
    {
        $labels = [
            'a_preparer' => 'À préparer', 'en_preparation' => 'En préparation', 'pret' => 'Prêt',
            'charge' => 'Chargé', 'livre' => 'Livré', 'expedie' => 'Expédié', 'annule' => 'Annulé',
        ];
        return response()->json([
            'types_conteneur' => self::types(),
            'statuts' => collect(Livraison::STATUTS)->map(fn ($k) => ['key' => $k, 'label' => $labels[$k] ?? $k])->values(),
        ]);
    }

    public function store(Request $request, Commande $commande)
    {
        $data = $request->validate([
            'numero_conteneur' => 'nullable|string|max:255',
            'type_conteneur' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::in(array_column(self::types(), 'valeur'))],
            'tare_conteneur' => 'nullable|string|max:255',
            'numero_plomb' => 'nullable|string|max:255',
            'date_enlevement' => 'nullable|date',
            'numero_booking' => 'nullable|string|max:255',
            'navire' => 'nullable|string|max:255',
            'port_depart' => 'nullable|string|max:255',
            'port_arrivee' => 'nullable|string|max:255',
            'etd' => 'nullable|date',
            'eta' => 'nullable|date',
            'statut' => 'nullable|in:' . implode(',', Livraison::STATUTS),
            'transporteur' => 'nullable|string|max:255',
            'matricule_camion' => 'nullable|string|max:255',
            'chauffeur' => 'nullable|string|max:255',
            'cin_chauffeur' => 'nullable|string|max:255',
            'articles' => 'array',
            'articles.*.commande_ligne_id' => 'required|integer',
            'articles.*.cartons' => 'required|integer|min:1',
        ]);

        $lignes = $commande->lignes()->get()->keyBy('id');
        $capacite = self::capacite($data['type_conteneur'] ?? null);

        $conteneur = DB::transaction(function () use ($commande, $data, $lignes, $capacite, $request) {
            $totalPalettes = 0;
            $rows = [];
            foreach ($data['articles'] ?? [] as $i => $a) {
                $ligne = $lignes->get($a['commande_ligne_id']);
                if (!$ligne) {
                    throw ValidationException::withMessages(["articles.$i" => 'Article hors commande.']);
                }
                $deja = (int) LivraisonConteneurArticle::where('commande_ligne_id', $ligne->id)->lockForUpdate()->sum('cartons');
                $dispo = max(0, self::colisCommandes($ligne) - $deja);
                $cartons = (int) $a['cartons'];
                if ($cartons > $dispo) {
                    throw ValidationException::withMessages(["articles.$i" => "Quantité supérieure au disponible ($dispo cartons) pour {$ligne->designation}."]);
                }
                $cpp = (int) ($ligne->colis_par_palette ?? 0);
                $palettes = $cpp > 0 ? (int) ceil($cartons / $cpp) : 0;
                $totalPalettes += $palettes;
                $rows[] = ['commande_ligne_id' => $ligne->id, 'article_id' => $ligne->article_id, 'cartons' => $cartons, 'palettes' => $palettes];
            }
            if ($capacite !== null && $totalPalettes > $capacite) {
                throw ValidationException::withMessages(['articles' => "Capacité dépassée : $totalPalettes / $capacite palettes."]);
            }

            $livraison = $commande->livraisons()->orderByDesc('id')->first();
            $shared = array_filter([
                'numero_booking' => $data['numero_booking'] ?? null,
                'navire' => $data['navire'] ?? null,
                'port_depart' => $data['port_depart'] ?? null,
                'port_arrivee' => $data['port_arrivee'] ?? null,
                'etd' => $data['etd'] ?? null,
                'eta' => $data['eta'] ?? null,
                'statut' => $data['statut'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if (!$livraison) {
                $livraison = Livraison::create(array_merge([
                    'numero' => Livraison::nextNumero(),
                    'commande_id' => $commande->id,
                    'type_livraison' => $commande->type === 'export' ? 'export' : 'locale',
                    'adresse' => $commande->adresse,
                    'quantite_a_livrer' => max(0, (float) $commande->lignes()->sum('quantite') - (float) $commande->quantite_livree),
                    'date_prevue' => $commande->date_souhaitee?->format('Y-m-d'),
                    'statut' => 'a_preparer',
                    'reservation_booking' => true,
                    'created_by' => $request->user()?->id,
                ], $shared));
                if (in_array($commande->statut, ['brouillon', 'en_attente', 'confirmee', 'en_production'], true)) {
                    $commande->update(['statut' => 'en_preparation']);
                }
                AuditLog::record($livraison, 'create', null, null, $livraison->numero, $request->user()?->id);
            } elseif ($shared) {
                $livraison->update($shared);
            }

            $conteneur = $livraison->conteneurs()->create([
                'ordre' => (int) $livraison->conteneurs()->max('ordre') + 1,
                'numero_conteneur' => $data['numero_conteneur'] ?? null,
                'type_conteneur' => $data['type_conteneur'] ?? null,
                'tare_conteneur' => $data['tare_conteneur'] ?? null,
                'numero_plomb' => $data['numero_plomb'] ?? null,
                'date_enlevement' => $data['date_enlevement'] ?? null,
                'transporteur' => $data['transporteur'] ?? null,
                'matricule_camion' => $data['matricule_camion'] ?? null,
                'chauffeur' => $data['chauffeur'] ?? null,
                'cin_chauffeur' => $data['cin_chauffeur'] ?? null,
                'arrivee_usine' => now(),
            ]);
            foreach ($rows as $r) {
                $conteneur->articles()->create($r);
            }

            if (empty($livraison->numero_conteneur) && $conteneur->numero_conteneur) {
                $livraison->update(['numero_conteneur' => $conteneur->numero_conteneur, 'numero_plomb' => $conteneur->numero_plomb, 'tare_conteneur' => $conteneur->tare_conteneur]);
            }

            return $conteneur;
        });

        return response()->json($conteneur->load('articles'), 201);
    }
}
