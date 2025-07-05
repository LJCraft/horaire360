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
     * Synchroniser un appareil biométrique spécifique avec insertion automatique
     */
    public function syncDevice(BiometricDevice $device): array
    {
        try {
            Log::info("🚀 DÉBUT SYNCHRONISATION COMPLÈTE", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'device_type' => $device->type,
                'api_url' => $device->api_url,
                'timezone' => config('app.timezone'),
                'sync_start' => now()->format('Y-m-d H:i:s')
            ]);

            $startTime = microtime(true);

            // Récupérer le driver approprié
            $driver = DriverFactory::create($device);
            
            if (!$driver) {
                throw new \Exception("Driver non disponible pour le type: {$device->type}");
            }

            // Récupérer les données consolidées
            $attendanceData = $driver->fetchAttendanceData();
            
            if (empty($attendanceData)) {
                Log::warning("⚠️ AUCUNE DONNÉE RÉCUPÉRÉE", [
                    'device_id' => $device->id,
                    'device_name' => $device->name
                ]);
                
                return [
                    'success' => true,
                    'device' => $device->name,
                    'message' => 'Synchronisation effectuée - aucune nouvelle donnée',
                    'processed_records' => 0,
                    'inserted_records' => 0,
                    'execution_time' => round(microtime(true) - $startTime, 2)
                ];
            }

            // 🎯 TRAITEMENT AUTOMATIQUE : Insérer les données dans Horaire360
            $insertionResults = $this->insertConsolidatedAttendance($attendanceData, $device);
            
            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info("✅ SYNCHRONISATION TERMINÉE AVEC SUCCÈS", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'raw_records' => count($attendanceData),
                'inserted_records' => $insertionResults['inserted'],
                'updated_records' => $insertionResults['updated'],
                'skipped_records' => $insertionResults['skipped'],
                'execution_time' => $executionTime
            ]);

            return [
                'success' => true,
                'device' => $device->name,
                'message' => "Synchronisation réussie - {$insertionResults['inserted']} nouveaux pointages consolidés",
                'processed_records' => count($attendanceData),
                'inserted_records' => $insertionResults['inserted'],
                'updated_records' => $insertionResults['updated'],
                'execution_time' => $executionTime,
                'details' => $insertionResults
            ];

        } catch (\Exception $e) {
            Log::error("❌ ERREUR SYNCHRONISATION GÉNÉRALE", [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'device' => $device->name,
                'message' => "Erreur: " . $e->getMessage(),
                'processed_records' => 0,
                'inserted_records' => 0
            ];
        }
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
            $rawData = $driver->fetchAttendanceData();
            $result['total_records'] = count($rawData);

            if (empty($rawData)) {
                $result['success'] = true; // Pas d'erreur, juste pas de nouvelles données
                $timezone = config('app.timezone', 'Africa/Douala');
                $localTime = now()->setTimezone($timezone)->format('H:i');
                $result['warnings'][] = "❗ Aucun pointage réel n'a été récupéré depuis l'appareil \"{$device->name}\" à {$localTime} (heure locale)";
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
     */
    private function getIpDriver(BiometricDevice $device)
    {
        // Utiliser le vrai driver selon la marque
        switch ($device->brand) {
            case 'zkteco':
                return new \App\Services\BiometricSync\Drivers\ZKTecoDriver($device);
            case 'hikvision':
                return new \App\Services\BiometricSync\Drivers\HikvisionDriver($device);
            case 'anviz':
                return new \App\Services\BiometricSync\Drivers\AnvizDriver($device);
            case 'suprema':
                return new \App\Services\BiometricSync\Drivers\SupremaDriver($device);
            default:
                return new \App\Services\BiometricSync\Drivers\GenericIPDriver($device);
        }
    }

    /**
     * Driver pour connexion API REST
     */
    private function getApiDriver(BiometricDevice $device)
    {
        // Utiliser le vrai driver selon la marque
        switch ($device->brand) {
            case 'api-facial':
                return new \App\Services\BiometricSync\Drivers\ApiFacialDriver($device);
            default:
                // Pour les autres API, créer un driver générique avec testConnection
                return new class($device) {
                    private $device;

                    public function __construct($device)
                    {
                        $this->device = $device;
                    }

                    public function testConnection(): bool
                    {
                        if (!$this->device->api_url) {
                            return false;
                        }

                        try {
                            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($this->device->api_url);
                            return $response->successful();
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Test de connexion API échoué", [
                                'device' => $this->device->name,
                                'error' => $e->getMessage()
                            ]);
                            return false;
                        }
                    }

                    public function fetchAttendanceData(): array
                    {
                        if (!$this->device->api_url) {
                            return [];
                        }

                        try {
                            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($this->device->api_url);
                            
                            if ($response->successful()) {
                                $data = $response->json();
                                
                                if (isset($data['pointages'])) {
                                    return $data['pointages'];
                                } elseif (isset($data['data'])) {
                                    return $data['data'];
                                } elseif (is_array($data)) {
                                    return $data;
                                }
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Erreur lors de la récupération des données API", [
                                'device' => $this->device->name,
                                'error' => $e->getMessage()
                            ]);
                        }

                        return [];
                    }
                };
        }
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
     * Créer ou mettre à jour une présence avec gestion des fuseaux horaires
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
        $timezone = config('app.timezone', 'Africa/Douala');

        // Chercher une présence existante pour cette date et cet employé
        $presence = Presence::where('employe_id', $employe->id)
            ->where('date', $date)
            ->first();

        $metaData = [
            'type' => 'biometric_sync',
            'source' => 'synchronisation_automatique_temps_reel',
            'device_id' => $device->id,
            'device_name' => $device->name,
            'device_brand' => $device->brand,
            'device_ip' => $device->ip_address,
            'connection_type' => $device->connection_type,
            'terminal_id' => $record['terminal_id'] ?? $device->id,
            'type_pointage' => $type,
            'sync_type' => $device->connection_type,
            'sync_timestamp' => now()->setTimezone($timezone)->format('Y-m-d H:i:s'),
            'timezone' => $timezone,
            'confidence' => $record['confidence'] ?? 100,
            'location' => $record['location'] ?? null,
            'sync_session' => 'sync_' . $device->id . '_' . now()->format('YmdHis'),
            'api_url' => $device->api_url,
            'real_time_sync' => true
        ];

        if (isset($record['raw_line'])) {
            $metaData['raw_line'] = $record['raw_line'];
        }

        if (isset($record['photo_path'])) {
            $metaData['photo_path'] = $record['photo_path'];
        }

        if (!$presence) {
            // Créer une nouvelle présence
            $presence = new Presence([
                'employe_id' => $employe->id,
                'date' => $date,
                'source_pointage' => 'synchronisation',
                'meta_data' => json_encode($metaData)
            ]);
            
            Log::info("API-FACIAL: Création nouvelle présence", [
                'employee_id' => $employe->id,
                'employee_name' => $employe->nom . ' ' . $employe->prenom,
                'date' => $date,
                'time' => $time,
                'type' => $type === 1 ? 'entrée' : 'sortie',
                'timezone' => $timezone,
                'device' => $device->name
            ]);
        } else {
            // Mettre à jour les métadonnées de la présence existante
            $existingMeta = json_decode($presence->meta_data, true) ?? [];
            $presence->meta_data = json_encode(array_merge($existingMeta, $metaData));
            
            Log::info("API-FACIAL: Mise à jour présence existante", [
                'employee_id' => $employe->id,
                'employee_name' => $employe->nom . ' ' . $employe->prenom,
                'date' => $date,
                'time' => $time,
                'type' => $type === 1 ? 'entrée' : 'sortie',
                'timezone' => $timezone,
                'device' => $device->name
            ]);
        }

        // Mettre à jour les heures selon le type de pointage avec gestion des fuseaux horaires
        if ($type == 1) { // Entrée
            $presence->heure_arrivee = $time;
            Log::info("API-FACIAL: Heure d'arrivée enregistrée", [
                'employee' => $employe->nom . ' ' . $employe->prenom,
                'date' => $date,
                'heure_arrivee' => $time,
                'timezone' => $timezone
            ]);
        } else { // Sortie
            $presence->heure_depart = $time;
            Log::info("API-FACIAL: Heure de départ enregistrée", [
                'employee' => $employe->nom . ' ' . $employe->prenom,
                'date' => $date,
                'heure_depart' => $time,
                'timezone' => $timezone
            ]);
        }

        // Calculer les heures travaillées si on a les deux heures
        if ($presence->heure_arrivee && $presence->heure_depart) {
            try {
                $debut = Carbon::parse($presence->heure_arrivee)->setTimezone($timezone);
                $fin = Carbon::parse($presence->heure_depart)->setTimezone($timezone);
                
                // Gérer le cas où la sortie est le lendemain
                if ($fin < $debut) {
                    $fin->addDay();
                }
                
                $heuresTravaillees = $debut->diffInHours($fin, false);
                $presence->heures_travaillees = max(0, $heuresTravaillees); // Éviter les valeurs négatives
                
                Log::info("API-FACIAL: Heures travaillées calculées", [
                    'employee' => $employe->nom . ' ' . $employe->prenom,
                    'date' => $date,
                    'heure_arrivee' => $presence->heure_arrivee,
                    'heure_depart' => $presence->heure_depart,
                    'heures_travaillees' => $presence->heures_travaillees,
                    'timezone' => $timezone
                ]);
            } catch (\Exception $e) {
                Log::warning("API-FACIAL: Erreur calcul heures travaillées", [
                    'error' => $e->getMessage(),
                    'employee' => $employe->nom . ' ' . $employe->prenom,
                    'date' => $date
                ]);
            }
        }

        $presence->save();

        return $presence;
    }

    /**
     * 🎯 INSERTION AUTOMATIQUE OPTIMISÉE : Traiter et insérer les données consolidées rapidement
     * OPTIMISATION: Insertions en lot + logs réduits
     */
    private function insertConsolidatedAttendance(array $consolidatedData, BiometricDevice $device): array
    {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        Log::info("💾 DÉBUT INSERTION OPTIMISÉE", [
            'device_id' => $device->id,
            'records_to_process' => count($consolidatedData)
        ]);

        // Optimisation : Grouper les données par type d'opération
        $toInsert = [];
        $toUpdate = [];
        $employeeIds = array_unique(array_column($consolidatedData, 'employee_id'));
        
        // Vérifier l'existence des employés en une seule requête
        $existingEmployees = \App\Models\Employe::whereIn('id', $employeeIds)->pluck('id')->toArray();
        
        // Récupérer toutes les présences existantes en une seule requête
        $dates = array_unique(array_column($consolidatedData, 'date'));
        $existingPresences = \App\Models\Presence::whereIn('employe_id', $employeeIds)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(function ($presence) {
                return $presence->employe_id . '_' . $presence->date;
            });

        // Traitement optimisé : classifier les enregistrements
        foreach ($consolidatedData as $record) {
            try {
                // Vérifications rapides
                if (!isset($record['employee_id']) || !isset($record['date'])) {
                    $skipped++;
                    continue;
                }

                if (!in_array($record['employee_id'], $existingEmployees)) {
                    $skipped++;
                    continue;
                }

                $key = $record['employee_id'] . '_' . $record['date'];
                $existingPresence = $existingPresences->get($key);

                // Calculer les heures travaillées
                $heuresTravaillees = $this->calculateWorkingHours(
                    $record['heure_arrivee'], 
                    $record['heure_depart'] ?? null
                );

                // Métadonnées optimisées
                $metaData = [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'source' => 'api-facial-consolidated',
                    'sync_timestamp' => now()->format('Y-m-d H:i:s'),
                    'sync_mode' => 'optimized_bulk'
                ];

                $presenceData = [
                    'employe_id' => $record['employee_id'],
                    'date' => $record['date'],
                    'heure_arrivee' => $record['heure_arrivee'],
                    'heure_depart' => $record['heure_depart'],
                    'heures_travaillees' => $heuresTravaillees,
                    'source_pointage' => 'api-facial',
                    'statut' => 'present',
                    'meta_data' => json_encode($metaData)
                ];

                if ($existingPresence) {
                    // Vérifier si mise à jour nécessaire
                    if ($this->needsPresenceUpdateOptimized($existingPresence, $record, $heuresTravaillees)) {
                        $presenceData['id'] = $existingPresence->id;
                        $toUpdate[] = $presenceData;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Ajouter les timestamps pour la création
                    $presenceData['created_at'] = now();
                    $presenceData['updated_at'] = now();
                    $toInsert[] = $presenceData;
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'record' => $record,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Insertion en lot pour les nouveaux enregistrements
        if (!empty($toInsert)) {
            try {
                \App\Models\Presence::insert($toInsert);
                $inserted = count($toInsert);
                Log::info("✅ INSERTION EN LOT", [
                    'device_id' => $device->id,
                    'inserted_count' => $inserted
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur insertion en lot", ['error' => $e->getMessage()]);
                $errors[] = ['type' => 'bulk_insert', 'error' => $e->getMessage()];
            }
        }

        // Mise à jour en lot pour les enregistrements existants
        if (!empty($toUpdate)) {
            try {
                foreach ($toUpdate as $updateData) {
                    $id = $updateData['id'];
                    unset($updateData['id']);
                    $updateData['updated_at'] = now();
                    
                    \App\Models\Presence::where('id', $id)->update($updateData);
                }
                $updated = count($toUpdate);
                Log::info("🔄 MISE À JOUR EN LOT", [
                    'device_id' => $device->id,
                    'updated_count' => $updated
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur mise à jour en lot", ['error' => $e->getMessage()]);
                $errors[] = ['type' => 'bulk_update', 'error' => $e->getMessage()];
            }
        }

        Log::info("💾 INSERTION OPTIMISÉE TERMINÉE", [
            'device_id' => $device->id,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => count($errors)
        ]);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => $inserted + $updated + $skipped
        ];
    }

    /**
     * Calculer les heures travaillées entre arrivée et départ
     */
    private function calculateWorkingHours(?string $heureArrivee, ?string $heureDepart): ?float
    {
        if (!$heureArrivee || !$heureDepart) {
            return null;
        }

        try {
            $arrivee = \Carbon\Carbon::createFromFormat('H:i:s', $heureArrivee);
            $depart = \Carbon\Carbon::createFromFormat('H:i:s', $heureDepart);
            
            // Si le départ est avant l'arrivée, on assume que c'est le jour suivant
            if ($depart->lt($arrivee)) {
                $depart->addDay();
            }
            
            $diffMinutes = $depart->diffInMinutes($arrivee);
            return round($diffMinutes / 60, 2);
            
        } catch (\Exception $e) {
            Log::warning("Erreur calcul heures travaillées", [
                'heure_arrivee' => $heureArrivee,
                'heure_depart' => $heureDepart,
                'error' => $e->getMessage()
            ]);
            return null;
        }
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
        $query = BiometricDevice::query()
            ->where('active', true)
            ->whereNotNull('device_id') // S'assurer que l'ID de l'appareil est configuré
            ->where(function ($query) {
                $query->where('connection_type', 'ip')
                    ->orWhere('connection_type', 'api');
            });

        // Filtrer par marque si spécifié
        if (!empty($options['brand'])) {
            $query->where('brand', $options['brand']);
        }

        // Récupérer les appareils
        $devices = $query->get();

        // Vérifier la connexion de chaque appareil
        return $devices->filter(function ($device) {
            try {
                $driver = $this->getDriver($device);
                if (!$driver) {
                    Log::warning("Driver non disponible pour l'appareil {$device->name}");
                    return false;
                }

                // Tester la connexion avec vérification de l'ID
                $connectionResult = $driver->testConnection($device);
                $isConnected = $connectionResult['success'] ?? false;
                
                if ($isConnected) {
                    $device->update([
                        'connection_status' => 'connected',
                        'last_connection_test_at' => now()
                    ]);
                    return true;
                } else {
                    $device->update([
                        'connection_status' => 'disconnected',
                        'last_connection_test_at' => now(),
                        'last_error' => 'Échec de la connexion ou ID non correspondant'
                    ]);
                    return false;
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors du test de connexion de l'appareil {$device->name}", [
                    'device_id' => $device->id,
                    'error' => $e->getMessage()
                ]);
                
                $device->update([
                    'connection_status' => 'error',
                    'last_connection_test_at' => now(),
                    'last_error' => $e->getMessage()
                ]);
                return false;
            }
        });
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

    /**
     * OPTIMISÉE : Vérification rapide des mises à jour nécessaires
     */
    private function needsPresenceUpdateOptimized(\App\Models\Presence $existingPresence, array $newRecord, ?float $newHeuresTravaillees): bool
    {
        // Vérifications rapides sans logs verbeux
        return $existingPresence->heure_arrivee !== $newRecord['heure_arrivee'] ||
               $existingPresence->heure_depart !== $newRecord['heure_depart'] ||
               $existingPresence->source_pointage !== 'api-facial' ||
               ($existingPresence->heures_travaillees !== null && $newHeuresTravaillees !== null && 
                abs($existingPresence->heures_travaillees - $newHeuresTravaillees) > 0.1);
    }
} 