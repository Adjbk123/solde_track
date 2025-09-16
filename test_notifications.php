<?php

// Script de test pour les notifications FCM
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

echo "🧪 Test des endpoints de notifications FCM\n";
echo "==========================================\n\n";

// Test 1: Diagnostic
echo "1️⃣ Test de l'endpoint de diagnostic...\n";
$result = makeRequest($baseUrl . '/notifications/diagnostic');
echo "Code HTTP: " . $result['code'] . "\n";
echo "Réponse: " . $result['body'] . "\n\n";

// Test 2: Test de notification avec token FCM factice
echo "2️⃣ Test de l'endpoint de test avec token FCM factice...\n";
$testData = [
    'fcm_token' => 'APA91bGHXQBB_test_token_factice_123456789'
];
$result = makeRequest($baseUrl . '/notifications/test', 'POST', $testData);
echo "Code HTTP: " . $result['code'] . "\n";
echo "Réponse: " . $result['body'] . "\n\n";

// Test 3: Test de motivation (sans token FCM)
echo "3️⃣ Test de l'endpoint de motivation (sans token FCM)...\n";
$result = makeRequest($baseUrl . '/notifications/motivation', 'POST');
echo "Code HTTP: " . $result['code'] . "\n";
echo "Réponse: " . $result['body'] . "\n\n";

// Test 4: Types de notifications
echo "4️⃣ Test de l'endpoint des types de notifications...\n";
$result = makeRequest($baseUrl . '/notifications/types');
echo "Code HTTP: " . $result['code'] . "\n";
echo "Réponse: " . $result['body'] . "\n\n";

echo "✅ Tests terminés !\n";
