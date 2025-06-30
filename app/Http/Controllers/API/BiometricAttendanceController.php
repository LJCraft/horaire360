<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Presence;
use App\Models\Planning;
use App\Models\Employe;
use App\Models\CriterePointage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class BiometricAttendanceController extends Controller
{
    /**
     * Enregistrer l'arrivée d'un employé avec données biométriques
     */
    public function checkIn(Request $request)
    {
        // Valider les données reçues
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employes,id',
            'timestamp' => 'required|date_format:Y-m-d\TH:i:sP',
            'location' => 'required|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.accuracy' => 'required|numeric',
            'biometric_verification' => 'required|array',
            'biometric_verification.hash' => 'required|string',
            'biometric_verification.confidence_score' => 'required|numeric|min:0.7',
            'device_info' => 'required|array',
            'device_info.device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer les données validées
        $data = $validator->validated();
        
        // Parser le timestamp
        $timestamp = Carbon::parse($data['timestamp']);
        $date = $timestamp->toDateString();
        $time = $timestamp->toTimeString();
        
        // Vérifier si un enregistrement existe déjà pour cet employé à cette date
        $existingPresence = Presence::where('employe_id', $data['employee_id'])
            ->where('date', $date)
            ->first();
            
        if ($existingPresence) {
            return response()->json([
                'success' => false,
                'message' => 'L\'employé a déjà pointé aujourd\'hui',
                'presence' => $existingPresence
            ], 409);
        }

        // Récupérer le planning de l'employé pour ce jour
        $jourSemaine = $timestamp->dayOfWeekIso; // 1 (lundi) à 7 (dimanche)
        
        // Trouver le planning actif pour cet employé
        $planning = Planning::where('employe_id', $data['employee_id'])
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('statut', 'actif')
            ->first();
            
        $retard = false;
        $commentaire = null;
        
        if ($planning) {
            // Récupérer le détail du planning pour ce jour de la semaine
            $planningDetail = $planning->details()
                ->where('jour', $jourSemaine)
                ->first();
                
            if ($planningDetail && !$planningDetail->jour_repos) {
                // Récupérer les critères de pointage applicables
                $employe = Employe::find($data['employee_id']);
                $critere = CriterePointage::getCritereApplicable($employe, $date);
                
                // Utiliser les critères configurés ou les valeurs par défaut
                $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                $nombrePointages = $critere ? $critere->nombre_pointages : 2;
                
                $heureArrivee = Carbon::parse($time);
                $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
                
                if ($nombrePointages == 1) {
                    // Si un seul pointage est requis, on vérifie que le pointage est dans la plage
                    // [heure début - tolérance] -> [heure fin + tolérance]
                    $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                    $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
                    $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
                    
                    if (!($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage))) {
                        $retard = true;
                        $commentaire = "Pointage hors plage autorisée";
                    }
                } else {
                    // Si deux pointages sont requis, on vérifie que l'arrivée est dans la plage
                    // [heure début - tolérance] -> [heure début + tolérance]
                    $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
                    $finPlage = (clone $heureDebutPlanning)->addMinutes($toleranceApres);
                    
                    if (!($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage))) {
                        $retard = true;
                        $minutesRetard = $heureArrivee->diffInMinutes($heureDebutPlanning);
                        $commentaire = "Retard de {$minutesRetard} minutes";
                    }
                }
            }
        }

        // Créer l'enregistrement de présence
        $presence = new Presence();
        $presence->employe_id = $data['employee_id'];
        $presence->date = $date;
        $presence->heure_arrivee = $time;
        $presence->retard = $retard;
        $presence->commentaire = $commentaire;
        $presence->source_pointage = 'biometrique';
        
        // Stocker les données biométriques et de localisation
        $metaData = [
            'location' => $data['location'],
            'biometric_verification' => [
                'hash' => $data['biometric_verification']['hash'],
                'confidence_score' => $data['biometric_verification']['confidence_score']
            ],
            'device_info' => $data['device_info']
        ];
        
        $presence->meta_data = $metaData;
        
        // Calculer les heures prévues et faites
        $employe = Employe::find($data['employee_id']);
        $critere = CriterePointage::getCritereApplicable($employe, $date);
        
        if ($critere && $critere->nombre_pointages == 1 && !$retard) {
            // Si un seul pointage est requis et l'employé n'est pas en retard,
            // on calcule les heures prévues et faites
            $heures = $presence->calculerHeures();
            $presence->heures_prevues = $heures['heures_prevues'];
            $presence->heures_faites = $heures['heures_faites'];
        }
        
        $presence->save();

        return response()->json([
            'success' => true,
            'message' => 'Pointage d\'arrivée enregistré avec succès',
            'presence' => $presence,
            'retard' => $retard
        ], 201);
    }

    /**
     * Enregistrer le départ d'un employé
     */
    public function checkOut(Request $request)
    {
        // Valider les données reçues
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employes,id',
            'timestamp' => 'required|date_format:Y-m-d\TH:i:sP',
            'location' => 'required|array',
            'location.latitude' => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'location.accuracy' => 'required|numeric',
            'biometric_verification' => 'required|array',
            'biometric_verification.hash' => 'required|string',
            'biometric_verification.confidence_score' => 'required|numeric|min:0.7',
            'device_info' => 'required|array',
            'device_info.device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer les données validées
        $data = $validator->validated();
        
        // Parser le timestamp
        $timestamp = Carbon::parse($data['timestamp']);
        $date = $timestamp->toDateString();
        $time = $timestamp->toTimeString();
        
        // Récupérer l'enregistrement de présence existant
        $presence = Presence::where('employe_id', $data['employee_id'])
            ->where('date', $date)
            ->first();
            
        if (!$presence) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun pointage d\'arrivée trouvé pour cet employé aujourd\'hui'
            ], 404);
        }
        
        if ($presence->heure_depart) {
            return response()->json([
                'success' => false,
                'message' => 'L\'employé a déjà pointé son départ aujourd\'hui'
            ], 409);
        }

        // Récupérer le planning de l'employé pour ce jour
        $jourSemaine = $timestamp->dayOfWeekIso; // 1 (lundi) à 7 (dimanche)
        
        // Trouver le planning actif pour cet employé
        $planning = Planning::where('employe_id', $data['employee_id'])
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('statut', 'actif')
            ->first();
            
        $departAnticipe = false;
        $commentaire = null;
        
        // Récupérer les critères de pointage applicables
        $employe = Employe::find($data['employee_id']);
        $critere = CriterePointage::getCritereApplicable($employe, $date);
        
        // Si un seul pointage est requis, pas de départ anticipé
        if ($critere && $critere->nombre_pointages == 1) {
            $departAnticipe = false;
        } else if ($planning) {
            // Récupérer le détail du planning pour ce jour de la semaine
            $planningDetail = $planning->details()
                ->where('jour', $jourSemaine)
                ->first();
                
            if ($planningDetail && !$planningDetail->jour_repos) {
                // Utiliser les critères configurés ou les valeurs par défaut
                $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                
                // Calculer si l'employé est parti avant l'heure prévue
                $heureDepart = Carbon::parse($time);
                $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                
                // Pour deux pointages, on vérifie que le départ est dans la plage
                // [heure fin - tolérance] -> [heure fin + tolérance]
                $debutPlage = (clone $heureFinPlanning)->subMinutes($toleranceApres);
                $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
                
                if (!($heureDepart->gte($debutPlage) && $heureDepart->lte($finPlage))) {
                    $departAnticipe = true;
                    $minutesAvance = $heureFinPlanning->diffInMinutes($heureDepart);
                    $commentaire = "Départ anticipé de {$minutesAvance} minutes";
                }
            }
        }

        $metaData = $presence->meta_data ?? [];
        $metaData['checkout'] = [
            'location' => $data['location'],
            'biometric_verification' => [
                'hash' => $data['biometric_verification']['hash'],
                'confidence_score' => $data['biometric_verification']['confidence_score']
            ],
            'device_info' => $data['device_info']
        ];
        
        // Mettre à jour l'enregistrement de présence
        $presence->heure_depart = $time;
        $presence->depart_anticipe = $departAnticipe;
        $presence->meta_data = $metaData;
        $presence->save();

        return response()->json([
            'success' => true,
            'message' => 'Pointage de départ enregistré avec succès',
            'presence' => $presence,
            'depart_anticipe' => $departAnticipe
        ], 200);
    }

    /**
     * Récupérer l'historique des pointages d'un employé
     */
    public function history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employes,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        $presences = Presence::where('employe_id', $data['employee_id'])
            ->whereBetween('date', [$data['start_date'], $data['end_date']])
            ->orderBy('date', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'presences' => $presences
        ], 200);
    }
} 