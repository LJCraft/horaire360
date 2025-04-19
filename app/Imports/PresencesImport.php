<?php

namespace App\Imports;

use App\Models\Presence;
use App\Models\Employe;
use App\Models\Planning;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PresencesImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
     * Traitement des données importées.
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $importedCount = 0;
        $errorCount = 0;
        $updatedCount = 0;
        
        foreach ($rows as $row) {
            try {
                // Validation supplémentaire pour les champs obligatoires
                $validator = Validator::make($row->toArray(), [
                    'employe_id' => 'required|exists:employes,id',
                    'date' => 'required',
                    'heure_arrivee' => 'required',
                ]);
                
                if ($validator->fails()) {
                    $errorCount++;
                    Log::error('Validation failed for row: ' . json_encode($row) . ' - Errors: ' . json_encode($validator->errors()));
                    continue;
                }
                
                // Conversion des dates et heures
                $date = Date::excelToDateTimeObject($row['date'])->format('Y-m-d');
                $heureArrivee = $this->formatTime($row['heure_arrivee']);
                $heureDepart = isset($row['heure_depart']) ? $this->formatTime($row['heure_depart']) : null;
                
                // Recherche du planning pour déterminer le retard
                $planning = Planning::where('employe_id', $row['employe_id'])
                    ->where('date', $date)
                    ->first();
                
                // Déterminer si l'employé est en retard (tolérance de 10 minutes)
                $retard = false;
                $departAnticipe = false;
                
                if ($planning) {
                    $heureDebutPlanning = \Carbon\Carbon::parse($planning->heure_debut)->addMinutes(10);
                    $heureArriveeCarbon = \Carbon\Carbon::parse($heureArrivee);
                    $retard = $heureArriveeCarbon > $heureDebutPlanning;
                    
                    if ($heureDepart && $planning->heure_fin) {
                        $heureFinPlanning = \Carbon\Carbon::parse($planning->heure_fin)->subMinutes(10);
                        $heureDepartCarbon = \Carbon\Carbon::parse($heureDepart);
                        $departAnticipe = $heureDepartCarbon < $heureFinPlanning;
                    }
                }
                
                // Vérifier si une présence existe déjà pour cet employé à cette date
                $presence = Presence::where('employe_id', $row['employe_id'])
                    ->where('date', $date)
                    ->first();
                
                if ($presence) {
                    // Mise à jour de la présence existante
                    $presence->update([
                        'heure_arrivee' => $heureArrivee,
                        'heure_depart' => $heureDepart,
                        'retard' => $retard,
                        'depart_anticipe' => $departAnticipe,
                        'commentaire' => $row['commentaire'] ?? null,
                    ]);
                    
                    $updatedCount++;
                } else {
                    // Création d'une nouvelle présence
                    Presence::create([
                        'employe_id' => $row['employe_id'],
                        'date' => $date,
                        'heure_arrivee' => $heureArrivee,
                        'heure_depart' => $heureDepart,
                        'retard' => $retard,
                        'depart_anticipe' => $departAnticipe,
                        'commentaire' => $row['commentaire'] ?? null,
                    ]);
                    
                    $importedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Error importing presence: ' . $e->getMessage());
            }
        }
        
        session()->flash('import_summary', [
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ]);
    }
    
    /**
     * Règles de validation pour l'importation.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'employe_id' => 'required|exists:employes,id',
            'date' => 'required',
            'heure_arrivee' => 'required',
        ];
    }
    
    /**
     * Messages d'erreur personnalisés.
     *
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'employe_id.required' => 'L\'ID de l\'employé est obligatoire.',
            'employe_id.exists' => 'L\'employé sélectionné n\'existe pas.',
            'date.required' => 'La date est obligatoire.',
            'heure_arrivee.required' => 'L\'heure d\'arrivée est obligatoire.',
        ];
    }
    
    /**
     * Formater l'heure correctement.
     *
     * @param mixed $time
     * @return string
     */
    private function formatTime($time)
    {
        // Si c'est une valeur numérique d'Excel (fraction de jour)
        if (is_numeric($time)) {
            return Date::excelToDateTimeObject($time)->format('H:i:s');
        }
        
        // Si c'est déjà une chaîne de format heure
        if (is_string($time) && preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time)) {
            // S'assurer qu'il y a les secondes
            if (substr_count($time, ':') === 1) {
                $time .= ':00';
            }
            return $time;
        }
        
        // Par défaut, retourner 00:00:00
        return '00:00:00';
    }
}