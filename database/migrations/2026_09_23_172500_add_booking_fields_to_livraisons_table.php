<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('livraisons')) {
            return;
        }

        Schema::table('livraisons', function (Blueprint $table) {
            if (!Schema::hasColumn('livraisons', 'reservation_booking')) {
                $table->boolean('reservation_booking')->default(false)->after('commande_id');
            }
            if (!Schema::hasColumn('livraisons', 'numero_booking')) {
                $table->string('numero_booking')->nullable()->after('numero_reservation');
            }
            if (!Schema::hasColumn('livraisons', 'numero_bl_swb')) {
                $table->string('numero_bl_swb')->nullable()->after('numero_booking');
            }
            if (!Schema::hasColumn('livraisons', 'date_cutoff')) {
                $table->date('date_cutoff')->nullable()->after('date_chargement');
            }
            if (!Schema::hasColumn('livraisons', 'port_depart')) {
                $table->string('port_depart')->nullable()->after('navire');
            }
            if (!Schema::hasColumn('livraisons', 'port_arrivee')) {
                $table->string('port_arrivee')->nullable()->after('port_depart');
            }
            if (!Schema::hasColumn('livraisons', 'eta')) {
                $table->date('eta')->nullable()->after('port_arrivee');
            }
            if (!Schema::hasColumn('livraisons', 'etd')) {
                $table->date('etd')->nullable()->after('eta');
            }
            if (!Schema::hasColumn('livraisons', 'numero_conteneur')) {
                $table->string('numero_conteneur')->nullable()->after('etd');
            }
            if (!Schema::hasColumn('livraisons', 'tare_conteneur')) {
                $table->string('tare_conteneur')->nullable()->after('numero_conteneur');
            }
            if (!Schema::hasColumn('livraisons', 'numero_plomb')) {
                $table->string('numero_plomb')->nullable()->after('tare_conteneur');
            }
            if (!Schema::hasColumn('livraisons', 'changement_plomb')) {
                $table->boolean('changement_plomb')->default(false)->after('numero_plomb');
            }
            if (!Schema::hasColumn('livraisons', 'raison_changement_plomb')) {
                $table->text('raison_changement_plomb')->nullable()->after('changement_plomb');
            }
            if (!Schema::hasColumn('livraisons', 'nouveau_plomb')) {
                $table->string('nouveau_plomb')->nullable()->after('raison_changement_plomb');
            }
            if (!Schema::hasColumn('livraisons', 'matricule_camion')) {
                $table->string('matricule_camion')->nullable()->after('vehicule');
            }
            if (!Schema::hasColumn('livraisons', 'cin_chauffeur')) {
                $table->string('cin_chauffeur')->nullable()->after('chauffeur');
            }
            if (!Schema::hasColumn('livraisons', 'cin_chauffeur_scan')) {
                $table->string('cin_chauffeur_scan')->nullable()->after('cin_chauffeur');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('livraisons')) {
            return;
        }

        $cols = [
            'reservation_booking', 'numero_booking', 'numero_bl_swb', 'date_cutoff',
            'port_depart', 'port_arrivee', 'eta', 'etd', 'numero_conteneur',
            'tare_conteneur', 'numero_plomb', 'changement_plomb',
            'raison_changement_plomb', 'nouveau_plomb', 'matricule_camion',
            'cin_chauffeur', 'cin_chauffeur_scan',
        ];

        Schema::table('livraisons', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                if (Schema::hasColumn('livraisons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
