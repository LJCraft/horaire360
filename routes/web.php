<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\PosteController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlanningDepartementController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CriterePointageController;
use App\Http\Controllers\RapportControllerAdditions;
use App\Http\Controllers\BiometricDeviceController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Route de test publique (sans authentification)
Route::get('/test', [TestController::class, 'index'])->name('test');

// Routes d'authentification (générées par Laravel UI)
Auth::routes();

// Groupe de routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {
    // Route home (redirige vers le dashboard)
    Route::get('/home', function() {
        return redirect()->route('dashboard');
    })->name('home');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard'])->name('dashboard.admin');
    Route::get('/dashboard/employe', [DashboardController::class, 'employeDashboard'])->name('dashboard.employe');
    
    // Gestion des employés
    Route::resource('employes', EmployeController::class);
    Route::post('/employes/import', [EmployeController::class, 'import'])->name('employes.import');
    Route::post('/employes/import-direct', [EmployeController::class, 'importDirect'])->name('employes.import-direct');
    Route::post('/employes/check-file', [EmployeController::class, 'checkImportFile'])->name('employes.check-file');
    Route::get('/employes/export/template', [EmployeController::class, 'exportTemplate'])->name('employes.export-template');
    Route::get('/employes/export', [EmployeController::class, 'export'])->name('employes.export');
    Route::get('/employes/export/pdf', [EmployeController::class, 'exportPdf'])->name('employes.export-pdf');
    Route::post('/employes/check-email', [EmployeController::class, 'checkEmail'])->name('employes.check-email');
    
    // Gestion des postes
    Route::resource('postes', PosteController::class);
    Route::get('/postes/by-departement', [PosteController::class, 'getPostesByDepartement'])->name('postes.by-departement');
    
    // Création d'un compte utilisateur pour un employé
    Route::get('/users/create-from-employee/{employe}', [UserController::class, 'redirectToCreateFromEmployee'])->name('users.redirect-create-from-employee');
    Route::post('/users/create-from-employee/{employe}', [UserController::class, 'createFromEmployee'])->name('users.create-from-employee');
    
    // Gestion des utilisateurs
    Route::resource('users', UserController::class);
    
    // Routes pour les plannings (itération 2)
    Route::resource('plannings', PlanningController::class);
    Route::post('/plannings/import', [PlanningController::class, 'import'])->name('plannings.import');
    Route::get('/plannings/export/template', [PlanningController::class, 'exportTemplate'])->name('plannings.export-template');
    Route::get('/plannings/export', [PlanningController::class, 'export'])->name('plannings.export');
    Route::get('/plannings/search-employes', [PlanningController::class, 'searchEmployes'])->name('plannings.search-employes');
    
    // Routes pour les plannings par département
    Route::prefix('admin/plannings')->group(function () {
        Route::get('/departement', [PlanningDepartementController::class, 'index'])->name('plannings.departement.index');
        Route::get('/departement/create', [PlanningDepartementController::class, 'create'])->name('plannings.departement.create');
        Route::post('/departement', [PlanningDepartementController::class, 'store'])->name('plannings.departement.store');
        Route::get('/departement/calendrier', [PlanningDepartementController::class, 'calendrier'])->name('plannings.departement.calendrier');
    });
    
    // Routes pour les présences (itération 3)
    // Import
    Route::get('/presences/import', [PresenceController::class, 'importForm'])->name('presences.importForm');
    Route::post('/presences/import', [PresenceController::class, 'import'])->name('presences.import');
    // Import des données biométriques (nouvelle fonctionnalité)
    Route::post('/presences/import-biometrique', [PresenceController::class, 'importBiometrique'])->name('presences.importBiometrique');
