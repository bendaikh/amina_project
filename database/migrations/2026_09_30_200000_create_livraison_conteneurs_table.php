<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('livraison_conteneurs')) {
            Schema::create('livraison_conteneurs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('livraison_id')->constrained('livraisons')->cascadeOnDelete();
                $table->unsignedInteger('ordre')->default(0);
                $table->string('numero_conteneur')->nullable();
                $table->string('tare_conteneur')->nullable();
                $table->string('numero_plomb')->nullable();
                $table->boolean('changement_plomb')->default(false);
                $table->text('raison_changement_plomb')->nullable();
                $table->string('nouveau_plomb')->nullable();
                $table->string('matricule_camion')->nullable();
                $table->string('chauffeur')->nullable();
                $table->string('transporteur')->nullable();
                $table->string('cin_chauffeur')->nullable();
                $table->string('cin_chauffeur_scan')->nullable();
                $table->timestamps();

                $table->index(['livraison_id', 'ordre']);
            });
        }

        if (!Schema::hasTable('livraisons') || !Schema::hasTable('livraison_conteneurs')) {
            return;
        }

        // Migrer les données conteneur/transport existantes vers la nouvelle table
        $rows = DB::table('livraisons')
            ->select([
                'id',
                'numero_conteneur',
                'tare_conteneur',
                'numero_plomb',
                'changement_plomb',
                'raison_changement_plomb',
                'nouveau_plomb',
                'matricule_camion',
                'chauffeur',
                'transporteur',
                'cin_chauffeur',
                'cin_chauffeur_scan',
            ])
            ->get();

        foreach ($rows as $row) {
            $hasData = collect([
                $row->numero_conteneur,
                $row->tare_conteneur,
                $row->numero_plomb,
                $row->matricule_camion,
                $row->chauffeur,
                $row->transporteur,
                $row->cin_chauffeur,
                $row->cin_chauffeur_scan,
            ])->filter(fn ($v) => filled($v))->isNotEmpty();

            if (!$hasData && empty($row->changement_plomb)) {
                continue;
            }

            $exists = DB::table('livraison_conteneurs')
                ->where('livraison_id', $row->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('livraison_conteneurs')->insert([
                'livraison_id' => $row->id,
                'ordre' => 0,
                'numero_conteneur' => $row->numero_conteneur,
                'tare_conteneur' => $row->tare_conteneur,
                'numero_plomb' => $row->numero_plomb,
                'changement_plomb' => (bool) $row->changement_plomb,
                'raison_changement_plomb' => $row->raison_changement_plomb,
                'nouveau_plomb' => $row->nouveau_plomb,
                'matricule_camion' => $row->matricule_camion,
                'chauffeur' => $row->chauffeur,
                'transporteur' => $row->transporteur,
                'cin_chauffeur' => $row->cin_chauffeur,
                'cin_chauffeur_scan' => $row->cin_chauffeur_scan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('livraison_conteneurs');
    }
};
