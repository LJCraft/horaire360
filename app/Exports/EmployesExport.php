<?php

namespace App\Exports;

use App\Models\Employe;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EmployesExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithTitle,
    WithColumnWidths,
    ShouldAutoSize
{
    protected $template;
    
    public function __construct($template = false)
    {
        $this->template = $template;
    }
    
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if ($this->template) {
            // Pour le modèle, générer 100 exemples est suffisant
            $employees = [];
            
            // Noms et prénoms réalistes
            $noms = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 
                    'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier'];
            
            $prenoms = ['Jean', 'Pierre', 'Michel', 'André', 'Philippe', 'René', 'Louis', 'Alain', 'Jacques', 'Bernard',
                       'Marie', 'Isabelle', 'Françoise', 'Catherine', 'Anne', 'Monique', 'Sylvie', 'Nathalie', 'Nicole', 'Sophie'];
            
            // Postes réalistes par département
            $departements = [
                'Ressources Humaines' => ['Responsable RH', 'Chargé de recrutement', 'Assistant RH', 'Gestionnaire paie', 'Responsable formation'],
                'Informatique' => ['Développeur', 'Chef de projet', 'Administrateur système', 'Technicien support', 'Responsable sécurité'],
                'Commercial' => ['Commercial', 'Responsable commercial', 'Assistant commercial', 'Chargé de clientèle', 'Directeur commercial'],
                'Comptabilité' => ['Comptable', 'Aide-comptable', 'Contrôleur de gestion', 'Responsable comptable', 'Trésorier'],
                'Production' => ['Opérateur', 'Chef d\'équipe', 'Responsable production', 'Technicien maintenance', 'Ingénieur qualité']
            ];
            
            // Convertir les clés du tableau $departements en tableau pour pouvoir utiliser array_rand
            $departementKeys = array_keys($departements);
            
            for ($i = 1; $i <= 100; $i++) {
                // Choisir un nom et prénom aléatoires
                $nomIndex = array_rand($noms);
                $prenomIndex = array_rand($prenoms);
                $nom = $noms[$nomIndex];
                $prenom = $prenoms[$prenomIndex];
                
                // Choisir un département aléatoire
                $departementIndex = array_rand($departementKeys);
                $departement = $departementKeys[$departementIndex];
                
                // Choisir un poste cohérent avec le département
                $posteIndex = array_rand($departements[$departement]);
                $poste = $departements[$departement][$posteIndex];
                
                $employees[] = [
                    'matricule' => 'EMP' . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => strtolower($prenom . '.' . $nom . $i . '@entreprise.com'),
                    'telephone' => '06' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'date_naissance' => now()->subYears(rand(25, 60))->subDays(rand(0, 365)),
                    'date_embauche' => now()->subYears(rand(0, 15))->subMonths(rand(0, 11)),
                    'poste' => [
                        'nom' => $poste,
                        'departement' => $departement
                    ],
                    'statut' => ($i % 10 == 0) ? 'inactif' : 'actif',
                    'utilisateur_id' => null
                ];
            }
            
            return collect($employees);
        }
        
        // Pour l'exportation de données réelles, utiliser un curseur pour économiser la mémoire
        return new \Illuminate\Support\LazyCollection(function () {
            $query = Employe::with('poste')->cursor();
            
            foreach ($query as $employe) {
                yield $employe;
            }
        });
    }
    
    /**
     * @param Employe $employe
     */
    public function map($employe): array
    {
        if ($this->template && !isset($employe->id)) {
            // Cas du modèle avec données d'exemple
            return [
                $employe['matricule'],
                $employe['nom'],
                $employe['prenom'],
                $employe['email'],
                $employe['telephone'],
                $employe['date_naissance']->format('Y-m-d'),
                $employe['date_embauche']->format('Y-m-d'),
                $employe['poste']['nom'],
                $employe['poste']['departement'],
                $employe['statut'],
                'Non'
            ];
        }
        
        // Cas normal avec un vrai objet employé
        return [
            $employe->matricule,
            $employe->nom,
            $employe->prenom,
            $employe->email,
            $employe->telephone,
            $employe->date_naissance ? $employe->date_naissance->format('Y-m-d') : '',
            $employe->date_embauche->format('Y-m-d'),
            $employe->poste->nom,
            $employe->poste->departement,
            $employe->statut,
            $employe->utilisateur_id ? 'Oui' : 'Non',
        ];
    }
    
    /**
     * En-têtes du fichier Excel
     */
    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Date de naissance',
            'Date d\'embauche',
            'Poste',
            'Département',
            'Statut',
            'Compte utilisateur',
        ];
    }
    
    /**
     * Titre de la feuille de calcul
     */
    public function title(): string
    {
        return $this->template ? 'Modèle' : 'Employés';
    }
    
    /**
     * Largeurs des colonnes
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Matricule
            'B' => 20,  // Nom
            'C' => 20,  // Prénom
            'D' => 30,  // Email
            'E' => 15,  // Téléphone
            'F' => 15,  // Date naissance
            'G' => 15,  // Date embauche
            'H' => 20,  // Poste
            'I' => 20,  // Département
            'J' => 10,  // Statut
            'K' => 15,  // Compte utilisateur
        ];
    }
    
    /**
     * Styles du fichier Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Style de l'en-tête
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4361EE'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Style des données
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Style pour les lignes alternées
        $evenRowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F2F2F2'],
            ],
        ];
        
        // Appliquer le style à l'en-tête
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        
        // Obtenir le nombre total de lignes
        $rowCount = $sheet->getHighestRow();
        
        // Appliquer le style aux données si le tableau n'est pas vide
        if ($rowCount > 1) {
            // Appliquer les styles aux données
            $sheet->getStyle('A2:K' . $rowCount)->applyFromArray($dataStyle);
            
            // Appliquer un style alterné aux lignes
            for ($i = 2; $i <= $rowCount; $i += 2) {
                $sheet->getStyle('A' . $i . ':K' . $i)->applyFromArray($evenRowStyle);
            }
            
            // Centrer certaines colonnes
            $sheet->getStyle('A2:A' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E2:G' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J2:K' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Formater visuellement les statuts
            for ($i = 2; $i <= $rowCount; $i++) {
                $statut = $sheet->getCell('J' . $i)->getValue();
                
                if ($statut === 'actif') {
                    $sheet->getStyle('J' . $i)->applyFromArray([
                        'font' => ['color' => ['rgb' => '2FC18C']],
                    ]);
                } else if ($statut === 'inactif') {
                    $sheet->getStyle('J' . $i)->applyFromArray([
                        'font' => ['color' => ['rgb' => 'F45C5D']],
                    ]);
                }
            }
        }
        
        // Ajouter un titre et des informations supplémentaires pour le modèle
        if ($this->template) {
            // Ajouter un titre
            $sheet->insertNewRowBefore(1, 2);
            $sheet->mergeCells('A1:K1');
            $sheet->setCellValue('A1', 'MODÈLE D\'IMPORTATION DES EMPLOYÉS');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => '4361EE'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
            
            // Ajouter des instructions
            $sheet->mergeCells('A2:K2');
            $sheet->setCellValue('A2', 'Remplissez ce modèle avec vos données et importez-le. Les colonnes avec * sont obligatoires.');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '555555'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
            
            // Mettre à jour le style de l'en-tête
            $sheet->getStyle('A3:K3')->applyFromArray($headerStyle);
            
            // Ajouter des astérisques aux colonnes obligatoires
            $requiredColumns = ['B', 'C', 'D', 'G', 'H'];
            foreach ($requiredColumns as $col) {
                $cellValue = $sheet->getCell($col . '3')->getValue();
                $sheet->setCellValue($col . '3', $cellValue . ' *');
            }
        }

        return [];
    }
}