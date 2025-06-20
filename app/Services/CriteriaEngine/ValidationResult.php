<?php

namespace App\Services\CriteriaEngine;

class ValidationResult
{
    public function __construct(
        public bool $success,
        public string $message = '',
        public array $data = [],
        public array $errors = [],
        public array $warnings = [],
        public bool $requiresPlanning = false
    ) {}

    /**
     * Créer un résultat de succès
     */
    public static function success(string $message = '', array $data = []): self
    {
        return new self(
            success: true,
            message: $message,
            data: $data
        );
    }

    /**
     * Créer un résultat d'échec
     */
    public static function failure(string $message, array $errors = []): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors
        );
    }

    /**
     * Créer un résultat en attente de planning
     */
    public static function pendingPlanning(string $message = 'Planning requis pour cette validation'): self
    {
        return new self(
            success: false,
            message: $message,
            requiresPlanning: true
        );
    }

    /**
     * Ajouter un avertissement
     */
    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    /**
     * Ajouter des données
     */
    public function addData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Vérifier si le résultat a des avertissements
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Vérifier si le résultat a des erreurs
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Convertir en tableau
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'requires_planning' => $this->requiresPlanning,
        ];
    }
} 