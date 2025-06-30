<?php

// Script de test pour l'API mobile
echo "=== TEST API MOBILE ===\n";

$apiUrl = 'https://apitface.onrender.com/pointages?nameEntreprise=Pop';

echo "URL testée: $apiUrl\n\n";

// Test avec cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";

if ($error) {
    echo "Erreur cURL: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "Erreur HTTP: $httpCode\n";
    echo "Réponse: $response\n";
    exit(1);
}

echo "Réponse brute (200 premiers caractères):\n";
echo substr($response, 0, 200) . "...\n\n";

// Décoder JSON
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Erreur JSON: " . json_last_error_msg() . "\n";
    echo "Réponse complète:\n$response\n";
    exit(1);
}

echo "=== ANALYSE DES DONNÉES ===\n";
echo "Type de données: " . gettype($data) . "\n";

if (is_array($data)) {
    echo "Clés principales: " . implode(', ', array_keys($data)) . "\n";
    
    // Vérifier si c'est dans un wrapper
    if (isset($data['pointages']) && is_array($data['pointages'])) {
        $pointages = $data['pointages'];
        echo "Nombre de pointages: " . count($pointages) . "\n";
        
        if (!empty($pointages)) {
            echo "\n=== PREMIER POINTAGE ===\n";
            $first = $pointages[0];
            foreach ($first as $key => $value) {
                $valueStr = is_array($value) ? json_encode($value) : (string)$value;
                if (strlen($valueStr) > 100) {
                    $valueStr = substr($valueStr, 0, 100) . '...';
                }
                echo "$key: $valueStr\n";
            }
            
            echo "\n=== MAPPING SUGGÉRÉ ===\n";
            $mapped = [
                'employeeId' => $first['id'] ?? 'NON_TROUVÉ',
                'employeeName' => $first['nom'] ?? 'NON_TROUVÉ',
                'timestamp' => $first['date'] ?? 'NON_TROUVÉ',
                'latitude' => $first['latitude'] ?? 'NON_TROUVÉ',
                'longitude' => $first['longitude'] ?? 'NON_TROUVÉ',
                'matchPercentage' => $first['matchPercentage'] ?? 'NON_TROUVÉ',
                'type' => 'entry'
            ];
            
            foreach ($mapped as $key => $value) {
                echo "$key: $value\n";
            }
        }
    } else {
        echo "Structure directe - Nombre d'éléments: " . count($data) . "\n";
        if (!empty($data)) {
            echo "\nPremier élément:\n";
            $first = $data[0];
            foreach ($first as $key => $value) {
                $valueStr = is_array($value) ? json_encode($value) : (string)$value;
                if (strlen($valueStr) > 100) {
                    $valueStr = substr($valueStr, 0, 100) . '...';
                }
                echo "$key: $valueStr\n";
            }
        }
    }
} else {
    echo "Données non-array:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== FIN DU TEST ===\n"; 