<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exportateurs')) {
            return;
        }

        Schema::table('exportateurs', function (Blueprint $table) {
            $after = Schema::hasColumn('exportateurs', 'adresse') ? 'adresse' : 'nom';
            $cols = [
                'ville' => fn () => $table->string('ville')->nullable()->after($after),
                'pays' => fn () => $table->string('pays', 100)->nullable(),
                'forme_juridique' => fn () => $table->string('forme_juridique', 50)->nullable(),
                'capital' => fn () => $table->string('capital', 50)->nullable(),
                'ice' => fn () => $table->string('ice', 32)->nullable(),
                'identifiant_fiscal' => fn () => $table->string('identifiant_fiscal', 32)->nullable(),
                'rc' => fn () => $table->string('rc', 32)->nullable(),
                'patente' => fn () => $table->string('patente', 32)->nullable(),
                'agrement_eacce' => fn () => $table->string('agrement_eacce', 50)->nullable(),
            ];
            foreach ($cols as $name => $add) {
                if (!Schema::hasColumn('exportateurs', $name)) {
                    $add();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('exportateurs')) {
            return;
        }

        Schema::table('exportateurs', function (Blueprint $table) {
            foreach ([
                'ville', 'pays', 'forme_juridique', 'capital', 'ice',
                'identifiant_fiscal', 'rc', 'patente', 'agrement_eacce',
            ] as $col) {
                if (Schema::hasColumn('exportateurs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
