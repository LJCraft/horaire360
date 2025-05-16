<?php

// Charger l'environnement Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Poste;

// Récupérer quelques postes pour vérification
$postes = Poste::take(5)->get();

echo "=== Vérification des grades des postes ===\n\n";

foreach ($postes as $poste) {
    $grades = json_decode($poste->grades_disponibles) ?? [];
    
    echo "Poste: {$poste->nom} ({$poste->departement})\n";
    echo "Nombre de grades: " . count($grades) . "\n";
    
    if (!empty($grades)) {
        echo "Grades: " . implode(', ', $grades) . "\n";
    } else {
        echo "Aucun grade défini pour ce poste.\n";
    }
    
    echo "\n-------------------\n\n";
}

// Vérifier si des postes n'ont pas de grades
$postesWithoutGrades = Poste::whereNull('grades_disponibles')
    ->orWhere('grades_disponibles', '[]')
    ->orWhere('grades_disponibles', '')
    ->get();

echo "Postes sans grades: " . count($postesWithoutGrades) . "\n";

if (count($postesWithoutGrades) > 0) {
    echo "Liste des postes sans grades:\n";
    foreach ($postesWithoutGrades as $poste) {
        echo "- {$poste->nom} ({$poste->departement})\n";
    }
}
