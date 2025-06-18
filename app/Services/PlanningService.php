<?php

namespace App\Services;

use App\Models\Planning;
use App\Models\PlanningDetail;
use App\Models\Employe;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PlanningService
{
    /**
     * Récupérer le planning actif d'un employé pour une date donnée
     */
    public function getPlanningForDate(Employe $employe, Carbon $date): ?Planning
    {
        return Planning::where('employe_id', $employe->id)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->where('actif', true)
            ->with('details')
            ->first();
    }

    /**
     * Récupérer les horaires prévus pour un employé à une date donnée
     */
    public function getHorairesForDate(Employe $employe, Carbon $date): ?array
    {
        $planning = $this->getPlanningForDate($employe, $date);
        
        if (!$planning) {
            return null;
        }

        $jourSemaine = $date->dayOfWeek === 0 ? 7 : $date->dayOfWeek;
        $detail = $planning->details->firstWhere('jour', $jourSemaine);

        if (!$detail) {
            return null;
        }

        if ($detail->jour_repos) {
            return [
                'type' => 'repos',
                'heure_debut' => null,
                'heure_fin' => null,
                'duree' => 0
            ];
        }

        if ($detail->jour_entier) {
            return [
                'type' => 'jour_entier',
                'heure_debut' => '08:00',
                'heure_fin' => '17:00',
                'duree' => 8
            ];
        }

        if ($detail->heure_debut && $detail->heure_fin) {
            $debut = Carbon::parse($detail->heure_debut);
            $fin = Carbon::parse($detail->heure_fin);
            
            if ($fin->lt($debut)) {
                $fin->addDay();
            }
            
            $duree = $debut->diffInHours($fin);
            
            return [
                'type' => 'horaire',
                'heure_debut' => $detail->heure_debut,
                'heure_fin' => $detail->heure_fin,
                'duree' => $duree
            ];
        }

        return null;
    }

    /**
     * Calculer les heures prévues pour une période donnée
     */
    public function calculerHeuresPrevues(Employe $employe, Carbon $dateDebut, Carbon $dateFin): array
    {
        $period = CarbonPeriod::create($dateDebut, $dateFin);
        $heuresPrevues = 0;
        $joursPrevus = 0;
        $details = [];

        foreach ($period as $date) {
            $horaires = $this->getHorairesForDate($employe, $date);
            
            if ($horaires) {
                $details[$date->format('Y-m-d')] = $horaires;
                
                if ($horaires['type'] !== 'repos') {
                    $joursPrevus++;
                    $heuresPrevues += $horaires['duree'];
                }
            }
        }

        return [
            'heures_prevues' => $heuresPrevues,
            'jours_prevus' => $joursPrevus,
            'details' => $details
        ];
    }

    /**
     * Vérifier si un pointage est conforme au planning
     */
    public function verifierConformitePointage(Employe $employe, Carbon $date, ?Carbon $heurePointage): array
    {
        $horaires = $this->getHorairesForDate($employe, $date);
        
        if (!$horaires) {
            return [
                'conforme' => false,
                'raison' => 'Pas de planning défini pour cette date'
            ];
        }

        if ($horaires['type'] === 'repos') {
            return [
                'conforme' => $heurePointage === null,
                'raison' => $heurePointage ? 'Pointage en jour de repos' : null
            ];
        }

        if (!$heurePointage) {
            return [
                'conforme' => false,
                'raison' => 'Pointage manquant'
            ];
        }

        $heurePrevue = Carbon::parse($horaires['heure_debut']);
        $tolerance = 15; // minutes de tolérance

        if ($heurePointage->gt($heurePrevue->copy()->addMinutes($tolerance))) {
            return [
                'conforme' => false,
                'raison' => 'Retard de ' . $heurePointage->diffInMinutes($heurePrevue) . ' minutes'
            ];
        }

        return [
            'conforme' => true,
            'raison' => null
        ];
    }

    /**
     * Calculer les heures supplémentaires pour une période
     */
    public function calculerHeuresSupplementaires(Employe $employe, Carbon $dateDebut, Carbon $dateFin, array $pointages): array
    {
        $heuresSup = 0;
        $details = [];

        foreach ($pointages as $date => $pointage) {
            $horaires = $this->getHorairesForDate($employe, Carbon::parse($date));
            
            if ($horaires && $horaires['type'] !== 'repos') {
                $heureFinPrevue = Carbon::parse($horaires['heure_fin']);
                $heureFinPointage = Carbon::parse($pointage['heure_fin']);
                
                if ($heureFinPointage->gt($heureFinPrevue)) {
                    $minutesSup = $heureFinPrevue->diffInMinutes($heureFinPointage);
                    $heuresSup += $minutesSup / 60;
                    
                    $details[$date] = [
                        'heures_sup' => $minutesSup / 60,
                        'heure_fin_prevue' => $horaires['heure_fin'],
                        'heure_fin_pointage' => $pointage['heure_fin']
                    ];
                }
            }
        }

        return [
            'heures_sup' => $heuresSup,
            'details' => $details
        ];
    }
} 