Route::get('/presences/download-dat-template', [PresenceController::class, 'downloadDatTemplate'])->name('presences.downloadDatTemplate');
    
    // Template et export
    Route::get('/presences/template', [PresenceController::class, 'template'])->name('presences.template');
    Route::get('/presences/export', [PresenceController::class, 'export'])->name('presences.export');
    Route::get('/presences/export/excel', [PresenceController::class, 'exportExcel'])->name('presences.export.excel');
    Route::get('/presences/export/pdf', [PresenceController::class, 'exportPdf'])->name('presences.export.pdf');
    
    // Template de pointage (nouveau)
    Route::get('/presences/template-pointage', [PresenceController::class, 'downloadPointageTemplate'])->name('presences.downloadPointageTemplate');
    Route::get('/presences/import-pointage', [PresenceController::class, 'importPointageForm'])->name('presences.importPointageForm');
    Route::post('/presences/import-pointage', [PresenceController::class, 'importPointage'])->name('presences.importPointage');
    // Resource route (must be after all other custom routes)
    Route::resource('presences', PresenceController::class);
    
    // Routes pour les appareils biométriques
    Route::prefix('biometric-devices')->name('biometric-devices.')->group(function () {
        Route::get('/', [App\Http\Controllers\BiometricDeviceController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\BiometricDeviceController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\BiometricDeviceController::class, 'store'])->name('store');
        Route::delete('/{id}', [App\Http\Controllers\BiometricDeviceController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/test-connection', [App\Http\Controllers\BiometricDeviceController::class, 'testConnection'])->name('test-connection');
        Route::post('/{id}/disconnect', [App\Http\Controllers\BiometricDeviceController::class, 'disconnect'])->name('disconnect');
        
        // Route de test ultra-simple
        Route::get('/simple-test', function() {
            return '<h1>Test Simple Réussi</h1><p>Si vous voyez ceci, PHP fonctionne correctement.</p><p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>';
        })->name('biometric-devices.simple-test');
        
        // Route de test pour vérifier les données
        Route::get('/debug-data', function() {
            try {
                $devices = \App\Models\BiometricDevice::all();
                $count = $devices->count();
                
                $html = '<h1>Debug - Appareils Biométriques</h1>';
                $html .= '<p><strong>Nombre total:</strong> ' . $count . '</p>';
                
                if ($count > 0) {
                    $html .= '<h2>Liste des appareils:</h2><ul>';
                    foreach ($devices as $device) {
                        $html .= '<li><strong>' . $device->name . '</strong> - ' . $device->brand . ' (' . $device->connection_type . ')</li>';
                    }
                    $html .= '</ul>';
                } else {
                    $html .= '<p style="color: red;">Aucun appareil trouvé en base de données.</p>';
                }
                
                return $html;
            } catch (\Exception $e) {
                return '<h1>Erreur</h1><p style="color: red;">' . $e->getMessage() . '</p>';
            }
        })->name('biometric-devices.debug-data');
    });

    // Route pour synchroniser tous les appareils biométriques connectés (depuis les rapports)
    Route::post('/rapports/biometrique/synchronize-all', [RapportController::class, 'synchronizeAllDevices'])->name('rapports.biometrique.synchronize-all');

    // Routes pour les critères de pointage
    // Route personnalisée pour l'édition des critères de pointage
    Route::get('/criteres-pointage/edit/{id}', [CriterePointageController::class, 'edit'])->name('criteres-pointage.edit-custom');
    
    // Route personnalisée pour la mise à jour des critères de pointage
    Route::put('/criteres-pointage/update/{id}', [CriterePointageController::class, 'update'])->name('criteres-pointage.update-custom');
    
    // Route personnalisée pour afficher les détails d'un critère de pointage
    Route::get('/criteres-pointage/{id}', [CriterePointageController::class, 'show'])->name('criteres-pointage.show-custom');
    
    // Route pour les critères de pointage par département
    Route::get('/criteres-pointage/departement', [CriterePointageController::class, 'departement'])->name('criteres-pointage.departement');
    
    // Route pour lister les critères départementaux
    Route::get('/criteres-pointage/departementaux/liste', [CriterePointageController::class, 'listeCriteresDepartementaux'])->name('criteres-pointage.departementaux');
    
    // Resource route pour les critères de pointage (except edit et update pour éviter les conflits)
    Route::resource('criteres-pointage', CriterePointageController::class)->except(['edit', 'update']);
    
    // Alias pour les routes standards
    Route::get('/criteres-pointage/{id}/edit', [CriterePointageController::class, 'edit'])->name('criteres-pointage.edit');
    Route::put('/criteres-pointage/{id}', [CriterePointageController::class, 'update'])->name('criteres-pointage.update');
    
    Route::post('/criteres-pointage/get-planning', [CriterePointageController::class, 'getPlanning'])->name('criteres-pointage.get-planning');
    Route::post('/criteres-pointage/get-employes-departement', [CriterePointageController::class, 'getEmployesDepartement'])->name('criteres-pointage.get-employes-departement');
    Route::post('/criteres-pointage/get-postes-departement', [CriterePointageController::class, 'getPostesDepartement'])->name('criteres-pointage.get-postes-departement');
    Route::post('/criteres-pointage/get-grades-poste', [CriterePointageController::class, 'getGradesPoste'])->name('criteres-pointage.get-grades-poste');
    Route::post('/criteres-pointage/get-critere-employe', [CriterePointageController::class, 'getCritereEmploye'])->name('criteres-pointage.get-critere-employe');
    
    // Routes pour les rapports
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/presences', [RapportController::class, 'presences'])->name('rapports.presences');
    Route::get('/rapports/absences', [RapportController::class, 'absences'])->name('rapports.absences');
    Route::get('/rapports/retards', [RapportController::class, 'retards'])->name('rapports.retards');
    Route::get('/rapports/ponctualite-assiduite', [RapportController::class, 'ponctualiteAssiduite'])->name('rapports.ponctualite-assiduite');
    Route::get('/rapports/ponctualite-assiduite-v2', [RapportControllerAdditions::class, 'ponctualiteAssiduiteV2'])->name('rapports.ponctualite-assiduite-v2');
    Route::get('/rapports/biometrique', [RapportController::class, 'biometrique'])->name('rapports.biometrique');
    Route::get('/rapports/heures-supplementaires', [RapportController::class, 'heuresSupplementaires'])->name('rapports.heures-supplementaires');
    Route::get('/rapports/global-multi-periode', [RapportController::class, 'globalMultiPeriode'])->name('rapports.global-multi-periode');
    Route::get('/rapports/export-options', [RapportController::class, 'exportOptions'])->name('rapports.export-options');
    
    // Export PDF
    Route::post('/rapports/export-pdf', [RapportController::class, 'exportPdf'])->name('rapports.export-pdf');
    Route::post('/rapports/export-pdf-v2', [RapportControllerAdditions::class, 'exportPdfV2'])->name('rapports.export-pdf-v2');
    Route::get('/rapports/presences/pdf', [RapportController::class, 'exportPresencesPdf'])->name('rapports.presences.pdf');
    Route::get('/rapports/absences/pdf', [RapportController::class, 'exportAbsencesPdf'])->name('rapports.absences.pdf');
    Route::get('/rapports/retards/pdf', [RapportController::class, 'exportRetardsPdf'])->name('rapports.retards.pdf');
    Route::get('/rapports/ponctualite-assiduite/pdf', [RapportController::class, 'exportPonctualiteAssiduitePdf'])->name('rapports.ponctualite-assiduite.pdf');
    Route::get('/rapports/biometrique/pdf', [RapportController::class, 'exportBiometriquePdf'])->name('rapports.biometrique.pdf');
    Route::get('/rapports/heures-supplementaires/pdf', [RapportController::class, 'exportHeuresSupplementairesPdf'])->name('rapports.heures-supplementaires.pdf');

    // Route API pour les données du tableau de bord
    Route::get('/api/dashboard-data', [DashboardController::class, 'getDashboardData'])->name('api.dashboard-data');

    // Route API pour les données de plannings pour le calendrier
    Route::get('/api/plannings/departement', [PlanningDepartementController::class, 'getCalendarData'])->name('api.plannings.departement');
    Route::get('/api/plannings', [PlanningController::class, 'getCalendarData'])->name('api.plannings');
    Route::get('/api/plannings/{planning}', [PlanningController::class, 'getPlanningData'])->name('api.planning-detail');
    Route::get('/api/postes', [PosteController::class, 'getPostesByDepartement'])->name('api.postes');

    // Route API pour les données de test
    Route::get('/api/test-charts-data', function () {
        // Données simples pour le test
        return response()->json([
            'postes' => [
                'labels' => ['Développeur', 'Manager', 'Designer', 'Commercial'],
                'values' => [12, 5, 8, 3]
            ],
            'presences' => [
                'labels' => ['01/05', '02/05', '03/05', '04/05', '05/05', '06/05', '07/05'],
                'values' => [25, 22, 28, 24, 27, 10, 15]
            ],
            'stats_presence' => [
                'tauxPresence' => 75,
                'tauxRetard' => 15,
                'tauxAbsence' => 10
            ]
        ]);
    })->name('api.test-charts-data');
});

