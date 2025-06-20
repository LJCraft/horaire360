<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CriteriaEngine\CriteriaEngine;
use App\Models\Presence;
use App\Models\Employe;
use App\Enums\ProcessingStatus;
use Carbon\Carbon;

class ProcessCriteriaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'criteria:process
                            {--employe= : ID de l\'employé à retraiter}
                            {--from= : Date de début (YYYY-MM-DD)}
                            {--to= : Date de fin (YYYY-MM-DD)}
                            {--status= : Statut spécifique à retraiter}
                            {--force : Forcer le retraitement même si déjà traité}
                            {--batch-size=100 : Taille des lots pour le traitement}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traiter ou retraiter les critères de pointage pour les employés';

    protected CriteriaEngine $criteriaEngine;

    public function __construct(CriteriaEngine $criteriaEngine)
    {
        parent::__construct();
        $this->criteriaEngine = $criteriaEngine;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Démarrage du traitement des critères de pointage');

        // Récupérer les options
        $employeId = $this->option('employe');
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subMonth();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $status = $this->option('status');
        $force = $this->option('force');
        $batchSize = (int) $this->option('batch-size');

        $this->info("📅 Période: {$from->toDateString()} → {$to->toDateString()}");

        // Construire la requête
        $query = Presence::whereBetween('date', [$from, $to]);

        if ($employeId) {
            $employe = Employe::find($employeId);
            if (!$employe) {
                $this->error("❌ Employé ID {$employeId} non trouvé");
                return 1;
            }
            $query->where('employe_id', $employeId);
            $this->info("👤 Employé: {$employe->nom} {$employe->prenom}");
        }

        if ($status && !$force) {
            $query->where('criteria_processing_status', $status);
            $this->info("📊 Statut filtré: {$status}");
        }

        if (!$force) {
            $query->whereIn('criteria_processing_status', [
                ProcessingStatus::NOT_PROCESSED->value,
                ProcessingStatus::CRITERIA_ERROR->value,
                ProcessingStatus::REPROCESSING_REQUIRED->value
            ]);
        }

        $totalPointages = $query->count();
        $this->info("📈 Total à traiter: {$totalPointages} pointages");

        if ($totalPointages === 0) {
            $this->info("✅ Aucun pointage à traiter");
            return 0;
        }

        // Demander confirmation pour un grand nombre
        if ($totalPointages > 1000 && !$this->confirm("Traiter {$totalPointages} pointages ?")) {
            $this->info("❌ Traitement annulé");
            return 0;
        }

        // Traitement par lots
        $processed = 0;
        $successful = 0;
        $errors = 0;

        $this->withProgressBar($totalPointages, function ($bar) use ($query, $batchSize, &$processed, &$successful, &$errors) {
            $query->chunk($batchSize, function ($pointages) use ($bar, &$processed, &$successful, &$errors) {
                $batchResult = $this->criteriaEngine->applyCriteriaToBatch($pointages);
                
                foreach ($pointages as $pointage) {
                    $result = $batchResult->getResult($pointage->id);
                    if ($result && $result->isSuccessful()) {
                        $successful++;
                    } else {
                        $errors++;
                    }
                    $processed++;
                    $bar->advance();
                }
            });
        });

        $this->newLine(2);
        $this->info("✅ Traitement terminé!");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total traité', $processed],
                ['Succès', $successful],
                ['Erreurs', $errors],
                ['Taux de succès', round(($successful / $processed) * 100, 1) . '%']
            ]
        );

        // Afficher les statistiques par statut
        $this->displayStatusStatistics($from, $to, $employeId);

        return 0;
    }

    /**
     * Afficher les statistiques par statut
     */
    protected function displayStatusStatistics(Carbon $from, Carbon $to, ?int $employeId): void
    {
        $this->info("\n📊 Statistiques par statut:");

        $query = Presence::whereBetween('date', [$from, $to]);
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        $statistics = $query->selectRaw('criteria_processing_status, COUNT(*) as count')
            ->groupBy('criteria_processing_status')
            ->get();

        $tableData = [];
        foreach ($statistics as $stat) {
            $status = ProcessingStatus::from($stat->criteria_processing_status);
            $tableData[] = [
                $status->getDescription(),
                $stat->count,
                $status->requiresAction() ? '⚠️' : '✅'
            ];
        }

        $this->table(
            ['Statut', 'Nombre', 'Action requise'],
            $tableData
        );

        // Identifier les employés sans planning
        $employesSansPlanning = $query->where('criteria_processing_status', ProcessingStatus::PENDING_PLANNING->value)
            ->join('employes', 'presences.employe_id', '=', 'employes.id')
            ->selectRaw('employes.id, employes.nom, employes.prenom, COUNT(*) as pointages_en_attente')
            ->groupBy('employes.id', 'employes.nom', 'employes.prenom')
            ->get();

        if ($employesSansPlanning->isNotEmpty()) {
            $this->warn("\n⚠️  Employés avec pointages en attente de planning:");
            foreach ($employesSansPlanning as $employe) {
                $this->line("   • {$employe->nom} {$employe->prenom} ({$employe->pointages_en_attente} pointages)");
            }
        }
    }
}
