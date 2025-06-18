<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\Pointage;
use Carbon\Carbon;

class PointageService
{
    protected $planningService;

    public function __construct(PlanningService $planningService)
    {
        $this->planningService = $planningService;
    }

    /**
     * Valider un pointage manuel
     */
    public function validerPointageManuel(Employe $employe, Carbon $date, ?string $heureArrivee, ?string $heureDepart): array
    {
        // Vérifier la conformité du pointage d'arrivée
        $conformiteArrivee = $this->planningService->verifierConformitePointage(
            $employe,
            $date,
            $heureArrivee ? Carbon::parse($heureArrivee) : null
        );

        // Vérifier la conformité du pointage de départ
        $conformiteDepart = null;
        if ($heureDepart) {
            $horaires = $this->planningService->getHorairesForDate($employe, $date);
            
            if ($horaires && $horaires['type'] !== 'repos') {
                $heureFinPrevue = Carbon::parse($horaires['heure_fin']);
                $heureFinPointage = Carbon::parse($heureDepart);
                
                $conformiteDepart = [
                    'conforme' => true,
                    'raison' => null
                ];
                
                if ($heureFinPointage->lt($heureFinPrevue)) {
                    $conformiteDepart = [
                        'conforme' => false,
                        'raison' => 'Départ anticipé de ' . $heureFinPrevue->diffInMinutes($heureFinPointage) . ' minutes'
                    ];
                }
            }
        }

        return [
            'valide' => $conformiteArrivee['conforme'] && (!$conformiteDepart || $conformiteDepart['conforme']),
            'conformite_arrivee' => $conformiteArrivee,
            'conformite_depart' => $conformiteDepart
        ];
    }

    /**
     * Valider un pointage biométrique
     */
    public function validerPointageBiometrique(Employe $employe, Carbon $date, array $pointages): array
    {
        if (empty($pointages)) {
            return [
                'valide' => false,
                'raison' => 'Aucun pointage biométrique trouvé'
            ];
        }

        // Trier les pointages par heure
        usort($pointages, function($a, $b) {
            return strtotime($a) - strtotime($b);
        });

        // Premier pointage = arrivée, dernier pointage = départ
        $heureArrivee = $pointages[0];
        $heureDepart = end($pointages);

        return $this->validerPointageManuel($employe, $date, $heureArrivee, $heureDepart);
    }

    /**
     * Enregistrer un pointage validé
     */
    public function enregistrerPointage(Employe $employe, Carbon $date, string $heureArrivee, ?string $heureDepart = null): Pointage
    {
        $validation = $this->validerPointageManuel($employe, $date, $heureArrivee, $heureDepart);
        
        if (!$validation['valide']) {
            throw new \Exception('Pointage invalide : ' . 
                ($validation['conformite_arrivee']['raison'] ?? '') . ' ' .
                ($validation['conformite_depart']['raison'] ?? '')
            );
        }

        return Pointage::create([
            'employe_id' => $employe->id,
            'date' => $date,
            'heure_arrivee' => $heureArrivee,
            'heure_depart' => $heureDepart,
            'type' => 'manuel',
            'statut' => 'valide'
        ]);
    }

    /**
     * Importer des pointages
     */
    public function importerPointages(array $pointages): array
    {
        $resultats = [
            'succes' => 0,
            'echecs' => 0,
            'details' => []
        ];

        foreach ($pointages as $pointage) {
            try {
                $employe = Employe::findOrFail($pointage['employe_id']);
                $date = Carbon::parse($pointage['date']);
                
                $validation = $this->validerPointageManuel(
                    $employe,
                    $date,
                    $pointage['heure_arrivee'],
                    $pointage['heure_depart'] ?? null
                );

                if ($validation['valide']) {
                    $this->enregistrerPointage(
                        $employe,
                        $date,
                        $pointage['heure_arrivee'],
                        $pointage['heure_depart'] ?? null
                    );
                    
                    $resultats['succes']++;
                } else {
                    $resultats['echecs']++;
                    $resultats['details'][] = [
                        'employe' => $employe->nom,
                        'date' => $date->format('Y-m-d'),
                        'raison' => $validation['conformite_arrivee']['raison'] ?? $validation['conformite_depart']['raison']
                    ];
                }
            } catch (\Exception $e) {
                $resultats['echecs']++;
                $resultats['details'][] = [
                    'employe_id' => $pointage['employe_id'],
                    'date' => $pointage['date'],
                    'raison' => $e->getMessage()
                ];
            }
        }

        return $resultats;
    }
} 