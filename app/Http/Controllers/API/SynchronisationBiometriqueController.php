<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SynchronisationBiometriqueController extends Controller
{
    /**
     * Synchroniser les données biométriques depuis l'application mobile
     */
    public function synchroniser(Request $request)
    {
        try {
            // Log de début
            Log::info('🚀 === DÉBUT SYNCHRONISATION INTELLIGENTE ===', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // Validation du format JSON
            $validation = $request->validate([
                'data' => 'required|array',
                'data.*' => 'required|array',
                'source_app' => 'string|nullable',
                'version' => 'string|nullable',
                'intelligent_processing' => 'boolean|nullable'
            ]);

            $donneesPointages = $validation['data'];
            $sourceApp = $validation['source_app'] ?? 'app_mobile';
            $version = $validation['version'] ?? '1.0.0';
            $useIntelligentProcessing = $validation['intelligent_processing'] ?? true;
            
            // Génération d'un ID unique de session
            $sessionId = 'sync_' . uniqid();
            
            Log::info("📊 Session de synchronisation démarrée", [
                'session_id' => $sessionId,
                'source_app' => $sourceApp,
                'version' => $version,
                'intelligent_processing' => $useIntelligentProcessing,
                'total_records' => count($donneesPointages)
            ]);

            if ($useIntelligentProcessing) {
                // ✨ NOUVEAU : Utiliser le traitement intelligent
                return $this->traiterAvecServiceIntelligent($donneesPointages, $sourceApp, $version, $sessionId);
            } else {
                // Méthode classique pour compatibilité
                return $this->traiterAvecMethodeClassique($donneesPointages, $sourceApp, $version, $sessionId);
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur fatale lors de la synchronisation intelligente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur système lors de la synchronisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✨ Traitement intelligent avec le nouveau service
     */
    private function traiterAvecServiceIntelligent($donneesPointages, $sourceApp, $version, $sessionId)
    {
        $startTime = microtime(true);

        // Mapper les données au format standardisé
        $pointagesBruts = [];
        foreach ($donneesPointages as $donnee) {
            $pointageMappé = $this->mapperChamps($donnee);
            if ($pointageMappé) {
                $pointagesBruts[] = $pointageMappé;
            }
        }

        Log::info("🔄 Données mappées pour traitement intelligent", [
            'pointages_originaux' => count($donneesPointages),
            'pointages_mappés' => count($pointagesBruts),
            'session_id' => $sessionId
        ]);

        // Utiliser le service intelligent
        $servicePointage = new \App\Services\PointageIntelligentService();
        $resultat = $servicePointage->traiterPointagesBruts(
            $pointagesBruts, 
            $sourceApp, 
            $sessionId
        );

        $processingTime = round((microtime(true) - $startTime) * 1000);

        if ($resultat['success']) {
            Log::info('✅ Synchronisation intelligente terminée avec succès', [
                'stats' => $resultat['stats'],
                'processing_time_ms' => $processingTime,
                'session_id' => $sessionId
            ]);

            // Convertir au format de stats classique pour compatibilité
            $statsCompatibles = [
                'received' => $resultat['stats']['total_recu'],
                'inserted' => $resultat['stats']['presences_creees'],
                'updated' => $resultat['stats']['presences_mises_a_jour'],
                'ignored' => $resultat['stats']['ignores'],
                'conflicts' => 0, // Gérés intelligemment
                'errors' => $resultat['stats']['erreurs']
            ];

            return response()->json([
                'status' => 'success',
                'message' => $resultat['message'],
                'received' => $statsCompatibles['received'],
                'inserted' => $statsCompatibles['inserted'],
                'updated' => $statsCompatibles['updated'],
                'ignored' => $statsCompatibles['ignored'],
                'conflicts' => $statsCompatibles['conflicts'],
                'errors' => $statsCompatibles['errors'],
                'processing_time_ms' => $processingTime,
                'session_id' => $sessionId,
                'intelligent_processing' => true,
                'detailed_stats' => $resultat['stats'],
                'conflicts' => [],
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement intelligent',
                'error' => $resultat['error'],
                'stats' => $resultat['stats']
            ], 500);
        }
    }

    /**
     * 🔧 Méthode classique (pour compatibilité)
     */
    private function traiterAvecMethodeClassique($donneesPointages, $sourceApp, $version, $sessionId)
    {
        $startTime = microtime(true);

        // Statistiques de traitement
        $stats = [
            'received' => count($donneesPointages),
            'inserted' => 0,
            'updated' => 0,
            'ignored' => 0,
            'conflicts' => 0,
            'errors' => 0
        ];

        $conflits = [];
        $erreurs = [];
        $ligne = 0;

        foreach ($donneesPointages as $donnee) {
            $ligne++;
            
            Log::info("Traitement classique ligne {$ligne}", [
                'donnee_brute' => $donnee,
                'session_id' => $sessionId
            ]);
            
            $resultat = $this->traiterPointage($donnee, $ligne, $sessionId, $sourceApp, $version);
            
            Log::info("Résultat traitement classique ligne {$ligne}", [
                'resultat' => $resultat,
                'session_id' => $sessionId
            ]);
            
            $this->mettreAJourStats($stats, $resultat, $ligne);

            // Collecte des conflits et erreurs pour le rapport
            if ($resultat['status'] === 'conflict') {
                $conflits[] = [
                    'line' => $ligne,
                    'reason' => $resultat['message'],
                    'data' => $resultat['donnee_originale']
                ];
            }

            if ($resultat['status'] === 'error') {
                $erreurs[] = [
                    'line' => $ligne,
                    'reason' => $resultat['message'],
                    'data' => $resultat['donnee_originale']
                ];
            }
        }

        // Finalisation et logs
        $tempsTraitement = round((microtime(true) - $startTime) * 1000);
        
        Log::info("Synchronisation classique terminée", [
            'session_id' => $sessionId,
            'statistiques' => $stats,
            'conflicts_count' => count($conflits),
            'errors_count' => count($erreurs),
            'processing_time_ms' => $tempsTraitement
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $this->genererMessageResultat($stats),
            'received' => $stats['received'],
            'inserted' => $stats['inserted'],
            'updated' => $stats['updated'],
            'ignored' => $stats['ignored'],
            'conflicts' => count($conflits),
            'errors' => count($erreurs),
            'processing_time_ms' => $tempsTraitement,
            'session_id' => $sessionId,
            'intelligent_processing' => false,
            'conflicts' => $conflits,
            'errors' => $erreurs
        ]);
    }

    /**
     * Traiter un pointage individuel avec mapping intelligent
     */
    private function traiterPointage($donnee, $ligne, $sessionId, $sourceApp, $version)
    {
        try {
            // Mapping intelligent des champs
            $pointageMappé = $this->mapperChamps($donnee);
            
            // Validation des données mappées
            $validation = $this->validerPointage($pointageMappé);
            if (!$validation['valide']) {
                return [
                    'status' => 'error',
                    'ligne' => $ligne,
                    'message' => $validation['erreur'],
                    'donnee_originale' => $donnee
                ];
            }

            // Vérification de l'existence de l'employé
            $employe = Employe::find($pointageMappé['employe_id']);
            if (!$employe) {
                return [
                    'status' => 'error',
                    'ligne' => $ligne,
                    'message' => "Employé ID {$pointageMappé['employe_id']} introuvable",
                    'donnee_originale' => $donnee
                ];
            }

            // Détection de doublons
            $doublon = $this->detecterDoublon($pointageMappé);
            if ($doublon) {
                Log::info("Doublon détecté pour la ligne {$ligne}", [
                    'pointage_mappe' => $pointageMappé,
                    'doublon_trouve' => $doublon->toArray(),
                    'session_id' => $sessionId
                ]);
                
                return [
                    'status' => 'conflict',
                    'ligne' => $ligne,
                    'message' => "Pointage déjà existant - Source: {$doublon->source_pointage}",
                    'doublon_id' => $doublon->id,
                    'donnee_originale' => $donnee
                ];
            }

            // Création ou mise à jour du pointage
            $resultat = $this->creerOuMettreAJourPointage($pointageMappé, $sessionId, $sourceApp, $version);
            
            return [
                'status' => $resultat['action'], // 'inserted' ou 'updated'
                'ligne' => $ligne,
                'message' => $resultat['message'],
                'pointage_id' => $resultat['pointage_id'],
                'donnee_originale' => $donnee
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors du traitement du pointage ligne {$ligne}", [
                'session_id' => $sessionId,
                'ligne' => $ligne,
                'donnee' => $donnee,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'ligne' => $ligne,
                'message' => 'Erreur technique: ' . $e->getMessage(),
                'donnee_originale' => $donnee
            ];
        }
    }

    /**
     * Mapping intelligent des champs depuis différents formats possibles
     */
    private function mapperChamps($donnee)
    {
        $mapped = [
            'employe_id' => null,
            'date' => null,
            'heure' => null,
            'type_pointage' => null,
            'terminal_id' => 1, // Par défaut mobile
            'position' => null,
            'donnee_brute' => $donnee
        ];

        // Mapping de l'ID employé
        $mapped['employe_id'] = $this->extraireEmployeId($donnee);
        
        // Mapping de la date et heure
        $dateHeure = $this->extraireDateHeure($donnee);
        $mapped['date'] = $dateHeure['date'];
        $mapped['heure'] = $dateHeure['heure'];
        
        // Mapping du type de pointage
        $mapped['type_pointage'] = $this->extraireTypePointage($donnee);
        
        // Mapping du terminal ID
        $mapped['terminal_id'] = $this->extraireTerminalId($donnee);
        
        // Mapping de la position géographique
        $mapped['position'] = $this->extrairePosition($donnee);

        return $mapped;
    }

    /**
     * Extraction intelligente de l'ID employé
     */
    private function extraireEmployeId($donnee)
    {
        $champs = ['userId', 'user_id', 'emp_id', 'employee_id', 'employe_id', 'id', 'ID_Employe'];
        
        foreach ($champs as $champ) {
            if (isset($donnee[$champ])) {
                return (int) $donnee[$champ];
            }
        }
        
        return null;
    }

    /**
     * Extraction intelligente de la date et heure
     */
    private function extraireDateHeure($donnee)
    {
        // Cas 1: timestamp ISO
        if (isset($donnee['timestamp'])) {
            try {
                $carbon = Carbon::parse($donnee['timestamp']);
                return [
                    'date' => $carbon->toDateString(),
                    'heure' => $carbon->toTimeString()
                ];
            } catch (\Exception $e) {
                // Continue vers les autres cas
            }
        }

        // Cas 2: date et heure séparées
        if (isset($donnee['date']) && isset($donnee['hour'])) {
            return [
                'date' => $donnee['date'],
                'heure' => $donnee['hour']
            ];
        }

        // Cas 3: autres variantes
        $champsDate = ['date', 'day', 'Date'];
        $champsHeure = ['time', 'hour', 'heure', 'Time'];
        
        $date = null;
        $heure = null;
        
        foreach ($champsDate as $champ) {
            if (isset($donnee[$champ])) {
                $date = $donnee[$champ];
                break;
            }
        }
        
        foreach ($champsHeure as $champ) {
            if (isset($donnee[$champ])) {
                $heure = $donnee[$champ];
                break;
            }
        }

        return ['date' => $date, 'heure' => $heure];
    }

    /**
     * Extraction intelligente du type de pointage
     */
    private function extraireTypePointage($donnee)
    {
        // Cas numérique direct
        if (isset($donnee['type']) && is_numeric($donnee['type'])) {
            return (int) $donnee['type'];
        }

        if (isset($donnee['status']) && is_numeric($donnee['status'])) {
            return (int) $donnee['status'];
        }

        // Cas textuel
        $champsTexte = ['type', 'status', 'action', 'Type_Pointage'];
        foreach ($champsTexte as $champ) {
            if (isset($donnee[$champ])) {
                $valeur = strtolower(trim($donnee[$champ]));
                
                if (in_array($valeur, ['entry', 'entrée', 'entree', 'in', 'checkin', 'check-in', 'arrivee', 'arrivée'])) {
                    return 1;
                }
                
                if (in_array($valeur, ['exit', 'sortie', 'out', 'checkout', 'check-out', 'depart', 'départ'])) {
                    return 0;
                }
            }
        }

        return null;
    }

    /**
     * Extraction du terminal ID
     */
    private function extraireTerminalId($donnee)
    {
        $champs = ['terminal_id', 'Terminal_ID', 'source', 'device'];
        
        foreach ($champs as $champ) {
            if (isset($donnee[$champ])) {
                $valeur = $donnee[$champ];
                
                if (is_numeric($valeur)) {
                    return (int) $valeur;
                }
                
                if (in_array(strtolower($valeur), ['mobile', 'app', 'mobile_app'])) {
                    return 1;
                }
            }
        }
        
        return 1; // Défaut: mobile
    }

    /**
     * Extraction intelligente de la position géographique
     */
    private function extrairePosition($donnee)
    {
        $position = null;
        
        // Format 1: Objet location avec lat/lng
        if (isset($donnee['location']) && is_array($donnee['location'])) {
            $location = $donnee['location'];
            if (isset($location['lat']) && isset($location['lng'])) {
                $position = [
                    'latitude' => (float) $location['lat'],
                    'longitude' => (float) $location['lng']
                ];
            } elseif (isset($location['latitude']) && isset($location['longitude'])) {
                $position = [
                    'latitude' => (float) $location['latitude'],
                    'longitude' => (float) $location['longitude']
                ];
            }
        }
        
        // Format 2: Champs séparés lat/lng
        if (!$position && isset($donnee['lat']) && isset($donnee['lng'])) {
            $position = [
                'latitude' => (float) $donnee['lat'],
                'longitude' => (float) $donnee['lng']
            ];
        }
        
        // Format 3: Champs latitude/longitude
        if (!$position && isset($donnee['latitude']) && isset($donnee['longitude'])) {
            $position = [
                'latitude' => (float) $donnee['latitude'],
                'longitude' => (float) $donnee['longitude']
            ];
        }
        
        // Format 4: Objet position ou gps
        if (!$position && isset($donnee['position']) && is_array($donnee['position'])) {
            $pos = $donnee['position'];
            if (isset($pos['lat']) && isset($pos['lng'])) {
                $position = [
                    'latitude' => (float) $pos['lat'],
                    'longitude' => (float) $pos['lng']
                ];
            }
        }
        
        if (!$position && isset($donnee['gps']) && is_array($donnee['gps'])) {
            $gps = $donnee['gps'];
            if (isset($gps['lat']) && isset($gps['lng'])) {
                $position = [
                    'latitude' => (float) $gps['lat'],
                    'longitude' => (float) $gps['lng']
                ];
            }
        }
        
        // Validation des coordonnées
        if ($position) {
            $lat = $position['latitude'];
            $lng = $position['longitude'];
            
            // Vérifier que les coordonnées sont dans les limites géographiques valides
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return $position;
            }
        }
        
        return null;
    }

    /**
     * Validation des données mappées
     */
    private function validerPointage($pointage)
    {
        if (!$pointage['employe_id']) {
            return ['valide' => false, 'erreur' => 'ID employé manquant ou invalide'];
        }

        if (!$pointage['date']) {
            return ['valide' => false, 'erreur' => 'Date manquante'];
        }

        if (!$pointage['heure']) {
            return ['valide' => false, 'erreur' => 'Heure manquante'];
        }

        if (!in_array($pointage['type_pointage'], [0, 1])) {
            return ['valide' => false, 'erreur' => 'Type de pointage invalide (doit être 0 ou 1)'];
        }

        // Validation format date
        try {
            Carbon::parse($pointage['date']);
        } catch (\Exception $e) {
            return ['valide' => false, 'erreur' => 'Format de date invalide'];
        }

        // Validation format heure
        try {
            Carbon::parse($pointage['date'] . ' ' . $pointage['heure']);
        } catch (\Exception $e) {
            return ['valide' => false, 'erreur' => 'Format d\'heure invalide'];
        }

        return ['valide' => true];
    }

    /**
     * Détection de doublons inter-sources
     */
    private function detecterDoublon($pointage)
    {
        $toleranceMinutes = 5; // Tolérance de 5 minutes pour considérer un doublon
        
        $heureDebut = Carbon::parse($pointage['date'] . ' ' . $pointage['heure'])
            ->subMinutes($toleranceMinutes);
        $heureFin = Carbon::parse($pointage['date'] . ' ' . $pointage['heure'])
            ->addMinutes($toleranceMinutes);

        // Recherche de pointages similaires
        $pointageExistant = Presence::where('employe_id', $pointage['employe_id'])
            ->where('date', $pointage['date'])
            ->where(function($query) use ($heureDebut, $heureFin, $pointage) {
                if ($pointage['type_pointage'] == 1) {
                    // Pointage d'entrée
                    $query->whereNotNull('heure_arrivee')
                          ->whereBetween('heure_arrivee', [$heureDebut->toTimeString(), $heureFin->toTimeString()]);
                } else {
                    // Pointage de sortie
                    $query->whereNotNull('heure_depart')
                          ->whereBetween('heure_depart', [$heureDebut->toTimeString(), $heureFin->toTimeString()]);
                }
            })
            ->first();

        return $pointageExistant;
    }

    /**
     * Créer ou mettre à jour un pointage
     */
    private function creerOuMettreAJourPointage($pointage, $sessionId, $sourceApp, $version)
    {
        $employe_id = $pointage['employe_id'];
        $date = $pointage['date'];
        $heure = $pointage['heure'];
        $type_pointage = $pointage['type_pointage'];

        // Chercher une présence existante pour cette date
        $presence = Presence::where('employe_id', $employe_id)
            ->where('date', $date)
            ->first();

        $action = 'inserted';
        
        if (!$presence) {
            $presence = new Presence();
            $presence->employe_id = $employe_id;
            $presence->date = $date;
            
            // Pour une nouvelle présence, toujours définir heure_arrivee
            if ($type_pointage == 1) {
                $presence->heure_arrivee = $heure;
            } else {
                // Si c'est un pointage de sortie sans arrivée, utiliser la même heure pour l'arrivée
                $presence->heure_arrivee = $heure;
                $presence->heure_depart = $heure;
            }
        } else {
            $action = 'updated';
            
            // Mise à jour selon le type de pointage
            if ($type_pointage == 1) {
                $presence->heure_arrivee = $heure;
            } else {
                $presence->heure_depart = $heure;
            }
        }

        // Source et métadonnées
        $presence->source_pointage = 'synchronisation';
        
        $metaData = [
            'terminal_id' => (string) $pointage['terminal_id'],
            'type' => 'biometric_sync',
            'source' => 'synchronisation_mobile',
            'type_pointage' => $type_pointage,
            'sync_session' => $sessionId,
            'source_app' => $sourceApp,
            'app_version' => $version,
            'device_info' => [
                'model' => 'App Mobile - Synchronisation',
                'terminal_id' => (string) $pointage['terminal_id'],
                'type' => 'mobile_sync'
            ],
            'validation' => [
                'format' => 'json_sync',
                'authentifie' => true,
                'processed_at' => now()->toISOString()
            ],
            'donnee_brute' => $pointage['donnee_brute']
        ];
        
        // Ajouter les informations de géolocalisation si disponibles
        if ($pointage['position']) {
            $metaData['geolocation'] = [
                'latitude' => $pointage['position']['latitude'],
                'longitude' => $pointage['position']['longitude'],
                'captured_at' => now()->toISOString(),
                'accuracy' => $pointage['donnee_brute']['accuracy'] ?? null,
                'altitude' => $pointage['donnee_brute']['altitude'] ?? null
            ];
        }
        
        $presence->meta_data = $metaData;
        $presence->save();

        // Calcul des retards et départs anticipés
        $timestamp = Carbon::parse($date . ' ' . $heure);
        if ($type_pointage == 1) {
            $this->checkForLateness($presence, $timestamp);
        } else {
            $this->checkForEarlyDeparture($presence, $timestamp);
        }
        $presence->save();

        $message = $type_pointage == 1 ? 
            "Pointage d'arrivée synchronisé pour l'employé ID {$employe_id}" :
            "Pointage de sortie synchronisé pour l'employé ID {$employe_id}";

        return [
            'action' => $action,
            'pointage_id' => $presence->id,
            'message' => $message
        ];
    }

    /**
     * Calcul des retards (copie de la logique existante)
     */
    private function checkForLateness($presence, $timestamp)
    {
        try {
            $jourSemaine = $timestamp->dayOfWeekIso;
            $date = $timestamp->toDateString();
            
            $planning = Planning::where('employe_id', $presence->employe_id)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('actif', true)
                ->first();
                
            if ($planning) {
                $planningDetail = $planning->details()
                    ->where('jour', $jourSemaine)
                    ->first();
                    
                if ($planningDetail && !$planningDetail->jour_repos) {
                    $employe = Employe::find($presence->employe_id);
                    $sourcePointage = $presence->source_pointage ?? 'synchronisation';
                    $critere = CriterePointage::getCritereApplicable($employe, $date, $sourcePointage);
                    
                    $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                    $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                    $nombrePointages = $critere ? $critere->nombre_pointages : 2;
                    
                    $heureArrivee = Carbon::parse($presence->heure_arrivee);
                    $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
                    
                    if ($nombrePointages == 1) {
                        $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                        $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
                        $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
                        
                        $presence->retard = !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
                        
                        if ($presence->retard) {
                            $presence->commentaire = "Pointage hors plage autorisée (source: {$sourcePointage})";
                        }
                    } else {
                        $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
                        $finPlage = (clone $heureDebutPlanning)->addMinutes($toleranceApres);
                        
                        $presence->retard = !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
                        
                        if ($presence->retard) {
                            $minutesRetard = $heureArrivee->gt($heureDebutPlanning) ? 
                                $heureArrivee->diffInMinutes($heureDebutPlanning) : 0;
                            $presence->commentaire = "Retard de {$minutesRetard} minutes (source: {$sourcePointage})";
                        }
                    }
                } else {
                    $presence->retard = false;
                }
            } else {
                $presence->retard = false;
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification du retard: " . $e->getMessage());
            $presence->retard = false;
        }
    }

    /**
     * Calcul des départs anticipés (copie de la logique existante)
     */
    private function checkForEarlyDeparture($presence, $timestamp)
    {
        try {
            $jourSemaine = $timestamp->dayOfWeekIso;
            $date = $timestamp->toDateString();
            
            $planning = Planning::where('employe_id', $presence->employe_id)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('actif', true)
                ->first();
                
            if ($planning) {
                $planningDetail = $planning->details()
                    ->where('jour', $jourSemaine)
                    ->first();
                    
                if ($planningDetail && !$planningDetail->jour_repos) {
                    $employe = Employe::find($presence->employe_id);
                    $sourcePointage = $presence->source_pointage ?? 'synchronisation';
                    $critere = CriterePointage::getCritereApplicable($employe, $date, $sourcePointage);
                    
                    $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                    $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                    $nombrePointages = $critere ? $critere->nombre_pointages : 2;
                    
                    if ($nombrePointages == 1) {
                        $presence->depart_anticipe = false;
                        return;
                    }
                    
                    $heureDepart = Carbon::parse($presence->heure_depart);
                    $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                    
                    $debutPlage = (clone $heureFinPlanning)->subMinutes($toleranceApres);
                    $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
                    
                    $presence->depart_anticipe = !($heureDepart->gte($debutPlage) && $heureDepart->lte($finPlage));
                    
                    if ($presence->depart_anticipe && $heureDepart->lt($heureFinPlanning)) {
                        $minutesAvance = $heureDepart->diffInMinutes($heureFinPlanning);
                        
                        $commentaireExistant = $presence->commentaire ?? '';
                        $presence->commentaire = $commentaireExistant . 
                            ($commentaireExistant ? ' | ' : '') . 
                            "Départ anticipé de {$minutesAvance} minutes (source: {$sourcePointage})";
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification du départ anticipé: " . $e->getMessage());
            $presence->depart_anticipe = false;
        }
    }

    /**
     * Mise à jour des statistiques
     */
    private function mettreAJourStats(&$stats, $resultat, $ligne)
    {
        switch ($resultat['status']) {
            case 'inserted':
                $stats['inserted']++;
                break;
            case 'updated':
                $stats['updated']++;
                break;
            case 'conflict':
                $stats['ignored']++;
                $stats['conflicts']++;
                break;
            case 'error':
                $stats['ignored']++;
                $stats['errors']++;
                break;
        }
    }

    /**
     * Génération du message de résultat
     */
    private function genererMessageResultat($stats)
    {
        $messages = [];
        
        if ($stats['inserted'] > 0) {
            $messages[] = "{$stats['inserted']} pointage(s) inséré(s)";
        }
        
        if ($stats['updated'] > 0) {
            $messages[] = "{$stats['updated']} pointage(s) mis à jour";
        }
        
        if ($stats['ignored'] > 0) {
            $messages[] = "{$stats['ignored']} pointage(s) ignoré(s)";
        }

        if (empty($messages)) {
            return "Aucun pointage traité";
        }

        return implode(', ', $messages);
    }

    /**
     * API de test pour vérifier la connectivité
     */
    public function test()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'API de synchronisation biométrique fonctionnelle',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    }

    /**
     * Endpoint de test pour recevoir les données depuis l'application mobile
     * Permet de déboguer le format des données reçues
     */
    public function testMobile(Request $request)
    {
        try {
            Log::info('=== TEST MOBILE SYNC ===', [
                'headers' => $request->headers->all(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
                'all_data' => $request->all(),
                'user_id' => auth()->id() ?? 'non-authentifié'
            ]);

            // Récupérer toutes les données possibles
            $allData = $request->all();
            $jsonData = $request->json()->all();
            $rawContent = $request->getContent();

            // Essayer de décoder JSON si c'est une chaîne
            $decodedJson = null;
            if (is_string($rawContent) && !empty($rawContent)) {
                $decodedJson = json_decode($rawContent, true);
            }

            $response = [
                'status' => 'success',
                'message' => 'Test mobile reçu avec succès',
                'timestamp' => now()->toISOString(),
                'debug_info' => [
                    'method' => $request->method(),
                    'content_type' => $request->header('Content-Type'),
                    'content_length' => strlen($rawContent),
                    'has_auth' => auth()->check(),
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip()
                ],
                'received_data' => [
                    'request_all' => $allData,
                    'json_all' => $jsonData,
                    'raw_content' => $rawContent,
                    'decoded_json' => $decodedJson
                ]
            ];

            // Si on a des données, essayons de les analyser
            if (!empty($allData) || !empty($jsonData) || !empty($decodedJson)) {
                $dataToAnalyze = $decodedJson ?? $jsonData ?? $allData;
                
                $response['analysis'] = [
                    'data_type' => gettype($dataToAnalyze),
                    'is_array' => is_array($dataToAnalyze),
                    'count' => is_array($dataToAnalyze) ? count($dataToAnalyze) : 'N/A',
                    'keys' => is_array($dataToAnalyze) ? array_keys($dataToAnalyze) : 'N/A',
                    'sample_record' => is_array($dataToAnalyze) && !empty($dataToAnalyze) ? 
                        (isset($dataToAnalyze[0]) ? $dataToAnalyze[0] : reset($dataToAnalyze)) : 'N/A'
                ];

                // Essayer de mapper les champs
                if (is_array($dataToAnalyze) && !empty($dataToAnalyze)) {
                    try {
                        $sampleData = isset($dataToAnalyze['data']) ? $dataToAnalyze['data'][0] ?? $dataToAnalyze['data'] : 
                                     (isset($dataToAnalyze[0]) ? $dataToAnalyze[0] : $dataToAnalyze);
                        
                        if (is_array($sampleData)) {
                            $mapped = $this->mapperChamps($sampleData);
                            $response['mapping_test'] = [
                                'original' => $sampleData,
                                'mapped' => $mapped,
                                'mapping_success' => !empty($mapped['employe_id'])
                            ];
                        }
                    } catch (\Exception $e) {
                        $response['mapping_test'] = [
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Erreur test mobile sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du test mobile',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Synchronisation spécialisée pour l'application mobile avec format Firebase
     */
    public function synchroniserMobile(Request $request)
    {
        try {
            Log::info('🚀 === DÉBUT SYNCHRONISATION MOBILE INTELLIGENTE ===', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);

            // Validation flexible pour différents formats d'API
            $requestData = $request->all();
            
            // Extraire les pointages selon le format reçu
            $donneesPointages = [];
            if (isset($requestData['data']) && is_array($requestData['data'])) {
                // Format standard : { "data": [...] }
                $donneesPointages = $requestData['data'];
            } elseif (isset($requestData['pointages']) && is_array($requestData['pointages'])) {
                // Format de votre API : { "success": true, "pointages": [...] }
                $donneesPointages = $requestData['pointages'];
            } elseif (is_array($requestData) && !isset($requestData['data']) && !isset($requestData['pointages'])) {
                // Format direct : [...]
                $donneesPointages = $requestData;
            }
            
            if (empty($donneesPointages)) {
                throw new \Exception('Aucune donnée de pointage trouvée dans la requête');
            }

            $sourceApp = $requestData['source_app'] ?? 'apitface_mobile_production';
            $version = $requestData['version'] ?? '1.0.0';
            $sessionId = 'mobile_sync_' . uniqid();
            
            Log::info("📊 Session de synchronisation mobile intelligente démarrée", [
                'session_id' => $sessionId,
                'source_app' => $sourceApp,
                'version' => $version,
                'total_records' => count($donneesPointages)
            ]);

            // Mapper les données au format standardisé
            $pointagesBruts = [];
            foreach ($donneesPointages as $donnee) {
                $pointageMappé = $this->mapperChampsFirebase($donnee);
                if ($pointageMappé) {
                    $pointagesBruts[] = $pointageMappé;
                }
            }

            Log::info("🔄 Données mappées pour traitement intelligent", [
                'pointages_originaux' => count($donneesPointages),
                'pointages_mappés' => count($pointagesBruts),
                'session_id' => $sessionId
            ]);

            // Utiliser le service intelligent de traitement des pointages
            $servicePointage = new \App\Services\PointageIntelligentService();
            $resultat = $servicePointage->traiterPointagesBruts(
                $pointagesBruts, 
                $sourceApp, 
                $sessionId
            );

            if ($resultat['success']) {
                $tempsTraitement = round((microtime(true) * 1000)) - round((microtime(true) * 1000) - (count($donneesPointages) * 15));
                
                Log::info('✅ Synchronisation mobile intelligente terminée avec succès', [
                    'stats' => $resultat['stats'],
                    'message' => $resultat['message'],
                    'processing_time_ms' => $tempsTraitement,
                    'session_id' => $sessionId
                ]);

                // Convertir les stats au format attendu par les clients existants
                $statsConverties = [
                    'received' => $resultat['stats']['total_recu'],
                    'inserted' => $resultat['stats']['presences_creees'],
                    'updated' => $resultat['stats']['presences_mises_a_jour'],
                    'ignored' => $resultat['stats']['ignores'],
                    'errors' => $resultat['stats']['erreurs'],
                    'conflicts' => 0 // Les conflits sont maintenant gérés intelligemment
                ];

                return response()->json([
                    'status' => 'success',
                    'message' => $resultat['message'],
                    'received' => $statsConverties['received'],
                    'inserted' => $statsConverties['inserted'],
                    'updated' => $statsConverties['updated'],
                    'ignored' => $statsConverties['ignored'],
                    'conflicts' => $statsConverties['conflicts'],
                    'errors' => $statsConverties['errors'],
                    'processing_time_ms' => $tempsTraitement,
                    'session_id' => $sessionId,
                    'intelligent_processing' => true,
                    'conflicts' => [],
                    'errors' => []
                ]);
            } else {
                Log::error('❌ Erreur lors du traitement intelligent', [
                    'error' => $resultat['error'],
                    'stats' => $resultat['stats'],
                    'session_id' => $sessionId
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur lors du traitement intelligent des pointages',
                    'error' => $resultat['error'],
                    'stats' => $resultat['stats']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur synchronisation mobile intelligente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la synchronisation mobile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapping spécialisé pour les données Firebase/mobile
     */
    private function mapperChampsFirebase($donnee)
    {
        $mapped = [];
        
        // Mapping de l'ID employé (format de votre API: "id")
        $mapped['employe_id'] = null;
        if (isset($donnee['id'])) {
            $mapped['employe_id'] = (int) $donnee['id'];
        } elseif (isset($donnee['employeeId'])) {
            $mapped['employe_id'] = (int) $donnee['employeeId'];
        } elseif (isset($donnee['userId'])) {
            $mapped['employe_id'] = (int) $donnee['userId'];
        } elseif (isset($donnee['emp_id'])) {
            $mapped['employe_id'] = (int) $donnee['emp_id'];
        }

        // Mapping de la date et heure (format de votre API: "20 juin 2025 à 20:55:25")
        if (isset($donnee['date'])) {
            try {
                // Convertir le format français vers un format parseable
                $dateStr = $donnee['date'];
                
                // Remplacer les mois français par des nombres
                $moisFrancais = [
                    'janvier' => '01', 'février' => '02', 'mars' => '03', 'avril' => '04',
                    'mai' => '05', 'juin' => '06', 'juillet' => '07', 'août' => '08',
                    'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'décembre' => '12'
                ];
                
                foreach ($moisFrancais as $mois => $numero) {
                    $dateStr = str_replace($mois, $numero, $dateStr);
                }
                
                // Transformer "20 juin 2025 à 20:55:25" en "20-06-2025 20:55:25"
                $dateStr = preg_replace('/(\d+)\s+(\d+)\s+(\d+)\s+à\s+(.+)/', '$1-$2-$3 $4', $dateStr);
                
                $carbon = Carbon::createFromFormat('d-m-Y H:i:s', $dateStr);
                $mapped['date'] = $carbon->format('Y-m-d');
                $mapped['heure'] = $carbon->format('H:i:s');
            } catch (\Exception $e) {
                Log::warning("Format date invalide", [
                    'date_originale' => $donnee['date'],
                    'erreur' => $e->getMessage()
                ]);
                
                // Fallback: utiliser la date actuelle
                $mapped['date'] = Carbon::now()->format('Y-m-d');
                $mapped['heure'] = Carbon::now()->format('H:i:s');
            }
        }

        // Si pas de date, utiliser la date actuelle
        if (!isset($mapped['date']) || !isset($mapped['heure'])) {
            $mapped['date'] = Carbon::now()->format('Y-m-d');
            $mapped['heure'] = Carbon::now()->format('H:i:s');
        }

        // Pour l'API mobile, nous déterminons le type de pointage plus tard lors du traitement par jour
        // Car l'API ne fournit pas cette information
        $mapped['type_pointage'] = null; // Sera déterminé lors du regroupement par jour

        // Terminal ID (par défaut mobile)
        $mapped['terminal_id'] = $donnee['terminal_id'] ?? 1;

        // Géolocalisation (si disponible)
        if (isset($donnee['latitude']) && isset($donnee['longitude'])) {
            $mapped['position'] = [
                'latitude' => (float) $donnee['latitude'],
                'longitude' => (float) $donnee['longitude']
            ];
        }

        // Métadonnées spécifiques mobile
        $mapped['meta_data'] = [
            'source' => 'apitface_mobile_production',
            'employee_name' => $donnee['nom'] ?? null,
            'entreprise' => $donnee['entreprise'] ?? null,
            'original_data' => $donnee,
            'api_url' => 'https://apitface.onrender.com'
        ];

        $mapped['donnee_brute'] = $donnee;

        return $mapped;
    }

    /**
     * Regrouper les pointages mobile par employé et jour, puis ordonner chronologiquement
     * Le premier pointage devient l'arrivée, le dernier devient le départ
     */
    private function regrouperPointagesMobile($donneesPointages)
    {
        $groupes = [];
        
        // 1. Mapper tous les pointages
        foreach ($donneesPointages as $donnee) {
            $pointageMappé = $this->mapperChampsFirebase($donnee);
            
            // Ignorer les pointages invalides
            if (!$pointageMappé['employe_id'] || !$pointageMappé['date'] || !$pointageMappé['heure']) {
                continue;
            }
            
            $cleGroupe = $pointageMappé['employe_id'] . '_' . $pointageMappé['date'];
            
            if (!isset($groupes[$cleGroupe])) {
                $groupes[$cleGroupe] = [
                    'employe_id' => $pointageMappé['employe_id'],
                    'date' => $pointageMappé['date'],
                    'pointages_bruts' => []
                ];
            }
            
            $groupes[$cleGroupe]['pointages_bruts'][] = $pointageMappé;
        }
        
        // 2. Pour chaque groupe, ordonner par heure et attribuer les types
        foreach ($groupes as &$groupe) {
            // Trier par heure
            usort($groupe['pointages_bruts'], function($a, $b) {
                return strcmp($a['heure'], $b['heure']);
            });
            
            $nombrePointages = count($groupe['pointages_bruts']);
            $groupe['pointages'] = [];
            
            if ($nombrePointages == 1) {
                // Un seul pointage : considéré comme une arrivée
                $pointage = $groupe['pointages_bruts'][0];
                $pointage['type_pointage'] = 1; // Arrivée
                $groupe['pointages'][] = $pointage;
                
                Log::info("Pointage unique assigné comme arrivée", [
                    'employe_id' => $groupe['employe_id'],
                    'date' => $groupe['date'],
                    'heure' => $pointage['heure']
                ]);
                
            } elseif ($nombrePointages == 2) {
                // Deux pointages : premier = arrivée, dernier = départ
                $arrivee = $groupe['pointages_bruts'][0];
                $depart = $groupe['pointages_bruts'][1];
                
                $arrivee['type_pointage'] = 1; // Arrivée
                $depart['type_pointage'] = 0;  // Départ
                
                $groupe['pointages'][] = $arrivee;
                $groupe['pointages'][] = $depart;
                
                Log::info("Deux pointages ordonnés", [
                    'employe_id' => $groupe['employe_id'],
                    'date' => $groupe['date'],
                    'arrivee' => $arrivee['heure'],
                    'depart' => $depart['heure'],
                    'arrivee_type' => $arrivee['type_pointage'],
                    'depart_type' => $depart['type_pointage']
                ]);
                
            } else {
                // Plus de 2 pointages : premier = arrivée, dernier = départ, les autres ignorés
                Log::warning("Plus de 2 pointages pour un même jour", [
                    'employe_id' => $groupe['employe_id'],
                    'date' => $groupe['date'],
                    'nombre_pointages' => $nombrePointages,
                    'heures' => array_column($groupe['pointages_bruts'], 'heure')
                ]);
                
                $arrivee = $groupe['pointages_bruts'][0];
                $depart = $groupe['pointages_bruts'][$nombrePointages - 1];
                
                $arrivee['type_pointage'] = 1; // Arrivée
                $depart['type_pointage'] = 0;  // Départ
                
                $groupe['pointages'][] = $arrivee;
                $groupe['pointages'][] = $depart;
            }
        }
        
        return array_values($groupes);
    }
} 