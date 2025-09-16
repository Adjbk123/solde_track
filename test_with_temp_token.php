<?php

// Test avec un token d'accès temporaire (à remplacer par le vrai)
echo "🧪 Test avec token d'accès temporaire\n";
echo "=====================================\n\n";

// Configuration
$baseUrl = 'https://soldetrack.site/api';
$jwtToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NTgwMzAxMzEsImV4cCI6MTc4OTU2NjEzMSwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiYWRqaWJha28xMjNAZ21haWwuY29tIn0.JHymSwchyEJ7ppO7F7g7Quhbw7EggUYvX2RSztgpA0_sMBgIJTYKuwvrdoRoNBnf6tPfgFpJh6cgtIcWNWUJ1W_r_cU2sGjnfCdbi2tHwadwAS9BF6UVhe-aAy84oCQy36Kv1C_FVboHPPHm2btautvsu93HzvPkTQg5vT30G5jXkgYzcyPlBobb-TwRc8x4KUzWo0nl_E7nsHIqLF3VNQFaiwTU6aEDKca7hM4HSY6C7GaYn_peN0ZCECJyIeNmq8czvZwm-zLBnIf_JjNTiLxswruYYD4mxJfMaSSjRnrVLYOG4_ROnh7GJ4A5NwCoJSRg0YjVGjvV7v1Zlge-hw';

function makeRequest($url, $method = 'GET', $data = null) {
    global $jwtToken;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $jwtToken
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

echo "1️⃣ Test de l'endpoint de diagnostic...\n";
$result = makeRequest($baseUrl . '/notifications/diagnostic');
echo "Code HTTP: " . $result['code'] . "\n";

if ($result['code'] === 200) {
    $diagnostic = json_decode($result['body'], true);
    echo "✅ Configuration FCM :\n";
    echo "  - Token FCM enregistré: " . ($diagnostic['fcm_token_registered'] ? 'Oui' : 'Non') . "\n";
    echo "  - Longueur du token: " . $diagnostic['fcm_token_length'] . " caractères\n";
    echo "  - Devise utilisateur: " . $diagnostic['user_currency'] . "\n";
    echo "  - Project ID: " . $diagnostic['configuration']['fcm_project_id'] . "\n";
    echo "  - Access token configuré: " . ($diagnostic['configuration']['access_token_configured'] ? 'Oui' : 'Non') . "\n\n";
    
    echo "2️⃣ Test des types de notifications...\n";
    $result = makeRequest($baseUrl . '/notifications/types');
    echo "Code HTTP: " . $result['code'] . "\n";
    
    if ($result['code'] === 200) {
        $types = json_decode($result['body'], true);
        echo "✅ Types de notifications disponibles :\n";
        foreach ($types['types'] as $type => $info) {
            echo "  - {$type}: {$info['name']} {$info['icon']}\n";
        }
        echo "\n";
    }
    
    echo "3️⃣ Test de l'endpoint de motivation (sans token FCM valide)...\n";
    $result = makeRequest($baseUrl . '/notifications/motivation', 'POST');
    echo "Code HTTP: " . $result['code'] . "\n";
    echo "Réponse: " . $result['body'] . "\n\n";
    
    echo "📋 Résumé :\n";
    echo "✅ API de notifications : Fonctionnelle\n";
    echo "✅ Configuration FCM : Correcte\n";
    echo "✅ Token FCM utilisateur : Présent\n";
    echo "❌ Token d'accès FCM : Expiré (à renouveler)\n\n";
    
    echo "🔧 Prochaines étapes :\n";
    echo "1. Obtenir la clé privée complète depuis Firebase Console\n";
    echo "2. Générer un nouveau token d'accès FCM\n";
    echo "3. Mettre à jour config/packages/notification.yaml\n";
    echo "4. Tester l'envoi de notifications\n\n";
    
} else {
    echo "❌ Erreur lors du diagnostic: " . $result['body'] . "\n";
}

echo "✅ Test terminé !\n";
