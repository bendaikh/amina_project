<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exportateurs')) {
            Schema::table('exportateurs', function (Blueprint $table) {
                if (!Schema::hasColumn('exportateurs', 'banque')) {
                    $table->string('banque')->nullable()->after('web');
                }
                if (!Schema::hasColumn('exportateurs', 'beneficiaire')) {
                    $table->string('beneficiaire')->nullable()->after('banque');
                }
                if (!Schema::hasColumn('exportateurs', 'iban')) {
                    $table->string('iban', 64)->nullable()->after('beneficiaire');
                }
                if (!Schema::hasColumn('exportateurs', 'swift')) {
                    $table->string('swift', 32)->nullable()->after('iban');
                }
                if (!Schema::hasColumn('exportateurs', 'rib')) {
                    $table->string('rib', 64)->nullable()->after('swift');
                }
                if (!Schema::hasColumn('exportateurs', 'est_defaut')) {
                    $table->boolean('est_defaut')->default(false)->after('actif');
                }
            });
        }

        if (Schema::hasTable('articles') && !Schema::hasColumn('articles', 'sous_reserve_retour')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->boolean('sous_reserve_retour')->default(false)->after('type_palette');
            });
        }

        if (Schema::hasTable('exportations')) {
            Schema::table('exportations', function (Blueprint $table) {
                if (!Schema::hasColumn('exportations', 'commande_id')) {
                    $table->foreignId('commande_id')->nullable()->after('exportateur_id')
                        ->constrained('commandes')->nullOnDelete();
                }
                if (!Schema::hasColumn('exportations', 'numero_facture')) {
                    $table->string('numero_facture')->nullable()->after('numero');
                }
                if (!Schema::hasColumn('exportations', 'numero_commande')) {
                    $table->string('numero_commande')->nullable()->after('reference_commande_client');
                }
                if (!Schema::hasColumn('exportations', 'numero_liste_colisage')) {
                    $table->string('numero_liste_colisage')->nullable()->after('numero_commande');
                }
                if (!Schema::hasColumn('exportations', 'transport_applique')) {
                    $table->boolean('transport_applique')->default(false)->after('devise');
                }
                if (!Schema::hasColumn('exportations', 'montant_transport')) {
                    $table->decimal('montant_transport', 14, 2)->default(0)->after('transport_applique');
                }
                if (!Schema::hasColumn('exportations', 'assurance')) {
                    $table->string('assurance')->nullable()->after('conditions_paiement');
                }
                if (!Schema::hasColumn('exportations', 'type_emballage_doc')) {
                    $table->string('type_emballage_doc')->nullable()->after('assurance');
                }
                if (!Schema::hasColumn('exportations', 'envoi_documents')) {
                    $table->string('envoi_documents')->nullable()->after('type_emballage_doc');
                }
                if (!Schema::hasColumn('exportations', 'origine_marchandise')) {
                    $table->string('origine_marchandise')->nullable()->after('envoi_documents');
                }
                if (!Schema::hasColumn('exportations', 'transitaire')) {
                    $table->string('transitaire')->nullable()->after('origine_marchandise');
                }
                if (!Schema::hasColumn('exportations', 'banque')) {
                    $table->string('banque')->nullable();
                    $table->string('beneficiaire')->nullable();
                    $table->string('iban', 64)->nullable();
                    $table->string('swift', 32)->nullable();
                    $table->string('rib', 64)->nullable();
                }
            });
        }

        if (Schema::hasTable('exportation_articles')) {
            Schema::table('exportation_articles', function (Blueprint $table) {
                if (!Schema::hasColumn('exportation_articles', 'reference_emballage')) {
                    $table->string('reference_emballage')->nullable()->after('conditionnement');
                }
                if (!Schema::hasColumn('exportation_articles', 'nombre_par_colis')) {
                    $table->integer('nombre_par_colis')->nullable()->after('nb_colis');
                }
                if (!Schema::hasColumn('exportation_articles', 'total_emballage')) {
                    $table->integer('total_emballage')->default(0)->after('nombre_par_colis');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exportation_articles')) {
            Schema::table('exportation_articles', function (Blueprint $table) {
                foreach (['reference_emballage', 'nombre_par_colis', 'total_emballage'] as $col) {
                    if (Schema::hasColumn('exportation_articles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('exportations')) {
            Schema::table('exportations', function (Blueprint $table) {
                if (Schema::hasColumn('exportations', 'commande_id')) {
                    $table->dropConstrainedForeignId('commande_id');
                }
                foreach ([
                    'numero_facture', 'numero_commande', 'numero_liste_colisage',
                    'transport_applique', 'montant_transport', 'assurance',
                    'type_emballage_doc', 'envoi_documents', 'origine_marchandise', 'transitaire',
                    'banque', 'beneficiaire', 'iban', 'swift', 'rib',
                ] as $col) {
                    if (Schema::hasColumn('exportations', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'sous_reserve_retour')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('sous_reserve_retour');
            });
        }

        if (Schema::hasTable('exportateurs')) {
            Schema::table('exportateurs', function (Blueprint $table) {
                foreach (['banque', 'beneficiaire', 'iban', 'swift', 'rib', 'est_defaut'] as $col) {
                    if (Schema::hasColumn('exportateurs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
