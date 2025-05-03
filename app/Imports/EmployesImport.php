<?php

namespace App\Imports;

use App\Models\Employe;
use App\Models\Poste;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell as CellNode;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Closure;

class EmployesImport implements 
    ToCollection, 
    WithHeadingRow, 
    WithValidation, 
    SkipsOnError, 
    SkipsOnFailure,
    WithBatchInserts,
    WithEvents
{
    use Importable;
    
    // Stocker les erreurs et échecs
    protected $errors = [];
    protected $failures = [];
    
    /**
     * Enregistrer les événements de feuille de calcul pour débogage
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $sheet = $event->getSheet();
                $worksheet = $sheet->getDelegate();
                
                // Log le nom de la feuille
                Log::info("Traitement de la feuille: " . $worksheet->getTitle());
                
                // Log les dimensions utilisées
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                Log::info("Dimensions de la feuille: A1:{$highestColumn}{$highestRow}");
                
                // Examiner les en-têtes (première ligne)
                $headers = [];
                $headerRow = 1; // Ligne d'en-tête, ajustez si nécessaire
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $cellValue = $worksheet->getCell($col . $headerRow)->getValue();
                    $headers[$col] = $cellValue;
                }
                Log::info("En-têtes trouvés: " . json_encode($headers));
                
                // Examiner quelques exemples de cellules
                if ($highestRow > 1) {
                    $sampleRow = 2; // Première ligne de données
                    $rowData = [];
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cell = $worksheet->getCell($col . $sampleRow);
                        $value = $cell->getValue();
                        $formattedValue = $cell->getFormattedValue();
                        $calculatedValue = $cell->getCalculatedValue();
                        $dataType = $cell->getDataType();
                        
                        $rowData[$col] = [
                            'header' => $headers[$col] ?? 'N/A',
                            'value' => $value,
                            'formatted' => $formattedValue,
                            'calculated' => $calculatedValue,
                            'type' => $dataType
                        ];
                    }
                    Log::info("Exemple ligne {$sampleRow}: " . json_encode($rowData));
                }
            }
        ];
    }
    
    /**
     * Déterminer le type d'en-tête et ajuster le paramètre WithHeadingRow
     */
    public function headingRow(): int
    {
        // Pour un fichier standard sans titre/sous-titre, utiliser la ligne 1
        // Car l'image montre clairement que les en-têtes sont à la première ligne
        return 1;
    }
    
    /**
     * Vérifie que tous les champs obligatoires sont présents
     * 
     * @param array $row
     * @return array|bool Retourne true si tous les champs sont présents, sinon un tableau d'erreurs
     */
    private function checkRequiredFields($row)
    {
        $requiredFields = ['nom', 'prenom', 'email', 'date_embauche', 'poste'];
        $missingFields = [];
        
        // Rechercher des variantes possibles pour les noms de colonnes
        $nameVariants = [
            'nom' => ['nom', 'name', 'lastname', 'last_name', 'famille', 'nom *', 'nom*'],
            'prenom' => ['prenom', 'prénom', 'firstname', 'first_name', 'given_name', 'prénom *', 'prenom *', 'prénom*', 'prenom*'],
            'email' => ['email', 'courriel', 'mail', 'e-mail', 'email *', 'email*'],
            'date_embauche' => ['date_embauche', 'dateembauche', 'date embauche', 'hiring_date', 'embauche', 'date_debut', 'date début', "date d'embauche", "date d'embauche *", "date d'embauche*", "datedembauche", "date dembauche"],
            'poste' => ['poste', 'position', 'job_title', 'titre', 'fonction', 'job', 'poste *', 'poste*']
        ];
        
        // Déboguer l'entrée brute
        Log::info("Vérification des champs obligatoires pour la ligne: " . json_encode($row));
        
        // Normaliser les clés du tableau (enlever accents, minuscules, etc.)
        $normalizedRow = [];
        
        // D'abord essayer de repérer les clés avec des astérisques et les traiter
        foreach ($row as $key => $value) {
            // La clé contient-elle un astérisque?
            if (strpos($key, '*') !== false) {
                $cleanKey = str_replace('*', '', strtolower(trim($key)));
                Log::info("Clé avec astérisque trouvée: '$key' -> '$cleanKey'");
                $normalizedRow[$cleanKey] = $value;
            }
            
            // Normaliser la clé
            $normalizedKey = $this->removeAccents(strtolower(trim($key)));
            $normalizedKey = preg_replace('/[*\s]+/', '', $normalizedKey);
            $normalizedRow[$normalizedKey] = $value;
            
            // Aussi conserver la version originale pour la recherche
            $normalizedRow[strtolower(trim($key))] = $value;
        }
        
        // Remplir les clés canoniques si des variantes sont trouvées
        foreach ($requiredFields as $field) {
            $found = false;
            
            // Vérifier le champ exact (après normalisation)
            if (isset($normalizedRow[$field]) && !empty($normalizedRow[$field])) {
                $found = true;
                // Utiliser cette valeur pour le champ canonique
                $row[$field] = $normalizedRow[$field];
                Log::info("Champ '$field' trouvé directement: " . $normalizedRow[$field]);
                continue;
            }
            
            // Vérifier les variantes possibles
            if (isset($nameVariants[$field])) {
                foreach ($nameVariants[$field] as $variant) {
                    // Normaliser la variante aussi
                    $normalizedVariant = $this->removeAccents(strtolower(trim($variant)));
                    $normalizedVariant = preg_replace('/[*\s]+/', '', $normalizedVariant);
                    
                    if (isset($normalizedRow[$normalizedVariant]) && !empty($normalizedRow[$normalizedVariant])) {
                        // Remap la valeur dans la clé standard
                        $row[$field] = $normalizedRow[$normalizedVariant];
                        $found = true;
                        Log::info("Correspondance trouvée pour '$field' via la variante '$variant': " . $normalizedRow[$normalizedVariant]);
                        break;
                    }
                    
                    // Essayer aussi avec la version originale
                    if (isset($normalizedRow[strtolower(trim($variant))]) && !empty($normalizedRow[strtolower(trim($variant))])) {
                        $row[$field] = $normalizedRow[strtolower(trim($variant))];
                        $found = true;
                        Log::info("Correspondance trouvée pour '$field' via la variante non normalisée '$variant': " . $normalizedRow[strtolower(trim($variant))]);
                        break;
                    }
                }
            }
            
            // Recherche plus approfondie pour ce champ - parcourir toutes les clés
            if (!$found) {
                foreach (array_keys($normalizedRow) as $key) {
                    // Rechercher si la clé contient le nom du champ ou une variante
                    if (strpos($key, $field) !== false) {
                        $row[$field] = $normalizedRow[$key];
                        $found = true;
                        Log::info("Correspondance partielle trouvée pour '$field' via la clé '$key': " . $normalizedRow[$key]);
                        break;
                    }
                    
                    // Vérifier aussi les variantes
                    if (isset($nameVariants[$field])) {
                        foreach ($nameVariants[$field] as $variant) {
                            $normalizedVariant = $this->removeAccents(strtolower(trim($variant)));
                            $normalizedVariant = preg_replace('/[*\s]+/', '', $normalizedVariant);
                            
                            if (strpos($key, $normalizedVariant) !== false) {
                                $row[$field] = $normalizedRow[$key];
                                $found = true;
                                Log::info("Correspondance partielle trouvée pour '$field' via la variante '$variant' dans la clé '$key': " . $normalizedRow[$key]);
                                break 2;
                            }
                        }
                    }
                }
            }
            
            if (!$found) {
                $missingFields[] = $field;
                Log::warning("Champ obligatoire '$field' non trouvé");
            }
        }
        
        if (empty($missingFields)) {
            return true;
        }
        
        return $missingFields;
    }
    
    /**
     * Supprime les accents d'une chaîne
     * 
     * @param string $string
     * @return string
     */
    private function removeAccents($string) {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        
        $chars = [
            // Décompositions pour Latin-1 Supplement
            'ª' => 'a', 'º' => 'o',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
            'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'TH', 'ß' => 's',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y',
        ];
        
        return strtr($string, $chars);
    }
    
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Débogage: Enregistrer le nombre de lignes reçues et la structure
        Log::info("Importation d'employés - Nombre de lignes: " . $rows->count());
        
        // Problème: fichier vide ou mal formaté
        if ($rows->isEmpty()) {
            $this->errors[] = "Le fichier importé est vide ou ne contient aucune donnée valide.";
            session(['import_errors' => $this->errors]);
            return;
        }
        
        // Analyser chaque ligne pour comprendre la structure
        $firstRow = $rows->first();
        Log::info("Structure de la première ligne: " . json_encode([
            'keys' => array_keys($firstRow->toArray()),
            'values' => array_values($firstRow->toArray())
        ]));
        
        // Vérifier si les en-têtes correspondent au format de l'image
        // Avec des clés comme "nom *", "prénom *", etc.
        $columnsMap = [];
        if ($firstRow) {
            $keys = array_keys($firstRow->toArray());
            
            // Mappings spécifiques basés sur l'image
            $headerMappings = [
                'matricule' => ['matricule'],
                'nom' => ['nom *', 'nom*'],
                'prenom' => ['prénom *', 'prenom *', 'prénom*', 'prenom*'],
                'email' => ['email *', 'email*'],
                'telephone' => ['téléphone', 'telephone'],
                'date_naissance' => ['date de naissance'],
                'date_embauche' => ["date d'embauche *", "date d'embauche*"],
                'poste' => ['poste *', 'poste*'],
                'departement' => ['département', 'departement'],
                'statut' => ['statut'],
                'compte_utilisateur' => ['compte utilisateur']
            ];
            
            // Construire la correspondance entre les en-têtes du fichier et les champs attendus
            foreach ($keys as $key) {
                $matched = false;
                foreach ($headerMappings as $field => $variants) {
                    foreach ($variants as $variant) {
                        if (strtolower(trim($key)) === strtolower(trim($variant))) {
                            $columnsMap[$key] = $field;
                            $matched = true;
                            Log::info("Correspondance d'en-tête trouvée: '$key' -> '$field'");
                            break 2;
                        }
                    }
                }
                
                if (!$matched) {
                    Log::warning("Aucune correspondance trouvée pour l'en-tête: '$key'");
                }
            }
            
            Log::info("Carte des colonnes: " . json_encode($columnsMap));
        }
        
        // Suite normal du traitement
        $importCount = 0;
        
        foreach ($rows as $rowIndex => $row) {
            try {
                // Convertir les en-têtes si nécessaire
                if (!empty($columnsMap)) {
                    $newRow = [];
                    foreach ($row as $key => $value) {
                        if (isset($columnsMap[$key])) {
                            $newRow[$columnsMap[$key]] = $value;
                        } else {
                            $newRow[$key] = $value;
                        }
                    }
                    $row = $newRow;
                }
                
                // Débogage: Enregistrer les données de la ligne en cours
                Log::info("Traitement ligne #" . ($rowIndex + 1) . " après mapping: " . json_encode($row));
                
                // Vérifier les champs obligatoires
                $requiredFieldsCheck = $this->checkRequiredFields($row);
                if ($requiredFieldsCheck !== true) {
                    $missingFields = implode(', ', $requiredFieldsCheck);
                    $errorMessage = "Ligne " . ($rowIndex + 1) . " ignorée: champs obligatoires manquants ($missingFields)";
                    $this->errors[] = $errorMessage;
                    Log::warning($errorMessage);
                    continue;
                }
                
                // Trouver le poste par nom
                $poste = Poste::where('nom', trim($row['poste']))->first();
                
                if (!$poste) {
                    // Créer le poste s'il n'existe pas
                    Log::info("Création d'un nouveau poste: " . $row['poste']);
                    $poste = Poste::create([
                        'nom' => trim($row['poste']),
                        'departement' => isset($row['departement']) ? trim($row['departement']) : null,
                    ]);
                }
                
                // Vérifier si l'email existe déjà
                $existingEmploye = Employe::where('email', trim($row['email']))->first();
                if ($existingEmploye) {
                    $errorMessage = "L'email {$row['email']} existe déjà pour un autre employé";
                    $this->errors[] = $errorMessage;
                    Log::warning($errorMessage);
                    continue;
                }
                
                // Génération du matricule si non fourni
                $matricule = isset($row['matricule']) && !empty($row['matricule']) 
                    ? trim($row['matricule']) 
                    : 'EMP' . str_pad(Employe::max('id') + 1, 5, '0', STR_PAD_LEFT);
                
                // Préparation des dates
                $dateNaissance = null;
                if (isset($row['date_naissance']) && !empty($row['date_naissance'])) {
                    try {
                        // Vérifier si c'est un nombre (format Excel) ou une chaîne de date
                        if (is_numeric($row['date_naissance'])) {
                            $dateNaissance = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_naissance']);
                        } else {
                            // Essayer différents formats
                            $dateFormats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'];
                            foreach ($dateFormats as $format) {
                                try {
                                    $dateNaissance = \Carbon\Carbon::createFromFormat($format, $row['date_naissance']);
                                    if ($dateNaissance) break;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            if (!$dateNaissance) {
                                // Dernier essai avec parse général
                                $dateNaissance = \Carbon\Carbon::parse($row['date_naissance']);
                            }
                        }
                        
                        Log::info("Date naissance convertie: " . $dateNaissance->format('Y-m-d'));
                    } catch (\Exception $e) {
                        Log::error("Erreur format date naissance: " . $e->getMessage());
                        $dateNaissance = null;
                    }
                }
                
                $dateEmbauche = null;
                $dateEmbaucheValue = $row['date_embauche'] ?? null;
                
                Log::info("Valeur date embauche brute: " . json_encode($dateEmbaucheValue) . " (type: " . gettype($dateEmbaucheValue) . ")");
                
                if (!empty($dateEmbaucheValue)) {
                    try {
                        // Vérifier si c'est un nombre (format Excel)
                        if (is_numeric($dateEmbaucheValue) || (is_string($dateEmbaucheValue) && is_numeric(trim($dateEmbaucheValue)))) {
                            try {
                                $numericValue = is_numeric($dateEmbaucheValue) ? $dateEmbaucheValue : floatval(trim($dateEmbaucheValue));
                                $dateEmbauche = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($numericValue);
                                Log::info("Date embauche convertie depuis Excel numérique: " . $dateEmbauche->format('Y-m-d'));
                            } catch (\Exception $e) {
                                Log::error("Échec de conversion de date Excel: " . $e->getMessage());
                            }
                        }
                        
                        // Si la conversion numérique a échoué ou si ce n'est pas un nombre
                        if (!$dateEmbauche) {
                            // Essayer différents formats de date
                            $dateFormats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'];
                            foreach ($dateFormats as $format) {
                                try {
                                    if (is_string($dateEmbaucheValue)) {
                                        $dateEmbauche = \Carbon\Carbon::createFromFormat($format, trim($dateEmbaucheValue));
                                        if ($dateEmbauche) {
                                            Log::info("Date embauche parsée avec format $format: " . $dateEmbauche->format('Y-m-d'));
                                            break;
                                        }
                                    }
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            if (!$dateEmbauche && is_string($dateEmbaucheValue)) {
                                // Dernier essai avec parse général
                                try {
                                    $dateEmbauche = \Carbon\Carbon::parse(trim($dateEmbaucheValue));
                                    Log::info("Date embauche parsée avec parse général: " . $dateEmbauche->format('Y-m-d'));
                                } catch (\Exception $e) {
                                    Log::error("Échec de parse général: " . $e->getMessage());
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $errorMessage = "Erreur format date embauche ligne " . ($rowIndex + 1) . ": " . $e->getMessage();
                        $this->errors[] = $errorMessage;
                        Log::error($errorMessage);
                        continue;
                    }
                }
                
                if (!$dateEmbauche) {
                    $errorMessage = "Date d'embauche manquante ou invalide à la ligne " . ($rowIndex + 1);
                    $this->errors[] = $errorMessage;
                    Log::error($errorMessage);
                    continue;
                }
                
                // Nettoyer et préparer les données
                $nom = trim($row['nom']);
                $prenom = trim($row['prenom']);
                $email = trim($row['email']);
                $telephone = isset($row['telephone']) ? trim($row['telephone']) : null;
                $statut = isset($row['statut']) && in_array(strtolower(trim($row['statut'])), ['actif', 'inactif']) 
                    ? strtolower(trim($row['statut'])) 
                    : 'actif';
                
                // Log des données d'employé avant création
                Log::info("Tentative création employé: Nom=$nom, Prénom=$prenom, Email=$email, Poste={$poste->nom}, Date embauche=" . $dateEmbauche->format('Y-m-d'));
                
                // Création de l'employé
                $employe = Employe::create([
                    'matricule' => $matricule,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'date_naissance' => $dateNaissance,
                    'date_embauche' => $dateEmbauche,
                    'poste_id' => $poste->id,
                    'statut' => $statut,
                ]);
                
                Log::info("Employé créé avec succès: ID={$employe->id}, Email={$employe->email}");
                
                // Création d'un compte utilisateur si demandé
                if (isset($row['compte_utilisateur']) && strtolower(trim($row['compte_utilisateur'])) === 'oui') {
                    $user = User::create([
                        'name' => $prenom . ' ' . $nom,
                        'email' => $email,
                        'password' => Hash::make('password'), // Mot de passe par défaut
                        'role_id' => 2, // Rôle Employé
                    ]);
                    
                    $employe->update(['utilisateur_id' => $user->id]);
                    Log::info("Compte utilisateur créé pour l'employé: {$employe->id}");
                }
                
                $importCount++;
            } catch (\Exception $e) {
                $error = "Erreur ligne " . ($rowIndex + 1) . " lors de l'importation de " . 
                    (isset($row['prenom']) ? $row['prenom'] : '') . " " . 
                    (isset($row['nom']) ? $row['nom'] : '') . " : " . $e->getMessage();
                
                $this->errors[] = $error;
                Log::error("Erreur d'importation d'employé: " . $e->getMessage());
                Log::error("Trace: " . $e->getTraceAsString());
            }
        }
        
        // Résumé de l'importation
        Log::info("Importation terminée: $importCount employés importés avec succès, " . count($this->errors) . " erreurs");
        
        // Ajouter un message sur le nombre d'importations réussies
        if ($importCount > 0) {
            session(['import_success_count' => $importCount]);
        }
        
        // Si des erreurs ont été détectées, les enregistrer dans la session
        if (!empty($this->errors)) {
            session(['import_errors' => $this->errors]);
        }
    }
    
    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email',
            'date_embauche' => 'required',
            'poste' => 'required|string|max:255',
        ];
    }
    
    /**
     * Messages de validation personnalisés
     */
    public function customValidationMessages()
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email n\'est pas valide',
            'date_embauche.required' => 'La date d\'embauche est obligatoire',
            'poste.required' => 'Le poste est obligatoire',
        ];
    }
    
    /**
     * Gérer les erreurs de validation
     */
    public function onError(\Throwable $e)
    {
        $this->errors[] = "Erreur système : " . $e->getMessage();
        Log::error("Erreur d'importation: " . $e->getMessage());
    }
    
    /**
     * Gérer les échecs de validation
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
            $row = $failure->row();
            $attributes = $failure->attribute();
            $errors = $failure->errors();
            
            $errorMessage = "Ligne $row: " . implode(", ", $errors);
            $this->errors[] = $errorMessage;
        }
    }
    
    /**
     * Taille du lot pour les insertions
     */
    public function batchSize(): int
    {
        return 100;
    }
}