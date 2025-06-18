<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Pointage;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RapportService
{
    protected $planningService;

    public function __construct(PlanningService $planningService)
    {
        $this->planningService = $planningService;
    }

    /**
     * Générer le rapport de ponctualité et d'assiduité
     */
    public function genererRapportPonctualite(Employe $employe, Carbon $dateDebut, Carbon $dateFin): array
    {
        // Récupérer les horaires prévus
        $horairesPrevus = $this->planningService->calculerHeuresPrevues($employe, $dateDebut, $dateFin);
        
        // Récupérer les pointages
        $pointages = Pointage::where('employe_id', $employe->id)
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->get()
            ->groupBy(function($pointage) {
                return $pointage->date->format('Y-m-d');
            });

        $retards = 0;
        $absences = 0;
        $details = [];

        foreach ($horairesPrevus['details'] as $date => $horaires) {
            $dateCarbon = Carbon::parse($date);
            $pointageDuJour = $pointages[$date] ?? null;
            
            $conformite = $this->planningService->verifierConformitePointage(
                $employe,
                $dateCarbon,
                $pointageDuJour ? Carbon::parse($pointageDuJour->heure_arrivee) : null
            );

            if (!$conformite['conforme']) {
                if ($conformite['raison'] === 'Pointage manquant') {
                    $absences++;
                } elseif (strpos($conformite['raison'], 'Retard') === 0) {
                    $retards++;
                }
            }

            $details[$date] = [
                'horaires_prevus' => $horaires,
                'pointage' => $pointageDuJour,
                'conformite' => $conformite
            ];
        }

        return [
            'employe' => $employe,
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'statistiques' => [
                'jours_prevus' => $horairesPrevus['jours_prevus'],
                'heures_prevues' => $horairesPrevus['heures_prevues'],
                'retards' => $retards,
                'absences' => $absences
            ],
            'details' => $details
        ];
    }

    /**
     * Générer le rapport global
     */
    public function genererRapportGlobal(Employe $employe, Carbon $dateDebut, Carbon $dateFin): array
    {
        // Récupérer les horaires prévus
        $horairesPrevus = $this->planningService->calculerHeuresPrevues($employe, $dateDebut, $dateFin);
        
        // Récupérer les pointages
        $pointages = Pointage::where('employe_id', $employe->id)
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->get()
            ->groupBy(function($pointage) {
                return $pointage->date->format('Y-m-d');
            });

        // Calculer les heures supplémentaires
        $heuresSup = $this->planningService->calculerHeuresSupplementaires(
            $employe,
            $dateDebut,
            $dateFin,
            $pointages->map(function($pointage) {
                return [
                    'heure_fin' => $pointage->first()->heure_depart
                ];
            })->toArray()
        );

        $details = [];
        foreach ($horairesPrevus['details'] as $date => $horaires) {
            $pointageDuJour = $pointages[$date] ?? null;
            
            $details[$date] = [
                'horaires_prevus' => $horaires,
                'pointage' => $pointageDuJour ? [
                    'arrivee' => $pointageDuJour->first()->heure_arrivee,
                    'depart' => $pointageDuJour->first()->heure_depart
                ] : null,
                'heures_sup' => $heuresSup['details'][$date] ?? null
            ];
        }

        return [
            'employe' => $employe,
            'periode' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'statistiques' => [
                'jours_prevus' => $horairesPrevus['jours_prevus'],
                'heures_prevues' => $horairesPrevus['heures_prevues'],
                'heures_sup' => $heuresSup['heures_sup']
            ],
            'details' => $details
        ];
    }
} 