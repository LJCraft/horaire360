<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;

/**
 * Validateur de pause
 * Valide les pauses selon les critères définis
 */
class PauseValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Validation des pauses';
    }

    public function getDescription(): string
    {
        return 'Valide les pauses selon les critères définis';
    }

    public function getPriority(): int
    {
        return 8;
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true;
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        return !is_null($criteria->duree_pause);
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        return ValidationResult::success('Validation des pauses réussie', [
            'duree_pause_attendue' => $criteria->duree_pause
        ]);
    }
} 