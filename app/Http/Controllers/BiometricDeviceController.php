<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class BiometricDeviceController extends Controller
{
    /**
     * Afficher la liste des appareils biométriques
     */
    public function index()
    {
        try {
            // Récupérer tous les appareils biométriques
            $devices = BiometricDevice::orderBy('created_at', 'desc')->get();
            
            // Calculer les statistiques
            $totalDevices = $devices->count();
            $activeDevices = $devices->where('is_active', true)->count();
            $connectedDevices = $devices->where('connection_status', 'connected')->count();
            
            return view('biometric-devices.index', [
                'devices' => $devices,
                'stats' => [
                    'total_devices' => $totalDevices,
                    'active_devices' => $activeDevices,
                    'connected_devices' => $connectedDevices,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Erreur lors du chargement des appareils biométriques: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors du chargement des appareils');
        }
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $brands = [
            'zkteco' => 'ZKTeco',
            'suprema' => 'Suprema',
            'hikvision' => 'Hikvision',
            'anviz' => 'Anviz',
            'api-facial' => 'API-FACIAL',
            'generic' => 'Générique'
        ];
        
        return view('biometric-devices.create', compact('brands'));
    }

    /**
     * Enregistrer un nouvel appareil
     */
    public function store(Request $request)
    {
        try {
            // Validation des données avec règles spécifiques pour API-FACIAL
            $rules = [
                'name' => 'required|string|max:255',
                'brand' => 'required|string',
                'model' => 'nullable|string|max:255',
                'connection_type' => 'required|in:ip,api',
                'sync_interval' => 'nullable|integer|min:60|max:86400',
                'active' => 'nullable|boolean',
            ];

            // Règles conditionnelles selon la marque
            if ($request->brand === 'api-facial') {
                // Pour API-FACIAL, forcer connection_type à 'api' et valider l'URL spécifique
                $rules['api_facial_url'] = 'required|url';
                $rules['api_facial_token'] = 'nullable|string|max:500';
                $rules['api_facial_format'] = 'nullable|in:json,xml';
                // Pas besoin de valider connection_type pour API-FACIAL, on le force à 'api'
                $request->merge(['connection_type' => 'api']);
            } else {
                // Pour les autres marques, connection_type est requis
                $rules['connection_type'] = 'required|in:ip,api';
                if ($request->connection_type === 'ip') {
                    $rules['ip_address'] = 'required|ip';
                    $rules['port'] = 'required|integer|between:1,65535';
                } elseif ($request->connection_type === 'api') {
                    $rules['api_url'] = 'required|url';
                }
            }

            $validated = $request->validate($rules);
            
            // Déterminer l'URL API selon la marque
            $apiUrl = null;
            $username = null;
            $password = null;
            
            if ($validated['brand'] === 'api-facial') {
                $apiUrl = $validated['api_facial_url'] ?? null;
                $username = $validated['api_facial_token'] ?? null; // Stocker le token dans username
                $password = $validated['api_facial_format'] ?? 'json'; // Stocker le format dans password
            } else {
                $apiUrl = $validated['api_url'] ?? null;
            }
            
            // Créer l'appareil biométrique
            $device = BiometricDevice::create([
                'name' => $validated['name'],
                'brand' => $validated['brand'],
                'model' => $validated['model'] ?? null,
                'connection_type' => $validated['connection_type'],
                'ip_address' => $validated['ip_address'] ?? null,
                'port' => $validated['port'] ?? null,
                'api_url' => $apiUrl,
                'username' => $username,
                'password' => $password,
                'sync_interval' => $validated['sync_interval'] ?? 300,
                'is_active' => $request->has('active'),
                'connection_status' => 'disconnected',
                'last_sync_at' => null,
            ]);
            
            Log::info('Nouvel appareil biométrique créé', [
                'device_id' => $device->id,
                'name' => $device->name,
                'brand' => $device->brand
            ]);
            
            return redirect()->route('biometric-devices.index')
                ->with('success', 'Appareil biométrique créé avec succès !');
                
        } catch (Exception $e) {
            Log::error('Erreur lors de la création de l\'appareil biométrique: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un appareil
     */
    public function destroy($id)
    {
        try {
            $device = BiometricDevice::findOrFail($id);
            $deviceName = $device->name;
            
            Log::info('Suppression appareil biométrique', [
                'device_id' => $device->id,
                'name' => $deviceName
            ]);
            
            $device->delete();
            
            return response()->json([
                'success' => true,
                'message' => "L'appareil \"{$deviceName}\" a été supprimé avec succès."
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Tentative de suppression d\'un appareil inexistant', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Appareil introuvable.'
            ], 404);
            
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'appareil biométrique: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion d'un appareil
     */
    public function testConnection($id)
    {
        try {
            $device = BiometricDevice::findOrFail($id);
            
            Log::info('Test de connexion pour appareil biométrique', [
                'device_id' => $device->id,
                'name' => $device->name,
                'connection_type' => $device->connection_type
            ]);
            
            // Simuler un test de connexion (en production, utiliser le vrai driver)
            $isConnected = $this->performConnectionTest($device);
            
            if ($isConnected) {
                $device->update(['connection_status' => 'connected']);
                Log::info('Test de connexion réussi', ['device_id' => $device->id]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Connexion réussie à l'appareil \"{$device->name}\""
                ]);
            } else {
                $device->update(['connection_status' => 'disconnected']);
                Log::warning('Test de connexion échoué', ['device_id' => $device->id]);
                
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de se connecter à l'appareil \"{$device->name}\""
                ], 422);
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Tentative de test sur un appareil inexistant', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Appareil introuvable.'
            ], 404);
            
        } catch (Exception $e) {
            Log::error('Erreur lors du test de connexion: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Effectuer le test de connexion réel
     */
    private function performConnectionTest(BiometricDevice $device): bool
    {
        // Pour le moment, simuler un test de connexion
        // En production, utiliser le driver approprié selon la marque
        
        if ($device->connection_type === 'ip') {
            // Test de connexion IP
            return $this->testIpConnection($device->ip_address, $device->port);
        } else {
            // Test de connexion API
            return $this->testApiConnection($device->api_url);
        }
    }

    /**
     * Tester la connexion IP
     */
    private function testIpConnection($ip, $port): bool
    {
        try {
            $connection = @fsockopen($ip, $port, $errno, $errstr, 5);
            if ($connection) {
                fclose($connection);
                return true;
            }
            return false;
        } catch (Exception $e) {
            Log::error('Erreur test connexion IP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tester la connexion API
     */
    private function testApiConnection($apiUrl): bool
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Considérer comme réussi si le code HTTP est entre 200 et 399
            return ($httpCode >= 200 && $httpCode < 400);
        } catch (Exception $e) {
            Log::error('Erreur test connexion API: ' . $e->getMessage());
            return false;
        }
    }
} 