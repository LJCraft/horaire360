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
        // Créer la table departements
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Insérer les départements à partir des valeurs uniques de la colonne 'departement' de la table 'postes'
        $departements = DB::table('postes')
            ->select('departement')
            ->distinct()
            ->whereNotNull('departement')
            ->get();
            
        foreach ($departements as $dept) {
            DB::table('departements')->insert([
                'nom' => $dept->departement,
                'code' => strtoupper(substr(str_replace(' ', '', $dept->departement), 0, 3)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
