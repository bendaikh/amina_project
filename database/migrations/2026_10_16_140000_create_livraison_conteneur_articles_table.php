<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('livraison_conteneurs') && !Schema::hasColumn('livraison_conteneurs', 'date_enlevement')) {
            Schema::table('livraison_conteneurs', function (Blueprint $table) {
                $table->date('date_enlevement')->nullable()->after('tare_conteneur');
            });
        }

        if (!Schema::hasTable('livraison_conteneur_articles')) {
            Schema::create('livraison_conteneur_articles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('livraison_conteneur_id')->constrained('livraison_conteneurs')->cascadeOnDelete();
                $table->foreignId('commande_ligne_id')->nullable()->constrained('commande_lignes')->nullOnDelete();
                $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
                $table->unsignedInteger('cartons')->default(0);
                $table->unsignedInteger('palettes')->default(0);
                $table->timestamps();
                $table->index(['commande_ligne_id']);
            });
        }
    }

    public function down(): void
    {
        // Additive only: no destructive rollback.
    }
};
