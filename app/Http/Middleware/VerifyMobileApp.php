<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMobileApp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier que la requête provient bien de notre application mobile
        if (!$request->hasHeader('X-Mobile-App') || $request->header('X-Mobile-App') !== config('app.mobile_app_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized mobile application'
            ], 403);
        }
        
        // Vérifier la version de l'application
        if ($request->hasHeader('X-App-Version')) {
            $version = $request->header('X-App-Version');
            $minVersion = config('app.min_mobile_version', '1.0.0');
            
            if (version_compare($version, $minVersion, '<')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application version not supported. Please update your app.',
                    'min_version' => $minVersion
                ], 426); // Upgrade Required
            }
        }

        return $next($request);
    }
} 