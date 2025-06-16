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
            $table->unsignedBigInteger('employe_id')->nullable();
            $table->string('departement_id')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('periode', ['jour', 'semaine', 'mois']);
            $table->integer('nombre_pointages')->default(2); // 1 ou 2
            $table->integer('tolerance_avant')->default(10); // en minutes
            $table->integer('tolerance_apres')->default(10); // en minutes
            $table->integer('duree_pause')->default(0); // en minutes
            $table->enum('source_pointage', ['biometrique', 'manuel', 'tous'])->default('tous');
            $table->boolean('calcul_heures_sup')->default(false);
            $table->integer('seuil_heures_sup')->default(0);
            $table->integer('priorite')->default(2);
            $table->unsignedBigInteger('parent_critere_id')->nullable();
            $table->boolean('actif')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            // Contraintes et indexes
            $table->index(['niveau', 'employe_id', 'departement_id', 'date_debut', 'date_fin']);
            $table->index(['actif', 'date_debut', 'date_fin']);
            
            // Clés étrangères (ajoutées séparément pour éviter les erreurs de contraintes)
            $table->foreign('employe_id')->references('id')->on('employes')->onDelete('cascade');
            $table->foreign('departement_id')->references('departement')->on('departements')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_critere_id')->references('id')->on('criteres_pointage')->onDelete('cascade');
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
