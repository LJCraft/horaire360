<?php

// Charger l'environnement Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Poste;
use Illuminate\Support\Facades\DB;

// Définir les grades par département
$gradesByDepartment = [
    'Informatique' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Développeur', 'Lead Développeur', 'Architecte', 'Chef de Projet', 'Directeur Technique'],
        'Spécialisation' => ['Frontend', 'Backend', 'Fullstack', 'DevOps', 'Mobile', 'Data Science', 'IA', 'Sécurité']
    ],
    'Ressources Humaines' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Assistant RH', 'Chargé de Recrutement', 'Responsable RH', 'DRH'],
        'Spécialisation' => ['Recrutement', 'Formation', 'Paie', 'Administration', 'Relations Sociales']
    ],
    'Marketing' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Assistant Marketing', 'Chargé de Marketing', 'Responsable Marketing', 'Directeur Marketing'],
        'Spécialisation' => ['Digital', 'Content', 'SEO', 'SEM', 'Social Media', 'Événementiel', 'Produit']
    ],
    'Finance' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Comptable', 'Contrôleur de Gestion', 'Responsable Financier', 'Directeur Financier'],
        'Spécialisation' => ['Comptabilité', 'Contrôle de Gestion', 'Audit', 'Trésorerie', 'Fiscalité']
    ],
    'Commercial' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Commercial', 'Responsable Commercial', 'Directeur Commercial'],
        'Spécialisation' => ['B2B', 'B2C', 'Key Account', 'Export', 'Avant-Vente']
    ],
    'Production' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Opérateur', 'Chef d\'Équipe', 'Superviseur', 'Responsable Production', 'Directeur Production'],
        'Spécialisation' => ['Ligne de Production', 'Qualité', 'Maintenance', 'Logistique', 'Planification']
    ],
    'Direction' => [
        'Niveau hiérarchique' => ['Directeur', 'Directeur Général', 'Président', 'PDG'],
    ],
    // Département par défaut pour les postes sans département spécifique
    'default' => [
        'Niveau d\'expérience' => ['Stagiaire', 'Junior', 'Intermédiaire', 'Senior', 'Expert'],
        'Niveau hiérarchique' => ['Employé', 'Chef d\'Équipe', 'Superviseur', 'Manager', 'Directeur'],
    ]
];

// Définir les grades spécifiques par nom de poste
$gradesByPosition = [
    'Développeur Web' => [
        'Frontend', 'Backend', 'Fullstack', 'Junior', 'Intermédiaire', 'Senior', 'Lead Développeur'
    ],
    'Développeur Mobile' => [
        'iOS', 'Android', 'Cross-Platform', 'Junior', 'Intermédiaire', 'Senior', 'Lead Mobile'
    ],
    'Designer UI/UX' => [
        'UI Designer', 'UX Designer', 'UI/UX Designer', 'Junior', 'Intermédiaire', 'Senior', 'Lead Designer'
    ],
    'Chef de Projet' => [
        'Chef de Projet Junior', 'Chef de Projet Confirmé', 'Chef de Projet Senior', 'Directeur de Projet'
    ],
    'Responsable RH' => [
        'Responsable Recrutement', 'Responsable Formation', 'Responsable Paie', 'DRH'
    ],
    'Comptable' => [
        'Comptable Junior', 'Comptable Confirmé', 'Chef Comptable', 'Responsable Comptabilité'
    ],
    'Commercial' => [
        'Commercial Junior', 'Commercial Confirmé', 'Commercial Senior', 'Key Account Manager'
    ],
    'Responsable Marketing' => [
        'Responsable Marketing Digital', 'Responsable Communication', 'Responsable Produit', 'Directeur Marketing'
    ]
];

// Fonction pour obtenir les grades appropriés pour un poste
function getGradesForPosition($position) {
    global $gradesByDepartment, $gradesByPosition;
    
    $grades = [];
    
    // Ajouter les grades spécifiques au poste si disponibles
    if (isset($gradesByPosition[$position->nom])) {
        $grades = array_merge($grades, $gradesByPosition[$position->nom]);
    }
    
    // Ajouter les grades du département
    $dept = $position->departement;
    if (!empty($dept) && isset($gradesByDepartment[$dept])) {
        foreach ($gradesByDepartment[$dept] as $category => $categoryGrades) {
            $grades = array_merge($grades, $categoryGrades);
        }
    } else {
        // Utiliser les grades par défaut si le département n'est pas reconnu
        foreach ($gradesByDepartment['default'] as $category => $categoryGrades) {
            $grades = array_merge($grades, $categoryGrades);
        }
    }
    
    // Éliminer les doublons et trier
    $grades = array_unique($grades);
    sort($grades);
    
    return $grades;
}

// Mettre à jour tous les postes
try {
    DB::beginTransaction();
    
    $postes = Poste::all();
    $updatedCount = 0;
    
    foreach ($postes as $poste) {
        $grades = getGradesForPosition($poste);
        
        // Vérifier si le poste a déjà des grades
        $currentGrades = json_decode($poste->grades_disponibles ?? '[]');
        
        // Si le poste n'a pas de grades ou a un tableau vide, mettre à jour
        if (empty($currentGrades)) {
            $poste->grades_disponibles = json_encode($grades);
            $poste->save();
            $updatedCount++;
            echo "Poste mis à jour: {$poste->nom} ({$poste->departement}) - " . count($grades) . " grades ajoutés\n";
        } else {
            echo "Poste ignoré (grades déjà définis): {$poste->nom}\n";
        }
    }
    
    DB::commit();
    
    echo "\n=== Résumé ===\n";
    echo "Total des postes: " . count($postes) . "\n";
    echo "Postes mis à jour: {$updatedCount}\n";
    echo "Postes ignorés: " . (count($postes) - $updatedCount) . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "Erreur: " . $e->getMessage() . "\n";
}
