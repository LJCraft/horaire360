<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;
use Carbon\Carbon;

/**
 * Validateur d'heures supplémentaires
 * Calcule les heures supplémentaires selon le planning et les seuils définis
 */
class OvertimeValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Heures supplémentaires';
    }

    public function getDescription(): string
    {
        return 'Calcule les heures supplémentaires selon le planning et les seuils définis';
    }

    public function getPriority(): int
    {
        return 9; // Priorité basse - calcul final
    }

    public function canApplyWithoutPlanning(): bool
    {
        return false; // Nécessite absolument un planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        return $criteria->calcul_heures_sup && !is_null($criteria->seuil_heures_sup);
    }

    public function getRequiredCriteriaFields(): array
    {
        return ['calcul_heures_sup', 'seuil_heures_sup'];
    }

    public function getCalculatedFields(): array
    {
        return ['heures_supplementaires'];
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        // Vérifier les données de base
        $baseValidation = $this->validatePointageData($pointage);
        if (!$baseValidation->success) {
            return $baseValidation;
        }

        // Vérifier la présence du planning
        if (!$planning) {
            return ValidationResult::pendingPlanning('Planning requis pour calculer les heures supplémentaires');
        }

        // Vérifier si c'est un jour de repos
        if ($this->isJourRepos($planning, $pointage)) {
            // Sur un jour de repos, toutes les heures travaillées sont supplémentaires
            if ($pointage->heure_arrivee && $pointage->heure_depart) {
                $heuresTravaillees = $this->calculateHeuresTravaillees($pointage);
                return ValidationResult::success('Jour de repos - toutes les heures sont supplémentaires', [
                    'jour_repos' => true,
                    'heures_supplementaires' => $heuresTravaillees,
                    'heures_normales' => 0
                ]);
            }
            
            return ValidationResult::success('Jour de repos sans heures travaillées', [
                'jour_repos' => true,
                'heures_supplementaires' => 0
            ]);
        }

        // Obtenir le détail du planning
        $planningDetail = $this->getPlanningDetail($planning, $pointage);
        if (!$planningDetail) {
            return ValidationResult::failure('Détail de planning non trouvé pour ce jour');
        }

        // Calculer les heures supplémentaires
        return $this->calculateOvertime($pointage, $planningDetail, $criteria);
    }

    /**
     * Calculer les heures travaillées
     */
    private function calculateHeuresTravaillees(Presence $pointage): float
    {
        if (!$pointage->heure_arrivee || !$pointage->heure_depart) {
            return 0;
        }

        try {
            $arrivee = Carbon::parse($pointage->heure_arrivee);
            $depart = Carbon::parse($pointage->heure_depart);

            // Gérer le travail de nuit
            if ($depart->lt($arrivee)) {
                $depart->addDay();
            }

            return $arrivee->diffInMinutes($depart) / 60;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculer les heures supplémentaires
     */
    private function calculateOvertime(Presence $pointage, $planningDetail, CriterePointage $criteria): ValidationResult
    {
        $data = [];
        $warnings = [];

        // Calculer les heures travaillées
        $heuresTravaillees = $this->calculateHeuresTravaillees($pointage);
        
        if ($heuresTravaillees === 0) {
            return ValidationResult::success('Aucune heure travaillée', [
                'heures_travaillees' => 0,
                'heures_supplementaires' => 0
            ]);
        }

        // Calculer les heures prévues selon le planning
        try {
            $heureDebut = Carbon::parse($planningDetail->heure_debut);
            $heureFin = Carbon::parse($planningDetail->heure_fin);
            
            // Gérer le travail de nuit dans le planning
            if ($heureFin->lt($heureDebut)) {
                $heureFin->addDay();
            }
            
            $heuresPrevues = $heureDebut->diffInMinutes($heureFin) / 60;
            
        } catch (\Exception $e) {
            return ValidationResult::failure('Erreur lors du calcul des heures prévues: ' . $e->getMessage());
        }

        $data['heures_travaillees'] = $heuresTravaillees;
        $data['heures_prevues'] = $heuresPrevues;

        // Appliquer le seuil d'heures supplémentaires
        $seuilMinutes = $criteria->seuil_heures_sup; // Seuil en minutes
        $seuilHeures = $seuilMinutes / 60;
        
        $heuresNormales = min($heuresTravaillees, $heuresPrevues + $seuilHeures);
        $heuresSupplementaires = max(0, $heuresTravaillees - $heuresNormales);

        $data['seuil_heures_sup'] = $seuilHeures;
        $data['heures_normales'] = $heuresNormales;
        $data['heures_supplementaires'] = round($heuresSupplementaires, 2);

        // Avertissements
        if ($heuresSupplementaires > 2) {
            $warnings[] = "Nombre important d'heures supplémentaires: " . round($heuresSupplementaires, 1) . "h";
        }

        if ($heuresTravaillees < $heuresPrevues - 1) {
            $warnings[] = "Heures travaillées inférieures aux heures prévues";
            $data['heures_manquantes'] = round($heuresPrevues - $heuresTravaillees, 2);
        }

        // Calculer le pourcentage d'heures supplémentaires
        if ($heuresPrevues > 0) {
            $data['pourcentage_heures_sup'] = round(($heuresSupplementaires / $heuresPrevues) * 100, 1);
        }

        $result = ValidationResult::success('Heures supplémentaires calculées', $data);
        foreach ($warnings as $warning) {
            $result->addWarning($warning);
        }

        return $result;
    }
} 