<?php

namespace App\Exports;

use App\Models\Employe;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyledHeader;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PresencesTemplateExport implements WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'employe_id*',
            'date*',
            'heure_arrivee*',
            'heure_depart',
            'commentaire',
        ];
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Ajouter la liste des employés sur une feuille séparée
        $employes = Employe::all(['id', 'matricule', 'nom', 'prenom']);
        $sheet->createSheet();
        $sheet->setActiveSheetIndex(1);
        $sheet->getActiveSheet()->setTitle('Employés');
        $sheet->getActiveSheet()->setCellValue('A1', 'ID');
        $sheet->getActiveSheet()->setCellValue('B1', 'Matricule');
        $sheet->getActiveSheet()->setCellValue('C1', 'Nom');
        $sheet->getActiveSheet()->setCellValue('D1', 'Prénom');
        
        $row = 2;
        foreach ($employes as $employe) {
            $sheet->getActiveSheet()->setCellValue('A' . $row, $employe->id);
            $sheet->getActiveSheet()->setCellValue('B' . $row, $employe->matricule);
            $sheet->getActiveSheet()->setCellValue('C' . $row, $employe->nom);
            $sheet->getActiveSheet()->setCellValue('D' . $row, $employe->prenom);
            $row++;
        }
        
        // Formatage de l'en-tête des employés
        $sheet->getActiveSheet()->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4361EE']],
        ]);
        
        // Retour à la première feuille
        $sheet->setActiveSheetIndex(0);
        $sheet->getActiveSheet()->setTitle('Présences');
        
        // Ajouter une ligne d'exemple
        $sheet->getActiveSheet()->setCellValue('A2', '1');
        $sheet->getActiveSheet()->setCellValue('B2', '2023-06-01');
        $sheet->getActiveSheet()->setCellValue('C2', '09:05');
        $sheet->getActiveSheet()->setCellValue('D2', '17:30');
        $sheet->getActiveSheet()->setCellValue('E2', 'Journée normale de travail');
        
        // Style pour les lignes normales
        $sheet->getActiveSheet()->getStyle('A2:E2')->applyFromArray([
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'EEEEEE']],
        ]);
        
        // Ajouter des commentaires pour aider à comprendre les champs
        $sheet->getActiveSheet()->getComment('A1')->getText()->createTextRun('Voir la feuille "Employés" pour les ID disponibles');
        $sheet->getActiveSheet()->getComment('B1')->getText()->createTextRun('Format: YYYY-MM-DD');
        $sheet->getActiveSheet()->getComment('C1')->getText()->createTextRun('Format: HH:MM');
        $sheet->getActiveSheet()->getComment('D1')->getText()->createTextRun('Format: HH:MM (optionnel)');
        $sheet->getActiveSheet()->getComment('E1')->getText()->createTextRun('Optionnel: Commentaire sur la présence');
        
        return [
            // Style pour l'en-tête
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4361EE']],
            ],
        ];
    }
    
    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 30,
        ];
    }
}