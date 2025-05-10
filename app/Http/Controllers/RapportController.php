<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\Planning;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use PDF;
use Illuminate\Support\Facades\Schema;

class RapportController extends Controller
{
    /**
     * Affichage de la page d'accueil des rapports.
     */
    public function index()
    {
        // Statistiques générales pour le tableau de bord des rapports
        $totalEmployes = Employe::where('statut', 'actif')->count();
        $totalPresences = Presence::whereMonth('date', Carbon::now()->month)->count();
        $totalRetards = Presence::where('retard', true)->whereMonth('date', Carbon::now()->month)->count();
        $totalDepartsAnticipes = Presence::where('depart_anticipe', true)->whereMonth('date', Carbon::now()->month)->count();
        
        // Pourcentage d'assiduité (présences sans retard ni départ anticipé)
        $pourcentageAssiduite = ($totalPresences > 0) 
            ? round(100 - (($totalRetards + $totalDepartsAnticipes) / $totalPresences * 100), 1) 
            : 0;
        
        // Données pour le graphique d'assiduité (7 derniers jours)
        $dateDebut = Carbon::now()->subDays(6)->startOfDay();
        $dateFin = Carbon::now()->endOfDay();
        $periode = CarbonPeriod::create($dateDebut, $dateFin);
        
        $donneesPeriode = [];
        $donneesPresences = [];
        $donneesRetards = [];
        
        foreach ($periode as $date) {
            $dateFormatee = $date->format('Y-m-d');
            $donneesPeriode[] = $date->format('d/m');
            
            $presencesJour = Presence::whereDate('date', $dateFormatee)->count();
            $retardsJour = Presence::whereDate('date', $dateFormatee)->where('retard', true)->count();
            
            $donneesPresences[] = $presencesJour;
            $donneesRetards[] = $retardsJour;
        }
        
        return view('rapports.index', compact(
            'totalEmployes', 
            'totalPresences', 
            'totalRetards', 
            'totalDepartsAnticipes',
            'pourcentageAssiduite',
            'donneesPeriode',
            'donneesPresences',
            'donneesRetards'
        ));
    }

    /**
     * Rapport des présences
     */
    public function presences(Request $request)
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'employe_id' => 'nullable|exists:employes,id',
            'poste_id' => 'nullable|exists:postes,id',
            'type' => 'nullable|in:tous,retards,departs_anticipes',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->startOfMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now()->endOfMonth();
        $employeId = $request->employe_id;
        $posteId = $request->poste_id;
        $type = $request->type ?? 'tous';

