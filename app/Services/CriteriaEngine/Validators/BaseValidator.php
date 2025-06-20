<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Services\CriteriaEngine\Contracts\CriteriaValidator;
use App\Services\CriteriaEngine\ValidationResult;
use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;

/**
 * Classe de base pour tous les validateurs
 */
abstract class BaseValidator implements CriteriaValidator
{
    /**
     * Priorité par défaut (5 = moyenne)
     */
    public function getPriority(): int
    {
        return 5;
    }

    /**
     * Par défaut, un validateur ne peut pas s'appliquer sans planning
     */
    public function canApplyWithoutPlanning(): bool
    {
        return false;
    }

    /**
     * Champs calculés par défaut (vide)
     */
    public function getCalculatedFields(): array
    {
        return [];
    }

    /**
     * Champs de critères requis par défaut (vide)
     */
    public function getRequiredCriteriaFields(): array
    {
        return [];
    }

    /**
     * Vérifier si tous les champs requis sont présents dans le critère
     */
    protected function validateRequiredFields(CriterePointage $criteria): ValidationResult
    {
        $requiredFields = $this->getRequiredCriteriaFields();
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (is_null($criteria->$field)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return ValidationResult::failure(
                "Champs requis manquants: " . implode(', ', $missingFields),
                ['missing_fields' => $missingFields]
            );
        }

        return ValidationResult::success();
    }

    /**
     * Vérifier si le pointage a les données minimales requises
     */
    protected function validatePointageData(Presence $pointage): ValidationResult
    {
        if (!$pointage->date) {
            return ValidationResult::failure('Date de pointage manquante');
        }

        if (!$pointage->employe_id) {
            return ValidationResult::failure('ID employé manquant');
        }

        return ValidationResult::success();
    }

    /**
     * Obtenir le détail de planning pour un jour donné
     */
    protected function getPlanningDetail(Planning $planning, Presence $pointage): ?\App\Models\PlanningDetail
    {
        $jourSemaine = $pointage->date->dayOfWeekIso;
        
        return $planning->details()
            ->where('jour', $jourSemaine)
            ->first();
    }

    /**
     * Vérifier si c'est un jour de repos selon le planning
     */
    protected function isJourRepos(Planning $planning, Presence $pointage): bool
    {
        $planningDetail = $this->getPlanningDetail($planning, $pointage);
        
        return $planningDetail ? $planningDetail->jour_repos : false;
    }
} 