<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('achats')) {
            Schema::create('achats', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_achat')->nullable();
                $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
                $table->string('reference_commande')->nullable();
                $table->string('reference_facture')->nullable();
                $table->date('date_facture')->nullable();
                $table->string('devise')->default('MAD');
                $table->string('conditions')->nullable();
                $table->string('mode_paiement')->nullable();
                $table->date('echeance')->nullable();
                $table->string('acheteur')->nullable();
                $table->text('observations')->nullable();
                $table->enum('type', ['stockable', 'non_stockable'])->default('stockable');
                $table->string('categorie')->nullable();
                $table->string('statut')->default('demande');
                $table->boolean('genere_entree_stock')->default(false);
                $table->decimal('total_ht', 14, 2)->default(0);
                $table->decimal('total_tva', 14, 2)->default(0);
                $table->decimal('total_ttc', 14, 2)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'type', 'fournisseur_id']);
            });
        }

        if (!Schema::hasTable('achat_lignes')) {
            Schema::create('achat_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('achat_id')->constrained('achats')->cascadeOnDelete();
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
                $table->string('lot')->nullable();
                $table->date('date_prevue')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('achat_receptions')) {
            Schema::create('achat_receptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('achat_id')->constrained('achats')->cascadeOnDelete();
                $table->string('numero')->unique();
                $table->date('date_reception')->nullable();
                $table->string('statut')->default('recue');
                $table->text('observations')->nullable();
                $table->boolean('entree_stock_generee')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('facture_fournisseurs')) {
            Schema::create('facture_fournisseurs', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_facture')->nullable();
                $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
                $table->foreignId('achat_id')->nullable()->constrained('achats')->nullOnDelete();
                $table->string('reference')->nullable();
                $table->string('devise')->default('MAD');
                $table->string('conditions')->nullable();
                $table->string('mode_paiement')->nullable();
                $table->date('echeance')->nullable();
                $table->enum('origine', ['commande', 'reception', 'directe'])->default('directe');
                $table->string('statut')->default('brouillon');
                $table->decimal('total_ht', 14, 2)->default(0);
                $table->decimal('total_tva', 14, 2)->default(0);
                $table->decimal('total_ttc', 14, 2)->default(0);
                $table->text('observations')->nullable();
                // Suivi réglementaire
                $table->boolean('suivi_facture')->default(true);
                $table->boolean('suivi_tva')->default(false);
                $table->boolean('suivi_justificatifs')->default(false);
                $table->boolean('suivi_docs_fiscaux')->default(false);
                $table->boolean('suivi_import')->default(false);
                $table->boolean('suivi_transport')->default(false);
                $table->boolean('suivi_certificats')->default(false);
                $table->boolean('suivi_echeance')->default(false);
                $table->boolean('suivi_pieces_jointes')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'fournisseur_id', 'echeance']);
            });
        }

        if (!Schema::hasTable('facture_fournisseur_lignes')) {
            Schema::create('facture_fournisseur_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facture_fournisseur_id')->constrained('facture_fournisseurs')->cascadeOnDelete();
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
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('facture_reception')) {
            Schema::create('facture_reception', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facture_fournisseur_id')->constrained('facture_fournisseurs')->cascadeOnDelete();
                $table->foreignId('achat_reception_id')->constrained('achat_receptions')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['facture_fournisseur_id', 'achat_reception_id'], 'facture_reception_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_reception');
        Schema::dropIfExists('facture_fournisseur_lignes');
        Schema::dropIfExists('facture_fournisseurs');
        Schema::dropIfExists('achat_receptions');
        Schema::dropIfExists('achat_lignes');
        Schema::dropIfExists('achats');
    }
};
