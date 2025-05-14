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
        // Ajouter des index pour améliorer les performances avec 1000+ employés
        Schema::table('employes', function (Blueprint $table) {
            // Index pour les recherches fréquentes
            $table->index(['nom', 'prenom']);
            $table->index('email');
            $table->index('matricule');
            $table->index('statut');
            $table->index('poste_id');
        });
        
        Schema::table('presences', function (Blueprint $table) {
            // Index pour les requêtes de présence
            $table->index('date');
            $table->index(['employe_id', 'date']);
            $table->index('retard');
            $table->index('depart_anticipe');
        });
        
        Schema::table('plannings', function (Blueprint $table) {
            $table->index(['employe_id', 'date_debut', 'date_fin']);
            $table->index('actif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropIndex(['nom', 'prenom']);
            $table->dropIndex(['email']);
            $table->dropIndex(['matricule']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['poste_id']);
        });
        
        Schema::table('presences', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['employe_id', 'date']);
            $table->dropIndex(['retard']);
            $table->dropIndex(['depart_anticipe']);
        });
        
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropIndex(['employe_id', 'date_debut', 'date_fin']);
            $table->dropIndex(['actif']);
        });
    }
}; 