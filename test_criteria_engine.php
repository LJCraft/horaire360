<?php

/**
 * Script de test pour le moteur de critères de pointage
 * Usage: php test_criteria_engine.php
 */

require_once 'vendor/autoload.php';

use App\Services\CriteriaEngine\CriteriaEngine;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Models\Poste;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST DU MOTEUR DE CRITÈRES DE POINTAGE ===\n\n";

try {
    // Initialiser le moteur
    $engine = new CriteriaEngine();
    echo "✅ Moteur initialisé avec succès\n";

    // Test 1: Créer un poste et un employé de test
    echo "\n📝 Test 1: Création d'un poste et employé de test\n";
    
    // Créer un poste de test
    $poste = Poste::firstOrCreate([
        'nom' => 'Développeur Test'
    ], [
        'nom' => 'Développeur Test',
        'description' => 'Poste de test pour le moteur de critères'
    ]);
    echo "✅ Poste créé: {$poste->nom} (ID: {$poste->id})\n";
    
    $employe = Employe::firstOrCreate([
        'email' => 'test.moteur@example.com'
    ], [
        'nom' => 'Test',
        'prenom' => 'Moteur',
        'email' => 'test.moteur@example.com',
        'matricule' => 'TEST001',
        'date_embauche' => Carbon::now()->subMonths(6)->toDateString(),
        'poste_id' => $poste->id
    ]);
    echo "✅ Employé créé: {$employe->nom} {$employe->prenom} (ID: {$employe->id})\n";

    // Test 2: Créer un critère de test
    echo "\n📝 Test 2: Création d'un critère de test\n";
    $critere = CriterePointage::firstOrCreate([
        'niveau' => 'individuel',
        'employe_id' => $employe->id
    ], [
        'niveau' => 'individuel',
        'employe_id' => $employe->id,
        'date_debut' => Carbon::now()->subDays(30),
        'date_fin' => Carbon::now()->addDays(30),
        'nombre_pointages' => 2,
        'tolerance_avant' => 15,
        'tolerance_apres' => 15,
        'source_pointage' => 'tous',
        'actif' => true,
        'priorite' => 1,
        'created_by' => 1 // ID utilisateur fictif pour le test
    ]);
    echo "✅ Critère créé (ID: {$critere->id})\n";

    // Test 3: Créer un pointage de test
    echo "\n📝 Test 3: Création d'un pointage de test\n";
    $pointage = new Presence([
        'employe_id' => $employe->id,
        'date' => Carbon::now()->toDateString(),
        'heure_arrivee' => '08:30:00',
        'heure_depart' => '17:15:00',
        'source_pointage' => 'manuel'
    ]);
    $pointage->save();
    echo "✅ Pointage créé (ID: {$pointage->id})\n";

    // Test 4: Obtenir les critères applicables
    echo "\n📝 Test 4: Test de récupération des critères applicables\n";
    $criteresApplicables = $engine->getApplicableCriteria($employe, Carbon::now());
    echo "✅ Critères applicables trouvés: {$criteresApplicables->count()}\n";
    foreach ($criteresApplicables as $c) {
        echo "   - Critère {$c->id}: {$c->niveau}, priorité {$c->priorite}\n";
    }

    // Test 5: Appliquer les critères au pointage
    echo "\n📝 Test 5: Application des critères au pointage\n";
    $result = $engine->applyCriteriaToPointage($pointage);
    
    echo "✅ Traitement terminé\n";
    echo "   - Statut: {$result->status->value} ({$result->status->getDescription()})\n";
    echo "   - Validateurs appliqués: " . count($result->appliedValidators) . "\n";
    echo "   - Validateurs en attente: " . count($result->pendingValidators) . "\n";
    echo "   - Erreurs: " . count($result->errors) . "\n";
    echo "   - Avertissements: " . count($result->warnings) . "\n";

    // Afficher les détails des validateurs appliqués
    if (!empty($result->appliedValidators)) {
        echo "\n📊 Validateurs appliqués:\n";
        foreach ($result->appliedValidators as $validator) {
            $name = class_basename($validator['validator']);
            echo "   ✅ {$name}\n";
        }
    }

    // Afficher les validateurs en attente
    if (!empty($result->pendingValidators)) {
        echo "\n⏳ Validateurs en attente:\n";
        foreach ($result->pendingValidators as $validator) {
            $name = class_basename($validator['validator']);
            echo "   ⏳ {$name}: {$validator['reason']}\n";
        }
    }

    // Afficher les erreurs
    if (!empty($result->errors)) {
        echo "\n❌ Erreurs:\n";
        foreach ($result->errors as $error) {
            $name = class_basename($error['validator']);
            echo "   ❌ {$name}: {$error['message']}\n";
        }
    }

    // Afficher les avertissements
    if (!empty($result->warnings)) {
        echo "\n⚠️  Avertissements:\n";
        foreach ($result->warnings as $warning) {
            $name = class_basename($warning['validator']);
            echo "   ⚠️  {$name}: {$warning['message']}\n";
        }
    }

    // Afficher les valeurs calculées
    if (!empty($result->calculatedValues)) {
        echo "\n🧮 Valeurs calculées:\n";
        foreach ($result->calculatedValues as $key => $data) {
            $value = is_bool($data['value']) ? ($data['value'] ? 'true' : 'false') : $data['value'];
            echo "   📊 {$key}: {$value}\n";
        }
    }

    // Test 6: Test de traitement par lot
    echo "\n📝 Test 6: Test de traitement par lot\n";
    $pointages = collect([$pointage]);
    $batchResult = $engine->applyCriteriaToBatch($pointages);
    
    echo "✅ Traitement par lot terminé\n";
    $summary = $batchResult->getSummary();
    echo "   - Total traité: {$summary['total_processed']}\n";
    echo "   - Succès: {$summary['successful_count']}\n";
    echo "   - Erreurs: {$summary['error_count']}\n";
    echo "   - Taux de succès: {$summary['success_rate']}%\n";

    // Test 7: Recharger le pointage pour voir les modifications
    echo "\n📝 Test 7: Vérification des modifications du pointage\n";
    $pointage->refresh();
    echo "✅ Pointage rechargé\n";
    echo "   - Statut de traitement: {$pointage->criteria_processing_status}\n";
    echo "   - Traité le: " . ($pointage->criteria_processed_at ? $pointage->criteria_processed_at->format('Y-m-d H:i:s') : 'Non traité') . "\n";
    
    if ($pointage->meta_data && isset($pointage->meta_data['criteria_processing'])) {
        $processing = $pointage->meta_data['criteria_processing'];
        echo "   - Métadonnées de traitement disponibles\n";
        echo "   - Statut dans métadonnées: {$processing['status']}\n";
    }

    echo "\n🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS!\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR LORS DU TEST:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   Fichier: {$e->getFile()}:{$e->getLine()}\n";
    echo "   Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "\n=== FIN DES TESTS ===\n"; 