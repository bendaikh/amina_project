<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Client;
use App\Models\Exportation;
use App\Models\User;
use App\Services\DocumentGenerationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@batixper.site'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@amina.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        $client = Client::firstOrCreate(
            ['code_client' => 'CLI-EU-001'],
            [
                'ice_cin' => 'ICE0001',
                'nom' => 'Euro Foods Trading',
                'email' => 'orders@eurofoods.example',
                'telephone' => '+33 1 23 45 67 89',
                'adresse' => '12 Rue du Commerce, Paris',
                'adresse_livraison' => 'Entrepôt Nord, Le Havre',
                'ville' => 'Paris',
                'pays' => 'France',
                'categorie' => 'international',
                'devise' => 'EUR',
                'statut' => 'client',
                'actif' => true,
                'incoterm' => 'FOB',
                'commercial_charge' => 'Amina B.',
                'delai_paiement' => 30,
                'delai_paiement_type' => 'jours',
            ]
        );

        $article = Article::firstOrCreate(
            ['code_article' => 'ART-OLIVE-5L'],
            [
                'designation' => 'Huile d\'olive extra vierge 5L',
                'famille' => 'Huiles',
                'hs_code' => '150910',
                'actif' => true,
                'type_emballage' => 'Bidon',
                'unites_par_colis' => 4,
                'colis_par_palette' => 40,
                'poids_net_unitaire' => 4.6,
                'poids_brut_unitaire' => 5.1,
                'poids_net_egoutte_unitaire' => 4.6,
                'prix_vente' => 28.50,
                'devise' => 'EUR',
                'origine' => 'Maroc',
                'code_interne' => 'HO-5L',
            ]
        );

        if (!Exportation::where('numero', 'like', 'EXP-' . date('Y') . '-%')->exists()) {
            $export = Exportation::create([
                'numero' => DocumentGenerationService::nextExportNumber(),
                'date_creation' => now()->toDateString(),
                'client_id' => $client->id,
                'pays' => 'France',
                'destination' => 'Le Havre',
                'adresse_livraison' => $client->adresse_livraison,
                'commercial' => 'Amina B.',
                'reference_commande_client' => 'PO-2026-8841',
                'date_commande' => now()->subDays(5)->toDateString(),
                'incoterm' => 'FOB',
                'devise' => 'EUR',
                'conditions_paiement' => '30 jours',
                'mode_transport' => 'maritime',
                'booking' => 'BK-MSK-99201',
                'compagnie_maritime' => 'Maersk',
                'navire' => 'MSC ORION',
                'voyage' => 'V26W12',
                'port_chargement' => 'Casablanca',
                'port_destination' => 'Le Havre',
                'etd' => now()->addDays(7)->toDateString(),
                'eta' => now()->addDays(18)->toDateString(),
                'conteneur' => 'MSKU7654321',
                'type_conteneur' => '40HC',
                'plomb_scelle' => 'PLB-44521',
                'statut' => 'preparation',
                'emballages_temporaires' => true,
                'vgm_poids' => 18500,
                'vgm_valide' => true,
            ]);

            $export->lignes()->create([
                'article_id' => $article->id,
                'code_article' => $article->code_article,
                'designation' => $article->designation,
                'hs_code' => $article->hs_code,
                'conditionnement' => $article->type_emballage,
                'quantite' => 800,
                'cartons' => 200,
                'palettes' => 5,
                'poids_net' => 3680,
                'poids_brut' => 4080,
                'poids_egoutte' => 3680,
                'prix_unitaire' => 28.50,
                'devise' => 'EUR',
                'montant' => 22800,
                'origine' => 'Maroc',
            ]);

            $dossier = $export->dossierEmballage()->create([
                'client_id' => $client->id,
                'type_emballage' => 'Fût métallique',
                'type_fut' => 'Fût 200L',
                'reference_fut' => 'FUT-200-A',
                'quantite_exportee' => 50,
                'dum_52' => 'DUM52-2026-001',
                'date_dum' => now()->subDays(10)->toDateString(),
                'date_export' => now()->toDateString(),
                'date_limite_reimportation' => now()->addDays(25)->toDateString(),
                'facture_concernee' => 'SRR-001',
                'conteneur' => $export->conteneur,
                'destination' => $export->destination,
                'statut' => 'ouvert',
            ]);
            $dossier->recalculerStatut();
        }
    }
}
