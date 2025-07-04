<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Services\BiometricSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllBiometricDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:all-biometric-devices 
                            {--device-id= : ID spécifique d\'un appareil à synchroniser}
                            {--force : Forcer la synchronisation même si récente}
                            {--dry-run : Simulation sans modification de la base}
                            {--fast : Utiliser la synchronisation optimisée rapide}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser tous les appareils biométriques (VERSION OPTIMISÉE RAPIDE)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info("🚀 SYNCHRONISATION OPTIMISÉE DÉMARRÉE");
        
        if ($this->option('fast')) {
            $this->info("⚡ MODE RAPIDE ACTIVÉ - Synchronisation optimisée");
        }
        
        $deviceId = $this->option('device-id');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $fastMode = $this->option('fast');

        if ($dryRun) {
            $this->warn("🔍 MODE SIMULATION - Aucune donnée ne sera modifiée");
        }

        // Récupérer les appareils à synchroniser
        $devices = $deviceId 
            ? [\App\Models\BiometricDevice::find($deviceId)]
            : \App\Models\BiometricDevice::where('is_active', true)->get();

        if (empty($devices) || (count($devices) === 1 && $devices[0] === null)) {
            $this->error('Aucun appareil trouvé à synchroniser');
            return 1;
        }

        $this->info("📱 Appareils à synchroniser: " . count($devices));

        $totalResults = [
            'synced' => 0,
            'failed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Synchroniser chaque appareil
        foreach ($devices as $device) {
            if (!$device) continue;

            $this->info("🔄 Synchronisation: {$device->name} (ID: {$device->id})");
            
            try {
                $deviceStartTime = microtime(true);
                
                // Utiliser le service de synchronisation
                $syncService = app(\App\Services\BiometricSynchronizationService::class);
                $results = $syncService->syncDevice($device, [
                    'force' => $force,
                    'dry_run' => $dryRun,
                    'fast_mode' => $fastMode,
                    'timeout' => $fastMode ? 30 : 60
                ]);

                $deviceTime = round(microtime(true) - $deviceStartTime, 2);
                
                if ($results['success']) {
                    $totalResults['synced']++;
                    $totalResults['inserted'] += $results['inserted'] ?? 0;
                    $totalResults['updated'] += $results['updated'] ?? 0;
                    $totalResults['skipped'] += $results['skipped'] ?? 0;
                    
                    $this->info("✅ {$device->name} - {$deviceTime}s");
                    $this->info("   → Nouveau: {$results['inserted']} | Mis à jour: {$results['updated']} | Ignoré: {$results['skipped']}");
                } else {
                    $totalResults['failed']++;
                    $totalResults['errors'][] = [
                        'device' => $device->name,
                        'error' => $results['error'] ?? 'Erreur inconnue'
                    ];
                    $this->error("❌ {$device->name} - Échec");
                }

            } catch (\Exception $e) {
                $totalResults['failed']++;
                $totalResults['errors'][] = [
                    'device' => $device->name,
                    'error' => $e->getMessage()
                ];
                $this->error("💥 Erreur {$device->name}: {$e->getMessage()}");
            }
        }

        $totalTime = round(microtime(true) - $startTime, 2);

        // Résumé final
        $this->info("\n" . str_repeat('=', 50));
        $this->info("📊 RÉSUMÉ SYNCHRONISATION OPTIMISÉE");
        $this->info(str_repeat('=', 50));
        
        $this->info("⏱️  Temps total: {$totalTime}s");
        $this->info("✅ Réussis: {$totalResults['synced']}");
        $this->info("❌ Échoués: {$totalResults['failed']}");
        $this->info("📥 Nouveaux: {$totalResults['inserted']}");
        $this->info("🔄 Mis à jour: {$totalResults['updated']}");
        $this->info("⏭️  Ignorés: {$totalResults['skipped']}");
        
        if ($fastMode) {
            $this->info("⚡ MODE RAPIDE: Optimisations activées");
        }

        if (!empty($totalResults['errors'])) {
            $this->error("\n❌ ERREURS:");
            foreach ($totalResults['errors'] as $error) {
                $this->error("  • {$error['device']}: {$error['error']}");
            }
        }

        return $totalResults['failed'] === 0 ? 0 : 1;
    }
} 