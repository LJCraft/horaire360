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
            // Champ pour stocker le statut de traitement des critères
            $table->enum('criteria_processing_status', [
                'not_processed',
                'fully_processed', 
                'partially_processed',
                'pending_planning',
                'criteria_error',
                'no_criteria',
                'reprocessing_required'
            ])->default('not_processed')->after('heures_supplementaires');
            
            // Timestamp du dernier traitement des critères
            $table->timestamp('criteria_processed_at')->nullable()->after('criteria_processing_status');
            
            // Version des critères appliqués (pour le retraitement)
            $table->string('criteria_version', 50)->nullable()->after('criteria_processed_at');
            
            // Index pour les requêtes de monitoring
            $table->index(['criteria_processing_status', 'criteria_processed_at'], 'idx_criteria_processing');
            $table->index(['employe_id', 'criteria_processing_status'], 'idx_employe_criteria_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            $table->dropIndex('idx_criteria_processing');
            $table->dropIndex('idx_employe_criteria_status');
            $table->dropColumn([
                'criteria_processing_status',
                'criteria_processed_at', 
                'criteria_version'
            ]);
        });
    }
};
