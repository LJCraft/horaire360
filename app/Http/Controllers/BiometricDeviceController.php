<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\BiometricSynchronizationService; // Added this import

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
            $activeDevices = $devices->where('active', true)->count();
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
                'device_id' => 'required|string|max:255',
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
                    $rules['username'] = 'nullable|string|max:255';
                    $rules['password'] = 'nullable|string|max:255';
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
                $username = $validated['username'] ?? null;
                $password = $validated['password'] ?? null;
            }
            
            // Créer l'appareil biométrique
            $device = BiometricDevice::create([
                'name' => $validated['name'],
                'brand' => $validated['brand'],
                'model' => $validated['model'] ?? null,
                'device_id' => $validated['device_id'],
                'connection_type' => $validated['connection_type'],
                'ip_address' => $validated['ip_address'] ?? null,
                'port' => $validated['port'] ?? null,
                'api_url' => $apiUrl,
                'username' => $username,
                'password' => $password,
                'sync_interval' => $validated['sync_interval'] ?? 300,
                'active' => $request->has('active'),
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
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        try {
            $device = BiometricDevice::findOrFail($id);
            
            $brands = [
                'zkteco' => 'ZKTeco',
                'suprema' => 'Suprema',
                'hikvision' => 'Hikvision',
                'anviz' => 'Anviz',
                'api-facial' => 'API-FACIAL',
                'generic' => 'Générique'
            ];
            
            return view('biometric-devices.edit', compact('device', 'brands'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('biometric-devices.index')->with('error', 'Appareil introuvable.');
        } catch (Exception $e) {
            Log::error('Erreur lors du chargement de l\'appareil pour édition: ' . $e->getMessage());
            return redirect()->route('biometric-devices.index')->with('error', 'Erreur lors du chargement de l\'appareil');
        }
    }

    /**
     * Mettre à jour un appareil
     */
    public function update(Request $request, $id)
    {
        try {
            $device = BiometricDevice::findOrFail($id);
            
            // Validation des données avec règles spécifiques pour API-FACIAL
            $rules = [
                'name' => 'required|string|max:255',
                'brand' => 'required|string',
                'model' => 'nullable|string|max:255',
                'device_id' => 'required|string|max:255',
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
                    $rules['username'] = 'nullable|string|max:255';
                    $rules['password'] = 'nullable|string|max:255';
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
                $username = $validated['username'] ?? null;
                $password = $validated['password'] ?? null;
            }
            
            // Mettre à jour l'appareil biométrique
            $device->update([
                'name' => $validated['name'],
                'brand' => $validated['brand'],
                'model' => $validated['model'] ?? null,
                'device_id' => $validated['device_id'],
                'connection_type' => $validated['connection_type'],
                'ip_address' => $validated['ip_address'] ?? null,
                'port' => $validated['port'] ?? null,
                'api_url' => $apiUrl,
                'username' => $username,
                'password' => $password,
                'sync_interval' => $validated['sync_interval'] ?? 300,
                'active' => $request->has('active'),
                // Réinitialiser le statut de connexion si des paramètres critiques changent
                'connection_status' => 'disconnected',
            ]);
            
            Log::info('Appareil biométrique mis à jour', [
                'device_id' => $device->id,
                'name' => $device->name,
                'brand' => $device->brand
            ]);
            
            return redirect()->route('biometric-devices.index')
                ->with('success', 'Appareil biométrique mis à jour avec succès !');
                
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('biometric-devices.index')->with('error', 'Appareil introuvable.');
        } catch (Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'appareil biométrique: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
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
     * Déconnecter un appareil du système
     */
    public function disconnect($id)
    {
        try {
            $device = BiometricDevice::findOrFail($id);
            
            Log::info('Déconnexion de l\'appareil biométrique', [
                'device_id' => $device->id,
                'name' => $device->name,
                'current_status' => $device->connection_status
            ]);
            
            // Mettre à jour le statut de connexion
            $device->update([
                'connection_status' => 'disconnected',
                'last_sync_at' => null
            ]);
            
            Log::info('Appareil déconnecté avec succès', ['device_id' => $device->id]);
            
            return response()->json([
                'success' => true,
                'message' => "L'appareil \"{$device->name}\" a été déconnecté avec succès."
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Tentative de déconnexion d\'un appareil inexistant', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Appareil introuvable.'
            ], 404);
            
        } catch (Exception $e) {
            Log::error('Erreur lors de la déconnexion de l\'appareil: ' . $e->getMessage(), [
                'id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion : ' . $e->getMessage()
            ], 500);
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

    /**
     * 🔄 SYNCHRONISATION WEB AMÉLIORÉE avec consolidation intelligente
     */
    public function sync(Request $request)
    {
        try {
            $deviceIds = $request->input('device_ids', []);
            $ignoreExisting = $request->boolean('ignore_existing', false);
            
            Log::info("🚀 DÉBUT SYNCHRONISATION WEB CONSOLIDÉE", [
                'device_ids' => $deviceIds,
                'ignore_existing' => $ignoreExisting,
                'user' => auth()->user()->name ?? 'système',
                'sync_time' => now()->format('Y-m-d H:i:s')
            ]);

            if (empty($deviceIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun appareil sélectionné pour la synchronisation.'
                ], 400);
            }

            $devices = BiometricDevice::whereIn('id', $deviceIds)->get();
            $results = [];
            $totalInserted = 0;
            $totalUpdated = 0;
            $totalProcessed = 0;

            // Synchroniser chaque appareil avec la nouvelle logique
            foreach ($devices as $device) {
                try {
                    Log::info("📱 SYNCHRONISATION APPAREIL", [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'device_type' => $device->type,
                        'api_url' => $device->api_url
                    ]);

                    // Utiliser la nouvelle méthode de synchronisation consolidée
                    $syncService = new BiometricSynchronizationService();
                    $result = $syncService->syncDevice($device);
                    
                    $results[] = $result;
                    
                    if ($result['success']) {
                        $totalInserted += $result['inserted_records'] ?? 0;
                        $totalUpdated += $result['updated_records'] ?? 0;
                        $totalProcessed += $result['processed_records'] ?? 0;
                    }

                    Log::info("✅ RÉSULTAT SYNCHRONISATION", [
                        'device_name' => $device->name,
                        'success' => $result['success'],
                        'inserted' => $result['inserted_records'] ?? 0,
                        'processed' => $result['processed_records'] ?? 0,
                        'message' => $result['message']
                    ]);

                } catch (\Exception $e) {
                    Log::error("❌ ERREUR SYNCHRONISATION APPAREIL", [
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'error' => $e->getMessage()
                    ]);

                    $results[] = [
                        'success' => false,
                        'device' => $device->name,
                        'message' => "Erreur: " . $e->getMessage(),
                        'processed_records' => 0,
                        'inserted_records' => 0
                    ];
                }
            }

            // Calculer le succès global
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalDevices = count($devices);
            $globalSuccess = $successCount === $totalDevices;

            // Message de résumé amélioré
            $summaryMessage = $this->buildSyncSummaryMessage($results, $totalInserted, $totalUpdated, $totalProcessed);

            Log::info("🎉 SYNCHRONISATION WEB TERMINÉE", [
                'total_devices' => $totalDevices,
                'successful_devices' => $successCount,
                'total_inserted' => $totalInserted,
                'total_updated' => $totalUpdated,
                'total_processed' => $totalProcessed,
                'global_success' => $globalSuccess
            ]);

            return response()->json([
                'success' => $globalSuccess,
                'message' => $summaryMessage,
                'results' => $results,
                'summary' => [
                    'devices_synced' => $totalDevices,
                    'successful_devices' => $successCount,
                    'total_inserted' => $totalInserted,
                    'total_updated' => $totalUpdated,
                    'total_processed' => $totalProcessed,
                    'sync_timestamp' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("❌ ERREUR SYNCHRONISATION WEB GLOBALE", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur générale de synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Construire un message de résumé détaillé pour la synchronisation
     */
    private function buildSyncSummaryMessage(array $results, int $totalInserted, int $totalUpdated, int $totalProcessed): string
    {
        $successfulDevices = array_filter($results, fn($r) => $r['success']);
        $failedDevices = array_filter($results, fn($r) => !$r['success']);
        
        $summary = [];
        
        // Statistiques principales
        if (count($successfulDevices) > 0) {
            $summary[] = count($successfulDevices) . " appareil(s) synchronisé(s)";
            
            if ($totalInserted > 0) {
                $summary[] = "{$totalInserted} nouveau(x) pointage(s) consolidé(s)";
            }
            
            if ($totalUpdated > 0) {
                $summary[] = "{$totalUpdated} pointage(s) mis à jour";
            }
            
            if ($totalProcessed === 0) {
                $summary[] = "aucune nouvelle donnée trouvée";
            }
        }
        
        // Appareils en échec
        if (count($failedDevices) > 0) {
            $summary[] = count($failedDevices) . " appareil(s) en échec";
        }
        
        // Détails par appareil réussi
        foreach ($successfulDevices as $result) {
            if (($result['inserted_records'] ?? 0) > 0) {
                $deviceInfo = "✅ {$result['device']}: {$result['inserted_records']} pointage(s) récupéré(s)";
                if (isset($result['execution_time'])) {
                    $deviceInfo .= " ({$result['execution_time']}s)";
                }
                $summary[] = $deviceInfo;
            }
        }
        
        return implode(', ', $summary) ?: 'Synchronisation terminée';
    }
} 