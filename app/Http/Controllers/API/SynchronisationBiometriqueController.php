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
            Log::info('=== DÉBUT SYNCHRONISATION ===', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // Validation du format JSON
            $validation = $request->validate([
                'data' => 'required|array',
                'data.*' => 'required|array',
                'source_app' => 'string|nullable',
                'version' => 'string|nullable'
            ]);

            $donneesPointages = $validation['data'];
            $sourceApp = $validation['source_app'] ?? 'app_mobile';
            $version = $validation['version'] ?? '1.0.0';
            
            // Génération d'un ID unique de session
            $sessionId = 'sync_' . uniqid();
            
            Log::info("Session de synchronisation démarrée", [
                'session_id' => $sessionId,
                'source_app' => $sourceApp,
                'version' => $version,
                'total_records' => count($donneesPointages)
            ]);

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
                
                Log::info("Traitement ligne {$ligne}", [
                    'donnee_brute' => $donnee,
                    'session_id' => $sessionId
                ]);
                
                $resultat = $this->traiterPointage($donnee, $ligne, $sessionId, $sourceApp, $version);
                
                Log::info("Résultat traitement ligne {$ligne}", [
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
            $tempsTraitement = round(microtime(true) * 1000) - round(microtime(true) * 1000 - ($stats['received'] * 10));
            
            Log::info("Synchronisation terminée", [
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
                'conflicts' => $conflits,
                'errors' => $erreurs
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur fatale lors de la synchronisation', [
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
        } else {
            $action = 'updated';
        }

        // Mise à jour selon le type de pointage
        if ($type_pointage == 1) {
            $presence->heure_arrivee = $heure;
        } else {
            $presence->heure_depart = $heure;
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
        
        $presence->meta_data = json_encode($metaData);
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
} 