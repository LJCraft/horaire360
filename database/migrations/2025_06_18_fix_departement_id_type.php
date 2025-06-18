<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la table existe
        if (Schema::hasTable('criteres_pointage')) {
            // Supprimer les contraintes de clé étrangère existantes si elles existent
            try {
                Schema::table('criteres_pointage', function (Blueprint $table) {
                    $table->dropForeign(['departement_id']);
                });
            } catch (\Exception $e) {
                // Ignorer si la contrainte n'existe pas
            }

            // Modifier le type de colonne de integer à string
            DB::statement('ALTER TABLE criteres_pointage MODIFY COLUMN departement_id VARCHAR(255) NULL');
            
            // Optionnellement, ajouter un index sur la colonne
            Schema::table('criteres_pointage', function (Blueprint $table) {
                $table->index('departement_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('criteres_pointage')) {
            // Remettre le type en integer si nécessaire
            DB::statement('ALTER TABLE criteres_pointage MODIFY COLUMN departement_id INT NULL');
        }
    }
}; 