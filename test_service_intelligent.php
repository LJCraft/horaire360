<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PointageIntelligentService;

echo "🧪 === TEST DU SERVICE POINTAGE INTELLIGENT ===\n\n";

// Cas de test 1: Pointages multiples désordonnés
echo "📋 CAS 1: Pointages multiples désordonnés\n";
$pointagesDesordonnes = [
    [
        'employe_id' => 1,
        'date' => '2025-06-30',
        'heure' => '16:40:00',
        'source' => 'test_cas1'
    ],
    [
        'employe_id' => 1,
        'date' => '2025-06-30',
        'heure' => '08:15:00',
        'source' => 'test_cas1'
    ],
    [
        'employe_id' => 1,
        'date' => '2025-06-30',
        'heure' => '13:30:00',
        'source' => 'test_cas1'
    ]
];

$service = new PointageIntelligentService();
$resultat1 = $service->traiterPointagesBruts($pointagesDesordonnes, 'test_intelligent', 'test_cas1');

echo "Résultat: " . ($resultat1['success'] ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n";
echo "Message: " . $resultat1['message'] . "\n";
if (isset($resultat1['stats'])) {
    echo "Stats: " . json_encode($resultat1['stats'], JSON_PRETTY_PRINT) . "\n";
}
echo "\n";

// Cas de test 2: Pointage unique
echo "📋 CAS 2: Pointage unique\n";
$pointageUnique = [
    [
        'employe_id' => 2,
        'date' => '2025-06-30',
        'heure' => '09:00:00',
        'source' => 'test_cas2'
    ]
];

$resultat2 = $service->traiterPointagesBruts($pointageUnique, 'test_intelligent', 'test_cas2');

echo "Résultat: " . ($resultat2['success'] ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n";
echo "Message: " . $resultat2['message'] . "\n";
if (isset($resultat2['stats'])) {
    echo "Stats: " . json_encode($resultat2['stats'], JSON_PRETTY_PRINT) . "\n";
}
echo "\n";

// Cas de test 3: Format API mobile exact
echo "📋 CAS 3: Format API mobile (comme votre exemple)\n";
$pointagesMobile = [
    [
        'employe_id' => 1,
        'date' => '2025-06-20',
        'heure' => '20:55:25',
        'source' => 'apitface_mobile'
    ],
    [
        'employe_id' => 1,
        'date' => '2025-06-20',
        'heure' => '20:12:11',
        'source' => 'apitface_mobile'
    ]
];

$resultat3 = $service->traiterPointagesBruts($pointagesMobile, 'apitface_mobile', 'test_cas3');

echo "Résultat: " . ($resultat3['success'] ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n";
echo "Message: " . $resultat3['message'] . "\n";
if (isset($resultat3['stats'])) {
    echo "Stats: " . json_encode($resultat3['stats'], JSON_PRETTY_PRINT) . "\n";
}
echo "\n";

// Vérification en base de données
echo "🔍 === VÉRIFICATION EN BASE DE DONNÉES ===\n";

$presences = \App\Models\Presence::whereIn('employe_id', [1, 2])
    ->whereIn('date', ['2025-06-30', '2025-06-20'])
    ->where('source_pointage', 'LIKE', '%test%')
    ->orWhere('source_pointage', 'apitface_mobile')
    ->orderBy('employe_id')
    ->orderBy('date')
    ->get();

echo "Nombre de présences trouvées: " . $presences->count() . "\n\n";

foreach ($presences as $presence) {
    echo "📊 Employé {$presence->employe_id} - {$presence->date}:\n";
    echo "   Arrivée: " . ($presence->heure_arrivee ?? 'NON DÉFINIE') . "\n";
    echo "   Départ:  " . ($presence->heure_depart ?? 'NON DÉFINIE') . "\n";
    echo "   Source:  {$presence->source_pointage}\n";
    echo "   Statut:  " . ($presence->statut ?? 'N/A') . "\n";
    
    // Vérification logique
    if ($presence->heure_arrivee && $presence->heure_depart) {
        if ($presence->heure_arrivee < $presence->heure_depart) {
            echo "   ✅ Cohérence horaire: OK\n";
        } else {
            echo "   ❌ Cohérence horaire: PROBLÈME\n";
        }
    }
    echo "\n";
}

echo "🏁 Tests terminés !\n"; 