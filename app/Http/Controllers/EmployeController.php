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
        if (auth()->user()->isAdmin()) {
            // Admin peut voir tous les employés
        } else {
            // Utilisateur standard ne voit que son propre profil
            $query->where('utilisateur_id', auth()->id());
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
        
        // Tri
        $sortField = $request->input('sort', 'nom');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $employes = $query->paginate(10);
        $postes = Poste::all();
        
        return view('employes.index', compact('employes', 'postes'));
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
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:employes,email',
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'date_embauche' => 'required|date',
            'poste_id' => 'required|exists:postes,id',
            'create_user' => 'boolean',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        // Génération du matricule (préfixe EMP + 5 chiffres)
        $lastEmploye = Employe::latest()->first();
        $lastId = $lastEmploye ? $lastEmploye->id + 1 : 1;
        $matricule = 'EMP' . str_pad($lastId, 5, '0', STR_PAD_LEFT);
        
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
            
            // Assurez-vous que le répertoire existe
            if (!file_exists(public_path('storage/photos'))) {
                mkdir(public_path('storage/photos'), 0755, true);
            }
            
            $photoFile->move(public_path('storage/photos'), $photoName);
            $employeData['photo_profil'] = $photoName;
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
            if ($employe->photo_profil && file_exists(public_path('storage/photos/' . $employe->photo_profil))) {
                unlink(public_path('storage/photos/' . $employe->photo_profil));
            }
            
            // Enregistrer la nouvelle photo
            $photoFile = $request->file('photo_profil');
            $photoName = time() . '_' . $employe->matricule . '.' . $photoFile->getClientOriginalExtension();
            
            // Assurez-vous que le répertoire existe
            if (!file_exists(public_path('storage/photos'))) {
                mkdir(public_path('storage/photos'), 0755, true);
            }
            
            $photoFile->move(public_path('storage/photos'), $photoName);
            $dataToUpdate['photo_profil'] = $photoName;
        }
        // Supprimer la photo si demandé
        else if ($request->has('supprimer_photo')) {
            if ($employe->photo_profil && file_exists(public_path('storage/photos/' . $employe->photo_profil))) {
                unlink(public_path('storage/photos/' . $employe->photo_profil));
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
        $employe->delete();
        
        return redirect()->route('employes.index')
            ->with('success', 'Employé supprimé avec succès.');
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
            $pdf = \PDF::loadView('exports.employes_pdf', compact('employes'));
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
                    $matricule = !empty($rowData['matricule']) 
                        ? trim($rowData['matricule']) 
                        : 'EMP' . str_pad(Employe::max('id') + 1, 5, '0', STR_PAD_LEFT);
                    
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
}