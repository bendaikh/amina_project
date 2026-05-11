<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // famille, sous_famille, calibre, type_emballage_primaire, etc.
            $table->string('valeur');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('parametres')->onDelete('cascade');
            $table->timestamps();

            $table->index(['type', 'actif']);
            $table->index('parent_id');
        });

        // Seed initial data
        $this->seedInitialData();
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }

    private function seedInitialData(): void
    {
        $now = now();

        // Familles
        $familles = ['Olives', 'Huiles', 'Conserves', 'Épices', 'Condiments'];
        foreach ($familles as $ordre => $famille) {
            DB::table('parametres')->insert([
                'type' => 'famille',
                'valeur' => $famille,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Get famille IDs
        $familleIds = DB::table('parametres')->where('type', 'famille')->pluck('id', 'valeur');

        // Sous-familles (linked to familles)
        $sousFamilles = [
            'Olives' => ['Olives vertes', 'Olives noires', 'Olives mixtes', 'Olives farcies'],
            'Huiles' => ['Huile d\'olive vierge', 'Huile d\'olive extra vierge', 'Huile d\'argan'],
            'Conserves' => ['Légumes', 'Fruits', 'Poissons'],
            'Épices' => ['Épices en poudre', 'Épices entières', 'Mélanges d\'épices'],
            'Condiments' => ['Sauces', 'Vinaigres', 'Marinades']
        ];
        foreach ($sousFamilles as $famille => $items) {
            foreach ($items as $ordre => $item) {
                DB::table('parametres')->insert([
                    'type' => 'sous_famille',
                    'valeur' => $item,
                    'code' => null,
                    'parent_id' => $familleIds[$famille] ?? null,
                    'ordre' => $ordre,
                    'actif' => true,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }

        // Calibres
        $calibres = ['16/18', '19/21', '22/25', '26/29', '30/33', '34/37', '101/110', '111/120', '121/140', '141/160'];
        foreach ($calibres as $ordre => $calibre) {
            DB::table('parametres')->insert([
                'type' => 'calibre',
                'valeur' => $calibre,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Type emballage primaire
        $emballagesPrimaires = ['Bocal verre', 'Seaux', 'Pots', 'Sachets', 'Bidons', 'Boîtes conserve', 'Fûts'];
        foreach ($emballagesPrimaires as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'type_emballage_primaire',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Type emballage secondaire
        $emballagesSecondaires = ['Carton', 'Caisse bois', 'Film plastique', 'Palette filmée', 'Aucun'];
        foreach ($emballagesSecondaires as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'type_emballage_secondaire',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Type palette
        $palettes = ['Europe (80x120)', 'US (100x120)', 'Demi-palette (60x80)', 'Quart-palette (40x60)', 'Palette plastique'];
        foreach ($palettes as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'type_palette',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Unité de facturation
        $unites = ['Colis', 'Unité', 'Palette', 'Kg', 'Litre', 'Tonne'];
        foreach ($unites as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'unite_facturation',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Devise
        $devises = [
            ['code' => 'EUR', 'valeur' => 'EUR - Euro'],
            ['code' => 'USD', 'valeur' => 'USD - Dollar américain'],
            ['code' => 'MAD', 'valeur' => 'MAD - Dirham marocain'],
            ['code' => 'GBP', 'valeur' => 'GBP - Livre sterling'],
            ['code' => 'CAD', 'valeur' => 'CAD - Dollar canadien'],
            ['code' => 'CHF', 'valeur' => 'CHF - Franc suisse']
        ];
        foreach ($devises as $ordre => $devise) {
            DB::table('parametres')->insert([
                'type' => 'devise',
                'valeur' => $devise['valeur'],
                'code' => $devise['code'],
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Taux TVA
        $tvas = [
            ['valeur' => '0%', 'code' => '0'],
            ['valeur' => '7%', 'code' => '7'],
            ['valeur' => '10%', 'code' => '10'],
            ['valeur' => '14%', 'code' => '14'],
            ['valeur' => '20%', 'code' => '20']
        ];
        foreach ($tvas as $ordre => $tva) {
            DB::table('parametres')->insert([
                'type' => 'taux_tva',
                'valeur' => $tva['valeur'],
                'code' => $tva['code'],
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Marché
        $marches = ['Local', 'Export', 'Les deux'];
        foreach ($marches as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'marche',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Secteur d'activité (for clients)
        $secteurs = ['Agroalimentaire', 'Distribution', 'Restauration', 'Hôtellerie', 'Import/Export', 'Industrie', 'Commerce de détail', 'Grande distribution'];
        foreach ($secteurs as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'secteur_activite',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Groupe / Catégorie (for clients)
        $groupes = ['Distributeur', 'Grossiste', 'Détaillant', 'Industriel', 'Restaurateur', 'Particulier', 'VIP'];
        foreach ($groupes as $ordre => $item) {
            DB::table('parametres')->insert([
                'type' => 'groupe_categorie',
                'valeur' => $item,
                'code' => null,
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Incoterm
        $incoterms = [
            ['code' => 'EXW', 'valeur' => 'EXW - Ex Works'],
            ['code' => 'FOB', 'valeur' => 'FOB - Free On Board'],
            ['code' => 'CIF', 'valeur' => 'CIF - Cost Insurance Freight'],
            ['code' => 'CFR', 'valeur' => 'CFR - Cost and Freight'],
            ['code' => 'DAP', 'valeur' => 'DAP - Delivered At Place'],
            ['code' => 'DDP', 'valeur' => 'DDP - Delivered Duty Paid'],
            ['code' => 'FCA', 'valeur' => 'FCA - Free Carrier']
        ];
        foreach ($incoterms as $ordre => $incoterm) {
            DB::table('parametres')->insert([
                'type' => 'incoterm',
                'valeur' => $incoterm['valeur'],
                'code' => $incoterm['code'],
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        // Délai de paiement
        $delais = [
            ['code' => 'comptant', 'valeur' => 'Paiement comptant'],
            ['code' => 'reception', 'valeur' => 'À réception'],
            ['code' => '30j', 'valeur' => 'Par défaut (30 jours)'],
            ['code' => '60j_negocie', 'valeur' => 'Négocié (60 jours)'],
            ['code' => '45j_fdm', 'valeur' => 'Négocié (45 jours fin de mois)'],
            ['code' => '60j_facture', 'valeur' => '60 jours date de facturation'],
            ['code' => '90j_facture', 'valeur' => '90 jours date de facturation'],
            ['code' => '60j_bl', 'valeur' => '60 jours date de BL']
        ];
        foreach ($delais as $ordre => $delai) {
            DB::table('parametres')->insert([
                'type' => 'delai_paiement',
                'valeur' => $delai['valeur'],
                'code' => $delai['code'],
                'ordre' => $ordre,
                'actif' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }
};
