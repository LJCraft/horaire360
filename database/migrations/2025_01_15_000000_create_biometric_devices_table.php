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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de l'appareil
            $table->string('brand'); // Marque (suprema, zkteco, hikvision, anviz)
            $table->string('model')->nullable(); // Modèle spécifique
            $table->enum('connection_type', ['ip', 'api']); // Type de connexion
            $table->boolean('active')->default(true); // Appareil actif
            
            // Configuration IP
            $table->string('ip_address')->nullable();
            $table->integer('port')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable(); // Chiffré
            
            // Configuration API
            $table->string('api_url')->nullable();
            $table->enum('api_method', ['GET', 'POST', 'PUT'])->nullable();
            $table->enum('auth_type', ['none', 'bearer', 'api_key', 'basic'])->nullable();
            $table->string('auth_token')->nullable(); // Chiffré
            $table->string('api_key_header')->nullable(); // Ex: X-API-Key
            $table->integer('sync_interval')->default(300); // Secondes
            $table->boolean('is_push_mode')->default(false); // Push ou Pull
            
            // Métadonnées
            $table->json('device_config')->nullable(); // Configuration spécifique au driver
            $table->json('mapping_config')->nullable(); // Mapping des champs
            
            // État de connexion
            $table->enum('connection_status', ['connected', 'disconnected', 'error', 'unknown'])->default('unknown');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_connection_test_at')->nullable();
            $table->text('last_error')->nullable();
            
            // Statistiques
            $table->integer('total_records_synced')->default(0);
            $table->integer('sync_success_count')->default(0);
            $table->integer('sync_error_count')->default(0);
            
            $table->timestamps();
            
            // Index
            $table->index(['active', 'connection_type']);
            $table->index(['brand', 'connection_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
}; 