        $query = Presence::with('employe')
            ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);

        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        if ($posteId) {
            $query->whereHas('employe', function($q) use ($posteId) {
                $q->where('poste_id', $posteId);
            });
        }

        if ($type == 'retards') {
            $query->where('retard', true);
        } elseif ($type == 'departs_anticipes') {
            $query->where('depart_anticipe', true);
        }

        $presences = $query->orderBy('date', 'desc')->paginate(15);
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        $postes = Poste::orderBy('nom')->get();

        // Statistiques
        $totalPresences = $presences->total();
        $totalRetards = $query->where('retard', true)->count();
        $totalDepartsAnticipes = $query->where('depart_anticipe', true)->count();
        
        // Pourcentage d'assiduité (présences sans retard ni départ anticipé)
        $pourcentageAssiduite = ($totalPresences > 0) 
            ? round(100 - (($totalRetards + $totalDepartsAnticipes) / $totalPresences * 100), 1) 
            : 0;
        
        // Check if meta_data column exists in schema
        $hasMetaDataColumn = Schema::hasColumn('presences', 'meta_data');
        
        // Ajouter des informations sur les pointages biométriques
        $presencesAvecBiometrie = $hasMetaDataColumn ? $query->whereNotNull('meta_data')->count() : 0;
        $pourcentageBiometrie = $totalPresences > 0 ? round(($presencesAvecBiometrie / $totalPresences) * 100) : 0;

        return view('rapports.presences', compact(
            'presences', 
            'employes',
            'postes',
            'dateDebut', 
            'dateFin', 
            'employeId',
            'posteId',
            'type',
            'totalPresences', 
            'totalRetards', 
            'totalDepartsAnticipes',
            'pourcentageAssiduite',
            'presencesAvecBiometrie',
            'pourcentageBiometrie'
        ));
    }

    /**
     * Rapport des absences
     */
    public function absences(Request $request)
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'employe_id' => 'nullable|exists:employes,id',
            'poste_id' => 'nullable|exists:postes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->startOfMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now()->endOfMonth();
        $employeId = $request->employe_id;
        $posteId = $request->poste_id;

        // Récupérer tous les employés actifs
        $query = Employe::where('statut', 'actif');
        if ($employeId) {
            $query->where('id', $employeId);
        }
        
        if ($posteId) {
            $query->where('poste_id', $posteId);
        }
        
        $employes = $query->get();

        // Calculate working days and absences for each employee first
        $employeStatsMap = [];
        foreach ($employes as $employe) {
            $joursOuvrables = 0;
            $joursAbsence = 0;
            $datesAbsence = [];
            
            // Récupérer les plannings actifs pour cet employé dans la période
            $plannings = Planning::where('employe_id', $employe->id)
                ->where('actif', true)
                ->where(function ($query) use ($dateDebut, $dateFin) {
                    $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                        ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                        ->orWhere(function ($q) use ($dateDebut, $dateFin) {
                            $q->where('date_debut', '<=', $dateDebut)
                                ->where('date_fin', '>=', $dateFin);
                        });
                })
                ->get();

            if (!$plannings->isEmpty()) {
                // Create a period of dates
                $period = CarbonPeriod::create($dateDebut, $dateFin);
                
                foreach ($period as $date) {
                    $jourSemaine = $date->dayOfWeekIso;
                    $planningPourCeJour = false;
                    $jourRepos = true;
                    
                    foreach ($plannings as $planning) {
                        if ($date->between($planning->date_debut, $planning->date_fin)) {
                            $planningDetail = $planning->details()->where('jour', $jourSemaine)->first();
                            if ($planningDetail) {
                                $planningPourCeJour = true;
                                $jourRepos = $planningDetail->jour_repos;
                                break;
                            }
                        }
                    }
                    
                    if ($planningPourCeJour && !$jourRepos) {
                        $joursOuvrables++;
                        
                        // Check if employee has checked in this day
                        $presence = Presence::where('employe_id', $employe->id)
                            ->where('date', $date->format('Y-m-d'))
                            ->first();
                            
                        if (!$presence) {
                            $joursAbsence++;
                            $datesAbsence[] = $date->format('Y-m-d');
                        }
                    }
                }
            }
            
            $employeStatsMap[$employe->id] = [
                'jours_ouvrables' => $joursOuvrables,
                'jours_absence' => $joursAbsence,
                'taux_absenteisme' => ($joursOuvrables > 0) ? round(($joursAbsence / $joursOuvrables) * 100, 1) : 0,
                'dates_absence' => $datesAbsence
            ];
        }

        // Pour chaque employé, vérifier les jours d'absence
        $absences = [];
        $totalJoursAbsence = 0;
        $totalJoursOuvrables = 0;

        foreach ($employes as $employe) {
            // Skip employees with no working days
            if (!isset($employeStatsMap[$employe->id]) || $employeStatsMap[$employe->id]['jours_ouvrables'] == 0) {
                continue;
            }
            
            $employeStats = $employeStatsMap[$employe->id];
            $totalJoursOuvrables += $employeStats['jours_ouvrables'];
            $totalJoursAbsence += $employeStats['jours_absence'];
            
            // Only add to the absences array if there are absences
            if ($employeStats['jours_absence'] > 0) {
                // Add an entry for each employee with absences
                $absences[] = [
                    'employe' => $employe,
                    'jours_ouvrables' => $employeStats['jours_ouvrables'],
                    'jours_absence' => $employeStats['jours_absence'],
                    'taux_absenteisme' => $employeStats['taux_absenteisme'],
                    'dates_absence' => $employeStats['dates_absence']
                ];
            }
        }

        // Alias totalAbsences to totalJoursAbsence for the view
        $totalAbsences = $totalJoursAbsence;
        
        // Calculate global absence rate
        $tauxGlobalAbsenteisme = ($totalJoursOuvrables > 0) 
            ? round(($totalJoursAbsence / $totalJoursOuvrables) * 100, 1) 
            : 0;

        // Paginer manuellement les résultats
        $page = $request->get('page', 1);
        $perPage = 15;
        $total = count($absences);
        $absencesPaginated = array_slice($absences, ($page - 1) * $perPage, $perPage);

        $absencesPagination = new \Illuminate\Pagination\LengthAwarePaginator(
            $absencesPaginated,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $employesList = Employe::where('statut', 'actif')->orderBy('nom')->get();
        $postes = Poste::orderBy('nom')->get();

        // Process data for the chart using the same employee stats
        $employeAbsenceData = [];
        foreach ($employes as $employe) {
            if (isset($employeStatsMap[$employe->id])) {
                $stats = $employeStatsMap[$employe->id];
                
                $employeAbsenceData[] = [
                    'employe' => $employe,
                    'jours_absence' => $stats['jours_absence'],
                    'jours_ouvrables' => $stats['jours_ouvrables'],
                    'taux_absenteisme' => $stats['taux_absenteisme'],
                    'dates_absence' => $stats['dates_absence']
                ];
            }
        }

        return view('rapports.absences', compact(
            'employesList',
            'postes',
            'dateDebut',
            'dateFin',
            'employeId',
            'posteId',
            'totalJoursAbsence',
            'totalAbsences',
            'totalJoursOuvrables',
            'tauxGlobalAbsenteisme'
        ))->with([
            'absences' => $absencesPagination,
            'absencesData' => $employeAbsenceData
        ]);
    }

    /**
     * Rapport des retards
     */
    public function retards(Request $request)
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'employe_id' => 'nullable|exists:employes,id',
            'poste_id' => 'nullable|exists:postes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->startOfMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now()->endOfMonth();
        $employeId = $request->employe_id;
        $posteId = $request->poste_id;

        $query = Presence::with('employe')
            ->where('retard', true)
            ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);

        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        if ($posteId) {
            $query->whereHas('employe', function($q) use ($posteId) {
                $q->where('poste_id', $posteId);
            });
        }

        $retards = $query->orderBy('date', 'desc')->paginate(15);
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        $postes = Poste::orderBy('nom')->get();

        // Statistiques
        $totalRetards = $retards->total();
        
        // Calculate average delays per day
        $joursTotal = Carbon::parse($dateDebut)->diffInDays(Carbon::parse($dateFin)) + 1;
        $retardsMoyenParJour = ($joursTotal > 0) ? round($totalRetards / $joursTotal, 1) : 0;
        
        // Regrouper par employé pour les statistiques
        $retardsParEmployeQuery = DB::table('presences')
            ->join('employes', 'presences.employe_id', '=', 'employes.id')
            ->join('postes', 'employes.poste_id', '=', 'postes.id')
            ->select(
                'employes.id',
                'employes.nom',
                'employes.prenom',
                'postes.nom as poste',
                DB::raw('COUNT(*) as nombre_retards')
            )
            ->where('presences.retard', true)
            ->whereBetween('presences.date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);
        
        if ($employeId) {
            $retardsParEmployeQuery->where('employes.id', $employeId);
        }
        
        if ($posteId) {
            $retardsParEmployeQuery->where('employes.poste_id', $posteId);
        }
        
        $retardsParEmploye = $retardsParEmployeQuery
            ->groupBy('employes.id', 'employes.nom', 'employes.prenom', 'postes.nom')
            ->orderByDesc('nombre_retards')
            ->paginate(5);

        return view('rapports.retards', compact(
            'retards',
            'employes',
            'postes',
            'dateDebut',
            'dateFin',
            'employeId',
            'posteId',
            'totalRetards',
            'retardsParEmploye',
            'retardsMoyenParJour'
        ));
    }

    /**
     * Exporter en PDF
     */
    public function exportPdf(Request $request)
    {
        $type = $request->input('type', 'presences');
        $dateDebut = $request->input('date_debut', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', Carbon::now()->format('Y-m-d'));
        
        // Déterminer quelle méthode appeler selon le type de rapport
        switch ($type) {
            case 'absences':
                return $this->exportAbsencesPdf($request);
            case 'retards':
                return $this->exportRetardsPdf($request);
            default: // presences
                return $this->exportPresencesPdf($request);
        }
    }
    
    /**
     * Exporter les présences en PDF
     */
    private function exportPresencesPdf(Request $request)
    {
        // Récupérer les paramètres
        $employeId = $request->input('employe_id');
        $posteId = $request->input('poste_id');
        $dateDebut = $request->input('date_debut', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', Carbon::now()->format('Y-m-d'));
        $type = $request->input('type', 'tous');
        
        // Requête de base
        $query = Presence::with('employe.poste')
            ->whereBetween('date', [$dateDebut, $dateFin]);
        
        // Appliquer les filtres
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }
        
        if ($posteId) {
            $query->whereHas('employe', function($q) use ($posteId) {
                $q->where('poste_id', $posteId);
            });
        }
        
        if ($type == 'retards') {
            $query->where('retard', true);
        } elseif ($type == 'departs_anticipes') {
            $query->where('depart_anticipe', true);
        }
        
        // Récupérer toutes les présences
        $presences = $query->orderBy('date', 'desc')
            ->orderBy(DB::raw('(SELECT CONCAT(prenom, " ", nom) FROM employes WHERE employes.id = presences.employe_id)'))
            ->get();
        
        // Statistiques
        $totalPresences = $presences->count();
        $totalRetards = $presences->where('retard', true)->count();
        $totalDepartsAnticipes = $presences->where('depart_anticipe', true)->count();
        $pourcentageAssiduite = ($totalPresences > 0) 
            ? round(100 - (($totalRetards + $totalDepartsAnticipes) / $totalPresences * 100), 1) 
            : 0;
        
        $pdf = PDF::loadView('rapports.pdf.presences', compact(
            'presences',
            'dateDebut',
            'dateFin',
            'type',
            'totalPresences',
            'totalRetards',
            'totalDepartsAnticipes',
            'pourcentageAssiduite'
        ));
        
        return $pdf->download('rapport-presences-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }
    
    /**
     * Exporter les absences en PDF
     */
    private function exportAbsencesPdf(Request $request)
    {
        // Récupérer les paramètres
        $employeId = $request->input('employe_id');
        $posteId = $request->input('poste_id');
        $dateDebut = $request->input('date_debut', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', Carbon::now()->format('Y-m-d'));
        
        // Liste des employés actifs
        $employesQuery = Employe::where('statut', 'actif');
        
        // Filtrage par employé
        if ($employeId) {
            $employesQuery->where('id', $employeId);
        }
        
        // Filtrage par poste
        if ($posteId) {
            $employesQuery->where('poste_id', $posteId);
        }
        
        $employes = $employesQuery->orderBy('nom')->orderBy('prenom')->get();
        
        $periode = CarbonPeriod::create($dateDebut, $dateFin);
        $jours = iterator_to_array($periode);
        
        // Construire le tableau d'absences
        $absences = [];
        $totalJoursOuvrables = 0;
        $totalAbsences = 0;
        
        foreach ($employes as $employe) {
            $ligneAbsence = [
                'employe' => $employe,
                'jours_absence' => 0,
                'jours_ouvrables' => 0,
                'dates_absence' => []
            ];
            
            foreach ($jours as $jour) {
                // Ne pas compter les weekends
                if ($jour->isWeekend()) {
                    continue;
                }
                
                $ligneAbsence['jours_ouvrables']++;
                $totalJoursOuvrables++;
                
                // Vérifier s'il y a une présence pour ce jour
                $presence = Presence::where('employe_id', $employe->id)
                    ->whereDate('date', $jour->format('Y-m-d'))
                    ->first();
                
                if (!$presence) {
                    $ligneAbsence['jours_absence']++;
                    $ligneAbsence['dates_absence'][] = $jour->format('Y-m-d');
                    $totalAbsences++;
                }
            }
            
            // Calculer le taux d'absentéisme pour cet employé
            $ligneAbsence['taux_absenteisme'] = ($ligneAbsence['jours_ouvrables'] > 0)
                ? round(($ligneAbsence['jours_absence'] / $ligneAbsence['jours_ouvrables']) * 100, 1)
                : 0;
            
            $absences[] = $ligneAbsence;
        }
        
        // Taux global d'absentéisme
        $tauxGlobalAbsenteisme = ($totalJoursOuvrables > 0)
            ? round(($totalAbsences / $totalJoursOuvrables) * 100, 1)
            : 0;
        
        // Préparer les données pour le PDF
        $titre = 'Rapport des absences';
        $sousTitre = 'Analyse détaillée des absences par employé';
        
        // Statistiques pour le rapport
        $statistiques = [
            [
                'valeur' => $totalAbsences,
                'libelle' => 'Jours d\'absence',
                'couleur' => '#e74c3c'
            ],
            [
                'valeur' => $totalJoursOuvrables,
                'libelle' => 'Jours ouvrables',
                'couleur' => '#3498db'
            ],
            [
                'valeur' => $tauxGlobalAbsenteisme . '%',
                'libelle' => 'Taux d\'absentéisme',
                'couleur' => '#f39c12'
            ]
        ];
        
        // Données pour le tableau détaillé
        $donnees = [];
        $colonnes = [
            'employe' => 'Employé',
            'poste' => 'Poste',
            'jours_ouvrables' => 'Jours ouvrables',
            'jours_absence' => 'Jours d\'absence',
            'taux' => 'Taux d\'absentéisme'
        ];
        
        foreach ($absences as $absence) {
            $donnees[] = [
                'employe' => $absence['employe']->prenom . ' ' . $absence['employe']->nom,
                'poste' => $absence['employe']->poste->nom,
                'jours_ouvrables' => $absence['jours_ouvrables'],
                'jours_absence' => $absence['jours_absence'],
                'taux' => $absence['taux_absenteisme'] . '%'
            ];
        }
        
        // Notes pour le rapport
        $notes = [
            'Les absences sont calculées comme les jours ouvrables sans pointage',
            'Les weekends ne sont pas comptés comme des jours ouvrables',
            'Période analysée : du ' . Carbon::parse($dateDebut)->format('d/m/Y') . ' au ' . Carbon::parse($dateFin)->format('d/m/Y')
        ];
        
        $pdf = PDF::loadView('rapports.pdf.rapport', compact(
            'titre',
            'sousTitre',
            'dateDebut',
            'dateFin',
            'statistiques',
            'donnees',
            'colonnes',
            'notes'
        ));
        
        return $pdf->download('rapport-absences-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }
    
    /**
     * Exporter les retards en PDF
     */
    private function exportRetardsPdf(Request $request)
    {
        // Récupérer les paramètres
        $employeId = $request->input('employe_id');
        $posteId = $request->input('poste_id');
        $dateDebut = $request->input('date_debut', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateFin = $request->input('date_fin', Carbon::now()->format('Y-m-d'));
        
        // Requête de base
        $query = Presence::with('employe.poste')
            ->where('retard', true)
            ->whereBetween('date', [$dateDebut, $dateFin]);
        
        // Filtrage par employé
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }
        
        // Filtrage par poste
        if ($posteId) {
            $query->whereHas('employe', function($q) use ($posteId) {
                $q->where('poste_id', $posteId);
            });
        }
        
        // Grouper par employé pour obtenir les statistiques
        $retardsParEmploye = DB::table('presences')
            ->join('employes', 'presences.employe_id', '=', 'employes.id')
            ->join('postes', 'employes.poste_id', '=', 'postes.id')
            ->select(
                'employes.id as employe_id',
                'employes.nom',
                'employes.prenom',
                'postes.nom as poste',
                DB::raw('COUNT(*) as nombre_retards'),
                DB::raw('AVG(TIME_TO_SEC(TIMEDIFF(presences.heure_arrivee, presences.heure_depart))) as duree_moyenne_secondes')
            )
            ->where('presences.retard', true)
            ->whereBetween('presences.date', [$dateDebut, $dateFin]);
        
        if ($employeId) {
            $retardsParEmploye->where('employes.id', $employeId);
        }
        
        if ($posteId) {
            $retardsParEmploye->where('employes.poste_id', $posteId);
        }
        
        $retardsParEmploye = $retardsParEmploye
            ->groupBy('employes.id', 'employes.nom', 'employes.prenom', 'postes.nom')
            ->orderBy('nombre_retards', 'desc')
            ->paginate(20);
        
        // Liste détaillée des retards
        $retards = $query->orderBy('date', 'desc')->paginate(20);
        
        // Données pour les filtres
        $employes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
        
        $postes = Poste::orderBy('nom')->get();
        
        // Statistiques
        $totalRetards = $query->count();
        $retardsMoyenParJour = Carbon::parse($dateDebut)->diffInDays(Carbon::parse($dateFin)) + 1;
        $retardsMoyenParJour = ($retardsMoyenParJour > 0) ? round($totalRetards / $retardsMoyenParJour, 1) : 0;
        
        return view('rapports.retards', compact(
            'retards',
            'retardsParEmploye',
            'employes',
            'postes',
            'employeId',
            'posteId',
            'dateDebut',
            'dateFin',
            'totalRetards',
            'retardsMoyenParJour'
        ));
    }
    
    /**
     * Exporter en Excel
     */
    public function exportExcel(Request $request)
    {
        $type = $request->input('type', 'presences');
        
        // L'export Excel pourrait être implémenté en utilisant la bibliothèque 
        // Laravel Excel (https://laravel-excel.com/)
        // Exemple simple avec retour d'un message pour le moment
        
        return redirect()->back()->with('error', 'L\'export Excel sera disponible prochainement.');
    }

    /**
     * Afficher le rapport des pointages biométriques
     */
    public function biometrique(Request $request)
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'employe_id' => 'nullable|exists:employes,id',
            'poste_id' => 'nullable|exists:postes,id',
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->startOfMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now()->endOfMonth();
        $employeId = $request->employe_id;
        $posteId = $request->poste_id;

        // Check if meta_data column exists in schema
        $hasMetaDataColumn = Schema::hasColumn('presences', 'meta_data');

        $query = Presence::with('employe')
            ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);
            
        // Only filter by meta_data if the column exists
        if ($hasMetaDataColumn) {
            $query->whereNotNull('meta_data');
        }

        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        if ($posteId) {
            $query->whereHas('employe', function($q) use ($posteId) {
                $q->where('poste_id', $posteId);
            });
        }

        $pointages = $query->orderBy('date', 'desc')->paginate(15);
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        $postes = Poste::orderBy('nom')->get();

        // Statistiques
        $totalPointages = $pointages->total();
        
        // Only count points with departure time if column exists
        $totalPointagesArriveeDepart = $hasMetaDataColumn 
            ? $query->whereNotNull('heure_depart')->count() 
            : $query->whereNotNull('heure_depart')->count();
        
        // Calculate biometric confidence score if column exists
        $scoresMoyens = [];
        if ($hasMetaDataColumn) {
            foreach ($pointages as $pointage) {
                $metaData = json_decode($pointage->meta_data, true);
                if (isset($metaData['biometric_verification']['confidence_score'])) {
                    $scoresMoyens[] = $metaData['biometric_verification']['confidence_score'];
                }
            }
        }
        
        $scoreMoyenBiometrique = count($scoresMoyens) > 0 ? round(array_sum($scoresMoyens) / count($scoresMoyens), 2) : 0;

        return view('rapports.biometrique', compact(
            'pointages',
            'employes',
            'postes',
            'dateDebut',
            'dateFin',
            'employeId',
            'posteId',
            'totalPointages',
            'totalPointagesArriveeDepart',
            'scoreMoyenBiometrique'
        ));
    }
}
