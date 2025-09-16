<?php

echo "🧪 Test de l'API avec l'URL correcte\n";
echo "===================================\n\n";

$baseUrl = 'http://localhost/solde-track-api/public';
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NTgwMzAxMzEsImV4cCI6MTc4OTU2NjEzMSwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiYWRqaWJha28xMjNAZ21haWwuY29tIn0.JHymSwchyEJ7ppO7F7g7Quhbw7EggUYvX2RSztgpA0_sMBgIJTYKuwvrdoRoNBnf6tPfgFpJh6cgtIcWNWUJ1W_r_cU2sGjnfCdbi2tHwadwAS9BF6UVhe-aAy84oCQy36Kv1C_FVboHPPHm2btautvsu93HzvPkTQg5vT30G5jXkgYzcyPlBobb-TwRc8x4KUzWo0nl_E7nsHIqLF3VNQFaiwTU6aEDKca7hM4HSY6C7GaYn_peN0ZCECJyIeNmq8czvZwm-zLBnIf_JjNTiLxswruYYD4mxJfMaSSjRnrVLYOG4_ROnh7GJ4A5NwCoJSRg0YjVGjvV7v1Zlge-hw';
$fcmToken = 'eIX09ivBR_yP0WoxBaSVTC:APA91bFHrJV7oXXRYRmHuZdMlnTr6R1oBgqgmPUz1c3Mh4GugCCQiBiSwmCyPqMUDT6IYbhm9XStUhK7FIjDUulLWvtg5szhui7FWxOjRhpZfs6bp21Bi-c';

// Test 1: Diagnostic
echo "1️⃣ Test de l'endpoint de diagnostic...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/notifications/diagnostic');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: $response\n\n";

// Test 2: Test de notification
echo "2️⃣ Test de l'endpoint de test avec le vrai token FCM...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/notifications/test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'fcm_token' => $fcmToken,
    'title' => '🧪 Test API Firebase',
    'body' => 'Test via l\'API avec la nouvelle implémentation !'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
echo "Réponse: $response\n\n";

echo "✅ Tests terminés !\n";
