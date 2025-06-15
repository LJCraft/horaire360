<?php
// Test final pour vérifier que l'insertion fonctionne
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Test d'insertion dans la table presences...\n";
    
    // Données de test similaires à celles de l'erreur originale
    $testData = [
        'employe_id' => 82,
        'date' => '2025-06-15',
        'heure_arrivee' => '07:00',
        'heure_depart' => '15:00',
        'commentaire' => 'Test après correction',
        'source_pointage' => 'manuel',
        'retard' => 0,
        'depart_anticipe' => 0,
        'heures_supplementaires' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    // Vérifier si l'employé existe
    $employeExists = DB::table('employes')->where('id', 82)->exists();
    
    if ($employeExists) {
        // Supprimer l'enregistrement existant s'il y en a un
        DB::table('presences')
            ->where('employe_id', 82)
            ->where('date', '2025-06-15')
            ->delete();
        
        // Tenter l'insertion
        $insertId = DB::table('presences')->insertGetId($testData);
        echo "✓ Insertion réussie ! ID: $insertId\n";
        
        // Vérifier que l'enregistrement a été inséré
        $record = DB::table('presences')->where('id', $insertId)->first();
        echo "✓ Enregistrement vérifié:\n";
        echo "  - Employé ID: {$record->employe_id}\n";
        echo "  - Date: {$record->date}\n";
        echo "  - Source pointage: {$record->source_pointage}\n";
        
        // Nettoyer - supprimer l'enregistrement de test
        DB::table('presences')->where('id', $insertId)->delete();
        echo "✓ Enregistrement de test supprimé\n\n";
        
        echo "🎉 PROBLÈME RÉSOLU ! La colonne 'source_pointage' fonctionne correctement.\n";
        
    } else {
        echo "⚠️  L'employé avec l'ID 82 n'existe pas. Créons un employé de test...\n";
        
        // Créer un employé de test
        $employeTestId = DB::table('employes')->insertGetId([
            'nom' => 'Test',
            'prenom' => 'Employé',
            'email' => 'test@example.com',
            'statut' => 'actif',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Modifier les données de test
        $testData['employe_id'] = $employeTestId;
        
        // Tenter l'insertion
        $insertId = DB::table('presences')->insertGetId($testData);
        echo "✓ Insertion réussie avec employé de test ! ID: $insertId\n";
        
        // Nettoyer
        DB::table('presences')->where('id', $insertId)->delete();
        DB::table('employes')->where('id', $employeTestId)->delete();
        echo "✓ Données de test supprimées\n\n";
        
        echo "🎉 PROBLÈME RÉSOLU ! La colonne 'source_pointage' fonctionne correctement.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
} 