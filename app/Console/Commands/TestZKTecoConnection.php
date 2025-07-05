<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BiometricDevice;
use App\Services\BiometricSync\Drivers\ZKTecoDriver;
use App\Services\BiometricSynchronizationService;

class TestZKTecoConnection extends Command
{
    protected $signature = 'zkteco:test {device_id?}';
    protected $description = 'Tester la connexion avec un appareil ZKTeco';

    public function handle()
    {
        $deviceId = $this->argument('device_id');
        
        if ($deviceId) {
            $device = BiometricDevice::find($deviceId);
            if (!$device) {
                $this->error("Appareil avec ID {$deviceId} introuvable");
                return 1;
            }
        } else {
            $device = BiometricDevice::where('brand', 'zkteco')->first();
            if (!$device) {
                $this->error("Aucun appareil ZKTeco trouvé");
                return 1;
            }
        }

        $this->info("Test de connexion avec l'appareil: {$device->name}");
        $this->info("IP: {$device->ip_address}:{$device->port}");
        $this->info("Device ID: {$device->device_id}");
        $this->info("Username: " . ($device->username ?: 'N/A'));
        $this->info("Password: " . ($device->password ? 'CONFIGURÉ' : 'NON CONFIGURÉ'));
        
        $this->line('');
        $this->info('Test de connexion en cours...');
        
        try {
            $driver = new ZKTecoDriver($device);
            $result = $driver->testConnection($device);
            
            if ($result['success']) {
                $this->info('✅ CONNEXION RÉUSSIE');
                $this->info("Message: {$result['message']}");
                if (!empty($result['details'])) {
                    $this->info("Détails: " . json_encode($result['details'], JSON_PRETTY_PRINT));
                }
            } else {
                $this->error('❌ CONNEXION ÉCHOUÉE');
                $this->error("Message: {$result['message']}");
            }
        } catch (\Exception $e) {
            $this->error('❌ ERREUR CRITIQUE');
            $this->error("Message: " . $e->getMessage());
        }
        
        $this->line('');
        $this->info('Test du service de synchronisation...');
        
        try {
            $service = new BiometricSynchronizationService();
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('getValidConnectedDevices');
            $method->setAccessible(true);
            
            $validDevices = $method->invoke($service, []);
            
            $this->info("Appareils valides détectés: " . $validDevices->count());
            
            if ($validDevices->contains($device)) {
                $this->info('✅ Appareil détecté comme valide par le service');
            } else {
                $this->warn('⚠️ Appareil NON détecté comme valide par le service');
            }
        } catch (\Exception $e) {
            $this->error('Erreur lors du test du service: ' . $e->getMessage());
        }
        
        return 0;
    }
}
