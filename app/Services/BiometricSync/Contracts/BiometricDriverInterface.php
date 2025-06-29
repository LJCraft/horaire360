<?php

namespace App\Services\BiometricSync\Contracts;

use App\Models\BiometricDevice;

/**
 * Interface pour tous les drivers d'appareils biométriques
 */
interface BiometricDriverInterface
{
    /**
     * Tester la connexion à l'appareil
     *
     * @param BiometricDevice $device
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(BiometricDevice $device): array;

    /**
     * Synchroniser les données de pointage depuis l'appareil
     *
     * @param BiometricDevice $device
     * @param array $options Options de synchronisation (date_from, date_to, etc.)
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function syncData(BiometricDevice $device, array $options = []): array;

    /**
     * Obtenir les informations sur l'appareil
     *
     * @param BiometricDevice $device
     * @return array Informations sur l'appareil
     */
    public function getDeviceInfo(BiometricDevice $device): array;

    /**
     * Vérifier si le driver est disponible (SDK installé, etc.)
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Obtenir les options de configuration par défaut
     *
     * @return array
     */
    public function getDefaultConfig(): array;

    /**
     * Valider la configuration de l'appareil
     *
     * @param array $config
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateConfig(array $config): array;
}

/**
 * Interface pour la validation des données en temps réel
 */
interface DataValidatorInterface
{
    /**
     * Valider les données avant traitement
     * 
     * @param array $data Données brutes de l'appareil
     * @return ValidationResult Résultat de la validation
     */
    public function validateData(array $data): ValidationResult;

    /**
     * Nettoyer et normaliser les données
     * 
     * @param array $data Données à nettoyer
     * @return array Données nettoyées
     */
    public function sanitizeData(array $data): array;

    /**
     * Détecter les anomalies dans les données
     * 
     * @param array $data Données à analyser
     * @return array Liste des anomalies détectées
     */
    public function detectAnomalies(array $data): array;
}

/**
 * Classe pour les résultats de validation
 */
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public array $validRecords = [],
        public array $invalidRecords = []
    ) {}

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getValidCount(): int
    {
        return count($this->validRecords);
    }

    public function getErrorCount(): int
    {
        return count($this->invalidRecords);
    }
} 