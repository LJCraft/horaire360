<?php

namespace App\Services\BiometricSync;

use App\Models\BiometricDevice;
use App\Models\BiometricSyncLog;
use App\Models\Presence;
use App\Models\Employe;
use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Services\BiometricSync\Contracts\ApiConnectorInterface;
use App\Services\BiometricSync\Factories\DriverFactory;
use App\Services\BiometricSync\Factories\ApiConnectorFactory;
use App\Services\BiometricSync\DataProcessors\BiometricDataProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BiometricSyncService
{
    protected $driverFactory;
    protected $apiConnectorFactory;
    protected $dataProcessor;

    public function __construct(
        DriverFactory $driverFactory,
        ApiConnectorFactory $apiConnectorFactory,
        BiometricDataProcessor $dataProcessor
    ) {
        $this->driverFactory = $driverFactory;
        $this->apiConnectorFactory = $apiConnectorFactory;
        $this->dataProcessor = $dataProcessor;
    }

    /**
     * Tester la connexion d'un appareil
     */
    public function testDeviceConnection(BiometricDevice $device): array
    {
        try {
            if ($device->connection_type === 'ip') {
                $driver = $this->driverFactory->create($device->brand);
                $result = $driver->testConnection($device);
            } else {
                $connector = $this->apiConnectorFactory->create();
                $result = $connector->testConnection($device);
            }

            // Mettre à jour le statut de l'appareil
            if ($result['success']) {
                $device->markAsConnected();
            } else {
                $device->markAsDisconnected($result['message']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Erreur lors du test de connexion de l'appareil {$device->id}", [
                'error' => $e->getMessage(),
                'device' => $device->name
            ]);

            $device->markAsError($e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors du test de connexion : ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Synchroniser les données d'un appareil
     */
    public function syncDevice(BiometricDevice $device, array $options = []): array
    {
        $sessionId = 'sync_' . uniqid();
        $syncType = $options['sync_type'] ?? 'manual';
        $initiatedBy = $options['user_id'] ?? null;

        // Créer un log de synchronisation
        $syncLog = BiometricSyncLog::create([
            'biometric_device_id' => $device->id,
            'sync_session_id' => $sessionId,
            'sync_type' => $syncType,
            'status' => 'started',
            'started_at' => now(),
            'initiated_by' => $initiatedBy,
            'client_ip' => request()->ip(),
        ]);

        Log::info("Début de synchronisation de l'appareil {$device->name}", [
            'session_id' => $sessionId,
            'device_id' => $device->id,
            'sync_type' => $syncType
        ]);

        try {
            // Récupérer les données selon le type de connexion
            if ($device->connection_type === 'ip') {
                $result = $this->syncFromIPDevice($device, $options);
            } else {
                $result = $this->syncFromAPIDevice($device, $options);
            }

            if (!$result['success']) {
                $syncLog->markAsFailed($result, "Échec de la récupération des données");
                return $result;
            }

            // Traiter les données récupérées
            $processResult = $this->dataProcessor->processData($result['data'], $device, $sessionId);

            // Mettre à jour le log avec les statistiques
            $syncLog->updateProcessingStats(
                $processResult['total_processed'],
                $processResult['inserted'],
                $processResult['updated'],
                $processResult['ignored'],
                $processResult['errors']
            );

            // Marquer la synchronisation comme terminée
            if ($processResult['errors'] > 0 && $processResult['inserted'] + $processResult['updated'] === 0) {
                $syncLog->markAsFailed($processResult['error_details'], $processResult['summary']);
            } elseif ($processResult['errors'] > 0) {
                $syncLog->markAsPartial($processResult['error_details'], $processResult['summary']);
            } else {
                $syncLog->markAsSuccessful($processResult['summary']);
            }

            // Mettre à jour les statistiques de l'appareil
            $device->incrementSyncStats(
                $processResult['inserted'],
                $processResult['updated'],
                $processResult['errors']
            );

            Log::info("Synchronisation terminée pour l'appareil {$device->name}", [
                'session_id' => $sessionId,
                'inserted' => $processResult['inserted'],
                'updated' => $processResult['updated'],
                'errors' => $processResult['errors']
            ]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'stats' => $processResult,
                'message' => $processResult['summary']
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors de la synchronisation de l'appareil {$device->name}", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $syncLog->markAsFailed(['error' => $e->getMessage()], "Erreur technique : " . $e->getMessage());

            return [
                'success' => false,
                'session_id' => $sessionId,
                'message' => 'Erreur lors de la synchronisation : ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchroniser depuis un appareil IP
     */
    protected function syncFromIPDevice(BiometricDevice $device, array $options): array
    {
        $driver = $this->driverFactory->create($device->brand);
        
        if (!$driver->isAvailable()) {
            return [
                'success' => false,
                'message' => "Driver {$device->brand} non disponible",
                'data' => []
            ];
        }

        return $driver->syncData($device, $options);
    }

    /**
     * Synchroniser depuis un appareil API
     */
    protected function syncFromAPIDevice(BiometricDevice $device, array $options): array
    {
        $connector = $this->apiConnectorFactory->create();
        
        if ($device->is_push_mode) {
            return [
                'success' => false,
                'message' => "Appareil en mode Push - utilisez l'endpoint webhook",
                'data' => []
            ];
        }

        return $connector->pullData($device, $options);
    }

    /**
     * Synchroniser tous les appareils actifs
     */
    public function syncAllActiveDevices(array $options = []): array
    {
        $devices = BiometricDevice::active()->get();
        $results = [];

        foreach ($devices as $device) {
            $results[$device->id] = $this->syncDevice($device, $options);
        }

        return $results;
    }

    /**
     * Traiter les données Push reçues
     */
    public function processPushData(BiometricDevice $device, array $payload): array
    {
        $sessionId = 'push_' . uniqid();

        // Créer un log de synchronisation Push
        $syncLog = BiometricSyncLog::create([
            'biometric_device_id' => $device->id,
            'sync_session_id' => $sessionId,
            'sync_type' => 'push',
            'status' => 'started',
            'started_at' => now(),
            'client_ip' => request()->ip(),
        ]);

        try {
            $connector = $this->apiConnectorFactory->create();
            $result = $connector->processPushData($device, $payload);

            if ($result['success']) {
                $syncLog->markAsSuccessful("Données Push traitées avec succès");
            } else {
                $syncLog->markAsFailed(['error' => $result['message']], "Échec du traitement Push");
            }

            return $result;

        } catch (\Exception $e) {
            $syncLog->markAsFailed(['error' => $e->getMessage()], "Erreur technique Push");
            
            return [
                'success' => false,
                'message' => 'Erreur lors du traitement Push : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les statistiques de synchronisation pour un appareil
     */
    public function getDeviceStats(BiometricDevice $device, int $days = 30): array
    {
        $logs = $device->syncLogs()
            ->where('started_at', '>=', now()->subDays($days))
            ->orderBy('started_at', 'desc')
            ->get();

        return [
            'total_syncs' => $logs->count(),
            'successful_syncs' => $logs->where('status', 'success')->count(),
            'failed_syncs' => $logs->where('status', 'failed')->count(),
            'last_sync' => $device->last_sync_at,
            'total_records' => $logs->sum('records_inserted') + $logs->sum('records_updated'),
            'average_duration' => $logs->whereNotNull('duration_seconds')->avg('duration_seconds'),
            'recent_logs' => $logs->take(10)
        ];
    }

    /**
     * Obtenir un résumé global des synchronisations
     */
    public function getGlobalSyncStats(int $days = 30): array
    {
        $devices = BiometricDevice::with(['recentSyncLogs'])->get();
        $totalLogs = BiometricSyncLog::where('started_at', '>=', now()->subDays($days))->get();

        return [
            'total_devices' => $devices->count(),
            'active_devices' => $devices->where('active', true)->count(),
            'connected_devices' => $devices->where('connection_status', 'connected')->count(),
            'total_syncs' => $totalLogs->count(),
            'successful_syncs' => $totalLogs->where('status', 'success')->count(),
            'failed_syncs' => $totalLogs->where('status', 'failed')->count(),
            'total_records_synced' => $totalLogs->sum('records_inserted') + $totalLogs->sum('records_updated'),
            'devices_by_brand' => $devices->groupBy('brand')->map->count(),
            'devices_by_status' => $devices->groupBy('connection_status')->map->count()
        ];
    }
} 