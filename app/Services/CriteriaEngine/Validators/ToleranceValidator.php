<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;

/**
 * Validateur de tolérance simple
 * Applique des validations de tolérance de base sans planning
 */
class ToleranceValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Tolérance de base';
    }

    public function getDescription(): string
    {
        return 'Applique des validations de tolérance de base';
    }

    public function getPriority(): int
    {
        return 7;
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true;
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        return !is_null($criteria->tolerance_avant) || !is_null($criteria->tolerance_apres);
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        return ValidationResult::success('Validation de tolérance de base réussie', [
            'tolerance_avant' => $criteria->tolerance_avant,
            'tolerance_apres' => $criteria->tolerance_apres
        ]);
    }
} 