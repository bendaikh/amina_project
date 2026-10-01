<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exportateurs') && !Schema::hasColumn('exportateurs', 'agence')) {
            Schema::table('exportateurs', function (Blueprint $table) {
                $table->string('agence')->nullable();
            });
        }

        if (Schema::hasTable('exportations') && !Schema::hasColumn('exportations', 'agence')) {
            Schema::table('exportations', function (Blueprint $table) {
                $table->string('agence')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exportations') && Schema::hasColumn('exportations', 'agence')) {
            Schema::table('exportations', function (Blueprint $table) {
                $table->dropColumn('agence');
            });
        }
        if (Schema::hasTable('exportateurs') && Schema::hasColumn('exportateurs', 'agence')) {
            Schema::table('exportateurs', function (Blueprint $table) {
                $table->dropColumn('agence');
            });
        }
    }
};
