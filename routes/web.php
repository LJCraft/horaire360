<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\PosteController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Route de connexion directe pour le débogage
// Ces routes ont été supprimées pour garantir la sécurité de l'authentification

// Routes d'authentification (générées par Laravel UI)
Auth::routes();

// Groupe de routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {
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
    Route::get('/plannings/calendrier', [PlanningController::class, 'calendrier'])->name('plannings.calendrier');
    Route::get('/plannings/search-employes', [PlanningController::class, 'searchEmployes'])->name('plannings.search-employes');
    
    // Routes pour les présences (itération 3)
    // Import
    Route::get('/presences/import', [PresenceController::class, 'importForm'])->name('presences.importForm');
    Route::post('/presences/import', [PresenceController::class, 'import'])->name('presences.import');
    // Import des données biométriques (nouvelle fonctionnalité)
    Route::post('/presences/import-biometrique', [PresenceController::class, 'importBiometrique'])->name('presences.importBiometrique');
    Route::post('/presences/verify-biometrique', [PresenceController::class, 'verifyBiometrique'])->name('presences.verifyBiometrique');
    // Template et export
    Route::get('/presences/template', [PresenceController::class, 'template'])->name('presences.template');
    Route::get('/presences/export', [PresenceController::class, 'export'])->name('presences.export');
    Route::get('/presences/export/excel', [PresenceController::class, 'exportExcel'])->name('presences.export.excel');
    Route::get('/presences/export/pdf', [PresenceController::class, 'exportPdf'])->name('presences.export.pdf');
    // Resource route (must be after all other custom routes)
    Route::resource('presences', PresenceController::class);
    
    // Routes pour les rapports (itération 4)
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/presences', [RapportController::class, 'presences'])->name('rapports.presences');
    Route::get('/rapports/absences', [RapportController::class, 'absences'])->name('rapports.absences');
    Route::get('/rapports/retards', [RapportController::class, 'retards'])->name('rapports.retards');
    Route::get('/rapports/biometrique', [RapportController::class, 'biometrique'])->name('rapports.biometrique');
    Route::get('/rapports/export/pdf', [RapportController::class, 'exportPdf'])->name('rapports.export.pdf');
    Route::get('/rapports/export/excel', [RapportController::class, 'exportExcel'])->name('rapports.export.excel');

    // Route API pour les données du tableau de bord
    Route::get('/api/dashboard-data', [DashboardController::class, 'getDashboardData'])->name('api.dashboard-data');

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