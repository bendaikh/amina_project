<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('source')->nullable()->after('commercial_charge');
            $table->string('statut_prospection')->nullable()->after('statut');
            $table->text('produits_interesses')->nullable()->after('source');
            $table->date('premier_contact')->nullable()->after('produits_interesses');
            $table->date('dernier_contact')->nullable()->after('premier_contact');
            $table->string('prochaine_action')->nullable()->after('dernier_contact');
            $table->date('prochaine_action_date')->nullable()->after('prochaine_action');
            $table->timestamp('converti_at')->nullable()->after('prochaine_action_date');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'statut_prospection',
                'produits_interesses',
                'premier_contact',
                'dernier_contact',
                'prochaine_action',
                'prochaine_action_date',
                'converti_at',
            ]);
        });
    }
};
