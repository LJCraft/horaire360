<?php

namespace App\Exports;

use App\Models\Employe;
use App\Models\Departement;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class PointageTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithMapping, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Récupérer tous les employés actifs avec leurs départements
        // Classés par département puis par ordre alphabétique (nom, prénom)
        return Employe::with(['poste'])
            ->where('actif', true)
            ->get()
            ->sortBy([
                ['poste.departement', 'asc'],
                ['nom', 'asc'],
                ['prenom', 'asc']
            ])
            ->values();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $today = Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY');
        
        return [
            ['FEUILLE DE POINTAGE - ' . strtoupper($today)], // Ligne 1 : Titre avec date
            [], // Ligne 2 : Vide pour l'espacement
            ['Département', 'Matricule', 'Nom', 'Prénom', 'Poste', 'Arrivée Réelle (AR)', 'Heure de Départ (HD)'] // Ligne 3 : En-têtes des colonnes
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Obtenir la collection d'employés pour calculer le nombre de lignes
        $employes = $this->collection();
        $totalRows = $employes->count() + 3; // +3 pour les en-têtes

        // Style du titre principal (ligne 1)
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1e3a8a'] // Bleu foncé
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Style des en-têtes de colonnes (ligne 3)
        $sheet->getStyle('A3:G3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3b82f6'] // Bleu
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style des données (à partir de la ligne 4)
        if ($totalRows > 3) {
            $sheet->getStyle('A4:G' . $totalRows)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'cccccc']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            // Alternance des couleurs pour les lignes de données
            for ($row = 4; $row <= $totalRows; $row++) {
                if (($row - 4) % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'f8fafc'] // Gris très clair
                        ]
                    ]);
                }
            }
        }

        // Colonnes AR et HD avec une couleur de fond différente pour les mettre en évidence
        if ($totalRows > 3) {
            $sheet->getStyle('F4:G' . $totalRows)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'fef3c7'] // Jaune clair
                ]
            ]);
        }

        // Hauteur des lignes
        $sheet->getRowDimension(1)->setRowHeight(30); // Titre
        $sheet->getRowDimension(3)->setRowHeight(25); // En-têtes

        // Ajout de commentaires pour les colonnes AR et HD
        $sheet->getComment('F3')->getText()->createTextRun('Format: HH:MM (ex: 08:30)');
        $sheet->getComment('G3')->getText()->createTextRun('Format: HH:MM (ex: 17:30)');

        return [];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18, // Département
            'B' => 12, // Matricule
            'C' => 20, // Nom
            'D' => 20, // Prénom
            'E' => 25, // Poste
            'F' => 18, // Arrivée Réelle (AR)
            'G' => 18, // Heure de Départ (HD)
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Pointage ' . Carbon::now()->format('d-m-Y');
    }

    /**
     * Map the data for each row
     */
    public function map($employe): array
    {
        return [
            $employe->poste->departement ?? 'Non défini',
            $employe->matricule,
            $employe->nom,
            $employe->prenom,
            $employe->poste->nom ?? 'Non défini',
            '', // Arrivée Réelle (AR) - à remplir
            '', // Heure de Départ (HD) - à remplir
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $this->mergeDepartmentCells($event->sheet);
            },
        ];
    }

    /**
     * Fusionner les cellules des départements
     */
    private function mergeDepartmentCells($sheet)
    {
        $employes = $this->collection();
        $currentRow = 4; // Début des données (après les en-têtes)
        $currentDepartment = null;
        $departmentStartRow = $currentRow;

        foreach ($employes as $employe) {
            $department = $employe->poste->departement ?? 'Non défini';
            
            if ($currentDepartment === null) {
                $currentDepartment = $department;
                $departmentStartRow = $currentRow;
            } elseif ($currentDepartment !== $department) {
                // Fusionner les cellules du département précédent
                if ($currentRow - 1 > $departmentStartRow) {
                    $sheet->mergeCells("A{$departmentStartRow}:A" . ($currentRow - 1));
                    
                    // Centrer verticalement le texte du département
                    $sheet->getStyle("A{$departmentStartRow}:A" . ($currentRow - 1))->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 11
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'e3f2fd'] // Bleu très clair
                        ]
                    ]);
                }
                
                $currentDepartment = $department;
                $departmentStartRow = $currentRow;
            }
            
            $currentRow++;
        }
        
        // Fusionner les cellules du dernier département
        if ($currentRow - 1 > $departmentStartRow) {
            $sheet->mergeCells("A{$departmentStartRow}:A" . ($currentRow - 1));
            
            $sheet->getStyle("A{$departmentStartRow}:A" . ($currentRow - 1))->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ],
                'font' => [
                    'bold' => true,
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'e3f2fd'] // Bleu très clair
                ]
            ]);
        }

        // Ajouter des bordures plus épaisses entre les départements
        $this->addDepartmentBorders($sheet, $employes);
    }

    /**
     * Ajouter des bordures entre les départements
     */
    private function addDepartmentBorders($sheet, $employes)
    {
        $currentRow = 4;
        $currentDepartment = null;

        foreach ($employes as $employe) {
            $department = $employe->poste->departement ?? 'Non défini';
            
            if ($currentDepartment !== null && $currentDepartment !== $department) {
                // Ajouter une bordure épaisse au-dessus de la nouvelle section
                $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray([
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_THICK,
                            'color' => ['rgb' => '1976d2'] // Bleu foncé
                        ]
                    ]
                ]);
            }
            
            $currentDepartment = $department;
            $currentRow++;
        }
    }
} 