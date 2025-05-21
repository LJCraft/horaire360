<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pour la table employes
        DB::statement('CREATE INDEX employes_nom_prenom_index ON employes (nom(100), prenom(100))');
        DB::statement('CREATE INDEX employes_email_index ON employes (email(100))');
        Schema::table('employes', function (Blueprint $table) {
            $table->index('matricule', 'employes_matricule_index');
            $table->index('statut', 'employes_statut_index');
            $table->index('poste_id', 'employes_poste_id_index');
        });

        // Pour la table presences
        Schema::table('presences', function (Blueprint $table) {
            $table->index('date', 'presences_date_index');
            $table->index(['employe_id', 'date'], 'presences_employe_date_index');
            $table->index('retard', 'presences_retard_index');
            $table->index('depart_anticipe', 'presences_depart_anticipe_index');
        });

        // Pour la table plannings
        Schema::table('plannings', function (Blueprint $table) {
            $table->index(['employe_id', 'date_debut', 'date_fin'], 'plannings_employe_dates_index');
            $table->index('actif', 'plannings_actif_index');
        });
    }

    public function down(): void
    {
        // Pour la table employes
        DB::statement('DROP INDEX employes_nom_prenom_index ON employes');
        DB::statement('DROP INDEX employes_email_index ON employes');
        Schema::table('employes', function (Blueprint $table) {
            $table->dropIndex('employes_matricule_index');
            $table->dropIndex('employes_statut_index');
            $table->dropIndex('employes_poste_id_index');
        });

        // Pour la table presences
        Schema::table('presences', function (Blueprint $table) {
            $table->dropIndex('presences_date_index');
            $table->dropIndex('presences_employe_date_index');
            $table->dropIndex('presences_retard_index');
            $table->dropIndex('presences_depart_anticipe_index');
        });

        // Pour la table plannings
        Schema::table('plannings', function (Blueprint $table) {
            $table->dropIndex('plannings_employe_dates_index');
            $table->dropIndex('plannings_actif_index');
        });
    }
};