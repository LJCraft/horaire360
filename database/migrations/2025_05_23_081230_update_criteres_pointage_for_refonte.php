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
        Schema::table('criteres_pointage', function (Blueprint $table) {
            // Vérifier si la colonne source_pointage existe, sinon l'ajouter
            if (!Schema::hasColumn('criteres_pointage', 'source_pointage')) {
                $table->string('source_pointage')->default('tous')->after('duree_pause');
            }
            
            // Ajouter les nouveaux champs pour la refonte
            if (!Schema::hasColumn('criteres_pointage', 'calcul_heures_sup')) {
                $table->boolean('calcul_heures_sup')->default(false)->after('source_pointage');
            }
            
            if (!Schema::hasColumn('criteres_pointage', 'seuil_heures_sup')) {
                $table->integer('seuil_heures_sup')->default(0)->after('calcul_heures_sup');
            }
            
            if (!Schema::hasColumn('criteres_pointage', 'priorite')) {
                $table->integer('priorite')->default(2)->after('created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('criteres_pointage', function (Blueprint $table) {
            // Supprimer les colonnes ajoutées si elles existent
            $columns = ['calcul_heures_sup', 'seuil_heures_sup', 'priorite'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('criteres_pointage', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
