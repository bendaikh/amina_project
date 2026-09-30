<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('factures_locales')) {
            Schema::create('factures_locales', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_facture')->nullable();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
                $table->foreignId('bon_livraison_id')->nullable()->unique()->constrained('bons_livraison')->nullOnDelete();
                $table->string('reference_client')->nullable();
                $table->string('numero_bl')->nullable();
                $table->string('numero_commande')->nullable();
                $table->string('devise', 10)->default('MAD');
                $table->string('conditions_paiement')->nullable();
                $table->string('mode_paiement')->nullable();
                $table->date('echeance')->nullable();
                $table->string('statut')->default('brouillon'); // brouillon | validee | payee | annulee
                $table->decimal('total_ht', 14, 2)->default(0);
                $table->decimal('total_tva', 14, 2)->default(0);
                $table->decimal('total_ttc', 14, 2)->default(0);
                $table->text('observations')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'client_id', 'date_facture']);
            });
        }

        if (!Schema::hasTable('facture_locale_lignes')) {
            Schema::create('facture_locale_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facture_locale_id')->constrained('factures_locales')->cascadeOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->foreignId('bon_livraison_ligne_id')->nullable()->constrained('bon_livraison_lignes')->nullOnDelete();
                $table->foreignId('commande_ligne_id')->nullable()->constrained('commande_lignes')->nullOnDelete();
                $table->string('ref_article')->nullable();
                $table->string('designation')->nullable();
                $table->string('calibre')->nullable();
                $table->string('emballage')->nullable();
                $table->string('numero_lot')->nullable();
                $table->decimal('quantite', 14, 3)->default(0);
                $table->string('unite')->nullable();
                $table->decimal('prix_unitaire', 14, 4)->default(0);
                $table->decimal('tva_taux', 5, 2)->default(20);
                $table->decimal('montant_ht', 14, 2)->default(0);
                $table->decimal('montant_tva', 14, 2)->default(0);
                $table->decimal('montant_ttc', 14, 2)->default(0);
                $table->unsignedInteger('ordre')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_locale_lignes');
        Schema::dropIfExists('factures_locales');
    }
};
