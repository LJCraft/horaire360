<?php

namespace App\Services\BiometricSync\Drivers;

use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Models\BiometricDevice;
use Exception;

class ZKTecoDriver implements BiometricDriverInterface
{
    // Constantes pour les commandes ZKTeco
    const CMD_CONNECT = 1000;
    const CMD_AUTH = 1102;
    const CMD_USERINFO = 1;
    const CMD_ATTLOG = 13;
    const CMD_CLEAR_DATA = 14;
    const CMD_CLEAR_ADMIN = 15;
    const CMD_DISABLE_DEVICE = 1001;
    const CMD_ENABLE_DEVICE = 1002;
    const CMD_RESTART = 1004;
    const CMD_POWEROFF = 1005;
    const CMD_ACK_OK = 2000;
    const CMD_ACK_ERROR = 2001;
    
    private $socket;
    private $device;
    private $sessionId = 0;
    private $replyId = 0;
    
    public function __construct(BiometricDevice $device)
    {
        $this->device = $device;
    }
    
    public function testConnection(BiometricDevice $device = null): array
    {
        // Utiliser le device passé en paramètre ou celui de la classe
        $deviceToUse = $device ?? $this->device;
        
        try {
            // Test de connexion simple
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$this->socket) {
                throw new Exception("Impossible de créer le socket");
            }
            
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 3, 'usec' => 0));
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
            
            $result = socket_connect($this->socket, $deviceToUse->ip_address, $deviceToUse->port);
            if (!$result) {
                throw new Exception("Impossible de se connecter à {$deviceToUse->ip_address}:{$deviceToUse->port}");
            }
            
            socket_close($this->socket);
            
            return [
                'success' => true,
                'message' => 'Connexion réussie',
                'response_time' => 0.1
            ];
            
        } catch (Exception $e) {
            if ($this->socket) {
                socket_close($this->socket);
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'response_time' => null
            ];
        }
    }
    
    public function syncData(BiometricDevice $device = null, array $options = []): array
    {
        // Utiliser le device passé en paramètre ou celui de la classe
        $deviceToUse = $device ?? $this->device;
        
        try {
            // Connexion
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$this->socket) {
                throw new Exception("Impossible de créer le socket");
            }
            
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 10, 'usec' => 0));
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 10, 'usec' => 0));
            
            $result = socket_connect($this->socket, $deviceToUse->ip_address, $deviceToUse->port);
            if (!$result) {
                throw new Exception("Impossible de se connecter à {$deviceToUse->ip_address}:{$deviceToUse->port}");
            }
            
            // Tentative de récupération des données
            $attendanceRecords = $this->getAttendanceRecords();
            
            socket_close($this->socket);
            
            return [
                'success' => true,
                'records' => $attendanceRecords,
                'message' => 'Synchronisation réussie'
            ];
            
        } catch (Exception $e) {
            if ($this->socket) {
                socket_close($this->socket);
            }
            
            return [
                'success' => false,
                'records' => [],
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupérer les données de pointage depuis l'appareil (méthode attendue par le service)
     */
    public function fetchAttendanceData(): array
    {
        $result = $this->syncData();
        return $result['records'] ?? [];
    }
    
    private function getAttendanceRecords(): array
    {
        $records = [];
        
        try {
            // Commande pour récupérer les enregistrements de présence
            $command = $this->createCommand(self::CMD_ATTLOG);
            $sent = socket_send($this->socket, $command, strlen($command), 0);
            
            if ($sent === false) {
                throw new Exception("Erreur lors de l'envoi de la commande");
            }
            
            // Lire la réponse
            $response = socket_read($this->socket, 8192);
            if ($response === false) {
                throw new Exception("Erreur lors de la lecture de la réponse");
            }
            
            // Vérifier la taille de la réponse avant de la traiter
            if (strlen($response) < 8) {
                // Réponse trop courte, probablement pas de données
                return [];
            }
            
            // Parser la réponse avec vérification de taille
            $records = $this->parseAttendanceData($response);
            
        } catch (Exception $e) {
            // Log l'erreur mais ne pas faire échouer complètement la synchronisation
            error_log("Erreur ZKTeco getAttendanceRecords: " . $e->getMessage());
        }
        
        return $records;
    }
    
    private function parseAttendanceData($data): array
    {
        $records = [];
        
        try {
            // Vérifier la taille minimale des données
            if (strlen($data) < 8) {
                return [];
            }
            
            // Lire l'en-tête de la réponse
            $header = substr($data, 0, 8);
            if (strlen($header) < 8) {
                return [];
            }
            
            // Essayer de décompresser l'en-tête
            $headerData = @unpack('vcommand/vchecksum/vsession_id/vreply_id', $header);
            if (!$headerData) {
                return [];
            }
            
            // Extraire les données utiles (après l'en-tête)
            $payload = substr($data, 8);
            
            // Chaque enregistrement fait 40 bytes dans le protocole ZKTeco
            $recordSize = 40;
            $recordCount = intval(strlen($payload) / $recordSize);
            
            for ($i = 0; $i < $recordCount; $i++) {
                $recordData = substr($payload, $i * $recordSize, $recordSize);
                
                // Vérifier la taille de l'enregistrement
                if (strlen($recordData) < $recordSize) {
                    continue;
                }
                
                // Essayer de parser l'enregistrement
                $record = $this->parseAttendanceRecord($recordData);
                if ($record) {
                    $records[] = $record;
                }
            }
            
        } catch (Exception $e) {
            error_log("Erreur parsing ZKTeco: " . $e->getMessage());
        }
        
        return $records;
    }
    
    private function parseAttendanceRecord($data): ?array
    {
        try {
            // Vérifier la taille des données
            if (strlen($data) < 40) {
                return null;
            }
            
            // Structure simplifiée d'un enregistrement ZKTeco
            $record = @unpack('Luser_id/Ltimestamp/Lverify_type/Lverify_state/Lworkcode/Lreserved', substr($data, 0, 24));
            
            if (!$record) {
                return null;
            }
            
            // Convertir le timestamp
            $datetime = date('Y-m-d H:i:s', $record['timestamp']);
            
            return [
                'employee_id' => $record['user_id'],
                'datetime' => $datetime,
                'verify_type' => $record['verify_type'],
                'verify_state' => $record['verify_state'],
                'work_code' => $record['workcode']
            ];
            
        } catch (Exception $e) {
            error_log("Erreur parsing record ZKTeco: " . $e->getMessage());
            return null;
        }
    }
    
    private function createCommand($command, $data = ''): string
    {
        $checksum = 0;
        $sessionId = $this->sessionId;
        $replyId = $this->replyId++;
        
        // Créer l'en-tête de 8 bytes
        $header = pack('vvvv', $command, $checksum, $sessionId, $replyId);
        
        return $header . $data;
    }
    
    public function getDeviceInfo(BiometricDevice $device = null): array
    {
        // Utiliser le device passé en paramètre ou celui de la classe
        $deviceToUse = $device ?? $this->device;
        
        return [
            'device_id' => $deviceToUse->device_id,
            'name' => $deviceToUse->name,
            'ip_address' => $deviceToUse->ip_address,
            'port' => $deviceToUse->port,
            'brand' => 'ZKTeco',
            'model' => 'Generic'
        ];
    }
    
    public function isAvailable(): bool
    {
        $test = $this->testConnection();
        return $test['success'];
    }
    
    public function getDefaultConfig(): array
    {
        return [
            'port' => 4370,
            'timeout' => 30,
            'max_records' => 1000
        ];
    }
    
    public function validateConfig(array $config): array
    {
        $errors = [];
        
        if (empty($config['ip_address'])) {
            $errors[] = 'Adresse IP requise';
        }
        
        if (empty($config['port']) || !is_numeric($config['port'])) {
            $errors[] = 'Port valide requis';
        }
        
        if (empty($config['device_id'])) {
            $errors[] = 'Device ID requis';
        }
        
        return $errors;
    }
} 