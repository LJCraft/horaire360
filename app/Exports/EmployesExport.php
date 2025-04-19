<?php

namespace App\Exports;

use App\Models\Employe;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        
        return Employe::with('poste')->get();
    }
    
    /**
     * @param Employe $employe
     */
    public function map($employe): array
    {
        return [
            $employe->matricule,
            $employe->nom,
            $employe->prenom,
            $employe->email,
            $employe->telephone,
            $employe->date_naissance,
            $employe->date_embauche,
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
     * Styles du fichier Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}