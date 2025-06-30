<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST SYNCHRONISATION INTÉGRATION ===\n";

try {
    // Étape 1: Récupérer les données de l'API mobile
    echo "1. Récupération des données de l'API mobile...\n";
    
    $apiUrl = 'https://apitface.onrender.com/pointages?nameEntreprise=Pop';
    $response = file_get_contents($apiUrl);
    
    if ($response === false) {
        throw new Exception("Impossible de récupérer les données de l'API");
    }
    
    $apiData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur JSON: " . json_last_error_msg());
    }
    
    echo "   ✓ Données récupérées: " . count($apiData['pointages'] ?? []) . " pointages\n";
    
    // Étape 2: Préparer les données pour la synchronisation
    echo "2. Préparation des données...\n";
    
    $formattedData = [
        'pointages' => $apiData['pointages'] ?? [],
        'source_app' => 'apitface_mobile_production',
        'version' => '1.0.0'
    ];
    
    echo "   ✓ Format préparé avec " . count($formattedData['pointages']) . " pointages\n";
    
    // Étape 3: Appeler le contrôleur de synchronisation
    echo "3. Appel du contrôleur de synchronisation...\n";
    
    $controller = new \App\Http\Controllers\Api\SynchronisationBiometriqueController();
    
    // Créer une requête simulée
    $request = new Request();
    $request->merge($formattedData);
    
    $result = $controller->synchroniserMobile($request);
    $responseData = $result->getData(true);
    
    echo "   ✓ Synchronisation terminée\n";
    
    // Étape 4: Analyser les résultats
    echo "4. Résultats de synchronisation:\n";
    echo "   - Statut: " . $responseData['status'] . "\n";
    echo "   - Message: " . $responseData['message'] . "\n";
    echo "   - Reçus: " . $responseData['received'] . "\n";
    echo "   - Insérés: " . $responseData['inserted'] . "\n";
    echo "   - Mis à jour: " . $responseData['updated'] . "\n";
    echo "   - Ignorés: " . $responseData['ignored'] . "\n";
    echo "   - Erreurs: " . (is_array($responseData['errors']) ? count($responseData['errors']) : $responseData['errors']) . "\n";
    
    if (!empty($responseData['errors']) && is_array($responseData['errors'])) {
        echo "   Détails des erreurs:\n";
        foreach ($responseData['errors'] as $error) {
            if (is_array($error) && isset($error['line']) && isset($error['reason'])) {
                echo "     - Ligne {$error['line']}: {$error['reason']}\n";
            } else {
                echo "     - " . (is_array($error) ? json_encode($error) : $error) . "\n";
            }
        }
    }
    
    // Étape 5: Tester l'interface web
    echo "5. Test de l'interface web (RapportController)...\n";
    
    $rapportController = new \App\Http\Controllers\RapportController();
    $webRequest = new Request();
    
    $webResult = $rapportController->synchronizeAllDevices($webRequest);
    $webData = $webResult->getData(true);
    
    echo "   ✓ Interface web testée\n";
    echo "   - Succès: " . ($webData['success'] ? 'OUI' : 'NON') . "\n";
    echo "   - Message: " . $webData['message'] . "\n";
    echo "   - Appareils synchronisés: " . $webData['synchronized_devices'] . "\n";
    echo "   - Enregistrements traités: " . $webData['processed_records'] . "\n";
    
    echo "\n=== TEST TERMINÉ AVEC SUCCÈS ===\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 