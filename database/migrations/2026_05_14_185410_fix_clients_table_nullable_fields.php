<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('incoterm')->nullable()->change();
            $table->string('langue')->nullable()->change();
            $table->integer('delai_paiement')->nullable()->change();
            
            if (!Schema::hasColumn('clients', 'delai_paiement_type')) {
                $table->string('delai_paiement_type')->nullable()->after('delai_paiement');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('incoterm')->default('FOB')->change();
            $table->string('langue')->default('Français')->change();
            $table->integer('delai_paiement')->default(30)->change();
            
            if (Schema::hasColumn('clients', 'delai_paiement_type')) {
                $table->dropColumn('delai_paiement_type');
            }
        });
    }
};
