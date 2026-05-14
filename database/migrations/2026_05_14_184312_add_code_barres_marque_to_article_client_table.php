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
        Schema::table('article_client', function (Blueprint $table) {
            $table->string('code_barres')->nullable()->after('prix_negocie');
            $table->string('marque')->nullable()->after('code_barres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_client', function (Blueprint $table) {
            $table->dropColumn(['code_barres', 'marque']);
        });
    }
};
