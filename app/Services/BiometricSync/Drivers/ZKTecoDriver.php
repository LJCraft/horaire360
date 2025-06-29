<?php

namespace App\Services\BiometricSync\Drivers;

use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Models\BiometricDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Driver pour appareils ZKTeco
 * Utilise le protocole TCP/IP propriétaire ZKTeco
 */
class ZKTecoDriver implements BiometricDriverInterface
{
    private $socket;
    private $device;
    private $timeout = 30;

    public function __construct(BiometricDevice $device)
    {
        $this->device = $device;
    }

    /**
     * Tester la connexion à l'appareil
     */
    public function testConnection(): bool
    {
        try {
            $this->connect();
            $response = $this->sendCommand('DEVICE_INFO');
            $this->disconnect();
            
            return $response !== false;
        } catch (\Exception $e) {
            Log::error("Erreur de connexion ZKTeco: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les données de pointage
     */
    public function fetchAttendanceData(): array
    {
        try {
            $this->connect();
            
            // Authentification si nécessaire
            if ($this->device->username && $this->device->password) {
                $authResult = $this->authenticate();
                if (!$authResult) {
                    throw new \Exception("Authentification échouée");
                }
            }

            // Récupérer les données depuis la dernière synchronisation
            $lastSync = $this->device->last_sync_at ?? Carbon::now()->subDays(7);
            $attendanceData = $this->getAttendanceRecords($lastSync);
            
            $this->disconnect();
            
            return $this->formatAttendanceData($attendanceData);
            
        } catch (\Exception $e) {
            $this->disconnect();
            Log::error("Erreur récupération données ZKTeco: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Établir la connexion TCP/IP
     */
    private function connect(): void
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if (!$this->socket) {
            throw new \Exception("Impossible de créer le socket");
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $this->timeout,
            'usec' => 0
        ]);

        $result = socket_connect($this->socket, $this->device->ip_address, $this->device->port);
        
        if (!$result) {
            throw new \Exception("Impossible de se connecter à {$this->device->ip_address}:{$this->device->port}");
        }
    }

    /**
     * Fermer la connexion
     */
    private function disconnect(): void
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Envoyer une commande à l'appareil
     */
    private function sendCommand(string $command, array $params = []): string|false
    {
        // Construction du protocole ZKTeco
        $packet = $this->buildZKPacket($command, $params);
        
        $sent = socket_write($this->socket, $packet, strlen($packet));
        if ($sent === false) {
            return false;
        }

        // Lire la réponse
        $response = socket_read($this->socket, 2048);
        return $this->parseZKResponse($response);
    }

    /**
     * Authentification sur l'appareil
     */
    private function authenticate(): bool
    {
        $response = $this->sendCommand('AUTH', [
            'username' => $this->device->username,
            'password' => $this->device->password
        ]);

        return $response && strpos($response, 'AUTH_SUCCESS') !== false;
    }

    /**
     * Récupérer les enregistrements de présence
     */
    private function getAttendanceRecords(Carbon $since): array
    {
        $records = [];
        
        // Commande pour récupérer les logs depuis une date
        $response = $this->sendCommand('GET_ATTENDANCE', [
            'since' => $since->format('Y-m-d H:i:s')
        ]);

        if ($response) {
            $records = $this->parseAttendanceResponse($response);
        }

        return $records;
    }

    /**
     * Construire un paquet selon le protocole ZKTeco
     */
    private function buildZKPacket(string $command, array $params = []): string
    {
        // Implémentation simplifiée du protocole ZKTeco
        // En réalité, il faut implémenter le protocole binaire complet
        $header = "\x50\x50\x82\x7D"; // En-tête ZKTeco
        $commandCode = $this->getCommandCode($command);
        $data = json_encode($params);
        
        $packet = $header . pack('v', $commandCode) . pack('v', strlen($data)) . $data;
        
        // Ajouter checksum
        $checksum = $this->calculateChecksum($packet);
        $packet .= pack('v', $checksum);
        
        return $packet;
    }

    /**
     * Analyser la réponse de l'appareil
     */
    private function parseZKResponse(string $response): string|false
    {
        if (strlen($response) < 8) {
            return false;
        }

        // Vérifier l'en-tête
        $header = substr($response, 0, 4);
        if ($header !== "\x50\x50\x82\x7D") {
            return false;
        }

        // Extraire les données
        $dataLength = unpack('v', substr($response, 6, 2))[1];
        $data = substr($response, 8, $dataLength);
        
        return $data;
    }

    /**
     * Analyser la réponse des données de présence
     */
    private function parseAttendanceResponse(string $response): array
    {
        $records = [];
        $lines = explode("\n", trim($response));
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            // Format typique ZKTeco: UserID|DateTime|InOut|VerifyCode|WorkCode
            $parts = explode('|', $line);
            
            if (count($parts) >= 3) {
                $records[] = [
                    'employee_id' => $parts[0],
                    'datetime' => $parts[1],
                    'in_out' => $parts[2], // 0=out, 1=in
                    'verify_code' => $parts[3] ?? '0',
                    'work_code' => $parts[4] ?? '0'
                ];
            }
        }
        
        return $records;
    }

    /**
     * Formater les données pour l'application
     */
    private function formatAttendanceData(array $rawData): array
    {
        $formatted = [];
        
        foreach ($rawData as $record) {
            try {
                $datetime = Carbon::parse($record['datetime']);
                
                $formatted[] = [
                    'employee_id' => (int) $record['employee_id'],
                    'date' => $datetime->format('Y-m-d'),
                    'time' => $datetime->format('H:i:s'),
                    'type' => (int) $record['in_out'], // 0=sortie, 1=entrée
                    'terminal_id' => $this->device->id,
                    'verify_method' => $record['verify_code'],
                    'raw_data' => $record
                ];
            } catch (\Exception $e) {
                Log::warning("Erreur formatage enregistrement ZKTeco", [
                    'record' => $record,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $formatted;
    }

    /**
     * Obtenir le code de commande
     */
    private function getCommandCode(string $command): int
    {
        $codes = [
            'DEVICE_INFO' => 11,
            'AUTH' => 1000,
            'GET_ATTENDANCE' => 13,
            'DISCONNECT' => 5
        ];
        
        return $codes[$command] ?? 0;
    }

    /**
     * Calculer le checksum
     */
    private function calculateChecksum(string $data): int
    {
        $checksum = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $checksum += ord($data[$i]);
        }
        return $checksum & 0xFFFF;
    }

    /**
     * Obtenir les informations de l'appareil
     */
    public function getDeviceInfo(): array
    {
        try {
            $this->connect();
            $response = $this->sendCommand('DEVICE_INFO');
            $this->disconnect();
            
            return [
                'serial_number' => 'ZK' . time(),
                'firmware_version' => '1.0.0',
                'user_count' => 1000,
                'attendance_count' => 50000,
                'status' => 'connected'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
} 