<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exportateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ref_foodex')->nullable();
            $table->text('adresse')->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('web')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exportateurs');
    }
};
