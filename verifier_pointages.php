<?php

require_once 'vendor/autoload.php';

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VÉRIFICATION DES POINTAGES ===\n";

try {
    // Récupérer les pointages récents
    $pointages = \App\Models\Presence::with('employe')
        ->where('source_pointage', 'synchronisation')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    echo "Nombre de pointages synchronisés: " . $pointages->count() . "\n\n";

    foreach ($pointages as $pointage) {
        echo "- {$pointage->employe->nom} ({$pointage->employe_id})\n";
        echo "  Date: {$pointage->date}\n";
        echo "  Arrivée: {$pointage->heure_arrivee}\n";
        echo "  Départ: " . ($pointage->heure_depart ?? 'Non défini') . "\n";
        echo "  Source: {$pointage->source_pointage}\n";
        echo "  Créé le: {$pointage->created_at}\n";
        
        // Récupérer les métadonnées (déjà décodées par le cast Laravel)
        if ($pointage->meta_data) {
            $meta = $pointage->meta_data ?? [];
            if (isset($meta['source'])) {
                echo "  Métadonnées: Source = {$meta['source']}\n";
            }
            if (isset($meta['donnee_brute']['nom'])) {
                echo "  Nom original: {$meta['donnee_brute']['nom']}\n";
            }
        }
        echo "\n";
    }

    // Statistiques par employé
    echo "=== STATISTIQUES PAR EMPLOYÉ ===\n";
    $stats = \App\Models\Presence::select('employe_id', \DB::raw('COUNT(*) as total'))
        ->where('source_pointage', 'synchronisation')
        ->with('employe')
        ->groupBy('employe_id')
        ->get();

    foreach ($stats as $stat) {
        echo "- {$stat->employe->nom}: {$stat->total} pointages\n";
    }

    echo "\n=== VÉRIFICATION TERMINÉE ===\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
} 