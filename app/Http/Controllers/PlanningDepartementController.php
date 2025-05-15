<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Poste;
use App\Models\Planning;
use App\Models\Employe;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlanningDepartementController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher la liste des plannings par département
     */
    public function index(Request $request)
    {
        // Récupérer tous les départements (depuis la table postes)
        $departements = Poste::select('departement')
            ->distinct()
            ->whereNotNull('departement')
            ->where('departement', '!=', '')
            ->orderBy('departement')
            ->pluck('departement');
            
        // Filtrer par département si spécifié
        $departementSelectionne = $request->input('departement');
        
        // Récupérer les postes du département sélectionné
        $postes = collect();
        $employes = collect();
        
        if ($departementSelectionne) {
            $postes = Poste::where('departement', $departementSelectionne)
                ->orderBy('nom')
                ->get();
                
            // Récupérer les employés de ce département
            $employes = Employe::whereIn('poste_id', $postes->pluck('id'))
                ->where('statut', 'actif')
                ->orderBy('nom')
                ->get();
        }
        
        // Récupérer les plannings existants pour ces employés
        $plannings = collect();
        
        if ($employes->count() > 0) {
            $plannings = Planning::whereIn('employe_id', $employes->pluck('id'))
                ->where(function($query) {
                    // Plannings actifs ou futurs
                    $query->where('date_fin', '>=', Carbon::today());
                })
                ->orderBy('date_debut')
                ->get();
        }
        
        return view('plannings.departement.index', compact(
            'departements', 
            'departementSelectionne',
            'postes',
            'employes',
            'plannings'
        ));
    }
    
    /**
     * Afficher le formulaire pour créer un planning départemental
     */
    public function create(Request $request)
    {
        $departement = $request->input('departement');
        
        if (!$departement) {
            return redirect()->route('plannings.departement.index')
                ->with('error', 'Veuillez sélectionner un département.');
        }
        
        // Récupérer les employés du département
        $postes = Poste::where('departement', $departement)->pluck('id');
        $employes = Employe::whereIn('poste_id', $postes)
            ->where('statut', 'actif')
            ->orderBy('nom')
            ->get();
            
        return view('plannings.departement.create', compact(
            'departement',
            'employes'
        ));
    }
    
    /**
     * Enregistrer un nouveau planning départemental
     */
    public function store(Request $request)
    {
        $request->validate([
            'departement' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'employe_ids' => 'required|array',
            'employe_ids.*' => 'exists:employes,id',
            'description' => 'nullable|string',
        ]);
        
        // Récupérer les employés sélectionnés
        $employeIds = $request->employe_ids;
        
        try {
            DB::beginTransaction();
            
            foreach ($employeIds as $employeId) {
                // Vérifier s'il existe déjà un planning individuel pour cet employé aux dates indiquées
                $planningIndividuel = Planning::where('employe_id', $employeId)
                    ->where(function($query) use ($request) {
                        $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                            ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                            ->orWhere(function($q) use ($request) {
                                $q->where('date_debut', '<=', $request->date_debut)
                                  ->where('date_fin', '>=', $request->date_fin);
                            });
                    })
                    ->first();
                
                // Si l'employé n'a pas de planning individuel pour cette période, créer un nouveau planning
                if (!$planningIndividuel) {
                    $planning = Planning::create([
                        'employe_id' => $employeId,
                        'date_debut' => $request->date_debut,
                        'date_fin' => $request->date_fin,
                        'titre' => 'Planning départemental - ' . $request->departement,
                        'description' => $request->description,
                        'actif' => true,
                    ]);
                    
                    // Créer les détails du planning pour chaque jour
                    for ($jour = 1; $jour <= 7; $jour++) {
                        // Récupérer les données du formulaire pour ce jour
                        $jourType = $request->input('jour_type_' . $jour);
                        
                        if ($jourType) {
                            $detail = [
                                'planning_id' => $planning->id,
                                'jour' => $jour,
                                'jour_repos' => ($jourType === 'repos'),
                                'jour_entier' => ($jourType === 'jour_entier'),
                                'note' => $request->input('note_' . $jour),
                            ];
                            
                            if ($jourType === 'horaire') {
                                $detail['heure_debut'] = $request->input('heure_debut_' . $jour);
                                $detail['heure_fin'] = $request->input('heure_fin_' . $jour);
                            }
                            
                            \App\Models\PlanningDetail::create($detail);
                        }
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('plannings.departement.index', ['departement' => $request->departement])
                ->with('success', 'Planning départemental créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue : ' . $e->getMessage()]);
        }
    }

    /**
     * Afficher le calendrier des plannings départementaux
     */
    public function calendrier(Request $request)
    {
        // Récupérer tous les départements pour le filtre
        $departements = Poste::select('departement')
            ->distinct()
            ->whereNotNull('departement')
            ->where('departement', '!=', '')
            ->orderBy('departement')
            ->pluck('departement');
            
        // Filtrer par département si spécifié
        $departementSelectionne = $request->input('departement');
        
        // Récupérer les postes du département sélectionné
        $postes = collect();
        
        if ($departementSelectionne) {
            $postes = Poste::where('departement', $departementSelectionne)
                ->orderBy('nom')
                ->get();
        } else {
            // Si aucun département n'est sélectionné, récupérer tous les postes
            $postes = Poste::whereNotNull('departement')
                ->where('departement', '!=', '')
                ->orderBy('departement')
                ->orderBy('nom')
                ->get();
        }
        
        return view('plannings.departement.calendrier', compact(
            'departements', 
            'departementSelectionne',
            'postes'
        ));
    }
    
    /**
     * Récupérer les données du calendrier regroupées par poste
     */
    public function getCalendarData(Request $request)
    {
        // Récupérer les paramètres de la requête
        $start = $request->input('start');
        $end = $request->input('end');
        $departement = $request->input('departement');
        $posteId = $request->input('poste_id');

        // Requête pour obtenir les employés par poste
        $postesQuery = Poste::with(['employes' => function($q) {
            $q->where('statut', 'actif');
        }]);
        
        // Filtrer par département
        if ($departement) {
            $postesQuery->where('departement', $departement);
        } else {
            $postesQuery->whereNotNull('departement')->where('departement', '!=', '');
        }
        
        // Filtrer par poste spécifique si demandé
        if ($posteId) {
            $postesQuery->where('id', $posteId);
        }
        
        $postes = $postesQuery->orderBy('departement')->orderBy('nom')->get();
        
        // Générer des couleurs distinctes pour chaque poste
        $colors = [
            '#4285F4', '#EA4335', '#FBBC05', '#34A853', // Google colors
            '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', // Flat UI colors
            '#607D8B', '#FF5722', '#795548', '#9C27B0', '#673AB7', '#3F51B5', // Material colors
            '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFC107', '#FF9800'  // More Material colors
        ];
        
        $events = [];
        $colorIndex = 0;
        
        foreach ($postes as $poste) {
            // Attribuer une couleur à ce poste
            $posteColor = $colors[$colorIndex % count($colors)];
            $colorIndex++;
            
            // Récupérer les IDs des employés de ce poste
            $employeIds = $poste->employes->pluck('id')->toArray();
            
            if (empty($employeIds)) {
                continue; // Passer au poste suivant s'il n'y a pas d'employés
            }
            
            // Récupérer les plannings pour ces employés
            $planningsQuery = Planning::with(['employe', 'details'])
                ->whereIn('employe_id', $employeIds);
            
            // Filtrer par période
            if ($start && $end) {
                $planningsQuery->where(function($q) use ($start, $end) {
                    $q->whereBetween('date_debut', [$start, $end])
                      ->orWhereBetween('date_fin', [$start, $end])
                      ->orWhere(function($q2) use ($start, $end) {
                          $q2->where('date_debut', '<=', $start)
                             ->where('date_fin', '>=', $end);
                      });
                });
            }
            
            $plannings = $planningsQuery->get();
            
            // Regrouper les plannings par jour et par type d'horaire
            $planningsByDay = [];
            
            foreach ($plannings as $planning) {
                // Calculer la période du planning en jours
                $startDate = Carbon::parse($planning->date_debut);
                $endDate = Carbon::parse($planning->date_fin);
                $currentDate = $startDate->copy();
                
                // Pour chaque jour du planning
                while ($currentDate->lte($endDate)) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $dayOfWeek = $currentDate->dayOfWeek ?: 7; // 1 (lundi) à 7 (dimanche)
                    
                    // Trouver le détail du planning pour ce jour de la semaine
                    $detail = $planning->details->firstWhere('jour', $dayOfWeek);
                    
                    if ($detail) {
                        // Déterminer le type d'horaire pour ce jour
                        $type = 'horaire';
                        if ($detail->jour_repos) {
                            $type = 'repos';
                        } elseif ($detail->jour_entier) {
                            $type = 'jour_entier';
                        }
                        
                        // Clé pour regrouper les employés avec le même type d'horaire ce jour-là
                        $timeKey = $type;
                        if ($type === 'horaire') {
                            $timeKey = $detail->heure_debut . '-' . $detail->heure_fin;
                        }
                        
                        // Initialiser le tableau pour ce jour et ce type d'horaire s'il n'existe pas
                        if (!isset($planningsByDay[$dateStr])) {
                            $planningsByDay[$dateStr] = [];
                        }
                        if (!isset($planningsByDay[$dateStr][$timeKey])) {
                            $planningsByDay[$dateStr][$timeKey] = [
                                'type' => $type,
                                'heure_debut' => $detail->heure_debut,
                                'heure_fin' => $detail->heure_fin,
                                'employes' => []
                            ];
                        }
                        
                        // Ajouter l'employé à ce groupe
                        $planningsByDay[$dateStr][$timeKey]['employes'][] = [
                            'id' => $planning->employe->id,
                            'nom' => $planning->employe->nom_complet,
                            'planning_id' => $planning->id
                        ];
                    }
                    
                    $currentDate->addDay();
                }
            }
            
            // Créer les événements pour le calendrier
            foreach ($planningsByDay as $date => $timeGroups) {
                foreach ($timeGroups as $timeKey => $group) {
                    $count = count($group['employes']);
                    $title = $poste->nom . ' (' . $count . ')';
                    
                    // Déterminer l'apparence en fonction du type
                    $backgroundColor = $posteColor;
                    $borderColor = $posteColor;
                    $textColor = '#FFFFFF';
                    $className = 'poste-event';
                    
                    if ($group['type'] === 'repos') {
                        $backgroundColor = '#28a745'; // vert pour repos
                        $borderColor = '#28a745';
                        $className .= ' repos-event';
                    } elseif ($group['type'] === 'jour_entier') {
                        // Garder la couleur du poste mais ajouter une classe spécifique
                        $className .= ' jour-entier-event';
                    } else {
                        // Pour les horaires spécifiques, ajouter les heures au titre
                        $title .= ' ' . substr($group['heure_debut'], 0, 5) . '-' . substr($group['heure_fin'], 0, 5);
                        $className .= ' horaire-event';
                    }
                    
                    $events[] = [
                        'id' => $poste->id . '-' . $date . '-' . $timeKey,
                        'title' => $title,
                        'start' => $date,
                        'end' => $date,
                        'allDay' => true,
                        'backgroundColor' => $backgroundColor,
                        'borderColor' => $borderColor,
                        'textColor' => $textColor,
                        'className' => $className,
                        'extendedProps' => [
                            'poste' => $poste->nom,
                            'departement' => $poste->departement,
                            'type' => $group['type'],
                            'heure_debut' => $group['heure_debut'],
                            'heure_fin' => $group['heure_fin'],
                            'employes' => $group['employes'],
                            'count' => $count
                        ]
                    ];
                }
            }
        }
        
        return response()->json($events);
    }
} 