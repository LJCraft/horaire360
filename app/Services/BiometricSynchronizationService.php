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
     * Synchroniser tous les appareils connectés avec validation renforcée
     * 
     * @param array $options Options de synchronisation
     * @return array Résultats de la synchronisation
     */
    public function synchronizeAllConnectedDevices(array $options = []): array
    {
        $results = [
            'success' => true,
            'total_devices' => 0,
            'synchronized_devices' => 0,
            'total_records' => 0,
            'processed_records' => 0,
            'skipped_records' => 0,
            'invalid_records' => 0,
            'errors' => [],
            'warnings' => [],
            'devices_results' => [],
            'execution_time' => 0,
            'sync_session_id' => 'sync_' . uniqid(),
            'options' => $options
        ];

        $startTime = microtime(true);

        try {
            // Récupérer tous les appareils avec validation stricte
            $connectedDevices = $this->getValidConnectedDevices($options);
            
            Log::info("🔍 Vérification des appareils pour synchronisation", [
                'session_id' => $results['sync_session_id'],
                'total_found' => $connectedDevices->count(),
                'validation_enabled' => $options['validate_production_data'] ?? true
            ]);

            $results['total_devices'] = $connectedDevices->count();

            if ($results['total_devices'] === 0) {
                $results['warnings'][] = 'Aucun appareil valide trouvé pour la synchronisation';
                $results['errors'][] = 'Aucun appareil connecté et actif trouvé ou tous les appareils ont été rejetés par les filtres de validation';
                return $results;
            }

            // Synchroniser chaque appareil avec les options
            foreach ($connectedDevices as $device) {
                $deviceResult = $this->synchronizeDevice($device, $options);
                $results['devices_results'][] = $deviceResult;

                if ($deviceResult['success']) {
                    $results['synchronized_devices']++;
                    $results['total_records'] += $deviceResult['total_records'];
                    $results['processed_records'] += $deviceResult['processed_records'];
                    $results['skipped_records'] += $deviceResult['skipped_records'] ?? 0;
                    $results['invalid_records'] += $deviceResult['invalid_records'] ?? 0;
                } else {
                    $results['errors'] = array_merge($results['errors'], $deviceResult['errors']);
                }
                
                // Collecter les avertissements
                if (!empty($deviceResult['warnings'])) {
                    $results['warnings'] = array_merge($results['warnings'], $deviceResult['warnings']);
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
     * Synchroniser un appareil spécifique avec validation renforcée
     * 
     * @param BiometricDevice $device
     * @param array $options Options de synchronisation
     * @return array
     */
    private function synchronizeDevice(BiometricDevice $device, array $options = []): array
    {
        $result = [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'device_brand' => $device->brand,
            'success' => false,
            'total_records' => 0,
            'processed_records' => 0,
            'skipped_records' => 0,
            'invalid_records' => 0,
            'errors' => [],
            'warnings' => [],
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
                $result['warnings'][] = "❗ Aucun pointage réel n'a été récupéré depuis l'appareil \"{$device->name}\" à " . now()->format('H:i');
                return $result;
            }

            // Valider les données si l'option est activée
            if ($options['validate_production_data'] ?? true) {
                $rawData = $this->validateProductionData($rawData, $device, $options, $result);
                $result['total_records'] = count($rawData); // Mettre à jour après filtrage
            }

            // Traiter et injecter les données avec les nouvelles options
            $processingResult = $this->processAttendanceData($rawData, $device, $options);
            $result['processed_records'] = $processingResult['processed'];
            $result['skipped_records'] = $processingResult['skipped'];
            $result['invalid_records'] = $processingResult['invalid'];
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
     * Traiter et injecter les données de présence avec validation renforcée
     * 
     * @param array $rawData
     * @param BiometricDevice $device
     * @param array $options Options de traitement
     * @return array Statistiques de traitement
     */
    private function processAttendanceData(array $rawData, BiometricDevice $device, array $options = []): array
    {
        $stats = [
            'processed' => 0,
            'skipped' => 0,
            'invalid' => 0,
            'errors' => []
        ];

        $skipExisting = $options['skip_existing'] ?? true;

        foreach ($rawData as $record) {
            try {
                // Vérifier si l'employé existe
                $employe = Employe::find($record['employee_id']);
                if (!$employe) {
                    Log::warning("Employé non trouvé pour ID: {$record['employee_id']}", [
                        'device' => $device->name,
                        'record' => $record
                    ]);
                    $stats['invalid']++;
                    continue;
                }

                // Vérifier les doublons si l'option est activée
                if ($skipExisting && $this->isDuplicateRecord($record, $device, $employe)) {
                    $stats['skipped']++;
                    continue;
                }

                // Créer ou mettre à jour la présence
                $presence = $this->createOrUpdatePresence($record, $device, $employe, $options);
                
                if ($presence) {
                    $stats['processed']++;
                }

            } catch (\Exception $e) {
                Log::error("Erreur lors du traitement d'un enregistrement", [
                    'device' => $device->name,
                    'record' => $record,
                    'error' => $e->getMessage()
                ]);
                $stats['invalid']++;
                $stats['errors'][] = $e->getMessage();
            }
        }

        return $stats;
    }

    /**
     * Créer ou mettre à jour une présence
     * 
     * @param array $record
     * @param BiometricDevice $device
     * @param Employe $employe
     * @param array $options Options de création
     * @return Presence|null
     */
    private function createOrUpdatePresence(array $record, BiometricDevice $device, Employe $employe, array $options = []): ?Presence
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

    /**
     * ✅ VALIDATION STRICTE : Récupérer uniquement les appareils valides et connectés
     * 
     * @param array $options Options de validation
     * @return Collection
     */
    private function getValidConnectedDevices(array $options = []): Collection
    {
        $validateProduction = $options['validate_production_data'] ?? true;
        
        $query = BiometricDevice::where('active', true)
            ->where('connection_status', 'connected');

        if ($validateProduction) {
            // 🚫 Rejeter les appareils de test ou génériques non validés
            $query->where(function($q) {
                $q->where('brand', '!=', 'generic')
                  ->orWhere(function($subq) {
                      // Autoriser les appareils génériques seulement s'ils ont été testés récemment
                      $subq->where('brand', 'generic')
                           ->where('last_connection_test_at', '>=', now()->subHours(24));
                  });
            });

            // 🚫 Rejeter les appareils avec des noms suspects
            $query->where(function($q) {
                $suspiciousNames = ['test', 'demo', 'simulated', 'mock', 'fake'];
                foreach ($suspiciousNames as $name) {
                    $q->where('name', 'not like', "%{$name}%");
                }
            });

            // ✅ Vérifier que la dernière connexion est récente (max 48h)
            $query->where('last_connection_test_at', '>=', now()->subHours(48));
        }

        $devices = $query->get();

        Log::info("🔍 Filtrage des appareils", [
            'total_active' => BiometricDevice::where('active', true)->count(),
            'total_connected' => BiometricDevice::where('active', true)->where('connection_status', 'connected')->count(),
            'valid_after_filtering' => $devices->count(),
            'validation_enabled' => $validateProduction
        ]);

        return $devices;
    }

    /**
     * ✅ VALIDATION DES DONNÉES : Vérifier que les données sont en production et récentes
     * 
     * @param array $rawData Données brutes
     * @param BiometricDevice $device Appareil source
     * @param array $options Options de validation
     * @param array &$result Résultat à modifier
     * @return array Données filtrées
     */
    private function validateProductionData(array $rawData, BiometricDevice $device, array $options, array &$result): array
    {
        $maxAgeHours = $options['max_data_age_hours'] ?? 48;
        $validData = [];
        $invalidCount = 0;
        $tooOldCount = 0;

        foreach ($rawData as $record) {
            // ✅ Vérifier que le timestamp est valide et récent
            try {
                $recordDate = Carbon::parse($record['date'] . ' ' . $record['time']);
                
                // 🚫 Rejeter les données futures (plus de 1 heure dans le futur)
                if ($recordDate->isFuture() && $recordDate->diffInHours(now()) > 1) {
                    $invalidCount++;
                    continue;
                }

                // 🚫 Rejeter les données trop anciennes
                if ($recordDate->diffInHours(now()) > $maxAgeHours) {
                    $tooOldCount++;
                    continue;
                }

                // ✅ Vérifier que les données ne proviennent pas de sources suspectes
                if (isset($record['source'])) {
                    $suspiciousSources = ['test', 'demo', 'simulated', 'mock'];
                    if (in_array(strtolower($record['source']), $suspiciousSources)) {
                        $invalidCount++;
                        continue;
                    }
                }

                $validData[] = $record;

            } catch (\Exception $e) {
                Log::warning("Données invalides détectées", [
                    'device' => $device->name,
                    'record' => $record,
                    'error' => $e->getMessage()
                ]);
                $invalidCount++;
            }
        }

        // Enregistrer les statistiques de validation
        if ($invalidCount > 0) {
            $result['warnings'][] = "🚫 {$invalidCount} pointages invalides rejetés (données futures ou sources suspectes)";
        }
        if ($tooOldCount > 0) {
            $result['warnings'][] = "⏰ {$tooOldCount} pointages trop anciens rejetés (> {$maxAgeHours}h)";
        }

        Log::info("🔍 Validation des données de production", [
            'device' => $device->name,
            'total_input' => count($rawData),
            'valid_output' => count($validData),
            'invalid_rejected' => $invalidCount,
            'too_old_rejected' => $tooOldCount,
            'max_age_hours' => $maxAgeHours
        ]);

        return $validData;
    }

    /**
     * ✅ ANTI-DOUBLONS : Vérifier si un enregistrement existe déjà
     * 
     * @param array $record Enregistrement à vérifier
     * @param BiometricDevice $device Appareil source
     * @param Employe $employe Employé concerné
     * @return bool True si c'est un doublon
     */
    private function isDuplicateRecord(array $record, BiometricDevice $device, Employe $employe): bool
    {
        $date = $record['date'];
        $time = $record['time'];
        $type = $record['type'];

        // Chercher un pointage existant avec les mêmes caractéristiques
        $existing = Presence::where('employe_id', $employe->id)
            ->where('date', $date)
            ->where(function($q) use ($time, $type) {
                if ($type == 1) { // Entrée
                    $q->where('heure_arrivee', $time);
                } else { // Sortie
                    $q->where('heure_depart', $time);
                }
            })
            ->where('source_pointage', 'synchronisation')
            ->whereNotNull('meta_data')
            ->where(function($q) use ($device) {
                // Vérifier si c'est du même appareil ou terminal
                $q->whereRaw("JSON_EXTRACT(meta_data, '$.device_id') = ?", [$device->id])
                  ->orWhereRaw("JSON_EXTRACT(meta_data, '$.terminal_id') = ?", [$device->id]);
            })
            ->first();

        if ($existing) {
            Log::debug("Doublon détecté et ignoré", [
                'employe_id' => $employe->id,
                'date' => $date,
                'time' => $time,
                'type' => $type,
                'device' => $device->name,
                'existing_id' => $existing->id
            ]);
            return true;
        }

        return false;
    }
} 