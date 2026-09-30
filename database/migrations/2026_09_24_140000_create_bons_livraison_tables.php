<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bons_livraison')) {
            Schema::create('bons_livraison', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_creation')->nullable();
                $table->foreignId('commande_id')->nullable()->unique()->constrained('commandes')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('reference_client')->nullable();
                $table->dateTime('date_heure_livraison')->nullable();
                $table->text('informations_additionnelles')->nullable();
                $table->string('statut')->default('brouillon'); // brouillon | valide | annule
                $table->decimal('total_colis', 14, 3)->default(0);
                $table->decimal('total_poids_net', 14, 3)->default(0);
                $table->decimal('total_poids_total', 14, 3)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'client_id', 'date_creation']);
            });
        }

        if (!Schema::hasTable('bon_livraison_lignes')) {
            Schema::create('bon_livraison_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bon_livraison_id')->constrained('bons_livraison')->cascadeOnDelete();
                $table->foreignId('commande_ligne_id')->nullable()->constrained('commande_lignes')->nullOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->string('ref_article')->nullable();
                $table->string('designation')->nullable();
                $table->string('calibre')->nullable();
                $table->string('emballage')->nullable();
                $table->string('numero_lot')->nullable();
                $table->decimal('total_colis', 14, 3)->default(0);
                $table->decimal('poids_net_eg_unitaire', 14, 3)->nullable();
                $table->decimal('total_poids_net', 14, 3)->default(0);
                $table->decimal('total_poids_total', 14, 3)->default(0);
                $table->unsignedInteger('ordre')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bon_livraison_lignes');
        Schema::dropIfExists('bons_livraison');
    }
};
