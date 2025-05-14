<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\Presence;
use App\Models\Planning;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher le tableau de bord administrateur par défaut
     */
    public function index()
    {
        // Afficher le tableau de bord administrateur par défaut
        return $this->adminDashboard();
    }
    
    /**
     * Tableau de bord administrateur
     */
    public function adminDashboard()
    {
        // Statistiques générales
        $stats = [
            'employes' => Employe::count(),
            'employes_actifs' => Employe::where('statut', 'actif')->count(),
            'postes' => Poste::count(),
            'nouveaux' => Employe::where('date_embauche', '>=', Carbon::now()->subDays(30))->count(),
            'presences_today' => Presence::whereDate('date', Carbon::today())->count()
        ];
        
        // Répartition des employés par poste
        $postes = Poste::withCount(['employes' => function($query) {
            $query->where('statut', 'actif');
        }])->get();
        
        // Employés récemment ajoutés
        $recent_employes = Employe::with('poste')
                             ->orderBy('created_at', 'desc')
                             ->limit(5)
                             ->get();
        
        // Statistiques de présence du jour
        $today = Carbon::today();
        $presencesAujourdhui = Presence::whereDate('date', $today)->count();
        $retardsAujourdhui = Presence::whereDate('date', $today)->where('retard', true)->count();
        $absencesAujourdhui = Employe::where('statut', 'actif')->count() - $presencesAujourdhui;
        
        // Données pour le graphique des présences des 30 derniers jours
        $dateDebut = Carbon::now()->subDays(29)->startOfDay();
        $dateFin = Carbon::now()->endOfDay();
        
        $presencesDonnees = Presence::selectRaw('DATE(date) as jour, COUNT(*) as total')
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();
        
        // Formater les données pour le graphique
        $jours = [];
        $presencesData = [];
        
        // Préparer un tableau de dates pour les 30 derniers jours
        $periode = [];
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(29 - $i)->format('Y-m-d');
            $periode[$date] = 0;
            $jours[] = Carbon::parse($date)->format('d/m');
        }
        
        // Remplir les données de présence réelles
        foreach ($presencesDonnees as $presence) {
            $periode[$presence->jour] = $presence->total;
        }
        
        // Convertir en tableau pour le graphique
        $presencesData = array_values($periode);
        
        // Calculer les taux de présence, retard et absence
        $employesActifs = Employe::where('statut', 'actif')->count();
        $joursOuvres = 0;
        
        // Compter les jours ouvrés (lundi-vendredi) dans les 30 derniers jours
        for ($i = 0; $i < 30; $i++) {
            $jourSemaine = Carbon::now()->subDays(29 - $i)->dayOfWeek;
            if ($jourSemaine >= 1 && $jourSemaine <= 5) { // 1 = lundi, 5 = vendredi
                $joursOuvres++;
            }
        }
        
        // Total théorique de présences sur la période (jours ouvrés * employés actifs)
        $totalTheorique = $joursOuvres * $employesActifs;
        
        // Total réel de présences
        $totalPresences = Presence::whereBetween('date', [$dateDebut, $dateFin])->count();
        $totalRetards = Presence::whereBetween('date', [$dateDebut, $dateFin])->where('retard', true)->count();
        $totalAbsences = $totalTheorique > 0 ? $totalTheorique - $totalPresences : 0;
        
        // Calculer les taux
        $tauxPresence = $totalTheorique > 0 ? round(($totalPresences / $totalTheorique) * 100) : 0;
        $tauxRetard = $totalPresences > 0 ? round(($totalRetards / $totalPresences) * 100) : 0;
        $tauxAbsence = $totalTheorique > 0 ? round(($totalAbsences / $totalTheorique) * 100) : 0;
        
        // Statistiques de présence
        $statsPresence = [
            'presencesAujourdhui' => $presencesAujourdhui,
            'retardsAujourdhui' => $retardsAujourdhui,
            'absencesAujourdhui' => $absencesAujourdhui,
            'tauxPresence' => $tauxPresence,
            'tauxRetard' => $tauxRetard,
            'tauxAbsence' => $tauxAbsence
        ];
        
        return view('dashboard.admin', compact(
            'stats', 
            'postes', 
            'recent_employes', 
            'jours', 
            'presencesData', 
            'statsPresence'
        ));
    }
    
    /**
     * Tableau de bord employé
     */
    public function employeDashboard()
    {
        // Récupérer l'employé associé à l'utilisateur connecté
        $employe = Auth::user()->employe;
        
        if (!$employe) {
            return view('dashboard.employe', ['employe' => null]);
        }
        
        // Récupérer le planning courant de l'employé
        $planning = Planning::where('employe_id', $employe->id)
                          ->where('date_debut', '<=', Carbon::now())
                          ->where('date_fin', '>=', Carbon::now())
                          ->first();
        
        // Récupérer les présences récentes
        $presences = Presence::where('employe_id', $employe->id)
                           ->orderBy('date', 'desc')
                           ->limit(5)
                           ->get();
        
        return view('dashboard.employe', compact('employe', 'planning', 'presences'));
    }
    
    /**
     * Route API pour récupérer les données du tableau de bord en JSON
     */
    public function getDashboardData()
    {
        try {
            // Statistiques générales
            $stats = [
                'employes' => Employe::count(),
                'employes_actifs' => Employe::where('statut', 'actif')->count(),
                'postes' => Poste::count(),
                'presences_today' => Presence::whereDate('date', Carbon::today())->count()
            ];
            
            // Données pour le graphique des postes
            $postes = Poste::withCount('employes')->get();
                          
            $postesData = [
                'labels' => $postes->pluck('nom')->toArray(),
                'values' => $postes->pluck('employes_count')->toArray()
            ];
            
            // Données pour le graphique des présences sur 30 jours
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            
            $jours = [];
            $presencesData = [];
            
            // Préparer un tableau de dates pour les 30 derniers jours
            $periode = [];
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays(29 - $i)->format('Y-m-d');
                $periode[$date] = 0;
                $jours[] = Carbon::parse($date)->format('d/m');
            }
            
            // Données de présence par jour
            $presencesDonnees = Presence::selectRaw('DATE(date) as jour, COUNT(*) as total')
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('jour')
                ->orderBy('jour')
                ->get();
            
            // Remplir les données de présence réelles
            foreach ($presencesDonnees as $presence) {
                if (isset($periode[$presence->jour])) {
                    $periode[$presence->jour] = $presence->total;
                }
            }
            
            // Convertir en tableau pour le graphique
            $presencesData = array_values($periode);
            
            $presencesChartData = [
                'labels' => $jours,
                'values' => $presencesData
            ];
            
            // Calculer les taux de présence, retard et absence
            $employesActifs = Employe::where('statut', 'actif')->count();
            $joursOuvres = 0;
            
            // Compter les jours ouvrés (lundi-vendredi) dans les 30 derniers jours
            for ($i = 0; $i < 30; $i++) {
                $jourSemaine = Carbon::now()->subDays(29 - $i)->dayOfWeek;
                if ($jourSemaine >= 1 && $jourSemaine <= 5) { // 1 = lundi, 5 = vendredi
                    $joursOuvres++;
                }
            }
            
            // Total théorique de présences sur la période (jours ouvrés * employés actifs)
            $totalTheorique = $joursOuvres * $employesActifs;
            $totalTheorique = max(1, $totalTheorique); // Éviter division par zéro
            
            // Total réel de présences
            $totalPresences = Presence::whereBetween('date', [$startDate, $endDate])->count();
            
            // Vérifier si le champ retard existe, sinon utiliser une requête différente
            $totalRetards = 0;
            try {
                $totalRetards = Presence::whereBetween('date', [$startDate, $endDate])
                                    ->where('retard', true)
                                    ->count();
            } catch (\Exception $e) {
                // Si la colonne retard n'existe pas, on suppose qu'il n'y a pas de retards
                $totalRetards = 0;
            }
            
            $totalAbsences = $totalTheorique - $totalPresences;
            $totalAbsences = max(0, $totalAbsences);
            
            // Calculer les taux avec protection contre division par zéro
            $tauxPresence = round(($totalPresences / $totalTheorique) * 100);
            $tauxRetard = $totalPresences > 0 ? round(($totalRetards / $totalPresences) * 100) : 0;
            $tauxAbsence = round(($totalAbsences / $totalTheorique) * 100);
            
            // Statistiques de présence - assurer que les taux totalisent 100%
            $total = $tauxPresence + $tauxRetard + $tauxAbsence;
            if ($total > 0 && $total != 100) {
                $tauxPresence = max(0, 100 - $tauxRetard - $tauxAbsence);
            }
            
            $statsPresence = [
                'tauxPresence' => $tauxPresence,
                'tauxRetard' => $tauxRetard,
                'tauxAbsence' => $tauxAbsence
            ];
            
            // Renvoyer toutes les données en JSON
            return response()->json([
                'stats' => $stats,
                'postes' => $postesData,
                'presences' => $presencesChartData,
                'stats_presence' => $statsPresence
            ]);
        
        } catch (\Exception $e) {
            // Enregistrer l'erreur pour le débogage
            \Log::error('Erreur dashboard-data: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            // Renvoyer des données fictives pour éviter de casser l'interface
            return response()->json([
                'stats' => [
                    'employes' => 0,
                    'employes_actifs' => 0,
                    'postes' => 0,
                    'presences_today' => 0
                ],
                'postes' => [
                    'labels' => ['Données indisponibles'],
                    'values' => [0]
                ],
                'presences' => [
                    'labels' => ['Données indisponibles'],
                    'values' => [0]
                ],
                'stats_presence' => [
                    'tauxPresence' => 0,
                    'tauxRetard' => 0,
                    'tauxAbsence' => 0
                ],
                'error' => 'Une erreur est survenue lors du chargement des données : ' . $e->getMessage()
            ], 200); // Utiliser code 200 pour que le client puisse traiter la réponse
        }
    }
}