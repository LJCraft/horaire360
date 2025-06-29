<?php

namespace App\Services\BiometricSync\Drivers;

use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Models\BiometricDevice;
use App\Models\Employe;
use App\Models\Presence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Driver pour l'application mobile de reconnaissance faciale
 * Synchronise les pointages via API REST
 */
class ApiFacialDriver implements BiometricDriverInterface
{
    private $device;
    private $timeout = 30;

    public function __construct(BiometricDevice $device = null)
    {
        $this->device = $device;
    }

    /**
     * Tester la connexion à l'API
     */
    public function testConnection(): bool
    {
        if (!$this->device || !$this->device->api_url) {
            Log::error("API-FACIAL: Configuration manquante", [
                'device_id' => $this->device->id ?? 'null',
                'api_url' => $this->device->api_url ?? 'null'
            ]);
            return false;
        }

        try {
            Log::info("API-FACIAL: Test de connexion", [
                'device_id' => $this->device->id,
                'api_url' => $this->device->api_url,
                'name' => $this->device->name
            ]);

            $response = $this->makeApiRequest('GET', $this->device->api_url, [], 10);
            
            $success = $response->successful();
            
            Log::info("API-FACIAL: Résultat test connexion", [
                'success' => $success,
                'status' => $response->status(),
                'device_id' => $this->device->id
            ]);

            return $success;

        } catch (\Exception $e) {
            Log::error("API-FACIAL: Erreur test connexion", [
                'error' => $e->getMessage(),
                'device_id' => $this->device->id
            ]);
            return false;
        }
    }

