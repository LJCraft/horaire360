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
        // Vérifier si la table existe déjà
        if (Schema::hasTable('criteres_pointage')) {
            // Vérifier si la colonne n'existe pas déjà
            if (!Schema::hasColumn('criteres_pointage', 'source_pointage')) {
                Schema::table('criteres_pointage', function (Blueprint $table) {
                    $table->string('source_pointage', 20)->default('tous')->after('duree_pause');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vérifier si la table existe
        if (Schema::hasTable('criteres_pointage')) {
            // Vérifier si la colonne existe
            if (Schema::hasColumn('criteres_pointage', 'source_pointage')) {
                Schema::table('criteres_pointage', function (Blueprint $table) {
                    $table->dropColumn('source_pointage');
                });
            }
        }
    }
};
