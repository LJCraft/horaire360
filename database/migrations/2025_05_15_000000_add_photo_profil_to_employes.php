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
        Schema::table('employes', function (Blueprint $table) {
            if (!Schema::hasColumn('employes', 'photo_profil')) {
                $table->string('photo_profil')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            if (Schema::hasColumn('employes', 'photo_profil')) {
                $table->dropColumn('photo_profil');
            }
        });
    }
}; 