<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Add new emballage fields
            $table->string('type_emballage_primaire')->nullable()->after('type_emballage');
            $table->string('type_emballage_secondaire')->nullable()->after('type_emballage_primaire');
            
            // Rename carton fields to colis
            if (Schema::hasColumn('articles', 'unites_par_carton')) {
                $table->renameColumn('unites_par_carton', 'unites_par_colis');
            }
            if (Schema::hasColumn('articles', 'cartons_par_palette')) {
                $table->renameColumn('cartons_par_palette', 'colis_par_palette');
            }
            if (Schema::hasColumn('articles', 'dimension_carton_l')) {
                $table->renameColumn('dimension_carton_l', 'dimension_colis_l');
            }
            if (Schema::hasColumn('articles', 'dimension_carton_w')) {
                $table->renameColumn('dimension_carton_w', 'dimension_colis_w');
            }
            if (Schema::hasColumn('articles', 'dimension_carton_h')) {
                $table->renameColumn('dimension_carton_h', 'dimension_colis_h');
            }
            if (Schema::hasColumn('articles', 'poids_carton')) {
                $table->renameColumn('poids_carton', 'poids_colis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['type_emballage_primaire', 'type_emballage_secondaire']);
            
            // Revert renames
            if (Schema::hasColumn('articles', 'unites_par_colis')) {
                $table->renameColumn('unites_par_colis', 'unites_par_carton');
            }
            if (Schema::hasColumn('articles', 'colis_par_palette')) {
                $table->renameColumn('colis_par_palette', 'cartons_par_palette');
            }
            if (Schema::hasColumn('articles', 'dimension_colis_l')) {
                $table->renameColumn('dimension_colis_l', 'dimension_carton_l');
            }
            if (Schema::hasColumn('articles', 'dimension_colis_w')) {
                $table->renameColumn('dimension_colis_w', 'dimension_carton_w');
            }
            if (Schema::hasColumn('articles', 'dimension_colis_h')) {
                $table->renameColumn('dimension_colis_h', 'dimension_carton_h');
            }
            if (Schema::hasColumn('articles', 'poids_colis')) {
                $table->renameColumn('poids_colis', 'poids_carton');
            }
        });
    }
};
