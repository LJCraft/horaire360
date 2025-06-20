<?php

namespace App\Enums;

enum ProcessingStatus: string
{
    case FULLY_PROCESSED = 'fully_processed';
    case PARTIALLY_PROCESSED = 'partially_processed';
    case PENDING_PLANNING = 'pending_planning';
    case CRITERIA_ERROR = 'criteria_error';
    case NO_CRITERIA = 'no_criteria';
    case NOT_PROCESSED = 'not_processed';
    case REPROCESSING_REQUIRED = 'reprocessing_required';

    /**
     * Obtenir une description lisible du statut
     */
    public function getDescription(): string
    {
        return match($this) {
            self::FULLY_PROCESSED => 'Traitement complet effectué',
            self::PARTIALLY_PROCESSED => 'Traitement partiel (certains critères en attente)',
            self::PENDING_PLANNING => 'En attente de planning',
            self::CRITERIA_ERROR => 'Erreur dans l\'application des critères',
            self::NO_CRITERIA => 'Aucun critère applicable',
            self::NOT_PROCESSED => 'Non traité',
            self::REPROCESSING_REQUIRED => 'Retraitement requis',
        };
    }

    /**
     * Vérifier si le statut indique un traitement réussi
     */
    public function isSuccessful(): bool
    {
        return in_array($this, [
            self::FULLY_PROCESSED,
            self::PARTIALLY_PROCESSED,
        ]);
    }

    /**
     * Vérifier si le statut nécessite une action
     */
    public function requiresAction(): bool
    {
        return in_array($this, [
            self::PENDING_PLANNING,
            self::CRITERIA_ERROR,
            self::REPROCESSING_REQUIRED,
        ]);
    }
} 