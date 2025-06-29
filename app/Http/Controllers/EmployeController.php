<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use App\Models\Poste;
use App\Models\User;
use App\Imports\EmployesImport;
use App\Exports\EmployesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Afficher la liste des employés
     */
    public function index(Request $request)
    {
        $query = Employe::with('poste');
        
        // Filtres par rôle utilisateur
        if (Auth::user()->role_id === 1) { // Supposons que role_id 1 est admin
            // Admin peut voir tous les employés
        } else {
            // Utilisateur standard ne voit que son propre profil
            $query->where('utilisateur_id', Auth::id());
        }
        
        // Filtres de recherche
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('matricule', 'like', "%$search%");
            });
        }
        
        if ($request->has('poste_id') && $request->poste_id) {
            $query->where('poste_id', $request->poste_id);
        }
        
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }
        
        // Filtre par accès au système
        if ($request->has('acces')) {
            if ($request->acces === 'avec') {
                $query->whereNotNull('utilisateur_id');
            } elseif ($request->acces === 'sans') {
                $query->whereNull('utilisateur_id');
            }
        }
        
        // Tri
        $sortField = $request->input('sort', 'nom');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination optimisée - augmenter le nombre par page et utiliser simplePaginate pour les grandes listes
        $perPage = $request->input('per_page', 25); // Par défaut 25 employés par page
        $employes = $query->simplePaginate($perPage);
        
        // Statistiques de filtrage
        // Nous utilisons des requêtes séparées pour calculer les statistiques afin de ne pas perturber la pagination
        $statsQuery = clone $query;
        
        // Statistiques filtrées (selon les critères de recherche actuels)
        $filteredCount = $statsQuery->count();
        
        // Statistiques par statut avec les mêmes filtres (sauf le statut lui-même)
        $statsBaseQuery = Employe::query();
        
        // Appliquer les mêmes filtres que la requête principale, sauf le statut
        if ($request->has('search')) {
            $search = $request->input('search');
            $statsBaseQuery->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('matricule', 'like', "%$search%");
            });
        }
        
        if ($request->has('poste_id') && $request->poste_id) {
            $statsBaseQuery->where('poste_id', $request->poste_id);
        }
        
        // Statistiques par statut
        $actifsCount = (clone $statsBaseQuery)->where('statut', 'actif')->count();
        $inactifsCount = (clone $statsBaseQuery)->where('statut', 'inactif')->count();
        
        // Statistiques globales (sans filtres)
        $totalCount = Employe::count();
        $totalActifsCount = Employe::where('statut', 'actif')->count();
        $totalInactifsCount = Employe::where('statut', 'inactif')->count();
        
        // Statistiques d'accès au système
        $avecAccesCount = (clone $statsBaseQuery)->whereNotNull('utilisateur_id')->count();
        $sansAccesCount = (clone $statsBaseQuery)->whereNull('utilisateur_id')->count();
        $totalAvecAccesCount = Employe::whereNotNull('utilisateur_id')->count();
        $totalSansAccesCount = Employe::whereNull('utilisateur_id')->count();
        
        // Statistiques par poste (pour le poste sélectionné)
        $posteStats = null;
        if ($request->has('poste_id') && $request->poste_id) {
            $posteStats = [
                'nom' => Poste::find($request->poste_id)->nom,
                'total' => (clone $statsBaseQuery)->count(),
                'actifs' => (clone $statsBaseQuery)->where('statut', 'actif')->count(),
                'inactifs' => (clone $statsBaseQuery)->where('statut', 'inactif')->count(),
            ];
        }
        
        $postes = Poste::all();
        
        // Statistiques à passer à la vue
        $stats = [
            'filtered' => $filteredCount,
            'actifs' => $actifsCount,
            'inactifs' => $inactifsCount,
            'total' => $totalCount,
            'totalActifs' => $totalActifsCount,
            'totalInactifs' => $totalInactifsCount,
            'avecAcces' => $avecAccesCount,
            'sansAcces' => $sansAccesCount,
            'totalAvecAcces' => $totalAvecAccesCount,
            'totalSansAcces' => $totalSansAccesCount,
            'poste' => $posteStats
        ];
        
        return view('employes.index', compact('employes', 'postes', 'perPage', 'stats'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $postes = Poste::all();
        return view('employes.create', compact('postes'));
    }

    /**
     * Enregistrer un nouvel employé
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'date_embauche' => 'required|date',
            'poste_id' => 'required|exists:postes,id',
            'create_user' => 'boolean',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        // Vérifier si l'email existe déjà
        $email = $request->email;
        $emailExists = Employe::where('email', $email)->exists() || User::where('email', $email)->exists();
        
        if ($emailExists) {
            // Générer un email alternatif basé sur prenom.nom
            $baseEmail = $this->generateBaseEmail($request->prenom, $request->nom);
            $randomNum = rand(100, 999);
            $alternativeEmail = $baseEmail . $randomNum . '@gmail.com';
            
            // Vérifier que l'email alternatif n'existe pas déjà
            while (Employe::where('email', $alternativeEmail)->exists() || User::where('email', $alternativeEmail)->exists()) {
                $randomNum = rand(100, 999);
                $alternativeEmail = $baseEmail . $randomNum . '@gmail.com';
            }
            
            // Ajouter l'erreur d'email existant avec suggestion d'alternative
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => "Cet email existe déjà. Essayez plutôt : {$alternativeEmail}"]);
        }
        
        // Continuer la validation
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Génération du matricule unique (préfixe EMP + 5 chiffres)
        $lastId = Employe::max('id') ?? 0;
        $nextId = $lastId + 1;
        $matricule = 'EMP' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
        
        // Vérifier que le matricule est unique
        while (Employe::where('matricule', $matricule)->exists()) {
            $nextId++;
            $matricule = 'EMP' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
        }
        
        $employeData = [
            'matricule' => $matricule,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'date_naissance' => $request->date_naissance,
            'date_embauche' => $request->date_embauche,
            'poste_id' => $request->poste_id,
            'statut' => 'actif',
        ];
        
        // Traitement de la photo de profil
        if ($request->hasFile('photo_profil')) {
            $photoFile = $request->file('photo_profil');
            $photoName = time() . '_' . $matricule . '.' . $photoFile->getClientOriginalExtension();
            
            // Définir le chemin avec DIRECTORY_SEPARATOR pour la compatibilité cross-platform
            $photoDirectory = public_path('storage'.DIRECTORY_SEPARATOR.'photos');
            
            // S'assurer que le répertoire existe
            try {
                if (!is_dir($photoDirectory)) {
                    mkdir($photoDirectory, 0755, true);
                }
                
                // Vérifier que le dossier est accessible en écriture
                if (!is_writable($photoDirectory)) {
                    chmod($photoDirectory, 0755);
                }
                
                // Déplacer le fichier
                $photoFile->move($photoDirectory, $photoName);
                
                // Sauvegarder uniquement le nom du fichier
                $employeData['photo_profil'] = $photoName;
                
                // Log pour débogage
                Log::info('Photo enregistrée dans: ' . $photoDirectory . DIRECTORY_SEPARATOR . $photoName);
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'enregistrement de la photo: ' . $e->getMessage());
                Log::error('Chemin du dossier: ' . $photoDirectory);
            }
        }
        
        $employe = new Employe($employeData);
        
        // Création d'un compte utilisateur si demandé
        if ($request->create_user) {
            $password = 'password'; // Mot de passe par défaut
            $user = User::create([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role_id' => 2, // Rôle Employé
            ]);
            
            $employe->utilisateur_id = $user->id;
            
            // Ici, vous pourriez envoyer un email avec les identifiants
            // Mail::to($request->email)->send(new NouveauCompte($user, $password));
        }
        
        $employe->save();
        
        $message = 'Employé créé avec succès.';
        if ($request->create_user) {
            $message .= ' Un compte utilisateur a été créé avec l\'email "'.$request->email.'" et le mot de passe "'.$password.'".';
        }
        
        return redirect()->route('employes.index')
            ->with('success', $message);
    }

    /**
     * Afficher les détails d'un employé
     */
    public function show(Employe $employe)
    {
        return view('employes.show', compact('employe'));
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(Employe $employe)
    {
        $postes = Poste::all();
        return view('employes.edit', compact('employe', 'postes'));
    }

    /**
     * Mettre à jour un employé
     */
    public function update(Request $request, Employe $employe)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email,' . $employe->id,
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'date_embauche' => 'required|date',
            'poste_id' => 'required|exists:postes,id',
            'statut' => 'required|in:actif,inactif',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        $dataToUpdate = $request->except(['photo_profil', 'supprimer_photo']);
        
        // Traitement de la photo de profil
        if ($request->hasFile('photo_profil')) {
            // Supprimer l'ancienne photo si elle existe
            if ($employe->photo_profil) {
                $oldPhotoPath = public_path('storage'.DIRECTORY_SEPARATOR.'photos'.DIRECTORY_SEPARATOR.$employe->photo_profil);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                    Log::info('Photo supprimée: ' . $oldPhotoPath);
                }
            }
            
            // Enregistrer la nouvelle photo
            $photoFile = $request->file('photo_profil');
            $photoName = time() . '_' . $employe->matricule . '.' . $photoFile->getClientOriginalExtension();
            
            // Définir le chemin avec DIRECTORY_SEPARATOR pour la compatibilité cross-platform
            $photoDirectory = public_path('storage'.DIRECTORY_SEPARATOR.'photos');
            
            // S'assurer que le répertoire existe
            try {
                if (!is_dir($photoDirectory)) {
                    mkdir($photoDirectory, 0755, true);
                }
                
                // Vérifier que le dossier est accessible en écriture
                if (!is_writable($photoDirectory)) {
                    chmod($photoDirectory, 0755);
                }
                
                // Déplacer le fichier
                $photoFile->move($photoDirectory, $photoName);
                
                // Sauvegarder uniquement le nom du fichier
                $dataToUpdate['photo_profil'] = $photoName;
                
                // Log pour débogage
                Log::info('Photo mise à jour dans: ' . $photoDirectory . DIRECTORY_SEPARATOR . $photoName);
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'enregistrement de la photo: ' . $e->getMessage());
                Log::error('Chemin du dossier: ' . $photoDirectory);
            }
        }
        // Supprimer la photo si demandé
        else if ($request->has('supprimer_photo')) {
            if ($employe->photo_profil) {
                $oldPhotoPath = public_path('storage'.DIRECTORY_SEPARATOR.'photos'.DIRECTORY_SEPARATOR.$employe->photo_profil);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                    Log::info('Photo supprimée: ' . $oldPhotoPath);
                }
            }
            $dataToUpdate['photo_profil'] = null;
        }
        
        $employe->update($dataToUpdate);
        
        // Si l'employé a un compte utilisateur, mettre à jour son nom et email
        if ($employe->utilisateur) {
            $employe->utilisateur->update([
                'name' => $request->prenom . ' ' . $request->nom,
                'email' => $request->email,
            ]);
        }
        
        return redirect()->route('employes.index')
            ->with('success', 'Employé modifié avec succès.');
    }

    /**
     * Supprimer un employé
     */
    public function destroy(Employe $employe)
    {
        try {
            // Récupérer les paramètres de la requête actuelle pour maintenir le contexte de pagination
            $currentPage = request()->get('page', 1);
            $perPage = request()->get('per_page', 25);
            $searchFilters = request()->except(['_token', '_method']);
            
            // Commencer une transaction pour garantir l'intégrité des données
            DB::beginTransaction();
            
            // Supprimer manuellement les enregistrements liés
            
            // Récupérer l'ID de l'employé pour les requêtes directes
            $employeId = $employe->id;
            
            // Récupérer l'utilisateur associé avant de supprimer l'employé
            $utilisateur = $employe->utilisateur;
            
            // Supprimer les présences de manière définitive
            \App\Models\Presence::where('employe_id', $employeId)->delete();
            
            // Supprimer les plannings et leurs détails
            $plannings = \App\Models\Planning::where('employe_id', $employeId)->get();
            foreach ($plannings as $planning) {
                \App\Models\PlanningDetail::where('planning_id', $planning->id)->delete();
                $planning->delete();
            }
            
            // Supprimer les congés si le modèle existe
            if (class_exists('\App\Models\Conge')) {
                \App\Models\Conge::where('employe_id', $employeId)->delete();
            }
            
            // Supprimer la photo de profil si elle existe
            if (!empty($employe->photo_profil)) {
                $oldPhotoPath = public_path('storage'.DIRECTORY_SEPARATOR.'photos'.DIRECTORY_SEPARATOR.$employe->photo_profil);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                    Log::info('Photo supprimée: ' . $oldPhotoPath);
                }
            }
            
            // Supprimer l'employé
            $employe->delete();
            
            // Supprimer définitivement l'utilisateur associé s'il existe
            // Le modèle User utilise SoftDeletes, donc il faut utiliser forceDelete
            if ($utilisateur) {
                $utilisateur->forceDelete();
            }
            
            // Valider la transaction
            DB::commit();
            
            // Calculer la page correcte après suppression
            $redirectPage = $this->calculateCorrectPageAfterDeletion($searchFilters, $currentPage, $perPage);
            
            // Construire l'URL de redirection avec les bons paramètres
            $redirectUrl = route('employes.index', array_merge($searchFilters, ['page' => $redirectPage]));
            
            return redirect($redirectUrl)
                ->with('success', 'Employé et toutes ses données associées supprimés définitivement.');
                
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
            
            Log::error('Erreur lors de la suppression de l\'employé: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->route('employes.index')
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Calculer la page correcte après suppression d'un employé
     */
    private function calculateCorrectPageAfterDeletion($filters, $currentPage, $perPage)
    {
        // Reconstruire la requête avec les mêmes filtres que la page courante
        $query = Employe::with('poste');
        
        // Appliquer les mêmes filtres que dans la méthode index
        if (Auth::user()->role_id !== 1) {
            $query->where('utilisateur_id', Auth::id());
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('matricule', 'like', "%$search%");
            });
        }
        
        if (isset($filters['poste_id']) && !empty($filters['poste_id'])) {
            $query->where('poste_id', $filters['poste_id']);
        }
        
        if (isset($filters['statut']) && !empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
        
        if (isset($filters['acces']) && !empty($filters['acces'])) {
            if ($filters['acces'] === 'avec') {
                $query->whereNotNull('utilisateur_id');
            } elseif ($filters['acces'] === 'sans') {
                $query->whereNull('utilisateur_id');
            }
        }
        
        // Compter le nombre total d'employés restants
        $totalEmployes = $query->count();
        
        // Si aucun employé ne reste, rediriger vers la page 1
        if ($totalEmployes === 0) {
            return 1;
        }
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalEmployes / $perPage);
        
        // Si la page courante est supérieure au nombre total de pages, 
        // rediriger vers la dernière page disponible
        if ($currentPage > $totalPages) {
            return max(1, $totalPages);
        }
        
        // Sinon, rester sur la page courante
        return $currentPage;
    }

    /**
     * Exporter la liste des employés
     */
    public function export(Request $request)
    {
        try {
            $format = $request->format ?? 'xlsx';
            
            if ($format === 'pdf') {
                return $this->exportPdf();
            }
            
            if ($format === 'csv') {
                return Excel::download(new EmployesExport, 'employes.csv', \Maatwebsite\Excel\Excel::CSV);
            }
            
            return Excel::download(new EmployesExport, 'employes.' . $format);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['export' => 'Erreur lors de l\'exportation : ' . $e->getMessage()]);
        }
    }

    /**
     * Exporter la liste des employés au format PDF
     */
    public function exportPdf()
    {
        try {
            $employes = Employe::with('poste')->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.employes_pdf', compact('employes'));
            return $pdf->download('employes.pdf');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['export' => 'Erreur lors de l\'exportation PDF : ' . $e->getMessage()]);
        }
    }

    /**
     * Importer des employés depuis un fichier Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
        
        try {
            Log::info("Début d'importation d'employés");
            
            // Vérifier d'abord la structure du fichier pour détecter la ligne d'en-tête
            $this->checkImportFile($request);
            
            $import = new EmployesImport;
            $import->import($request->file('file'));
            
            $successCount = session('import_success_count', 0);
            $successMessage = $successCount > 0 
                ? "Importation réussie : $successCount employé(s) importé(s)." 
                : "Aucun employé n'a été importé.";
                
            // Vérifier s'il y a des erreurs d'importation
            if (session()->has('import_errors')) {
                $errors = session()->get('import_errors');
                
                if (count($errors) > 0) {
                    Log::warning("Importation avec erreurs: " . json_encode($errors));
                    
                    // S'il y a des erreurs mais aussi des imports réussis
                    if ($successCount > 0) {
                        session()->forget(['import_success_count', 'import_header_row']);
                        
                        return redirect()->route('employes.index', ['page' => 1])
                            ->with('warning', $successMessage . ' Certaines lignes n\'ont pas été importées.')
                            ->with('import_errors', $errors);
                    } else {
                        // Uniquement des erreurs, pas d'imports réussis
                        session()->forget(['import_errors', 'import_success_count', 'import_header_row']);
                        
                        // Analyser les erreurs pour un message plus précis
                        $errorSummary = $this->categorizeImportErrors($errors);
                        $errorMessage = 'Erreur : aucun employé n\'a pu être importé. ';
                        
                        if (!empty($errorSummary)) {
                            $errorMessage .= 'Raison(s) : ' . $errorSummary;
                        } else {
                            $errorMessage .= 'Vérifiez le format du fichier et les entêtes de colonnes.';
                        }
                        
                        return redirect()->back()
                            ->withErrors(['file' => $errorMessage])
                            ->with('import_errors', $errors);
                    }
                }
            }
            
            session()->forget(['import_success_count', 'import_header_row']);
            
            // Si on arrive ici, c'est que tout s'est bien passé
            return redirect()->route('employes.index', ['page' => 1])
                ->with('success', $successMessage);
                
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            
            foreach ($failures as $failure) {
                $errors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
            }
            
            Log::error("Échec de validation Excel: " . json_encode($errors));
            
            session()->forget('import_header_row');
            
            return redirect()->back()
                ->withErrors(['file' => 'Erreurs de validation dans le fichier importé. Vérifiez le format.'])
                ->with('import_errors', $errors);
                
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            Log::error("Erreur de lecture du fichier Excel: " . $e->getMessage());
            
            session()->forget('import_header_row');
            
            return redirect()->back()
                ->withErrors(['file' => 'Impossible de lire le fichier Excel. Format non reconnu ou fichier corrompu.']);
                
        } catch (\Exception $e) {
            Log::error("Erreur générale d'importation: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            session()->forget('import_header_row');
            
            // Analyser le message d'erreur pour fournir des informations plus claires
            $errorMessage = 'Erreur lors de l\'importation. ';
            
            if (strpos($e->getMessage(), 'Duplicata') !== false && strpos($e->getMessage(), 'matricule') !== false) {
                $errorMessage .= 'Un ou plusieurs matricules existent déjà dans la base de données.';
            } 
            else if (strpos($e->getMessage(), 'Duplicata') !== false && strpos($e->getMessage(), 'email') !== false) {
                $errorMessage .= 'Une ou plusieurs adresses email existent déjà dans la base de données.';
            }
            else if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMessage .= 'Violation de contrainte d\'intégrité. Vérifiez les valeurs uniques comme le matricule ou l\'email.';
            }
            else {
                $errorMessage .= $e->getMessage();
            }
            
            return redirect()->back()
                ->withErrors(['file' => $errorMessage]);
        }
    }

    /**
     * Exporter le modèle d'importation
     */
    public function exportTemplate()
    {
        return Excel::download(new EmployesExport(true), 'modele_employes.xlsx');
    }

    /**
     * Vérifier le format du fichier avant import
     */
    public function checkImportFile(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
        
        try {
            // Charger le fichier sans l'importer pour examiner sa structure
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            if ($highestRow < 4) { // Minimum 4 lignes (2 pour info, 1 pour en-têtes, 1 pour données)
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier ne contient pas suffisamment de données (au moins 4 lignes requises).',
                ]);
            }
            
            // Vérifier si le fichier correspond à notre modèle (avec lignes de titre)
            $firstCell = $worksheet->getCell('A1')->getValue();
            $isTemplateFormat = (strpos($firstCell, "MODÈLE D'IMPORTATION") !== false);
            $headerRow = $isTemplateFormat ? 3 : 1; // Si c'est notre modèle, les en-têtes sont à la ligne 3
            
            // Enregistrer la ligne d'en-tête dans la session
            session(['import_header_row' => $headerRow]);
            Log::info("Ligne d'en-tête détectée et enregistrée: $headerRow");
            
            // Récupérer les en-têtes
            $headers = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $headers[$col] = $worksheet->getCell($col . $headerRow)->getValue();
            }
            
            // Récupérer un exemple de données (première ligne après en-têtes)
            $sampleDataRow = $headerRow + 1;
            $sampleData = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $sampleData[$col] = $worksheet->getCell($col . $sampleDataRow)->getFormattedValue();
            }
            
            // Vérifier si les en-têtes requis sont présents
            $requiredColumns = ['nom', 'prenom', 'email', 'date_embauche', 'poste'];
            $foundColumns = [];
            $missingColumns = [];
            
            foreach ($requiredColumns as $required) {
                $found = false;
                foreach ($headers as $header) {
                    // Amélioration de la détection des en-têtes
                    if ($header) {
                        // Normaliser l'en-tête pour la comparaison
                        $normalizedHeader = strtolower(trim(preg_replace('/[*\s]+/', '', $header)));
                        // Enlever les accents
                        $normalizedHeader = strtr($normalizedHeader, [
                            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                            'à' => 'a', 'â' => 'a', 'ä' => 'a',
                            'î' => 'i', 'ï' => 'i',
                            'ô' => 'o', 'ö' => 'o',
                            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                        ]);
                        
                        // Variantes courants des noms de colonnes
                        $variants = [
                            'nom' => ['nom', 'name', 'lastname', 'family'],
                            'prenom' => ['prenom', 'firstname', 'given'],
                            'email' => ['email', 'mail', 'courriel'],
                            'date_embauche' => ['dateembauche', 'embauche', 'hiring', 'datedebut'],
                            'poste' => ['poste', 'position', 'job', 'titre', 'fonction']
                        ];
                        
                        if ($normalizedHeader === $required || 
                            strpos($normalizedHeader, $required) !== false) {
                            $found = true;
                            break;
                        }
                        
                        // Vérifier les variantes
                        if (isset($variants[$required])) {
                            foreach ($variants[$required] as $variant) {
                                if (strpos($normalizedHeader, $variant) !== false) {
                                    $found = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                if ($found) {
                    $foundColumns[] = $required;
                } else {
                    $missingColumns[] = $required;
                }
            }
            
            return response()->json([
                'success' => true,
                'file_info' => [
                    'rows' => $highestRow,
                    'columns' => $highestColumn,
                    'headers' => $headers,
                    'header_row' => $headerRow,
                    'is_template_format' => $isTemplateFormat,
                    'sample_data' => $sampleData,
                    'sample_data_row' => $sampleDataRow,
                    'found_columns' => $foundColumns,
                    'missing_columns' => $missingColumns,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse du fichier: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Importer des employés directement depuis un fichier Excel (méthode alternative)
     */
    public function importDirect(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
        
        try {
            Log::info("Début d'importation directe d'employés");
            
            // Charger le fichier Excel directement
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            // Déterminer la ligne d'en-tête (par défaut la première ligne)
            $headerRow = 1;
            
            // Vérifier si c'est notre modèle d'importation
            $firstCell = $worksheet->getCell('A1')->getValue();
            if (strpos($firstCell, "MODÈLE D'IMPORTATION") !== false) {
                $headerRow = 3; // Les en-têtes sont à la ligne 3 dans notre modèle
            }
            
            Log::info("Ligne d'en-tête détectée: $headerRow");
            
            // Récupérer les en-têtes
            $headers = [];
            $columnIndexMap = [];
            
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $header = $worksheet->getCell($col . $headerRow)->getValue();
                if ($header) {
                    $headers[$col] = $header;
                    
                    // Normaliser le nom d'en-tête pour correspondre aux champs du modèle
                    $normalizedHeader = strtolower(trim(str_replace('*', '', $header)));
                    
                    // Mappings spécifiques
                    if (in_array($normalizedHeader, ['nom', 'name', 'lastname'])) {
                        $columnIndexMap['nom'] = $col;
                    } 
                    else if (in_array($normalizedHeader, ['prénom', 'prenom', 'firstname'])) {
                        $columnIndexMap['prenom'] = $col;
                    }
                    else if (in_array($normalizedHeader, ['email', 'courriel'])) {
                        $columnIndexMap['email'] = $col;
                    }
                    else if (strpos($normalizedHeader, "date d'embauche") !== false || 
                             strpos($normalizedHeader, "date embauche") !== false) {
                        $columnIndexMap['date_embauche'] = $col;
                    }
                    else if (in_array($normalizedHeader, ['poste', 'position', 'titre'])) {
                        $columnIndexMap['poste'] = $col;
                    }
                    else if (in_array($normalizedHeader, ['téléphone', 'telephone', 'phone'])) {
                        $columnIndexMap['telephone'] = $col;
                    }
                    else if (strpos($normalizedHeader, "date de naissance") !== false || 
                             $normalizedHeader == 'date naissance') {
                        $columnIndexMap['date_naissance'] = $col;
                    }
                    else if (in_array($normalizedHeader, ['matricule', 'id'])) {
                        $columnIndexMap['matricule'] = $col;
                    }
                    else if (in_array($normalizedHeader, ['département', 'departement'])) {
                        $columnIndexMap['departement'] = $col;
                    }
                    else if ($normalizedHeader == 'statut') {
                        $columnIndexMap['statut'] = $col;
                    }
                    else if (strpos($normalizedHeader, 'compte utilisateur') !== false || 
                             strpos($normalizedHeader, 'user account') !== false) {
                        $columnIndexMap['compte_utilisateur'] = $col;
                    }
                }
            }
            
            Log::info("En-têtes trouvés: " . json_encode($headers));
            Log::info("Correspondance des colonnes: " . json_encode($columnIndexMap));
            
            // Vérifier que les colonnes obligatoires sont présentes
            $requiredColumns = ['nom', 'prenom', 'email', 'date_embauche', 'poste'];
            $missingColumns = [];
            
            foreach ($requiredColumns as $column) {
                if (!isset($columnIndexMap[$column])) {
                    $missingColumns[] = $column;
                }
            }
            
            if (!empty($missingColumns)) {
                return redirect()->back()
                    ->withErrors(['file' => 'Colonnes obligatoires manquantes: ' . implode(', ', $missingColumns)]);
            }
            
            // Traiter les lignes de données
            $importCount = 0;
            $errors = [];
            
            for ($rowIndex = $headerRow + 1; $rowIndex <= $highestRow; $rowIndex++) {
                try {
                    // Vérifier si la ligne a des données
                    $hasData = false;
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        if ($worksheet->getCell($col . $rowIndex)->getValue() !== null) {
                            $hasData = true;
                            break;
                        }
                    }
                    
                    if (!$hasData) continue; // Ignorer les lignes vides
                    
                    // Récupérer les données de la ligne
                    $rowData = [];
                    foreach ($columnIndexMap as $field => $col) {
                        $cell = $worksheet->getCell($col . $rowIndex);
                        $value = $cell->getValue();
                        $rowData[$field] = $value;
                    }
                    
                    Log::info("Données de la ligne $rowIndex: " . json_encode($rowData));
                    
                    // Valider les champs obligatoires
                    $isValid = true;
                    foreach ($requiredColumns as $column) {
                        if (empty($rowData[$column])) {
                            $errors[] = "Ligne $rowIndex: Le champ '$column' est obligatoire";
                            $isValid = false;
                            break;
                        }
                    }
                    
                    if (!$isValid) continue;
                    
                    // Traiter les dates
                    $dateNaissance = null;
                    if (!empty($rowData['date_naissance'])) {
                        try {
                            if (is_numeric($rowData['date_naissance'])) {
                                $dateNaissance = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rowData['date_naissance']);
                            } else {
                                $dateNaissance = \Carbon\Carbon::parse($rowData['date_naissance']);
                            }
                        } catch (\Exception $e) {
                            Log::warning("Erreur de conversion date naissance ligne $rowIndex: " . $e->getMessage());
                        }
                    }
                    
                    $dateEmbauche = null;
                    try {
                        if (is_numeric($rowData['date_embauche'])) {
                            $dateEmbauche = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rowData['date_embauche']);
                        } else {
                            $dateEmbauche = \Carbon\Carbon::parse($rowData['date_embauche']);
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Ligne $rowIndex: Erreur de format pour la date d'embauche";
                        Log::warning("Erreur de conversion date embauche ligne $rowIndex: " . $e->getMessage());
                        continue;
                    }
                    
                    // Trouver ou créer le poste
                    $poste = Poste::firstOrCreate(
                        ['nom' => trim($rowData['poste'])],
                        ['departement' => $rowData['departement'] ?? null]
                    );
                    
                    // Vérifier si l'email existe déjà
                    $existingEmploye = Employe::where('email', trim($rowData['email']))->first();
                    if ($existingEmploye) {
                        $errors[] = "Ligne $rowIndex: L'email '{$rowData['email']}' existe déjà pour un autre employé";
                        continue;
                    }
                    
                    // Vérifier si cet email existe déjà dans les lignes précédentes de ce même fichier
                    $emailToCheck = strtolower(trim($rowData['email']));
                    $duplicateEmailsInFile = [];
                    for ($i = $headerRow + 1; $i < $rowIndex; $i++) {
                        $previousCol = $columnIndexMap['email'];
                        $previousEmail = strtolower(trim($worksheet->getCell($previousCol . $i)->getValue() ?? ''));
                        if ($previousEmail && $previousEmail === $emailToCheck) {
                            $duplicateEmailsInFile[] = $i;
                        }
                    }
                    
                    if (!empty($duplicateEmailsInFile)) {
                        $errorMessage = "Ligne $rowIndex: L'email '{$rowData['email']}' est en doublon avec la/les ligne(s) " . implode(', ', $duplicateEmailsInFile) . " du fichier";
                        $errors[] = $errorMessage;
                        Log::warning($errorMessage);
                        continue;
                    }
                    
                    // Générer matricule si nécessaire
                    if (!empty($rowData['matricule'])) {
                        $matricule = trim($rowData['matricule']);
                        
                        // Vérifier si ce matricule existe déjà
                        if (Employe::where('matricule', $matricule)->exists()) {
                            // Générer un matricule unique
                            $lastId = Employe::max('id') ?? 0;
                            $nextId = $lastId + 1;
                            $matricule = 'EMP' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                            
                            // Vérifier que le matricule est unique
                            while (Employe::where('matricule', $matricule)->exists()) {
                                $nextId++;
                                $matricule = 'EMP' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                            }
                            
                            $errors[] = "Ligne $rowIndex: Le matricule '{$rowData['matricule']}' existe déjà, un nouveau matricule a été généré: $matricule";
                        }
                    } else {
                        // Générer un matricule unique automatiquement
                        $lastId = Employe::max('id') ?? 0;
                        $nextId = $lastId + 1;
                        $matricule = 'EMP' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                        
                        // Vérifier que le matricule est unique
                        while (Employe::where('matricule', $matricule)->exists()) {
                            $nextId++;
                            $matricule = 'EMP' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Déterminer le statut
                    $statut = isset($rowData['statut']) && in_array(strtolower(trim($rowData['statut'])), ['actif', 'inactif']) 
                        ? strtolower(trim($rowData['statut'])) 
                        : 'actif';
                    
                    // Créer l'employé
                    $employe = Employe::create([
                        'matricule' => $matricule,
                        'nom' => trim($rowData['nom']),
                        'prenom' => trim($rowData['prenom']),
                        'email' => trim($rowData['email']),
                        'telephone' => $rowData['telephone'] ?? null,
                        'date_naissance' => $dateNaissance,
                        'date_embauche' => $dateEmbauche,
                        'poste_id' => $poste->id,
                        'statut' => $statut,
                    ]);
                    
                    // Créer un compte utilisateur si demandé
                    if (isset($rowData['compte_utilisateur']) && 
                        strtolower(trim($rowData['compte_utilisateur'])) === 'oui') {
                        $password = 'password'; // Mot de passe par défaut
                        $user = User::create([
                            'name' => $employe->prenom . ' ' . $employe->nom,
                            'email' => $employe->email,
                            'password' => Hash::make($password),
                            'role_id' => 2, // Rôle Employé
                        ]);
                        
                        $employe->update(['utilisateur_id' => $user->id]);
                        
                        // Ajouter un message indiquant que le mot de passe par défaut est 'password'
                        Log::info("Compte utilisateur créé pour {$employe->prenom} {$employe->nom} avec mot de passe par défaut: 'password'");
                    }
                    
                    $importCount++;
                    Log::info("Employé importé ligne $rowIndex: {$employe->prenom} {$employe->nom}");
                    
                } catch (\Exception $e) {
                    $errors[] = "Ligne $rowIndex: " . $e->getMessage();
                    Log::error("Erreur import ligne $rowIndex: " . $e->getMessage());
                }
            }
            
            // Résumé de l'importation
            Log::info("Importation directe terminée: $importCount employés importés, " . count($errors) . " erreurs");
            
            if ($importCount > 0) {
                if (count($errors) > 0) {
                    return redirect()->route('employes.index')
                        ->with('warning', "$importCount employé(s) importé(s) avec " . count($errors) . " erreur(s)")
                        ->with('import_errors', $errors);
                } else {
                    return redirect()->route('employes.index')
                        ->with('success', "$importCount employé(s) importé(s) avec succès");
                }
            } else {
                // Analyser les erreurs pour donner un message plus précis
                $errorSummary = $this->categorizeImportErrors($errors);
                $errorMessage = 'Aucun employé n\'a pu être importé. ';
                
                if (!empty($errorSummary)) {
                    $errorMessage .= 'Raison(s) : ' . $errorSummary;
                }
                
                return redirect()->back()
                    ->withErrors(['file' => $errorMessage])
                    ->with('import_errors', $errors);
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur générale d'importation directe: " . $e->getMessage());
            
            // Analyser le message d'erreur pour fournir des informations plus claires
            $errorMessage = 'Erreur lors de l\'importation. ';
            
            if (strpos($e->getMessage(), 'Duplicata') !== false && strpos($e->getMessage(), 'matricule') !== false) {
                $errorMessage .= 'Un ou plusieurs matricules existent déjà dans la base de données.';
            } 
            else if (strpos($e->getMessage(), 'Duplicata') !== false && strpos($e->getMessage(), 'email') !== false) {
                $errorMessage .= 'Une ou plusieurs adresses email existent déjà dans la base de données.';
            }
            else if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMessage .= 'Violation de contrainte d\'intégrité. Vérifiez les valeurs uniques comme le matricule ou l\'email.';
            }
            else {
                $errorMessage .= $e->getMessage();
            }
            
            return redirect()->back()
                ->withErrors(['file' => $errorMessage]);
        }
    }
    
    /**
     * Analyser les erreurs d'importation pour fournir un résumé clair
     * 
     * @param array $errors Liste des erreurs
     * @return string Résumé des causes principales d'erreur
     */
    private function categorizeImportErrors($errors)
    {
        $errorTypes = [
            'doublon_email' => 0,
            'doublon_matricule' => 0,
            'champ_obligatoire' => 0,
            'date_invalide' => 0,
            'autre' => 0
        ];
        
        foreach ($errors as $error) {
            $errorLower = strtolower($error);
            
            if (strpos($errorLower, 'email') !== false && (strpos($errorLower, 'exist') !== false || strpos($errorLower, 'doublon') !== false)) {
                $errorTypes['doublon_email']++;
            } 
            else if (strpos($errorLower, 'matricule') !== false && strpos($errorLower, 'dupl') !== false) {
                $errorTypes['doublon_matricule']++;
            }
            else if (strpos($errorLower, 'obligatoire') !== false || strpos($errorLower, 'manquant') !== false) {
                $errorTypes['champ_obligatoire']++;
            }
            else if (strpos($errorLower, 'date') !== false && (strpos($errorLower, 'inval') !== false || strpos($errorLower, 'format') !== false)) {
                $errorTypes['date_invalide']++;
            }
            else {
                $errorTypes['autre']++;
            }
        }
        
        // Construire le message de résumé
        $summaryParts = [];
        
        if ($errorTypes['doublon_email'] > 0) {
            $summaryParts[] = "adresses email en doublon (" . $errorTypes['doublon_email'] . ")";
        }
        
        if ($errorTypes['doublon_matricule'] > 0) {
            $summaryParts[] = "matricules déjà existants (" . $errorTypes['doublon_matricule'] . ")";
        }
        
        if ($errorTypes['champ_obligatoire'] > 0) {
            $summaryParts[] = "champs obligatoires manquants (" . $errorTypes['champ_obligatoire'] . ")";
        }
        
        if ($errorTypes['date_invalide'] > 0) {
            $summaryParts[] = "dates invalides (" . $errorTypes['date_invalide'] . ")";
        }
        
        if ($errorTypes['autre'] > 0) {
            $summaryParts[] = "autres erreurs (" . $errorTypes['autre'] . ")";
        }
        
        return implode(', ', $summaryParts);
    }

    /**
     * Vérifier si un email existe déjà et suggérer une alternative si nécessaire
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $prenom = $request->input('prenom');
        $nom = $request->input('nom');
        
        // Vérifier si l'email existe déjà
        $exists = Employe::where('email', $email)->exists() || User::where('email', $email)->exists();
        
        if (!$exists) {
            return response()->json([
                'exists' => false,
                'message' => 'Cet email est disponible.',
                'email' => $email
            ]);
        }
        
        // Générer un email alternatif basé sur le prénom et le nom
        $baseEmail = strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII', $prenom)) . '.' . 
                    strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII', $nom));
        $randomNumber = rand(100, 999);
        $alternativeEmail = $baseEmail . $randomNumber . '@gmail.com';
        
        // Vérifier que l'email alternatif n'existe pas déjà
        while (Employe::where('email', $alternativeEmail)->exists() || User::where('email', $alternativeEmail)->exists()) {
            $randomNumber = rand(100, 999);
            $alternativeEmail = $baseEmail . $randomNumber . '@gmail.com';
        }
        
        return response()->json([
            'exists' => true,
            'message' => 'Cet email existe déjà dans le système. Voulez-vous utiliser l\'email suggéré ci-dessous ?',
            'email' => $email,
            'alternativeEmail' => $alternativeEmail
        ]);
    }
    
    /**
     * Générer la partie base de l'email à partir du prénom et du nom
     */
    private function generateBaseEmail($prenom, $nom)
    {
        // Nettoyer le prénom et le nom
        $prenom = $this->cleanName($prenom);
        $nom = $this->cleanName($nom);
        
        // Créer la base de l'email: prenom.nom
        return strtolower($prenom . '.' . $nom);
    }
    
    /**
     * Nettoyer un nom pour l'utiliser dans un email
     */
    private function cleanName($name)
    {
        if (empty($name)) {
            return '';
        }
        
        // Remplacer les espaces et caractères spéciaux
        $name = preg_replace('/\s+/', '', $name); // Supprimer les espaces
        $name = str_replace(['-', "'", '"'], '', $name); // Supprimer les tirets et apostrophes
        
        // Supprimer les accents
        $name = str_replace(
            ['à', 'â', 'ä', 'á', 'ã', 'å', 'æ', 'ç', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ñ', 'ó', 'ò', 'ô', 'ö', 'õ', 'ø', 'œ', 'ß', 'ú', 'ù', 'û', 'ü', 'ý', 'ÿ'],
            ['a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'oe', 'ss', 'u', 'u', 'u', 'u', 'y', 'y'],
            $name
        );
        
        return $name;
    }
}