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
        Schema::table('biometric_sync_logs', function (Blueprint $table) {
            // Rendre le champ biometric_device_id nullable pour permettre les logs globaux
            $table->foreignId('biometric_device_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biometric_sync_logs', function (Blueprint $table) {
            // Revenir à l'état précédent (non nullable)
            $table->foreignId('biometric_device_id')->nullable(false)->change();
        });
    }
};
