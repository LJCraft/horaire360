<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CriterePointageApiController extends Controller
{
    /**
     * Récupérer tous les critères définis
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Récupérer tous les critères actifs
            $criteres = CriterePointage::with(['employe', 'employe.poste', 'employe.grade'])
                ->where('actif', true)
                ->orderBy('created_at', 'desc')
                ->get();
                
            return response()->json([
                'success' => true,
                'criteres' => $criteres,
                'count' => $criteres->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la rÃ©cupÃ©ration des critères: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la rÃ©cupÃ©ration des critères',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si un employé a déjà  un critère défini
     *
     * @param int $employeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmployeCritere($employeId)
    {
        try {
            // Vérifier si l'employé existe
            $employe = Employe::find($employeId);
            
            if (!$employe) {
                return response()->json([
                    'success' => false,
                    'message' => 'EmployÃ© non trouvÃ©'
                ], 404);
            }
            
            // Vérifier si l'employé a déjà  un critère actif
            $critere = CriterePointage::where('niveau', 'individuel')
                ->where('employe_id', $employeId)
                ->where('actif', true)
                ->first();
                
            if ($critere) {
                return response()->json([
                    'success' => true,
                    'hasCritere' => true,
                    'critere' => $critere,
                    'message' => 'L\'employé a déjà un critère individuel actif'
                ]);
            }
            
            // Vérifier si l'employé est couvert par un critère départemental
            $departementCritere = null;
            
            if ($employe->poste && $employe->poste->departement) {
                $departementCritere = CriterePointage::where('niveau', 'departemental')
                    ->where('departement_id', $employe->poste->departement)
                    ->where('actif', true)
                    ->first();
            }
            
            return response()->json([
                'success' => true,
                'hasCritere' => false,
                'hasDepartementCritere' => $departementCritere ? true : false,
                'departementCritere' => $departementCritere,
                'message' => $departementCritere 
                    ? 'L\'employé est couvert par un critère départemental' 
                    : 'L\'employé n\'a pas de critère défini'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du critère employé: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si un département a déjà  un critère défini
     *
     * @param string $departementId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDepartementCritere($departementId)
    {
        try {
            // Vérifier si le département existe
            $departement = Departement::find($departementId);
            
            if (!$departement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Département non trouvé'
                ], 404);
            }
            
            // Vérifier si le département a déjà  un critère actif
            $critere = CriterePointage::where('niveau', 'departemental')
                ->where('departement_id', $departementId)
                ->where('actif', true)
                ->first();
                
            if ($critere) {
                return response()->json([
                    'success' => true,
                    'hasCritere' => true,
                    'critere' => $critere,
                    'message' => 'Le département a déjà un critère actif'
                ]);
            }
            
            // Récupérer les employés du département qui ont déjà  un critère individuel
            $employesAvecCriteres = Employe::whereHas('poste', function($q) use ($departementId) {
                $q->where('departement', $departementId);
            })->whereHas('criteres', function($q) {
                $q->where('actif', true)
                  ->where('niveau', 'individuel');
            })->get();
            
            return response()->json([
                'success' => true,
                'hasCritere' => false,
                'employesAvecCriteres' => $employesAvecCriteres,
                'employesAvecCriteresCount' => $employesAvecCriteres->count(),
                'message' => 'Le département n\'a pas de critère défini'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du critère département: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier la validité d'un planning pour une période donnée
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validatePlanning(Request $request)
    {
        try {
            // Valider les données de la requête
            $validator = Validator::make($request->all(), [
                'employe_id' => 'required_without:departement_id|exists:employes,id',
                'departement_id' => 'required_without:employe_id|exists:departements,departement',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after_or_equal:date_debut',
                'periode' => 'required|in:jour,semaine,mois',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $dateDebut = Carbon::parse($request->date_debut);
            $dateFin = Carbon::parse($request->date_fin);
            $planningValide = false;
            $message = '';
            
            // Vérifier le planning pour un employé spécifique
            if ($request->has('employe_id')) {
                $employe = Employe::find($request->employe_id);
                
                if (!$employe) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employé non trouvé'
                    ], 404);
                }
                
                // Vérifier si l'employé a un planning pour la période donnée
                $plannings = Planning::where('employe_id', $employe->id)
                    ->where(function($query) use ($dateDebut, $dateFin) {
                        $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                            ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                            ->orWhere(function($q) use ($dateDebut, $dateFin) {
                                $q->where('date_debut', '<=', $dateDebut)
                                  ->where('date_fin', '>=', $dateFin);
                            });
                    })
                    ->get();
                
                if ($plannings->isEmpty()) {
                    $planningValide = false;
                    $message = "L'employé n'a pas de planning défini pour la période sélectionnée";
                } else {
                    $planningValide = true;
                    $message = "L'employé a un planning valide pour la période sélectionnée";
                }
                
                return response()->json([
                    'success' => true,
                    'planningValide' => $planningValide,
                    'message' => $message,
                    'plannings' => $plannings
                ]);
            }
            
            // Vérifier le planning pour un département
            if ($request->has('departement_id')) {
                $departement = Departement::find($request->departement_id);
                
                if (!$departement) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Département non trouvé'
                    ], 404);
                }
                
                // Récupérer les employés du département
                $employes = Employe::whereHas('poste', function($q) use ($departement) {
                    $q->where('departement', $departement->departement);
                })->where('statut', 'actif')->get();
                
                if ($employes->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun employé actif dans ce département'
                    ], 404);
                }
                
                // Vérifier les plannings pour chaque employé
                $employesAvecPlanning = [];
                $employesSansPlanning = [];
                
                foreach ($employes as $employe) {
                    $planning = Planning::where('employe_id', $employe->id)
                        ->where(function($query) use ($dateDebut, $dateFin) {
                            $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                                ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                                ->orWhere(function($q) use ($dateDebut, $dateFin) {
                                    $q->where('date_debut', '<=', $dateDebut)
                                      ->where('date_fin', '>=', $dateFin);
                                });
                        })
                        ->first();
                    
                    if ($planning) {
                        $employesAvecPlanning[] = [
                            'id' => $employe->id,
                            'nom' => $employe->nom,
                            'prenom' => $employe->prenom,
                            'planning_id' => $planning->id
                        ];
                    } else {
                        $employesSansPlanning[] = [
                            'id' => $employe->id,
                            'nom' => $employe->nom,
                            'prenom' => $employe->prenom
                        ];
                    }
                }
                
                $planningValide = count($employesSansPlanning) === 0;
                
                if ($planningValide) {
                    $message = "Tous les employés du département ont un planning valide pour la période sélectionnée";
                } else {
                    $message = count($employesSansPlanning) . " employé(s) du département n'ont pas de planning défini pour la période sélectionnée";
                }
                
                return response()->json([
                    'success' => true,
                    'planningValide' => $planningValide,
                    'message' => $message,
                    'employesAvecPlanning' => $employesAvecPlanning,
                    'employesSansPlanning' => $employesSansPlanning,
                    'totalEmployes' => count($employes),
                    'employesAvecPlanningCount' => count($employesAvecPlanning),
                    'employesSansPlanningCount' => count($employesSansPlanning)
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Vous devez spécifier un employé ou un département'
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation du planning: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la validation du planning',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un critère individuel ou départemental
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Valider les données de la requête
            $validator = Validator::make($request->all(), [
                'niveau' => 'required|in:individuel,departemental',
                'employe_id' => 'required_if:niveau,individuel|exists:employes,id',
                'departement_id' => 'required_if:niveau,departemental|exists:departements,departement',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after_or_equal:date_debut',
                'periode' => 'required|in:jour,semaine,mois',
                'nombre_pointages' => 'required|in:1,2',
                'tolerance_avant' => 'required|integer|min:0',
                'tolerance_apres' => 'required|integer|min:0',
                'duree_pause' => 'required_if:nombre_pointages,2|integer|min:0',
                'source_pointage' => 'required|in:biometrique,manuel,tous',
                'calcul_heures_sup' => 'required|boolean',
                'seuil_heures_sup' => 'required_if:calcul_heures_sup,1|integer|min:0',
                'priorite' => 'required|integer|min:1|max:3',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Vérifier si l'employé a déjà un critère individuel actif
            if ($request->niveau === 'individuel') {
                $critereExistant = CriterePointage::where('niveau', 'individuel')
                    ->where('employe_id', $request->employe_id)
                    ->where('actif', true)
                    ->first();
                    
                if ($critereExistant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer ce critère : l\'employé a déjà un critère individuel actif.'
                    ], 422);
                }
                
                // Vérifier si l'employé a un planning pour la période choisie
                $employe = Employe::find($request->employe_id);
                $dateDebut = Carbon::parse($request->date_debut);
                $dateFin = Carbon::parse($request->date_fin);
                
                $planning = Planning::where('employe_id', $employe->id)
                    ->where(function($query) use ($dateDebut, $dateFin) {
                        $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                            ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                            ->orWhere(function($q) use ($dateDebut, $dateFin) {
                                $q->where('date_debut', '<=', $dateDebut)
                                  ->where('date_fin', '>=', $dateFin);
                            });
                    })
                    ->first();
                
                if (!$planning) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer ce critère : l\'employé n\'a pas de planning défini pour la période sélectionnée.'
                    ], 422);
                }
            }
            
            // Vérifier si le département a déjà un critère départemental actif
            if ($request->niveau === 'departemental') {
                $critereExistant = CriterePointage::where('niveau', 'departemental')
                    ->where('departement_id', $request->departement_id)
                    ->where('actif', true)
                    ->first();
                    
                if ($critereExistant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer ce critère : le département a déjà un critère départemental actif.'
                    ], 422);
                }
                
                // Vérifier si tous les employés du département ont un planning pour la période choisie
                $departement = Departement::find($request->departement_id);
                $dateDebut = Carbon::parse($request->date_debut);
                $dateFin = Carbon::parse($request->date_fin);
                
                // Récupérer les employés du département qui n'ont pas déjà un critère individuel
                $employes = Employe::whereHas('poste', function($q) use ($departement) {
                    $q->where('departement', $departement->departement);
                })
                ->where('statut', 'actif')
                ->whereDoesntHave('criteres', function($q) {
                    $q->where('actif', true)
                      ->where('niveau', 'individuel');
                })
                ->get();
                
                if ($employes->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer ce critère : tous les employés du département ont déjà un critère individuel ou aucun employé actif dans ce département.'
                    ], 422);
                }
                
                // Vérifier les plannings pour chaque employé
                $employesSansPlanning = [];
                
                foreach ($employes as $employe) {
                    $planning = Planning::where('employe_id', $employe->id)
                        ->where(function($query) use ($dateDebut, $dateFin) {
                            $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                                ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                                ->orWhere(function($q) use ($dateDebut, $dateFin) {
                                    $q->where('date_debut', '<=', $dateDebut)
                                      ->where('date_fin', '>=', $dateFin);
                                });
                        })
                        ->first();
                    
                    if (!$planning) {
                        $employesSansPlanning[] = [
                            'id' => $employe->id,
                            'nom' => $employe->nom,
                            'prenom' => $employe->prenom
                        ];
                    }
                }
                
                if (count($employesSansPlanning) > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer ce critère : ' . count($employesSansPlanning) . ' employé(s) du département n\'ont pas de planning défini pour la période sélectionnée.',
                        'employesSansPlanning' => $employesSansPlanning
                    ], 422);
                }
            }
            
            // Vérifier que le nombre de pointages est cohérent
            if ($request->nombre_pointages == 1 && isset($request->duree_pause) && $request->duree_pause > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de créer ce critère : la durée de pause doit être 0 pour un critère avec un seul pointage.'
                ], 422);
            }
            
            // Vérifier que la période est valide
            if (!in_array($request->periode, ['jour', 'semaine', 'mois'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de créer ce critère : la période doit être jour, semaine ou mois.'
                ], 422);
            }
            
            // Créer le critère
            $critere = new CriterePointage();
            $critere->niveau = $request->niveau;
            
            if ($request->niveau === 'individuel') {
                $critere->employe_id = $request->employe_id;
            } else {
                $critere->departement_id = $request->departement_id;
            }
            
            $critere->date_debut = $request->date_debut;
            $critere->date_fin = $request->date_fin;
            $critere->periode = $request->periode;
            $critere->nombre_pointages = $request->nombre_pointages;
            $critere->tolerance_avant = $request->tolerance_avant;
            $critere->tolerance_apres = $request->tolerance_apres;
            $critere->duree_pause = $request->duree_pause ?? 0;
            $critere->source_pointage = $request->source_pointage;
            $critere->calcul_heures_sup = $request->calcul_heures_sup;
            $critere->seuil_heures_sup = $request->calcul_heures_sup ? $request->seuil_heures_sup : 0;
            $critere->priorite = $request->priorite;
            $critere->actif = true;
            $critere->created_by = Auth::id();
            
            $critere->save();
            
            // Préparer le message de succès
            $message = '';
            
            if ($request->niveau === 'individuel') {
                $employe = Employe::find($request->employe_id);
                $message = 'Critère individuel créé avec succès pour l\'employé ' . $employe->prenom . ' ' . $employe->nom . '.';
            } else {
                $departement = Departement::find($request->departement_id);
                $nbEmployes = Employe::whereHas('poste', function($q) use ($departement) {
                    $q->where('departement', $departement->departement);
                })
                ->where('statut', 'actif')
                ->whereDoesntHave('criteres', function($q) {
                    $q->where('actif', true)
                      ->where('niveau', 'individuel');
                })
                ->count();
                
                $message = 'Critère appliqué avec succès à ' . $nbEmployes . ' employés du département ' . $departement->nom . '.';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'critere' => $critere
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du critère: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du critère',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Modifier un critère existant
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Trouver le critère
            $critere = CriterePointage::find($id);
            
            if (!$critere) {
                return response()->json([
                    'success' => false,
                    'message' => 'Critère non trouvé'
                ], 404);
            }
            
            // Valider les données de la requête
            $validator = Validator::make($request->all(), [
                'date_debut' => 'sometimes|required|date',
                'date_fin' => 'sometimes|required|date|after_or_equal:date_debut',
                'periode' => 'sometimes|required|in:jour,semaine,mois',
                'nombre_pointages' => 'sometimes|required|in:1,2',
                'tolerance_avant' => 'sometimes|required|integer|min:0',
                'tolerance_apres' => 'sometimes|required|integer|min:0',
                'duree_pause' => 'required_if:nombre_pointages,2|integer|min:0',
                'source_pointage' => 'sometimes|required|in:biometrique,manuel,tous',
                'calcul_heures_sup' => 'sometimes|required|boolean',
                'seuil_heures_sup' => 'required_if:calcul_heures_sup,1|integer|min:0',
                'priorite' => 'sometimes|required|integer|min:1|max:3',
                'actif' => 'sometimes|required|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Vérifier si les dates ont changé et si le planning existe toujours
            if (($request->has('date_debut') && $request->date_debut != $critere->date_debut->format('Y-m-d')) || 
                ($request->has('date_fin') && $request->date_fin != $critere->date_fin->format('Y-m-d'))) {
                
                $dateDebut = Carbon::parse($request->date_debut ?? $critere->date_debut);
                $dateFin = Carbon::parse($request->date_fin ?? $critere->date_fin);
                
                if ($critere->niveau === 'individuel') {
                    $planning = Planning::where('employe_id', $critere->employe_id)
                        ->where(function($query) use ($dateDebut, $dateFin) {
                            $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                                ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                                ->orWhere(function($q) use ($dateDebut, $dateFin) {
                                    $q->where('date_debut', '<=', $dateDebut)
                                      ->where('date_fin', '>=', $dateFin);
                                });
                        })
                        ->first();
                    
                    if (!$planning) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Impossible de modifier ce critère : l\'employé n\'a pas de planning défini pour la nouvelle période sélectionnée.'
                        ], 422);
                    }
                } else {
                    // Vérifier les plannings pour tous les employés du département
                    $departement = Departement::find($critere->departement_id);
                    
                    // Récupérer les employés du département qui n'ont pas déjà un critère individuel
                    $employes = Employe::whereHas('poste', function($q) use ($departement) {
                        $q->where('departement', $departement->departement);
                    })
                    ->where('statut', 'actif')
                    ->whereDoesntHave('criteres', function($q) use ($critere) {
                        $q->where('actif', true)
                          ->where('niveau', 'individuel')
                          ->where('id', '!=', $critere->id);
                    })
                    ->get();
                    
                    $employesSansPlanning = [];
                    
                    foreach ($employes as $employe) {
                        $planning = Planning::where('employe_id', $employe->id)
                            ->where(function($query) use ($dateDebut, $dateFin) {
                                $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                                    ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                                    ->orWhere(function($q) use ($dateDebut, $dateFin) {
                                        $q->where('date_debut', '<=', $dateDebut)
                                          ->where('date_fin', '>=', $dateFin);
                                    });
                            })
                            ->first();
                        
                        if (!$planning) {
                            $employesSansPlanning[] = [
                                'id' => $employe->id,
                                'nom' => $employe->nom,
                                'prenom' => $employe->prenom
                            ];
                        }
                    }
                    
                    if (count($employesSansPlanning) > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Impossible de modifier ce critère : ' . count($employesSansPlanning) . ' employé(s) du département n\'ont pas de planning défini pour la nouvelle période sélectionnée.',
                            'employesSansPlanning' => $employesSansPlanning
                        ], 422);
                    }
                }
            }
            
            // Vérifier que le nombre de pointages est cohérent
            $nombrePointages = $request->nombre_pointages ?? $critere->nombre_pointages;
            $dureePause = $request->duree_pause ?? $critere->duree_pause;
            
            if ($nombrePointages == 1 && $dureePause > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de modifier ce critère : la durée de pause doit être 0 pour un critère avec un seul pointage.'
                ], 422);
            }
            
            // Mettre à jour le critère
            if ($request->has('date_debut')) $critere->date_debut = $request->date_debut;
            if ($request->has('date_fin')) $critere->date_fin = $request->date_fin;
            if ($request->has('periode')) $critere->periode = $request->periode;
            if ($request->has('nombre_pointages')) $critere->nombre_pointages = $request->nombre_pointages;
            if ($request->has('tolerance_avant')) $critere->tolerance_avant = $request->tolerance_avant;
            if ($request->has('tolerance_apres')) $critere->tolerance_apres = $request->tolerance_apres;
            if ($request->has('duree_pause')) $critere->duree_pause = $request->duree_pause;
            if ($request->has('source_pointage')) $critere->source_pointage = $request->source_pointage;
            if ($request->has('calcul_heures_sup')) {
                $critere->calcul_heures_sup = $request->calcul_heures_sup;
                if (!$request->calcul_heures_sup) {
                    $critere->seuil_heures_sup = 0;
                }
            }
            if ($request->has('seuil_heures_sup') && $critere->calcul_heures_sup) {
                $critere->seuil_heures_sup = $request->seuil_heures_sup;
            }
            if ($request->has('priorite')) $critere->priorite = $request->priorite;
            if ($request->has('actif')) $critere->actif = $request->actif;
            
            $critere->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Critère modifié avec succès',
                'critere' => $critere
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification du critère: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la modification du critère',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Supprimer un critère
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Trouver le critère
            $critere = CriterePointage::find($id);
            
            if (!$critere) {
                return response()->json([
                    'success' => false,
                    'message' => 'Critère non trouvé'
                ], 404);
            }
            
            // Désactiver le critère au lieu de le supprimer
            $critere->actif = false;
            $critere->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Critère supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du critère: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du critère',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
