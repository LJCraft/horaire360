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
    ->whereRaw('DATE(created_at) = ?', [$validatedData['date']])
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
                ->where('date', $validatedData['date'])
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
            ->where('date', $presence->date)
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
                ->where('date', $validatedData['date'])
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
    public function downloadTemplate()
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
}