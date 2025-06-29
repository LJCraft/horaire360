<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\PlanningDetail;
use App\Models\Employe;
use App\Imports\PlanningsImport;
use App\Exports\PlanningsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlanningController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher la liste des plannings
     */
    public function index(Request $request)
    {
        // Filtres
        $query = Planning::with('employe');
        
        if ($request->filled('employe_id')) {
            $query->where('employe_id', $request->employe_id);
        }
        
        if ($request->filled('statut')) {
            $today = Carbon::today();
            
            switch ($request->statut) {
                case 'en_cours':
                    $query->where('date_debut', '<=', $today)
                          ->where('date_fin', '>=', $today);
                    break;
                case 'a_venir':
                    $query->where('date_debut', '>', $today);
                    break;
                case 'termine':
                    $query->where('date_fin', '<', $today);
                    break;
            }
        }
        
        if ($request->filled('date_debut')) {
            $query->where('date_debut', '>=', $request->date_debut);
        }
        
        if ($request->filled('date_fin')) {
            $query->where('date_fin', '<=', $request->date_fin);
        }
        
        // Tri
        $sortField = $request->input('sort', 'date_debut');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $plannings = $query->paginate(10);
        $planning = $plannings->first();
        
        // Liste des employés pour le filtre
        $employes = Employe::orderBy('nom')->get();
        
        return view('plannings.edit', compact('planning', 'employes'));//, 'detailsParJour'
        
    }

    /**
     * Mettre à jour un planning
     */
    public function update(Request $request, Planning $planning)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        // Vérifier s'il existe déjà un planning pour cet employé à ces dates (autre que celui-ci)
        $conflits = Planning::where('employe_id', $request->employe_id)
            ->where('id', '!=', $planning->id)
            ->where(function($query) use ($request) {
                $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                    ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                    ->orWhere(function($q) use ($request) {
                        $q->where('date_debut', '<=', $request->date_debut)
                          ->where('date_fin', '>=', $request->date_fin);
                    });
            })
            ->count();
            
        if ($conflits > 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_debut' => 'Un planning existe déjà pour cet employé à ces dates.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Mettre à jour le planning
            $planning->update([
                'employe_id' => $request->employe_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'titre' => $request->titre,
                'description' => $request->description,
            ]);
            
            // Supprimer les anciens détails
            $planning->details()->delete();
            
            // Créer les nouveaux détails
            for ($jour = 1; $jour <= 7; $jour++) {
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
                    
                    PlanningDetail::create($detail);
                }
            }
            
            DB::commit();
            
            return redirect()->route('plannings.show', $planning)
                ->with('success', 'Planning modifié avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue : ' . $e->getMessage()]);
        }
    }

    /**
     * Supprimer un planning
     */
    public function destroy(Planning $planning)
    {
        // Récupérer les paramètres de la requête actuelle pour maintenir le contexte de pagination
        $currentPage = request()->get('page', 1);
        $perPage = 15; // Même valeur que dans la méthode index
        $searchFilters = request()->except(['_token', '_method']);
        
        // Supprimer les détails du planning puis le planning
        $planning->details()->delete();
        $planning->delete();
        
        // Calculer la page correcte après suppression
        $redirectPage = $this->calculateCorrectPageAfterDeletion($searchFilters, $currentPage, $perPage);
        
        // Construire l'URL de redirection avec les bons paramètres
        $redirectUrl = route('plannings.index', array_merge($searchFilters, ['page' => $redirectPage]));
        
        return redirect($redirectUrl)
            ->with('success', 'Planning supprimé avec succès.');
    }

    /**
     * Calculer la page correcte après suppression d'un planning
     */
    private function calculateCorrectPageAfterDeletion($filters, $currentPage, $perPage)
    {
        // Reconstruire la requête avec les mêmes filtres que la page courante
        $query = Planning::with('employe');
        
        // Appliquer les mêmes filtres que dans la méthode index
        if (isset($filters['employe_id']) && !empty($filters['employe_id'])) {
            $query->where('employe_id', $filters['employe_id']);
        }
        
        if (isset($filters['statut']) && !empty($filters['statut'])) {
            $now = now();
            if ($filters['statut'] === 'en_cours') {
                $query->where('date_debut', '<=', $now)
                      ->where('date_fin', '>=', $now);
            } elseif ($filters['statut'] === 'a_venir') {
                $query->where('date_debut', '>', $now);
            } elseif ($filters['statut'] === 'termine') {
                $query->where('date_fin', '<', $now);
            }
        }
        
        if (isset($filters['date_debut']) && !empty($filters['date_debut'])) {
            $query->where('date_debut', '>=', $filters['date_debut']);
        }
        
        if (isset($filters['date_fin']) && !empty($filters['date_fin'])) {
            $query->where('date_fin', '<=', $filters['date_fin']);
        }
        
        // Compter le nombre total de plannings restants
        $totalPlannings = $query->count();
        
        // Si aucun planning ne reste, rediriger vers la page 1
        if ($totalPlannings === 0) {
            return 1;
        }
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalPlannings / $perPage);
        
        // Si la page courante est supérieure au nombre total de pages, 
        // rediriger vers la dernière page disponible
        if ($currentPage > $totalPages) {
            return max(1, $totalPages);
        }
        
        // Sinon, rester sur la page courante
        return $currentPage;
    }
    
    /**
     * Afficher le calendrier des plannings
     */
    public function calendrier(Request $request)
    {
        // Redirect to the new working calendar route
        return redirect()->route('plannings.departement.calendrier');
    }
    
    /**
     * Importer des plannings depuis un fichier Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
        
        try {
            Excel::import(new PlanningsImport, $request->file('file'));
            
            return redirect()->route('plannings.index')
                ->with('success', 'Plannings importés avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['file' => 'Erreur lors de l\'importation : ' . $e->getMessage()]);
        }
    }

    /**
     * Exporter le modèle d'importation
     */
    public function exportTemplate()
    {
        return Excel::download(new PlanningsExport(true), 'modele_plannings.xlsx');
    }
    
    /**
     * Exporter tous les plannings
     */
    public function export()
    {
        return Excel::download(new PlanningsExport, 'plannings.xlsx');
    }

    /**
     * Rechercher des employés pour la création de planning
     */
    public function searchEmployes(Request $request)
    {
        $search = $request->input('search');
        $withoutPlanning = $request->input('without_planning', false);
        
        $query = Employe::where('statut', 'actif')
            ->where(function($query) use ($search) {
                $query->where('nom', 'like', "%$search%")
                    ->orWhere('prenom', 'like', "%$search%")
                    ->orWhere('matricule', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
            
        // Si on filtre par employés sans planning
        if ($withoutPlanning) {
            $query->whereNotIn('id', function($subquery) {
                $subquery->select('employe_id')
                    ->from('plannings');
            });
        }
        
        $employes = $query->orderBy('nom')
            ->limit(10)
            ->get(['id', 'matricule', 'nom', 'prenom', 'email']);
            
        // Ajouter un indicateur pour chaque employé indiquant s'il a déjà un planning
        $employes->each(function($employe) {
            $employe->has_planning = Planning::where('employe_id', $employe->id)->exists();
        });
        
        return response()->json($employes);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(Request $request)
    {
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        $employe_id = $request->input('employe_id');
        $employe = $employe_id ? Employe::find($employe_id) : null;
        
        // Trouver les employés qui n'ont pas de planning
        $employesSansPlannings = Employe::where('statut', 'actif')
            ->whereNotIn('id', function($query) {
                $query->select('employe_id')
                    ->from('plannings');
            })
            ->orderBy('nom')
            ->get();
        
        return view('plannings.create', compact('employes', 'employe_id', 'employe', 'employesSansPlannings'));
    }

    /**
     * Enregistrer un nouveau planning
     */
    public function store(Request $request)
    {
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        // Vérifier s'il existe déjà un planning pour cet employé à ces dates
        $conflits = Planning::where('employe_id', $request->employe_id)
            ->where(function($query) use ($request) {
                $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                    ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                    ->orWhere(function($q) use ($request) {
                        $q->where('date_debut', '<=', $request->date_debut)
                          ->where('date_fin', '>=', $request->date_fin);
                    });
            })
            ->count();
            
        if ($conflits > 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_debut' => 'Un planning existe déjà pour cet employé à ces dates.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Créer le planning
            $planning = Planning::create([
                'employe_id' => $request->employe_id,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'titre' => $request->titre,
                'description' => $request->description,
                'actif' => true,
            ]);
            
            // Créer les détails du planning
            for ($jour = 1; $jour <= 7; $jour++) {
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
                    
                    PlanningDetail::create($detail);
                }
            }
            
            DB::commit();
            
            return redirect()->route('plannings.show', $planning)
                ->with('success', 'Planning créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue : ' . $e->getMessage()]);
        }
    }

    /**
     * Afficher les détails d'un planning
     */
    public function show(Planning $planning)
    {
        $planning->load(['employe', 'details']);
        return view('plannings.show', compact('planning'));
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(Planning $planning)
    {
        $planning->load(['employe', 'details']);
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        
        // Organiser les détails par jour
        $detailsParJour = [];
        foreach (range(1, 7) as $jour) {
            $detailsParJour[$jour] = $planning->details->firstWhere('jour', $jour);
        }
        
        return view('plannings.edit', compact('planning', 'employes', 'detailsParJour'));
    }

    /**
     * Récupérer les données des plannings pour le calendrier
     */
    public function getCalendarData(Request $request)
    {
        // Récupérer les paramètres de la requête
        $start = $request->input('start');
        $end = $request->input('end');
        $departement = $request->input('departement');
        $employeId = $request->input('employe');

        // Requête de base
        $query = Planning::with(['employe', 'employe.poste', 'details']);
        
        // Filtrer par période
        if ($start && $end) {
            $query->where(function($q) use ($start, $end) {
                $q->whereBetween('date_debut', [$start, $end])
                  ->orWhereBetween('date_fin', [$start, $end])
                  ->orWhere(function($q2) use ($start, $end) {
                      $q2->where('date_debut', '<=', $start)
                         ->where('date_fin', '>=', $end);
                  });
            });
        }
        
        // Filtrer par département
        if ($departement) {
            $query->whereHas('employe.poste', function($q) use ($departement) {
                $q->where('departement', $departement);
            });
        }
        
        // Filtrer par employé
        if ($employeId) {
            $query->where('employe_id', $employeId);
        }
        
        // Récupérer les plannings
        $plannings = $query->get();
        
        // Formater les données pour FullCalendar
        $events = [];
        
        foreach ($plannings as $planning) {
            // Couleur en fonction du type (planning, congé, etc.)
            $backgroundColor = '#3788d8'; // Bleu par défaut
            $borderColor = '#3788d8';
            $className = 'planning-event';
            
            // Données communes à tous les événements
            $baseEvent = [
                'id' => $planning->id,
                'title' => $planning->employe->nom_complet . ' - ' . $planning->titre,
                'start' => $planning->date_debut->format('Y-m-d'),
                'end' => $planning->date_fin->format('Y-m-d'),
                'backgroundColor' => $backgroundColor,
                'borderColor' => $borderColor,
                'className' => $className,
                'extendedProps' => [
                    'description' => $planning->description,
                    'employeId' => $planning->employe_id,
                    'employe' => $planning->employe->nom_complet,
                    'poste' => $planning->employe->poste->nom,
                    'departement' => $planning->employe->poste->departement,
                ]
            ];
            
            // Ajouter l'événement principal (planning sur toute la période)
            $events[] = $baseEvent;
        }
        
        return response()->json($events);
    }
    
    /**
     * Récupérer les détails d'un planning spécifique
     */
    public function getPlanningData(Planning $planning)
    {
        $planning->load(['employe', 'employe.poste', 'details']);
        
        // Tableau pour les jours de la semaine
        $joursLabels = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];
        
        // Formater les détails
        $details = [];
        foreach ($planning->details as $detail) {
            $type = 'horaire';
            if ($detail->jour_repos) {
                $type = 'repos';
            } elseif ($detail->jour_entier) {
                $type = 'jour_entier';
            }
            
            $details[] = [
                'jour' => $joursLabels[$detail->jour],
                'type' => $type,
                'heure_debut' => $detail->heure_debut ? substr($detail->heure_debut, 0, 5) : null,
                'heure_fin' => $detail->heure_fin ? substr($detail->heure_fin, 0, 5) : null,
                'note' => $detail->note
            ];
        }
        
        // Formater la réponse
        $response = [
            'id' => $planning->id,
            'titre' => $planning->titre,
            'date_debut' => $planning->date_debut->format('d/m/Y'),
            'date_fin' => $planning->date_fin->format('d/m/Y'),
            'description' => $planning->description,
            'employe' => $planning->employe->nom_complet,
            'poste' => $planning->employe->poste->nom,
            'departement' => $planning->employe->poste->departement,
            'details' => $details
        ];
        
        return response()->json($response);
    }
}