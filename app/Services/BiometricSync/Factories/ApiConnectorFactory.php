<?php

namespace App\Services\BiometricSync\Factories;

use App\Services\BiometricSync\Contracts\ApiConnectorInterface;
use App\Services\BiometricSync\Connectors\RestApiConnector;
use App\Services\BiometricSync\Connectors\WebhookConnector;

class ApiConnectorFactory
{
    /**
     * Créer un connecteur API
     *
     * @param string $type Type de connecteur (rest, webhook)
     * @return ApiConnectorInterface
     */
    public function create(string $type = 'rest'): ApiConnectorInterface
    {
        return match($type) {
            'webhook' => new WebhookConnector(),
            'rest', 'default' => new RestApiConnector()
        };
    }

    /**
     * Obtenir les connecteurs disponibles
     *
     * @return array
     */
    public function getAvailableConnectors(): array
    {
        return [
            'rest' => [
                'name' => 'REST API',
                'class' => RestApiConnector::class,
                'description' => 'Connexion via API REST standard'
            ],
            'webhook' => [
                'name' => 'Webhook',
                'class' => WebhookConnector::class,
                'description' => 'Réception de données via webhook'
            ]
        ];
    }
} 