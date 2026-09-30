<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reclamations')) {
            Schema::create('reclamations', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_reclamation')->nullable();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('origine')->default('directe'); // commande|bl|facture|retour|vente_locale|vente_export|article|client|directe
                $table->string('document_origine')->nullable();
                $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->string('article_libelle')->nullable();
                $table->decimal('quantite', 14, 3)->nullable();
                $table->string('numero_lot')->nullable();
                $table->string('motif')->nullable();
                $table->text('description')->nullable();
                $table->string('priorite')->default('normale'); // basse|normale|haute|urgente
                $table->string('responsable')->nullable();
                $table->date('date_limite')->nullable();
                $table->string('action_prevue')->nullable(); // remplacement|retour|avoir|remboursement|analyse_qualite|correction_logistique|correction_facturation|information_client
                $table->text('action_corrective')->nullable();
                $table->text('reponse')->nullable();
                $table->string('statut')->default('nouvelle');
                $table->date('date_resolution')->nullable();
                $table->date('date_cloture')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'priorite', 'client_id', 'date_reclamation']);
            });
        }

        if (!Schema::hasTable('notes_credit')) {
            Schema::create('notes_credit', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_note')->nullable();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('origine')->default('directe'); // facture|retour|reclamation|correction_commerciale|erreur_prix|annulation_partielle|directe
                $table->string('facture_origine')->nullable();
                $table->foreignId('reclamation_id')->nullable()->constrained('reclamations')->nullOnDelete();
                $table->foreignId('commande_id')->nullable()->constrained('commandes')->nullOnDelete();
                $table->string('retour_ref')->nullable();
                $table->string('motif')->nullable(); // retour|erreur_facturation|remise|non_conformite|annulation|correction_prix|geste_commercial|autre
                $table->string('devise')->default('MAD');
                $table->text('observations')->nullable();
                $table->string('statut')->default('brouillon'); // brouillon|validee|appliquee|annulee
                $table->decimal('total_ht', 14, 2)->default(0);
                $table->decimal('total_tva', 14, 2)->default(0);
                $table->decimal('total_ttc', 14, 2)->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'client_id', 'date_note', 'motif']);
            });
        }

        if (!Schema::hasTable('note_credit_lignes')) {
            Schema::create('note_credit_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('note_credit_id')->constrained('notes_credit')->cascadeOnDelete();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('note_credit_lignes');
        Schema::dropIfExists('notes_credit');
        Schema::dropIfExists('reclamations');
    }
};
