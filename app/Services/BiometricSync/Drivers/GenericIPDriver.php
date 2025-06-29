<?php

namespace App\Services\BiometricSync\Drivers;

use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Models\BiometricDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Driver générique pour appareils biométriques
 * Compatible avec la plupart des protocoles standards
 */
class GenericIPDriver implements BiometricDriverInterface
{
    private $device;
    private $timeout = 30;

    public function __construct(BiometricDevice $device = null)
    {
        $this->device = $device;
    }

    /**
     * Tester la connexion à l'appareil
     */
    public function testConnection(): bool
    {
        if (!$this->device) {
            return false;
        }

        try {
            // Test de base de connectivité réseau
            if ($this->device->connection_type === 'ip') {
                return $this->testBasicConnection();
            } else {
                return $this->testApiConnection();
            }
        } catch (\Exception $e) {
            Log::error("Erreur de connexion Generic: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les données de pointage
     * Note: Implémentation basique pour appareil générique
     */
    public function fetchAttendanceData(): array
    {
        Log::info("Tentative de récupération des données pour appareil générique", [
            'device_id' => $this->device->id,
            'name' => $this->device->name
        ]);

        // Pour un appareil générique, nous ne pouvons pas récupérer automatiquement
        // les données sans connaître le protocole spécifique.
        // Retourner un tableau vide et logguer l'information
        
        Log::warning("Appareil générique: récupération automatique non supportée", [
            'device_id' => $this->device->id,
            'suggestion' => 'Utiliser l\'importation manuelle de fichiers .dat'
        ]);

        return [];
    }

    /**
     * Test de connexion basique TCP/IP
     */
    private function testBasicConnection(): bool
    {
        $ip = $this->device->ip_address;
        $port = $this->device->port ?: 80;

        Log::info("Test de connexion générique TCP/IP", [
            'ip' => $ip,
            'port' => $port,
            'device' => $this->device->name
        ]);

        // Test de connectivité basique
        $connection = @fsockopen($ip, $port, $errno, $errstr, $this->timeout);
        
        if ($connection) {
            fclose($connection);
            Log::info("Connexion TCP/IP réussie pour appareil générique");
            return true;
        }

        Log::warning("Échec connexion TCP/IP générique", [
            'error_no' => $errno,
            'error_str' => $errstr
        ]);

        return false;
    }

    /**
     * Test de connexion API pour appareil générique
     */
    private function testApiConnection(): bool
    {
        $apiUrl = $this->device->api_url;
        
        Log::info("Test de connexion générique API", [
            'url' => $apiUrl,
            'device' => $this->device->name
        ]);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Horaire360-Generic/1.0');

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $success = ($httpCode >= 200 && $httpCode < 400);
            
            Log::info("Résultat test API générique", [
                'http_code' => $httpCode,
                'success' => $success
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error("Erreur test API générique: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les informations de l'appareil
     */
    public function getDeviceInfo(): array
    {
        return [
            'brand' => 'Générique',
            'model' => $this->device->model ?? 'Inconnu',
            'serial_number' => 'GENERIC-' . $this->device->id,
            'firmware_version' => 'Non disponible',
            'protocol' => 'Standard IP/TCP',
            'features' => [
                'basic_connectivity' => true,
                'auto_sync' => false,
                'manual_import' => true
            ],
            'status' => 'compatible',
            'notes' => 'Appareil générique - Synchronisation manuelle recommandée'
        ];
    }

    /**
     * Vérifier si le driver est disponible
     */
    public function isAvailable(): bool
    {
        // Le driver générique est toujours disponible
        return true;
    }

    /**
     * Obtenir les capacités du driver
     */
    public function getCapabilities(): array
    {
        return [
            'test_connection' => true,
            'auto_sync' => false,
            'manual_import' => true,
            'real_time' => false,
            'user_management' => false,
            'file_formats' => ['.dat', '.csv', '.txt']
        ];
    }

    /**
     * Obtenir des recommandations d'usage
     */
    public function getUsageRecommendations(): array
    {
        return [
            'sync_method' => 'Import manuel de fichiers',
            'file_format' => 'Fichier .dat avec format: ID_Employe Date Heure Type_Pointage Terminal_ID',
            'frequency' => 'Import quotidien ou hebdomadaire selon les besoins',
            'notes' => [
                'Aucune synchronisation automatique disponible',
                'Utiliser l\'import de fichiers .dat dans les rapports biométriques',
                'Vérifier la compatibilité du format avec l\'appareil'
            ]
        ];
    }
} 