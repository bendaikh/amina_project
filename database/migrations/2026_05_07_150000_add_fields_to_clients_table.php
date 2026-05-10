<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('code_client')->nullable()->after('id');
            $table->string('ville')->nullable()->after('adresse');
            $table->date('date_creation')->nullable()->after('statut');
            $table->string('secteur_activite')->nullable();
            $table->string('groupe_categorie')->nullable();
            $table->string('commercial_charge')->nullable();
            $table->string('site_web')->nullable();
            
            // Contact principal
            $table->string('contact_nom')->nullable();
            $table->string('contact_fonction')->nullable();
            $table->string('contact_telephone')->nullable();
            $table->string('contact_email')->nullable();
            
            // Identifiants
            $table->string('numero_tva')->nullable();
            $table->string('numero_rc')->nullable();
            $table->string('eori')->nullable();
            $table->string('pays_eori')->nullable();
            $table->string('incoterm')->default('FOB');
            $table->string('langue')->default('Français');
            $table->integer('delai_paiement')->default(30);
            
            // Financier
            $table->decimal('plafond_credit', 15, 2)->nullable();
            $table->decimal('solde_actuel', 15, 2)->nullable();
            
            // Logistique
            $table->string('mode_transport')->nullable();
            $table->text('adresse_livraison')->nullable();
            $table->text('transitaire')->nullable();
        });
        
        // Make ice_cin nullable
        Schema::table('clients', function (Blueprint $table) {
            $table->string('ice_cin')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'code_client', 'ville', 'date_creation', 'secteur_activite',
                'groupe_categorie', 'commercial_charge', 'site_web',
                'contact_nom', 'contact_fonction', 'contact_telephone', 'contact_email',
                'numero_tva', 'numero_rc', 'eori', 'pays_eori', 'incoterm', 'langue', 'delai_paiement',
                'plafond_credit', 'solde_actuel', 'mode_transport', 'adresse_livraison', 'transitaire'
            ]);
        });
    }
};
