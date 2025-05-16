<?php

// Charger l'environnement Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Poste;

// Récupérer tous les postes
$postes = Poste::all();

echo "=== Vérification des grades des postes ===\n\n";

foreach ($postes as $poste) {
    echo "ID: {$poste->id}, Nom: {$poste->nom}, Département: {$poste->departement}\n";
    
    // Récupérer et décoder les grades
    $gradesJson = $poste->grades_disponibles;
    $grades = json_decode($gradesJson) ?? [];
    
    echo "Grades JSON: " . $gradesJson . "\n";
    echo "Nombre de grades: " . count($grades) . "\n";
    
    if (count($grades) > 0) {
        echo "Premiers grades: " . implode(', ', array_slice($grades, 0, 5)) . "...\n";
    } else {
        echo "ATTENTION: Aucun grade défini pour ce poste.\n";
    }
    
    echo "\n-------------------\n\n";
}
