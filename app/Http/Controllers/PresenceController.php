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
        $sourcePointage = $request->query('source_pointage');
        
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
        
        // Filtre par source de pointage
        if ($sourcePointage !== null && $sourcePointage !== '') {
            $presencesQuery->where('source_pointage', $sourcePointage);
        }
        
        // Récupération des présences
        $presences = $presencesQuery->orderBy('date', 'desc')->orderBy('heure_arrivee')->paginate(15);
        
        // Récupération des employés pour le filtre
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get();
        
        return view('presences.index', compact('presences', 'employes', 'employe', 'date', 'retard', 'departAnticipe', 'sourcePointage'));
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
                'heure_arrivee' => 'nullable|date_format:H:i',
                'heure_depart' => 'nullable|date_format:H:i',
                'commentaire' => 'nullable|string',
                'source_pointage' => 'nullable|in:biometrique,manuel',
            ]);
            
            // S'assurer qu'au moins une heure (arrivée ou départ) est fournie
            if (empty($validatedData['heure_arrivee']) && empty($validatedData['heure_depart'])) {
                return redirect()->back()->withInput()->with('error', "Veuillez renseigner l'heure d'arrivée ou de départ.");
            }
            
            // Définir la source de pointage (manuel par défaut)
            $validatedData['source_pointage'] = $request->input('source_pointage', 'manuel');
            
            // Vérifier s'il existe déjà un pointage pour cet employé et cette date
            $presence = Presence::firstOrNew([
                'employe_id' => $validatedData['employe_id'],
                'date'       => $validatedData['date'],
            ]);
            
            // Si l'heure d'arrivée est fournie, la définir (sans écraser une valeur existante sauf si on veut la mettre à jour)
            if (!empty($validatedData['heure_arrivee'])) {
                $presence->heure_arrivee = $validatedData['heure_arrivee'];
            }
            
            // Si l'heure de départ est fournie, la définir
            if (!empty($validatedData['heure_depart'])) {
                $presence->heure_depart = $validatedData['heure_depart'];
            }
            
            // Mettre à jour les informations supplémentaires
            $presence->commentaire       = $validatedData['commentaire'] ?? $presence->commentaire;
            $presence->source_pointage   = $validatedData['source_pointage'];
            
            // ---------- Calcul des statuts retard / départ anticipé -----------
            $employe      = Employe::find($validatedData['employe_id']);
            $planning     = Planning::where('employe_id', $validatedData['employe_id'])
                ->where('date_debut', '<=', $validatedData['date'])
                ->where('date_fin', '>=', $validatedData['date'])
                ->first();
            
            $presence->retard          = false;
            $presence->depart_anticipe = false;
            $presence->heures_supplementaires = 0;
            
            if ($planning) {
                // Création des objets Carbon du planning
                $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut);
                $heureFinPlanning   = \Carbon\Carbon::parse($planning->heure_fin);
                
                // Appliquer les tolérances (10 minutes)
                $heureDebutPlanningTol  = (clone $heureDebutPlanning)->addMinutes(10);
                $heureFinPlanningTol    = (clone $heureFinPlanning)->subMinutes(10);
                
                if ($presence->heure_arrivee) {
                    $heureArrivee = \Carbon\Carbon::parse($presence->heure_arrivee);
                    $presence->retard = $heureArrivee->gt($heureDebutPlanningTol);
                }
                
                if ($presence->heure_depart) {
                    $heureDepart = \Carbon\Carbon::parse($presence->heure_depart);
                    $presence->depart_anticipe = $heureDepart->lt($heureFinPlanningTol);
                    
                    // Heures supplémentaires (simple calcul : durée réelle - durée prévue)
                    $dureeReelle = $presence->heure_arrivee ?
                        \Carbon\Carbon::parse($presence->heure_arrivee)->diffInMinutes($heureDepart) : 0;
                    $dureePrevue = $heureDebutPlanning->diffInMinutes($heureFinPlanning);
                    if ($dureeReelle > $dureePrevue) {
                        $presence->heures_supplementaires = $dureeReelle - $dureePrevue;
                    }
                }
            }
            
            $presence->save();
            
            // Message utilisateur
            $action = $presence->wasRecentlyCreated ? 'créé' : 'mis à jour';
            $message = "Pointage {$action} avec succès.";
            if ($presence->retard) {
                $message .= ' (Retard détecté)';
            }
            if ($presence->depart_anticipe) {
                $message .= ' (Départ anticipé détecté)';
            }
            
            return redirect()->route('presences.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du pointage : ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Erreur technique : ' . $e->getMessage());
        }
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
        $employes = Employe::where('statut', 'actif')->orderBy('nom')->orderBy('prenom')->get();
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
            'source_pointage' => 'nullable|in:biometrique,manuel',
        ]);
        
        // Définir la source de pointage (conserver l'existante ou utiliser manuel par défaut)
        $validatedData['source_pointage'] = $request->input('source_pointage', $presence->source_pointage ?? 'manuel');
        
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
            
            // Récupérer l'employé pour les messages d'erreur
            $employe = Employe::find($validatedData['employe_id']);
            $nomEmploye = $employe ? $employe->prenom . ' ' . $employe->nom : 'L\'employé';
            $dateFormatee = Carbon::parse($validatedData['date'])->format('d/m/Y');
            
            // Recherche du planning pour déterminer le retard
            $planning = Planning::where('employe_id', $validatedData['employe_id'])
                ->where('date_debut', '<=', $validatedData['date'])
                ->where('date_fin', '>=', $validatedData['date'])
                ->first();
            
            // Vérifier si un planning existe pour cet employé à cette date
            if (!$planning) {
                // Aucun planning défini pour cet employé à cette date
                Log::warning("Tentative de mise à jour d'un pointage sans planning défini pour l'employé ID {$validatedData['employe_id']} à la date {$validatedData['date']}");
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Impossible de mettre à jour ce pointage : aucun planning n'est défini pour {$nomEmploye} à la date du {$dateFormatee}. Veuillez d'abord créer un planning pour cet employé à cette date.");
            }
            
            // Déterminer si l'employé est en retard (tolérance de 10 minutes)
            $retard = false;
            $departAnticipe = false;
            
            $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut)->addMinutes(10);
            $heureArriveeCarbon = \Carbon\Carbon::parse($validatedData['heure_arrivee']);
            $retard = $heureArriveeCarbon > $heureDebutPlanning;
            
            if (isset($validatedData['heure_depart']) && $planning->heure_fin) {
                $heureFinPlanning = \Carbon\Carbon::parse($planning->heure_fin)->subMinutes(10);
                $heureDepartCarbon = \Carbon\Carbon::parse($validatedData['heure_depart']);
                $departAnticipe = $heureDepartCarbon < $heureFinPlanning;
            }
            
            // Ajout des champs de retard et départ anticipé
            $validatedData['retard'] = $retard;
            $validatedData['depart_anticipe'] = $departAnticipe;
            
            // Calcul des heures supplémentaires
            $validatedData['heures_supplementaires'] = 0;
            if (isset($validatedData['heure_depart']) && $planning->heure_fin) {
                $heuresSupplementaires = $this->calculerHeuresSupplementaires(
                    $validatedData['employe_id'],
                    $validatedData['date'],
                    $validatedData['heure_depart'],
                    $planning->heure_fin
                );
                $validatedData['heures_supplementaires'] = $heuresSupplementaires;
            }
            
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
     * Calculer les heures supplémentaires en fonction des critères de pointage
     * 
     * @param int $employeId ID de l'employé
     * @param string $date Date du pointage au format Y-m-d
     * @param string $heureDepart Heure de départ au format H:i
     * @param string $heureFinPrevue Heure de fin prévue au format H:i
     * @return int Nombre de minutes d'heures supplémentaires
     */
    private function calculerHeuresSupplementaires($employeId, $date, $heureDepart, $heureFinPrevue)
    {
        // Convertir les heures en objets Carbon
        $heureFinPrevueCarbon = \Carbon\Carbon::parse($heureFinPrevue);
        $heureDepartCarbon = \Carbon\Carbon::parse($heureDepart);
        
        // Si l'employé est parti avant l'heure de fin prévue, pas d'heures supplémentaires
        if ($heureDepartCarbon <= $heureFinPrevueCarbon) {
            return 0;
        }
        
        // Rechercher les critères de pointage applicables pour cet employé et cette date
        $criterePointage = $this->getCriterePointage($employeId, $date);
        
        // Si aucun critère n'est trouvé ou si le calcul des heures supplémentaires n'est pas activé
        if (!$criterePointage || !$criterePointage->calcul_heures_sup) {
            return 0;
        }
        
        // Calculer la différence en minutes entre l'heure de départ et l'heure de fin prévue
        $differenceMinutes = $heureDepartCarbon->diffInMinutes($heureFinPrevueCarbon);
        
        // Vérifier si la différence dépasse le seuil configuré
        if ($differenceMinutes <= $criterePointage->seuil_heures_sup) {
            return 0;
        }
        
        // Retourner le nombre de minutes d'heures supplémentaires
        return $differenceMinutes;
    }
    
    /**
     * Récupérer le critère de pointage applicable pour un employé et une date
     * 
     * @param int $employeId ID de l'employé
     * @param string $date Date au format Y-m-d
     * @return \App\Models\CriterePointage|null Critère de pointage applicable ou null si aucun
     */
    private function getCriterePointage($employeId, $date)
    {
        // Convertir la date en objet Carbon
        $dateCarbon = \Carbon\Carbon::parse($date);
        
        // Déterminer la période (jour, semaine, mois)
        $jourSemaine = $dateCarbon->dayOfWeek; // 0 (dimanche) à 6 (samedi)
        $semaine = $dateCarbon->weekOfYear;
        $mois = $dateCarbon->month;
        
        // Récupérer l'employé pour connaître son département
        $employe = \App\Models\Employe::with('poste.departement')->find($employeId);
        $departementId = $employe->poste->departement->id ?? null;
        
        // Rechercher les critères de pointage applicables par ordre de priorité
        
        // 1. Critère individuel pour cette date spécifique
        $critereIndividuel = \App\Models\CriterePointage::where('employe_id', $employeId)
            ->where('niveau', 'individuel')
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->orderBy('priorite')
            ->first();
            
        if ($critereIndividuel) {
            return $critereIndividuel;
        }
        
        // 2. Critère départemental pour cette date spécifique
        if ($departementId) {
            $critereDepartemental = \App\Models\CriterePointage::where('departement_id', $departementId)
                ->where('niveau', 'departemental')
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('actif', true)
                ->orderBy('priorite')
                ->first();
                
            if ($critereDepartemental) {
                return $critereDepartemental;
            }
        }
        
        // Aucun critère applicable trouvé
        return null;
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
        try {
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

            // Diagnostics
            $diagnosticSession = 'import_' . now()->format('YmdHis');
            $this->logDiagnostic($diagnosticSession, 'start', 'Début de l\'importation', [
                'format' => $format,
                'skip_existing' => $skipExisting,
                'file_size' => $file->getSize(),
                'file_extension' => $file->getClientOriginalExtension()
            ]);

            // Traiter le fichier selon son format
            if ($format === 'json') {
                $content = file_get_contents($file->path());
                $this->logDiagnostic($diagnosticSession, 'json_content', 'Contenu JSON', [
                    'sample' => substr($content, 0, 200) . '...'
                ]);
                
                $jsonData = json_decode($content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logDiagnostic($diagnosticSession, 'json_error', 'Erreur JSON', [
                        'error' => json_last_error_msg()
                    ]);
                    return redirect()->route('rapports.biometrique')
                        ->with('error', 'Le fichier JSON est invalide: ' . json_last_error_msg())
                        ->with('form_modal', 'import');
                }
                
                // Traiter d'abord les check-in puis les check-out pour s'assurer que les 
                // pointages d'arrivée existent avant de traiter les départs
                // Step 1: filtrer et trier les données
                $checkIns = [];
                $checkOuts = [];
                
                foreach ($jsonData as $pointageData) {
                    $type = strtolower($pointageData['type'] ?? '');
                    if ($type === 'check-in') {
                        $checkIns[] = $pointageData;
                    } else {
                        $checkOuts[] = $pointageData;
                    }
                }
                
                $this->logDiagnostic($diagnosticSession, 'json_processing', 'Traitement JSON', [
                    'total_records' => count($jsonData),
                    'checkins' => count($checkIns),
                    'checkouts' => count($checkOuts)
                ]);
                
                // Step 2: Traiter d'abord les arrivées
                foreach ($checkIns as $index => $pointageData) {
                    $stats['total']++;
                    $this->logDiagnostic($diagnosticSession, 'checkin_' . $index, 'Traitement check-in', [
                        'employee_id' => $pointageData['employee_id'] ?? 'non spécifié',
                        'timestamp' => $pointageData['timestamp'] ?? 'non spécifié'
                    ]);
                    
                    $result = $this->processPointageBiometrique($pointageData, $skipExisting);
                    $this->updateStats($stats, $result);
                    
                    $this->logDiagnostic($diagnosticSession, 'checkin_result_' . $index, 'Résultat check-in', [
                        'success' => $result['success'],
                        'message' => $result['message'],
                        'debug_info' => $result['debug_info'] ?? []
                    ]);
                }
                
                // Step 3: Puis traiter les départs
                foreach ($checkOuts as $index => $pointageData) {
                    $stats['total']++;
                    $this->logDiagnostic($diagnosticSession, 'checkout_' . $index, 'Traitement check-out', [
                        'employee_id' => $pointageData['employee_id'] ?? 'non spécifié',
                        'timestamp' => $pointageData['timestamp'] ?? 'non spécifié'
                    ]);
                    
                    $result = $this->processPointageBiometrique($pointageData, $skipExisting);
                    $this->updateStats($stats, $result);
                    
                    $this->logDiagnostic($diagnosticSession, 'checkout_result_' . $index, 'Résultat check-out', [
                        'success' => $result['success'],
                        'message' => $result['message'],
                        'debug_info' => $result['debug_info'] ?? []
                    ]);
                }
            } 
            else if ($format === 'csv') {
                // Traitement du fichier CSV...
                // (code similaire ici)
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

            $this->logDiagnostic($diagnosticSession, 'end', 'Fin de l\'importation', [
                'stats' => $stats,
                'status' => $status,
                'message' => $message
            ]);

            return redirect()->route('rapports.biometrique')->with($status, $message)->with('import_stats', $stats);
        } 
        catch (\Exception $e) {
            $this->logDiagnostic($diagnosticSession ?? 'error', 'exception', 'Exception durant l\'importation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('rapports.biometrique')
                ->with('error', 'Erreur lors de l\'importation: ' . $e->getMessage())
                ->with('form_modal', 'import');
        }
    }
    
    /**
     * Enregistrer des informations de diagnostic
     */
    private function logDiagnostic($session, $step, $description, $data = [])
    {
        try {
            $diagnosticFile = storage_path('logs/biometric_import_' . $session . '.log');
            $content = '[' . now()->format('Y-m-d H:i:s') . '] ' . $step . ': ' . $description . "\n";
            $content .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            
            file_put_contents($diagnosticFile, $content, FILE_APPEND);
        } catch (\Exception $e) {
            // Ignorer les erreurs d'écriture pour ne pas bloquer l'importation
        }
    }

    /**
     * Traiter un enregistrement de pointage biométrique
     */
    private function processPointageBiometrique($data, $skipExisting = false)
    {
        $result = [
            'success' => false,
            'message' => '',
            'skipped' => false,
            'debug_info' => [] // Pour le débogage
        ];
        
        // Normaliser l'ID d'employé en entier
        if (isset($data['employee_id'])) {
            $data['employee_id'] = (int)$data['employee_id'];
            $result['debug_info']['normalized_employee_id'] = $data['employee_id'];
        }
        
        // Vérifier que l'employé existe
        $employe = Employe::find($data['employee_id']);
        if (!$employe) {
            // Vérifier si l'employé est soft-deleted
            $employeTrashed = Employe::withTrashed()->find($data['employee_id']);
            if ($employeTrashed) {
                $result['message'] = "L'employé avec l'ID {$data['employee_id']} a été supprimé et ne peut pas être utilisé";
            } else {
                $result['message'] = "L'employé avec l'ID {$data['employee_id']} n'existe pas";
            }
            return $result;
        }
        
        $result['debug_info']['employee_exists'] = true;
        $result['debug_info']['employee_name'] = $employe->prenom . ' ' . $employe->nom;
        
        // Parser l'horodatage
        try {
            $timestamp = Carbon::parse($data['timestamp']);
            $date = $timestamp->toDateString();
            $time = $timestamp->toTimeString();
            $result['debug_info']['parsed_date'] = $date;
            $result['debug_info']['parsed_time'] = $time;
        } catch (\Exception $e) {
            $result['message'] = "Format d'horodatage invalide: {$data['timestamp']}";
            return $result;
        }
        
        // Récupérer le type de pointage (normalisé en minuscules)
        $type = strtolower($data['type'] ?? '');
        $isCheckIn = $type === 'check-in';
        $result['debug_info']['is_check_in'] = $isCheckIn;
        $result['debug_info']['type'] = $type;
        
        // Vérifier si un planning existe pour cet employé à cette date
        $planning = Planning::where('employe_id', $data['employee_id'])
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->first();
        
        $result['debug_info']['planning_exists'] = $planning ? true : false;
        
        // Refuser le pointage si aucun planning n'est défini
        if (!$planning) {
            $result['message'] = "Impossible d'enregistrer le pointage pour {$employe->prenom} {$employe->nom} le {$date} : aucun planning n'est défini pour cet employé à cette date.";
            $result['debug_info']['action'] = 'rejected_no_planning';
            return $result;
        }
        
        // Récupérer la présence existante pour cette date
        $presence = Presence::where('employe_id', $data['employee_id'])
            ->where('date', $date)
            ->first();
            
        $result['debug_info']['existing_presence'] = $presence ? true : false;
        
        // Traiter selon le type de pointage
        if ($isCheckIn) {
            // Pointage d'arrivée
            
            // Si une présence existe déjà et qu'il faut ignorer les existants
            if ($presence && $skipExisting) {
                $result['message'] = "Pointage d'arrivée existant ignoré pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
                $result['skipped'] = true;
                return $result;
            }
            
            // Créer un nouveau pointage s'il n'existe pas
            if (!$presence) {
                $presence = new Presence();
                $presence->employe_id = $data['employee_id'];
                $presence->date = $date;
                $presence->heure_arrivee = $time;
                $presence->source_pointage = 'biometrique';
                
                // Vérifier si l'employé est en retard par rapport au planning
                // en utilisant les critères de pointage configurés
                $this->checkForLateness($presence, $timestamp);
                
                $result['debug_info']['action'] = 'create_new_checkin';
            } 
            // Sinon, mettre à jour l'existant si on ne l'ignore pas
            else if (!$skipExisting) {
                $presence->heure_arrivee = $time;
                $presence->source_pointage = 'biometrique';
                $this->checkForLateness($presence, $timestamp);
                
                $result['debug_info']['action'] = 'update_existing_checkin';
            }
        } 
        else { // Check-out
            // Si aucune présence n'existe pour ce jour, erreur
            if (!$presence) {
                $result['message'] = "Aucun pointage d'arrivée trouvé pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
                $result['debug_info']['action'] = 'checkout_without_checkin';
                return $result;
            }

            // Si le départ a déjà été enregistré et que nous devons ignorer les doublons
            if ($presence->heure_depart && $skipExisting) {
                $result['message'] = "Pointage de départ existant ignoré pour l'employé {$employe->prenom} {$employe->nom} le {$date}";
                $result['skipped'] = true;
                $result['debug_info']['action'] = 'skip_existing_checkout';
                return $result;
            }

            // Mettre à jour l'heure de départ
            $presence->heure_depart = $time;
            $presence->source_pointage = 'biometrique';

            // Vérifier si l'employé part en avance par rapport au planning
            $this->checkForEarlyDeparture($presence, $timestamp);
            
            // Calculer les heures supplémentaires si le planning a une heure de fin
            if ($planning && $planning->heure_fin) {
                $heuresSupplementaires = $this->calculerHeuresSupplementaires(
                    $data['employee_id'],
                    $date,
                    $time,
                    $planning->heure_fin
                );
                $presence->heures_supplementaires = $heuresSupplementaires;
                $result['debug_info']['overtime_minutes'] = $heuresSupplementaires;
            }
            
            $result['debug_info']['action'] = 'update_with_checkout';
        }

        // Préparer les métadonnées biométriques
        $metaData = json_decode($presence->meta_data ?? '{}', true);
        
        // Gestion des formats de données flexibles
        $location = [];
        $biometricVerification = [];
        $deviceInfo = [];
        
        // Format 1: données structurées (format JSON)
        if (isset($data['location']) && is_array($data['location'])) {
            $location = $data['location'];
            $result['debug_info']['location_format'] = 'nested';
        } else {
            // Format 2: données à plat (format CSV)
            $location = [
                'latitude' => $data['latitude'] ?? 0,
                'longitude' => $data['longitude'] ?? 0,
                'accuracy' => $data['accuracy'] ?? 10
            ];
            $result['debug_info']['location_format'] = 'flat';
        }
        
        if (isset($data['biometric_verification']) && is_array($data['biometric_verification'])) {
            $biometricVerification = $data['biometric_verification'];
            $result['debug_info']['biometric_format'] = 'nested';
        } else {
            $biometricVerification = [
                'hash' => $data['hash'] ?? md5($data['employee_id'] . $data['timestamp']),
                'confidence_score' => $data['biometric_score'] ?? 0.8
            ];
            $result['debug_info']['biometric_format'] = 'flat';
        }
        
        if (isset($data['device_info']) && is_array($data['device_info'])) {
            $deviceInfo = $data['device_info'];
            $result['debug_info']['device_format'] = 'nested';
        } else {
            $deviceInfo = [
                'device_id' => $data['device_id'] ?? 'imported-device'
            ];
            $result['debug_info']['device_format'] = 'flat';
        }
        
        // Construire les données biométriques
        $bioData = [
            'location' => $location,
            'biometric_verification' => $biometricVerification,
            'device_info' => $deviceInfo
        ];

        // Mettre à jour ou définir les métadonnées
        if ($isCheckIn) {
            // Pour un check-in, mettre à jour les métadonnées principales
            $metaData = array_merge($metaData, $bioData);
            $result['debug_info']['meta_update'] = 'main';
        } else {
            // Pour un check-out, ajouter à la clé 'checkout'
            $metaData['checkout'] = $bioData;
            $result['debug_info']['meta_update'] = 'checkout';
        }
        
        // Enregistrer les métadonnées JSON
        $presence->meta_data = json_encode($metaData);
        
        try {
            $presence->save();
            $result['success'] = true;
            $result['message'] = "Pointage " . ($isCheckIn ? "d'arrivée" : "de départ") . 
                              " importé pour {$employe->prenom} {$employe->nom} le {$date} à {$time}";
            $result['debug_info']['save_success'] = true;
        } catch (\Exception $e) {
            $result['message'] = "Erreur lors de l'enregistrement: " . $e->getMessage();
            $result['debug_info']['save_success'] = false;
            $result['debug_info']['save_error'] = $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Vérifie si l'employé est arrivé en retard par rapport à son planning
     * en utilisant les critères de pointage configurés
     */
    private function checkForLateness($presence, $timestamp)
    {
        try {
            $jourSemaine = $timestamp->dayOfWeekIso;
            $date = $timestamp->toDateString();
            
            // Trouver le planning actif pour cet employé
            $planning = Planning::where('employe_id', $presence->employe_id)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('statut', 'actif')
                ->first();
                
            if ($planning) {
                // Récupérer le détail du planning pour ce jour de la semaine
                $planningDetail = $planning->details()
                    ->where('jour', $jourSemaine)
                    ->first();
                    
                if ($planningDetail && !$planningDetail->jour_repos) {
                    // Récupérer les critères de pointage applicables
                    $employe = Employe::find($presence->employe_id);
                    $sourcePointage = $presence->source_pointage ?? 'manuel';
                    $critere = CriterePointage::getCritereApplicable($employe, $date, $sourcePointage);
                    
                    // Utiliser les critères configurés ou les valeurs par défaut
                    $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                    $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                    $nombrePointages = $critere ? $critere->nombre_pointages : 2;
                    
                    // Calculer si l'employé est en retard
                    $heureArrivee = Carbon::parse($presence->heure_arrivee);
                    $heureDebutPlanning = Carbon::parse($planningDetail->heure_debut);
                    
                    if ($nombrePointages == 1) {
                        // Si un seul pointage est requis, on vérifie que le pointage est dans la plage
                        // [heure début - tolérance] -> [heure fin + tolérance]
                        $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                        $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
                        $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
                        
                        $presence->retard = !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
                        
                        if ($presence->retard) {
                            $presence->commentaire = "Pointage hors plage autorisée (source: {$sourcePointage})";
                        }
                    } else {
                        // Si deux pointages sont requis, on vérifie que l'arrivée est dans la plage
                        // [heure début - tolérance] -> [heure début + tolérance]
                        $debutPlage = (clone $heureDebutPlanning)->subMinutes($toleranceAvant);
                        $finPlage = (clone $heureDebutPlanning)->addMinutes($toleranceApres);
                        
                        $presence->retard = !($heureArrivee->gte($debutPlage) && $heureArrivee->lte($finPlage));
                        
                        if ($presence->retard) {
                            $minutesRetard = $heureArrivee->gt($heureDebutPlanning) ? 
                                $heureArrivee->diffInMinutes($heureDebutPlanning) : 
                                0;
                            $presence->commentaire = "Retard de {$minutesRetard} minutes (source: {$sourcePointage})";
                        }
                    }
                    
                    // Enregistrer les informations sur le critère utilisé
                    $metaData = json_decode($presence->meta_data ?? '{}', true);
                    $metaData['critere_applique'] = $critere ? [
                        'id' => $critere->id,
                        'niveau' => $critere->niveau,
                        'nombre_pointages' => $critere->nombre_pointages,
                        'tolerance_avant' => $critere->tolerance_avant,
                        'tolerance_apres' => $critere->tolerance_apres,
                        'source_pointage' => $critere->source_pointage
                    ] : 'default';
                    $presence->meta_data = json_encode($metaData);
                    
                } else {
                    // Pas de planning pour ce jour ou jour de repos
                    $presence->retard = false;
                }
            } else {
                // Pas de planning actif
                $presence->retard = false;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, ne pas marquer comme retard
            \Log::error("Erreur lors de la vérification du retard: " . $e->getMessage());
            $presence->retard = false;
        }
    }

    /**
     * Vérifie si l'employé est parti en avance par rapport à son planning
     * en utilisant les critères de pointage configurés
     */
    private function checkForEarlyDeparture($presence, $timestamp)
    {
        try {
            $jourSemaine = $timestamp->dayOfWeekIso;
            $date = $timestamp->toDateString();
            
            // Trouver le planning actif pour cet employé
            $planning = Planning::where('employe_id', $presence->employe_id)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('statut', 'actif')
                ->first();
                
            if ($planning) {
                // Récupérer le détail du planning pour ce jour de la semaine
                $planningDetail = $planning->details()
                    ->where('jour', $jourSemaine)
                    ->first();
                    
                if ($planningDetail && !$planningDetail->jour_repos) {
                    // Récupérer les critères de pointage applicables
                    $employe = Employe::find($presence->employe_id);
                    $sourcePointage = $presence->source_pointage ?? 'manuel';
                    $critere = CriterePointage::getCritereApplicable($employe, $date, $sourcePointage);
                    
                    // Utiliser les critères configurés ou les valeurs par défaut
                    $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                    $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                    $nombrePointages = $critere ? $critere->nombre_pointages : 2;
                    
                    // Si un seul pointage est requis, pas de départ anticipé
                    if ($nombrePointages == 1) {
                        $presence->depart_anticipe = false;
                        return;
                    }
                    
                    // Calculer si l'employé part en avance
                    $heureDepart = Carbon::parse($presence->heure_depart);
                    $heureFinPlanning = Carbon::parse($planningDetail->heure_fin);
                    
                    // Pour deux pointages, on vérifie que le départ est dans la plage
                    // [heure fin - tolérance] -> [heure fin + tolérance]
                    $debutPlage = (clone $heureFinPlanning)->subMinutes($toleranceApres);
                    $finPlage = (clone $heureFinPlanning)->addMinutes($toleranceApres);
                    
                    $presence->depart_anticipe = !($heureDepart->gte($debutPlage) && $heureDepart->lte($finPlage));
                    
                    if ($presence->depart_anticipe && $heureDepart->lt($heureFinPlanning)) {
                        $minutesAvance = $heureDepart->diffInMinutes($heureFinPlanning);
                        
                        // Ajouter un commentaire sur le départ anticipé
                        $commentaireExistant = $presence->commentaire ?? '';
                        $presence->commentaire = $commentaireExistant . 
                            ($commentaireExistant ? ' | ' : '') . 
                            "Départ anticipé de {$minutesAvance} minutes (source: {$sourcePointage})";
                    }
                    
                    // Mettre à jour les métadonnées avec les informations du critère
                    $metaData = json_decode($presence->meta_data ?? '{}', true);
                    if (!isset($metaData['critere_applique'])) {
                        $metaData['critere_applique'] = $critere ? [
                            'id' => $critere->id,
                            'niveau' => $critere->niveau,
                            'nombre_pointages' => $critere->nombre_pointages,
                            'tolerance_avant' => $critere->tolerance_avant,
                            'tolerance_apres' => $critere->tolerance_apres,
                            'source_pointage' => $critere->source_pointage
                        ] : 'default';
                        $presence->meta_data = json_encode($metaData);
                    }
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, ne pas marquer comme départ anticipé
            \Log::error("Erreur lors de la vérification du départ anticipé: " . $e->getMessage());
            $presence->depart_anticipe = false;
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
                'invalid' => 0,
                'errors' => 0
            ];
            $records = [];
            $maxRecordsToReturn = 10; // Limiter le nombre d'enregistrements à retourner
            $debugInfo = []; // Informations supplémentaires pour le débogage
            
            // Traiter le fichier selon son format
            if ($format === 'json') {
                $content = file_get_contents($file->path());
                $debugInfo['content_sample'] = substr($content, 0, 100) . '...';
                
                $jsonData = json_decode($content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier JSON est invalide: ' . json_last_error_msg(),
                        'debug_info' => $debugInfo
                    ]);
                }
                
                // Vérifier que les données sont un tableau
                if (!is_array($jsonData)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier JSON doit contenir un tableau d\'objets',
                        'debug_info' => $debugInfo
                    ]);
                }
                
                // Parcourir les données JSON
                foreach ($jsonData as $index => $data) {
                    $stats['total']++;
                    
                    // Normaliser l'ID d'employé en entier
                    if (isset($data['employee_id'])) {
                        $data['employee_id'] = (int)$data['employee_id'];
                    }
                    
                    $validationResult = $this->validateBiometricRecord($data);
                    
                    if ($validationResult['valid']) {
                        $stats['valid']++;
                    } else {
                        $stats['invalid']++;
                        $stats['errors']++;
                    }
                    
                    // Ajouter l'enregistrement à la liste des échantillons
                    if (count($records) < $maxRecordsToReturn) {
                        $records[] = array_merge($data, $validationResult);
                    }
                }
            } 
            else if ($format === 'csv') {
                // Lire le contenu du fichier pour le débogage
                $content = file_get_contents($file->path());
                $debugInfo['content_sample'] = substr($content, 0, 200) . '...';
                $debugInfo['file_size'] = filesize($file->path()) . ' bytes';
                $debugInfo['encoding'] = mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true);
                
                // Essayer de détecter le séparateur
                $detectedDelimiter = $this->detectCsvDelimiter($content);
                $debugInfo['detected_delimiter'] = $detectedDelimiter ? "'{$detectedDelimiter}'" : 'non détecté';
                
                // Ouvrir le fichier CSV avec le délimiteur détecté ou par défaut
                $csvFile = fopen($file->path(), 'r');
                
                if (!$csvFile) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible d\'ouvrir le fichier CSV',
                        'debug_info' => $debugInfo
                    ]);
                }
                
                // Lire l'en-tête pour déterminer les colonnes
                $headers = fgetcsv($csvFile, 0, $detectedDelimiter ?: ',');
                $debugInfo['raw_headers'] = $headers;
                
                if (!$headers || count($headers) <= 1) {
                    // Tenter une autre approche: lire la première ligne et la diviser manuellement
                    rewind($csvFile);
                    $firstLine = fgets($csvFile);
                    $debugInfo['first_line'] = $firstLine;
                    
                    // Essayer différents délimiteurs
                    foreach ([',', ';', "\t", '|'] as $delimiter) {
                        $testHeaders = str_getcsv($firstLine, $delimiter);
                        if (count($testHeaders) > 1) {
                            $headers = $testHeaders;
                            $debugInfo['manual_delimiter'] = $delimiter;
                            break;
                        }
                    }
                }
                
                if (!$headers || count($headers) <= 1) {
                    fclose($csvFile);
                    return response()->json([
                        'success' => false,
                        'message' => 'Format CSV invalide ou délimiteur non reconnu. Essayez d\'exporter avec des virgules (,) comme séparateurs.',
                        'debug_info' => $debugInfo
                    ]);
                }
                
                // Nettoyer les en-têtes (supprimer BOM et espaces)
                $headers = array_map(function($header) {
                    // Nettoyer le BOM (Byte Order Mark) UTF-8 s'il existe
                    $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                    return trim($header);
                }, $headers);
                
                $debugInfo['cleaned_headers'] = $headers;
                
                // Vérifier les colonnes requises
                $requiredColumns = ['employee_id', 'timestamp', 'type', 'latitude', 'longitude', 'biometric_score'];
                $missingColumns = array_diff($requiredColumns, $headers);
                
                if (!empty($missingColumns)) {
                    fclose($csvFile);
                    return response()->json([
                        'success' => false,
                        'message' => 'Colonnes manquantes dans le CSV: ' . implode(', ', $missingColumns) . '. Colonnes trouvées: ' . implode(', ', $headers),
                        'debug_info' => $debugInfo
                    ]);
                }
                
                // Créer un mappage des indices de colonnes
                $columnMap = array_flip($headers);
                $debugInfo['column_map'] = $columnMap;
                
                // Traiter chaque ligne du CSV
                $rowIndex = 1; // Commencer à 1 pour l'en-tête
                $delimiter = $detectedDelimiter ?: ',';
                
                while (($row = fgetcsv($csvFile, 0, $delimiter)) !== false) {
                    $rowIndex++;
                    $stats['total']++;
                    
                    // Vérifier que la ligne contient suffisamment de colonnes
                    if (count($row) < count($headers)) {
                        $stats['invalid']++;
                        $stats['errors']++;  // Incrémenter errors pour les lignes mal formatées
                        if (count($records) < $maxRecordsToReturn) {
                            $debugRecord = [
                                'valid' => false,
                                'error' => "Ligne {$rowIndex}: Nombre insuffisant de colonnes dans la ligne (" . count($row) . " trouvées, " . count($headers) . " attendues)",
                                'row_data' => $row,
                                'row_index' => $rowIndex
                            ];
                            $records[] = $debugRecord;
                        }
                        continue;
                    }
                    
                    // Convertir la ligne CSV en structure de données
                    $data = [
                        'employee_id' => isset($columnMap['employee_id']) && isset($row[$columnMap['employee_id']]) ? 
                                        (int)$row[$columnMap['employee_id']] : null,
                        'timestamp' => isset($columnMap['timestamp']) && isset($row[$columnMap['timestamp']]) ? 
                                        $row[$columnMap['timestamp']] : null,
                        'type' => isset($columnMap['type']) && isset($row[$columnMap['type']]) ? 
                                        $row[$columnMap['type']] : null,
                        'latitude' => isset($columnMap['latitude']) && isset($row[$columnMap['latitude']]) ? 
                                        $row[$columnMap['latitude']] : null,
                        'longitude' => isset($columnMap['longitude']) && isset($row[$columnMap['longitude']]) ? 
                                        $row[$columnMap['longitude']] : null,
                        'biometric_score' => isset($columnMap['biometric_score']) && isset($row[$columnMap['biometric_score']]) ? 
                                        $row[$columnMap['biometric_score']] : null
                    ];
                    
                    // Si c'est le premier enregistrement, ajouter les données brutes pour le débogage
                    if (count($records) === 0) {
                        $debugInfo['first_row'] = $row;
                        $debugInfo['mapped_data'] = $data;
                    }
                    
                    $validationResult = $this->validateBiometricRecord($data);
                    
                    if ($validationResult['valid']) {
                        $stats['valid']++;
                    } else {
                        $stats['invalid']++;
                        $stats['errors']++;  // Incrémenter errors pour les données invalides
                    }
                    
                    // Ajouter l'enregistrement à la liste des échantillons avec les données brutes pour le débogage
                    if (count($records) < $maxRecordsToReturn) {
                        $recordWithRawData = array_merge($data, $validationResult);
                        $recordWithRawData['raw_data'] = $row;
                        $recordWithRawData['headers'] = $headers;
                        $recordWithRawData['row_index'] = $rowIndex;
                        $records[] = $recordWithRawData;
                    }
                }
                
                fclose($csvFile);
            }
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'records' => $records,
                'debug_info' => $debugInfo,
                'message' => "Vérification terminée. {$stats['valid']} enregistrements valides, {$stats['invalid']} invalides sur un total de {$stats['total']}."
            ]);
        } 
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage(),
                'debug_info' => [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }
    
    /**
     * Détecte le délimiteur utilisé dans un fichier CSV
     */
    private function detectCsvDelimiter($content, $checkLines = 5) 
    {
        $delimiters = [',', ';', "\t", '|'];
        $results = [];
        
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $checkLines = min(count($lines), $checkLines);
        
        for ($i = 0; $i < $checkLines; $i++) {
            $line = $lines[$i];
            foreach ($delimiters as $delimiter) {
                $regExp = '/'.preg_quote($delimiter, '/').'/';
                $fields = preg_split($regExp, $line);
                if (count($fields) > 1) {
                    if (!isset($results[$delimiter])) {
                        $results[$delimiter] = 0;
                    }
                    $results[$delimiter] += count($fields) - 1;
                }
            }
        }
        
        if (empty($results)) {
            return null;
        }
        
        $results = array_keys($results, max($results));
        return $results[0];
    }
    
    /**
     * Valide un enregistrement de données biométriques
     */
    private function validateBiometricRecord($data)
    {
        // Résultat par défaut: valide
        $result = [
            'valid' => true,
            'error' => null,
            'debug_info' => [] // Pour stocker des informations de débogage
        ];
        
        // Vérifier les champs obligatoires
        if (!isset($data['employee_id']) || !$data['employee_id']) {
            $result['valid'] = false;
            $result['error'] = "ID d'employé manquant";
            return $result;
        }
        
        // Normaliser l'ID d'employé
        $data['employee_id'] = (int)$data['employee_id'];
        $result['debug_info']['normalized_employee_id'] = $data['employee_id'];
        
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
        
        $type = strtolower($data['type']);
        $isCheckIn = $type === 'check-in';
        $result['debug_info']['is_check_in'] = $isCheckIn;
        $result['debug_info']['type'] = $type;
        
        // Vérifier que l'horodatage est valide
        try {
            $timestamp = Carbon::parse($data['timestamp']);
            $result['debug_info']['parsed_date'] = $timestamp->toDateString();
        } catch (\Exception $e) {
            $result['valid'] = false;
            $result['error'] = "Format d'horodatage invalide";
            return $result;
        }
        
        // Vérifier que l'employé existe
        // Convertir l'ID en entier pour s'assurer qu'il est correctement traité
        $employeeId = (int)$data['employee_id'];
        $employe = Employe::find($employeeId);
        
        if (!$employe) {
            // Vérifier si l'employé existe avec un withTrashed()
            $employeTrashed = Employe::withTrashed()->find($employeeId);
            
            if ($employeTrashed) {
                $result['valid'] = false;
                $result['error'] = "L'employé avec l'ID {$employeeId} existe mais a été supprimé";
                $result['debug_info']['employee_trashed'] = true;
            } else {
                $result['valid'] = false;
                $result['error'] = "L'employé avec l'ID {$employeeId} n'existe pas";
                $result['debug_info']['employee_exists'] = false;
            }
            return $result;
        }
        
        $result['debug_info']['employee_exists'] = true;
        $result['debug_info']['employee_name'] = $employe->prenom . ' ' . $employe->nom;
        
        // Vérifier les coordonnées géographiques (gestion des deux formats possibles)
        $hasValidCoordinates = false;
        
        // Format 1: coordonnées au niveau racine
        if (isset($data['latitude']) && is_numeric($data['latitude']) && 
            isset($data['longitude']) && is_numeric($data['longitude'])) {
            $hasValidCoordinates = true;
            $result['debug_info']['coordinates_format'] = 'flat';
        }
        
        // Format 2: coordonnées dans un objet location
        if (isset($data['location']) && is_array($data['location']) &&
            isset($data['location']['latitude']) && is_numeric($data['location']['latitude']) && 
            isset($data['location']['longitude']) && is_numeric($data['location']['longitude'])) {
            $hasValidCoordinates = true;
            $result['debug_info']['coordinates_format'] = 'nested';
        }
        
        if (!$hasValidCoordinates) {
            $result['valid'] = false;
            $result['error'] = "Coordonnées géographiques invalides";
            $result['debug_info']['location_data'] = isset($data['location']) ? json_encode($data['location']) : 'missing';
            return $result;
        }
        
        // Vérifier le score biométrique (gestion des deux formats possibles)
        $hasValidBiometricScore = false;
        
        // Format 1: score biométrique au niveau racine
        if (isset($data['biometric_score']) && is_numeric($data['biometric_score']) && 
            $data['biometric_score'] >= 0 && $data['biometric_score'] <= 1) {
            $hasValidBiometricScore = true;
            $result['debug_info']['biometric_format'] = 'flat';
        }
        
        // Format 2: score biométrique dans un objet biometric_verification
        if (isset($data['biometric_verification']) && is_array($data['biometric_verification']) &&
            isset($data['biometric_verification']['confidence_score']) && 
            is_numeric($data['biometric_verification']['confidence_score']) &&
            $data['biometric_verification']['confidence_score'] >= 0 && 
            $data['biometric_verification']['confidence_score'] <= 1) {
            $hasValidBiometricScore = true;
            $result['debug_info']['biometric_format'] = 'nested';
        }
        
        if (!$hasValidBiometricScore) {
            $result['valid'] = false;
            $result['error'] = "Score biométrique invalide (doit être entre 0 et 1)";
            $result['debug_info']['biometric_data'] = isset($data['biometric_verification']) ? 
                json_encode($data['biometric_verification']) : 'missing';
            return $result;
        }
        
        // Vérifier la présence existante pour les check-out uniquement
        if (!$isCheckIn) {
            $presence = Presence::where('employe_id', $employeeId)
                ->where('date', $timestamp->toDateString())
                ->first();
                
            if (!$presence) {
                $result['valid'] = false;
                $result['error'] = "Aucun pointage d'arrivée trouvé pour cet employé à cette date";
                $result['debug_info']['existing_checkin'] = false;
                return $result;
            }
            $result['debug_info']['existing_checkin'] = true;
        }
        
        return $result;
    }
}