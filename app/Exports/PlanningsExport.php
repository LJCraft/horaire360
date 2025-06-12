<?php

namespace App\Exports;

use App\Models\Planning;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanningsExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles
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
            // Retourner une collection vide pour le modèle
            return collect([]);
        }
        
        return Planning::with(['employe', 'details'])->get();
    }
    
    /**
     * @param Planning $planning
     */
    public function map($planning): array
    {
        // Déterminer les valeurs pour chaque jour
        $jours = [];
        $notes = [];
        
        for ($j = 1; $j <= 7; $j++) {
            $jours[$j] = '';
            $notes[$j] = '';
        }
        
        // Parcourir les détails du planning
        foreach ($planning->details as $detail) {
            if ($detail->jour_repos) {
                $jours[$detail->jour] = 'Repos';
            } elseif ($detail->jour_entier) {
                $jours[$detail->jour] = 'Journée';
            } elseif ($detail->heure_debut && $detail->heure_fin) {
                $jours[$detail->jour] = substr($detail->heure_debut, 0, 5) . '-' . substr($detail->heure_fin, 0, 5);
            }
            
            $notes[$detail->jour] = $detail->note ?? '';
        }
        
        return [
            $planning->employe->matricule,
            $planning->employe->nom,
            $planning->employe->prenom,
            $planning->date_debut->format('Y-m-d'),
            $planning->date_fin->format('Y-m-d'),
            $planning->titre,
            $planning->description,
            $jours[1], // Lundi
            $notes[1],
            $jours[2], // Mardi
            $notes[2],
            $jours[3], // Mercredi
            $notes[3],
            $jours[4], // Jeudi
            $notes[4],
            $jours[5], // Vendredi
            $notes[5],
            $jours[6], // Samedi
            $notes[6],
            $jours[7], // Dimanche
            $notes[7],
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
            'Date début',
            'Date fin',
            'Titre',
            'Description',
            'Jour_1 (Lundi)',
            'Note_1',
            'Jour_2 (Mardi)',
            'Note_2',
            'Jour_3 (Mercredi)',
            'Note_3',
            'Jour_4 (Jeudi)',
            'Note_4',
            'Jour_5 (Vendredi)',
            'Note_5',
            'Jour_6 (Samedi)',
            'Note_6',
            'Jour_7 (Dimanche)',
            'Note_7',
        ];
    }
    
    /**
     * Styles du fichier Excel
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'U'; // Colonne de la dernière note
        
        // Rendre la première ligne en gras et définir la couleur de fond
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4361EE'],
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
            ],
        ]);
        
        // Ajouter des couleurs de fond alternées pour les jours
        $sheet->getStyle('H1:H' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F0FF'],
            ],
        ]);
        
        $sheet->getStyle('J1:J' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F0FF'],
            ],
        ]);
        
        $sheet->getStyle('L1:L' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F0FF'],
            ],
        ]);
        
        $sheet->getStyle('N1:N' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F0FF'],
            ],
        ]);
        
        $sheet->getStyle('P1:P' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F0FF'],
            ],
        ]);
        
        $sheet->getStyle('R1:R' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFEAD6'],
            ],
        ]);
        
        $sheet->getStyle('T1:T' . ($sheet->getHighestRow()))->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFEAD6'],
            ],
        ]);
        
        // Ajuster la largeur des colonnes
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Ajouter des informations en bas de la feuille pour expliquer le format
        $lastRow = $sheet->getHighestRow() + 2;
        
        $sheet->setCellValue('A' . $lastRow, 'Instructions:');
        $sheet->getStyle('A' . $lastRow)->applyFromArray(['font' => ['bold' => true]]);
        
        $sheet->setCellValue('A' . ($lastRow + 1), '- Pour les jours de repos, indiquez "Repos" ou "R"');
        $sheet->setCellValue('A' . ($lastRow + 2), '- Pour les journées complètes, indiquez "Journée" ou "J"');
        $sheet->setCellValue('A' . ($lastRow + 3), '- Pour les horaires spécifiques, utilisez le format "09:00-17:00" ou "9-17"');
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}