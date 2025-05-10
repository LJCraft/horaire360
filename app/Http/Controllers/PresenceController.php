<?php

namespace App\Http\Controllers;

use App\Exports\PresencesExport;
use App\Exports\PresencesTemplateExport;
use App\Imports\PresencesImport;
use App\Models\Presence;
use App\Models\Employe;
use App\Models\Planning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    /**
     * Afficher la liste des présences.
     */
    public function index(Request $request)
    {
        // Paramètres de filtrage
        $employe = $request->query('employe');
        $date = $request->query('date');
        $retard = $request->query('retard');
        $departAnticipe = $request->query('depart_anticipe');
        
        // Filtrage par employé pour les utilisateurs non admin
        if (!auth()->user()->is_admin && auth()->user()->employe) {
            $employe = auth()->user()->employe->id;
        }
        
        // Date par défaut : aujourd'hui
        if (!$date) {
            $date = Carbon::now()->format('Y-m-d');
        }
        
        // Construction de la requête
        $presencesQuery = Presence::with('employe');
        
        // Filtre par employé
        if ($employe) {
            $presencesQuery->where('employe_id', $employe);
        }
        
        // Filtre par date
        if ($date) {
            $presencesQuery->where('date', $date);
        }
        
        // Filtre par retard
        if ($retard !== null && $retard !== '') {
            $presencesQuery->where('retard', $retard);
        }
        
        // Filtre par départ anticipé
        if ($departAnticipe !== null && $departAnticipe !== '') {
            $presencesQuery->where('depart_anticipe', $departAnticipe);
        }
        
        // Récupération des présences
        $presences = $presencesQuery->orderBy('date', 'desc')->orderBy('heure_arrivee')->paginate(15);
        
        // Récupération des employés pour le filtre
        $employes = Employe::orderBy('nom')->orderBy('prenom')->get();
        
        return view('presences.index', compact('presences', 'employes', 'employe', 'date', 'retard', 'departAnticipe'));
    }
    
    /**
     * Afficher le formulaire de création d'une présence.
     */
    public function create()
    {
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get();
        return view('presences.create', compact('employes'));
    }
    
    /**
     * Stocker une nouvelle présence.
     */
    public function store(Request $request)
    {
        try {
            // Validation des données
            $validatedData = $request->validate([
                'employe_id' => 'required|exists:employes,id',
                'date' => 'required|date',
                'heure_arrivee' => 'required|date_format:H:i',
                'heure_depart' => 'nullable|date_format:H:i',
                'commentaire' => 'nullable|string',
            ]);
    
            // Recherche du planning
            $planning = Planning::where('employe_id', $validatedData['employe_id'])
                ->where('date_debut', '<=', $validatedData['date'])
                ->where('date_fin', '>=', $validatedData['date'])
                ->first();
    
            // Déterminer le retard
            $retard = false;
            if ($planning) {
                // Créer les objets Carbon pour les heures
                $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut);
                $heureArrivee = \Carbon\Carbon::parse($validatedData['heure_arrivee']);
                
                // Ajouter 10 minutes au début du planning
                $heureDebutPlanning->addMinutes(10);
                
                // Comparer les heures
                $retard = $heureArrivee->gt($heureDebutPlanning);
            }
    
            // Ajout des champs de retard
            $validatedData['retard'] = $retard;
            $validatedData['depart_anticipe'] = false; // On peut ajouter la logique pour le départ anticipé plus tard
    
            // Création de la présence
            $presence = Presence::create($validatedData);
            
            return redirect()->route('presences.index')
                ->with('success', 'Présence créée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la présence : ' . $e->getMessage());
            Log::error('Trace de la pile : ', $e->getTrace());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur technique : ' . $e->getMessage());
        }

        // Validation des données
       /*$validatedData = $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date' => 'required|date',
            'heure_arrivee' => 'required|date_format:H:i',
            'heure_depart' => 'nullable|date_format:H:i',
            'commentaire' => 'nullable|string',
        ]);
        
        try {
            // Vérifier si une présence existe déjà pour cette date et cet employé
            $existingPresence = Presence::where('employe_id', $validatedData['employe_id'])
                ->where('date', $validatedData['date'])
                ->first();
            
            if ($existingPresence) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Une présence existe déjà pour cet employé à cette date.');
            }
            
            // Recherche du planning pour déterminer le retard
            $planning = Planning::where('employe_id', $validatedData['employe_id'])
                ->where('date_debut', '<=', $validatedData['date'])
                ->where('date_fin', '>=', $validatedData['date'])
                ->first();
            
            // Déterminer si l'employé est en retard (tolérance de 10 minutes)
            $retard = false;
            $departAnticipe = false;
            
            if ($planning) {
                $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut)->addMinutes(10);
                $heureArriveeCarbon = \Carbon\Carbon::parse($validatedData['heure_arrivee']);
                $retard = $heureArriveeCarbon > $heureDebutPlanning;
                
                if (isset($validatedData['heure_depart']) && $planning->heure_fin) {
                    $heureFinPlanning = \Carbon\Carbon::parse($planning->heure_fin)->subMinutes(10);
                    $heureDepartCarbon = \Carbon\Carbon::parse($validatedData['heure_depart']);
                    $departAnticipe = $heureDepartCarbon < $heureFinPlanning;
                }
            }
            
            // Ajout des champs de retard et départ anticipé
            $validatedData['retard'] = $retard;
            $validatedData['depart_anticipe'] = $departAnticipe;
            
            // Création de la présence
            Presence::create($validatedData);
            
            return redirect()->route('presences.index')
                ->with('success', 'Présence créée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la présence : ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création de la présence.');
        }
        Log::info('Méthode store appelée');*/

    }
    
    /**
     * Afficher les détails d'une présence.
     */
    public function show(Presence $presence)
    {
        // Récupérer le planning associé s'il existe
        $planning = Planning::where('employe_id', $presence->employe_id)
            ->where('date_debut', '<=', $presence->date)
            ->where('date_fin', '>=', $presence->date)
            ->first();
            
        return view('presences.show', compact('presence', 'planning'));
    }
    
    /**
     * Afficher le formulaire d'édition d'une présence.
     */
    public function edit(Presence $presence)
    {
        $employes = Employe::where('actif', true)->orderBy('nom')->orderBy('prenom')->get();
        return view('presences.edit', compact('presence', 'employes'));
    }
    
    /**
     * Mettre à jour une présence.
     */
    public function update(Request $request, Presence $presence)
    {
        // Validation des données
        $validatedData = $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'date' => 'required|date',
            'heure_arrivee' => 'required|date_format:H:i',
            'heure_depart' => 'nullable|date_format:H:i',
            'commentaire' => 'nullable|string',
        ]);
        
        try {
            // Vérifier si une présence existe déjà pour cette date et cet employé (sauf celle-ci)
            $existingPresence = Presence::where('employe_id', $validatedData['employe_id'])
                ->where('date', $validatedData['date'])
                ->where('id', '!=', $presence->id)
                ->first();
            
            if ($existingPresence) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Une présence existe déjà pour cet employé à cette date.');
            }
            
            // Recherche du planning pour déterminer le retard
            $planning = Planning::where('employe_id', $validatedData['employe_id'])
                ->where('date_debut', '<=', $validatedData['date'])
                ->where('date_fin', '>=', $validatedData['date'])
                ->first();
            
            // Déterminer si l'employé est en retard (tolérance de 10 minutes)
            $retard = false;
            $departAnticipe = false;
            
            if ($planning) {
                $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut)->addMinutes(10);
                $heureArriveeCarbon = \Carbon\Carbon::parse($validatedData['heure_arrivee']);
                $retard = $heureArriveeCarbon > $heureDebutPlanning;
                
                if (isset($validatedData['heure_depart']) && $planning->heure_fin) {
                    $heureFinPlanning = \Carbon\Carbon::parse($planning->heure_fin)->subMinutes(10);
                    $heureDepartCarbon = \Carbon\Carbon::parse($validatedData['heure_depart']);
                    $departAnticipe = $heureDepartCarbon < $heureFinPlanning;
                }
            }
            
            // Ajout des champs de retard et départ anticipé
            $validatedData['retard'] = $retard;
            $validatedData['depart_anticipe'] = $departAnticipe;
            
            // Mise à jour de la présence
            $presence->update($validatedData);
            
            return redirect()->route('presences.index')
                ->with('success', 'Présence mise à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la présence : ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la présence.');
        }
    }
    
    /**
     * Supprimer une présence.
     */
    public function destroy(Presence $presence)
    {
        try {
            // Suppression de la présence
            $presence->delete();
            
            return redirect()->route('presences.index')
                ->with('success', 'Présence supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la présence : ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de la présence.');
        }
    }
    
    /**
     * Télécharger le modèle d'importation.
     */
    public function template()
    {
        return Excel::download(new PresencesTemplateExport, 'modele_presences.xlsx');
    }
    
    /**
     * Afficher le formulaire d'importation.
     */
    public function importForm()
    {
        return view('presences.import');
    }
    
    /**
     * Importer les présences.
     */
    public function import(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        
        try {
            Excel::import(new PresencesImport, $request->file('fichier'));
            
            $summary = session('import_summary');
            $message = 'Importation terminée : ' . $summary['imported'] . ' présences importées, ' . $summary['updated'] . ' présences mises à jour.';
            
            if ($summary['errors'] > 0) {
                $message .= ' ' . $summary['errors'] . ' erreurs rencontrées.';
            }
            
            return redirect()->route('presences.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'importation des présences : ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'importation des présences : ' . $e->getMessage());
        }
    }
    
    /**
     * Exporter les présences.
     */
    public function export()
    {
        return Excel::download(new PresencesExport, 'presences.xlsx');
    }
    
    /**
     * Exporter les présences en format Excel.
     */
    public function exportExcel()
    {
        return Excel::download(new PresencesExport, 'presences_export.xlsx');
    }
    
    /**
     * Exporter les présences en format PDF.
     */
    public function exportPdf()
    {
        $presences = Presence::with('employe.poste')->orderBy('date', 'desc')->get();
        $pdf = Pdf::loadView('presences.pdf.export', compact('presences'));
        return $pdf->download('presences_export.pdf');
    }

    /**
     * Importe les données biométriques depuis un fichier (JSON ou CSV).
     */
    public function importBiometrique(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,csv,txt|max:10240',
            'format' => 'required|in:json,csv'
        ]);

        $file = $request->file('file');
        $format = $request->input('format');
        $skipExisting = $request->has('skip_existing');

        // Statistiques d'importation
        $stats = [
            'total' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Traiter le fichier selon son format
            if ($format === 'json') {
                $jsonData = json_decode(file_get_contents($file->path()), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return redirect()->route('rapports.biometrique')
                        ->with('error', 'Le fichier JSON est invalide: ' . json_last_error_msg())
                        ->with('form_modal', 'import');
                }
                
                // Traiter chaque pointage dans le fichier JSON
                foreach ($jsonData as $index => $pointageData) {
                    $stats['total']++;
                    $result = $this->processPointageBiometrique($pointageData, $skipExisting);
                    $this->updateStats($stats, $result);
                }
            } 
            else if ($format === 'csv') {
                // Ouvrir le fichier CSV
                $csvFile = fopen($file->path(), 'r');
                
                // Lire l'en-tête pour déterminer les colonnes
                $headers = fgetcsv($csvFile);
                
                // Valider que l'en-tête contient les colonnes requises
                $requiredColumns = ['employee_id', 'timestamp', 'type', 'latitude', 'longitude', 'biometric_score'];
                $missingColumns = array_diff($requiredColumns, $headers);
                
                if (!empty($missingColumns)) {
                    return redirect()->route('rapports.biometrique')
                        ->with('error', 'Colonnes manquantes dans le CSV: ' . implode(', ', $missingColumns))
                        ->with('form_modal', 'import');
                }
                
                // Créer un mappage des indices de colonnes
                $columnMap = array_flip($headers);
                
                // Traiter chaque ligne du CSV
                while (($row = fgetcsv($csvFile)) !== false) {
                    $stats['total']++;
                    
                    // Convertir la ligne CSV en structure de données pointage
                    $pointageData = [
                        'employee_id' => $row[$columnMap['employee_id']],
                        'timestamp' => $row[$columnMap['timestamp']],
                        'type' => $row[$columnMap['type']], // 'check-in' ou 'check-out'
                        'location' => [
                            'latitude' => $row[$columnMap['latitude']],
                            'longitude' => $row[$columnMap['longitude']],
                            'accuracy' => $row[$columnMap['accuracy'] ?? $columnMap['precision'] ?? 10] // valeur par défaut si non spécifiée
                        ],
                        'biometric_verification' => [
                            'hash' => $row[$columnMap['biometric_hash'] ?? $columnMap['hash'] ?? 0] ?? md5($row[$columnMap['employee_id']] . $row[$columnMap['timestamp']]),
                            'confidence_score' => $row[$columnMap['biometric_score']]
                        ],
                        'device_info' => [
                            'device_id' => $row[$columnMap['device_id'] ?? $columnMap['device'] ?? 0] ?? 'imported-device'
                        ]
                    ];
                    
                    $result = $this->processPointageBiometrique($pointageData, $skipExisting);
                    $this->updateStats($stats, $result);
                }
                
                fclose($csvFile);
            }

            // Déterminer le message de statut approprié
            if ($stats['errors'] > 0) {
                $status = 'warning';
                $message = "Importation terminée avec des erreurs. {$stats['imported']} pointages importés, {$stats['skipped']} ignorés, {$stats['errors']} erreurs.";
            } else if ($stats['skipped'] > 0) {
                $status = 'info';
                $message = "Importation terminée. {$stats['imported']} pointages importés, {$stats['skipped']} ignorés.";
            } else {
                $status = 'success';
                $message = "Importation réussie. {$stats['imported']} pointages importés.";
            }

            return redirect()->route('rapports.biometrique')->with($status, $message)->with('import_stats', $stats);
        } 
        catch (\Exception $e) {
            return redirect()->route('rapports.biometrique')
                ->with('error', 'Erreur lors de l\'importation: ' . $e->getMessage())
                ->with('form_modal', 'import');
        }
    }

    /**
     * Traite une entrée de pointage biométrique et l'insère en base de données.
     */
    private function processPointageBiometrique($data, $skipExisting = false)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => $data
        ];

        try {
            // Valider les données minimales requises
            if (!isset($data['employee_id']) || !isset($data['timestamp']) || !isset($data['type'])) {
                $result['message'] = 'Données incomplètes: employee_id, timestamp ou type manquant';
                return $result;
            }

            // Vérifier que l'employé existe
            $employe = Employe::find($data['employee_id']);
            if (!$employe) {
                $result['message'] = "L'employé ID {$data['employee_id']} n'existe pas";
                return $result;
            }

            // Parser la date et l'heure
            $timestamp = Carbon::parse($data['timestamp']);
            $date = $timestamp->toDateString();
            $time = $timestamp->toTimeString();

            // Déterminer s'il s'agit d'un check-in ou check-out
            $isCheckIn = strtolower($data['type']) === 'check-in';

            // Rechercher une présence existante
            $presence = Presence::where('employe_id', $data['employee_id'])
                ->where('date', $date)
                ->first();

            // Gérer les cas de check-in et check-out
            if ($isCheckIn) {
                // Si une présence existe déjà pour cette date et que nous devons ignorer les doublons
                if ($presence && $skipExisting) {
                    $result['message'] = "Pointage d'arrivée existant ignoré pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
                    $result['skipped'] = true;
                    return $result;
                }

                // Si une présence existe mais que nous ne l'ignorons pas, mettre à jour
                if ($presence) {
                    $presence->heure_arrivee = $time;
                } else {
                    // Créer une nouvelle présence
                    $presence = new Presence();
                    $presence->employe_id = $data['employee_id'];
                    $presence->date = $date;
                    $presence->heure_arrivee = $time;
                }

                // Vérifier si l'employé est en retard par rapport au planning
                $this->checkForLateArrival($presence, $timestamp);
            } 
            else { // Check-out
                // Si aucune présence n'existe pour ce jour, erreur
                if (!$presence) {
                    $result['message'] = "Aucun pointage d'arrivée trouvé pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
                    return $result;
                }

                // Si le départ a déjà été enregistré et que nous devons ignorer les doublons
                if ($presence->heure_depart && $skipExisting) {
                    $result['message'] = "Pointage de départ existant ignoré pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
                    $result['skipped'] = true;
                    return $result;
                }

                // Mettre à jour l'heure de départ
                $presence->heure_depart = $time;

                // Vérifier si l'employé part en avance par rapport au planning
                $this->checkForEarlyDeparture($presence, $timestamp);
            }

            // Préparer les métadonnées biométriques
            $metaData = json_decode($presence->meta_data ?? '{}', true);
            
            // Données de localisation et biométriques
            $bioData = [
                'location' => $data['location'] ?? ['latitude' => 0, 'longitude' => 0, 'accuracy' => 10],
                'biometric_verification' => $data['biometric_verification'] ?? ['hash' => '', 'confidence_score' => 0.8],
                'device_info' => $data['device_info'] ?? ['device_id' => 'imported-device']
            ];

            // Mettre à jour ou définir les métadonnées
            if ($isCheckIn) {
                // Pour un check-in, mettre à jour les métadonnées principales
                $metaData = array_merge($metaData, $bioData);
            } else {
                // Pour un check-out, ajouter à la clé 'checkout'
                $metaData['checkout'] = $bioData;
            }

            // Enregistrer les métadonnées
            $presence->meta_data = json_encode($metaData);
            $presence->save();

            $result['success'] = true;
            $result['message'] = ($isCheckIn ? "Pointage d'arrivée" : "Pointage de départ") . 
                              " importé pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
            return $result;
        } 
        catch (\Exception $e) {
            $result['message'] = "Erreur lors du traitement: " . $e->getMessage();
            return $result;
        }
    }

    /**
     * Vérifie si l'employé est arrivé en retard par rapport à son planning
     */
    private function checkForLateArrival($presence, $timestamp)
    {
        $jourSemaine = $timestamp->dayOfWeekIso;
        $date = $timestamp->toDateString();
        
        // Trouver le planning actif pour cet employé
        $planning = Planning::where('employe_id', $presence->employe_id)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->first();
            
        if ($planning) {
            // Récupérer le détail du planning pour ce jour de la semaine
            $planningDetail = $planning->details()
                ->where('jour', $jourSemaine)
                ->first();
                
            if ($planningDetail && !$planningDetail->jour_repos) {
                // Calculer si l'employé est en retard
                $heureArrivee = Carbon::parse($presence->heure_arrivee);
                $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
                
                // Tolérance de 10 minutes
                $heureDebutAvecTolerance = (clone $heureDebutPlanning)->addMinutes(10);
                
                if ($heureArrivee->gt($heureDebutAvecTolerance)) {
                    $presence->retard = true;
                    $minutesRetard = $heureArrivee->diffInMinutes($heureDebutPlanning);
                    $presence->commentaire = "Retard de {$minutesRetard} minutes (importé)";
                }
            }
        }
    }

    /**
     * Vérifie si l'employé est parti en avance par rapport à son planning
     */
    private function checkForEarlyDeparture($presence, $timestamp)
    {
        $jourSemaine = $timestamp->dayOfWeekIso;
        $date = $timestamp->toDateString();
        
        // Trouver le planning actif pour cet employé
        $planning = Planning::where('employe_id', $presence->employe_id)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->first();
            
        if ($planning) {
            // Récupérer le détail du planning pour ce jour de la semaine
            $planningDetail = $planning->details()
                ->where('jour', $jourSemaine)
                ->first();
                
            if ($planningDetail && !$planningDetail->jour_repos) {
                // Calculer si l'employé part en avance
                $heureDepart = Carbon::parse($presence->heure_depart);
                $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                
                // Tolérance de 10 minutes
                $heureFinAvecTolerance = (clone $heureFinPlanning)->subMinutes(10);
                
                if ($heureDepart->lt($heureFinAvecTolerance)) {
                    $presence->depart_anticipe = true;
                    $minutesAvance = $heureDepart->diffInMinutes($heureFinPlanning);
                    
                    // Ajouter un commentaire sur le départ anticipé
                    $commentaireExistant = $presence->commentaire ?? '';
                    $presence->commentaire = $commentaireExistant . 
                        ($commentaireExistant ? ' | ' : '') . 
                        "Départ anticipé de {$minutesAvance} minutes (importé)";
                }
            }
        }
    }

    /**
     * Mettre à jour les statistiques d'importation
     */
    private function updateStats(&$stats, $result)
    {
        if ($result['success']) {
            $stats['imported']++;
            $stats['details'][] = [
                'type' => 'success',
                'message' => $result['message']
            ];
        } else if (isset($result['skipped']) && $result['skipped']) {
            $stats['skipped']++;
            $stats['details'][] = [
                'type' => 'info',
                'message' => $result['message']
            ];
        } else {
            $stats['errors']++;
            $stats['details'][] = [
                'type' => 'danger',
                'message' => $result['message']
            ];
        }
    }

    /**
     * Vérifie la validité d'un fichier de données biométriques sans l'importer
     */
    public function verifyBiometrique(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:json,csv,txt|max:10240',
                'format' => 'required|in:json,csv'
            ]);

            $file = $request->file('file');
            $format = $request->input('format');
            
            // Statistiques et enregistrements
            $stats = [
                'total' => 0,
                'valid' => 0,
                'invalid' => 0
            ];
            $records = [];
            $maxRecordsToReturn = 10; // Limiter le nombre d'enregistrements à retourner
            
            // Traiter le fichier selon son format
            if ($format === 'json') {
                $jsonData = json_decode(file_get_contents($file->path()), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier JSON est invalide: ' . json_last_error_msg()
                    ]);
                }
                
                // Parcourir les données JSON
                foreach ($jsonData as $index => $data) {
                    $stats['total']++;
                    $validationResult = $this->validateBiometricRecord($data);
                    
                    if ($validationResult['valid']) {
                        $stats['valid']++;
                    } else {
                        $stats['invalid']++;
                    }
                    
                    // Ajouter l'enregistrement à la liste des échantillons
                    if (count($records) < $maxRecordsToReturn) {
                        $records[] = array_merge($data, $validationResult);
                    }
                }
            } 
            else if ($format === 'csv') {
                // Ouvrir le fichier CSV
                $csvFile = fopen($file->path(), 'r');
                
                // Lire l'en-tête pour déterminer les colonnes
                $headers = fgetcsv($csvFile);
                
                // Valider que l'en-tête contient les colonnes requises
                $requiredColumns = ['employee_id', 'timestamp', 'type', 'latitude', 'longitude', 'biometric_score'];
                $missingColumns = array_diff($requiredColumns, $headers);
                
                if (!empty($missingColumns)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Colonnes manquantes dans le CSV: ' . implode(', ', $missingColumns)
                    ]);
                }
                
                // Créer un mappage des indices de colonnes
                $columnMap = array_flip($headers);
                
                // Parcourir les lignes du CSV
                while (($row = fgetcsv($csvFile)) !== false) {
                    $stats['total']++;
                    
                    // Convertir la ligne CSV en structure de données
                    $data = [
                        'employee_id' => $row[$columnMap['employee_id']] ?? null,
                        'timestamp' => $row[$columnMap['timestamp']] ?? null,
                        'type' => $row[$columnMap['type']] ?? null,
                        'latitude' => $row[$columnMap['latitude']] ?? null,
                        'longitude' => $row[$columnMap['longitude']] ?? null,
                        'biometric_score' => $row[$columnMap['biometric_score']] ?? null
                    ];
                    
                    $validationResult = $this->validateBiometricRecord($data);
                    
                    if ($validationResult['valid']) {
                        $stats['valid']++;
                    } else {
                        $stats['invalid']++;
                    }
                    
                    // Ajouter l'enregistrement à la liste des échantillons
                    if (count($records) < $maxRecordsToReturn) {
                        $records[] = array_merge($data, $validationResult);
                    }
                }
                
                fclose($csvFile);
            }
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'records' => $records,
                'message' => "Vérification terminée. {$stats['valid']} enregistrements valides, {$stats['invalid']} invalides sur un total de {$stats['total']}."
            ]);
        } 
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Valide un enregistrement de données biométriques
     */
    private function validateBiometricRecord($data)
    {
        // Résultat par défaut: valide
        $result = [
            'valid' => true,
            'error' => null
        ];
        
        // Vérifier les champs obligatoires
        if (!isset($data['employee_id']) || !$data['employee_id']) {
            $result['valid'] = false;
            $result['error'] = "ID d'employé manquant";
            return $result;
        }
        
        if (!isset($data['timestamp']) || !$data['timestamp']) {
            $result['valid'] = false;
            $result['error'] = "Horodatage manquant";
            return $result;
        }
        
        if (!isset($data['type']) || !in_array(strtolower($data['type']), ['check-in', 'check-out'])) {
            $result['valid'] = false;
            $result['error'] = "Type invalide (doit être 'check-in' ou 'check-out')";
            return $result;
        }
        
        // Vérifier que l'horodatage est valide
        try {
            $timestamp = Carbon::parse($data['timestamp']);
        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['error'] = "Format d'horodatage invalide";
            return $result;
        }
        
        // Vérifier que l'employé existe
        $employe = Employe::find($data['employee_id']);
        if (!$employe) {
            $result['valid'] = false;
            $result['error'] = "L'employé avec l'ID {$data['employee_id']} n'existe pas";
            return $result;
        }
        
        // Vérifier les coordonnées
        if (!isset($data['latitude']) || !is_numeric($data['latitude']) || 
            !isset($data['longitude']) || !is_numeric($data['longitude'])) {
            $result['valid'] = false;
            $result['error'] = "Coordonnées géographiques invalides";
            return $result;
        }
        
        // Vérifier le score biométrique
        if (!isset($data['biometric_score']) || !is_numeric($data['biometric_score']) || 
            $data['biometric_score'] < 0 || $data['biometric_score'] > 1) {
            $result['valid'] = false;
            $result['error'] = "Score biométrique invalide (doit être entre 0 et 1)";
            return $result;
        }
        
        // Vérifier la présence existante pour les check-out
        if (strtolower($data['type']) === 'check-out') {
            $presence = Presence::where('employe_id', $data['employee_id'])
                ->where('date', $timestamp->toDateString())
                ->first();
                
            if (!$presence) {
                $result['valid'] = false;
                $result['error'] = "Aucun pointage d'arrivée trouvé pour cet employé à cette date";
                return $result;
            }
        }
        
        return $result;
    }
}