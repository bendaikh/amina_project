<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Informations générales
            $table->string('code_article')->nullable()->after('id');
            $table->string('famille')->nullable()->after('designation');
            $table->string('sous_famille')->nullable()->after('famille');
            $table->string('code_barres')->nullable();
            $table->boolean('actif')->default(true);
            $table->string('photo')->nullable();
            
            // Poids & caractéristiques
            $table->decimal('poids_brut', 10, 2)->nullable();
            $table->decimal('poids_net', 10, 2)->nullable();
            $table->decimal('poids_net_egoutte', 10, 2)->nullable();
            $table->decimal('ph', 5, 2)->nullable();
            $table->decimal('taux_sel', 5, 2)->nullable();
            
            // Conditionnement / Emballage
            $table->string('type_emballage')->nullable();
            $table->integer('unites_par_carton')->nullable();
            $table->integer('cartons_par_palette')->nullable();
            $table->integer('nombre_total_par_palette')->nullable();
            $table->string('type_palette')->nullable();
            $table->decimal('dimension_carton_l', 8, 2)->nullable();
            $table->decimal('dimension_carton_w', 8, 2)->nullable();
            $table->decimal('dimension_carton_h', 8, 2)->nullable();
            $table->decimal('poids_carton', 10, 2)->nullable();
            
            // Logistique
            $table->decimal('poids_palette', 10, 2)->nullable();
            $table->integer('dlc_dluo')->nullable();
            
            // Informations commerciales
            $table->decimal('prix_vente', 15, 2)->nullable();
            $table->string('unite_facturation')->nullable();
            $table->string('devise')->default('EUR');
            $table->decimal('taux_tva', 5, 2)->nullable();
            $table->string('marche')->nullable();
            $table->integer('minimum_commande')->nullable();
            $table->string('code_interne')->nullable();
            $table->text('observations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'code_article', 'famille', 'sous_famille', 'code_barres', 'actif', 'photo',
                'poids_brut', 'poids_net', 'poids_net_egoutte', 'ph', 'taux_sel',
                'type_emballage', 'unites_par_carton', 'cartons_par_palette', 
                'nombre_total_par_palette', 'type_palette',
                'dimension_carton_l', 'dimension_carton_w', 'dimension_carton_h', 'poids_carton',
                'poids_palette', 'dlc_dluo',
                'prix_vente', 'unite_facturation', 'devise', 'taux_tva', 
                'marche', 'minimum_commande', 'code_interne', 'observations'
            ]);
        });
    }
};
