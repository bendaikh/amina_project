<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Emplacements: keep existing stock_locations; ensure quarantine exists
        if (Schema::hasTable('stock_locations')) {
            $exists = DB::table('stock_locations')->where('code', 'quarantaine')->exists();
            if (!$exists) {
                DB::table('stock_locations')->insert([
                    'code' => 'quarantaine',
                    'nom' => 'Quarantaine',
                    'type' => 'quarantaine',
                    'actif' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $existsTransit = DB::table('stock_locations')->where('code', 'transit')->exists();
            if (!$existsTransit) {
                DB::table('stock_locations')->insert([
                    'code' => 'transit',
                    'nom' => 'Transit',
                    'type' => 'transit',
                    'actif' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Rebuild balances to match cahier situation
        if (Schema::hasTable('stock_balances')) {
            Schema::drop('stock_balances');
        }
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->string('lot')->default('');
            $table->decimal('stock_initial', 14, 3)->default(0);
            $table->decimal('entrees', 14, 3)->default(0);
            $table->decimal('sorties', 14, 3)->default(0);
            $table->decimal('stock_theorique', 14, 3)->default(0);
            $table->decimal('stock_reserve', 14, 3)->default(0);
            $table->decimal('quarantaine', 14, 3)->default(0);
            $table->decimal('endommage', 14, 3)->default(0);
            $table->decimal('transit', 14, 3)->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'stock_location_id', 'lot'], 'stock_balances_unique');
            $table->index(['article_id']);
        });

        // Rebuild movements to match cahier
        if (Schema::hasTable('stock_movements')) {
            Schema::drop('stock_movements');
        }
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->date('date_mouvement');
            $table->string('type', 40);
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->decimal('quantite', 14, 3);
            $table->string('unite')->nullable();
            $table->string('lot')->nullable();
            $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('document_type')->nullable();
            $table->string('document_ref')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->boolean('annule')->default(false);
            $table->timestamps();
            $table->index(['type', 'date_mouvement']);
            $table->index(['article_id', 'lot']);
            $table->index(['document_type', 'document_id']);
        });

        if (!Schema::hasTable('bons_reception')) {
            Schema::create('bons_reception', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->date('date_reception')->nullable();
                $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
                $table->foreignId('achat_id')->nullable()->constrained('achats')->nullOnDelete();
                $table->foreignId('achat_reception_id')->nullable()->constrained('achat_receptions')->nullOnDelete();
                $table->string('reference_achat')->nullable();
                $table->string('reference_commande')->nullable();
                $table->string('reference_facture')->nullable();
                $table->string('statut')->default('brouillon'); // brouillon, valide, annule
                $table->text('observations')->nullable();
                $table->boolean('entree_stock_generee')->default(false);
                $table->timestamp('valide_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('valide_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'date_reception', 'fournisseur_id']);
            });
        }

        if (!Schema::hasTable('bon_reception_lignes')) {
            Schema::create('bon_reception_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bon_reception_id')->constrained('bons_reception')->cascadeOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->string('designation')->nullable();
                $table->decimal('quantite_commandee', 14, 3)->default(0);
                $table->decimal('quantite_recue', 14, 3)->default(0);
                $table->string('unite')->nullable();
                $table->string('lot')->nullable();
                $table->date('date_production')->nullable();
                $table->date('date_peremption')->nullable();
                $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
                $table->string('controle_qualite')->default('ok'); // ok, quarantaine, refuse, endommage
                $table->text('observations')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventaires')) {
            Schema::create('inventaires', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->string('libelle')->nullable();
                $table->date('date_inventaire')->nullable();
                $table->string('statut')->default('brouillon'); // brouillon, en_cours, valide, annule
                $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
                $table->text('observations')->nullable();
                $table->timestamp('valide_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('valide_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['statut', 'date_inventaire']);
            });
        }

        if (!Schema::hasTable('inventaire_lignes')) {
            Schema::create('inventaire_lignes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventaire_id')->constrained('inventaires')->cascadeOnDelete();
                $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
                $table->foreignId('stock_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
                $table->string('lot')->nullable();
                $table->decimal('quantite_theorique', 14, 3)->default(0);
                $table->decimal('quantite_physique', 14, 3)->nullable();
                $table->decimal('ecart', 14, 3)->default(0);
                $table->text('observations')->nullable();
                $table->timestamps();
                $table->index(['inventaire_id', 'article_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaire_lignes');
        Schema::dropIfExists('inventaires');
        Schema::dropIfExists('bon_reception_lignes');
        Schema::dropIfExists('bons_reception');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');

        // Restore minimal legacy shapes
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->integer('quantite_pcs')->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'stock_location_id']);
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->integer('quantite_pcs');
            $table->nullableMorphs('reference');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('raison')->nullable();
            $table->boolean('annule')->default(false);
            $table->timestamps();
        });
    }
};
