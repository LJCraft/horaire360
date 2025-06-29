<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiFacialSyncService;
use App\Models\BiometricDevice;

/**
 * Commande pour synchroniser les appareils API-FACIAL
 * Usage: php artisan sync:api-facial [--device=ID] [--force]
 */
class SyncApiFacialDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:api-facial 
                            {--device= : ID de l\'appareil spécifique à synchroniser}
                            {--force : Forcer la synchronisation même si récemment synchronisé}
                            {--stats : Afficher les statistiques de synchronisation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les pointages depuis les appareils API-FACIAL (reconnaissance faciale mobile)';

    protected $syncService;

    public function __construct(ApiFacialSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Synchronisation API-FACIAL - Démarrage');
        $this->info('===================================');

        // Afficher les statistiques si demandé
        if ($this->option('stats')) {
            $this->showStats();
            return;
        }

        $deviceId = $this->option('device');
        $force = $this->option('force');

        try {
            if ($deviceId) {
                // Synchroniser un appareil spécifique
                $this->syncSingleDevice($deviceId, $force);
            } else {
                // Synchroniser tous les appareils
                $this->syncAllDevices($force);
            }

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            return 1;
        }

        $this->info('✅ Synchronisation terminée avec succès');
        return 0;
    }

    /**
     * Synchroniser un appareil spécifique
     */
    protected function syncSingleDevice(int $deviceId, bool $force): void
    {
        $device = BiometricDevice::find($deviceId);
        
        if (!$device) {
            $this->error("❌ Appareil {$deviceId} non trouvé");
            return;
        }

        if ($device->brand !== 'api-facial') {
            $this->error("❌ L'appareil {$deviceId} n'est pas de type API-FACIAL");
            return;
        }

        $this->info("🔄 Synchronisation de l'appareil: {$device->name}");

        // Vérifier si synchronisation récente (sauf si forcée)
        if (!$force && $device->last_sync_at && 
            $device->last_sync_at->diffInMinutes() < 5) {
            $this->warn("⚠️  Appareil synchronisé récemment (il y a {$device->last_sync_at->diffInMinutes()} min)");
            $this->info("💡 Utilisez --force pour forcer la synchronisation");
            return;
        }

        $result = $this->syncService->syncDevice($device);
        $this->displaySyncResult($device, $result);
    }

    /**
     * Synchroniser tous les appareils
     */
    protected function syncAllDevices(bool $force): void
    {
        $devices = BiometricDevice::where('brand', 'api-facial')
                                 ->where('is_active', true)
                                 ->get();

        if ($devices->isEmpty()) {
            $this->warn('⚠️  Aucun appareil API-FACIAL actif trouvé');
            return;
        }

        $this->info("🔄 Synchronisation de {$devices->count()} appareils API-FACIAL");
        $this->newLine();

        $bar = $this->output->createProgressBar($devices->count());
        $bar->start();

        $totalSuccess = 0;
        $totalRecords = 0;

        foreach ($devices as $device) {
            // Vérifier si synchronisation récente (sauf si forcée)
            if (!$force && $device->last_sync_at && 
                $device->last_sync_at->diffInMinutes() < 5) {
                $bar->advance();
                continue;
            }

            $result = $this->syncService->syncDevice($device);
            
            if ($result['success']) {
                $totalSuccess++;
                $totalRecords += $result['records_count'] ?? 0;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher le résumé
        $this->info("📊 Résumé de la synchronisation:");
        $this->info("   • Appareils traités: {$devices->count()}");
        $this->info("   • Synchronisations réussies: {$totalSuccess}");
        $this->info("   • Total des enregistrements: {$totalRecords}");
    }

    /**
     * Afficher le résultat de synchronisation
     */
    protected function displaySyncResult(BiometricDevice $device, array $result): void
    {
        if ($result['success']) {
            $this->info("✅ {$device->name}:");
            $this->info("   • Enregistrements ajoutés: {$result['records_count']}");
            
            if (isset($result['errors_count']) && $result['errors_count'] > 0) {
                $this->warn("   • Erreurs: {$result['errors_count']}");
            }
            
            if (isset($result['duplicates_count']) && $result['duplicates_count'] > 0) {
                $this->info("   • Doublons ignorés: {$result['duplicates_count']}");
            }
            
            $this->info("   • Durée: {$result['sync_duration']}s");
        } else {
            $this->error("❌ {$device->name}: {$result['error']}");
        }
        
        $this->newLine();
    }

    /**
     * Afficher les statistiques
     */
    protected function showStats(): void
    {
        $this->info('📊 Statistiques de synchronisation API-FACIAL');
        $this->info('============================================');

        $stats = $this->syncService->getSyncStats();
        
        $this->info("Synchronisations totales: {$stats['total_syncs']}");
        $this->info("Réussites: {$stats['successful_syncs']}");
        $this->info("Échecs: {$stats['failed_syncs']}");
        $this->info("Enregistrements synchronisés: {$stats['total_records_synced']}");
        
        if ($stats['last_sync']) {
            $this->info("Dernière synchronisation: {$stats['last_sync']->format('Y-m-d H:i:s')}");
        }

        $this->newLine();

        // Afficher les appareils API-FACIAL
        $devices = BiometricDevice::where('brand', 'api-facial')->get();
        
        if ($devices->isNotEmpty()) {
            $this->info('📱 Appareils API-FACIAL configurés:');
            $this->table(
                ['ID', 'Nom', 'Statut', 'Dernière sync', 'URL API'],
                $devices->map(function ($device) {
                    return [
                        $device->id,
                        $device->name,
                        $device->connection_status,
                        $device->last_sync_at ? $device->last_sync_at->format('Y-m-d H:i:s') : 'Jamais',
                        substr($device->api_url, 0, 50) . '...'
                    ];
                })
            );
        }

        $this->newLine();

        // Afficher les logs récents
        if (!empty($stats['recent_logs'])) {
            $this->info('📋 Logs récents:');
            $this->table(
                ['Date', 'Succès', 'Enregistrements', 'Durée', 'Erreur'],
                collect($stats['recent_logs'])->map(function ($log) {
                    return [
                        $log['date']->format('Y-m-d H:i:s'),
                        $log['success'] ? '✅' : '❌',
                        $log['records_synced'],
                        $log['sync_duration'] . 's',
                        $log['error_message'] ?? '-'
                    ];
                })
            );
        }
    }
} 