<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BiometricDevice;

class BiometricDeviceSeeder extends Seeder
{
    /**
     * Exécuter le seeder des appareils biométriques
     *
     * @return void
     */
    public function run()
    {
        $devices = [
            [
                'name' => 'Terminal Entrée Principale',
                'brand' => 'ZKTeco',
                'model' => 'F18',
                'connection_type' => 'ip',
                'ip_address' => '192.168.1.100',
                'port' => 4370,
                'username' => 'admin',
                'password' => '123456',
                'active' => true,
                'connection_status' => 'connected'
            ],
            [
                'name' => 'Terminal Sortie Parking',
                'brand' => 'Hikvision',
                'model' => 'DS-K1T341AM',
                'connection_type' => 'ip',
                'ip_address' => '192.168.1.101',
                'port' => 8000,
                'username' => 'admin',
                'password' => '123456',
                'active' => true,
                'connection_status' => 'connected'
            ],
            [
                'name' => 'Application Mobile API',
                'brand' => 'Horaire360',
                'model' => 'Mobile App v1.0',
                'connection_type' => 'api',
                'api_url' => 'https://api.horaire360.com/v1/attendance',
                'auth_token' => 'h360_api_key_12345',
                'active' => true,
                'connection_status' => 'connected'
            ],
            [
                'name' => 'Terminal Cafétéria',
                'brand' => 'Suprema',
                'model' => 'BioStation 3',
                'connection_type' => 'ip',
                'ip_address' => '192.168.1.102',
                'port' => 1470,
                'username' => 'admin',
                'password' => '123456',
                'active' => false,
                'connection_status' => 'disconnected'
            ]
        ];

        foreach ($devices as $deviceData) {
            BiometricDevice::create($deviceData);
        }

        $this->command->info('4 appareils biométriques créés avec succès!');
    }
}
