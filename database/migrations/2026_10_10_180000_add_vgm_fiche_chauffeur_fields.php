<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('livraisons')) {
            Schema::table('livraisons', function (Blueprint $table) {
                if (!Schema::hasColumn('livraisons', 'voyage')) {
                    $table->string('voyage')->nullable()->after('navire');
                }
                if (!Schema::hasColumn('livraisons', 'terminal')) {
                    $table->string('terminal')->nullable()->after('port_depart');
                }
                if (!Schema::hasColumn('livraisons', 'lieu_chargement')) {
                    $table->string('lieu_chargement')->nullable()->after('adresse');
                }
                if (!Schema::hasColumn('livraisons', 'telephone_chauffeur')) {
                    $table->string('telephone_chauffeur')->nullable()->after('cin_chauffeur');
                }
                if (!Schema::hasColumn('livraisons', 'arrivee_usine')) {
                    $table->dateTime('arrivee_usine')->nullable()->after('lieu_chargement');
                }
                if (!Schema::hasColumn('livraisons', 'depart_usine')) {
                    $table->dateTime('depart_usine')->nullable()->after('arrivee_usine');
                }
            });
        }

        if (Schema::hasTable('livraison_conteneurs')) {
            Schema::table('livraison_conteneurs', function (Blueprint $table) {
                if (!Schema::hasColumn('livraison_conteneurs', 'type_conteneur')) {
                    $table->string('type_conteneur', 10)->nullable()->after('numero_conteneur');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'telephone_chauffeur')) {
                    $table->string('telephone_chauffeur')->nullable()->after('cin_chauffeur');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'vgm_methode')) {
                    $table->string('vgm_methode', 1)->nullable()->after('tare_conteneur');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'poids_brut_marchandise')) {
                    $table->decimal('poids_brut_marchandise', 12, 3)->nullable()->after('vgm_methode');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'vgm_poids')) {
                    $table->decimal('vgm_poids', 12, 3)->nullable()->after('poids_brut_marchandise');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'lieu_chargement')) {
                    $table->string('lieu_chargement')->nullable()->after('transporteur');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'arrivee_usine')) {
                    $table->dateTime('arrivee_usine')->nullable()->after('lieu_chargement');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'depart_usine')) {
                    $table->dateTime('depart_usine')->nullable()->after('arrivee_usine');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'ctrl_conteneur')) {
                    $table->boolean('ctrl_conteneur')->default(false)->after('depart_usine');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'ctrl_plomb')) {
                    $table->boolean('ctrl_plomb')->default(false)->after('ctrl_conteneur');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'ctrl_documents')) {
                    $table->boolean('ctrl_documents')->default(false)->after('ctrl_plomb');
                }
                if (!Schema::hasColumn('livraison_conteneurs', 'observations_controle')) {
                    $table->text('observations_controle')->nullable()->after('ctrl_documents');
                }
            });
        }

        if (Schema::hasTable('documents_generes') && !Schema::hasColumn('documents_generes', 'reference')) {
            Schema::table('documents_generes', function (Blueprint $table) {
                $table->string('reference')->nullable()->after('type');
                $table->index(['exportation_id', 'type', 'reference']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('documents_generes') && Schema::hasColumn('documents_generes', 'reference')) {
            Schema::table('documents_generes', function (Blueprint $table) {
                $table->dropIndex(['exportation_id', 'type', 'reference']);
                $table->dropColumn('reference');
            });
        }

        if (Schema::hasTable('livraison_conteneurs')) {
            Schema::table('livraison_conteneurs', function (Blueprint $table) {
                foreach ([
                    'type_conteneur', 'telephone_chauffeur', 'vgm_methode', 'poids_brut_marchandise',
                    'vgm_poids', 'lieu_chargement', 'arrivee_usine', 'depart_usine',
                    'ctrl_conteneur', 'ctrl_plomb', 'ctrl_documents', 'observations_controle',
                ] as $col) {
                    if (Schema::hasColumn('livraison_conteneurs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('livraisons')) {
            Schema::table('livraisons', function (Blueprint $table) {
                foreach ([
                    'voyage', 'terminal', 'lieu_chargement', 'telephone_chauffeur',
                    'arrivee_usine', 'depart_usine',
                ] as $col) {
                    if (Schema::hasColumn('livraisons', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
