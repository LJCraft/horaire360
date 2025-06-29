<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricSyncLog;
use App\Models\Presence;
use App\Models\Employe;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class BiometricSynchronizationService
{
    /**
     * Synchroniser tous les appareils connectés
     * 
     * @return array Résultats de la synchronisation
     */
    public function synchronizeAllConnectedDevices(): array
    {
        $results = [
            'success' => true,
            'total_devices' => 0,
            'synchronized_devices' => 0,
            'total_records' => 0,
            'processed_records' => 0,
            'errors' => [],
            'devices_results' => [],
            'execution_time' => 0
        ];

        $startTime = microtime(true);

        try {
            // Récupérer tous les appareils actifs et connectés
            $connectedDevices = BiometricDevice::where('active', true)
                ->where('connection_status', 'connected')
                ->get();

            $results['total_devices'] = $connectedDevices->count();

            if ($results['total_devices'] === 0) {
                $results['errors'][] = 'Aucun appareil connecté et actif trouvé';
                return $results;
            }

            // Synchroniser chaque appareil
            foreach ($connectedDevices as $device) {
                $deviceResult = $this->synchronizeDevice($device);
                $results['devices_results'][] = $deviceResult;

                if ($deviceResult['success']) {
                    $results['synchronized_devices']++;
                    $results['total_records'] += $deviceResult['total_records'];
                    $results['processed_records'] += $deviceResult['processed_records'];
                } else {
                    $results['errors'] = array_merge($results['errors'], $deviceResult['errors']);
                }
            }

            $results['execution_time'] = round(microtime(true) - $startTime, 2);

            // Log de la synchronisation globale
            $this->logSynchronization('global', $results);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Erreur critique : ' . $e->getMessage();
            Log::error('Erreur lors de la synchronisation globale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $results;
    }

    /**
     * Synchroniser un appareil spécifique
     * 
     * @param BiometricDevice $device
     * @return array
     */
    private function synchronizeDevice(BiometricDevice $device): array
    {
        $result = [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'success' => false,
            'total_records' => 0,
            'processed_records' => 0,
            'errors' => [],
            'execution_time' => 0
        ];

        $startTime = microtime(true);

        try {
            // Choisir le bon driver selon le type de connexion
            $driver = $this->getDriver($device);
            
            if (!$driver) {
                $result['errors'][] = "Driver non disponible pour le type : {$device->connection_type}";
                return $result;
            }

            // Récupérer les données de l'appareil
            $rawData = $driver->fetchAttendanceData($device);
            $result['total_records'] = count($rawData);

            if (empty($rawData)) {
                $result['success'] = true; // Pas d'erreur, juste pas de nouvelles données
                $result['errors'][] = "Aucune nouvelle donnée trouvée sur l'appareil";
                return $result;
            }

            // Traiter et injecter les données
            $processedCount = $this->processAttendanceData($rawData, $device);
            $result['processed_records'] = $processedCount;
            $result['success'] = true;

            // Mettre à jour la date de dernière synchronisation
            $device->update(['last_sync_at' => now()]);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("Erreur lors de la synchronisation de l'appareil {$device->name}", [
                'device_id' => $device->id,
                'error' => $e->getMessage()
            ]);
        }

        $result['execution_time'] = round(microtime(true) - $startTime, 2);
        
        // Log de la synchronisation de l'appareil
        $this->logDeviceSynchronization($device, $result);

        return $result;
    }

    /**
     * Obtenir le driver approprié selon le type d'appareil
     * 
     * @param BiometricDevice $device
     * @return mixed
     */
    private function getDriver(BiometricDevice $device)
    {
        switch ($device->connection_type) {
            case 'ip':
                return $this->getIpDriver($device);
            case 'api':
                return $this->getApiDriver($device);
            default:
                return null;
        }
    }

    /**
     * Driver pour connexion IP/TCP
     * 
     * @param BiometricDevice $device
     * @return object
     */
    private function getIpDriver(BiometricDevice $device)
    {
        return new class($device) {
            private $device;

            public function __construct($device)
            {
                $this->device = $device;
            }

            public function fetchAttendanceData($device): array
            {
                // Simuler la récupération des données via TCP/IP
                // En production, ici vous utiliseriez les vraies APIs des appareils
                
                $mockData = [];
                
                // Exemple de données simulées (format proche du .dat)
                for ($i = 0; $i < rand(5, 15); $i++) {
                    $employeId = rand(1, 50);
                    $date = Carbon::now()->subDays(rand(0, 7))->format('Y-m-d');
                    $time = Carbon::now()->subHours(rand(1, 12))->format('H:i:s');
                    $type = rand(0, 1); // 0 = sortie, 1 = entrée
                    
                    $mockData[] = [
                        'employee_id' => $employeId,
                        'date' => $date,
                        'time' => $time,
                        'type' => $type,
                        'terminal_id' => $device->id, // Utiliser l'ID de l'appareil
                        'device_id' => $device->id,
                        'raw_line' => "{$employeId}  {$date}  {$time}  {$type}  {$device->id}"
                    ];
                }
                
                return $mockData;
            }
        };
    }

    /**
     * Driver pour connexion API REST
     * 
     * @param BiometricDevice $device
     * @return object
     */
    private function getApiDriver(BiometricDevice $device)
    {
        return new class($device) {
            private $device;

            public function __construct($device)
            {
                $this->device = $device;
            }

            public function fetchAttendanceData($device): array
            {
                // Simuler un appel API REST
                // En production, ici vous feriez un vrai appel HTTP à l'API de l'appareil
                
                $mockData = [];
                
                // Exemple de données API simulées
                for ($i = 0; $i < rand(3, 10); $i++) {
                    $employeId = rand(1, 50);
                    $datetime = Carbon::now()->subHours(rand(1, 48));
                    
                    $mockData[] = [
                        'employee_id' => $employeId,
                        'date' => $datetime->format('Y-m-d'),
                        'time' => $datetime->format('H:i:s'),
                        'type' => rand(0, 1),
                        'device_id' => $device->id,
                        'source' => 'api_rest'
                    ];
                }
                
                return $mockData;
            }
        };
    }

    /**
     * Traiter et injecter les données de présence
     * 
     * @param array $rawData
     * @param BiometricDevice $device
     * @return int Nombre d'enregistrements traités
     */
    private function processAttendanceData(array $rawData, BiometricDevice $device): int
    {
        $processedCount = 0;

        foreach ($rawData as $record) {
            try {
                // Vérifier si l'employé existe
                $employe = Employe::find($record['employee_id']);
                if (!$employe) {
                    Log::warning("Employé non trouvé pour ID: {$record['employee_id']}");
                    continue;
                }

                // Créer ou mettre à jour la présence
                $presence = $this->createOrUpdatePresence($record, $device, $employe);
                
                if ($presence) {
                    $processedCount++;
                }

            } catch (\Exception $e) {
                Log::error("Erreur lors du traitement d'un enregistrement", [
                    'record' => $record,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedCount;
    }

    /**
     * Créer ou mettre à jour une présence
     * 
     * @param array $record
     * @param BiometricDevice $device
     * @param Employe $employe
     * @return Presence|null
     */
    private function createOrUpdatePresence(array $record, BiometricDevice $device, Employe $employe): ?Presence
    {
        $date = $record['date'];
        $time = $record['time'];
        $type = $record['type']; // 0 = sortie, 1 = entrée

        // Chercher une présence existante pour cette date et cet employé
        $presence = Presence::where('employe_id', $employe->id)
            ->where('date', $date)
            ->first();

        $metaData = [
            'type' => 'biometric_sync',
            'source' => 'synchronisation_automatique',
            'device_id' => $device->id,
            'device_name' => $device->name,
            'device_brand' => $device->brand,
            'device_ip' => $device->ip_address,
            'connection_type' => $device->connection_type,
            'terminal_id' => $record['terminal_id'] ?? $device->id,
            'type_pointage' => $type,
            'sync_type' => $device->connection_type, // ip, api, etc.
            'sync_timestamp' => now()->timestamp,
            'sync_session' => 'sync_' . $device->id . '_' . now()->format('YmdHis')
        ];

        if (isset($record['raw_line'])) {
            $metaData['raw_line'] = $record['raw_line'];
        }

        if (!$presence) {
            // Créer une nouvelle présence
            $presence = new Presence([
                'employe_id' => $employe->id,
                'date' => $date,
                'source_pointage' => 'synchronisation',
                'meta_data' => json_encode($metaData)
            ]);
        }

        // Mettre à jour les heures selon le type de pointage
        if ($type == 1) { // Entrée
            $presence->heure_arrivee = $time;
        } else { // Sortie
            $presence->heure_depart = $time;
        }

        // Calculer les heures travaillées si on a les deux heures
        if ($presence->heure_arrivee && $presence->heure_depart) {
            $debut = Carbon::parse($presence->heure_arrivee);
            $fin = Carbon::parse($presence->heure_depart);
            
            if ($fin < $debut) {
                $fin->addDay();
            }
            
            $presence->heures_travaillees = $debut->diffInHours($fin);
        }

        $presence->save();

        return $presence;
    }

    /**
     * Logger la synchronisation globale
     * 
     * @param string $type
     * @param array $results
     */
    private function logSynchronization(string $type, array $results): void
    {
        // Pour la synchronisation globale, on crée un log synthétique
        BiometricSyncLog::create([
            'biometric_device_id' => null, // Synchronisation globale
            'sync_session_id' => 'global_' . now()->format('YmdHis'),
            'sync_type' => 'manual',
            'status' => $results['success'] ? 'success' : 'failed',
            'records_processed' => $results['total_records'],
            'records_inserted' => $results['processed_records'],
            'records_updated' => 0,
            'records_ignored' => $results['total_records'] - $results['processed_records'],
            'records_errors' => 0,
            'started_at' => now()->subSeconds($results['execution_time']),
            'completed_at' => now(),
            'duration_seconds' => (int)$results['execution_time'],
            'summary' => "Synchronisation globale: {$results['synchronized_devices']}/{$results['total_devices']} appareils",
            'error_details' => $results['errors'],
            'initiated_by' => 'system'
        ]);
    }

    /**
     * Logger la synchronisation d'un appareil
     * 
     * @param BiometricDevice $device
     * @param array $result
     */
    private function logDeviceSynchronization(BiometricDevice $device, array $result): void
    {
        BiometricSyncLog::create([
            'biometric_device_id' => $device->id,
            'sync_session_id' => 'device_' . $device->id . '_' . now()->format('YmdHis'),
            'sync_type' => 'manual',
            'status' => $result['success'] ? 'success' : 'failed',
            'records_processed' => $result['total_records'],
            'records_inserted' => $result['processed_records'],
            'records_updated' => 0,
            'records_ignored' => $result['total_records'] - $result['processed_records'],
            'records_errors' => count($result['errors']),
            'started_at' => now()->subSeconds($result['execution_time']),
            'completed_at' => now(),
            'duration_seconds' => (int)$result['execution_time'],
            'summary' => "Synchronisation {$device->name}: {$result['processed_records']}/{$result['total_records']} traités",
            'error_details' => $result['errors'],
            'initiated_by' => 'system'
        ]);
    }

    /**
     * Récupérer les logs de synchronisation récents
     * 
     * @param int $limit
     * @return Collection
     */
    public function getRecentSyncLogs(int $limit = 50): Collection
    {
        return BiometricSyncLog::with('biometricDevice')
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }
} 