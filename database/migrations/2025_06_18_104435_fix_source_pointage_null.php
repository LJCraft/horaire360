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
        // Corriger les pointages avec source_pointage NULL qui ont des métadonnées
        // Ces pointages proviennent probablement d'imports .dat
        DB::update("
            UPDATE presences 
            SET source_pointage = 'biometrique' 
            WHERE source_pointage IS NULL 
            AND meta_data IS NOT NULL 
            AND meta_data != '{}' 
            AND meta_data != 'null'
        ");
        
        // Corriger les pointages avec source_pointage NULL qui n'ont pas de métadonnées
        // Ces pointages sont probablement des saisies manuelles
        DB::update("
            UPDATE presences 
            SET source_pointage = 'manuel' 
            WHERE source_pointage IS NULL 
            AND (meta_data IS NULL OR meta_data = '{}' OR meta_data = 'null')
        ");
        
        echo "Sources de pointage corrigées avec succès.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback nécessaire car on corrige des données incohérentes
    }
};
