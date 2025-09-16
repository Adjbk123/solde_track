<?php

// Test avec le vrai token FCM de l'utilisateur
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
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'error' => $error
    ];
}

echo "🧪 Test avec le vrai token FCM de l'utilisateur\n";
echo "===============================================\n\n";

// Étape 1: Récupérer le token FCM de l'utilisateur
echo "1️⃣ Récupération du token FCM de l'utilisateur...\n";
$result = makeRequest($baseUrl . '/notifications/diagnostic');
echo "Code HTTP: " . $result['code'] . "\n";

if ($result['code'] === 200) {
    $diagnostic = json_decode($result['body'], true);
    $fcmToken = $diagnostic['fcm_token_preview'] ?? 'Token non trouvé';
    echo "Token FCM: " . $fcmToken . "\n";
    echo "Longueur: " . $diagnostic['fcm_token_length'] . " caractères\n\n";
    
    // Étape 2: Test avec le token FCM réel
    echo "2️⃣ Test de notification avec le token FCM réel...\n";
    $testData = [
        'fcm_token' => 'eIX09ivBR_yP0WoxBaSV_test_token_real_123456789' // Token factice pour test
    ];
    $result = makeRequest($baseUrl . '/notifications/test', 'POST', $testData);
    echo "Code HTTP: " . $result['code'] . "\n";
    echo "Réponse: " . $result['body'] . "\n";
    if ($result['error']) {
        echo "Erreur cURL: " . $result['error'] . "\n";
    }
    echo "\n";
    
    // Étape 3: Test de motivation
    echo "3️⃣ Test de notification de motivation...\n";
    $result = makeRequest($baseUrl . '/notifications/motivation', 'POST');
    echo "Code HTTP: " . $result['code'] . "\n";
    echo "Réponse: " . $result['body'] . "\n";
    if ($result['error']) {
        echo "Erreur cURL: " . $result['error'] . "\n";
    }
    echo "\n";
    
} else {
    echo "Erreur lors de la récupération du diagnostic: " . $result['body'] . "\n";
}

echo "✅ Test terminé !\n";
