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
        Schema::table('biometric_devices', function (Blueprint $table) {
            // Augmenter la taille des colonnes qui peuvent contenir du texte chiffré
            $table->text('password')->nullable()->change();
            $table->text('auth_token')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            // Remettre en string pour rollback
            $table->string('password')->nullable()->change();
            $table->string('auth_token')->nullable()->change();
        });
    }
};
