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
        } else {
            // Si aucun département n'est sélectionné, récupérer tous les employés actifs
            $employes = Employe::where('statut', 'actif')
                ->orderBy('nom')
                ->get();
        }
        
        return view('plannings.departement.calendrier', compact(
            'departements', 
            'departementSelectionne',
            'postes',
            'employes'
        ));
    }
} 