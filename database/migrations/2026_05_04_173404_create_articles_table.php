<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            
            // Désignation
            $table->string('designation');
            
            // HS CODE
            $table->string('hs_code')->nullable();
            
            // TOTAL colis
            $table->integer('total_colis_palet')->nullable()->default(0);
            $table->integer('total_colis_carton')->nullable()->default(0);
            
            // LOT
            $table->string('lot')->nullable();
            
            // CALIBRE
            $table->string('calibre')->nullable();
            
            // Date Production
            $table->date('date_production')->nullable();
            
            // Marque
            $table->string('marque')->nullable();
            
            // EMBALLAGE
            $table->string('emballage_type')->nullable();
            $table->string('emballage_ref')->nullable();
            $table->decimal('emballage_tare', 10, 2)->nullable();
            $table->decimal('t_colis', 10, 2)->nullable();
            $table->decimal('t_emb', 10, 2)->nullable();
            
            // Poids Brut KG
            $table->decimal('poids_brut_unitaire', 10, 2)->nullable();
            $table->decimal('poids_brut_total', 10, 2)->nullable();
            
            // Poids Net KG
            $table->decimal('poids_net_unitaire', 10, 2)->nullable();
            $table->decimal('poids_net_total', 10, 2)->nullable();
            
            // Poids net Egoutté KG
            $table->decimal('poids_net_egoutte_unitaire', 10, 2)->nullable();
            $table->decimal('poids_net_egoutte_total', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
