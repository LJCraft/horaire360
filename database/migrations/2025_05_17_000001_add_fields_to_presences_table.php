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
            $table->enum('source_pointage', ['biometrique', 'manuel'])->default('manuel')->after('meta_data');
            $table->float('heures_prevues')->nullable()->after('source_pointage');
            $table->float('heures_faites')->nullable()->after('heures_prevues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            $table->dropColumn('source_pointage');
            $table->dropColumn('heures_prevues');
            $table->dropColumn('heures_faites');
        });
    }
};
