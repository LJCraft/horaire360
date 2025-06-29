<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricSyncLog;
use App\Services\BiometricSync\Factories\DriverFactory;
use App\Services\BiometricSync\Drivers\ApiFacialDriver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service de synchronisation pour les appareils API-FACIAL
 * Gère la récupération automatique des pointages depuis l'application mobile
 */
class ApiFacialSyncService
{
    protected $driverFactory;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->driverFactory = $driverFactory;
    }

    /**
     * Synchroniser tous les appareils API-FACIAL actifs
     */
    public function syncAllDevices(): array
    {
        $devices = BiometricDevice::where('brand', 'api-facial')
                                 ->where('is_active', true)
                                 ->get();

        $results = [
            'total_devices' => $devices->count(),
            'successful_syncs' => 0,
            'failed_syncs' => 0,
            'total_records' => 0,
            'devices' => []
        ];

        foreach ($devices as $device) {
            $deviceResult = $this->syncDevice($device);
            $results['devices'][$device->id] = $deviceResult;
            
            if ($deviceResult['success']) {
                $results['successful_syncs']++;
                $results['total_records'] += $deviceResult['records_count'];
            } else {
                $results['failed_syncs']++;
            }
        }

        Log::info('API-FACIAL: Synchronisation globale terminée', $results);
        return $results;
    }

    /**
     * Synchroniser un appareil spécifique
     */
    public function syncDevice(BiometricDevice $device): array
    {
        $startTime = Carbon::now();
        
        try {
            Log::info("API-FACIAL: Début synchronisation appareil", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'api_url' => $device->api_url
            ]);

            // Créer le driver
            $driver = $this->driverFactory->create('api-facial', $device);
            
            if (!$driver instanceof ApiFacialDriver) {
                throw new Exception("Driver invalide pour l'appareil API-FACIAL");
            }

            // Tester la connexion
            if (!$driver->testConnection()) {
                throw new Exception("Impossible de se connecter à l'API");
            }

            // Récupérer les données de pointage
            $attendanceData = $driver->fetchAttendanceData();
            
            if (empty($attendanceData)) {
                Log::info("API-FACIAL: Aucune nouvelle donnée à synchroniser", [
                    'device_id' => $device->id
                ]);
                
                return [
                    'success' => true,
                    'records_count' => 0,
                    'message' => 'Aucune nouvelle donnée',
                    'sync_duration' => $startTime->diffInSeconds(Carbon::now())
                ];
            }

            // Synchroniser en base de données
            $syncResult = $driver->syncToDatabase($attendanceData);
            
            // Mettre à jour l'appareil
            $device->update([
                'last_sync_at' => Carbon::now(),
                'connection_status' => 'connected'
            ]);

            // Enregistrer le log de synchronisation
            $this->logSyncResult($device, true, $syncResult, $startTime);

            Log::info("API-FACIAL: Synchronisation réussie", [
                'device_id' => $device->id,
                'records_processed' => count($attendanceData),
                'success_count' => $syncResult['success'],
                'error_count' => $syncResult['errors'],
                'duplicate_count' => $syncResult['duplicates']
            ]);

            return [
                'success' => true,
                'records_count' => $syncResult['success'],
                'errors_count' => $syncResult['errors'],
                'duplicates_count' => $syncResult['duplicates'],
                'sync_duration' => $startTime->diffInSeconds(Carbon::now()),
                'details' => $syncResult['details']
            ];

        } catch (Exception $e) {
            Log::error("API-FACIAL: Erreur synchronisation", [
                'device_id' => $device->id,
                'error' => $e->getMessage()
            ]);

            // Mettre à jour le statut de l'appareil
            $device->update([
                'connection_status' => 'error'
            ]);

            // Enregistrer le log d'erreur
            $this->logSyncResult($device, false, ['error' => $e->getMessage()], $startTime);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sync_duration' => $startTime->diffInSeconds(Carbon::now())
            ];
        }
    }

    /**
     * Synchroniser manuellement un appareil
     */
    public function manualSync(int $deviceId): array
    {
        $device = BiometricDevice::findOrFail($deviceId);
        
        if ($device->brand !== 'api-facial') {
            throw new Exception("Cet appareil n'est pas de type API-FACIAL");
        }

        Log::info("API-FACIAL: Synchronisation manuelle demandée", [
            'device_id' => $device->id,
            'user_triggered' => true
        ]);

        return $this->syncDevice($device);
    }

    /**
     * Tester la connexion d'un appareil API-FACIAL
     */
    public function testDeviceConnection(int $deviceId): array
    {
        try {
            $device = BiometricDevice::findOrFail($deviceId);
            
            if ($device->brand !== 'api-facial') {
                throw new Exception("Cet appareil n'est pas de type API-FACIAL");
            }

            $driver = $this->driverFactory->create('api-facial', $device);
            $connectionResult = $driver->testConnection();

            // Mettre à jour le statut
            $device->update([
                'connection_status' => $connectionResult ? 'connected' : 'disconnected'
            ]);

            return [
                'success' => $connectionResult,
                'message' => $connectionResult ? 'Connexion réussie' : 'Connexion échouée',
                'device_info' => $connectionResult ? $driver->getDeviceInfo() : null
            ];

        } catch (Exception $e) {
            Log::error("API-FACIAL: Erreur test connexion", [
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les statistiques de synchronisation
     */
    public function getSyncStats(int $deviceId = null): array
    {
        $query = BiometricSyncLog::where('device_brand', 'api-facial');
        
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $logs = $query->orderBy('created_at', 'desc')
                     ->limit(100)
                     ->get();

        $stats = [
            'total_syncs' => $logs->count(),
            'successful_syncs' => $logs->where('success', true)->count(),
            'failed_syncs' => $logs->where('success', false)->count(),
            'last_sync' => $logs->first()?->created_at,
            'total_records_synced' => $logs->where('success', true)->sum('records_synced'),
            'recent_logs' => $logs->take(10)->map(function ($log) {
                return [
                    'date' => $log->created_at,
                    'success' => $log->success,
                    'records_synced' => $log->records_synced,
                    'error_message' => $log->error_message,
                    'sync_duration' => $log->sync_duration
                ];
            })
        ];

        return $stats;
    }

    /**
     * Enregistrer le résultat de synchronisation
     */
    private function logSyncResult(BiometricDevice $device, bool $success, array $result, Carbon $startTime): void
    {
        BiometricSyncLog::create([
            'device_id' => $device->id,
            'device_name' => $device->name,
            'device_brand' => $device->brand,
            'sync_type' => 'api-facial',
            'success' => $success,
            'records_synced' => $success ? ($result['success'] ?? 0) : 0,
            'error_message' => $success ? null : ($result['error'] ?? 'Erreur inconnue'),
            'sync_duration' => $startTime->diffInSeconds(Carbon::now()),
            'sync_details' => json_encode($result)
        ]);
    }

    /**
     * Nettoyer les anciens logs de synchronisation
     */
    public function cleanupOldLogs(int $daysToKeep = 30): int
    {
        $deleted = BiometricSyncLog::where('device_brand', 'api-facial')
                                  ->where('created_at', '<', Carbon::now()->subDays($daysToKeep))
                                  ->delete();

        Log::info("API-FACIAL: Nettoyage des logs", [
            'deleted_count' => $deleted,
            'days_kept' => $daysToKeep
        ]);

        return $deleted;
    }
} 