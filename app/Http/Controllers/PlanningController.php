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
        // Supprimer les détails du planning puis le planning
        $planning->details()->delete();
        $planning->delete();
        
        return redirect()->route('plannings.index')
            ->with('success', 'Planning supprimé avec succès.');
    }
    
    /**
     * Afficher le calendrier des plannings
     */
    public function calendrier(Request $request)
    {
        // Récupérer le mois et l'année demandés, ou utiliser le mois courant
        $mois = $request->input('mois', date('m'));
        $annee = $request->input('annee', date('Y'));
        
        // Date de début et de fin du mois
        $dateDebut = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();
        
        // Récupérer les plannings de ce mois
        $plannings = Planning::with(['employe', 'details'])
            ->where(function($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                    ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                    ->orWhere(function($q) use ($dateDebut, $dateFin) {
                        $q->where('date_debut', '<=', $dateDebut)
                          ->where('date_fin', '>=', $dateFin);
                    });
            })
            ->orderBy('date_debut')
            ->get();
        
        // Liste des employés pour le filtre
        $employes = Employe::orderBy('nom')->get();
        
        return view('plannings.calendrier', compact('plannings', 'employes', 'mois', 'annee', 'dateDebut', 'dateFin'));
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
     * Afficher le formulaire de création
     */
    public function create(Request $request)
    {
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->get();
        $employe_id = $request->input('employe_id');
        $employe = $employe_id ? Employe::find($employe_id) : null;
        
        return view('plannings.create', compact('employes', 'employe_id', 'employe'));
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
}