// Route de test pour déboguer le téléchargement de photos
Route::get('/test-photo', function () {
    // Créer un fichier test dans le dossier storage/app/public/photos
    $filename = 'test_' . time() . '.txt';
    $path = 'photos/' . $filename;
    
    // Vérifications et diagnostics
    $results = [
        'storage_public_writable' => is_writable(storage_path('app/public')),
        'photos_dir_exists' => file_exists(storage_path('app/public/photos')),
        'photos_dir_writable' => is_writable(storage_path('app/public/photos')),
        'public_storage_exists' => file_exists(public_path('storage')),
        'symbolic_link_valid' => is_link(public_path('storage')),
    ];
    
    // Créer un fichier test
    try {
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'Test file content');
        $results['file_created'] = true;
        $results['file_path'] = storage_path('app/public/' . $path);
        $results['file_exists'] = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
        $results['url'] = asset('storage/' . $path);
        $results['file_accessible'] = @file_get_contents(asset('storage/' . $path)) !== false;
    } catch (\Exception $e) {
        $results['file_created'] = false;
        $results['error'] = $e->getMessage();
    }
    
    return response()->json($results);
});

// Page de test des graphiques - accessible à tous pour le diagnostic
Route::get('/test-charts', function () {
    // Vue simplifiée qui ne dépend pas des données du contrôleur
    return view('dashboard.test-charts');
})->name('test-charts');