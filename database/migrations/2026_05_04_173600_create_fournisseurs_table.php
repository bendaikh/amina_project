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
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            
            // ICE (required - Identifiant Commun de l'Entreprise)
            $table->string('ice');
            
            // EORI (Economic Operators Registration and Identification)
            $table->string('eori')->nullable();
            
            // Nom (required)
            $table->string('nom');
            
            // Email
            $table->string('email')->nullable();
            
            // Téléphone
            $table->string('telephone')->nullable();
            
            // Adresse
            $table->text('adresse')->nullable();
            
            // Pays
            $table->string('pays')->nullable();
            
            // Catégorie (local, international, etc.)
            $table->string('categorie')->default('local');
            
            // Devise (MAD, EUR, USD, etc.)
            $table->string('devise')->default('MAD');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
