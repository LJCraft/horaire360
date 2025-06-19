<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FlexibleApiAuth
{
    /**
     * Handle an incoming request.
     * Authentifie soit via session web soit via token Sanctum
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier d'abord l'authentification web (depuis l'interface)
        if (Auth::guard('web')->check()) {
            return $next($request);
        }
        
        // Vérifier l'authentification via session (pour les appels AJAX depuis l'interface)
        if ($request->hasSession() && $request->session()->has('_token')) {
            $user = Auth::guard('web')->user();
            if ($user) {
        return $next($request);
            }
        }
        
        // Sinon, vérifier l'authentification Sanctum (depuis l'app mobile)
        if ($request->bearerToken()) {
            try {
                $user = Auth::guard('sanctum')->user();
                if ($user) {
                    // Définir l'utilisateur authentifié pour la requête
                    Auth::setUser($user);
                    return $next($request);
                }
            } catch (\Exception $e) {
                // Continuer vers l'erreur d'authentification
            }
        }
        
        // Aucune authentification valide trouvée
        return response()->json([
            'status' => 'error',
            'message' => 'Non authentifié',
            'error' => 'Authentification requise via session web ou token API'
        ], 401);
    }
}
