<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\Poste;
use App\Models\Planning;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class RapportControllerAdditions extends Controller
{
    /**
     * Rapport Global - Vue Multi-Période V2 (Amélioré)
     * Affiche uniquement les heures de pointage (arrivée/départ) des employés, sans calcul ni analyse.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function globalMultiPeriodeV2(Request $request)
    {
        // Paramètres de période
        $periode = $request->query('periode', 'jour');
        $date = $request->query('date', now()->format('Y-m-d'));
        
        // Validation des paramètres
        if (!in_array($periode, ['jour', 'semaine', 'mois'])) {
            $periode = 'jour';
        }
        
        // Détermination des dates de début et fin selon la période
        $dateObj = Carbon::parse($date);
        $dateDebut = $dateObj->copy();
        $dateFin = $dateObj->copy();
        $periodeLabel = '';
        
        switch ($periode) {
            case 'jour':
                $periodeLabel = $dateObj->format('d/m/Y');
                break;
                
            case 'semaine':
                $dateDebut = $dateObj->startOfWeek();
                $dateFin = $dateObj->copy()->endOfWeek();
                $periodeLabel = 'Semaine du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
                break;
                
            case 'mois':
                $dateDebut = $dateObj->startOfMonth();
                $dateFin = $dateObj->copy()->endOfMonth();
                $periodeLabel = $dateObj->format('F Y');
                break;
        }
        
        // Récupération des jours de la période
        $jours = [];
        $period = CarbonPeriod::create($dateDebut, $dateFin);
        foreach ($period as $date) {
            $jours[] = $date->format('Y-m-d');
        }
        
        // Récupération des employés actifs
        $employes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
        
        // Récupération des présences pour la période
        $presences = Presence::whereBetween('date', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
            ->get()
            ->groupBy('employe_id');
        
        return view('rapports.global-multi-periode-v2', compact(
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
     * Rapport Ponctualité & Assiduité V2 (Amélioré)
     * Analyse la présence selon des indicateurs RH clés.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function ponctualiteAssiduiteV2(Request $request)
    {
        // Paramètres de filtrage
        $periode = $request->query('periode', 'mois');
        $dateDebutStr = $request->query('date_debut', now()->format('Y-m-d'));
        $dateFinStr = $request->query('date_fin');
        $employeId = $request->query('employe_id');
        $departementId = $request->query('departement_id');
        $serviceId = $request->query('service_id');
        $sort = $request->query('sort', 'nom');
        $afficherGraphiques = $request->query('graphiques', true);
        
        // Validation des paramètres
        if (!in_array($periode, ['jour', 'semaine', 'mois'])) {
            $periode = 'mois';
        }
        
        // Détermination des dates de début et fin selon la période
        $dateDebut = Carbon::parse($dateDebutStr);
        $dateFin = $dateFinStr ? Carbon::parse($dateFinStr) : $dateDebut->copy();
        $periodeLabel = '';
        
        switch ($periode) {
            case 'jour':
                $dateFin = $dateDebut->copy();
                $periodeLabel = $dateDebut->format('d/m/Y');
                break;
                
            case 'semaine':
                if (!$dateFinStr) {
                    $dateDebut = $dateDebut->startOfWeek();
                    $dateFin = $dateDebut->copy()->endOfWeek();
                }
                $periodeLabel = 'Semaine du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y');
                break;
                
            case 'mois':
                if (!$dateFinStr) {
                    $dateDebut = $dateDebut->startOfMonth();
                    $dateFin = $dateDebut->copy()->endOfMonth();
                }
                $periodeLabel = $dateDebut->format('F Y');
                break;
        }
        
        // Récupération des employés actifs pour les filtres
        $tousEmployes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
        
        // Récupération des postes et départements pour les filtres
        $postes = Poste::orderBy('nom')->get();
        $departements = $postes->map(function ($poste) {
            return [
                'id' => $poste->id,
                'nom' => $poste->departement
            ];
        })->groupBy('nom')->map(function ($group) {
            return $group->first();
        })->values()->toArray();
        
        // Récupération des services pour les filtres
        $services = Service::orderBy('nom')->get();
        
        // Calcul des statistiques pour chaque employé
        $statistiques = $this->getStatistiquesPonctualiteAssiduiteV2(
            $dateDebut->format('Y-m-d'),
            $dateFin->format('Y-m-d'),
            $employeId,
            $departementId,
            $serviceId
        );
        
        // Tri des statistiques selon le critère sélectionné
        switch ($sort) {
            case 'nom':
                $statistiques = $statistiques->sortBy(function ($stat) {
                    return $stat->employe->nom . ' ' . $stat->employe->prenom;
                });
                break;
                
            case 'departement':
                $statistiques = $statistiques->sortBy(function ($stat) {
                    return $stat->employe->departement ?? 'ZZZ';
                });
                break;
                
            case 'ponctualite':
                $statistiques = $statistiques->sortByDesc('taux_ponctualite');
                break;
                
            case 'assiduite':
                $statistiques = $statistiques->sortByDesc('taux_assiduite');
                break;
        }
        
        // Préparation des données pour les graphiques
        $employesNoms = $statistiques->map(function ($stat) {
            return $stat->employe->prenom . ' ' . substr($stat->employe->nom, 0, 1) . '.';
        })->values()->toArray();
        
        $tauxPonctualiteData = $statistiques->map(function ($stat) {
            return round($stat->taux_ponctualite, 1);
        })->values()->toArray();
        
        $tauxAssiduiteData = $statistiques->map(function ($stat) {
            return round($stat->taux_assiduite, 1);
        })->values()->toArray();
        
        return view('rapports.ponctualite-assiduite-v2', compact(
            'periode',
            'dateDebut',
            'dateFin',
            'periodeLabel',
            'employeId',
            'departementId',
            'serviceId',
            'tousEmployes',
            'departements',
            'services',
            'statistiques',
            'afficherGraphiques',
            'employesNoms',
            'tauxPonctualiteData',
            'tauxAssiduiteData'
        ));
    }
    
    /**
     * Récupérer les statistiques de ponctualité et assiduité pour une période donnée (version améliorée)
     *
     * @param string|null $dateDebut Date de début au format Y-m-d
     * @param string|null $dateFin Date de fin au format Y-m-d
     * @param int|null $employeId ID de l'employé (optionnel)
     * @param int|null $departementId ID du département (optionnel)
     * @param int|null $serviceId ID du service (optionnel)
     * @return \Illuminate\Support\Collection Collection de statistiques par employé
     */
    private function getStatistiquesPonctualiteAssiduiteV2($dateDebut = null, $dateFin = null, $employeId = null, $departementId = null, $serviceId = null)
    {
        // Récupération des employés selon les filtres
        $query = Employe::where('statut', 'actif');
        
        if ($employeId) {
            $query->where('id', $employeId);
        }
        
        if ($departementId) {
            $query->whereHas('poste', function ($q) use ($departementId) {
                $q->where('id', $departementId);
            });
        }
        
        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }
        
        $employes = $query->with(['poste', 'grade', 'service'])->get();
        
        // Calcul des statistiques pour chaque employé
        $statistiques = collect();
        
        foreach ($employes as $employe) {
            // === LOGIQUE MÉTIER STRICTEMENT FONCTIONNELLE ===
            // Calculer les jours prévus à partir du planning hebdomadaire
            $joursOuvrables = $this->calculerJoursOuvrablesPlanningHebdomadaire($employe->id, $dateDebut, $dateFin);
            
            // Calculer les heures prévues à partir du planning hebdomadaire
            $heuresPrevues = $this->calculerHeuresPrevuesPlanningHebdomadaire($employe->id, $dateDebut, $dateFin);
            
            // Récupération des présences pour la période
            $presences = Presence::where('employe_id', $employe->id)
                ->whereBetween('date', [$dateDebut, $dateFin])
                ->get();
            
            // Calcul des jours réalisés (nombre de jours avec présence)
            $joursRealises = $presences->count();
            
            // Calcul des heures réellement effectuées
            $heuresFaites = 0;
            foreach ($presences as $presence) {
                if ($presence->heure_arrivee && $presence->heure_depart) {
                    $heureArrivee = Carbon::parse($presence->heure_arrivee);
                    $heureDepart = Carbon::parse($presence->heure_depart);
                    $heuresFaites += $heureDepart->diffInHours($heureArrivee);
                }
            }
            
            // Calcul des heures d'absence
            $heuresAbsence = max(0, $heuresPrevues - $heuresFaites);
            
            // Calcul du nombre de retards et départs anticipés
            $nombreRetards = $presences->where('retard', true)->count();
            $nombreDepartsAnticipes = $presences->where('depart_anticipe', true)->count();
            
            // Calcul des taux
            $tauxPonctualite = $joursRealises > 0 ? (($joursRealises - $nombreRetards) / $joursRealises) * 100 : 0;
            $tauxAssiduite = $heuresPrevues > 0 ? ($heuresFaites / $heuresPrevues) * 100 : 0;
            
            // Création de l'objet statistique
            $stat = (object) [
                'employe' => $employe,
                'jours_prevus' => $joursOuvrables,
                'jours_realises' => $joursRealises,
                'heures_prevues' => $heuresPrevues,
                'heures_faites' => $heuresFaites,
                'heures_absence' => $heuresAbsence,
                'nombre_retards' => $nombreRetards,
                'nombre_departs_anticipes' => $nombreDepartsAnticipes,
                'taux_ponctualite' => $tauxPonctualite,
                'taux_assiduite' => $tauxAssiduite,
                'observation_rh' => '' // === CONTRAINTE : Vider systématiquement la colonne Observation RH ===
            ];
            
            $statistiques->push($stat);
        }
        
        return $statistiques;
    }
    
    /**
     * Exporter le rapport en PDF (version améliorée)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdfV2(Request $request)
    {
        $type = $request->query('type');
        $periode = $request->query('periode', 'mois');
        $dateDebut = $request->query('date_debut', now()->format('Y-m-d'));
        $dateFin = $request->query('date_fin');
        $employeId = $request->query('employe_id');
        $departementId = $request->query('departement_id');
        $serviceId = $request->query('service_id');
        
        // Préparation des données selon le type de rapport
        switch ($type) {
            case 'global-multi-periode-v2':
                return $this->exportGlobalMultiPeriodePdf($periode, $dateDebut, $dateFin);
                
            case 'ponctualite-assiduite-v2':
                return $this->exportPonctualiteAssiduitePdf($periode, $dateDebut, $dateFin, $employeId, $departementId, $serviceId);
                
            default:
                return redirect()->back()->with('error', 'Type de rapport non pris en charge');
        }
    }
    
    /**
     * Exporter le rapport global multi-période en PDF
     *
     * @param string $periode
     * @param string $dateDebut
     * @param string|null $dateFin
     * @return \Illuminate\Http\Response
     */
    private function exportGlobalMultiPeriodePdf($periode, $dateDebut, $dateFin = null)
    {
        // Détermination des dates de début et fin selon la période
        $dateObj = Carbon::parse($dateDebut);
        $dateDebutObj = $dateObj->copy();
        $dateFinObj = $dateFin ? Carbon::parse($dateFin) : $dateObj->copy();
        $periodeLabel = '';
        
        switch ($periode) {
            case 'jour':
                $dateFinObj = $dateDebutObj->copy();
                $periodeLabel = $dateDebutObj->format('d/m/Y');
                break;
                
            case 'semaine':
                if (!$dateFin) {
                    $dateDebutObj = $dateDebutObj->startOfWeek();
                    $dateFinObj = $dateDebutObj->copy()->endOfWeek();
                }
                $periodeLabel = 'Semaine du ' . $dateDebutObj->format('d/m/Y') . ' au ' . $dateFinObj->format('d/m/Y');
                break;
                
            case 'mois':
                if (!$dateFin) {
                    $dateDebutObj = $dateDebutObj->startOfMonth();
                    $dateFinObj = $dateDebutObj->copy()->endOfMonth();
                }
                $periodeLabel = $dateDebutObj->format('F Y');
                break;
        }
        
        // Récupération des jours de la période
        $jours = [];
        $period = CarbonPeriod::create($dateDebutObj, $dateFinObj);
        foreach ($period as $date) {
            $jours[] = $date->format('Y-m-d');
        }
        
        // Récupération des employés actifs
        $employes = Employe::where('statut', 'actif')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
        
        // Récupération des présences pour la période
        $presences = Presence::whereBetween('date', [$dateDebutObj->format('Y-m-d'), $dateFinObj->format('Y-m-d')])
            ->get()
            ->groupBy('employe_id');
        
        // Génération du PDF
        $pdf = PDF::loadView('rapports.pdf.global-multi-periode-v2', [
            'periode' => $periode,
            'periodeLabel' => $periodeLabel,
            'jours' => $jours,
            'employes' => $employes,
            'presences' => $presences
        ]);
        
        // Configuration du PDF pour le format A4
        $pdf->setPaper('a4', 'landscape');
        
        // Téléchargement du PDF
        return $pdf->download('rapport-global-presence-' . Str::slug($periodeLabel) . '.pdf');
    }
    
    /**
     * Exporter le rapport de ponctualité et assiduité en PDF
     *
     * @param string $periode
     * @param string $dateDebut
     * @param string|null $dateFin
     * @param int|null $employeId
     * @param int|null $departementId
     * @param int|null $serviceId
     * @return \Illuminate\Http\Response
     */
    private function exportPonctualiteAssiduitePdf($periode, $dateDebut, $dateFin = null, $employeId = null, $departementId = null, $serviceId = null)
    {
        // Détermination des dates de début et fin selon la période
        $dateObj = Carbon::parse($dateDebut);
        $dateDebutObj = $dateObj->copy();
        $dateFinObj = $dateFin ? Carbon::parse($dateFin) : $dateObj->copy();
        $periodeLabel = '';
        
        switch ($periode) {
            case 'jour':
                $dateFinObj = $dateDebutObj->copy();
                $periodeLabel = $dateDebutObj->format('d/m/Y');
                break;
                
            case 'semaine':
                if (!$dateFin) {
                    $dateDebutObj = $dateDebutObj->startOfWeek();
                    $dateFinObj = $dateDebutObj->copy()->endOfWeek();
                }
                $periodeLabel = 'Semaine du ' . $dateDebutObj->format('d/m/Y') . ' au ' . $dateFinObj->format('d/m/Y');
                break;
                
            case 'mois':
                if (!$dateFin) {
                    $dateDebutObj = $dateDebutObj->startOfMonth();
                    $dateFinObj = $dateDebutObj->copy()->endOfMonth();
                }
                $periodeLabel = $dateDebutObj->format('F Y');
                break;
        }
        
        // Calcul des statistiques
        $statistiques = $this->getStatistiquesPonctualiteAssiduiteV2(
            $dateDebutObj->format('Y-m-d'),
            $dateFinObj->format('Y-m-d'),
            $employeId,
            $departementId,
            $serviceId
        );
        
        // Génération du PDF
        $pdf = PDF::loadView('rapports.pdf.ponctualite-assiduite-v2', [
            'periode' => $periode,
            'periodeLabel' => $periodeLabel,
            'statistiques' => $statistiques
        ]);
        
        // Configuration du PDF pour le format A4
        $pdf->setPaper('a4', 'landscape');
        
        // Téléchargement du PDF
        return $pdf->download('rapport-ponctualite-assiduite-' . Str::slug($periodeLabel) . '.pdf');
    }

    /**
     * === NOUVELLE LOGIQUE MÉTIER STRICTE ===
     * Calculer les jours prévus en utilisant la PÉRIODE RÉELLE DU PLANNING
     * Base temporelle = [date_debut_planning → date_fin_planning]
     * 
     * @param int $employeId ID de l'employé
     * @param string $dateDebut Date de début du rapport (pour intersection)
     * @param string $dateFin Date de fin du rapport (pour intersection)
     * @return int|null Nombre de jours prévus ou null si aucun planning
     */
    private function calculerJoursOuvrablesPlanningHebdomadaire($employeId, $dateDebut, $dateFin)
    {
        $dateDebutObj = Carbon::parse($dateDebut);
        $dateFinObj = Carbon::parse($dateFin);
        
        // Récupérer les plannings actifs pour cet employé dans la période
        $plannings = Planning::where('employe_id', $employeId)
            ->where('actif', true)
            ->where(function ($query) use ($dateDebutObj, $dateFinObj) {
                $query->whereBetween('date_debut', [$dateDebutObj, $dateFinObj])
                    ->orWhereBetween('date_fin', [$dateDebutObj, $dateFinObj])
                    ->orWhere(function ($q) use ($dateDebutObj, $dateFinObj) {
                        $q->where('date_debut', '<=', $dateDebutObj)
                            ->where('date_fin', '>=', $dateFinObj);
                    });
            })
            ->with('details')
            ->get();

        if ($plannings->isEmpty()) {
            return null; // Aucun planning défini : champ vide selon la règle métier
        }

        $joursOuvrables = 0;
        
        foreach ($plannings as $planning) {
            // === BASE TEMPORELLE = PÉRIODE RÉELLE COMPLÈTE DU PLANNING ===
            $planningDebut = Carbon::parse($planning->date_debut);
            $planningFin = Carbon::parse($planning->date_fin);
            
            // ❌ SUPPRESSION DE L'INTERSECTION PROBLÉMATIQUE
            // ✅ UTILISATION DE LA PÉRIODE COMPLÈTE DU PLANNING
            $periodeDebut = $planningDebut;
            $periodeFin = $planningFin;
            
            // Créer la période basée sur les dates COMPLÈTES du planning
            $period = CarbonPeriod::create($periodeDebut, $periodeFin);
            
            // Compter les occurrences de chaque jour planifié dans la période COMPLÈTE
            foreach ($period as $date) {
                $jourSemaine = $date->dayOfWeekIso; // 1 = Lundi, 7 = Dimanche
                    
                // Chercher le détail du planning pour ce jour de la semaine
                $detail = $planning->details->firstWhere('jour', $jourSemaine);
                
                if ($detail && !$detail->jour_repos) {
                    $joursOuvrables++;
                }
            }
        }
        
        return $joursOuvrables;
    }

    /**
     * === NOUVELLE LOGIQUE MÉTIER STRICTE ===
     * Calculer les heures prévues en utilisant la PÉRIODE RÉELLE DU PLANNING
     * Base temporelle = [date_debut_planning → date_fin_planning]
     * 
     * @param int $employeId ID de l'employé
     * @param string $dateDebut Date de début du rapport (pour intersection)
     * @param string $dateFin Date de fin du rapport (pour intersection)
     * @return float|null Nombre d'heures prévues ou null si aucun planning
     */
    private function calculerHeuresPrevuesPlanningHebdomadaire($employeId, $dateDebut, $dateFin)
    {
        $dateDebutObj = Carbon::parse($dateDebut);
        $dateFinObj = Carbon::parse($dateFin);
        
        // Récupérer les plannings actifs pour cet employé dans la période
        $plannings = Planning::where('employe_id', $employeId)
            ->where('actif', true)
            ->where(function ($query) use ($dateDebutObj, $dateFinObj) {
                $query->whereBetween('date_debut', [$dateDebutObj, $dateFinObj])
                    ->orWhereBetween('date_fin', [$dateDebutObj, $dateFinObj])
                    ->orWhere(function ($q) use ($dateDebutObj, $dateFinObj) {
                        $q->where('date_debut', '<=', $dateDebutObj)
                            ->where('date_fin', '>=', $dateFinObj);
                    });
            })
            ->with('details')
            ->get();

        if ($plannings->isEmpty()) {
            return null; // Aucun planning défini : champ vide selon la règle métier
        }

        $heuresPrevues = 0;
        
        foreach ($plannings as $planning) {
            // === BASE TEMPORELLE = PÉRIODE RÉELLE COMPLÈTE DU PLANNING ===
            $planningDebut = Carbon::parse($planning->date_debut);
            $planningFin = Carbon::parse($planning->date_fin);
            
            // ❌ SUPPRESSION DE L'INTERSECTION PROBLÉMATIQUE
            // ✅ UTILISATION DE LA PÉRIODE COMPLÈTE DU PLANNING
            $periodeDebut = $planningDebut;
            $periodeFin = $planningFin;
            
            // Créer la période basée sur les dates COMPLÈTES du planning
            $period = CarbonPeriod::create($periodeDebut, $periodeFin);
            
            // Calculer l'amplitude horaire × occurrences pour chaque jour planifié
            foreach ($period as $date) {
                $jourSemaine = $date->dayOfWeekIso; // 1 = Lundi, 7 = Dimanche
                    
                // Chercher le détail du planning pour ce jour de la semaine
                $detail = $planning->details->firstWhere('jour', $jourSemaine);
                
                if ($detail && !$detail->jour_repos) {
                    if ($detail->jour_entier) {
                        // Journée entière = 8 heures par défaut
                        $heuresPrevues += 8;
                    } elseif ($detail->heure_debut && $detail->heure_fin) {
                        // Calculer l'amplitude horaire (heure de fin - heure de début)
                        $heureDebut = Carbon::parse($detail->heure_debut);
                        $heureFin = Carbon::parse($detail->heure_fin);
                        
                        // Validation : ne pas calculer si heure de fin < heure de début (sauf horaires de nuit)
                        if ($heureFin->lt($heureDebut)) {
                            $heureFin->addDay();
                        }
                        
                        $dureeEnHeures = $heureDebut->diffInMinutes($heureFin) / 60;
                        $heuresPrevues += $dureeEnHeures;
                    }
                }
            }
        }
        
        return round($heuresPrevues, 2);
    }

}
