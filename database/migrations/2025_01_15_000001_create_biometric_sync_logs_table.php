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
        Schema::create('biometric_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->onDelete('cascade');
            
            // Informations de synchronisation
            $table->string('sync_session_id'); // ID unique de session
            $table->enum('sync_type', ['manual', 'scheduled', 'push']); // Type de sync
            $table->enum('status', ['started', 'success', 'partial', 'failed']); // Statut
            
            // Statistiques
            $table->integer('records_processed')->default(0);
            $table->integer('records_inserted')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_ignored')->default(0);
            $table->integer('records_errors')->default(0);
            
            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // Durée en secondes
            
            // Détails et erreurs
            $table->text('summary')->nullable(); // Résumé de la synchronisation
            $table->json('error_details')->nullable(); // Détails des erreurs
            $table->json('sync_metadata')->nullable(); // Métadonnées additionnelles
            
            // Informations de la requête
            $table->string('initiated_by')->nullable(); // User qui a lancé la sync
            $table->ipAddress('client_ip')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['biometric_device_id', 'status']);
            $table->index(['sync_session_id']);
            $table->index(['started_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_sync_logs');
    }
}; 