<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BiometricSynchronizationService;
use App\Models\BiometricDevice;

class TestSynchronization extends Command
{
    protected $signature = 'sync:test';
    protected $description = 'Tester la synchronisation des appareils biométriques';

    public function handle()
    {
        $this->info('🔄 Test de synchronisation des appareils biométriques');
        $this->line('');

        // Vérifier les appareils disponibles
        $devices = BiometricDevice::where('active', true)->get();
        $this->info("📱 Appareils actifs trouvés: {$devices->count()}");

        foreach ($devices as $device) {
            $this->info("   - {$device->name} ({$device->brand}) - ID: {$device->device_id}");
        }

        $this->line('');
        $this->info('🚀 Lancement de la synchronisation...');

        try {
            $service = new BiometricSynchronizationService();
            $result = $service->synchronizeAllConnectedDevices();

            $this->line('');
            $this->info('📊 RÉSULTATS DE LA SYNCHRONISATION:');
            $this->info("✅ Appareils synchronisés: {$result['synchronized_devices']}/{$result['total_devices']}");
            $this->info("📝 Enregistrements traités: {$result['processed_records']}");
            $this->info("⏱️ Temps d'exécution: {$result['execution_time']}s");

            if (!empty($result['errors'])) {
                $this->line('');
                $this->error('❌ ERREURS:');
                foreach ($result['errors'] as $error) {
                    $this->error("   - {$error}");
                }
            }

            if (!empty($result['warnings'])) {
                $this->line('');
                $this->warn('⚠️ AVERTISSEMENTS:');
                foreach ($result['warnings'] as $warning) {
                    $this->warn("   - {$warning}");
                }
            }

            // Détails par appareil
            if (!empty($result['devices_results'])) {
                $this->line('');
                $this->info('📱 DÉTAILS PAR APPAREIL:');
                foreach ($result['devices_results'] as $deviceResult) {
                    $status = $deviceResult['success'] ? '✅' : '❌';
                    $this->info("   {$status} {$deviceResult['device_name']}:");
                    $this->info("      - Enregistrements: {$deviceResult['processed_records']}");
                    if (!empty($deviceResult['errors'])) {
                        foreach ($deviceResult['errors'] as $error) {
                            $this->error("      - Erreur: {$error}");
                        }
                    }
                }
            }

            $this->line('');
            if ($result['success']) {
                $this->info('🎉 Synchronisation terminée avec succès !');
            } else {
                $this->error('💥 Synchronisation terminée avec des erreurs');
            }

        } catch (\Exception $e) {
            $this->line('');
            $this->error('💥 ERREUR CRITIQUE:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
