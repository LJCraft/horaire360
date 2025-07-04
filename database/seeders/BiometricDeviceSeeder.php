<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BiometricDevice;
use Carbon\Carbon;

class BiometricDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des appareils biométriques API Facial pour la production
        $devices = [
            [
                'name' => 'API FACIAL - Production Principal',
                'brand' => 'api-facial',
                'model' => 'Mobile App v1.0',
                'connection_type' => 'api',
                'api_url' => 'https://apitface.onrender.com/pointages?nameEntreprise=Pop',
                'username' => null, // Token d'authentification si nécessaire
                'password' => 'json', // Format de réponse
                'sync_interval' => 300, // 5 minutes
                'active' => true,
                'connection_status' => 'connected',
                'last_connection_test_at' => Carbon::now(),
                'device_config' => [
                    'timeout' => 30,
                    'retry_count' => 3,
                    'validate_ssl' => false
                ],
                'mapping_config' => [
                    'employee_id_field' => 'employee_id',
                    'date_field' => 'date',
                    'time_field' => 'time',
                    'type_field' => 'type'
                ]
            ],
            [
                'name' => 'API FACIAL - Pop2',
                'brand' => 'api-facial',
                'model' => 'Mobile App v1.0',
                'connection_type' => 'api',
                'api_url' => 'https://apitface.onrender.com/pointages?nameEntreprise=Pop2',
                'username' => null,
                'password' => 'json',
                'sync_interval' => 300,
                'active' => true,
                'connection_status' => 'connected',
                'last_connection_test_at' => Carbon::now(),
                'device_config' => [
                    'timeout' => 30,
                    'retry_count' => 3,
                    'validate_ssl' => false
                ],
                'mapping_config' => [
                    'employee_id_field' => 'employee_id',
                    'date_field' => 'date',
                    'time_field' => 'time',
                    'type_field' => 'type'
                ]
            ],
            [
                'name' => 'API FACIAL - Test',
                'brand' => 'api-facial',
                'model' => 'Mobile App v1.0',
                'connection_type' => 'api',
                'api_url' => 'https://apitface.onrender.com/pointages?nameEntreprise=Test',
                'username' => null,
                'password' => 'json',
                'sync_interval' => 600, // 10 minutes
                'active' => false, // Désactivé par défaut pour les tests
                'connection_status' => 'disconnected',
                'last_connection_test_at' => null,
                'device_config' => [
                    'timeout' => 30,
                    'retry_count' => 2,
                    'validate_ssl' => false
                ],
                'mapping_config' => [
                    'employee_id_field' => 'employee_id',
                    'date_field' => 'date',
                    'time_field' => 'time',
                    'type_field' => 'type'
                ]
            ]
        ];

        foreach ($devices as $deviceData) {
            // Vérifier si l'appareil existe déjà
            $existingDevice = BiometricDevice::where('name', $deviceData['name'])->first();
            
            if (!$existingDevice) {
                $device = BiometricDevice::create($deviceData);
                
                $this->command->info("✅ Appareil biométrique créé : {$device->name}");
                $this->command->info("   📡 URL API : {$device->api_url}");
                $this->command->info("   🔄 Statut : " . ($device->active ? 'Actif' : 'Inactif'));
            } else {
                // Mettre à jour la configuration si nécessaire
                $existingDevice->update([
                    'api_url' => $deviceData['api_url'],
                    'connection_status' => $deviceData['connection_status'],
                    'last_connection_test_at' => $deviceData['last_connection_test_at'],
                    'device_config' => $deviceData['device_config'],
                    'mapping_config' => $deviceData['mapping_config']
                ]);
                
                $this->command->info("🔄 Appareil biométrique mis à jour : {$existingDevice->name}");
                $this->command->info("   📡 URL API : {$existingDevice->api_url}");
            }
        }

        $this->command->info("");
        $this->command->info("🎯 Instructions pour tester la synchronisation :");
        $this->command->info("1. Allez dans 'Rapports > Pointages biométriques'");
        $this->command->info("2. Cliquez sur le bouton 'Synchroniser'");
        $this->command->info("3. Le système utilisera automatiquement les URLs configurées");
        $this->command->info("4. Vérifiez les logs dans storage/logs/ pour le debugging");
        $this->command->info("");
        $this->command->info("💡 Pour modifier l'URL d'un appareil :");
        $this->command->info("1. Allez dans 'Configuration > Appareils biométriques'");
        $this->command->info("2. Modifiez l'URL de l'appareil");
        $this->command->info("3. La prochaine synchronisation utilisera automatiquement la nouvelle URL");
    }
}
