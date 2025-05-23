<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BiometricAttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\PosteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Groupe de routes avec vérification d'application mobile
Route::middleware(['mobile.verify'])->group(function () {
    // Routes d'authentification pour l'application mobile
    Route::post('/login', [AuthController::class, 'login']);
    
    // Routes protégées par sanctum et vérification d'application mobile
    Route::middleware('auth:sanctum')->group(function () {
        // Vérifier l'authentification
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        
        // Routes pour les pointages biométriques
        Route::post('/attendance/check-in', [BiometricAttendanceController::class, 'checkIn']);
        Route::post('/attendance/check-out', [BiometricAttendanceController::class, 'checkOut']);
        
        // Récupérer l'historique des pointages d'un employé
        Route::get('/attendance/history', [BiometricAttendanceController::class, 'history']);
        
        // Déconnexion
        Route::post('/logout', [AuthController::class, 'logout']);
    });
}); 

// Route pour récupérer les grades disponibles pour un poste
Route::get('/postes/{poste}/grades', [PosteController::class, 'getGradesDisponibles']);

// Route pour récupérer les données du rapport de ponctualité et d'assiduité
Route::get('/rapport-data', [\App\Http\Controllers\Api\RapportController::class, 'getRapportData']);

// Routes API pour la gestion des critères de pointage
Route::prefix('criteres')->group(function () {
    // Créer un critère individuel ou départemental
    Route::post('/', [\App\Http\Controllers\API\CriterePointageApiController::class, 'store']);
    
    // Récupérer tous les critères définis
    Route::get('/', [\App\Http\Controllers\API\CriterePointageApiController::class, 'index']);
    
    // Vérifier si un employé a déjà un critère défini
    Route::get('/employe/{employeId}', [\App\Http\Controllers\API\CriterePointageApiController::class, 'checkEmployeCritere']);
    
    // Vérifier si un département a déjà un critère défini
    Route::get('/departement/{departementId}', [\App\Http\Controllers\API\CriterePointageApiController::class, 'checkDepartementCritere']);
    
    // Vérifier la validité d'un planning pour une période donnée
    Route::post('/validate-planning', [\App\Http\Controllers\API\CriterePointageApiController::class, 'validatePlanning']);
    
    // Modifier un critère
    Route::put('/{id}', [\App\Http\Controllers\API\CriterePointageApiController::class, 'update']);
    
    // Supprimer un critère
    Route::delete('/{id}', [\App\Http\Controllers\API\CriterePointageApiController::class, 'destroy']);
});