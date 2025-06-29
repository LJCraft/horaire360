<?php

namespace App\Services\BiometricSync\Factories;

use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Services\BiometricSync\Drivers\ZKTecoDriver;
use App\Services\BiometricSync\Drivers\SupremaDriver;
use App\Services\BiometricSync\Drivers\HikvisionDriver;
use App\Services\BiometricSync\Drivers\AnvizDriver;
use App\Services\BiometricSync\Drivers\ApiFacialDriver;
use App\Services\BiometricSync\Drivers\GenericIPDriver;
use InvalidArgumentException;

class DriverFactory
{
    /**
     * Créer un driver selon la marque de l'appareil
     *
     * @param string $brand
     * @param BiometricDevice $device
     * @return BiometricDriverInterface
     * @throws InvalidArgumentException
     */
    public function create(string $brand, BiometricDevice $device = null): BiometricDriverInterface
    {
        return match(strtolower($brand)) {
            'zkteco' => new ZKTecoDriver($device),
            'suprema' => new SupremaDriver($device),
            'hikvision' => new HikvisionDriver($device),
            'anviz' => new AnvizDriver($device),
            'api-facial' => new ApiFacialDriver($device),
            'generic' => new GenericIPDriver($device),
            default => new GenericIPDriver($device)
        };
    }

    /**
     * Obtenir la liste des drivers disponibles
     *
     * @return array
     */
    public function getAvailableDrivers(): array
    {
        return [
            'zkteco' => [
                'name' => 'ZKTeco',
                'class' => ZKTecoDriver::class,
                'available' => $this->checkDriverAvailability('zkteco')
            ],
            'suprema' => [
                'name' => 'Suprema',
                'class' => SupremaDriver::class,
                'available' => $this->checkDriverAvailability('suprema')
            ],
            'hikvision' => [
                'name' => 'Hikvision',
                'class' => HikvisionDriver::class,
                'available' => $this->checkDriverAvailability('hikvision')
            ],
            'anviz' => [
                'name' => 'Anviz',
                'class' => AnvizDriver::class,
                'available' => $this->checkDriverAvailability('anviz')
            ],
            'api-facial' => [
                'name' => 'API-FACIAL',
                'class' => ApiFacialDriver::class,
                'available' => true // Toujours disponible
            ],
            'generic' => [
                'name' => 'Générique',
                'class' => GenericIPDriver::class,
                'available' => true // Toujours disponible
            ]
        ];
    }

    /**
     * Vérifier la disponibilité d'un driver
     *
     * @param string $brand
     * @return bool
     */
    protected function checkDriverAvailability(string $brand): bool
    {
        try {
            $driver = $this->create($brand, null);
            return method_exists($driver, 'isAvailable') ? $driver->isAvailable() : true;
        } catch (\Exception $e) {
            return false;
        }
    }
} 