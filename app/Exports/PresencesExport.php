<?php

namespace App\Exports;

use App\Models\Presence;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PresencesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Presence::with('employe')->get();
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Employé ID',
            'Employé',
            'Date',
            'Heure d\'arrivée',
            'Heure de départ',
            'Durée (heures)',
            'Retard',
            'Départ anticipé',
            'Commentaire',
        ];
    }
    
    /**
     * @param Presence $presence
     * @return array
     */
    public function map($presence): array
    {
        return [
            $presence->id,
            $presence->employe_id,
            $presence->employe->nom_complet,
            $presence->date->format('Y-m-d'),
            $presence->heure_arrivee,
            $presence->heure_depart,
            $presence->duree,
            $presence->retard ? 'Oui' : 'Non',
            $presence->depart_anticipe ? 'Oui' : 'Non',
            $presence->commentaire,
        ];
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4361EE']],
            ],
        ];
    }
}
