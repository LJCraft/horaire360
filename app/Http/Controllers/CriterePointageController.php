<?php

namespace App\Http\Controllers;

use App\Models\CriterePointage;
use App\Models\Employe;
use App\Models\Departement;
use App\Models\Planning;
use App\Models\PlanningDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CriterePointageController extends Controller
{
    /**
     * Afficher la page de configuration des critères de pointage
     */
    public function index()
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }

        $employes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
            
        $departements = Departement::orderBy('nom')->get();
        
        $criteres = CriterePointage::with(['employe', 'departement', 'createur'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('criteres-pointage.index', compact('employes', 'departements', 'criteres'));
    }
    
    /**
     * Afficher le formulaire de configuration des critères
     */
    public function create()
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        $employes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
            
        $departements = Departement::orderBy('nom')->get();
        
        return view('criteres-pointage.create', compact('employes', 'departements'));
    }
    
    /**
     * Récupérer le planning pour la période sélectionnée
     */
    public function getPlanning(Request $request)
    {
        $request->validate([
            'niveau' => 'required|in:individuel,departemental',
            'employe_id' => 'required_if:niveau,individuel|exists:employes,id',
            'departement_id' => 'required_if:niveau,departemental|exists:departements,id',
            'periode' => 'required|in:jour,semaine,mois',
            'date_debut' => 'required|date',
        ]);
        
        $niveau = $request->niveau;
        $periode = $request->periode;
        $dateDebut = Carbon::parse($request->date_debut);
        
        // Déterminer la date de fin en fonction de la période
        $dateFin = null;
        if ($periode === 'jour') {
            $dateFin = $dateDebut->copy();
        } elseif ($periode === 'semaine') {
            $dateFin = $dateDebut->copy()->addDays(6); // 7 jours au total
        } elseif ($periode === 'mois') {
            $dateFin = $dateDebut->copy()->addMonth()->subDay(); // Jusqu'à la fin du mois
        }
        
        $plannings = [];
        
        if ($niveau === 'individuel') {
            $employe = Employe::findOrFail($request->employe_id);
            $plannings = $this->getPlanningEmploye($employe, $dateDebut, $dateFin);
        } elseif ($niveau === 'departemental') {
            $departement = Departement::findOrFail($request->departement_id);
            $employes = $departement->employes()->where('statut', 'actif')->get();
            
            foreach ($employes as $employe) {
                $planningEmploye = $this->getPlanningEmploye($employe, $dateDebut, $dateFin);
                if (!empty($planningEmploye)) {
                    $plannings[$employe->id] = [
                        'employe' => $employe,
                        'planning' => $planningEmploye
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'niveau' => $niveau,
            'periode' => $periode,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'plannings' => $plannings
        ]);
    }
    
    /**
     * Récupérer le planning d'un employé pour une période donnée
     */
    private function getPlanningEmploye(Employe $employe, Carbon $dateDebut, Carbon $dateFin)
    {
        $planningData = [];
        $currentDate = $dateDebut->copy();
        
        while ($currentDate->lte($dateFin)) {
            $jourSemaine = $currentDate->dayOfWeekIso; // 1 (lundi) à 7 (dimanche)
            
            // Rechercher le planning actif pour cette date
            $planning = Planning::where('employe_id', $employe->id)
                ->where('date_debut', '<=', $currentDate->format('Y-m-d'))
                ->where('date_fin', '>=', $currentDate->format('Y-m-d'))
                ->first();
                
            if ($planning) {
                // Récupérer le détail du planning pour ce jour
                $planningDetail = $planning->details()
                    ->where('jour', $jourSemaine)
                    ->first();
                    
                if ($planningDetail && !$planningDetail->jour_repos) {
                    $planningData[$currentDate->format('Y-m-d')] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'jour_semaine' => $jourSemaine,
                        'nom_jour' => $planningDetail->nom_jour,
                        'heure_debut' => $planningDetail->heure_debut,
                        'heure_fin' => $planningDetail->heure_fin,
                        'jour_entier' => $planningDetail->jour_entier,
                    ];
                }
            }
            
            $currentDate->addDay();
        }
        
        return $planningData;
    }
    
    /**
     * Enregistrer les critères de pointage
     */
    public function store(Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        $request->validate([
            'niveau' => 'required|in:individuel,departemental',
            'employe_id' => 'required_if:niveau,individuel|exists:employes,id',
            'departement_id' => 'required_if:niveau,departemental|exists:departements,id',
            'periode' => 'required|in:jour,semaine,mois',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'nombre_pointages' => 'required|in:1,2',
            'tolerance_avant' => 'required|integer|min:0|max:60',
            'tolerance_apres' => 'required|integer|min:0|max:60',
            'duree_pause' => 'required|integer|min:0|max:240',
            'source_pointage' => 'required|in:biometrique,manuel,tous',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Désactiver les critères existants qui se chevauchent
            if ($request->niveau === 'individuel') {
                CriterePointage::where('niveau', 'individuel')
                    ->where('employe_id', $request->employe_id)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                            ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                            ->orWhere(function ($q) use ($request) {
                                $q->where('date_debut', '<=', $request->date_debut)
                                  ->where('date_fin', '>=', $request->date_fin);
                            });
                    })
                    ->update(['actif' => false]);
            } elseif ($request->niveau === 'departemental') {
                CriterePointage::where('niveau', 'departemental')
                    ->where('departement_id', $request->departement_id)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                            ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                            ->orWhere(function ($q) use ($request) {
                                $q->where('date_debut', '<=', $request->date_debut)
                                  ->where('date_fin', '>=', $request->date_fin);
                            });
                    })
                    ->update(['actif' => false]);
            }
            
            // Créer le nouveau critère
            $critere = new CriterePointage();
            $critere->niveau = $request->niveau;
            
            if ($request->niveau === 'individuel') {
                $critere->employe_id = $request->employe_id;
            } elseif ($request->niveau === 'departemental') {
                $critere->departement_id = $request->departement_id;
            }
            
            $critere->date_debut = $request->date_debut;
            $critere->date_fin = $request->date_fin;
            $critere->periode = $request->periode;
            $critere->nombre_pointages = $request->nombre_pointages;
            $critere->tolerance_avant = $request->tolerance_avant;
            $critere->tolerance_apres = $request->tolerance_apres;
            $critere->duree_pause = $request->duree_pause;
            $critere->source_pointage = $request->source_pointage;
            $critere->actif = true;
            $critere->created_by = Auth::id();
            $critere->save();
            
            DB::commit();
            
            return redirect()->route('criteres-pointage.index')
                ->with('success', 'Les critères de pointage ont été configurés avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher les détails d'un critère de pointage
     */
    public function show(CriterePointage $criterePointage)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        return view('criteres-pointage.show', compact('criterePointage'));
    }
    
    /**
     * Afficher le formulaire de modification d'un critère
     */
    public function edit(CriterePointage $criterePointage)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        $employes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
            
        $departements = Departement::orderBy('nom')->get();
        
        return view('criteres-pointage.edit', compact('criterePointage', 'employes', 'departements'));
    }
    
    /**
     * Mettre à jour un critère de pointage
     */
    public function update(Request $request, CriterePointage $criterePointage)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        $request->validate([
            'nombre_pointages' => 'required|in:1,2',
            'tolerance_avant' => 'required|integer|min:0|max:60',
            'tolerance_apres' => 'required|integer|min:0|max:60',
            'duree_pause' => 'required|integer|min:0|max:240',
            'source_pointage' => 'required|in:biometrique,manuel,tous',
            'actif' => 'boolean',
        ]);
        
        $criterePointage->nombre_pointages = $request->nombre_pointages;
        $criterePointage->tolerance_avant = $request->tolerance_avant;
        $criterePointage->tolerance_apres = $request->tolerance_apres;
        $criterePointage->duree_pause = $request->duree_pause;
        $criterePointage->source_pointage = $request->source_pointage;
        $criterePointage->actif = $request->has('actif');
        $criterePointage->save();
        
        return redirect()->route('criteres-pointage.index')
            ->with('success', 'Le critère de pointage a été mis à jour avec succès.');
    }
    
    /**
     * Supprimer un critère de pointage
     */
    public function destroy(CriterePointage $criterePointage)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        $criterePointage->delete();
        
        return redirect()->route('criteres-pointage.index')
            ->with('success', 'Le critère de pointage a été supprimé avec succès.');
    }
}
