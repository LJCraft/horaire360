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
    public function testConnection(BiometricDevice $device): array
    {
        if (!$this->device) {
            $this->device = $device;
        }
        
        if (!$this->device || !$this->device->api_url) {
            Log::error("API-FACIAL: Configuration manquante", [
                'device_id' => $this->device->id ?? 'null',
                'api_url' => $this->device->api_url ?? 'null'
            ]);
            
            return [
                'success' => false,
                'message' => 'Configuration API manquante (URL)',
                'details' => []
            ];
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

            return [
                'success' => $success,
                'message' => $success ? 'Connexion API réussie' : "Erreur HTTP {$response->status()}",
                'details' => [
                    'status_code' => $response->status(),
                    'response_time' => $response->transferStats?->getTransferTime() ?? 0,
                    'url_tested' => $this->device->api_url
                ]
            ];

        } catch (\Exception $e) {
            Log::error("API-FACIAL: Erreur test connexion", [
                'error' => $e->getMessage(),
                'device_id' => $this->device->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur de connexion: ' . $e->getMessage(),
                'details' => [
                    'exception' => $e->getMessage(),
                    'url_tested' => $this->device->api_url
                ]
            ];
        }
    }

    /**
     * Tester la connexion à l'API (méthode legacy pour compatibilité)
     */
    public function testConnectionLegacy(): bool
    {
        $result = $this->testConnection($this->device);
        return $result['success'];
    }

    /**
     * Récupérer les données de pointage depuis l'API mobile avec debugging avancé
     * MODIFICATION: Récupère TOUS les pointages disponibles sur l'appareil
     */
    public function fetchAttendanceData(): array
    {
        if (!$this->device || !$this->device->api_url) {
            throw new \Exception("Configuration API-FACIAL manquante");
        }

        try {
            Log::info("API-FACIAL: 🚀 DÉBUT RÉCUPÉRATION COMPLÈTE DE TOUS LES POINTAGES", [
                'device_id' => $this->device->id,
                'device_name' => $this->device->name,
                'api_url' => $this->device->api_url,
                'timezone' => config('app.timezone'),
                'sync_type' => 'COMPLETE_SYNC',
                'local_time' => now()->format('Y-m-d H:i:s')
            ]);

            // Récupérer TOUS les pointages disponibles (pas de filtre de date)
            $allPointages = $this->fetchAllAvailableAttendance();
            
            Log::info("API-FACIAL: 📊 POINTAGES BRUTS RÉCUPÉRÉS", [
                'device_id' => $this->device->id,
                'total_raw_records' => count($allPointages),
                'date_range' => $this->getDateRangeFromRecords($allPointages)
            ]);

            if (empty($allPointages)) {
                Log::warning("API-FACIAL: ⚠️ AUCUN POINTAGE DISPONIBLE", [
                    'device_id' => $this->device->name,
                'api_url' => $this->device->api_url
            ]);
                return [];
            }

            // Traitement intelligent des pointages avec consolidation COMPLÈTE
            $processedData = $this->processAndConsolidateAttendance($allPointages);

            Log::info("API-FACIAL: ✅ SYNCHRONISATION COMPLÈTE TERMINÉE", [
                'device_id' => $this->device->id,
                'device_name' => $this->device->name,
                'raw_records' => count($allPointages),
                'processed_records' => count($processedData),
                'unique_employees' => count(array_unique(array_column($processedData, 'employee_id'))),
                'date_range_processed' => $this->getDateRangeFromProcessed($processedData),
                'timezone' => config('app.timezone'),
                'sync_time' => now()->format('Y-m-d H:i:s')
            ]);

            // Mettre à jour la dernière synchronisation
            $this->device->update([
                'last_sync_at' => now(),
                'connection_status' => 'connected',
                'total_records_synced' => count($allPointages)
            ]);

            return $processedData;

        } catch (\Exception $e) {
            Log::error("API-FACIAL: ❌ ERREUR SYNCHRONISATION COMPLÈTE", [
                'device_id' => $this->device->id,
                'device_name' => $this->device->name,
                'api_url' => $this->device->api_url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->device->update(['connection_status' => 'error']);
            throw $e;
        }
    }

    /**
     * 🔄 OPTIMISÉE : Récupérer TOUS les pointages disponibles sur l'appareil - VERSION RAPIDE
     */
    private function fetchAllAvailableAttendance(): array
    {
        $allRecords = [];
        
        Log::info("API-FACIAL: 🚀 DÉBUT RÉCUPÉRATION OPTIMISÉE", [
            'device_id' => $this->device->id
        ]);
        
        // Stratégie 1 PRIORITAIRE : Récupération directe rapide
        try {
            $records = $this->fetchDirectAllRecordsOptimized();
            if (!empty($records)) {
                Log::info("API-FACIAL: ✅ RÉCUPÉRATION DIRECTE RÉUSSIE - ARRÊT", [
                    'device_id' => $this->device->id,
                    'records_found' => count($records)
                ]);
                return $this->removeDuplicateRecordsOptimized($records);
            }
        } catch (\Exception $e) {
            Log::warning("API-FACIAL: Stratégie directe échouée", ['error' => $e->getMessage()]);
        }

        // Stratégie 2 : Récupération paginée limitée (max 10 pages)
        try {
            $records = $this->fetchPaginatedRecordsOptimized();
            if (!empty($records)) {
                Log::info("API-FACIAL: ✅ RÉCUPÉRATION PAGINÉE RÉUSSIE - ARRÊT", [
                    'device_id' => $this->device->id,
                    'records_found' => count($records)
                ]);
                return $this->removeDuplicateRecordsOptimized($records);
            }
        } catch (\Exception $e) {
            Log::warning("API-FACIAL: Stratégie paginée échouée", ['error' => $e->getMessage()]);
        }

        // Stratégie 3 RÉDUITE : Récupération par dates (derniers 30 jours seulement)
        try {
            $records = $this->fetchByDateRangeOptimized();
            if (!empty($records)) {
                Log::info("API-FACIAL: ✅ RÉCUPÉRATION PAR DATES RÉUSSIE", [
                    'device_id' => $this->device->id,
                    'records_found' => count($records)
                ]);
                return $this->removeDuplicateRecordsOptimized($records);
            }
        } catch (\Exception $e) {
            Log::warning("API-FACIAL: Stratégie par dates échouée", ['error' => $e->getMessage()]);
        }

        Log::warning("API-FACIAL: ⚠️ AUCUNE STRATÉGIE RÉUSSIE", [
            'device_id' => $this->device->id
        ]);

        return [];
    }

        /**
     * OPTIMISÉE : Récupération directe avec URLs prioritaires
     */
    private function fetchDirectAllRecordsOptimized(): array
    {
        // URLs optimisées par ordre de priorité
        $urls = [
            $this->device->api_url, // URL de base d'abord
            $this->device->api_url . '?format=json&limit=5000', // Limite raisonnable
            $this->device->api_url . '?all=true&format=json'
        ];

        foreach ($urls as $url) {
            try {
                $response = $this->makeOptimizedApiRequest($url);
                
                if ($response->successful()) {
                    $data = $this->parseJsonResponseOptimized($response->body());
                    
                    if (!empty($data)) {
                        Log::info("API-FACIAL: ✅ URL RÉUSSIE", [
                            'device_id' => $this->device->id,
                            'url' => parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY),
                            'records' => count($data)
                        ]);
                        return $data;
                    }
                }
            } catch (\Exception $e) {
                continue; // Essayer l'URL suivante
            }
        }

        return [];
    }

    /**
     * OPTIMISÉE : Récupération paginée limitée (max 10 pages)
     */
    private function fetchPaginatedRecordsOptimized(): array
    {
        $allRecords = [];
        $page = 1;
        $limit = 500; // Limite plus petite pour plus de rapidité
        $maxPages = 10; // Limitation stricte

        while ($page <= $maxPages) {
            try {
                $url = $this->device->api_url . "?page={$page}&limit={$limit}&format=json";
                $response = $this->makeOptimizedApiRequest($url);
                
                if (!$response->successful()) {
                    break;
                }

                $pageData = $this->parseJsonResponseOptimized($response->body());
                
                if (empty($pageData)) {
                    break; // Plus de données
                }

                $allRecords = array_merge($allRecords, $pageData);
                $page++;
                
                // Si moins de records que la limite, on a probablement tout
                if (count($pageData) < $limit) {
                    break;
                }
                
            } catch (\Exception $e) {
                break; // Arrêter en cas d'erreur
            }
        }

        return $allRecords;
    }

    /**
     * OPTIMISÉE : Récupération par plage de dates (30 jours seulement)
     */
    private function fetchByDateRangeOptimized(): array
    {
        $allRecords = [];
        
        // Réduire à 30 jours pour la rapidité
        $endDate = now();
        $startDate = now()->subDays(30);
        
        $currentDate = $startDate->copy();
        $maxDays = 30; // Limite stricte
        $dayCount = 0;
        
        while ($currentDate <= $endDate && $dayCount < $maxDays) {
            try {
                $dateStr = $currentDate->format('Y-m-d');
                $url = $this->device->api_url . "?date={$dateStr}&format=json";
                
                $response = $this->makeOptimizedApiRequest($url);
                
                if ($response->successful()) {
                    $dayData = $this->parseJsonResponseOptimized($response->body());
                    
                    if (!empty($dayData)) {
                        $allRecords = array_merge($allRecords, $dayData);
                    }
                }
                
                $currentDate->addDay();
                $dayCount++;
                
            } catch (\Exception $e) {
                $currentDate->addDay();
                $dayCount++;
                continue;
            }
        }

        return $allRecords;
    }

    /**
     * OPTIMISÉE : Requête API avec timeout réduit
     */
    private function makeOptimizedApiRequest(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(10) // Timeout réduit à 10s
            ->withOptions([
                'verify' => false,
                'timeout' => 10,
                'connect_timeout' => 5 // Connexion plus rapide
            ])
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Horaire360-Fast/1.0',
                'Cache-Control' => 'no-cache'
            ])
            ->get($url);
    }

    /**
     * OPTIMISÉE : Parse JSON rapide sans logs verbeux
     */
    private function parseJsonResponseOptimized(string $rawBody): array
    {
        $cleanBody = trim($rawBody);
        $data = json_decode($cleanBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        // Extraire rapidement selon les formats courants
        if (is_array($data)) {
            if (isset($data[0])) {
                return $data;
            }
            
            // Champs communs prioritaires
            foreach (['pointages', 'data', 'records'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $data[$key];
                }
            }
        }

        return [];
    }

    /**
     * OPTIMISÉE : Suppression de doublons rapide
     */
    private function removeDuplicateRecordsOptimized(array $records): array
    {
        if (count($records) <= 1000) {
            // Pour petits datasets, utiliser la méthode normale
            return $this->removeDuplicateRecords($records);
        }
        
        // Pour gros datasets, optimisation
        $unique = [];
        $seenKeys = [];

        foreach ($records as $record) {
            $employeeId = $this->extractEmployeeId($record);
            $datetime = $this->extractDateTime($record, config('app.timezone'));
            
            if ($employeeId && $datetime) {
                $key = $employeeId . '_' . $datetime->format('Ymd_Hi'); // Précision à la minute
                
                if (!isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $unique[] = $record;
                }
            }
        }

        Log::info("API-FACIAL: 🔄 DOUBLONS SUPPRIMÉS RAPIDEMENT", [
            'device_id' => $this->device->id,
            'original' => count($records),
            'unique' => count($unique)
        ]);

        return $unique;
    }

    /**
     * Supprimer les doublons des enregistrements
     */
    private function removeDuplicateRecords(array $records): array
    {
        $unique = [];
        $seen = [];

        foreach ($records as $record) {
            // Créer une clé unique basée sur employé + datetime
            $employeeId = $this->extractEmployeeId($record);
            $datetime = $this->extractDateTime($record, config('app.timezone'));
            
            if ($employeeId && $datetime) {
                $key = $employeeId . '_' . $datetime->format('Y-m-d_H:i:s');
                
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[] = $record;
                }
            }
        }

        Log::info("API-FACIAL: 🔄 SUPPRESSION DOUBLONS", [
            'device_id' => $this->device->id,
            'original_count' => count($records),
            'unique_count' => count($unique),
            'duplicates_removed' => count($records) - count($unique)
        ]);

        return $unique;
    }

    /**
     * Obtenir la plage de dates des enregistrements bruts
     */
    private function getDateRangeFromRecords(array $records): array
    {
        if (empty($records)) {
            return ['start' => null, 'end' => null];
        }

        $dates = [];
        foreach ($records as $record) {
            $datetime = $this->extractDateTime($record, config('app.timezone'));
            if ($datetime) {
                $dates[] = $datetime->format('Y-m-d');
            }
        }

        if (empty($dates)) {
            return ['start' => null, 'end' => null];
        }

        sort($dates);
        return [
            'start' => reset($dates),
            'end' => end($dates),
            'days_span' => count(array_unique($dates))
        ];
    }

    /**
     * Obtenir la plage de dates des enregistrements traités
     */
    private function getDateRangeFromProcessed(array $processed): array
    {
        if (empty($processed)) {
            return ['start' => null, 'end' => null];
        }

        $dates = array_column($processed, 'date');
        sort($dates);
        
        return [
            'start' => reset($dates),
            'end' => end($dates),
            'days_span' => count(array_unique($dates))
        ];
    }

    /**
     * Construire l'URL complète avec tous les paramètres
     */
    private function buildFullApiUrl(): string
    {
        $baseUrl = $this->device->api_url;
        
        // Si l'URL contient déjà des paramètres, les utiliser tels quels
        if (strpos($baseUrl, '?') !== false) {
            Log::info("API-FACIAL: URL avec paramètres existants détectée", [
                'device_id' => $this->device->id,
                'url' => $baseUrl
            ]);
            return $baseUrl;
        }
        
        // Sinon, ajouter des paramètres par défaut
        $params = [
            'format' => 'json',
            'limit' => 1000,
            'order' => 'desc'
        ];
        
        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Faire une requête API directe simplifiée
     */
    private function makeDirectApiRequest(string $url): \Illuminate\Http\Client\Response
    {
        Log::info("API-FACIAL: 🌐 REQUÊTE DIRECTE", [
            'device_id' => $this->device->id,
            'url' => $url
        ]);

        return Http::timeout(30)
            ->withOptions([
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 15
            ])
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Horaire360-Sync/1.0',
                'Cache-Control' => 'no-cache'
            ])
            ->get($url);
    }

    /**
     * Parser la réponse JSON de manière robuste
     */
    private function parseJsonResponse(string $rawBody): array
    {
        // Nettoyer la réponse
        $cleanBody = trim($rawBody);
        
        // Essayer de parser le JSON
        $data = json_decode($cleanBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("API-FACIAL: ERREUR JSON", [
                'device_id' => $this->device->id,
                'json_error' => json_last_error_msg(),
                'raw_sample' => substr($cleanBody, 0, 200)
            ]);
            return [];
        }

        // Extraire les données selon différents formats possibles
        if (is_array($data)) {
            // Si c'est directement un tableau
            if (isset($data[0])) {
                return $data;
            }
            
            // Chercher dans les clés communes
            $possibleKeys = ['pointages', 'data', 'records', 'attendances', 'items'];
            foreach ($possibleKeys as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $data[$key];
                }
            }
        }

        Log::warning("API-FACIAL: Format JSON non reconnu", [
            'device_id' => $this->device->id,
            'data_keys' => is_array($data) ? array_keys($data) : 'Non-array'
        ]);

        return [];
    }

    /**
     * Construire les paramètres pour l'API avec gestion des fuseaux horaires
     */
    private function buildApiParams(): array
    {
        $params = [];
        
        // Configuration du fuseau horaire local
        $timezone = config('app.timezone', 'Africa/Douala');
        
        // Ajouter la date de dernière synchronisation si disponible
        if ($this->device->last_sync_at) {
            // Convertir au fuseau horaire local
            $lastSync = $this->device->last_sync_at->setTimezone($timezone);
            $params['since'] = $lastSync->format('Y-m-d H:i:s');
            
            Log::info("API-FACIAL: Synchronisation incrémentale", [
                'device_id' => $this->device->id,
                'last_sync_utc' => $this->device->last_sync_at->format('Y-m-d H:i:s'),
                'last_sync_local' => $lastSync->format('Y-m-d H:i:s'),
                'timezone' => $timezone
            ]);
        } else {
            // Récupérer les 7 derniers jours par défaut en heure locale
            $since = now()->setTimezone($timezone)->subDays(7);
            $params['since'] = $since->format('Y-m-d H:i:s');
            
            Log::info("API-FACIAL: Première synchronisation", [
                'device_id' => $this->device->id,
                'since_local' => $since->format('Y-m-d H:i:s'),
                'timezone' => $timezone
            ]);
        }

        // Ajouter des paramètres supplémentaires
        $params['format'] = $this->device->password ?? 'json'; // Format stocké dans password
        $params['limit'] = 1000; // Limite de sécurité
        $params['timezone'] = $timezone; // Indiquer le fuseau horaire souhaité à l'API
        
        // Paramètre pour récupérer les données les plus récentes en premier
        $params['order'] = 'desc';
        $params['real_time'] = true;

        return $params;
    }

    /**
     * Effectuer une requête API optimisée pour l'application mobile
     */
    private function makeApiRequest(string $method, string $url, array $params = [], int $timeout = null): \Illuminate\Http\Client\Response
    {
        $timeout = $timeout ?? $this->timeout;
        
        Log::info("API-FACIAL: Préparation requête {$method}", [
            'device_id' => $this->device->id,
            'url' => $url,
            'params_count' => count($params),
            'timeout' => $timeout
        ]);
        
        $http = Http::timeout($timeout)
            ->withOptions([
                'verify' => false, // Désactiver la vérification SSL en développement
                'timeout' => $timeout,
                'connect_timeout' => 15, // Augmenter le timeout de connexion
                'read_timeout' => $timeout,
                'http_errors' => false // Ne pas lever d'exception sur erreurs HTTP
            ])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Horaire360-ApiFacial/1.0',
                'X-Requested-With' => 'XMLHttpRequest',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        
        // Ajouter l'authentification si un token est configuré
        if ($this->device->username) { // Token stocké dans username
            $http = $http->withHeaders([
                'Authorization' => 'Bearer ' . $this->device->username
            ]);
            
            Log::info("API-FACIAL: Token d'authentification ajouté", [
                'device_id' => $this->device->id,
                'token_length' => strlen($this->device->username)
            ]);
        }

        try {
            $startTime = microtime(true);

        if ($method === 'GET') {
                $response = $http->get($url, $params);
        } else {
                $response = $http->post($url, $params);
            }
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info("API-FACIAL: Réponse reçue", [
                'device_id' => $this->device->id,
                'status_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'content_length' => strlen($response->body()),
                'content_type' => $response->header('Content-Type')
            ]);
            
            // Vérifier si la réponse est valide
            if (!$response->successful()) {
                Log::warning("API-FACIAL: Réponse HTTP non réussie", [
                    'device_id' => $this->device->id,
                    'status_code' => $response->status(),
                    'status_reason' => $response->reason(),
                    'response_body' => substr($response->body(), 0, 500)
                ]);
            }
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("API-FACIAL: Erreur lors de la requête", [
                'device_id' => $this->device->id,
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);
            
            throw $e;
        }
    }

    /**
     * Analyser la réponse de l'API mobile avec gestion robuste du JSON
     */
    private function parseApiResponse(\Illuminate\Http\Client\Response $response): array
    {
        $format = $this->device->password ?? 'json';
        
        Log::info("API-FACIAL: Analyse de la réponse API", [
            'device_id' => $this->device->id,
            'status_code' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'body_length' => strlen($response->body()),
            'expected_format' => $format
        ]);
        
        try {
        if ($format === 'xml') {
            // Parser XML si nécessaire
            $xml = simplexml_load_string($response->body());
                $data = json_decode(json_encode($xml), true);
            } else {
                // Parser JSON - méthode principale
                $rawBody = $response->body();
                
                // Log du contenu brut pour debugging
                Log::info("API-FACIAL: Contenu JSON brut", [
                    'device_id' => $this->device->id,
                    'raw_sample' => substr($rawBody, 0, 500), // Premier 500 caractères
                    'full_length' => strlen($rawBody)
                ]);
                
        $data = $response->json();
        
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Erreur JSON: " . json_last_error_msg());
                }
            }
            
            // Extraction intelligente des données de pointage
            $extractedData = $this->extractPointageData($data);
            
            Log::info("API-FACIAL: Données extraites avec succès", [
                'device_id' => $this->device->id,
                'raw_structure' => array_keys($data ?? []),
                'extracted_count' => count($extractedData),
                'sample_data' => array_slice($extractedData, 0, 2)
            ]);
            
            return $extractedData;
            
        } catch (\Exception $e) {
            Log::error("API-FACIAL: Erreur parsing JSON", [
                'device_id' => $this->device->id,
                'error' => $e->getMessage(),
                'raw_body_sample' => substr($response->body(), 0, 200),
                'status_code' => $response->status()
            ]);
            
            // Fallback : essayer de parser manuellement
            return $this->fallbackJsonParsing($response->body());
        }
    }

    /**
     * Extraction intelligente des données de pointage depuis la réponse JSON
     */
    private function extractPointageData(array $data): array
    {
        // Essayer différents formats de structure JSON
        $possibleDataKeys = [
            'pointages',           // Format standard Horaire360
            'data',               // Format générique API
            'attendances',        // Format anglais
            'records',           // Format enregistrements
            'items',             // Format items
            'results',           // Format résultats
            'payload',           // Format avec payload
            'content'            // Format avec contenu
        ];
        
        // 1. Chercher dans les clés connues
        foreach ($possibleDataKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                Log::info("API-FACIAL: Données trouvées dans la clé '{$key}'", [
                    'device_id' => $this->device->id,
                    'count' => count($data[$key])
                ]);
                return $data[$key];
            }
        }
        
        // 2. Si la racine est directement un tableau de pointages
        if (is_array($data) && $this->isPointageArray($data)) {
            Log::info("API-FACIAL: Données trouvées à la racine", [
                'device_id' => $this->device->id,
                'count' => count($data)
            ]);
            return $data;
        }
        
        // 3. Chercher récursivement dans la structure
        foreach ($data as $key => $value) {
            if (is_array($value) && $this->isPointageArray($value)) {
                Log::info("API-FACIAL: Données trouvées dans la clé '{$key}' (récursif)", [
                    'device_id' => $this->device->id,
                    'count' => count($value)
                ]);
                return $value;
            }
        }
        
        Log::warning("API-FACIAL: Aucune donnée de pointage trouvée", [
            'device_id' => $this->device->id,
            'structure_keys' => array_keys($data),
            'data_sample' => array_slice($data, 0, 3, true)
        ]);

        return [];
    }

    /**
     * Vérifier si un tableau contient des données de pointage valides
     */
    private function isPointageArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        
        // Prendre le premier élément pour vérification
        $firstItem = reset($array);
        
        if (!is_array($firstItem)) {
            return false;
        }
        
        // Champs attendus dans un pointage
        $expectedFields = [
            ['employee_id', 'employe_id', 'user_id', 'id_employe'],
            ['date', 'datetime', 'timestamp', 'created_at'],
            ['time', 'heure', 'timestamp', 'datetime']
        ];
        
        $matchedFields = 0;
        foreach ($expectedFields as $fieldGroup) {
            foreach ($fieldGroup as $field) {
                if (isset($firstItem[$field])) {
                    $matchedFields++;
                    break;
                }
            }
        }
        
        // Au moins 2 champs sur 3 doivent être présents
        return $matchedFields >= 2;
    }

    /**
     * Parsing JSON de secours en cas d'échec
     */
    private function fallbackJsonParsing(string $rawBody): array
    {
        try {
            // Nettoyer le JSON si nécessaire
            $cleanedBody = trim($rawBody);
            
            // Enlever les caractères invisibles
            $cleanedBody = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cleanedBody);
            
            $data = json_decode($cleanedBody, true);
            
            if ($data !== null) {
                Log::info("API-FACIAL: Parsing de secours réussi", [
                    'device_id' => $this->device->id,
                    'original_length' => strlen($rawBody),
                    'cleaned_length' => strlen($cleanedBody)
                ]);
                
                return $this->extractPointageData($data);
            }
            
        } catch (\Exception $e) {
            Log::error("API-FACIAL: Échec parsing de secours", [
                'device_id' => $this->device->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return [];
    }

    /**
     * Formater les données de pointage pour Horaire360 avec gestion des fuseaux horaires
     */
    private function formatAttendanceData(array $rawData): array
    {
        $formatted = [];
        $timezone = config('app.timezone', 'Africa/Douala');
        
        Log::info("API-FACIAL: Début formatage données", [
            'device_id' => $this->device->id,
            'raw_count' => count($rawData),
            'timezone' => $timezone
        ]);
        
        foreach ($rawData as $index => $record) {
            try {
                // Conversion des données temps réel
                $employeeId = $this->extractEmployeeId($record);
                $date = $this->extractDate($record, $timezone);
                $time = $this->extractTime($record, $timezone);
                $type = $this->extractType($record);
                
                if (!$employeeId) {
                    Log::warning("API-FACIAL: ID employé manquant", [
                        'record_index' => $index,
                        'record' => $record,
                        'device_id' => $this->device->id
                    ]);
                    continue;
                }

                // Formatage final pour Horaire360
                $formatted[] = [
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'time' => $time,
                    'type' => $type,
                    'terminal_id' => $this->device->id,
                    'source' => 'api-facial',
                    'confidence' => $record['confidence'] ?? 100,
                    'photo_path' => $record['photo_path'] ?? null,
                    'location' => $record['location'] ?? null,
                    'device_name' => $this->device->name,
                    'sync_timestamp' => now()->format('Y-m-d H:i:s'),
                    'timezone' => $timezone,
                    'raw_data' => $record
                ];
                
            } catch (\Exception $e) {
                Log::warning("API-FACIAL: Erreur formatage enregistrement", [
                    'record_index' => $index,
                    'record' => $record,
                    'error' => $e->getMessage(),
                    'device_id' => $this->device->id
                ]);
            }
        }
        
        Log::info("API-FACIAL: Formatage terminé", [
            'device_id' => $this->device->id,
            'input_count' => count($rawData),
            'output_count' => count($formatted),
            'timezone' => $timezone
        ]);
        
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
     * Extraire la date du record avec gestion des fuseaux horaires
     */
    private function extractDate(array $record, string $timezone = 'Africa/Douala'): string
    {
        // Essayer différents champs possibles pour la date
        $possibleFields = ['date', 'datetime', 'timestamp', 'created_at', 'pointage_date'];
        
        foreach ($possibleFields as $field) {
            if (isset($record[$field])) {
                try {
                    // Créer un objet Carbon avec le fuseau horaire correct
                    $date = Carbon::parse($record[$field])->setTimezone($timezone);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::warning("API-FACIAL: Erreur parsing date", [
                        'field' => $field,
                        'value' => $record[$field],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Fallback sur la date actuelle en heure locale
        return now()->setTimezone($timezone)->format('Y-m-d');
    }

    /**
     * Extraire l'heure du record avec gestion des fuseaux horaires
     */
    private function extractTime(array $record, string $timezone = 'Africa/Douala'): string
    {
        // Essayer différents champs possibles pour l'heure
        $possibleFields = ['time', 'datetime', 'timestamp', 'created_at', 'heure', 'pointage_time'];
        
        foreach ($possibleFields as $field) {
            if (isset($record[$field])) {
                try {
                    // Créer un objet Carbon avec le fuseau horaire correct
                    $time = Carbon::parse($record[$field])->setTimezone($timezone);
                    return $time->format('H:i:s');
                } catch (\Exception $e) {
                    Log::warning("API-FACIAL: Erreur parsing time", [
                        'field' => $field,
                        'value' => $record[$field],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Fallback sur l'heure actuelle en heure locale
        return now()->setTimezone($timezone)->format('H:i:s');
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
     * 🎯 LOGIQUE PRINCIPALE : Traitement et consolidation intelligente des pointages
     * 
     * Pour chaque employé et chaque date :
     * - Récupère TOUS les pointages de la journée
     * - Prend le PLUS TÔT pour l'heure d'arrivée (AR)
     * - Prend le PLUS TARD pour l'heure de départ (DP)
     */
    private function processAndConsolidateAttendance(array $rawData): array
    {
        $timezone = config('app.timezone', 'Africa/Douala');
        $consolidated = [];
        
        Log::info("API-FACIAL: 🎯 DÉBUT CONSOLIDATION OPTIMISÉE", [
            'device_id' => $this->device->id,
            'raw_records' => count($rawData),
            'timezone' => $timezone
        ]);

        // Grouper les pointages par employé et par date (optimisé)
        $groupedData = $this->groupAttendanceByEmployeeAndDateOptimized($rawData, $timezone);
        
        Log::info("API-FACIAL: 📊 GROUPEMENT TERMINÉ", [
            'device_id' => $this->device->id,
            'employees_count' => count($groupedData),
            'total_days' => array_sum(array_map('count', $groupedData))
        ]);

        // Traitement optimisé de chaque groupe
        foreach ($groupedData as $employeeId => $dateGroups) {
            foreach ($dateGroups as $date => $records) {
                
                // Consolidation rapide sans logs verbeux
                $consolidatedRecord = $this->consolidateDayAttendanceOptimized($employeeId, $date, $records, $timezone);
                
                if ($consolidatedRecord) {
                    $consolidated[] = $consolidatedRecord;
                }
            }
        }

        Log::info("API-FACIAL: 🎉 CONSOLIDATION OPTIMISÉE TERMINÉE", [
            'device_id' => $this->device->id,
            'input_records' => count($rawData),
            'output_records' => count($consolidated),
            'employees_processed' => count($groupedData)
        ]);

        return $consolidated;
    }



    /**
     * OPTIMISÉE : Grouper les pointages plus rapidement
     */
    private function groupAttendanceByEmployeeAndDateOptimized(array $rawData, string $timezone): array
    {
        $grouped = [];

        foreach ($rawData as $record) {
            $employeeId = $this->extractEmployeeId($record);
            $datetime = $this->extractDateTime($record, $timezone);

            if (!$employeeId || !$datetime) {
                continue; // Skip sans log
            }

            $date = $datetime->format('Y-m-d');
            
            if (!isset($grouped[$employeeId][$date])) {
                $grouped[$employeeId][$date] = [];
            }

            $record['parsed_datetime'] = $datetime;
            $grouped[$employeeId][$date][] = $record;
        }

        return $grouped;
    }

    /**
     * Grouper les pointages par employé et par date (VERSION ORIGINALE)
     */
    private function groupAttendanceByEmployeeAndDate(array $rawData, string $timezone): array
    {
        $grouped = [];

        foreach ($rawData as $record) {
            // Extraire les informations essentielles
            $employeeId = $this->extractEmployeeId($record);
            $datetime = $this->extractDateTime($record, $timezone);

            if (!$employeeId || !$datetime) {
                Log::warning("API-FACIAL: Enregistrement ignoré", [
                    'reason' => 'employee_id ou datetime manquant',
                    'record' => $record
                ]);
                continue;
            }

            $date = $datetime->format('Y-m-d');
            
            if (!isset($grouped[$employeeId])) {
                $grouped[$employeeId] = [];
            }
            
            if (!isset($grouped[$employeeId][$date])) {
                $grouped[$employeeId][$date] = [];
            }

            // Ajouter l'enregistrement avec l'heure parsée
            $record['parsed_datetime'] = $datetime;
            $grouped[$employeeId][$date][] = $record;
        }

        return $grouped;
    }

    /**
     * OPTIMISÉE : Consolider rapidement sans logs verbeux
     */
    private function consolidateDayAttendanceOptimized(int $employeeId, string $date, array $records, string $timezone): ?array
    {
        if (empty($records)) {
            return null;
        }

        // Trier rapidement par timestamp
        usort($records, function($a, $b) {
            return $a['parsed_datetime']->timestamp <=> $b['parsed_datetime']->timestamp;
        });

        $firstRecord = reset($records);
        $lastRecord = end($records);

        $heureArrivee = $firstRecord['parsed_datetime']->format('H:i:s');
        $heureDepart = count($records) > 1 ? $lastRecord['parsed_datetime']->format('H:i:s') : null;

        // Calcul rapide des heures travaillées
        $heuresTravaillees = null;
        if ($heureDepart) {
            $diffMinutes = $lastRecord['parsed_datetime']->diffInMinutes($firstRecord['parsed_datetime']);
            $heuresTravaillees = round($diffMinutes / 60, 2);
        }

        return [
            'employee_id' => $employeeId,
            'date' => $date,
            'heure_arrivee' => $heureArrivee,
            'heure_depart' => $heureDepart,
            'heures_travaillees' => $heuresTravaillees,
            'source' => 'api-facial-consolidated',
            'device_id' => $this->device->id,
            'device_name' => $this->device->name,
            'records_consolidated' => count($records),
            'timezone' => $timezone,
            'sync_timestamp' => now()->format('Y-m-d H:i:s'),
            'consolidation_logic' => 'first_last'
        ];
    }

    /**
     * Consolider tous les pointages d'un employé pour une journée (VERSION ORIGINALE)
     * LOGIQUE : Plus tôt = Arrivée, Plus tard = Départ
     */
    private function consolidateDayAttendance(int $employeeId, string $date, array $records, string $timezone): ?array
    {
        if (empty($records)) {
            return null;
        }

        // Trier les enregistrements par heure croissante
        usort($records, function($a, $b) {
            return $a['parsed_datetime']->timestamp <=> $b['parsed_datetime']->timestamp;
        });

        // LOGIQUE SIMPLE ET RADICALE :
        // - Premier pointage de la journée = ARRIVÉE
        // - Dernier pointage de la journée = DÉPART
        $firstRecord = reset($records);
        $lastRecord = end($records);

        $heureArrivee = $firstRecord['parsed_datetime']->format('H:i:s');
        $heureDepart = count($records) > 1 ? $lastRecord['parsed_datetime']->format('H:i:s') : null;

        // Calculer les heures travaillées si on a arrivée ET départ
        $heuresTravaillees = null;
        if ($heureDepart) {
            $diffMinutes = $lastRecord['parsed_datetime']->diffInMinutes($firstRecord['parsed_datetime']);
            $heuresTravaillees = round($diffMinutes / 60, 2);
        }

        Log::info("API-FACIAL: 🕐 CONSOLIDATION JOURNÉE", [
            'employee_id' => $employeeId,
            'date' => $date,
            'records_count' => count($records),
            'first_time' => $heureArrivee,
            'last_time' => $heureDepart,
            'heures_travaillees' => $heuresTravaillees
        ]);

        return [
            'employee_id' => $employeeId,
            'date' => $date,
            'heure_arrivee' => $heureArrivee,
            'heure_depart' => $heureDepart,
            'heures_travaillees' => $heuresTravaillees,
            'source' => 'api-facial-consolidated',
            'device_id' => $this->device->id,
            'device_name' => $this->device->name,
            'records_consolidated' => count($records),
            'timezone' => $timezone,
            'sync_timestamp' => now()->format('Y-m-d H:i:s'),
            'consolidation_logic' => 'first_last', // Premier = Arrivée, Dernier = Départ
            'raw_records' => $records // Garder trace des données source
        ];
    }

    /**
     * Extraire la date/heure d'un enregistrement
     */
    private function extractDateTime(array $record, string $timezone): ?\Carbon\Carbon
    {
        // Essayer différents champs de date/heure
        $possibleFields = [
            'datetime', 'timestamp', 'created_at', 'date_time', 
            'pointage_datetime', 'time', 'date'
        ];

        foreach ($possibleFields as $field) {
            if (isset($record[$field])) {
                try {
                    $datetime = \Carbon\Carbon::parse($record[$field])->setTimezone($timezone);
                    return $datetime;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Si pas de champ datetime, essayer de combiner date + time
        if (isset($record['date']) && isset($record['time'])) {
            try {
                $dateStr = $record['date'] . ' ' . $record['time'];
                return \Carbon\Carbon::parse($dateStr)->setTimezone($timezone);
            } catch (\Exception $e) {
                // Continue
            }
        }

        Log::warning("API-FACIAL: Impossible d'extraire datetime", [
            'record' => $record,
            'available_fields' => array_keys($record)
        ]);

        return null;
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
    public function getDeviceInfo(BiometricDevice $device): array
    {
        if (!$this->device) {
            $this->device = $device;
        }
        
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
        // Le driver API-FACIAL est toujours disponible car il utilise HTTP
        return extension_loaded('curl') || function_exists('file_get_contents');
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

    /**
     * Synchroniser les données de pointage depuis l'appareil
     *
     * @param BiometricDevice $device
     * @param array $options Options de synchronisation
     * @return array
     */
    public function syncData(BiometricDevice $device, array $options = []): array
    {
        if (!$this->device) {
            $this->device = $device;
        }

        try {
            $attendanceData = $this->fetchAttendanceData();
            
            return [
                'success' => true,
                'data' => $attendanceData,
                'message' => 'Données récupérées avec succès',
                'records_count' => count($attendanceData)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage(),
                'records_count' => 0
            ];
        }
    }

    /**
     * Obtenir les options de configuration par défaut
     *
     * @return array
     */
    public function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
            'retry_count' => 3,
            'validate_ssl' => false,
            'format' => 'json',
            'max_records' => 1000,
            'date_range_days' => 7
        ];
    }

    /**
     * Valider la configuration de l'appareil
     *
     * @param array $config
     * @return array
     */
    public function validateConfig(array $config): array
    {
        $errors = [];
        
        // Vérifier l'URL de l'API
        if (empty($config['api_url'])) {
            $errors[] = 'URL de l\'API manquante';
        } elseif (!filter_var($config['api_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'URL de l\'API invalide';
        }
        
        // Vérifier le timeout
        if (isset($config['timeout']) && (!is_numeric($config['timeout']) || $config['timeout'] < 1)) {
            $errors[] = 'Timeout invalide (doit être un nombre positif)';
        }
        
        // Vérifier le format
        if (isset($config['format']) && !in_array($config['format'], ['json', 'xml'])) {
            $errors[] = 'Format invalide (json ou xml uniquement)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
} 