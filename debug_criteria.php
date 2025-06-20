<?php

/**
 * Script de debug pour identifier les erreurs dans les validateurs
 */

require_once 'vendor/autoload.php';

use App\Services\CriteriaEngine\CriteriaEngine;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\CriterePointage;
use App\Models\Planning;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG DU MOTEUR DE CRITÈRES ===\n\n";

try {
    // Récupérer l'employé de test existant
    $employe = Employe::where('email', 'test.planning@example.com')->first();
    if (!$employe) {
        echo "❌ Employé de test non trouvé\n";
        exit(1);
    }
    echo "✅ Employé trouvé: {$employe->nom} {$employe->prenom} (ID: {$employe->id})\n";

    // Récupérer le planning
    $planning = Planning::where('employe_id', $employe->id)->first();
    if (!$planning) {
        echo "❌ Planning non trouvé\n";
        exit(1);
    }
    echo "✅ Planning trouvé (ID: {$planning->id})\n";
    echo "   - Heure début: {$planning->heure_debut}\n";
    echo "   - Heure fin: {$planning->heure_fin}\n";
    echo "   - Jours travail: {$planning->jours_travail}\n";

    // Récupérer le critère
    $critere = CriterePointage::where('employe_id', $employe->id)->first();
    if (!$critere) {
        echo "❌ Critère non trouvé\n";
        exit(1);
    }
    echo "✅ Critère trouvé (ID: {$critere->id})\n";

    // Récupérer un pointage
    $pointage = Presence::where('employe_id', $employe->id)->first();
    if (!$pointage) {
        echo "❌ Pointage non trouvé\n";
        exit(1);
    }
    echo "✅ Pointage trouvé (ID: {$pointage->id})\n";
    echo "   - Date: {$pointage->date}\n";
    echo "   - Arrivée: {$pointage->heure_arrivee}\n";
    echo "   - Départ: {$pointage->heure_depart}\n";

    // Initialiser le moteur et tester chaque validateur individuellement
    $engine = new CriteriaEngine();
    echo "\n📝 Test des validateurs individuels:\n";

    // Charger les validateurs via reflection
    $reflection = new ReflectionClass($engine);
    $property = $reflection->getProperty('validators');
    $property->setAccessible(true);
    $validators = $property->getValue($engine);

    foreach ($validators as $validator) {
        $name = class_basename($validator);
        echo "\n🔍 Test du validateur: {$name}\n";
        
        try {
            // Vérifier si le validateur s'applique à ce critère
            if (!$validator->appliesTo($critere)) {
                echo "   ⏭️ Ne s'applique pas à ce critère\n";
                continue;
            }

            // Vérifier si on peut appliquer sans planning
            $canApplyWithoutPlanning = $validator->canApplyWithoutPlanning();
            echo "   📋 Peut fonctionner sans planning: " . ($canApplyWithoutPlanning ? 'Oui' : 'Non') . "\n";

            // Tester la validation
            $result = $validator->validate($pointage, $critere, $planning);
            
            echo "   ✅ Succès: " . ($result->success ? 'Oui' : 'Non') . "\n";
            echo "   📝 Message: {$result->message}\n";
            
            if (!empty($result->errors)) {
                echo "   ❌ Erreurs:\n";
                foreach ($result->errors as $error) {
                    echo "      - {$error}\n";
                }
            }
            
            if (!empty($result->warnings)) {
                echo "   ⚠️ Avertissements:\n";
                foreach ($result->warnings as $warning) {
                    echo "      - {$warning}\n";
                }
            }
            
            if (!empty($result->data)) {
                echo "   📊 Données calculées:\n";
                foreach ($result->data as $key => $value) {
                    $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                    echo "      - {$key}: {$displayValue}\n";
                }
            }

        } catch (Exception $e) {
            echo "   ❌ ERREUR: {$e->getMessage()}\n";
            echo "   📁 Fichier: {$e->getFile()}:{$e->getLine()}\n";
        }
    }

    echo "\n=== FIN DU DEBUG ===\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR FATALE:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   Fichier: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
} 