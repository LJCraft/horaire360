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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->date('date');
            $table->time('heure_arrivee');
            $table->time('heure_depart')->nullable();
            $table->boolean('retard')->default(false);
            $table->boolean('depart_anticipe')->default(false);
            $table->text('commentaire')->nullable();
            $table->timestamps();
            
            // Ajouter un index composite pour la date et l'employé
            $table->index(['employe_id', 'date']);
            // Ajouter un index unique pour éviter les doublons
            $table->unique(['employe_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};