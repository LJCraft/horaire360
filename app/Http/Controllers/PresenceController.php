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
            
            // Définir la source de pointage (manuel par défaut pour la page "nouveau pointage")
            $validatedData['source_pointage'] = $request->input('source_pointage', 'manuel');
            
            // S'assurer explicitement que les pointages créés via le formulaire sont marqués comme "manuel"
            if (empty($request->input('source_pointage'))) {
                $validatedData['source_pointage'] = 'manuel';
            }
            
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
                // Récupérer le détail du planning pour le jour spécifique
                $jourSemaine = \Carbon\Carbon::parse($validatedData['date'])->dayOfWeekIso;
                $planningDetail = $planning->details()
                    ->where('jour', $jourSemaine)
                    ->first();
                
                if ($planningDetail && !$planningDetail->jour_repos && $planningDetail->heure_debut && $planningDetail->heure_fin) {
                    // Récupérer les critères de pointage pour les tolérances
                    $critere = $this->getCriterePointage($validatedData['employe_id'], $validatedData['date']);
                    $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                    $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                    
                    // Création des objets Carbon du planning détail
                    $heureDebutPlanning = \Carbon\Carbon::parse($planningDetail->heure_debut);
                    $heureFinPlanning   = \Carbon\Carbon::parse($planningDetail->heure_fin);
                    
                    // Appliquer les tolérances
                    $heureDebutPlanningTol  = (clone $heureDebutPlanning)->addMinutes($toleranceAvant);
                    $heureFinPlanningTol    = (clone $heureFinPlanning)->subMinutes($toleranceApres);
                    
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
        
        // Récupérer le détail du planning pour le jour spécifique
        $planningDetail = null;
        if ($planning) {
            $jourSemaine = $presence->date->dayOfWeekIso; // 1 (lundi) à 7 (dimanche)
            $planningDetail = $planning->details()
                ->where('jour', $jourSemaine)
                ->first();
        }
            
        return view('presences.show', compact('presence', 'planning', 'planningDetail'));
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
        
        // S'assurer explicitement que les modifications via le formulaire conservent la source "manuel" si non spécifiée
        if (empty($request->input('source_pointage')) && empty($presence->source_pointage)) {
            $validatedData['source_pointage'] = 'manuel';
        }
        
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
            
            // Récupérer le détail du planning pour le jour spécifique
            $jourSemaine = \Carbon\Carbon::parse($validatedData['date'])->dayOfWeekIso;
            $planningDetail = $planning->details()
                ->where('jour', $jourSemaine)
                ->first();
            
            // Déterminer si l'employé est en retard
            $retard = false;
            $departAnticipe = false;
            
            if ($planningDetail && !$planningDetail->jour_repos && $planningDetail->heure_debut && $planningDetail->heure_fin) {
                // Récupérer les critères de pointage pour les tolérances
                $critere = $this->getCriterePointage($validatedData['employe_id'], $validatedData['date']);
                $toleranceAvant = $critere ? $critere->tolerance_avant : 10;
                $toleranceApres = $critere ? $critere->tolerance_apres : 10;
                
                $heureDebutPlanning = \Carbon\Carbon::parse($planningDetail->heure_debut)->addMinutes($toleranceAvant);
                $heureArriveeCarbon = \Carbon\Carbon::parse($validatedData['heure_arrivee']);
                $retard = $heureArriveeCarbon > $heureDebutPlanning;
                
                if (isset($validatedData['heure_depart'])) {
                    $heureFinPlanning = \Carbon\Carbon::parse($planningDetail->heure_fin)->subMinutes($toleranceApres);
                    $heureDepartCarbon = \Carbon\Carbon::parse($validatedData['heure_depart']);
                    $departAnticipe = $heureDepartCarbon < $heureFinPlanning;
                }
            }
            
            // Ajout des champs de retard et départ anticipé
            $validatedData['retard'] = $retard;
            $validatedData['depart_anticipe'] = $departAnticipe;
            
            // Calcul des heures supplémentaires
            $validatedData['heures_supplementaires'] = 0;
            if (isset($validatedData['heure_depart']) && $planningDetail && $planningDetail->heure_fin) {
                $heuresSupplementaires = $this->calculerHeuresSupplementaires(
                    $validatedData['employe_id'],
                    $validatedData['date'],
                    $validatedData['heure_depart'],
                    $planningDetail->heure_fin
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
        // Rechercher tous les critères départementaux actifs pour cette période
        $critereDepartemental = \App\Models\CriterePointage::where('niveau', 'departemental')
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->orderBy('priorite')
            ->first();
            
        if ($critereDepartemental) {
            return $critereDepartemental;
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
            // Récupérer les paramètres de la requête actuelle pour maintenir le contexte de pagination
            $currentPage = request()->get('page', 1);
            $perPage = 15; // Même valeur que dans la méthode index
            $searchFilters = request()->except(['_token', '_method']);
            
            // Suppression de la présence
            $presence->delete();
            
            // Calculer la page correcte après suppression
            $redirectPage = $this->calculateCorrectPageAfterDeletion($searchFilters, $currentPage, $perPage);
            
            // Construire l'URL de redirection avec les bons paramètres
            $redirectUrl = route('presences.index', array_merge($searchFilters, ['page' => $redirectPage]));
            
            return redirect($redirectUrl)
                ->with('success', 'Présence supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la présence : ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de la présence.');
        }
    }

    /**
     * Calculer la page correcte après suppression d'une présence
     */
    private function calculateCorrectPageAfterDeletion($filters, $currentPage, $perPage)
    {
        // Reconstruire la requête avec les mêmes filtres que la page courante
        $presencesQuery = Presence::with('employe');
        
        // Appliquer les mêmes filtres que dans la méthode index
        if (isset($filters['employe']) && !empty($filters['employe'])) {
            $presencesQuery->where('employe_id', $filters['employe']);
        }
        
        if (isset($filters['date']) && !empty($filters['date'])) {
            $presencesQuery->whereDate('date', $filters['date']);
        }
        
        if (isset($filters['retard']) && $filters['retard'] !== '') {
            $presencesQuery->where('retard', (bool) $filters['retard']);
        }
        
        if (isset($filters['depart_anticipe']) && $filters['depart_anticipe'] !== '') {
            $presencesQuery->where('depart_anticipe', (bool) $filters['depart_anticipe']);
        }
        
        if (isset($filters['source_pointage']) && !empty($filters['source_pointage'])) {
            $presencesQuery->where('source_pointage', $filters['source_pointage']);
        }
        
        // Compter le nombre total de présences restantes
        $totalPresences = $presencesQuery->count();
        
        // Si aucune présence ne reste, rediriger vers la page 1
        if ($totalPresences === 0) {
            return 1;
        }
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalPresences / $perPage);
        
        // Si la page courante est supérieure au nombre total de pages, 
        // rediriger vers la dernière page disponible
        if ($currentPage > $totalPages) {
            return max(1, $totalPages);
        }
        
        // Sinon, rester sur la page courante
        return $currentPage;
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
     * Importe les données biométriques depuis un fichier .dat uniquement.
     */
    public function importBiometrique(Request $request)
    {
        try {
            // Nettoyer les anciennes anomalies en session AVANT toute nouvelle importation
            session()->forget(['import_anomalies', 'import_stats']);

            // Validation spécifique pour les fichiers .dat
            $request->validate([
                'fichier_biometrique' => 'required|file|mimes:dat,txt|max:10240',
                'skip_existing' => 'nullable|boolean'
            ]);

            $file = $request->file('fichier_biometrique');
            $skipExisting = $request->has('skip_existing');

            // Statistiques d'importation (réinitialisées pour chaque importation)
            $stats = [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => 0,
                'anomalies' => []
            ];

            // Session de diagnostic
            $diagnosticSession = 'import_dat_' . now()->format('YmdHis');
            $this->logDiagnostic($diagnosticSession, 'start', 'Début de l\'importation .dat', [
                'skip_existing' => $skipExisting,
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName()
            ]);

            // Lire le fichier ligne par ligne
            $fileContent = file_get_contents($file->path());
            $lines = explode("\n", $fileContent);
            
            foreach ($lines as $lineNumber => $line) {
                $lineNumber++; // Numérotation à partir de 1
                $line = trim($line);
                
                // Ignorer les lignes vides
                if (empty($line)) {
                    continue;
                }
                
                    $stats['total']++;
                
                // Parser la ligne selon le format .dat
                $result = $this->parseDatLine($line, $lineNumber, $skipExisting);
                $this->updateDatStats($stats, $result, $lineNumber, $line);
                
                $this->logDiagnostic($diagnosticSession, 'line_' . $lineNumber, 'Traitement ligne', [
                    'raw_line' => $line,
                    'result' => $result
                ]);
            }

            // Déterminer le message de statut approprié
            if ($stats['errors'] > 0) {
                $status = 'warning';
                $message = "Importation terminée avec des anomalies. {$stats['imported']} pointages importés, {$stats['skipped']} ignorés, {$stats['errors']} anomalies détectées.";
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

            // Stocker les anomalies en session UNIQUEMENT pour cette importation
            if (!empty($stats['anomalies'])) {
                session()->put('import_anomalies', $stats['anomalies']);
            } else {
                // S'assurer qu'il n'y a pas d'anciennes anomalies affichées
                session()->forget('import_anomalies');
            }

            return redirect()->route('rapports.biometrique')
                ->with($status, $message)
                ->with('import_stats', $stats);
        } 
        catch (\Exception $e) {
            $this->logDiagnostic($diagnosticSession ?? 'error', 'exception', 'Exception durant l\'importation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('rapports.biometrique')
                ->with('error', 'Erreur lors de l\'importation: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse une ligne du fichier .dat selon le format spécifié
     * Format: ID_Employe  Date  Heure  Type_Pointage  Terminal_ID
     */
    private function parseDatLine($line, $lineNumber, $skipExisting = false)
    {
        $result = [
            'success' => false,
            'message' => '',
            'skipped' => false,
            'line_number' => $lineNumber,
            'raw_line' => $line
        ];

        // Séparer les colonnes par des espaces multiples (\s{2,})
        $columns = preg_split('/\s{2,}/', trim($line));
        
        // Vérifier qu'on a exactement 5 colonnes
        if (count($columns) !== 5) {
            $result['message'] = "Format invalide - attendu 5 colonnes, trouvé " . count($columns);
            return $result;
        }

        list($employeId, $date, $heure, $typePointage, $terminalId) = $columns;

        // Validation de l'ID employé
        if (!is_numeric($employeId)) {
            $result['message'] = "ID employé invalide (doit être numérique): {$employeId}";
            return $result;
        }
        $employeId = (int)$employeId;

        // Vérifier que l'employé existe
        $employe = Employe::find($employeId);
        if (!$employe) {
            $result['message'] = "Employé non trouvé avec ID: {$employeId}";
            return $result;
        }

        // Validation de la date (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $result['message'] = "Format de date invalide (attendu YYYY-MM-DD): {$date}";
            return $result;
        }

        try {
            $dateCarbon = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            $result['message'] = "Date invalide: {$date}";
            return $result;
        }

        // Validation de l'heure (HH:MM:SS)
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure)) {
            $result['message'] = "Format d'heure invalide (attendu HH:MM:SS): {$heure}";
            return $result;
        }

        try {
            $heureCarbon = Carbon::createFromFormat('H:i:s', $heure);
        } catch (\Exception $e) {
            $result['message'] = "Heure invalide: {$heure}";
            return $result;
        }

        // Validation du type de pointage (0 ou 1)
        if (!in_array($typePointage, ['0', '1'])) {
            $result['message'] = "Type de pointage invalide (attendu 0 ou 1): {$typePointage}";
            return $result;
        }

        // Validation du terminal ID (doit être 1)
        if ($terminalId !== '1') {
            $result['message'] = "Terminal ID invalide (attendu 1): {$terminalId}";
            return $result;
        }

        // Traitement du pointage
        return $this->processDatPointage($employeId, $date, $heure, $typePointage, $skipExisting);
    }

    /**
     * Traite un pointage du fichier .dat
     */
    private function processDatPointage($employeId, $date, $heure, $typePointage, $skipExisting = false)
    {
        $result = [
            'success' => false,
            'message' => '',
            'skipped' => false
        ];

        $isCheckIn = ($typePointage === '1');
        
        // Chercher une présence existante pour cet employé à cette date
        $presence = Presence::where('employe_id', $employeId)
            ->where('date', $date)
            ->first();

        if ($isCheckIn) {
            // Pointage d'arrivée
            if ($presence && $skipExisting) {
                $result['message'] = "Pointage d'arrivée existant ignoré pour l'employé ID {$employeId} le {$date}";
                $result['skipped'] = true;
                return $result;
            }

            if (!$presence) {
                $presence = new Presence();
                $presence->employe_id = $employeId;
                $presence->date = $date;
                $presence->source_pointage = 'biometrique';
            }

            $presence->heure_arrivee = $heure;
            
            // Créer les métadonnées spécifiques au format .dat
            $metaData = [
                'terminal_id' => '1',  // Chaîne pour compatibilité avec la requête
                'type' => 'biometric_dat',
                'source' => 'reconnaissance_faciale_mobile',
                'type_pointage' => 1,  // Ajouter le type de pointage pour la logique de regroupement
                'device_info' => [
                    'model' => 'App Mobile - Reconnaissance Faciale',
                    'terminal_id' => '1',
                    'type' => 'mobile_facial_recognition'
                ],
                'validation' => [
                    'format' => 'dat_file',
                    'authentifie' => true,
                    'processed_at' => now()->toISOString()
                ]
            ];
            
            $presence->meta_data = json_encode($metaData);
            $presence->save();

            // Calculer les retards basés sur le planning
            $timestamp = \Carbon\Carbon::parse($date . ' ' . $heure);
            $this->checkForLateness($presence, $timestamp);
            $presence->save();

            $result['success'] = true;
            $result['message'] = "Pointage d'arrivée enregistré pour l'employé ID {$employeId} le {$date} à {$heure}";
        } else {
            // Pointage de sortie
            if (!$presence) {
                $result['message'] = "Aucun pointage d'arrivée trouvé pour l'employé ID {$employeId} le {$date} - impossible d'enregistrer la sortie";
                return $result;
            }

            if ($presence->heure_depart && $skipExisting) {
                $result['message'] = "Pointage de sortie existant ignoré pour l'employé ID {$employeId} le {$date}";
                $result['skipped'] = true;
                return $result;
            }

            $presence->heure_depart = $heure;
            
            // Mettre à jour les métadonnées avec les informations de départ
            $metaData = json_decode($presence->meta_data, true) ?? [];
            $metaData['type_pointage'] = 0;  // Ajouter le type de pointage pour la sortie
            $metaData['checkout'] = [
                'type' => 'biometric_dat_checkout',
                'source' => 'reconnaissance_faciale_mobile',
                'device_info' => [
                    'model' => 'App Mobile - Reconnaissance Faciale',
                    'terminal_id' => '1',
                    'type' => 'mobile_facial_recognition'
                ],
                'validation' => [
                    'format' => 'dat_file',
                    'authentifie' => true,
                    'processed_at' => now()->toISOString()
                ]
            ];
            
            $presence->meta_data = json_encode($metaData);
            
            // Calculer les départs anticipés basés sur le planning
            $timestamp = \Carbon\Carbon::parse($date . ' ' . $heure);
            $this->checkForEarlyDeparture($presence, $timestamp);
            $presence->save();

            $result['success'] = true;
            $result['message'] = "Pointage de sortie enregistré pour l'employé ID {$employeId} le {$date} à {$heure}";
        }

        return $result;
    }

    /**
     * Mettre à jour les statistiques d'importation .dat
     */
    private function updateDatStats(&$stats, $result, $lineNumber, $rawLine)
    {
        if ($result['success']) {
            $stats['imported']++;
        } else if (isset($result['skipped']) && $result['skipped']) {
            $stats['skipped']++;
        } else {
            $stats['errors']++;
            $stats['anomalies'][] = [
                'line_number' => $lineNumber,
                'raw_line' => $rawLine,
                'error' => $result['message']
            ];
        }
    }

    /**
     * Télécharger un modèle de fichier .dat simplifié avec données valides
     */
    public function downloadDatTemplate()
    {
        // Récupérer quelques employés réels de la base de données
        $employes = Employe::where('statut', 'actif')->limit(3)->get(['id', 'nom', 'prenom']);
        
        // Générer des données simples sans commentaires
        $today = now()->format('Y-m-d');
        $content = "";
        
        if ($employes->count() > 0) {
            // Employé 1 - Journée normale
            $emp1 = $employes->first();
            $content .= "{$emp1->id}  {$today}  08:00:00  1  1\n";
            $content .= "{$emp1->id}  {$today}  17:00:00  0  1\n";
            
            // Employé 2 - Si disponible
            if ($employes->count() > 1) {
                $emp2 = $employes->get(1);
                $content .= "{$emp2->id}  {$today}  08:15:00  1  1\n";
                $content .= "{$emp2->id}  {$today}  17:15:00  0  1\n";
            }
            
            // Employé 3 - Si disponible
            if ($employes->count() > 2) {
                $emp3 = $employes->get(2);
                $content .= "{$emp3->id}  {$today}  07:55:00  1  1\n";
                $content .= "{$emp3->id}  {$today}  16:30:00  0  1\n";
            }
        } else {
            // Fallback si aucun employé trouvé
            $content .= "1  {$today}  08:00:00  1  1\n";
            $content .= "1  {$today}  17:00:00  0  1\n";
        }

        $fileName = 'pointage_biometrique_' . now()->format('Y-m-d') . '.dat';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
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
                ->where('actif', true)
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
                ->where('actif', true)
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
     * Détecte le délimiteur CSV (méthode héritée - conservée pour compatibilité)
     */

    
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
    

}