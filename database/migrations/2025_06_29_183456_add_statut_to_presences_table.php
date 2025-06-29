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
        Schema::table('presences', function (Blueprint $table) {
            // Ajouter seulement le champ manquant
            if (!Schema::hasColumn('presences', 'heures_travaillees')) {
                $table->decimal('heures_travaillees', 5, 2)->nullable()->after('heures_supplementaires')
                    ->comment('Nombre total d\'heures travaillées dans la journée');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            if (Schema::hasColumn('presences', 'heures_travaillees')) {
                $table->dropColumn('heures_travaillees');
            }
        });
    }
};
