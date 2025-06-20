<?php

namespace App\Services\CriteriaEngine\Validators;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;

/**
 * Validateur de source de pointage
 * Vérifie que la source du pointage correspond aux critères définis
 */
class SourceValidator extends BaseValidator
{
    public function getName(): string
    {
        return 'Source de pointage';
    }

    public function getDescription(): string
    {
        return 'Valide que la source du pointage (biométrique, manuel) correspond aux critères';
    }

    public function getPriority(): int
    {
        return 1; // Haute priorité - validation de base
    }

    public function canApplyWithoutPlanning(): bool
    {
        return true; // Peut fonctionner sans planning
    }

    public function appliesTo(CriterePointage $criteria): bool
    {
        // S'applique si une source de pointage spécifique est définie
        return !empty($criteria->source_pointage) && $criteria->source_pointage !== 'tous';
    }

    public function getRequiredCriteriaFields(): array
    {
        return ['source_pointage'];
    }

    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult
    {
        // Vérifier les données de base
        $baseValidation = $this->validatePointageData($pointage);
        if (!$baseValidation->success) {
            return $baseValidation;
        }

        // Vérifier les champs requis
        $fieldsValidation = $this->validateRequiredFields($criteria);
        if (!$fieldsValidation->success) {
            return $fieldsValidation;
        }

        $sourcePointage = $pointage->source_pointage ?? 'manuel';
        $sourceRequise = $criteria->source_pointage;

        // Si la source requise est 'tous', accepter toutes les sources
        if ($sourceRequise === 'tous') {
            return ValidationResult::success('Source de pointage acceptée (tous)', [
                'source_pointage_validee' => $sourcePointage,
                'source_requise' => $sourceRequise
            ]);
        }

        // Vérifier la correspondance exacte
        if ($sourcePointage === $sourceRequise) {
            return ValidationResult::success('Source de pointage valide', [
                'source_pointage_validee' => $sourcePointage,
                'source_requise' => $sourceRequise
            ]);
        }

        // Source non autorisée
        return ValidationResult::failure(
            "Source de pointage non autorisée. Attendu: {$sourceRequise}, Reçu: {$sourcePointage}",
            ['source_pointage' => $sourcePointage, 'source_requise' => $sourceRequise]
        );
    }
} 