    /**
     * Récupérer les données de pointage depuis l'API mobile
     */
    public function fetchAttendanceData(): array
    {
        if (!$this->device || !$this->device->api_url) {
            throw new \Exception("Configuration API-FACIAL manquante");
        }

        try {
            Log::info("API-FACIAL: Début récupération pointages", [
                'device_id' => $this->device->id,
                'api_url' => $this->device->api_url
            ]);

            // Récupérer les données depuis la dernière synchronisation
            $params = $this->buildApiParams();
            $response = $this->makeApiRequest('GET', $this->device->api_url, $params);

            if (!$response->successful()) {
                throw new \Exception("Erreur API: " . $response->status() . " - " . $response->body());
            }

            $rawData = $this->parseApiResponse($response);
            $formattedData = $this->formatAttendanceData($rawData);

            Log::info("API-FACIAL: Récupération réussie", [
                'device_id' => $this->device->id,
                'raw_count' => count($rawData),
                'formatted_count' => count($formattedData)
            ]);

            return $formattedData;

        } catch (\Exception $e) {
            Log::error("API-FACIAL: Erreur récupération pointages", [
                'device_id' => $this->device->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Construire les paramètres pour l'API
     */
    private function buildApiParams(): array
    {
        $params = [];
        
        // Ajouter la date de dernière synchronisation si disponible
        if ($this->device->last_sync_at) {
            $params['since'] = $this->device->last_sync_at->format('Y-m-d H:i:s');
        } else {
            // Récupérer les 7 derniers jours par défaut
            $params['since'] = Carbon::now()->subDays(7)->format('Y-m-d H:i:s');
        }

        // Ajouter des paramètres supplémentaires si nécessaire
        $params['format'] = $this->device->password ?? 'json'; // Format stocké dans password
        $params['limit'] = 1000; // Limite de sécurité

        return $params;
    }

    /**
     * Effectuer une requête API
     */
    private function makeApiRequest(string $method, string $url, array $params = [], int $timeout = null): \Illuminate\Http\Client\Response
    {
        $timeout = $timeout ?? $this->timeout;
        
        $http = Http::timeout($timeout);
        
        // Ajouter l'authentification si un token est configuré
        if ($this->device->username) { // Token stocké dans username
            $http = $http->withHeaders([
                'Authorization' => 'Bearer ' . $this->device->username,
                'Accept' => 'application/json',
                'User-Agent' => 'Horaire360-ApiFacial/1.0'
            ]);
        }

        if ($method === 'GET') {
            return $http->get($url, $params);
        } else {
            return $http->post($url, $params);
        }
    }

    /**
     * Analyser la réponse de l'API
     */
    private function parseApiResponse(\Illuminate\Http\Client\Response $response): array
    {
        $format = $this->device->password ?? 'json';
        
        if ($format === 'xml') {
            // Parser XML si nécessaire
            $xml = simplexml_load_string($response->body());
            return json_decode(json_encode($xml), true);
        }
        
        // Parser JSON par défaut
        $data = $response->json();
        
        // Gérer différents formats de réponse
        if (isset($data['pointages'])) {
            return $data['pointages'];
        } elseif (isset($data['data'])) {
            return $data['data'];
        } elseif (is_array($data)) {
            return $data;
        }

        return [];
    }

    /**
     * Formater les données de pointage pour Horaire360
     */
    private function formatAttendanceData(array $rawData): array
    {
        $formatted = [];
        
        foreach ($rawData as $record) {
            try {
                // Adapter selon le format de votre API mobile
                $formatted[] = [
                    'employee_id' => $this->extractEmployeeId($record),
                    'date' => $this->extractDate($record),
                    'time' => $this->extractTime($record),
                    'type' => $this->extractType($record),
                    'terminal_id' => $this->device->id,
                    'source' => 'api-facial',
                    'confidence' => $record['confidence'] ?? null,
                    'photo_path' => $record['photo_path'] ?? null,
                    'location' => $record['location'] ?? null,
                    'raw_data' => $record
                ];
            } catch (\Exception $e) {
                Log::warning("API-FACIAL: Erreur formatage enregistrement", [
                    'record' => $record,
                    'error' => $e->getMessage(),
                    'device_id' => $this->device->id
                ]);
            }
        }
        
        return $formatted;
    }

    /**
     * Extraire l'ID employé du record
     */
    private function extractEmployeeId(array $record): ?int
    {
        // Essayer différents champs possibles
        $possibleFields = ['employee_id', 'employe_id', 'user_id', 'id_employe'];
        
        foreach ($possibleFields as $field) {
            if (isset($record[$field])) {
                return (int) $record[$field];
            }
        }

        // Si pas d'ID direct, essayer de retrouver via nom/email
        if (isset($record['employee_name']) || isset($record['email'])) {
            $employe = Employe::where('nom', $record['employee_name'] ?? null)
                              ->orWhere('email', $record['email'] ?? null)
                              ->first();
            return $employe ? $employe->id : null;
        }

        return null;
    }

    /**
     * Extraire la date du record
     */
    private function extractDate(array $record): string
    {
        $dateFields = ['date', 'timestamp', 'datetime', 'created_at'];
        
        foreach ($dateFields as $field) {
            if (isset($record[$field])) {
                return Carbon::parse($record[$field])->format('Y-m-d');
            }
        }

        return Carbon::now()->format('Y-m-d');
    }

    /**
     * Extraire l'heure du record
     */
    private function extractTime(array $record): string
    {
        $timeFields = ['time', 'heure', 'timestamp', 'datetime', 'created_at'];
        
        foreach ($timeFields as $field) {
            if (isset($record[$field])) {
                return Carbon::parse($record[$field])->format('H:i:s');
            }
        }

        return Carbon::now()->format('H:i:s');
    }

    /**
     * Extraire le type de pointage
     */
    private function extractType(array $record): int
    {
        $typeFields = ['type', 'action', 'in_out', 'type_pointage'];
        
        foreach ($typeFields as $field) {
            if (isset($record[$field])) {
                $value = strtolower($record[$field]);
                
                // Mapper les valeurs vers les types Horaire360
                if (in_array($value, ['in', 'entree', 'entrée', '1', 1, 'arrival'])) {
                    return 1; // Entrée
                } elseif (in_array($value, ['out', 'sortie', '0', 0, 'departure'])) {
                    return 0; // Sortie
                }
            }
        }

        return 1; // Entrée par défaut
    }

    /**
     * Synchroniser les pointages dans la base de données
     */
    public function syncToDatabase(array $attendanceData): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'duplicates' => 0,
            'details' => []
        ];

        foreach ($attendanceData as $record) {
            try {
                if (!$record['employee_id']) {
                    $results['errors']++;
                    $results['details'][] = "Employé non trouvé pour: " . json_encode($record);
                    continue;
                }

                // Vérifier si le pointage existe déjà
                $existing = Presence::where('employe_id', $record['employee_id'])
                                  ->where('date', $record['date'])
                                  ->where('heure', $record['time'])
                                  ->where('terminal_id', $record['terminal_id'])
                                  ->first();

                if ($existing) {
                    $results['duplicates']++;
                    continue;
                }

                // Créer le nouveau pointage
                Presence::create([
                    'employe_id' => $record['employee_id'],
                    'date' => $record['date'],
                    'heure' => $record['time'],
                    'type_pointage' => $record['type'],
                    'terminal_id' => $record['terminal_id'],
                    'source_pointage' => 'api-facial',
                    'meta_data' => json_encode([
                        'confidence' => $record['confidence'],
                        'photo_path' => $record['photo_path'],
                        'location' => $record['location'],
                        'sync_timestamp' => time(),
                        'device_name' => $this->device->name
                    ])
                ]);

                $results['success']++;

            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = "Erreur pour: " . json_encode($record) . " - " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Obtenir les informations de l'appareil
     */
    public function getDeviceInfo(): array
    {
        return [
            'brand' => 'API-FACIAL',
            'model' => 'Application Mobile',
            'serial_number' => 'API-FACIAL-' . $this->device->id,
            'firmware_version' => '1.0.0',
            'protocol' => 'REST API',
            'features' => [
                'facial_recognition' => true,
                'auto_sync' => true,
                'real_time' => true,
                'photo_capture' => true,
                'geolocation' => true
            ],
            'api_url' => $this->device->api_url,
            'status' => 'connected',
            'capabilities' => $this->getCapabilities()
        ];
    }

    /**
     * Vérifier si le driver est disponible
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Obtenir les capacités du driver
     */
    public function getCapabilities(): array
    {
        return [
            'test_connection' => true,
            'auto_sync' => true,
            'manual_sync' => true,
            'real_time' => true,
            'facial_recognition' => true,
            'photo_capture' => true,
            'geolocation' => true,
            'confidence_score' => true,
            'supported_formats' => ['json', 'xml']
        ];
    }
} 