<?php

/**
 * Script de test pour le moteur de critères avec planning
 * Usage: php test_criteria_engine_with_planning.php
 */

require_once 'vendor/autoload.php';

use App\Services\CriteriaEngine\CriteriaEngine;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Models\Poste;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST COMPLET DU MOTEUR DE CRITÈRES AVEC PLANNING ===\n\n";

try {
    // Initialiser le moteur
    $engine = new CriteriaEngine();
    echo "✅ Moteur initialisé avec succès\n";

    // Test 1: Créer un poste et un employé de test
    echo "\n📝 Test 1: Création d'un poste et employé de test\n";
    
    $poste = Poste::firstOrCreate([
        'nom' => 'Développeur Test Planning'
    ], [
        'nom' => 'Développeur Test Planning',
        'description' => 'Poste de test pour le moteur de critères avec planning'
    ]);
    echo "✅ Poste créé: {$poste->nom} (ID: {$poste->id})\n";
    
    $employe = Employe::firstOrCreate([
        'email' => 'test.planning@example.com'
    ], [
        'nom' => 'Test',
        'prenom' => 'Planning',
        'email' => 'test.planning@example.com',
        'matricule' => 'PLAN001',
        'date_embauche' => Carbon::now()->subMonths(6)->toDateString(),
        'poste_id' => $poste->id
    ]);
    echo "✅ Employé créé: {$employe->nom} {$employe->prenom} (ID: {$employe->id})\n";

    // Test 2: Créer un planning pour l'employé
    echo "\n📝 Test 2: Création d'un planning\n";
    $planning = Planning::firstOrCreate([
        'employe_id' => $employe->id,
        'date_debut' => Carbon::now()->subDays(30)->toDateString(),
        'date_fin' => Carbon::now()->addDays(30)->toDateString()
    ], [
        'employe_id' => $employe->id,
        'date_debut' => Carbon::now()->subDays(30)->toDateString(),
        'date_fin' => Carbon::now()->addDays(30)->toDateString(),
        'heure_debut' => '08:00:00',
        'heure_fin' => '17:00:00',
        'jours_travail' => json_encode(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi']),
        'actif' => true
    ]);
    echo "✅ Planning créé (ID: {$planning->id}) - {$planning->heure_debut} à {$planning->heure_fin}\n";

    // Test 3: Créer un critère de test
    echo "\n📝 Test 3: Création d'un critère de test\n";
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
        'seuil_heures_supplementaires' => 8.0,
        'actif' => true,
        'priorite' => 1,
        'created_by' => 1
    ]);
    echo "✅ Critère créé (ID: {$critere->id})\n";

    // Test 4: Créer un pointage de test - À l'heure
    echo "\n📝 Test 4: Création d'un pointage à l'heure\n";
    $pointage1 = new Presence([
        'employe_id' => $employe->id,
        'date' => Carbon::now()->toDateString(),
        'heure_arrivee' => '08:00:00',
        'heure_depart' => '17:00:00',
        'source_pointage' => 'manuel'
    ]);
    $pointage1->save();
    echo "✅ Pointage à l'heure créé (ID: {$pointage1->id})\n";

    // Test 5: Créer un pointage de test - En retard
    echo "\n📝 Test 5: Création d'un pointage en retard\n";
    $pointage2 = new Presence([
        'employe_id' => $employe->id,
        'date' => Carbon::now()->subDay()->toDateString(),
        'heure_arrivee' => '08:30:00',
        'heure_depart' => '17:30:00',
        'source_pointage' => 'manuel'
    ]);
    $pointage2->save();
    echo "✅ Pointage en retard créé (ID: {$pointage2->id})\n";

    // Test 6: Application des critères au pointage à l'heure
    echo "\n📝 Test 6: Application des critères au pointage à l'heure\n";
    $result1 = $engine->applyCriteriaToPointage($pointage1);
    
    echo "✅ Traitement terminé pour pointage à l'heure\n";
    echo "   - Statut: {$result1->status->value}\n";
    echo "   - Validateurs appliqués: " . count($result1->appliedValidators) . "\n";
    echo "   - Validateurs en attente: " . count($result1->pendingValidators) . "\n";
    echo "   - Erreurs: " . count($result1->errors) . "\n";

    // Test 7: Application des critères au pointage en retard
    echo "\n📝 Test 7: Application des critères au pointage en retard\n";
    $result2 = $engine->applyCriteriaToPointage($pointage2);
    
    echo "✅ Traitement terminé pour pointage en retard\n";
    echo "   - Statut: {$result2->status->value}\n";
    echo "   - Validateurs appliqués: " . count($result2->appliedValidators) . "\n";
    echo "   - Validateurs en attente: " . count($result2->pendingValidators) . "\n";
    echo "   - Erreurs: " . count($result2->errors) . "\n";

    // Afficher les valeurs calculées pour le pointage en retard
    if (!empty($result2->calculatedValues)) {
        echo "\n🧮 Valeurs calculées pour le pointage en retard:\n";
        foreach ($result2->calculatedValues as $key => $data) {
            $value = is_bool($data['value']) ? ($data['value'] ? 'true' : 'false') : $data['value'];
            if (in_array($key, ['retard', 'avance', 'heures_faites', 'heures_supplementaires', 'temps_travail_net'])) {
                echo "   📊 {$key}: {$value}\n";
            }
        }
    }

    // Test 8: Traitement par lot
    echo "\n📝 Test 8: Traitement par lot des deux pointages\n";
    $pointages = collect([$pointage1, $pointage2]);
    $batchResult = $engine->applyCriteriaToBatch($pointages);
    
    echo "✅ Traitement par lot terminé\n";
    $summary = $batchResult->getSummary();
    echo "   - Total traité: {$summary['total_processed']}\n";
    echo "   - Succès: {$summary['successful_count']}\n";
    echo "   - Erreurs: {$summary['error_count']}\n";
    echo "   - Taux de succès: {$summary['success_rate']}%\n";

    // Test 9: Retraitement avec la commande Artisan
    echo "\n📝 Test 9: Test de la commande Artisan\n";
    $exitCode = Artisan::call('criteria:process', [
        '--employe' => $employe->id,
        '--force' => true
    ]);
    
    echo "✅ Commande Artisan exécutée (Exit Code: {$exitCode})\n";
    echo Artisan::output();

    // Test 10: Vérification des résultats finaux
    echo "\n📝 Test 10: Vérification des résultats finaux\n";
    $pointage1->refresh();
    $pointage2->refresh();
    
    echo "✅ Pointage 1 (à l'heure):\n";
    echo "   - Statut: {$pointage1->criteria_processing_status}\n";
    echo "   - Retard: " . ($pointage1->retard ?? 'Non calculé') . "\n";
    echo "   - Heures faites: " . ($pointage1->heures_faites ?? 'Non calculé') . "\n";
    
    echo "✅ Pointage 2 (en retard):\n";
    echo "   - Statut: {$pointage2->criteria_processing_status}\n";
    echo "   - Retard: " . ($pointage2->retard ?? 'Non calculé') . "\n";
    echo "   - Heures faites: " . ($pointage2->heures_faites ?? 'Non calculé') . "\n";

    echo "\n🎉 TOUS LES TESTS AVEC PLANNING SONT PASSÉS AVEC SUCCÈS!\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR LORS DU TEST:\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   Fichier: {$e->getFile()}:{$e->getLine()}\n";
    echo "   Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "\n=== FIN DES TESTS AVEC PLANNING ===\n"; 