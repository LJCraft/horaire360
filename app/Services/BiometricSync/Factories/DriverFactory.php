<?php

namespace App\Services\BiometricSync\Factories;

use App\Services\BiometricSync\Contracts\BiometricDriverInterface;
use App\Services\BiometricSync\Drivers\ZKTecoDriver;
use App\Services\BiometricSync\Drivers\SupremaDriver;
use App\Services\BiometricSync\Drivers\HikvisionDriver;
use App\Services\BiometricSync\Drivers\AnvizDriver;
use App\Services\BiometricSync\Drivers\GenericIPDriver;
use InvalidArgumentException;

class DriverFactory
{
    /**
     * Créer un driver selon la marque de l'appareil
     *
     * @param string $brand
     * @return BiometricDriverInterface
     * @throws InvalidArgumentException
     */
    public function create(string $brand): BiometricDriverInterface
    {
        return match(strtolower($brand)) {
            'zkteco' => new ZKTecoDriver(),
            'suprema' => new SupremaDriver(),
            'hikvision' => new HikvisionDriver(),
            'anviz' => new AnvizDriver(),
            default => new GenericIPDriver()
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
            $driver = $this->create($brand);
            return $driver->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }
} 