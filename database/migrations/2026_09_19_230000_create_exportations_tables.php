<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exportations')) {
            Schema::create('exportations', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_creation')->nullable();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('pays')->nullable();
                $table->string('destination')->nullable();
                $table->text('adresse_livraison')->nullable();
                $table->string('commercial')->nullable();
                $table->string('reference_commande_client')->nullable();
                $table->date('date_commande')->nullable();
                $table->string('incoterm')->nullable();
                $table->string('devise')->default('EUR');
                $table->string('conditions_paiement')->nullable();
                $table->enum('mode_transport', ['maritime', 'routier', 'aerien'])->nullable();
                $table->string('booking')->nullable();
                $table->string('compagnie_maritime')->nullable();
                $table->string('navire')->nullable();
                $table->string('voyage')->nullable();
                $table->string('port_chargement')->nullable();
                $table->string('port_destination')->nullable();
                $table->date('etd')->nullable();
                $table->date('eta')->nullable();
                $table->string('conteneur')->nullable();
                $table->string('type_conteneur')->nullable();
                $table->string('plomb_scelle')->nullable();
                $table->string('transporteur')->nullable();
                $table->string('chauffeur')->nullable();
                $table->string('chauffeur_cin')->nullable();
                $table->string('immatriculation')->nullable();
                $table->decimal('vgm_poids', 12, 3)->nullable();
                $table->boolean('vgm_valide')->default(false);
                $table->string('statut')->default('commande');
                $table->boolean('emballages_temporaires')->default(false);
                $table->text('observations')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'client_id']);
                $table->index('conteneur');
                $table->index('booking');
            });
        }

        if (!Schema::hasTable('exportation_articles')) {
            Schema::create('exportation_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exportation_id')->constrained('exportations')->cascadeOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->string('code_article')->nullable();
                $table->string('designation')->nullable();
                $table->string('hs_code')->nullable();
                $table->string('conditionnement')->nullable();
                $table->decimal('quantite', 14, 3)->default(0);
                $table->integer('cartons')->default(0);
                $table->integer('palettes')->default(0);
                $table->decimal('poids_net', 14, 3)->default(0);
                $table->decimal('poids_brut', 14, 3)->default(0);
                $table->decimal('poids_egoutte', 14, 3)->default(0);
                $table->decimal('prix_unitaire', 14, 4)->default(0);
                $table->string('devise')->nullable();
                $table->decimal('montant', 14, 2)->default(0);
                $table->string('origine')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dossiers_emballages')) {
            Schema::create('dossiers_emballages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exportation_id')->constrained('exportations')->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('type_emballage')->nullable();
                $table->string('type_fut')->nullable();
                $table->string('reference_fut')->nullable();
                $table->decimal('quantite_exportee', 14, 3)->default(0);
                $table->string('dum_52')->nullable();
                $table->date('date_dum')->nullable();
                $table->date('date_export')->nullable();
                $table->date('date_limite_reimportation')->nullable();
                $table->string('facture_concernee')->nullable();
                $table->string('conteneur')->nullable();
                $table->string('destination')->nullable();
                $table->string('statut')->default('ouvert');
                $table->text('observations')->nullable();
                $table->timestamps();
                $table->index('dum_52');
                $table->index('statut');
                $table->index('date_limite_reimportation');
            });
        }

        if (!Schema::hasTable('retours_emballages')) {
            Schema::create('retours_emballages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dossier_emballage_id')->constrained('dossiers_emballages')->cascadeOnDelete();
                $table->date('date_reimportation')->nullable();
                $table->string('dum_reimportation')->nullable();
                $table->decimal('quantite', 14, 3)->default(0);
                $table->string('type_emballage')->nullable();
                $table->text('observations')->nullable();
                $table->string('justificatif_path')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('documents_generes')) {
            Schema::create('documents_generes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exportation_id')->nullable()->constrained('exportations')->cascadeOnDelete();
                $table->foreignId('dossier_emballage_id')->nullable()->constrained('dossiers_emballages')->nullOnDelete();
                $table->string('type');
                $table->string('titre')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->string('chemin')->nullable();
                $table->string('categorie')->nullable();
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
                $table->index(['exportation_id', 'type']);
            });
        }

        if (!Schema::hasTable('pieces_jointes')) {
            Schema::create('pieces_jointes', function (Blueprint $table) {
                $table->id();
                $table->morphs('attachable');
                $table->string('type')->nullable();
                $table->string('nom_fichier');
                $table->string('chemin');
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('taille')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('auditable_type');
                $table->unsignedBigInteger('auditable_id');
                $table->string('champ')->nullable();
                $table->text('ancienne_valeur')->nullable();
                $table->text('nouvelle_valeur')->nullable();
                $table->string('action')->default('update');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['auditable_type', 'auditable_id']);
            });
        }

        if (!Schema::hasColumn('articles', 'origine')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('origine')->nullable()->after('devise');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('pieces_jointes');
        Schema::dropIfExists('documents_generes');
        Schema::dropIfExists('retours_emballages');
        Schema::dropIfExists('dossiers_emballages');
        Schema::dropIfExists('exportation_articles');
        Schema::dropIfExists('exportations');

        if (Schema::hasColumn('articles', 'origine')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('origine');
            });
        }
    }
};
