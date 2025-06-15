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
        Schema::table('criteres_pointage', function (Blueprint $table) {
            $table->boolean('calcul_heures_sup')->default(false)->after('source_pointage');
            $table->integer('seuil_heures_sup')->default(0)->after('calcul_heures_sup');
            $table->integer('priorite')->default(2)->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('criteres_pointage', function (Blueprint $table) {
            $table->dropColumn(['calcul_heures_sup', 'seuil_heures_sup', 'priorite']);
        });
    }
};
