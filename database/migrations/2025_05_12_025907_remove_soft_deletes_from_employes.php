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
            // Supprimer la colonne deleted_at de la table employes
            if (Schema::hasColumn('employes', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            // Recréer la colonne deleted_at si nécessaire
            if (!Schema::hasColumn('employes', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }
};
