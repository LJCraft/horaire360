<?php

namespace App\Imports;

use App\Models\Planning;
use App\Models\PlanningDetail;
use App\Models\Employe;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PlanningsImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Regrouper les lignes par employé et période
        $groupedRows = $rows->groupBy(function ($row) {
            return $row['matricule'] . '_' . $row['date_debut'] . '_' . $row['date_fin'];
        });

        foreach ($groupedRows as $group) {
            $firstRow = $group->first();
            
            // Trouver l'employé par matricule
            $employe = Employe::where('matricule', $firstRow['matricule'])->first();
            
            if (!$employe) {
                continue; // Ignorer si l'employé n'existe pas
            }
            
            // Convertir les dates
            $dateDebut = is_numeric($firstRow['date_debut']) 
                ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($firstRow['date_debut'])) 
                : Carbon::parse($firstRow['date_debut']);
            
            $dateFin = is_numeric($firstRow['date_fin']) 
                ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($firstRow['date_fin'])) 
                : Carbon::parse($firstRow['date_fin']);
            
            // Créer le planning
            $planning = Planning::create([
                'employe_id' => $employe->id,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'titre' => $firstRow['titre'] ?? 'Planning ' . $dateDebut->format('d/m/Y'),
                'description' => $firstRow['description'] ?? null,
                'actif' => true,
            ]);
            
            // Créer les détails du planning
            foreach ($group as $row) {
                // Déterminer quel jour est concerné
                $jour = null;
                foreach ([1, 2, 3, 4, 5, 6, 7] as $j) {
                    if (isset($row['jour_' . $j]) && !empty($row['jour_' . $j])) {
                        $jour = $j;
                        $valeur = strtolower($row['jour_' . $j]);
                        
                        $jourRepos = in_array($valeur, ['repos', 'r', 'off']);
                        $jourEntier = in_array($valeur, ['jour', 'j', 'journée', 'complet', 'full']);
                        
                        // Heures de début et fin
                        $heureDebut = null;
                        $heureFin = null;
                        
                        // Si ce n'est ni repos ni journée complète, on cherche les heures
                        if (!$jourRepos && !$jourEntier) {
                            // Format possible : "9-17" ou "9:00-17:00" ou "09:00 - 17:00"
                            $horaires = explode('-', str_replace(' ', '', $valeur));
                            
                            if (count($horaires) === 2) {
                                $heureDebut = $this->formatHeure($horaires[0]);
                                $heureFin = $this->formatHeure($horaires[1]);
                            }
                        }
                        
                        // Créer le détail
                        PlanningDetail::create([
                            'planning_id' => $planning->id,
                            'jour' => $jour,
                            'heure_debut' => $heureDebut,
                            'heure_fin' => $heureFin,
                            'jour_entier' => $jourEntier,
                            'jour_repos' => $jourRepos,
                            'note' => $row['note_' . $j] ?? null,
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Formatage de l'heure
     */
    private function formatHeure($heure)
    {
        // Vérifier si l'heure contient déjà un ":"
        if (strpos($heure, ':') !== false) {
            return $heure;
        }
        
        // Sinon, formatter en ajoutant ":00"
        return $heure . ':00';
    }
    
    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'matricule' => 'required|exists:employes,matricule',
            'date_debut' => 'required',
            'date_fin' => 'required',
        ];
    }
}