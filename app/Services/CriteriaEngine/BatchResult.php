<?php

namespace App\Services\CriteriaEngine;

use App\Enums\ProcessingStatus;
use Carbon\Carbon;

/**
 * Résultat du traitement par lot de pointages
 */
class BatchResult
{
    protected array $results = [];
    protected Carbon $processedAt;

    public function __construct()
    {
        $this->processedAt = now();
    }

    /**
     * Ajouter un résultat de traitement
     */
    public function addResult(int $pointageId, ProcessingResult $result): void
    {
        $this->results[$pointageId] = $result;
    }

    /**
     * Obtenir tous les résultats
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Obtenir un résultat spécifique
     */
    public function getResult(int $pointageId): ?ProcessingResult
    {
        return $this->results[$pointageId] ?? null;
    }

    /**
     * Obtenir le nombre total de pointages traités
     */
    public function getTotalProcessed(): int
    {
        return count($this->results);
    }

    /**
     * Obtenir le nombre de traitements réussis
     */
    public function getSuccessfulCount(): int
    {
        return count(array_filter($this->results, fn($result) => $result->isSuccessful()));
    }

    /**
     * Obtenir le nombre de traitements en erreur
     */
    public function getErrorCount(): int
    {
        return count(array_filter($this->results, fn($result) => $result->hasErrors()));
    }

    /**
     * Obtenir le nombre de traitements en attente
     */
    public function getPendingCount(): int
    {
        return count(array_filter($this->results, fn($result) => $result->status === ProcessingStatus::PENDING_PLANNING));
    }

    /**
     * Obtenir les résultats par statut
     */
    public function getResultsByStatus(ProcessingStatus $status): array
    {
        return array_filter($this->results, fn($result) => $result->status === $status);
    }

    /**
     * Obtenir un résumé du traitement par lot
     */
    public function getSummary(): array
    {
        $statusCounts = [];
        foreach (ProcessingStatus::cases() as $status) {
            $statusCounts[$status->value] = count($this->getResultsByStatus($status));
        }

        return [
            'processed_at' => $this->processedAt->toISOString(),
            'total_processed' => $this->getTotalProcessed(),
            'successful_count' => $this->getSuccessfulCount(),
            'error_count' => $this->getErrorCount(),
            'pending_count' => $this->getPendingCount(),
            'status_breakdown' => $statusCounts,
            'success_rate' => $this->getTotalProcessed() > 0 
                ? round(($this->getSuccessfulCount() / $this->getTotalProcessed()) * 100, 2) 
                : 0
        ];
    }

    /**
     * Vérifier si tous les traitements ont réussi
     */
    public function isFullySuccessful(): bool
    {
        return $this->getSuccessfulCount() === $this->getTotalProcessed();
    }

    /**
     * Obtenir toutes les erreurs du lot
     */
    public function getAllErrors(): array
    {
        $allErrors = [];
        foreach ($this->results as $pointageId => $result) {
            if ($result->hasErrors()) {
                $allErrors[$pointageId] = $result->errors;
            }
        }
        return $allErrors;
    }

    /**
     * Obtenir tous les avertissements du lot
     */
    public function getAllWarnings(): array
    {
        $allWarnings = [];
        foreach ($this->results as $pointageId => $result) {
            if ($result->hasWarnings()) {
                $allWarnings[$pointageId] = $result->warnings;
            }
        }
        return $allWarnings;
    }
} 