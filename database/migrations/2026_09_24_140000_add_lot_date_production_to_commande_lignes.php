<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commande_lignes')) {
            return;
        }

        Schema::table('commande_lignes', function (Blueprint $table) {
            if (!Schema::hasColumn('commande_lignes', 'lot')) {
                $table->string('lot')->nullable()->after('tva_taux');
            }
            if (!Schema::hasColumn('commande_lignes', 'date_production')) {
                $table->date('date_production')->nullable()->after('lot');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('commande_lignes')) {
            return;
        }

        Schema::table('commande_lignes', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('commande_lignes', 'lot')) {
                $cols[] = 'lot';
            }
            if (Schema::hasColumn('commande_lignes', 'date_production')) {
                $cols[] = 'date_production';
            }
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
