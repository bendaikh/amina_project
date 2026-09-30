<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'port_chargement')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('port_chargement')->nullable()->after('transitaire');
            });
        }

        if (Schema::hasTable('articles') && !Schema::hasColumn('articles', 'tare')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->decimal('tare', 10, 3)->nullable()->after('poids_brut');
            });
        }

        if (Schema::hasTable('commande_lignes')) {
            Schema::table('commande_lignes', function (Blueprint $table) {
                if (!Schema::hasColumn('commande_lignes', 'calibre')) {
                    $table->string('calibre')->nullable()->after('designation');
                }
                if (!Schema::hasColumn('commande_lignes', 'type_emballage_primaire')) {
                    $table->string('type_emballage_primaire')->nullable()->after('calibre');
                }
                if (!Schema::hasColumn('commande_lignes', 'reference_emballage')) {
                    $table->string('reference_emballage')->nullable()->after('type_emballage_primaire');
                }
                if (!Schema::hasColumn('commande_lignes', 'type_emballage_secondaire')) {
                    $table->string('type_emballage_secondaire')->nullable()->after('reference_emballage');
                }
                if (!Schema::hasColumn('commande_lignes', 'unites_par_colis')) {
                    $table->integer('unites_par_colis')->nullable()->after('type_emballage_secondaire');
                }
                if (!Schema::hasColumn('commande_lignes', 'colis_par_palette')) {
                    $table->integer('colis_par_palette')->nullable()->after('unites_par_colis');
                }
                if (!Schema::hasColumn('commande_lignes', 'nombre_total_par_palette')) {
                    $table->integer('nombre_total_par_palette')->nullable()->after('colis_par_palette');
                }
                if (!Schema::hasColumn('commande_lignes', 'poids_net_egoutte')) {
                    $table->decimal('poids_net_egoutte', 14, 3)->nullable()->after('nombre_total_par_palette');
                }
            });
        }

        if (Schema::hasTable('livraisons')) {
            Schema::table('livraisons', function (Blueprint $table) {
                if (!Schema::hasColumn('livraisons', 'compagnie_maritime')) {
                    $table->string('compagnie_maritime')->nullable()->after('vehicule');
                }
                if (!Schema::hasColumn('livraisons', 'numero_reservation')) {
                    $table->string('numero_reservation')->nullable()->after('compagnie_maritime');
                }
                if (!Schema::hasColumn('livraisons', 'navire')) {
                    $table->string('navire')->nullable()->after('numero_reservation');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'port_chargement')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('port_chargement');
            });
        }

        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'tare')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('tare');
            });
        }

        if (Schema::hasTable('commande_lignes')) {
            Schema::table('commande_lignes', function (Blueprint $table) {
                $cols = [
                    'calibre', 'type_emballage_primaire', 'reference_emballage',
                    'type_emballage_secondaire', 'unites_par_colis', 'colis_par_palette',
                    'nombre_total_par_palette', 'poids_net_egoutte',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('commande_lignes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('livraisons')) {
            Schema::table('livraisons', function (Blueprint $table) {
                foreach (['compagnie_maritime', 'numero_reservation', 'navire'] as $col) {
                    if (Schema::hasColumn('livraisons', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
