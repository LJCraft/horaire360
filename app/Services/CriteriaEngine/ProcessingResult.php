<?php

namespace App\Services\CriteriaEngine;

use App\Enums\ProcessingStatus;
use Carbon\Carbon;

class ProcessingResult
{
    public function __construct(
        public ProcessingStatus $status,
        public array $appliedValidators = [],
        public array $pendingValidators = [],
        public array $errors = [],
        public array $calculatedValues = [],
        public array $warnings = [],
        public ?Carbon $processedAt = null,
        public array $metadata = []
    ) {
        $this->processedAt = $processedAt ?? now();
    }

    /**
     * Ajouter un validateur appliqué avec succès
     */
    public function addAppliedValidator(string $validatorClass, array $result = []): void
    {
        $this->appliedValidators[] = [
            'validator' => $validatorClass,
            'result' => $result,
            'applied_at' => now()->toISOString()
        ];
    }

    /**
     * Ajouter un validateur en attente
     */
    public function addPendingValidator(string $validatorClass, string $reason): void
    {
        $this->pendingValidators[] = [
            'validator' => $validatorClass,
            'reason' => $reason,
            'pending_since' => now()->toISOString()
        ];
    }

    /**
     * Ajouter une erreur
     */
    public function addError(string $validator, string $message, array $context = []): void
    {
        $this->errors[] = [
            'validator' => $validator,
            'message' => $message,
            'context' => $context,
            'occurred_at' => now()->toISOString()
        ];
    }

    /**
     * Ajouter un avertissement
     */
    public function addWarning(string $validator, string $message, array $context = []): void
    {
        $this->warnings[] = [
            'validator' => $validator,
            'message' => $message,
            'context' => $context,
            'occurred_at' => now()->toISOString()
        ];
    }

    /**
     * Ajouter une valeur calculée
     */
    public function addCalculatedValue(string $key, mixed $value, string $validator = null): void
    {
        $this->calculatedValues[$key] = [
            'value' => $value,
            'calculated_by' => $validator,
            'calculated_at' => now()->toISOString()
        ];
    }

    /**
     * Obtenir une valeur calculée
     */
    public function getCalculatedValue(string $key): mixed
    {
        return $this->calculatedValues[$key]['value'] ?? null;
    }

    /**
     * Vérifier si le traitement a réussi
     */
    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * Vérifier si des erreurs sont présentes
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Vérifier si des avertissements sont présents
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Obtenir un résumé du traitement
     */
    public function getSummary(): array
    {
        return [
            'status' => $this->status->value,
            'status_description' => $this->status->getDescription(),
            'applied_validators_count' => count($this->appliedValidators),
            'pending_validators_count' => count($this->pendingValidators),
            'errors_count' => count($this->errors),
            'warnings_count' => count($this->warnings),
            'calculated_values_count' => count($this->calculatedValues),
            'processed_at' => $this->processedAt->toISOString(),
        ];
    }

    /**
     * Convertir en tableau pour stockage en base
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'applied_validators' => $this->appliedValidators,
            'pending_validators' => $this->pendingValidators,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'calculated_values' => $this->calculatedValues,
            'processed_at' => $this->processedAt->toISOString(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Créer depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: ProcessingStatus::from($data['status']),
            appliedValidators: $data['applied_validators'] ?? [],
            pendingValidators: $data['pending_validators'] ?? [],
            errors: $data['errors'] ?? [],
            calculatedValues: $data['calculated_values'] ?? [],
            warnings: $data['warnings'] ?? [],
            processedAt: isset($data['processed_at']) ? Carbon::parse($data['processed_at']) : null,
            metadata: $data['metadata'] ?? []
        );
    }
} 