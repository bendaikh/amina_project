<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commandes')) {
            Schema::create('commandes', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_commande')->nullable();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('reference_client')->nullable();
                $table->string('commercial')->nullable();
                $table->string('type')->default('local'); // local | export
                $table->string('devise')->default('MAD');
                $table->string('mode_paiement')->nullable();
                $table->string('incoterm')->nullable();
                $table->string('destination')->nullable();
                $table->text('adresse')->nullable();
                $table->date('date_souhaitee')->nullable();
                $table->string('priorite')->default('normale'); // basse | normale | haute | urgente
                $table->text('observations')->nullable();
                $table->string('statut')->default('brouillon');
                $table->decimal('total_ht', 14, 2)->default(0);
                $table->decimal('total_tva', 14, 2)->default(0);
                $table->decimal('total_ttc', 14, 2)->default(0);
                $table->decimal('marge_estimee', 14, 2)->default(0);
                $table->decimal('montant_facture', 14, 2)->default(0);
                $table->decimal('montant_regle', 14, 2)->default(0);
                $table->decimal('quantite_livree', 14, 3)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'type', 'client_id', 'date_commande']);
            });
        }

        if (!Schema::hasTable('commande_lignes')) {
            Schema::create('commande_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->string('designation')->nullable();
                $table->decimal('quantite', 14, 3)->default(0);
                $table->string('unite')->nullable();
                $table->decimal('prix', 14, 4)->default(0);
                $table->decimal('remise', 8, 2)->default(0);
                $table->decimal('tva_taux', 8, 2)->default(20);
                $table->decimal('montant_ht', 14, 2)->default(0);
                $table->decimal('montant_tva', 14, 2)->default(0);
                $table->decimal('montant_ttc', 14, 2)->default(0);
                $table->decimal('cout_unitaire', 14, 4)->default(0);
                $table->decimal('quantite_disponible', 14, 3)->default(0);
                $table->decimal('quantite_reservee', 14, 3)->default(0);
                $table->decimal('quantite_a_produire', 14, 3)->default(0);
                $table->decimal('quantite_livree', 14, 3)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ordres_production')) {
            Schema::create('ordres_production', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
                $table->foreignId('commande_ligne_id')->nullable()->constrained('commande_lignes')->nullOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->string('designation')->nullable();
                $table->decimal('quantite_commandee', 14, 3)->default(0);
                $table->decimal('quantite_a_produire', 14, 3)->default(0);
                $table->decimal('quantite_produite', 14, 3)->default(0);
                $table->date('date_planifiee')->nullable();
                $table->date('date_debut')->nullable();
                $table->date('date_fin')->nullable();
                $table->string('responsable')->nullable();
                $table->string('equipe')->nullable();
                $table->string('statut')->default('a_planifier');
                $table->text('observations')->nullable();
                $table->boolean('effet_sortie_matieres')->default(false);
                $table->boolean('effet_entree_pf')->default(false);
                $table->boolean('effet_sous_produits')->default(false);
                $table->boolean('effet_dechets')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'commande_id', 'article_id']);
            });
        }

        if (!Schema::hasTable('livraisons')) {
            Schema::create('livraisons', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
                $table->string('type_livraison')->default('locale'); // locale | export | express
                $table->date('date_prevue')->nullable();
                $table->date('date_preparation')->nullable();
                $table->date('date_chargement')->nullable();
                $table->date('date_livraison')->nullable();
                $table->string('transporteur')->nullable();
                $table->string('chauffeur')->nullable();
                $table->string('vehicule')->nullable();
                $table->text('adresse')->nullable();
                $table->decimal('quantite_a_livrer', 14, 3)->default(0);
                $table->decimal('quantite_preparee', 14, 3)->default(0);
                $table->decimal('quantite_chargee', 14, 3)->default(0);
                $table->string('statut')->default('a_preparer');
                $table->text('observations')->nullable();
                $table->text('documents')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'commande_id', 'type_livraison']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('livraisons');
        Schema::dropIfExists('ordres_production');
        Schema::dropIfExists('commande_lignes');
        Schema::dropIfExists('commandes');
    }
};
