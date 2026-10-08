<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('comptes_bancaires')) {
            Schema::create('comptes_bancaires', function (Blueprint $table) {
                $table->id();
                $table->string('nom_banque');
                $table->string('nom_compte');
                $table->string('rib')->nullable();
                $table->string('iban')->nullable();
                $table->string('devise', 10)->default('MAD');
                $table->decimal('solde_ouverture', 14, 2)->default(0);
                $table->boolean('actif')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reglements')) {
            Schema::create('reglements', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->string('sens', 20); // client | fournisseur
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
                $table->foreignId('compte_bancaire_id')->nullable()->constrained('comptes_bancaires')->nullOnDelete();
                $table->date('date_reglement');
                $table->string('mode_paiement', 40);
                $table->decimal('montant', 14, 2);
                $table->string('devise', 10)->default('MAD');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['sens', 'date_reglement']);
                $table->index(['client_id', 'fournisseur_id']);
            });
        }

        if (!Schema::hasTable('reglement_lignes')) {
            Schema::create('reglement_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reglement_id')->constrained('reglements')->cascadeOnDelete();
                $table->string('facture_type', 30); // locale | export | fournisseur
                $table->unsignedBigInteger('facture_id');
                $table->decimal('montant', 14, 2);
                $table->timestamps();
                $table->index(['facture_type', 'facture_id']);
            });
        }

        if (!Schema::hasTable('lettrage_historiques')) {
            Schema::create('lettrage_historiques', function (Blueprint $table) {
                $table->id();
                $table->string('sens', 20);
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
                $table->string('action', 30); // auto | manuel | partiel | delettrage
                $table->foreignId('reglement_id')->nullable()->constrained('reglements')->nullOnDelete();
                $table->string('facture_type', 30)->nullable();
                $table->unsignedBigInteger('facture_id')->nullable();
                $table->string('facture_numero')->nullable();
                $table->string('reglement_numero')->nullable();
                $table->decimal('montant_facture', 14, 2)->default(0);
                $table->decimal('montant_reglement', 14, 2)->default(0);
                $table->decimal('montant_lettre', 14, 2)->default(0);
                $table->decimal('reste', 14, 2)->default(0);
                $table->decimal('ecart', 14, 2)->default(0);
                $table->text('commentaire')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['sens', 'client_id', 'fournisseur_id']);
            });
        }

        if (!Schema::hasTable('mouvements_bancaires')) {
            Schema::create('mouvements_bancaires', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compte_bancaire_id')->constrained('comptes_bancaires')->cascadeOnDelete();
                $table->date('date_operation');
                $table->string('description');
                $table->string('reference')->nullable();
                $table->decimal('debit', 14, 2)->default(0);
                $table->decimal('credit', 14, 2)->default(0);
                $table->string('type', 40)->default('autre');
                $table->string('source', 20)->default('manuel'); // manuel | import
                $table->timestamps();
                $table->index(['compte_bancaire_id', 'date_operation']);
            });
        }

        if (!Schema::hasTable('rapprochement_lignes')) {
            Schema::create('rapprochement_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compte_bancaire_id')->constrained('comptes_bancaires')->cascadeOnDelete();
                $table->foreignId('mouvement_bancaire_id')->constrained('mouvements_bancaires')->cascadeOnDelete();
                $table->foreignId('reglement_id')->constrained('reglements')->cascadeOnDelete();
                $table->decimal('montant', 14, 2);
                $table->string('methode', 20)->default('manuel'); // auto | manuel
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['compte_bancaire_id', 'reglement_id']);
            });
        }

        if (Schema::hasTable('fournisseurs') && !Schema::hasColumn('fournisseurs', 'solde_actuel')) {
            Schema::table('fournisseurs', function (Blueprint $table) {
                $table->decimal('solde_actuel', 15, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rapprochement_lignes');
        Schema::dropIfExists('mouvements_bancaires');
        Schema::dropIfExists('lettrage_historiques');
        Schema::dropIfExists('reglement_lignes');
        Schema::dropIfExists('reglements');
        Schema::dropIfExists('comptes_bancaires');

        if (Schema::hasTable('fournisseurs') && Schema::hasColumn('fournisseurs', 'solde_actuel')) {
            Schema::table('fournisseurs', function (Blueprint $table) {
                $table->dropColumn('solde_actuel');
            });
        }
    }
};
