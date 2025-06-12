<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('criteres_pointage', function (Blueprint $table) {
            $table->id();
            $table->enum('niveau', ['individuel', 'departemental']);
            $table->foreignId('employe_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('departement_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('periode', ['jour', 'semaine', 'mois']);
            $table->integer('nombre_pointages')->default(2); // 1 ou 2
            $table->integer('tolerance_avant')->default(10); // en minutes
            $table->integer('tolerance_apres')->default(10); // en minutes
            $table->integer('duree_pause')->default(0); // en minutes
            $table->boolean('actif')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Contraintes
            $table->index(['niveau', 'employe_id', 'departement_id', 'date_debut', 'date_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteres_pointage');
    }
};
