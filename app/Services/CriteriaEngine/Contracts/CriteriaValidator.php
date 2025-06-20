<?php

namespace App\Services\CriteriaEngine\Contracts;

use App\Models\Presence;
use App\Models\CriterePointage;
use App\Models\Planning;
use App\Services\CriteriaEngine\ValidationResult;

/**
 * Interface pour tous les validateurs de critères
 */
interface CriteriaValidator
{
    /**
     * Vérifier si ce validateur peut fonctionner sans planning
     */
    public function canApplyWithoutPlanning(): bool;

    /**
     * Obtenir la priorité du validateur (1 = haute, 10 = basse)
     */
    public function getPriority(): int;

    /**
     * Obtenir le nom du validateur
     */
    public function getName(): string;

    /**
     * Obtenir la description du validateur
     */
    public function getDescription(): string;

    /**
     * Vérifier si ce validateur s'applique au critère donné
     */
    public function appliesTo(CriterePointage $criteria): bool;

    /**
     * Valider un pointage selon le critère donné
     * 
     * @param Presence $pointage Le pointage à valider
     * @param CriterePointage $criteria Le critère à appliquer
     * @param Planning|null $planning Le planning si disponible
     * @return ValidationResult Le résultat de la validation
     */
    public function validate(Presence $pointage, CriterePointage $criteria, ?Planning $planning = null): ValidationResult;

    /**
     * Obtenir les champs de présence que ce validateur peut calculer/modifier
     */
    public function getCalculatedFields(): array;

    /**
     * Vérifier si ce validateur nécessite des critères spécifiques
     */
    public function getRequiredCriteriaFields(): array;
} 