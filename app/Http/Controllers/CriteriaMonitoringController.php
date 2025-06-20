<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CriteriaEngine\CriteriaEngine;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\CriterePointage;
use App\Enums\ProcessingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CriteriaMonitoringController extends Controller
{
    protected CriteriaEngine $criteriaEngine;

    public function __construct(CriteriaEngine $criteriaEngine)
    {
        $this->criteriaEngine = $criteriaEngine;
    }

    /**
     * Dashboard principal de monitoring
     */
    public function dashboard(Request $request)
    {
        $dateFrom = $request->get('from', Carbon::now()->subMonth()->toDateString());
        $dateTo = $request->get('to', Carbon::now()->toDateString());

        // Statistiques générales
        $stats = $this->getGeneralStatistics($dateFrom, $dateTo);
        
        // Répartition par statut
        $statusDistribution = $this->getStatusDistribution($dateFrom, $dateTo);
        
        // Employés avec problèmes
        $problematicEmployees = $this->getProblematicEmployees($dateFrom, $dateTo);
        
        // Tendances temporelles
        $trends = $this->getProcessingTrends($dateFrom, $dateTo);

        return view('criteria-monitoring.dashboard', compact(
            'stats', 'statusDistribution', 'problematicEmployees', 'trends',
            'dateFrom', 'dateTo'
        ));
    }

    /**
     * Liste détaillée des pointages avec filtres
     */
    public function pointages(Request $request)
    {
        $query = Presence::with(['employe'])
            ->whereBetween('date', [
                $request->get('from', Carbon::now()->subMonth()->toDateString()),
                $request->get('to', Carbon::now()->toDateString())
            ]);

        // Filtres
        if ($request->filled('status')) {
            $query->where('criteria_processing_status', $request->get('status'));
        }

        if ($request->filled('employe_id')) {
            $query->where('employe_id', $request->get('employe_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('employe', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        $pointages = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $employes = Employe::orderBy('nom')->get();
        $statuses = ProcessingStatus::cases();

        return view('criteria-monitoring.pointages', compact(
            'pointages', 'employes', 'statuses'
        ));
    }

    /**
     * Détails d'un pointage spécifique
     */
    public function pointageDetails($id)
    {
        $pointage = Presence::with(['employe'])->findOrFail($id);
        
        // Récupérer les critères applicables
        $criteria = $this->criteriaEngine->getApplicableCriteria($pointage->employe, $pointage->date);
        
        // Récupérer les détails de traitement depuis meta_data
        $processingDetails = $pointage->meta_data['criteria_processing'] ?? null;
        
        // Simuler le retraitement pour voir ce qui se passerait
        $simulationResult = $this->criteriaEngine->applyCriteriaToPointage($pointage);

        return view('criteria-monitoring.pointage-details', compact(
            'pointage', 'criteria', 'processingDetails', 'simulationResult'
        ));
    }

    /**
     * Retraiter un pointage spécifique
     */
    public function reprocessPointage($id)
    {
        $pointage = Presence::findOrFail($id);
        
        try {
            $result = $this->criteriaEngine->applyCriteriaToPointage($pointage);
            
            return response()->json([
                'success' => true,
                'message' => 'Pointage retraité avec succès',
                'status' => $result->status->value,
                'summary' => $result->getSummary()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retraitement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retraitement en lot
     */
    public function batchReprocess(Request $request)
    {
        $request->validate([
            'pointage_ids' => 'required|array',
            'pointage_ids.*' => 'exists:presences,id'
        ]);

        $pointages = Presence::whereIn('id', $request->pointage_ids)->get();
        
        try {
            $batchResult = $this->criteriaEngine->applyCriteriaToBatch($pointages);
            
            return response()->json([
                'success' => true,
                'message' => "Retraitement terminé pour {$pointages->count()} pointages",
                'summary' => $batchResult->getSummary()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retraitement en lot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API pour les statistiques en temps réel
     */
    public function apiStats(Request $request)
    {
        $dateFrom = $request->get('from', Carbon::now()->subWeek()->toDateString());
        $dateTo = $request->get('to', Carbon::now()->toDateString());

        return response()->json([
            'general' => $this->getGeneralStatistics($dateFrom, $dateTo),
            'status_distribution' => $this->getStatusDistribution($dateFrom, $dateTo),
            'trends' => $this->getProcessingTrends($dateFrom, $dateTo, 'day')
        ]);
    }

    /**
     * Obtenir les statistiques générales
     */
    protected function getGeneralStatistics($dateFrom, $dateTo): array
    {
        $baseQuery = Presence::whereBetween('date', [$dateFrom, $dateTo]);

        return [
            'total_pointages' => $baseQuery->count(),
            'fully_processed' => $baseQuery->where('criteria_processing_status', ProcessingStatus::FULLY_PROCESSED->value)->count(),
            'partially_processed' => $baseQuery->where('criteria_processing_status', ProcessingStatus::PARTIALLY_PROCESSED->value)->count(),
            'pending_planning' => $baseQuery->where('criteria_processing_status', ProcessingStatus::PENDING_PLANNING->value)->count(),
            'errors' => $baseQuery->where('criteria_processing_status', ProcessingStatus::CRITERIA_ERROR->value)->count(),
            'not_processed' => $baseQuery->where('criteria_processing_status', ProcessingStatus::NOT_PROCESSED->value)->count(),
            'success_rate' => $this->calculateSuccessRate($baseQuery),
            'avg_processing_time' => $this->calculateAverageProcessingTime($baseQuery)
        ];
    }

    /**
     * Obtenir la répartition par statut
     */
    protected function getStatusDistribution($dateFrom, $dateTo): array
    {
        return Presence::whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw('criteria_processing_status, COUNT(*) as count')
            ->groupBy('criteria_processing_status')
            ->get()
            ->mapWithKeys(function ($item) {
                $status = ProcessingStatus::from($item->criteria_processing_status);
                return [
                    $item->criteria_processing_status => [
                        'count' => $item->count,
                        'label' => $status->getDescription(),
                        'requires_action' => $status->requiresAction()
                    ]
                ];
            })
            ->toArray();
    }

    /**
     * Obtenir les employés avec des problèmes
     */
    protected function getProblematicEmployees($dateFrom, $dateTo): array
    {
        return Presence::whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('criteria_processing_status', [
                ProcessingStatus::CRITERIA_ERROR->value,
                ProcessingStatus::PENDING_PLANNING->value,
                ProcessingStatus::REPROCESSING_REQUIRED->value
            ])
            ->join('employes', 'presences.employe_id', '=', 'employes.id')
            ->selectRaw('
                employes.id,
                employes.nom,
                employes.prenom,
                presences.criteria_processing_status,
                COUNT(*) as problematic_count
            ')
            ->groupBy('employes.id', 'employes.nom', 'employes.prenom', 'presences.criteria_processing_status')
            ->orderBy('problematic_count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Obtenir les tendances de traitement
     */
    protected function getProcessingTrends($dateFrom, $dateTo, $interval = 'week'): array
    {
        $dateFormat = $interval === 'day' ? '%Y-%m-%d' : '%Y-%u';
        
        return Presence::whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw("
                DATE_FORMAT(date, '{$dateFormat}') as period,
                criteria_processing_status,
                COUNT(*) as count
            ")
            ->groupBy('period', 'criteria_processing_status')
            ->orderBy('period')
            ->get()
            ->groupBy('period')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    return [$item->criteria_processing_status => $item->count];
                });
            })
            ->toArray();
    }

    /**
     * Calculer le taux de succès
     */
    protected function calculateSuccessRate($query): float
    {
        $total = $query->count();
        if ($total === 0) return 0;

        $successful = $query->whereIn('criteria_processing_status', [
            ProcessingStatus::FULLY_PROCESSED->value,
            ProcessingStatus::PARTIALLY_PROCESSED->value
        ])->count();

        return round(($successful / $total) * 100, 1);
    }

    /**
     * Calculer le temps de traitement moyen
     */
    protected function calculateAverageProcessingTime($query): ?float
    {
        $avgSeconds = $query->whereNotNull('criteria_processed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, criteria_processed_at)) as avg_time')
            ->value('avg_time');

        return $avgSeconds ? round($avgSeconds, 1) : null;
    }
}
