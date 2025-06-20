<?php

require_once 'vendor/autoload.php';

use App\Models\Planning;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CORRECTION DU PLANNING ===\n";

$planning = Planning::find(24);
if ($planning) {
    $planning->update([
        'heure_debut' => '08:00:00',
        'heure_fin' => '17:00:00'
    ]);
    echo "✅ Planning mis à jour (ID: 24)\n";
    echo "   - Heure début: {$planning->heure_debut}\n";
    echo "   - Heure fin: {$planning->heure_fin}\n";
} else {
    echo "❌ Planning non trouvé\n";
}

echo "=== FIN ===\n"; 