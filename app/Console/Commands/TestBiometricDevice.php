<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BiometricDevice;
use App\Services\BiometricSync\Drivers\ApiFacialDriver;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\BiometricSync\DriverFactory;

class TestBiometricDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biometric:test-device {id? : ID de l\'appareil à tester} {--all : Tester tous les appareils actifs} {--json : Afficher les données JSON récupérées}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester la connectivité d\'un ou plusieurs appareils biométriques';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🚀 TEST RÉCUPÉRATION ET CONSOLIDATION DONNÉES JSON");
        $this->info("═══════════════════════════════════════════════════════");
        
        $deviceId = $this->argument('device_id');
        $showJson = $this->option('json');
        
        try {
            // Récupérer l'appareil
            $device = BiometricDevice::find($deviceId);
            
            if (!$device) {
                $this->error("❌ Appareil non trouvé avec l'ID: {$deviceId}");
                return 1;
            }

            $this->info("📱 APPAREIL TESTÉ:");
            $this->table(
                ['Propriété', 'Valeur'],
                [
                    ['ID', $device->id],
                    ['Nom', $device->name],
                    ['Marque', $device->brand ?? 'N/A'],
                    ['Type de connexion', $device->connection_type ?? 'N/A'],
                    ['URL API', $device->api_url],
                    ['Statut', $device->connection_status],
                    ['Dernière Sync', $device->last_sync_at?->format('Y-m-d H:i:s') ?? 'Jamais']
                ]
            );

            $this->newLine();
            $this->info("🌐 ÉTAPE 1: Test de connectivité...");
            
            // Tester la connectivité de base
            $startTime = microtime(true);
            
            try {
                $response = Http::timeout(15)
                    ->withOptions(['verify' => false])
                    ->get($device->api_url);
                
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($response->successful()) {
                    $this->info("✅ Connectivité: SUCCÈS ({$responseTime}ms)");
                    $this->info("📊 Status HTTP: {$response->status()}");
                    $this->info("📦 Taille réponse: " . strlen($response->body()) . " octets");
                } else {
                    $this->error("❌ Connectivité: ÉCHEC - HTTP {$response->status()}");
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error("❌ Connectivité: ERREUR - " . $e->getMessage());
                return 1;
            }

            $this->newLine();
            $this->info("🔍 ÉTAPE 2: Test récupération et consolidation des données...");
            
            // Créer le driver ApiFacialDriver directement (car c'est ce qui est configuré)
            $driver = new \App\Services\BiometricSync\Drivers\ApiFacialDriver($device);
            
            $this->info("🔧 Driver créé: " . get_class($driver));
            
            // Récupérer les données avec la nouvelle logique
            $consolidatedData = $driver->fetchAttendanceData();
            
            $this->newLine();
            $this->info("📊 RÉSULTATS DE LA CONSOLIDATION:");
            $this->info("═════════════════════════════════════");
            
            if (empty($consolidatedData)) {
                $this->warn("⚠️ Aucune donnée consolidée récupérée");
                
                // Essayer de récupérer les données brutes pour debugging
                if ($showJson) {
                    $this->info("🔍 Tentative de récupération des données brutes...");
                    $rawResponse = Http::timeout(15)->withOptions(['verify' => false])->get($device->api_url);
                    
                    if ($rawResponse->successful()) {
                        $this->info("📥 DONNÉES BRUTES JSON:");
                        $this->line($rawResponse->body());
                    }
                }
            } else {
                $this->info("✅ Données consolidées: " . count($consolidatedData) . " enregistrement(s)");
                
                // Afficher un résumé des données consolidées
                $this->displayConsolidatedSummary($consolidatedData);
                
                // Afficher les détails si demandé
                if ($showJson) {
                    $this->newLine();
                    $this->info("📄 DÉTAILS DES DONNÉES CONSOLIDÉES:");
                    $this->info("═════════════════════════════════════");
                    
                    foreach ($consolidatedData as $index => $record) {
                        $this->info("📝 Enregistrement " . ($index + 1) . ":");
                        $this->table(
                            ['Champ', 'Valeur'],
                            [
                                ['Employé ID', $record['employee_id'] ?? 'N/A'],
                                ['Date', $record['date'] ?? 'N/A'],
                                ['Heure Arrivée', $record['heure_arrivee'] ?? 'N/A'],
                                ['Heure Départ', $record['heure_depart'] ?? 'N/A'],
                                ['Heures Travaillées', $record['heures_travaillees'] ?? 'N/A'],
                                ['Source', $record['source'] ?? 'N/A'],
                                ['Records Consolidés', $record['records_consolidated'] ?? 'N/A'],
                                ['Logique Utilisée', $record['consolidation_logic'] ?? 'N/A']
                            ]
                        );
                        
                        if ($index < count($consolidatedData) - 1) {
                            $this->newLine();
                        }
                    }
                }
            }

            $this->newLine();
            $this->info("🎯 ÉTAPE 3: Test d'insertion en base...");
            
            if (!empty($consolidatedData)) {
                $syncService = new \App\Services\BiometricSynchronizationService();
                $result = $syncService->syncDevice($device);
                
                $this->info("💾 RÉSULTATS INSERTION:");
                $this->table(
                    ['Métrique', 'Valeur'],
                    [
                        ['Succès', $result['success'] ? '✅ Oui' : '❌ Non'],
                        ['Message', $result['message']],
                        ['Enregistrements traités', $result['processed_records'] ?? 0],
                        ['Nouveaux insérés', $result['inserted_records'] ?? 0],
                        ['Mis à jour', $result['updated_records'] ?? 0],
                        ['Temps d\'exécution', ($result['execution_time'] ?? 0) . 's']
                    ]
                );
            } else {
                $this->warn("⚠️ Aucune donnée à insérer");
            }

            $this->newLine();
            $this->info("🎉 TEST TERMINÉ");
            $this->info("═══════════════════");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERREUR DURANT LE TEST: " . $e->getMessage());
            $this->error("📍 Trace: " . $e->getFile() . ":" . $e->getLine());
            
            if ($this->option('verbose')) {
                $this->error("🔍 Stack trace complet:");
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * Tester tous les appareils actifs
     */
    private function testAllDevices()
    {
        $devices = BiometricDevice::where('active', true)->get();
        
        if ($devices->isEmpty()) {
            $this->error('❌ Aucun appareil actif trouvé.');
            return;
        }

        $this->info("📊 Test de {$devices->count()} appareil(s) actif(s)...");
        $this->newLine();

        $results = [];
        
        foreach ($devices as $device) {
            $result = $this->performDeviceTest($device);
            $results[] = $result;
            
            $this->displayDeviceResult($device, $result);
            $this->newLine();
        }

        $this->displaySummary($results);
    }

    /**
     * Tester un appareil spécifique
     */
    private function testSingleDevice($deviceId)
    {
        $device = BiometricDevice::find($deviceId);
        
        if (!$device) {
            $this->error("❌ Appareil avec l'ID {$deviceId} non trouvé.");
            return;
        }

        $this->info("📡 Test de l'appareil : {$device->name}");
        $this->newLine();

        if ($this->option('json')) {
            // Mode JSON détaillé
            $this->testDeviceJson($device);
        } else {
            // Mode test standard
            $result = $this->performDeviceTest($device);
            $this->displayDeviceResult($device, $result, true);
        }
    }

    /**
     * Permettre à l'utilisateur de sélectionner un appareil
     */
    private function selectDevice()
    {
        $devices = BiometricDevice::all();
        
        if ($devices->isEmpty()) {
            $this->error('❌ Aucun appareil configuré.');
            return null;
        }

        $choices = [];
        foreach ($devices as $device) {
            $status = $device->active ? '🟢' : '🔴';
            $choices[$device->id] = "{$status} {$device->name} ({$device->brand})";
        }

        $deviceId = $this->choice('Choisir un appareil à tester:', $choices);
        
        return array_search($deviceId, $choices);
    }

    /**
     * Effectuer le test de connectivité
     */
    private function performDeviceTest(BiometricDevice $device)
    {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'response_time' => 0,
            'error' => null,
            'data_count' => 0,
            'url_tested' => $device->api_url,
            'details' => []
        ];

        try {
            if ($device->connection_type === 'api') {
                $result = $this->testApiDevice($device);
            } elseif ($device->connection_type === 'ip') {
                $result = $this->testIpDevice($device);
            } else {
                $result['error'] = "Type de connexion non supporté : {$device->connection_type}";
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        $result['response_time'] = round((microtime(true) - $startTime) * 1000, 2);

        // Mettre à jour le statut de l'appareil
        $device->update([
            'connection_status' => $result['success'] ? 'connected' : 'disconnected',
            'last_connection_test_at' => Carbon::now(),
            'last_error' => $result['success'] ? null : $result['error']
        ]);

        return $result;
    }

    /**
     * Tester un appareil API
     */
    private function testApiDevice(BiometricDevice $device)
    {
        $result = [
            'success' => false,
            'error' => null,
            'data_count' => 0,
            'url_tested' => $device->api_url,
            'details' => []
        ];

        if (!$device->api_url) {
            $result['error'] = 'URL de l\'API non configurée';
            return $result;
        }

        if ($device->brand === 'api-facial') {
            // Utiliser le driver ApiFacialDriver
            $driver = new ApiFacialDriver($device);
            
            $testResult = $driver->testConnection($device);
            if ($testResult['success']) {
                $result['success'] = true;
                $result['details'][] = 'Connexion API Facial réussie';
                
                // Tenter de récupérer les données
                try {
                    $data = $driver->fetchAttendanceData();
                    $result['data_count'] = count($data);
                    $result['details'][] = "Données récupérées : {$result['data_count']} pointages";
                } catch (\Exception $e) {
                    $result['details'][] = "Erreur récupération données : " . $e->getMessage();
                }
            } else {
                $result['error'] = 'Échec de la connexion API Facial: ' . $testResult['message'];
            }
        } else {
            // Test HTTP générique
            $response = Http::timeout(10)->get($device->api_url);
            
            if ($response->successful()) {
                $result['success'] = true;
                $result['details'][] = "HTTP {$response->status()} OK";
                
                $data = $response->json();
                if (is_array($data)) {
                    $result['data_count'] = count($data);
                    $result['details'][] = "Données JSON : {$result['data_count']} éléments";
                }
            } else {
                $result['error'] = "HTTP {$response->status()}: {$response->body()}";
            }
        }

        return $result;
    }

    /**
     * Tester un appareil IP
     */
    private function testIpDevice(BiometricDevice $device)
    {
        $result = [
            'success' => false,
            'error' => null,
            'data_count' => 0,
            'url_tested' => "{$device->ip_address}:{$device->port}",
            'details' => []
        ];

        if (!$device->ip_address) {
            $result['error'] = 'Adresse IP non configurée';
            return $result;
        }

        $port = $device->port ?: 80;
        
        // Test de connectivité TCP
        $connection = @fsockopen($device->ip_address, $port, $errno, $errstr, 5);
        
        if ($connection) {
            fclose($connection);
            $result['success'] = true;
            $result['details'][] = "Connexion TCP réussie sur {$device->ip_address}:{$port}";
        } else {
            $result['error'] = "Connexion TCP échouée : {$errstr} (code: {$errno})";
        }

        return $result;
    }

    /**
     * Afficher le résultat pour un appareil
     */
    private function displayDeviceResult(BiometricDevice $device, array $result, bool $detailed = false)
    {
        $status = $result['success'] ? '✅' : '❌';
        $statusText = $result['success'] ? 'CONNECTÉ' : 'ÉCHEC';
        
        $this->line("<fg=white;bg=" . ($result['success'] ? 'green' : 'red') . "> {$status} {$device->name} - {$statusText} </>");
        
        if ($detailed || !$result['success']) {
            $this->line("   📍 Type: {$device->connection_type} ({$device->brand})");
            $this->line("   🌐 URL/IP: {$result['url_tested']}");
            $this->line("   ⏱️  Temps de réponse: {$result['response_time']}ms");
            
            if ($result['success']) {
                $this->line("   📊 Données: {$result['data_count']} pointages");
                
                if (!empty($result['details'])) {
                    foreach ($result['details'] as $detail) {
                        $this->line("   ℹ️  {$detail}");
                    }
                }
            } else {
                $this->line("   ❗ Erreur: {$result['error']}");
            }
            
            $this->line("   🔄 Statut mis à jour: " . ($result['success'] ? 'connected' : 'disconnected'));
        }
    }

    /**
     * Afficher le résumé des tests
     */
    private function displaySummary(array $results)
    {
        $total = count($results);
        $success = count(array_filter($results, fn($r) => $r['success']));
        $failed = $total - $success;

        $this->info('📈 === RÉSUMÉ DES TESTS ===');
        $this->line("✅ Appareils connectés: {$success}/{$total}");
        
        if ($failed > 0) {
            $this->line("❌ Appareils en échec: {$failed}/{$total}");
        }

        $avgResponseTime = round(array_sum(array_column($results, 'response_time')) / $total, 2);
        $this->line("⏱️  Temps de réponse moyen: {$avgResponseTime}ms");

        $totalData = array_sum(array_column($results, 'data_count'));
        $this->line("📊 Total pointages disponibles: {$totalData}");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('💡 Conseil: Utilisez --all pour voir les détails de tous les appareils');
        }
    }

    /**
     * Tester et afficher les données JSON d'un appareil spécifique
     */
    protected function testDeviceJson($device)
    {
        $this->info("🔍 TEST JSON DÉTAILLÉ - Appareil: {$device->name}");
        $this->info("📡 URL API: {$device->api_url}");
        $this->info("🌍 Fuseau horaire: " . config('app.timezone'));
        $this->line("─────────────────────────────────────────────────");
        
        $result = [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'success' => false,
            'response_time' => 0,
            'json_data' => null,
            'parsed_data' => null,
            'errors' => []
        ];

        try {
            if ($device->brand === 'api-facial') {
                $this->info("🔗 Utilisation du driver ApiFacialDriver...");
                
                $driver = new \App\Services\BiometricSync\Drivers\ApiFacialDriver($device);
                
                $this->info("📡 Test de connexion...");
                $startTime = microtime(true);
                
                // Test de connexion d'abord
                $connectionResult = $driver->testConnection($device);
                
                if ($connectionResult['success']) {
                    $this->info("✅ Connexion réussie");
                    
                    $this->info("📥 Récupération des données JSON...");
                    
                    // Récupérer les données brutes
                    $jsonData = $driver->fetchAttendanceData();
                    
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    $this->info("⏱️  Temps de réponse: {$responseTime}ms");
                    $this->info("📊 Données récupérées: " . count($jsonData) . " enregistrements");
                    
                    if (!empty($jsonData)) {
                        $this->info("🎯 Aperçu des données (3 premiers):");
                        
                        $preview = array_slice($jsonData, 0, 3);
                        foreach ($preview as $index => $record) {
                            $this->line("   📝 Enregistrement " . ($index + 1) . ":");
                            
                            // Afficher les champs principaux
                            if (isset($record['employee_id'])) {
                                $this->line("      👤 ID Employé: " . $record['employee_id']);
                            }
                            if (isset($record['date'])) {
                                $this->line("      📅 Date: " . $record['date']);
                            }
                            if (isset($record['time'])) {
                                $localTime = \Carbon\Carbon::parse($record['time'])
                                    ->setTimezone(config('app.timezone'))
                                    ->format('H:i:s');
                                $this->line("      ⏰ Heure: " . $localTime . " (heure locale)");
                            }
                            if (isset($record['type'])) {
                                $typeText = $record['type'] == 1 ? 'Entrée' : 'Sortie';
                                $this->line("      🚪 Type: " . $typeText);
                            }
                            $this->line("");
                        }
                        
                        if (count($jsonData) > 3) {
                            $remaining = count($jsonData) - 3;
                            $this->line("   ... et {$remaining} autre(s) enregistrement(s)");
                        }
                    } else {
                        $this->warn("⚠️  Aucune donnée de pointage trouvée dans la réponse JSON");
                    }
                    
                    $result['success'] = true;
                    $result['response_time'] = $responseTime;
                    $result['json_data'] = $jsonData;
                    
                } else {
                    $this->error("❌ Échec de la connexion: " . implode(', ', $connectionResult['errors'] ?? ['Erreur inconnue']));
                    $result['errors'] = $connectionResult['errors'] ?? [];
                }
                
            } else {
                $this->warn("⚠️  Type d'appareil non pris en charge pour le test JSON: {$device->brand}");
                $result['errors'][] = "Type d'appareil non pris en charge";
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du test: " . $e->getMessage());
            $result['errors'][] = $e->getMessage();
        }
        
        // Mettre à jour le statut de l'appareil
        $status = $result['success'] ? 'connected' : 'disconnected';
        $device->update([
            'connection_status' => $status,
            'last_connection_test_at' => now()
        ]);
        
        $this->info("🔄 Statut mis à jour: {$status}");
        
        return $result;
    }

    /**
     * Afficher un résumé des données consolidées
     */
    private function displayConsolidatedSummary(array $consolidatedData): void
    {
        $employeeIds = array_column($consolidatedData, 'employee_id');
        $uniqueEmployees = array_unique($employeeIds);
        $dates = array_column($consolidatedData, 'date');
        $uniqueDates = array_unique($dates);
        
        $withDeparture = count(array_filter($consolidatedData, fn($r) => !empty($r['heure_depart'])));
        $arrivalOnly = count($consolidatedData) - $withDeparture;
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total enregistrements', count($consolidatedData)],
                ['Employés uniques', count($uniqueEmployees)],
                ['Dates uniques', count($uniqueDates)],
                ['Avec départ', $withDeparture],
                ['Arrivée seulement', $arrivalOnly],
                ['Plage de dates', min($dates) . ' → ' . max($dates)]
            ]
        );
        
        // Top 5 des employés avec le plus de pointages
        $employeeCounts = array_count_values($employeeIds);
        arsort($employeeCounts);
        $topEmployees = array_slice($employeeCounts, 0, 5, true);
        
        if (!empty($topEmployees)) {
            $this->newLine();
            $this->info("👥 TOP EMPLOYÉS (par nombre de pointages):");
            
            $tableData = [];
            foreach ($topEmployees as $empId => $count) {
                $tableData[] = ["Employé {$empId}", $count];
            }
            
            $this->table(['Employé', 'Pointages'], $tableData);
        }
    }
} 