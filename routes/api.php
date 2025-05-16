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