<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exportateurs') && !Schema::hasColumn('exportateurs', 'nom_societe')) {
            Schema::table('exportateurs', function (Blueprint $table) {
                $table->string('nom_societe')->nullable()->after('nom');
            });
        }

        if (Schema::hasTable('exportations')) {
            Schema::table('exportations', function (Blueprint $table) {
                if (!Schema::hasColumn('exportations', 'exportateur_id')) {
                    $table->foreignId('exportateur_id')->nullable()->after('client_id')
                        ->constrained('exportateurs')->nullOnDelete();
                }
                if (!Schema::hasColumn('exportations', 'numero_swb_bl')) {
                    $table->string('numero_swb_bl')->nullable()->after('booking');
                }
                if (!Schema::hasColumn('exportations', 'exp_nom')) {
                    $table->string('exp_nom')->nullable()->after('observations');
                    $table->string('exp_nom_societe')->nullable();
                    $table->string('exp_ref_foodex')->nullable();
                    $table->text('exp_adresse')->nullable();
                    $table->string('exp_web')->nullable();
                    $table->string('exp_telephone', 50)->nullable();
                    $table->string('exp_email')->nullable();
                }
                if (!Schema::hasColumn('exportations', 'dest_nom')) {
                    $table->string('dest_nom')->nullable();
                    $table->string('dest_societe')->nullable();
                    $table->text('dest_adresse')->nullable();
                    $table->string('dest_tva')->nullable();
                    $table->string('dest_eori')->nullable();
                    $table->string('dest_contact')->nullable();
                }
            });
        }

        if (Schema::hasTable('exportation_articles')) {
            Schema::table('exportation_articles', function (Blueprint $table) {
                if (!Schema::hasColumn('exportation_articles', 'calibre')) {
                    $table->string('calibre')->nullable()->after('hs_code');
                }
                if (!Schema::hasColumn('exportation_articles', 'numero_lot')) {
                    $table->string('numero_lot')->nullable()->after('calibre');
                }
                if (!Schema::hasColumn('exportation_articles', 'date_production')) {
                    $table->date('date_production')->nullable()->after('numero_lot');
                }
                if (!Schema::hasColumn('exportation_articles', 'nb_colis')) {
                    $table->integer('nb_colis')->default(0)->after('date_production');
                }
                if (!Schema::hasColumn('exportation_articles', 'poids_brut_unitaire')) {
                    $table->decimal('poids_brut_unitaire', 14, 3)->default(0)->after('nb_colis');
                }
                if (!Schema::hasColumn('exportation_articles', 'poids_net_unitaire')) {
                    $table->decimal('poids_net_unitaire', 14, 3)->default(0)->after('poids_brut');
                }
                if (!Schema::hasColumn('exportation_articles', 'poids_net_egoutte_unitaire')) {
                    $table->decimal('poids_net_egoutte_unitaire', 14, 3)->default(0)->after('poids_egoutte');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exportation_articles')) {
            Schema::table('exportation_articles', function (Blueprint $table) {
                foreach ([
                    'calibre', 'numero_lot', 'date_production', 'nb_colis',
                    'poids_brut_unitaire', 'poids_net_unitaire', 'poids_net_egoutte_unitaire',
                ] as $col) {
                    if (Schema::hasColumn('exportation_articles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('exportations')) {
            Schema::table('exportations', function (Blueprint $table) {
                if (Schema::hasColumn('exportations', 'exportateur_id')) {
                    $table->dropConstrainedForeignId('exportateur_id');
                }
                foreach ([
                    'numero_swb_bl',
                    'exp_nom', 'exp_nom_societe', 'exp_ref_foodex', 'exp_adresse',
                    'exp_web', 'exp_telephone', 'exp_email',
                    'dest_nom', 'dest_societe', 'dest_adresse', 'dest_tva', 'dest_eori', 'dest_contact',
                ] as $col) {
                    if (Schema::hasColumn('exportations', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('exportateurs') && Schema::hasColumn('exportateurs', 'nom_societe')) {
            Schema::table('exportateurs', function (Blueprint $table) {
                $table->dropColumn('nom_societe');
            });
        }
    }
};
