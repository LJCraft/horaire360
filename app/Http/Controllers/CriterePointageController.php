<?php

namespace App\Http\Controllers;

use App\Models\CriterePointage;
use App\Models\Employe;
use App\Models\Departement;
use App\Models\Poste;
use App\Models\Grade;
use App\Models\Planning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CriterePointageController extends Controller
{
    /**
     * Afficher la page de configuration des critères de pointage
     */
    public function index(Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les filtres depuis la requête
        $departementId = $request->input('departement_id');
        $niveau = $request->input('niveau');
        $statut = $request->input('statut');
        $periode = $request->input('periode', 'mois');
        
        // Récupérer les employés actifs
        $employesQuery = Employe::where('statut', 'actif');
        
        // Filtrer par département si spécifié
        if ($departementId) {
            $employesQuery->whereHas('poste', function($q) use ($departementId) {
                $q->where('departement', $departementId);
            });
        }
        
        $employes = $employesQuery->with(['poste', 'grade'])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
            
        // Récupérer les départements depuis les postes (pour avoir departement comme string)
        $departements = DB::table('postes')
            ->select('departement')
            ->distinct()
            ->whereNotNull('departement')
            ->orderBy('departement')
            ->get();
        
        // Récupérer les critères de pointage avec filtrage amélioré
        $criteresQuery = CriterePointage::with(['employe.poste', 'departement', 'createur']);
        
        // Filtrer par département si spécifié
        if ($departementId) {
            $criteresQuery->where(function($query) use ($departementId) {
                $query->where('niveau', 'departemental')
                      ->where('departement_id', $departementId)
                      ->orWhereHas('employe', function($q) use ($departementId) {
                          $q->whereHas('poste', function($p) use ($departementId) {
                              $p->where('departement', $departementId);
                          });
                      });
            });
        }
        
        // Filtrer par niveau si spécifié
        if ($niveau) {
            $criteresQuery->where('niveau', $niveau);
        }
        
        // Filtrer par statut si spécifié
        if ($statut === 'actif') {
            $criteresQuery->where('actif', true);
        } elseif ($statut === 'inactif') {
            $criteresQuery->where('actif', false);
        }
        
        $criteres = $criteresQuery->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Déterminer quels employés ont déjà des critères individuels
        $employesAvecCriteres = CriterePointage::where('niveau', 'individuel')
            ->where('actif', true)
            ->pluck('employe_id')
            ->toArray();
            
        return view('criteres-pointage.index', compact(
            'employes', 
            'departements', 
            'criteres', 
            'departementId', 
            'niveau',
            'statut',
            'periode', 
            'employesAvecCriteres'
        ));
    }
    
    /**
     * Redirection vers la page d'index avec le modal de création
     */
    public function create()
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        // Rediriger vers la page d'index
        return redirect()->route('criteres-pointage.index');
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
        
        // Définir les règles de validation
        $rules = [
            'niveau' => 'required|in:individuel,departemental',
            'periode' => 'required|in:jour,semaine,mois',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'nombre_pointages' => 'required|in:1,2',
            'tolerance_avant' => 'required|integer|min:0|max:60',
            'tolerance_apres' => 'required|integer|min:0|max:60',
            'duree_pause' => 'required|integer|min:0|max:240',
            'source_pointage' => 'required|in:biometrique,manuel,tous',
            'priorite' => 'integer|min:1|max:3',
        ];
        
        // Ajouter des règles spécifiques en fonction du niveau
        if ($request->niveau === 'individuel') {
            $rules['employe_id'] = 'required|exists:employes,id';
        } elseif ($request->niveau === 'departemental') {
            $rules['departement_id'] = 'required|string';
            // Rendre les employés sélectionnés obligatoires uniquement si on applique à une sélection
            if ($request->has('appliquer_selection') && $request->appliquer_selection) {
                $rules['employes_selectionnes'] = 'required|array|min:1';
                $rules['employes_selectionnes.*'] = 'exists:employes,id';
            }
        }
        
        // Ajouter des règles pour les heures supplémentaires
        if ($request->has('calcul_heures_sup')) {
            $rules['seuil_heures_sup'] = 'required|integer|min:0|max:240';
        }
        
        // Valider les données
        $validated = $request->validate($rules);
        
        // Vérification supplémentaire pour les départements
        if ($request->niveau === 'departemental') {
            $departementExists = DB::table('postes')
                ->where('departement', $request->departement_id)
                ->exists();
                
            if (!$departementExists) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['departement_id' => 'Le département sélectionné n\'existe pas.']);
            }
        }
        
        DB::beginTransaction();
        
        try {
            // Désactiver les critères existants qui se chevauchent
            if ($request->niveau === 'individuel') {
                // Rechercher les critères individuels existants qui se chevauchent
                $criteresChevauchants = CriterePointage::where('niveau', 'individuel')
                    ->where('employe_id', $request->employe_id)
                    ->where('actif', true)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                            ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                            ->orWhere(function ($q) use ($request) {
                                $q->where('date_debut', '<=', $request->date_debut)
                                  ->where('date_fin', '>=', $request->date_fin);
                            });
                    })
                    ->get();
                    
                // Désactiver les critères chevauchants
                foreach ($criteresChevauchants as $critereChevauchant) {
                    $critereChevauchant->actif = false;
                    $critereChevauchant->save();
                }
                    
                // Créer le nouveau critère individuel
                $critere = new CriterePointage();
                $critere->niveau = 'individuel';
                $critere->employe_id = $request->employe_id;
                $critere->date_debut = $request->date_debut;
                $critere->date_fin = $request->date_fin;
                $critere->periode = $request->periode;
                $critere->nombre_pointages = $request->nombre_pointages;
                $critere->tolerance_avant = $request->tolerance_avant;
                $critere->tolerance_apres = $request->tolerance_apres;
                $critere->duree_pause = $request->duree_pause;
                $critere->source_pointage = $request->source_pointage;
                $critere->calcul_heures_sup = $request->has('calcul_heures_sup');
                $critere->seuil_heures_sup = $request->input('seuil_heures_sup', 0);
                $critere->priorite = $request->input('priorite', 2);
                $critere->actif = true;
                $critere->created_by = Auth::id();
                $critere->save();
                
                // Récupérer l'employé pour les informations de synchronisation
                $employe = Employe::with('poste')->find($request->employe_id);
                $message = "Critère individuel créé pour l'employé {$employe->nom} {$employe->prenom}";
                
            } elseif ($request->niveau === 'departemental') {
                // Désactiver les critères départementaux existants qui se chevauchent
                $criteresChevauchants = CriterePointage::where('niveau', 'departemental')
                    ->where('departement_id', $request->departement_id)
                    ->where('actif', true)
                    ->where(function ($query) use ($request) {
                        $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                            ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                            ->orWhere(function ($q) use ($request) {
                                $q->where('date_debut', '<=', $request->date_debut)
                                  ->where('date_fin', '>=', $request->date_fin);
                            });
                    })
                    ->get();
                
                // Désactiver les critères chevauchants
                foreach ($criteresChevauchants as $critereChevauchant) {
                    $critereChevauchant->actif = false;
                    $critereChevauchant->save();
                }
                
                // Créer le critère départemental
                $critere = new CriterePointage();
                $critere->niveau = 'departemental';
                $critere->departement_id = $request->departement_id;
                $critere->date_debut = $request->date_debut;
                $critere->date_fin = $request->date_fin;
                $critere->periode = $request->periode;
                $critere->nombre_pointages = $request->nombre_pointages;
                $critere->tolerance_avant = $request->tolerance_avant;
                $critere->tolerance_apres = $request->tolerance_apres;
                $critere->duree_pause = $request->duree_pause;
                $critere->source_pointage = $request->source_pointage;
                $critere->calcul_heures_sup = $request->has('calcul_heures_sup');
                $critere->seuil_heures_sup = $request->input('seuil_heures_sup', 0);
                $critere->priorite = $request->input('priorite', 2);
                $critere->actif = true;
                $critere->created_by = Auth::id();
                $critere->save();
                
                // Récupérer le département pour les informations de synchronisation
                $departementNom = $request->departement_id;
                $message = "Critère départemental créé pour le département {$departementNom}";
                
                // Déterminer quels employés seront affectés
                if ($request->has('appliquer_tous') && $request->appliquer_tous) {
                    // Récupérer tous les employés du département
                    $employes = Employe::whereHas('poste', function($q) use ($request) {
                        $q->where('departement', $request->departement_id);
                    })->where('statut', 'actif')->get();
                    
                    $message .= " (appliqué à tous les employés du département)";
                } elseif ($request->has('employes_selectionnes') && is_array($request->employes_selectionnes)) {
                    // Récupérer uniquement les employés sélectionnés
                    $employes = Employe::whereIn('id', $request->employes_selectionnes)
                        ->where('statut', 'actif')
                        ->get();
                        
                    $message .= " (appliqué à {$employes->count()} employés sélectionnés)";
                } else {
                    // Aucun employé sélectionné, critère départemental uniquement
                    $employes = collect([]);
                }
                
                // Créer des critères individuels pour les employés concernés
                foreach ($employes as $employe) {
                    // Vérifier si l'employé n'a pas déjà un critère individuel actif
                    $critereExistant = CriterePointage::where('niveau', 'individuel')
                        ->where('employe_id', $employe->id)
                        ->where('actif', true)
                        ->where(function ($query) use ($request) {
                            $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                                ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                                ->orWhere(function ($q) use ($request) {
                                    $q->where('date_debut', '<=', $request->date_debut)
                                      ->where('date_fin', '>=', $request->date_fin);
                                });
                        })
                        ->first();
                        
                    if ($critereExistant) {
                        // Désactiver le critère existant
                        $critereExistant->actif = false;
                        $critereExistant->save();
                    }
                    
                    // Créer un critère individuel basé sur le critère départemental
                    $critereIndividuel = new CriterePointage();
                    $critereIndividuel->niveau = 'individuel';
                    $critereIndividuel->employe_id = $employe->id;
                    $critereIndividuel->departement_id = $request->departement_id; // Lien avec le département pour la traçabilité
                    $critereIndividuel->date_debut = $request->date_debut;
                    $critereIndividuel->date_fin = $request->date_fin;
                    $critereIndividuel->periode = $request->periode;
                    $critereIndividuel->nombre_pointages = $request->nombre_pointages;
                    $critereIndividuel->tolerance_avant = $request->tolerance_avant;
                    $critereIndividuel->tolerance_apres = $request->tolerance_apres;
                    $critereIndividuel->duree_pause = $request->duree_pause;
                    $critereIndividuel->source_pointage = $request->source_pointage;
                    $critereIndividuel->calcul_heures_sup = $request->has('calcul_heures_sup');
                    $critereIndividuel->seuil_heures_sup = $request->input('seuil_heures_sup', 0);
                    $critereIndividuel->priorite = $request->input('priorite', 2);
                    $critereIndividuel->actif = true;
                    $critereIndividuel->parent_critere_id = $critere->id; // Lien avec le critère parent
                    $critereIndividuel->created_by = Auth::id();
                    $critereIndividuel->save();
                }
            }
            
            DB::commit();
            
            // Synchroniser les rapports avec les nouveaux critères
            $this->synchroniserRapports();
            
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Les critères de pointage ont été configurés avec succès. ' . $message]);
            }
            
            // Rediriger vers la liste appropriée selon le niveau du critère
            if ($request->niveau === 'departemental') {
                return redirect()->route('criteres-pointage.departementaux')
                    ->with('success', 'Les critères de pointage ont été configurés avec succès. ' . $message);
            } else {
                return redirect()->route('criteres-pointage.index')
                    ->with('success', 'Les critères de pointage ont été configurés avec succès. ' . $message);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher les détails d'un critère de pointage
     */
    public function show($id)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        // Récupérer le critère de pointage avec ses relations
        $criterePointage = CriterePointage::with(['employe', 'departement', 'createur'])->findOrFail($id);
        
        return view('criteres-pointage.show', compact('criterePointage'));
    }
    
    /**
     * Afficher le formulaire d'édition d'un critère de pointage
     */
    public function edit($id)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        // Récupérer le critère de pointage
        $criterePointage = CriterePointage::findOrFail($id);
        
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
    public function update(Request $request, $id)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }
        
        // Récupérer le critère de pointage
        $criterePointage = CriterePointage::findOrFail($id);
        
        $request->validate([
            'nombre_pointages' => 'required|in:1,2',
            'tolerance_avant' => 'required|integer|min:0|max:60',
            'tolerance_apres' => 'required|integer|min:0|max:60',
            'duree_pause' => 'required|integer|min:0|max:240',
            'source_pointage' => 'required|in:biometrique,manuel,tous',
            'calcul_heures_sup' => 'boolean',
            'seuil_heures_sup' => 'required_if:calcul_heures_sup,1|integer|min:0|max:240',
            'priorite' => 'integer|min:1|max:3',
            'actif' => 'boolean',
        ]);
        
        $criterePointage->nombre_pointages = $request->nombre_pointages;
        $criterePointage->tolerance_avant = $request->tolerance_avant;
        $criterePointage->tolerance_apres = $request->tolerance_apres;
        $criterePointage->duree_pause = $request->duree_pause;
        $criterePointage->source_pointage = $request->source_pointage;
        $criterePointage->calcul_heures_sup = $request->has('calcul_heures_sup');
        $criterePointage->seuil_heures_sup = $request->input('seuil_heures_sup', 0);
        $criterePointage->priorite = $request->input('priorite', 2);
        $criterePointage->actif = $request->has('actif');
        $criterePointage->save();
        
        // Si c'est un critère départemental et que l'option est activée, mettre à jour les critères individuels associés
        if ($criterePointage->niveau === 'departemental' && $request->has('appliquer_aux_individuels')) {
            $departementId = $criterePointage->departement_id;
            $employesIds = Employe::whereHas('poste', function($q) use ($departementId) {
                $q->where('departement', $departementId);
            })->pluck('id')->toArray();
            
            // Mettre à jour les critères individuels des employés du département
            CriterePointage::where('niveau', 'individuel')
                ->whereIn('employe_id', $employesIds)
                ->where('actif', true)
                ->whereBetween('date_debut', [$criterePointage->date_debut, $criterePointage->date_fin])
                ->orWhereBetween('date_fin', [$criterePointage->date_debut, $criterePointage->date_fin])
                ->update([
                    'nombre_pointages' => $criterePointage->nombre_pointages,
                    'tolerance_avant' => $criterePointage->tolerance_avant,
                    'tolerance_apres' => $criterePointage->tolerance_apres,
                    'duree_pause' => $criterePointage->duree_pause,
                    'source_pointage' => $criterePointage->source_pointage,
                    'calcul_heures_sup' => $criterePointage->calcul_heures_sup,
                    'seuil_heures_sup' => $criterePointage->seuil_heures_sup,
                    'priorite' => $criterePointage->priorite
                ]);
        }
        
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
    
    /**
     * Récupérer les employés d'un département avec leurs critères de pointage
     * Optimisé selon les nouvelles exigences
     */
    public function getEmployesDepartement(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        
        $request->validate([
            'departement_id' => 'required|string',
            'poste_id' => 'nullable|exists:postes,id',
            'grade_id' => 'nullable|exists:grades,id',
        ]);
        
        $departementId = $request->departement_id;
        $posteId = $request->poste_id;
        $gradeId = $request->grade_id;
        
        try {
            // 1. Récupérer les postes du département
            $postes = Poste::where('departement', $departementId)
                ->when($posteId, function ($query) use ($posteId) {
                    $query->where('id', $posteId);
                })
                ->orderBy('nom')
                ->get();

            // 2. Récupérer tous les employés actifs du département avec filtres
            $employes = Employe::where('statut', 'actif')
                ->whereHas('poste', function ($query) use ($departementId) {
                    $query->where('departement', $departementId);
                })
                ->with(['poste', 'grade'])
                ->when($posteId, function ($query) use ($posteId) {
                    $query->where('poste_id', $posteId);
                })
                ->when($gradeId, function ($query) use ($gradeId) {
                    $query->where('grade_id', $gradeId);
                })
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();

            // 3. Récupérer les critères individuels actifs pour ces employés
            $criteresIndividuels = CriterePointage::where('niveau', 'individuel')
                ->where('actif', true)
                ->whereIn('employe_id', $employes->pluck('id'))
                ->get()
                ->keyBy('employe_id');

            // 4. Vérifier s'il existe un critère départemental actif
            $critereDepartemental = CriterePointage::where('niveau', 'departemental')
                ->where('departement_id', $departementId)
                ->where('actif', true)
                ->first();

            // 5. Préparer les données des employés avec état des critères
            $employesData = $employes->map(function ($employe) use ($criteresIndividuels, $critereDepartemental) {
                $critereIndividuel = $criteresIndividuels->get($employe->id);
                $aCritereIndividuel = !is_null($critereIndividuel);

                // Déterminer le type de critère et ses détails
                $typeCritere = null;
                $nombrePointages = null;
                $detailsCritere = null;

                if ($aCritereIndividuel) {
                    $typeCritere = 'individuel';
                    $nombrePointages = $critereIndividuel->nombre_pointages;
                    $detailsCritere = [
                        'id' => $critereIndividuel->id,
                        'tolerance_avant' => $critereIndividuel->tolerance_avant,
                        'tolerance_apres' => $critereIndividuel->tolerance_apres,
                        'source_pointage' => $critereIndividuel->source_pointage,
                        'date_debut' => $critereIndividuel->date_debut->format('d/m/Y'),
                        'date_fin' => $critereIndividuel->date_fin->format('d/m/Y')
                    ];
                } elseif ($critereDepartemental) {
                    $typeCritere = 'departemental';
                    $nombrePointages = $critereDepartemental->nombre_pointages;
                    $detailsCritere = [
                        'id' => $critereDepartemental->id,
                        'tolerance_avant' => $critereDepartemental->tolerance_avant,
                        'tolerance_apres' => $critereDepartemental->tolerance_apres,
                        'source_pointage' => $critereDepartemental->source_pointage
                    ];
                }

                return [
                    'id' => $employe->id,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                    'photo' => $employe->photo ?? '/images/default-avatar.png',
                    'poste' => $employe->poste->nom ?? 'Non assigné',
                    'poste_id' => $employe->poste_id,
                    'grade' => $employe->grade->nom ?? 'Non assigné',
                    'grade_id' => $employe->grade_id,
                    'a_critere_individuel' => $aCritereIndividuel,
                    'a_critere' => $aCritereIndividuel || !is_null($critereDepartemental),
                    'type_critere' => $typeCritere,
                    'nombre_pointages' => $nombrePointages,
                    'details_critere' => $detailsCritere,
                    'peut_recevoir_critere_departemental' => !$aCritereIndividuel
                ];
            });

            // 6. Statistiques
            $totalEmployes = $employes->count();
            $employesAvecCritereIndividuel = $employesData->where('a_critere_individuel', true)->count();
            $employesSansCritere = $employesData->where('a_critere', false)->count();

            // 7. Préparer les données des postes avec nombres d'employés
            $postesData = $postes->map(function($poste) use ($employes) {
                $nombreEmployes = $employes->where('poste_id', $poste->id)->count();
                return [
                    'id' => $poste->id,
                    'nom' => $poste->nom,
                    'poste' => $poste->nom,
                    'nombre_employes' => $nombreEmployes
                ];
            });

            $response = [
                'success' => true,
                'departement' => $departementId,
                'statistiques' => [
                    'total_employes' => $totalEmployes,
                    'avec_critere_individuel' => $employesAvecCritereIndividuel,
                    'sans_critere' => $employesSansCritere,
                    'eligible_departemental' => $totalEmployes - $employesAvecCritereIndividuel
                ],
                'postes' => $postesData,
                'employes' => $employesData->map(function($employe) {
                    return [
                        'user_id' => $employe['id'],
                        'nom' => $employe['nom'] . ' ' . $employe['prenom'],
                        'email' => '', // À ajouter si nécessaire
                        'photo' => $employe['photo'],
                        'poste' => $employe['poste'],
                        'poste_id' => $employe['poste_id'],
                        'grade' => $employe['grade'],
                        'grade_id' => $employe['grade_id'],
                        'a_critere_individuel' => $employe['a_critere_individuel'],
                        'a_critere_departemental' => $employe['type_critere'] === 'departemental',
                        'nombre_pointages' => $employe['nombre_pointages'] ?? 0,
                        'type_critere' => $employe['type_critere'],
                        'details_critere' => $employe['details_critere']
                    ];
                }),
                'criteres_departementaux' => $critereDepartemental ? [[
                    'id' => $critereDepartemental->id,
                    'periode' => 'mois', // Par défaut
                    'nombre_pointages' => $critereDepartemental->nombre_pointages,
                    'tolerance_avant' => $critereDepartemental->tolerance_avant,
                    'tolerance_apres' => $critereDepartemental->tolerance_apres,
                    'duree_pause' => $critereDepartemental->duree_pause ?? 0,
                    'source_pointage' => $critereDepartemental->source_pointage,
                    'date_debut' => $critereDepartemental->date_debut->format('Y-m-d'),
                    'date_fin' => $critereDepartemental->date_fin->format('Y-m-d'),
                    'employes_concernes' => $totalEmployes - $employesAvecCritereIndividuel
                ]] : []
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du chargement des données : ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupérer les postes d'un département
     */
    public function getPostesDepartement(Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        
        $request->validate([
            'departement_id' => 'required|exists:departements,departement',
        ]);
        
        $departementId = $request->departement_id;
        
        // Récupérer les postes du département
        $postes = Poste::where('departement', $departementId)
            ->orderBy('nom')
            ->get()
            ->map(function($poste) {
                return [
                    'id' => $poste->id,
                    'nom' => $poste->nom
                ];
            });
        
        return response()->json([
            'postes' => $postes
        ]);
    }
    
    /**
     * Récupérer les grades disponibles pour un poste
     */
    public function getGradesPoste(Request $request)
    {
        $request->validate([
            'poste_id' => 'required|exists:postes,id',
        ]);
        
        $posteId = $request->poste_id;
        
        // Récupérer le poste
        $poste = Poste::find($posteId);
        
        // Récupérer les grades disponibles pour ce poste
        $gradesDisponibles = [];
        
        if ($poste && $poste->grades_disponibles) {
            // Si le champ grades_disponibles est un JSON, le décoder
            $gradesIds = is_array($poste->grades_disponibles) 
                ? $poste->grades_disponibles 
                : json_decode($poste->grades_disponibles, true);
            
            if (is_array($gradesIds) && count($gradesIds) > 0) {
                $gradesDisponibles = Grade::whereIn('id', $gradesIds)
                    ->orderBy('nom')
                    ->get()
                    ->map(function($grade) {
                        return [
                            'id' => $grade->id,
                            'nom' => $grade->nom
                        ];
                    });
            }
        }
        
        return response()->json([
            'grades' => $gradesDisponibles
        ]);
    }
    
    /**
     * Récupérer l'ID du critère de pointage d'un employé
     */
    public function getCritereEmploye(Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
        ]);
        
        $employeId = $request->employe_id;
        
        // Récupérer le critère individuel actif de l'employé
        $critere = CriterePointage::where('niveau', 'individuel')
            ->where('employe_id', $employeId)
            ->where('actif', true)
            ->first();
        
        if ($critere) {
            return response()->json([
                'success' => true,
                'critere_id' => $critere->id
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Aucun critère trouvé pour cet employé'
            ]);
        }
    }
    
    /**
     * Affichage par département avec filtres (postes et employés)
     */
    public function departement(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }

        $departementId = $request->query('departement_id');

        // Récupérer les départements uniques depuis les postes
        $departements = DB::table('postes')
            ->select('departement as nom', 'departement as id')
            ->distinct()
            ->whereNotNull('departement')
            ->orderBy('departement')
            ->get()
            ->map(function($dept) {
                return (object)['id' => $dept->id, 'nom' => $dept->nom];
            });

        $postes = collect();
        $employesParPoste = collect();
        $critereParEmploye = collect();

        if ($departementId) {
            // Postes du département sélectionné
            $postes = Poste::where('departement', $departementId)->orderBy('nom')->get();

            // Récupérer tous les employés liés à ces postes (actifs)
            $employes = Employe::where('statut', 'actif')
                ->whereHas('poste', function ($q) use ($departementId) {
                    $q->where('departement', $departementId);
                })
                ->with('poste')
                ->get();

            // Grouper les employés par poste
            $employesParPoste = $employes->groupBy('poste.id');

            // Récupérer leurs critères actifs (individuels ou départementaux)
            $critereParEmploye = CriterePointage::where(function ($q) use ($employes, $departementId) {
                    // Critères individuels pour les employés de ce département
                    $q->where('niveau', 'individuel')
                      ->whereIn('employe_id', $employes->pluck('id'))
                      ->where('actif', true);
                })
                ->orWhere(function ($q) use ($departementId) {
                    // Critères départementaux pour ce département
                    $q->where('niveau', 'departemental')
                      ->where('departement_id', $departementId)
                      ->where('actif', true);
                })
                ->get()
                ->keyBy(function($critere) {
                    return $critere->niveau === 'individuel' ? $critere->employe_id : 'dept_' . $critere->departement_id;
                });
        }

        return view('criteres-pointage.departement', compact(
            'departements',
            'departementId',
            'postes',
            'employesParPoste',
            'critereParEmploye'
        ));
    }
    
    /**
     * Synchroniser les critères avec les rapports
     * Cette méthode assure que les critères de pointage sont correctement appliqués dans tous les rapports
     */
    private function synchroniserRapports()
    {
        try {
            // 1. Mettre à jour les caches des critères actifs
            Cache::forget('criteres_actifs');
            
            // Récupérer tous les critères actifs et les mettre en cache pour une utilisation rapide dans les rapports
            $criteresActifs = CriterePointage::where('actif', true)
                ->with(['employe', 'employe.poste', 'employe.grade'])
                ->get();
                
            Cache::put('criteres_actifs', $criteresActifs, now()->addDay());
            
            // 2. Mettre à jour les caches spécifiques aux rapports
            $rapports = ['presences', 'absences', 'retards', 'ponctualite-assiduite', 'biometrique', 'heures-supplementaires'];
            
            foreach ($rapports as $rapport) {
                Cache::forget("rapport_{$rapport}_config");
            }
            
            // 3. Enregistrer un événement pour informer le système de la mise à jour des critères
            event(new \App\Events\CriteresUpdated($criteresActifs));
            
            // 4. Mettre à jour les statistiques globales si nécessaire
            if (method_exists(\App\Services\StatistiquesService::class, 'refreshStatistiques')) {
                \App\Services\StatistiquesService::refreshStatistiques();
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la synchronisation des rapports: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Afficher la liste des critères départementaux
     */
    public function listeCriteresDepartementaux(Request $request)
    {
        // Vérifier si l'utilisateur est administrateur
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les filtres depuis la requête
        $departementId = $request->input('departement_id');
        $statut = $request->input('statut', 'tous'); // 'actif', 'inactif', 'tous'
        $periode = $request->input('periode');
        
        // Requête de base pour les critères départementaux
        $criteresQuery = CriterePointage::where('niveau', 'departemental')
            ->with(['createur']);
        
        // Filtrer par département si spécifié
        if ($departementId) {
            $criteresQuery->where('departement_id', $departementId);
        }
        
        // Filtrer par statut si spécifié
        if ($statut === 'actif') {
            $criteresQuery->where('actif', true);
        } elseif ($statut === 'inactif') {
            $criteresQuery->where('actif', false);
        }
        
        // Filtrer par période si spécifié
        if ($periode && in_array($periode, ['jour', 'semaine', 'mois'])) {
            $criteresQuery->where('periode', $periode);
        }
        
        // Ordonner par département puis par date de création
        $criteres = $criteresQuery->orderBy('departement_id')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Récupérer les départements pour le filtre
        $departements = DB::table('postes')
            ->select('departement')
            ->distinct()
            ->whereNotNull('departement')
            ->orderBy('departement')
            ->get();
        
        // Statistiques pour l'affichage
        $totalCriteres = CriterePointage::where('niveau', 'departemental')->count();
        $criteresActifs = CriterePointage::where('niveau', 'departemental')->where('actif', true)->count();
        $criteresInactifs = $totalCriteres - $criteresActifs;
        $departementsAvecCriteres = CriterePointage::where('niveau', 'departemental')
            ->distinct('departement_id')
            ->count('departement_id');
        
        return view('criteres-pointage.departementaux', compact(
            'criteres',
            'departements',
            'departementId',
            'statut',
            'periode',
            'totalCriteres',
            'criteresActifs',
            'criteresInactifs',
            'departementsAvecCriteres'
        ));
    }
}
