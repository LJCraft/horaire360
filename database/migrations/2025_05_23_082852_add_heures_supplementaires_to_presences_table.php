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
        // Vérifier si la table presences existe
        if (Schema::hasTable('presences')) {
            // Vérifier si la colonne n'existe pas déjà
            if (!Schema::hasColumn('presences', 'heures_supplementaires')) {
                Schema::table('presences', function (Blueprint $table) {
                    $table->integer('heures_supplementaires')->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vérifier si la table presences existe
        if (Schema::hasTable('presences')) {
            // Vérifier si la colonne existe
            if (Schema::hasColumn('presences', 'heures_supplementaires')) {
                Schema::table('presences', function (Blueprint $table) {
                    $table->dropColumn('heures_supplementaires');
                });
            }
        }
    }
};
