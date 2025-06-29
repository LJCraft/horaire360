<?php

namespace App\Imports;

use App\Models\Employe;
use App\Models\Presence;
use App\Models\Planning;
use App\Models\CriterePointage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PointageImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use Importable, SkipsErrors, SkipsFailures;

    private $importedCount = 0;
    private $updatedCount = 0;
    private $skippedCount = 0;
    private $date;

    public function __construct($date = null)
    {
        $this->date = $date ?? Carbon::today()->format('Y-m-d');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Trouver l'employé par matricule
        $employe = Employe::where('matricule', $row['matricule'])->first();
        
        if (!$employe) {
            Log::warning("Employé non trouvé avec le matricule: " . $row['matricule']);
            $this->skippedCount++;
            return null;
        }

        $heureArrivee = $this->parseTime($row['arrivee_reelle_ar']);
        $heureDepart = $this->parseTime($row['heure_de_depart_hd']);

        // Si aucune heure n'est renseignée, vérifier s'il y a un planning
        // pour marquer comme absent si nécessaire
        if (!$heureArrivee && !$heureDepart) {
            $planning = Planning::where('employe_id', $employe->id)
                ->where('date', $this->date)
                ->first();
                
            if ($planning) {
                // Il y a un planning mais pas de pointage = absence
                $presence = Presence::where('employe_id', $employe->id)
                    ->where('date', $this->date)
                    ->first();
                    
                if (!$presence) {
                    $presence = new Presence([
                        'employe_id' => $employe->id,
                        'date' => $this->date,
                        'heure_arrivee' => null,
                        'heure_depart' => null,
                        'source_pointage' => 'import_template',
                        'statut' => 'absent',
                        'retard' => false,
                        'depart_anticipe' => false
                    ]);
                    
                    $this->importedCount++;
                    return $presence;
                } else {
                    // Marquer comme absent si pas déjà renseigné
                    if (!$presence->heure_arrivee && !$presence->heure_depart) {
                        $presence->statut = 'absent';
                        $presence->save();
                        $this->updatedCount++;
                    }
                }
            }
            
            $this->skippedCount++;
            return null;
        }

        // Vérifier si une présence existe déjà pour cet employé à cette date
        $presence = Presence::where('employe_id', $employe->id)
            ->where('date', $this->date)
            ->first();

        if ($presence) {
            // Mettre à jour la présence existante
            $updated = false;
            
            if ($heureArrivee && $heureArrivee !== $presence->heure_arrivee) {
                $presence->heure_arrivee = $heureArrivee;
                $updated = true;
            }
            
            if ($heureDepart && $heureDepart !== $presence->heure_depart) {
                $presence->heure_depart = $heureDepart;
                $updated = true;
            }
            
            if ($updated) {
                // Recalculer les statuts (retard, départ anticipé, etc.)
                $this->calculatePresenceStatus($presence);
                $presence->save();
                $this->updatedCount++;
            } else {
                $this->skippedCount++;
            }
            
            return null; // Retourner null car on a mis à jour manuellement
        } else {
            // Créer une nouvelle présence
            if ($heureArrivee || $heureDepart) {
                $presence = new Presence([
                    'employe_id' => $employe->id,
                    'date' => $this->date,
                    'heure_arrivee' => $heureArrivee,
                    'heure_depart' => $heureDepart,
                    'source_pointage' => 'import_template'
                ]);

                // Calculer les statuts
                $this->calculatePresenceStatus($presence);
                
                $this->importedCount++;
                return $presence;
            }
        }

        $this->skippedCount++;
        return null;
    }

    /**
     * Parse time from various formats
     */
    private function parseTime($time)
    {
        if (empty($time)) {
            return null;
        }

        // Si c'est déjà au bon format HH:MM
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // Essayer de parser différents formats
        try {
            // Format HH:MM:SS
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
                return substr($time, 0, 5); // Garder seulement HH:MM
            }

            // Format H:MM ou H:M
            if (preg_match('/^\d{1,2}:\d{1,2}$/', $time)) {
                $parts = explode(':', $time);
                return sprintf('%02d:%02d', $parts[0], $parts[1]);
            }

            // Format HHMM
            if (preg_match('/^\d{4}$/', $time)) {
                return substr($time, 0, 2) . ':' . substr($time, 2, 2);
            }

            // Essayer de parser comme un timestamp Excel
            if (is_numeric($time)) {
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($time);
                return $excelDate->format('H:i');
            }

        } catch (\Exception $e) {
            Log::warning("Impossible de parser l'heure: " . $time . " - " . $e->getMessage());
        }

        return null;
    }

    /**
     * Calculate presence status (retard, départ anticipé, etc.)
     */
    private function calculatePresenceStatus(Presence $presence)
    {
        // Récupérer le planning de l'employé pour cette date
        $planning = Planning::where('employe_id', $presence->employe_id)
            ->where('date', $presence->date)
            ->first();

        // Récupérer les critères de pointage applicables
        $criteres = $this->getCriteresPointage($presence->employe_id, $presence->date);

        // Initialiser les statuts
        $presence->retard = false;
        $presence->depart_anticipe = false;
        $presence->statut = 'present';

        if ($planning) {
            // Calculer le retard
            if ($presence->heure_arrivee && $planning->heure_debut) {
                $heureArriveeRelle = Carbon::createFromFormat('H:i', $presence->heure_arrivee);
                $heureArriveePrevu = Carbon::createFromFormat('H:i', $planning->heure_debut);
                
                // Utiliser la tolérance des critères ou 10 minutes par défaut
                $tolerance = $criteres ? $criteres->tolerance_avant : 10;
                
                if ($heureArriveeRelle->diffInMinutes($heureArriveePrevu, false) > $tolerance) {
                    $presence->retard = true;
                    $presence->statut = 'retard';
                }
            }

            // Calculer le départ anticipé
            if ($presence->heure_depart && $planning->heure_fin) {
                $heureDepartRelle = Carbon::createFromFormat('H:i', $presence->heure_depart);
                $heureDepartPrevu = Carbon::createFromFormat('H:i', $planning->heure_fin);
                
                // Utiliser la tolérance des critères ou 10 minutes par défaut
                $tolerance = $criteres ? $criteres->tolerance_apres : 10;
                
                if ($heureDepartRelle->diffInMinutes($heureDepartPrevu, false) < -$tolerance) {
                    $presence->depart_anticipe = true;
                    if ($presence->statut === 'present') {
                        $presence->statut = 'depart_anticipe';
                    }
                }
            }
        } else {
            // Pas de planning trouvé - marquer comme présence sans planning
            $presence->statut = 'present_sans_planning';
        }

        // Calculer les heures travaillées
        if ($presence->heure_arrivee && $presence->heure_depart) {
            $arrivee = Carbon::createFromFormat('H:i', $presence->heure_arrivee);
            $depart = Carbon::createFromFormat('H:i', $presence->heure_depart);
            
            if ($depart->greaterThan($arrivee)) {
                $duree = $arrivee->diffInMinutes($depart);
                $presence->heures_travaillees = round($duree / 60, 2);
                
                // Calculer les heures supplémentaires si planning disponible
                if ($planning && $planning->heure_debut && $planning->heure_fin) {
                    $heuresPrevu = Carbon::createFromFormat('H:i', $planning->heure_debut)
                        ->diffInMinutes(Carbon::createFromFormat('H:i', $planning->heure_fin)) / 60;
                    
                    $heuresSupplementaires = max(0, $presence->heures_travaillees - $heuresPrevu);
                    
                    // Appliquer le seuil des critères si défini
                    if ($criteres && $criteres->calcul_heures_sup && $criteres->seuil_heures_sup > 0) {
                        $seuilHeures = $criteres->seuil_heures_sup / 60; // Convertir minutes en heures
                        $heuresSupplementaires = max(0, $presence->heures_travaillees - $seuilHeures);
                    }
                    
                    $presence->heures_supplementaires = $heuresSupplementaires;
                }
            }
        }

        // Déterminer le statut final
        if (!$presence->heure_arrivee) {
            $presence->statut = 'absent';
        } elseif ($presence->retard && $presence->depart_anticipe) {
            $presence->statut = 'retard_et_depart_anticipe';
        }
    }

    /**
     * Récupérer les critères de pointage applicables pour un employé à une date donnée
     */
    private function getCriteresPointage($employeId, $date)
    {
        // Chercher d'abord un critère individuel
        $critereIndividuel = CriterePointage::where('niveau', 'individuel')
            ->where('employe_id', $employeId)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->orderBy('priorite')
            ->first();

        if ($critereIndividuel) {
            return $critereIndividuel;
        }

        // Chercher un critère départemental
        $employe = Employe::find($employeId);
        if ($employe && $employe->poste && $employe->poste->departement) {
            $critereDepartemental = CriterePointage::where('niveau', 'departemental')
                ->where('departement_id', $employe->poste->departement)
                ->where('date_debut', '<=', $date)
                ->where('date_fin', '>=', $date)
                ->where('actif', true)
                ->orderBy('priorite')
                ->first();

            if ($critereDepartemental) {
                return $critereDepartemental;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'matricule' => ['required'],
            'arrivee_reelle_ar' => ['nullable'],
            'heure_de_depart_hd' => ['nullable'],
        ];
    }

    /**
     * @return int
     */
    public function headingRow(): int
    {
        return 3; // Les en-têtes sont à la ligne 3
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get import statistics
     */
    public function getStats()
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'total' => $this->importedCount + $this->updatedCount + $this->skippedCount
        ];
    }
} 