<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'actif')) {
                $table->boolean('actif')->default(true)->after('statut');
            }
            
            // Change delai_paiement from integer to string to store the code
            if (Schema::hasColumn('clients', 'delai_paiement')) {
                $table->string('delai_paiement_type')->nullable()->after('delai_paiement');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'actif')) {
                $table->dropColumn('actif');
            }
            if (Schema::hasColumn('clients', 'delai_paiement_type')) {
                $table->dropColumn('delai_paiement_type');
            }
        });
    }
};
