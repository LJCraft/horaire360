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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
// Pas de modèle Departement séparé, les départements sont dans la table postes

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
                ->where('statut', 'actif')
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
     * Afficher le formulaire d'options d'exportation
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportOptions(Request $request)
    {
        $type = $request->input('type', 'presences');
        $dateDebut = $request->input('date_debut', Carbon::now()->format('Y-m-d'));
        $employeId = $request->input('employe_id');
        $departementId = $request->input('departement');
        $serviceId = $request->input('service');
        $periode = $request->input('periode', 'mois');
        
        // Récupérer les données nécessaires pour le formulaire
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        
        // Récupérer les départements depuis la table postes
        $departements = DB::table('postes')->select('departement')->distinct()->orderBy('departement')->get();
        $services = $departements; // Utiliser les départements comme services puisqu'ils correspondent dans l'application
        
        // Déterminer le libellé du type de rapport
        $typeLabel = '';
        switch ($type) {
            case 'presences':
                $typeLabel = 'Rapport des présences';
                break;
            case 'absences':
                $typeLabel = 'Rapport des absences';
                break;
            case 'retards':
                $typeLabel = 'Rapport des retards';
                break;
            case 'ponctualite-assiduite':
                $typeLabel = 'Rapport de ponctualité et assiduité';
                break;
            case 'biometrique':
                $typeLabel = 'Rapport des pointages biométriques';
                break;
            case 'global-multi-periode':
                $typeLabel = 'Rapport global - Vue multi-période';
                break;
            default:
                $typeLabel = 'Rapport personnalisé';
        }
        
        return view('rapports.export-options', compact(
            'type',
            'typeLabel',
            'dateDebut',
            'employeId',
            'departementId',
            'serviceId',
            'periode',
            'employes',
            'departements',
            'services'
        ));
    }
    
    /**
     * Exporter en PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $type = $request->input('type', 'presences');
        $periode = $request->input('periode', 'mois');
        $dateDebutStr = $request->input('date_debut');
        $format = $request->input('format', 'pdf');
        $employeId = $request->input('employe_id');
        $departementId = $request->input('departement_id');
        $serviceId = $request->input('service_id');
        
        // Si aucune date n'est spécifiée, utiliser la date actuelle
        $dateDebut = $dateDebutStr ? Carbon::parse($dateDebutStr) : Carbon::now();
        
        // Déterminer la plage de dates en fonction de la période
        switch ($periode) {
            case 'jour':
                $dateFin = $dateDebut->copy();
                $periodeLabel = 'Journalier - ' . $dateDebut->format('d/m/Y');
                break;
            case 'semaine':
                $dateDebut = $dateDebut->startOfWeek();
                $dateFin = $dateDebut->copy()->endOfWeek();
                $periodeLabel = 'Hebdomadaire - Semaine du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
                break;
            case 'mois':
                $dateDebut = $dateDebut->startOfMonth();
                $dateFin = $dateDebut->copy()->endOfMonth();
                $periodeLabel = 'Mensuel - ' . $dateDebut->format('F Y');
                break;
            case 'annee':
                $dateDebut = $dateDebut->startOfYear();
                $dateFin = $dateDebut->copy()->endOfYear();
                $periodeLabel = 'Annuel - ' . $dateDebut->format('Y');
                break;
            default:
                $dateFin = Carbon::now();
                $periodeLabel = 'Période personnalisée';
        }
        
        // Si le format est Excel, rediriger vers la méthode d'exportation Excel
        if ($format === 'excel') {
            return $this->exportExcel($request->merge([
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d'),
                'periode_label' => $periodeLabel
            ]));
        }
        
        // Déterminer quelle méthode appeler selon le type de rapport
        switch ($type) {
            case 'absences':
                // Récupérer les données du rapport d'absences
                $query = Employe::where('statut', 'actif');
                
                if ($employeId) {
                    $query->where('id', $employeId);
                }
                
                if ($departementId) {
                    $query->whereHas('departement', function($q) use ($departementId) {
                        $q->where('id', $departementId);
                    });
                }
                
                if ($serviceId) {
                    $query->whereHas('service', function($q) use ($serviceId) {
                        $q->where('id', $serviceId);
                    });
                }
                
                $employes = $query->get();
                
                // Calculer les absences pour chaque employé
                $absences = [];
                $totalJoursOuvrables = 0;
                $totalAbsences = 0;
                
                foreach ($employes as $employe) {
                    $joursOuvrables = $this->calculerJoursOuvrables($employe->id, $dateDebut, $dateFin);
                    $presences = Presence::where('employe_id', $employe->id)
                        ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
                        ->get();
                    
                    $joursPresence = $presences->count();
                    $joursAbsence = max(0, $joursOuvrables - $joursPresence);
                    
                    $totalJoursOuvrables += $joursOuvrables;
                    $totalAbsences += $joursAbsence;
                    
                    if ($joursAbsence > 0) {
                        $absences[] = [
                            'employe' => $employe,
                            'jours_ouvrables' => $joursOuvrables,
                            'jours_absence' => $joursAbsence,
                            'taux_absenteisme' => $joursOuvrables > 0 ? round(($joursAbsence / $joursOuvrables) * 100, 1) : 0
                        ];
                    }
                }
                
                // Trier par taux d'absentéisme décroissant
                usort($absences, function($a, $b) {
                    return $b['taux_absenteisme'] <=> $a['taux_absenteisme'];
                });
                
                $tauxGlobalAbsenteisme = $totalJoursOuvrables > 0 ? round(($totalAbsences / $totalJoursOuvrables) * 100, 1) : 0;
                
                $data = [
                    'absences' => $absences,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'periodeLabel' => $periodeLabel,
                    'totalJoursOuvrables' => $totalJoursOuvrables,
                    'totalAbsences' => $totalAbsences,
                    'tauxGlobalAbsenteisme' => $tauxGlobalAbsenteisme,
                    'titre' => 'Rapport des absences',
                    'sousTitre' => 'Analyse détaillée des absences par employé'
                ];
                
                $view = 'rapports.pdf.absences';
                $filename = 'rapport-absences-' . date('Y-m-d') . '.pdf';
                break;
                
            case 'retards':
                // Récupérer les données du rapport de retards
                $query = Presence::with('employe')
                    ->where('retard', true)
                    ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);
                
                if ($employeId) {
                    $query->where('employe_id', $employeId);
                }
                
                if ($departementId || $serviceId) {
                    $query->whereHas('employe', function($q) use ($departementId, $serviceId) {
                        if ($departementId) {
                            $q->whereHas('departement', function($q2) use ($departementId) {
                                $q2->where('id', $departementId);
                            });
                        }
                        
                        if ($serviceId) {
                            $q->whereHas('service', function($q2) use ($serviceId) {
                                $q2->where('id', $serviceId);
                            });
                        }
                    });
                }
                
                $retards = $query->orderBy('date', 'desc')->get();
                
                // Grouper par employé pour les statistiques
                $retardsParEmploye = [];
                foreach ($retards as $retard) {
                    $employeId = $retard->employe_id;
                    if (!isset($retardsParEmploye[$employeId])) {
                        $retardsParEmploye[$employeId] = [
                            'employe' => $retard->employe,
                            'nombre_retards' => 0,
                            'duree_totale_minutes' => 0
                        ];
                    }
                    $retardsParEmploye[$employeId]['nombre_retards']++;
                    
                    // Calculer la durée du retard si possible
                    if ($retard->heure_arrivee && $retard->heure_prevue) {
                        $heureArrivee = Carbon::parse($retard->heure_arrivee);
                        $heurePrevue = Carbon::parse($retard->heure_prevue);
                        $dureeRetardMinutes = $heureArrivee->diffInMinutes($heurePrevue);
                        $retardsParEmploye[$employeId]['duree_totale_minutes'] += $dureeRetardMinutes;
                    }
                }
                
                // Convertir en tableau pour le tri
                $retardsParEmploye = array_values($retardsParEmploye);
                
                // Trier par nombre de retards décroissant
                usort($retardsParEmploye, function($a, $b) {
                    return $b['nombre_retards'] <=> $a['nombre_retards'];
                });
                
                $data = [
                    'retards' => $retards,
                    'retardsParEmploye' => $retardsParEmploye,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'periodeLabel' => $periodeLabel,
                    'totalRetards' => $retards->count(),
                    'titre' => 'Rapport des retards',
                    'sousTitre' => 'Analyse détaillée des retards par employé'
                ];
                
                $view = 'rapports.pdf.retards';
                $filename = 'rapport-retards-' . date('Y-m-d') . '.pdf';
                break;
                
            case 'presences':
                // Récupérer les données du rapport de présences
                $query = Presence::with('employe.poste')
                    ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);
                
                if ($employeId) {
                    $query->where('employe_id', $employeId);
                }
                
                if ($departementId || $serviceId) {
                    $query->whereHas('employe', function($q) use ($departementId, $serviceId) {
                        if ($departementId) {
                            $q->whereHas('departement', function($q2) use ($departementId) {
                                $q2->where('id', $departementId);
                            });
                        }
                        
                        if ($serviceId) {
                            $q->whereHas('service', function($q2) use ($serviceId) {
                                $q2->where('id', $serviceId);
                            });
                        }
                    });
                }
                
                $presences = $query->orderBy('date', 'desc')->get();
                
                // Statistiques
                $totalPresences = $presences->count();
                $totalRetards = $presences->where('retard', true)->count();
                $totalDepartsAnticipes = $presences->where('depart_anticipe', true)->count();
                $pourcentageAssiduite = $totalPresences > 0 
                    ? round(100 - (($totalRetards + $totalDepartsAnticipes) / $totalPresences * 100), 1) 
                    : 0;
                
                $data = [
                    'presences' => $presences,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'periodeLabel' => $periodeLabel,
                    'totalPresences' => $totalPresences,
                    'totalRetards' => $totalRetards,
                    'totalDepartsAnticipes' => $totalDepartsAnticipes,
                    'pourcentageAssiduite' => $pourcentageAssiduite,
                    'titre' => 'Rapport des présences',
                    'sousTitre' => 'Analyse détaillée des présences par employé'
                ];
                
                $view = 'rapports.pdf.presences';
                $filename = 'rapport-presences-' . date('Y-m-d') . '.pdf';
                break;
                
            case 'ponctualite-assiduite':
                // Récupérer les données du rapport de ponctualité et assiduité
                $statistiques = $this->getStatistiquesPonctualiteAssiduite($dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d'), $employeId, $departementId, $serviceId);
                
                $data = [
                    'statistiques' => $statistiques,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'periodeLabel' => $periodeLabel,
                    'titre' => 'Rapport de Ponctualité et Assiduité',
                    'sousTitre' => 'Analyse détaillée de la ponctualité et de l\'assiduité par employé'
                ];
                
                $view = 'rapports.pdf.ponctualite-assiduite';
                $filename = 'rapport-ponctualite-assiduite-' . date('Y-m-d') . '.pdf';
                break;
                
            case 'biometrique':
                // Récupérer les données du rapport biométrique
                $query = Presence::with('employe')
                    ->whereNotNull('meta_data')
                    ->where('meta_data', 'like', '%biometric_verification%')
                    ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);
                
                if ($employeId) {
                    $query->where('employe_id', $employeId);
                }
                
                if ($departementId || $serviceId) {
                    $query->whereHas('employe', function($q) use ($departementId, $serviceId) {
                        if ($departementId) {
                            $q->whereHas('departement', function($q2) use ($departementId) {
                                $q2->where('id', $departementId);
                            });
                        }
                        
                        if ($serviceId) {
                            $q->whereHas('service', function($q2) use ($serviceId) {
                                $q2->where('id', $serviceId);
                            });
                        }
                    });
                }
                
                $pointages = $query->orderBy('date', 'desc')->orderBy('heure_arrivee', 'desc')->get();
                
                // Calculer le score biométrique moyen
                $scoreMoyenBiometrique = 0;
                $totalScores = 0;
                $nombreScores = 0;
                
                foreach ($pointages as $pointage) {
                    try {
                        $metaData = json_decode($pointage->meta_data, true);
                        
                        if (isset($metaData['biometric_verification']['confidence_score'])) {
                            $totalScores += $metaData['biometric_verification']['confidence_score'];
                            $nombreScores++;
                        }
                        
                        // Si le pointage a des données de départ (checkout)
                        if (isset($metaData['checkout']['biometric_verification']['confidence_score'])) {
                            $totalScores += $metaData['checkout']['biometric_verification']['confidence_score'];
                            $nombreScores++;
                        }
                    } catch (\Exception $e) {
                        // Ignorer les erreurs de parsing JSON
                    }
                }
                
                $scoreMoyenBiometrique = $nombreScores > 0 ? number_format($totalScores / $nombreScores * 100, 1) . '%' : 'N/A';
                
                $data = [
                    'pointages' => $pointages,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'periodeLabel' => $periodeLabel,
                    'totalPointages' => $pointages->count(),
                    'scoreMoyenBiometrique' => $scoreMoyenBiometrique,
                    'titre' => 'Rapport des Pointages Biométriques',
                    'sousTitre' => 'Analyse détaillée des pointages biométriques'
                ];
                
                $view = 'rapports.pdf.biometrique';
                $filename = 'rapport-biometrique-' . date('Y-m-d') . '.pdf';
                break;
                
            case 'global-multi-periode':
                // Récupérer les données du rapport global
                $employes = $this->getEmployesWithPresences($dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d'), $employeId, $departementId, $serviceId);
                
                $data = [
                    'employes' => $employes,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'periode' => $periode,
                    'periodeLabel' => $periodeLabel,
                    'titre' => 'Rapport Global - Vue Multi-Période',
                    'sousTitre' => 'Analyse globale des présences et absences'
                ];
                
                $view = 'rapports.pdf.global-multi-periode';
                $filename = 'rapport-global-' . date('Y-m-d') . '.pdf';
                break;
                
            default:
                return redirect()->back()->with('error', 'Type de rapport non reconnu.');
        }
        
        // Pour les nouveaux types de rapports, générer le PDF
        if (isset($view) && isset($data) && isset($filename)) {
            $pdf = PDF::loadView($view, $data);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->download($filename);
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
        
        $pdf = Pdf::loadView('rapports.pdf.presences', compact(
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
        
        $pdf = Pdf::loadView('rapports.pdf.rapport', compact(
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
     * Exporter en Excel (Legacy)
     */
    public function exportExcelLegacy(Request $request)
    {
        // Implémentation à venir
        return redirect()->back()->with('info', 'L\'export Excel sera disponible prochainement.');
    }
    
    /**
     * Rapport Global - Vue Multi-Période
     */
    public function globalMultiPeriode(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|in:jour,semaine,mois,annee',
            'date_debut' => 'nullable|date',
        ]);

        $periode = $request->periode ?? 'jour';
        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now();
        
        // Déterminer la plage de dates en fonction de la période
        switch ($periode) {
            case 'jour':
                $dateFin = $dateDebut->copy();
                $periodeLabel = $dateDebut->format('d/m/Y');
                break;
            case 'semaine':
                $dateDebut = $dateDebut->startOfWeek();
                $dateFin = $dateDebut->copy()->endOfWeek();
                $periodeLabel = 'Semaine du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
                break;
            case 'mois':
                $dateDebut = $dateDebut->startOfMonth();
                $dateFin = $dateDebut->copy()->endOfMonth();
                $periodeLabel = $dateDebut->format('F Y');
                break;
            case 'annee':
                $dateDebut = $dateDebut->startOfYear();
                $dateFin = $dateDebut->copy()->endOfYear();
                $periodeLabel = $dateDebut->format('Y');
                break;
        }
        
        // Créer un tableau de jours pour la période
        $jours = [];
        $currentDate = $dateDebut->copy();
        while ($currentDate->lte($dateFin)) {
            $jours[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Récupérer tous les employés actifs
        $employes = Employe::where('statut', 'actif')
                          ->orderBy('nom')
                          ->orderBy('prenom')
                          ->get();
        
        // Récupérer toutes les présences pour la période
        $presences = Presence::whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
                           ->get();
        
        return view('rapports.global-multi-periode', compact(
            'periode',
            'dateDebut',
            'dateFin',
            'periodeLabel',
            'jours',
            'employes',
            'presences'
        ));
    }
    
    /**
     * Rapport Ponctualité & Assiduité
     */
    public function ponctualiteAssiduite(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|in:jour,semaine,mois,annee',
            'date_debut' => 'nullable|date',
            'service_id' => 'nullable|exists:services,id',
            'departement_id' => 'nullable|exists:departements,id',
            'performance' => 'nullable|in:excellent,bon,moyen,faible',
            'afficher_graphiques' => 'nullable',
            'sort_by' => 'nullable|in:nom,service,jours_travailles,ponctualite,assiduite',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        $periode = $request->periode ?? 'mois';
        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now();
        $serviceId = $request->service_id;
        $departementId = $request->departement_id;
        $performance = $request->performance;
        $afficherGraphiques = $request->has('afficher_graphiques');
        $sortBy = $request->sort_by ?? 'nom';
        $sortOrder = $request->sort_order ?? 'asc';
        
        // Déterminer la plage de dates en fonction de la période
        switch ($periode) {
            case 'jour':
                $dateFin = $dateDebut->copy();
                $periodeLabel = $dateDebut->format('d/m/Y');
                break;
            case 'semaine':
                $dateDebut = $dateDebut->startOfWeek();
                $dateFin = $dateDebut->copy()->endOfWeek();
                $periodeLabel = 'Semaine du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
                break;
            case 'mois':
                $dateDebut = $dateDebut->startOfMonth();
                $dateFin = $dateDebut->copy()->endOfMonth();
                $periodeLabel = $dateDebut->format('F Y');
                break;
            case 'annee':
                $dateDebut = $dateDebut->startOfYear();
                $dateFin = $dateDebut->copy()->endOfYear();
                $periodeLabel = $dateDebut->format('Y');
                break;
        }
        
        // Récupérer tous les employés actifs avec filtres
        $query = Employe::where('statut', 'actif');
        
        // Comme nous n'avons pas les colonnes service et departement,
        // nous allons simplement filtrer par poste_id si nécessaire
        if ($serviceId || $departementId) {
            // Pour une démo, nous pourrions filtrer par poste_id, mais
            // nous allons laisser tous les employés pour l'instant
        }
        
        $employes = $query->with(['poste'])
                         ->orderBy('nom')
                         ->orderBy('prenom')
                         ->get();
        
        // Récupérer les postes pour les filtres
        $postes = Poste::orderBy('nom')->get();
        
        // Créer des collections pour les services et départements
        // Comme nous n'avons pas ces tables, nous allons utiliser des données statiques
        $services = collect([
            (object)['id' => 'rh', 'nom' => 'Ressources Humaines'],
            (object)['id' => 'it', 'nom' => 'Informatique'],
            (object)['id' => 'finance', 'nom' => 'Finance'],
            (object)['id' => 'marketing', 'nom' => 'Marketing'],
            (object)['id' => 'operations', 'nom' => 'Opérations']
        ]);
        
        // Pour la compatibilité avec le code existant, on utilise la même structure pour les départements
        $departements = collect([
            (object)['id' => 'direction', 'nom' => 'Direction'],
            (object)['id' => 'administration', 'nom' => 'Administration'],
            (object)['id' => 'production', 'nom' => 'Production']
        ]);
        
        // Calculer les statistiques pour chaque employé
        $statistiques = collect();
        
        foreach ($employes as $employe) {
            // Jours prévus selon le planning
            $joursOuvrables = $this->calculerJoursOuvrables($employe->id, $dateDebut, $dateFin);
            
            // Récupérer les présences de l'employé
            $presences = Presence::where('employe_id', $employe->id)
                               ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
                               ->get();
            
            $joursTravailles = $presences->count();
            $retards = $presences->where('retard', true)->count();
            $departsAnticipes = $presences->where('depart_anticipe', true)->count();
            
            // Calculer les heures
            $heuresPrevues = $joursOuvrables * 8; // 8 heures par jour par défaut
            $heuresEffectuees = 0;
            
            foreach ($presences as $presence) {
                if ($presence->heure_arrivee && $presence->heure_depart) {
                    $debut = Carbon::parse($presence->heure_arrivee);
                    $fin = Carbon::parse($presence->heure_depart);
                    if ($fin < $debut) {
                        $fin->addDay();
                    }
                    $heuresEffectuees += $debut->diffInHours($fin);
                }
            }
            
            $heuresAbsence = max(0, $heuresPrevues - $heuresEffectuees);
            
            // Calculer les taux
            $tauxPonctualite = $joursTravailles > 0 ? round(100 - (($retards / $joursTravailles) * 100)) : 0;
            $tauxAssiduite = $joursOuvrables > 0 ? round(($joursTravailles / $joursOuvrables) * 100) : 0;
            
            // Filtrer par performance si nécessaire
            $performanceEmploye = '';
            if ($tauxPonctualite >= 95 && $tauxAssiduite >= 95) {
                $performanceEmploye = 'excellent';
            } elseif ($tauxPonctualite >= 80 && $tauxAssiduite >= 80) {
                $performanceEmploye = 'bon';
            } elseif ($tauxPonctualite >= 60 && $tauxAssiduite >= 60) {
                $performanceEmploye = 'moyen';
            } else {
                $performanceEmploye = 'faible';
            }
            
            if ($performance && $performanceEmploye != $performance) {
                continue;
            }
            
            $statistiques->push((object)[
                'employe' => $employe,
                'jours_prevus' => $joursOuvrables,
                'jours_travailles' => $joursTravailles,
                'heures_prevues' => $heuresPrevues,
                'heures_effectuees' => $heuresEffectuees,
                'heures_absence' => $heuresAbsence,
                'taux_ponctualite' => $tauxPonctualite,
                'taux_assiduite' => $tauxAssiduite,
                'performance' => $performanceEmploye
            ]);
        }
        
        // Trier les statistiques
        if ($sortBy == 'nom') {
            $statistiques = $statistiques->sortBy(function ($item) use ($sortOrder) {
                return $sortOrder == 'asc' ? $item->employe->nom : -1;
            });
        } elseif ($sortBy == 'service') {
            $statistiques = $statistiques->sortBy(function ($item) use ($sortOrder) {
                $serviceName = $item->employe->service ? $item->employe->service->nom : 'ZZZ';
                return $sortOrder == 'asc' ? $serviceName : -1;
            });
        } elseif ($sortBy == 'jours_travailles') {
            $statistiques = $statistiques->sortBy(function ($item) use ($sortOrder) {
                return $sortOrder == 'asc' ? $item->jours_travailles : -$item->jours_travailles;
            });
        } elseif ($sortBy == 'ponctualite') {
            $statistiques = $statistiques->sortBy(function ($item) use ($sortOrder) {
                return $sortOrder == 'asc' ? $item->taux_ponctualite : -$item->taux_ponctualite;
            });
        } elseif ($sortBy == 'assiduite') {
            $statistiques = $statistiques->sortBy(function ($item) use ($sortOrder) {
                return $sortOrder == 'asc' ? $item->taux_assiduite : -$item->taux_assiduite;
            });
        }
        
        // Préparer les données pour les graphiques
        $employesNoms = [];
        $tauxPonctualiteData = [];
        $tauxAssiduiteData = [];
        
        foreach($statistiques as $stat) {
            $employesNoms[] = $stat->employe->prenom . ' ' . $stat->employe->nom;
            $tauxPonctualiteData[] = $stat->taux_ponctualite;
            $tauxAssiduiteData[] = $stat->taux_assiduite;
        }
        
        return view('rapports.ponctualite-assiduite', compact(
            'periode',
            'dateDebut',
            'dateFin',
            'periodeLabel',
            'serviceId',
            'departementId',
            'performance',
            'afficherGraphiques',
            'services',
            'departements',
            'statistiques',
            'employesNoms',
            'tauxPonctualiteData',
            'tauxAssiduiteData'
        ));
    }
    
    /**
     * Calculer le nombre de jours ouvrables pour un employé sur une période
     */
    private function calculerJoursOuvrables($employeId, $dateDebut, $dateFin)
    {
        // Récupérer les plannings actifs pour cet employé dans la période
        $plannings = Planning::where('employe_id', $employeId)
            ->where('actif', true) // Utiliser la colonne 'actif' au lieu de 'statut'
            ->where(function ($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                    ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                    ->orWhere(function ($q) use ($dateDebut, $dateFin) {
                        $q->where('date_debut', '<=', $dateDebut)
                            ->where('date_fin', '>=', $dateFin);
                    });
            })
            ->get();

        if ($plannings->isEmpty()) {
            // Si pas de planning, on considère les jours ouvrés standard (lundi-vendredi)
            $joursOuvrables = 0;
            $period = CarbonPeriod::create($dateDebut, $dateFin);
            
            foreach ($period as $date) {
                if ($date->isWeekday()) { // Du lundi au vendredi
                    $joursOuvrables++;
                }
            }
            
            return $joursOuvrables;
        }
        
        // Sinon, on compte les jours selon les plannings
        $joursOuvrables = 0;
        $period = CarbonPeriod::create($dateDebut, $dateFin);
        
        foreach ($period as $date) {
            $jourSemaine = $date->dayOfWeekIso;
            $jourTravaille = false;
            
            foreach ($plannings as $planning) {
                $dateFormattee = $date->format('Y-m-d');
                
                if ($dateFormattee >= $planning->date_debut->format('Y-m-d') && $dateFormattee <= $planning->date_fin->format('Y-m-d')) {
                    // Vérifier dans les détails du planning si ce jour est travaillé
                    $jourTravaille = $planning->details()
                        ->where('jour', $jourSemaine)
                        ->where('jour_repos', false)
                        ->exists();
                    
                    if ($jourTravaille) {
                        break;
                    }
                }
            }
            
            if ($jourTravaille) {
                $joursOuvrables++;
            }
        }
        
        return $joursOuvrables;
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
        ]);

        $dateDebut = $request->date_debut ? Carbon::parse($request->date_debut) : Carbon::now()->startOfMonth();
        $dateFin = $request->date_fin ? Carbon::parse($request->date_fin) : Carbon::now()->endOfMonth();
        $employeId = $request->employe_id;

        // Requête de base pour tous les pointages avec métadonnées biométriques
        $query = Presence::whereNotNull('meta_data')
            ->where('meta_data', '<>', '{}')
            ->where('meta_data', '<>', 'null')
            ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')]);
        
        // La condition JSON_EXTRACT ne fonctionne pas correctement, on la supprime temporairement
        /* Ancienne condition:
        ->where(function($q) {
            // Cette condition vérifie que meta_data contient bien des données de biométrie
            $q->whereRaw("JSON_EXTRACT(meta_data, '$.biometric_verification') IS NOT NULL");
        })
        */

        // Filtrer par employé si spécifié
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }

        // Obtenir tous les pointages pour les statistiques
        $pointagesAll = $query->get();
        
        // Statistiques
        $totalPointages = $pointagesAll->count();
        $totalPointagesArriveeDepart = $pointagesAll->filter(function($p) {
            return !empty($p->heure_arrivee) && !empty($p->heure_depart);
        })->count();
        
        // Calculer le score biométrique moyen
        $scoreMoyenBiometrique = 0;
        $totalScores = 0;
        $nombreScores = 0;
        
        foreach ($pointagesAll as $pointage) {
            try {
                $metaData = json_decode($pointage->meta_data, true);
                
                if (isset($metaData['biometric_verification']['confidence_score'])) {
                    $totalScores += $metaData['biometric_verification']['confidence_score'];
                    $nombreScores++;
                }
                
                // Si le pointage a des données de départ (checkout)
                if (isset($metaData['checkout']['biometric_verification']['confidence_score'])) {
                    $totalScores += $metaData['checkout']['biometric_verification']['confidence_score'];
                    $nombreScores++;
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de parsing JSON
                \Log::error("Erreur de parsing JSON pour le pointage ID {$pointage->id}: " . $e->getMessage());
            }
        }
        
        if ($nombreScores > 0) {
            $scoreMoyenBiometrique = number_format($totalScores / $nombreScores * 100, 1) . '%';
        } else {
            $scoreMoyenBiometrique = 'N/A';
        }
        
        // Requête paginée pour l'affichage
        $pointages = $query->orderBy('date', 'desc')->orderBy('heure_arrivee', 'desc')->paginate(15);
        $employes = Employe::orderBy('nom')->get();
        
        // Récupérer les résultats d'importation s'ils existent
        $importStats = session('import_stats');
        
        return view('rapports.biometrique', compact(
            'dateDebut', 
            'dateFin', 
            'employeId', 
            'pointages', 
            'employes', 
            'totalPointages', 
            'totalPointagesArriveeDepart', 
            'scoreMoyenBiometrique',
            'importStats'
        ));
    }

    // La méthode exportPdf a été fusionnée avec celle qui se trouve plus haut dans le contrôleur
    
    /**
     * Exporter un rapport au format Excel
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportExcel(Request $request)
    {
        $type = $request->input('type', 'global-multi-periode');
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $employeId = $request->input('employe_id');
        $departementId = $request->input('departement_id');
        $serviceId = $request->input('service_id');
        $periode = $request->input('periode', 'mois');
        
        // Préparer les données pour l'export Excel
        switch ($type) {
            case 'ponctualite-assiduite':
                // Données pour le rapport de ponctualité et assiduité
                $statistiques = $this->getStatistiquesPonctualiteAssiduite($dateDebut, $dateFin, $employeId, $departementId, $serviceId);
                
                // Préparer les données pour l'export Excel
                $data = [];
                $data[] = ['Employé', 'Service', 'Taux de ponctualité', 'Taux d\'assiduité', 'Heures travaillées', 'Heures prévues', 'Retards', 'Départs anticipés'];
                
                foreach ($statistiques as $stat) {
                    $data[] = [
                        $stat->employe->nom . ' ' . $stat->employe->prenom,
                        $stat->employe->service ? $stat->employe->service->nom : 'N/A',
                        $stat->taux_ponctualite . '%',
                        $stat->taux_assiduite . '%',
                        $stat->heures_travaillees,
                        $stat->heures_prevues,
                        $stat->nombre_retards,
                        $stat->nombre_departs_anticipes
                    ];
                }
                
                $filename = 'rapport-ponctualite-assiduite-' . date('Y-m-d') . '.xlsx';
                break;
                
            case 'biometrique':
                // Données pour le rapport biométrique
                $query = Presence::query();
                
                if ($dateDebut) {
                    $query->whereDate('date', '>=', $dateDebut);
                }
                
                if ($dateFin) {
                    $query->whereDate('date', '<=', $dateFin);
                }
                
                if ($employeId) {
                    $query->where('employe_id', $employeId);
                }
                
                // Filtrer uniquement les pointages biométriques
                $query->whereNotNull('meta_data')
                      ->where('meta_data', 'like', '%biometric_verification%');
                
                $pointages = $query->orderBy('date', 'desc')->orderBy('heure_arrivee', 'desc')->get();
                
                // Préparer les données pour l'export Excel
                $data = [];
                $data[] = ['ID', 'Employé', 'Date', 'Arrivée', 'Départ', 'Score biométrique', 'Appareil', 'Type'];
                
                foreach ($pointages as $pointage) {
                    $metaData = json_decode($pointage->meta_data, true);
                    $scoreArrivee = isset($metaData['biometric_verification']['confidence_score']) 
                        ? number_format($metaData['biometric_verification']['confidence_score'] * 100, 1) . '%' 
                        : 'N/A';
                    
                    $data[] = [
                        $pointage->id,
                        $pointage->employe->nom . ' ' . $pointage->employe->prenom,
                        $pointage->date,
                        $pointage->heure_arrivee,
                        $pointage->heure_depart ?: 'N/A',
                        $scoreArrivee,
                        $metaData['device_info']['model'] ?? 'N/A',
                        $metaData['type'] ?? 'Standard'
                    ];
                }
                
                $filename = 'rapport-biometrique-' . date('Y-m-d') . '.xlsx';
                break;
                
            case 'global-multi-periode':
            default:
                // Données pour le rapport global
                $employes = $this->getEmployesWithPresences($dateDebut, $dateFin, $employeId, $departementId, $serviceId);
                
                // Préparer les données pour l'export Excel
                $data = [];
                $data[] = ['Employé', 'Service', 'Date', 'Arrivée', 'Départ', 'Heures travaillées', 'Retard', 'Départ anticipé', 'Statut'];
                
                foreach ($employes as $employe) {
                    foreach ($employe->presences as $presence) {
                        $data[] = [
                            $employe->nom . ' ' . $employe->prenom,
                            $employe->service ? $employe->service->nom : 'N/A',
                            $presence->date,
                            $presence->heure_arrivee,
                            $presence->heure_depart ?: 'N/A',
                            $presence->heures_travaillees ?: 'N/A',
                            $presence->retard ? 'Oui' : 'Non',
                            $presence->depart_anticipe ? 'Oui' : 'Non',
                            $presence->statut
                        ];
                    }
                }
                
                $filename = 'rapport-global-' . date('Y-m-d') . '.xlsx';
                break;
        }
        
        // Générer et télécharger le fichier Excel
        return (new \Maatwebsite\Excel\Excel())
            ->download(new \App\Exports\RapportExport($data), $filename);
    }
    
    /**
     * Récupérer les statistiques de ponctualité et assiduité pour une période donnée
     *
     * @param string|null $dateDebut Date de début au format Y-m-d
     * @param string|null $dateFin Date de fin au format Y-m-d
     * @param int|null $employeId ID de l'employé (optionnel)
     * @param int|null $departementId ID du département (optionnel)
     * @param int|null $serviceId ID du service (optionnel)
     * @return \Illuminate\Support\Collection Collection de statistiques par employé
     */
    private function getStatistiquesPonctualiteAssiduite($dateDebut = null, $dateFin = null, $employeId = null, $departementId = null, $serviceId = null)
    {
        // Convertir les dates si elles sont fournies
        $dateDebut = $dateDebut ? \Carbon\Carbon::parse($dateDebut) : \Carbon\Carbon::now()->startOfMonth();
        $dateFin = $dateFin ? \Carbon\Carbon::parse($dateFin) : \Carbon\Carbon::now()->endOfMonth();
        
        // Requête de base pour les employés
        $query = \App\Models\Employe::where('statut', 'actif');
        
        // Filtrer par employé si spécifié
        if ($employeId) {
            $query->where('id', $employeId);
        }
        
        // Filtrer par département ou service si spécifié
        if ($departementId) {
            // Si la relation existe dans votre modèle
            $query->whereHas('departement', function($q) use ($departementId) {
                $q->where('id', $departementId);
            });
        }
        
        if ($serviceId) {
            // Si la relation existe dans votre modèle
            $query->whereHas('service', function($q) use ($serviceId) {
                $q->where('id', $serviceId);
            });
        }
        
        // Récupérer les employés
        $employes = $query->with(['poste'])->get();
        
        // Calculer les statistiques pour chaque employé
        $statistiques = collect();
        
        foreach ($employes as $employe) {
            // Jours prévus selon le planning
            $joursOuvrables = $this->calculerJoursOuvrables($employe->id, $dateDebut, $dateFin);
            
            // Récupérer les présences de l'employé
            $presences = \App\Models\Presence::where('employe_id', $employe->id)
                               ->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
                               ->get();
            
            $joursTravailles = $presences->count();
            $retards = $presences->where('retard', true)->count();
            $departsAnticipes = $presences->where('depart_anticipe', true)->count();
            
            // Calculer les heures
            $heuresPrevues = $joursOuvrables * 8; // 8 heures par jour par défaut
            $heuresEffectuees = 0;
            $heuresTravaillees = 0;
            
            foreach ($presences as $presence) {
                if ($presence->heure_arrivee && $presence->heure_depart) {
                    $debut = \Carbon\Carbon::parse($presence->heure_arrivee);
                    $fin = \Carbon\Carbon::parse($presence->heure_depart);
                    if ($fin < $debut) {
                        $fin->addDay();
                    }
                    $heuresEffectuees += $debut->diffInHours($fin);
                    $heuresTravaillees += $debut->diffInHours($fin);
                }
            }
            
            $heuresAbsence = max(0, $heuresPrevues - $heuresEffectuees);
            
            // Calculer les taux
            $tauxPonctualite = $joursTravailles > 0 ? round(100 - (($retards / $joursTravailles) * 100)) : 0;
            $tauxAssiduite = $joursOuvrables > 0 ? round(($joursTravailles / $joursOuvrables) * 100) : 0;
            
            // Déterminer la performance
            $performance = '';
            if ($tauxPonctualite >= 95 && $tauxAssiduite >= 95) {
                $performance = 'excellent';
            } elseif ($tauxPonctualite >= 80 && $tauxAssiduite >= 80) {
                $performance = 'bon';
            } elseif ($tauxPonctualite >= 60 && $tauxAssiduite >= 60) {
                $performance = 'moyen';
            } else {
                $performance = 'faible';
            }
            
            $statistiques->push((object)[
                'employe' => $employe,
                'jours_prevus' => $joursOuvrables,
                'jours_travailles' => $joursTravailles,
                'heures_prevues' => $heuresPrevues,
                'heures_effectuees' => $heuresEffectuees,
                'heures_travaillees' => $heuresTravaillees,
                'heures_absence' => $heuresAbsence,
                'taux_ponctualite' => $tauxPonctualite,
                'taux_assiduite' => $tauxAssiduite,
                'nombre_retards' => $retards,
                'nombre_departs_anticipes' => $departsAnticipes,
                'performance' => $performance
            ]);
        }
        
        return $statistiques;
    }
    
    /**
     * Récupérer les employés avec leurs présences pour une période donnée
     *
     * @param string|null $dateDebut Date de début au format Y-m-d
     * @param string|null $dateFin Date de fin au format Y-m-d
     * @param int|null $employeId ID de l'employé (optionnel)
     * @param int|null $departementId ID du département (optionnel)
     * @param int|null $serviceId ID du service (optionnel)
     * @return \Illuminate\Database\Eloquent\Collection Collection d'employés avec leurs présences
     */
    private function getEmployesWithPresences($dateDebut = null, $dateFin = null, $employeId = null, $departementId = null, $serviceId = null)
    {
        // Convertir les dates si elles sont fournies
        $dateDebut = $dateDebut ? \Carbon\Carbon::parse($dateDebut) : \Carbon\Carbon::now()->startOfMonth();
        $dateFin = $dateFin ? \Carbon\Carbon::parse($dateFin) : \Carbon\Carbon::now()->endOfMonth();
        
        // Requête de base pour les employés
        $query = \App\Models\Employe::where('statut', 'actif');
        
        // Filtrer par employé si spécifié
        if ($employeId) {
            $query->where('id', $employeId);
        }
        
        // Filtrer par département ou service si spécifié
        // Note: Ces filtres dépendent de la structure de votre base de données
        if ($departementId) {
            // Si la relation existe dans votre modèle
            $query->whereHas('departement', function($q) use ($departementId) {
                $q->where('id', $departementId);
            });
        }
        
        if ($serviceId) {
            // Si la relation existe dans votre modèle
            $query->whereHas('service', function($q) use ($serviceId) {
                $q->where('id', $serviceId);
            });
        }
        
        // Récupérer les employés avec leurs présences pour la période spécifiée
        $employes = $query->with(['presences' => function($query) use ($dateDebut, $dateFin) {
            $query->whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
                  ->orderBy('date', 'asc');
        }])->get();
        
        return $employes;
    }
}
