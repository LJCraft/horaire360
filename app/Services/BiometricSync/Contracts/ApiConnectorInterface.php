<?php

namespace App\Services\BiometricSync\Contracts;

use App\Models\BiometricDevice;

interface ApiConnectorInterface
{
    /**
     * Tester la connexion API
     *
     * @param BiometricDevice $device
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(BiometricDevice $device): array;

    /**
     * Synchroniser les données via API (mode Pull)
     *
     * @param BiometricDevice $device
     * @param array $options Options de synchronisation
     * @return array ['success' => bool, 'data' => array, 'message' => string]
     */
    public function pullData(BiometricDevice $device, array $options = []): array;

    /**
     * Traiter les données reçues en Push
     *
     * @param BiometricDevice $device
     * @param array $payload Données reçues
     * @return array ['success' => bool, 'processed' => int, 'message' => string]
     */
    public function processPushData(BiometricDevice $device, array $payload): array;

    /**
     * Valider le payload reçu en Push
     *
     * @param array $payload
     * @param BiometricDevice $device
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePushPayload(array $payload, BiometricDevice $device): array;

    /**
     * Obtenir les headers nécessaires pour l'authentification
     *
     * @param BiometricDevice $device
     * @return array
     */
    public function getAuthHeaders(BiometricDevice $device): array;

    /**
     * Construire l'URL complète avec paramètres
     *
     * @param BiometricDevice $device
     * @param array $params
     * @return string
     */
    public function buildUrl(BiometricDevice $device, array $params = []): string;
} 