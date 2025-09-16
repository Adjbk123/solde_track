<?php

// Test direct de l'API FCM v1
echo "🧪 Test direct de l'API FCM v1\n";
echo "==============================\n\n";

// Configuration FCM
$fcmAccessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJmaXJlYmFzZS1hZG1pbnNkay1mYnN2Y0Bzb2xkZXRyYWNrLmlhbS5nc2VydmljZWFjY291bnQuY29tIiwic2NvcGUiOiJodHRwczpcL1wvd3d3Lmdvb2dsZWFwaXMuY29tXC9hdXRoXC9maXJlYmFzZS5tZXNzYWdpbmciLCJhdWQiOiJodHRwczpcL1wvb2F1dGgyLmdvb2dsZWFwaXMuY29tXC90b2tlbiIsImV4cCI6MTc1ODA0ODI5MCwiaWF0IjoxNzU4MDQ0NjkwfQ.OU6g3wG7rnMOPRXIZwLhv1LV2bNOgpwulXEL_we7I7QL8d4dKTMcj7lK15XEepIqMkI-sYuvU29IuXqg4r9wM9dAjex75WO7GBKj1RhPIga9n2kEN1CLgrmalyISfwhWxZvoOmwPFkWv-dqfLVkbabCdbXZo5X3UY6VPaUwnG251j8uV6RAW81bFANFJ0BAqxf6cafISJWyAIfiOpkpIQOV1nLw0x4G2fOgzXdr5Slaj0Ssd7qrwgfuB0Wx50RqgWwBlY31M7OJiPmHOnnX9Ei-fzjIaPaaJSjIKuKduEwxe03U_Rk6ryxCaOoAnkkemlfH47lvXlRRQaTOP9_A_pA';
$fcmProjectId = 'soldetrack';
$fcmUrl = "https://fcm.googleapis.com/v1/projects/{$fcmProjectId}/messages:send";

// Token FCM de test (factice)
$fcmToken = 'APA91bGHXQBB_test_token_factice_123456789';

// Payload de test
$payload = [
    'message' => [
        'token' => $fcmToken,
        'notification' => [
            'title' => '🧪 Test SoldeTrack',
            'body' => 'Notification de test envoyée avec succès !'
        ],
        'data' => [
            'type' => 'TEST',
            'timestamp' => time()
        ],
        'android' => [
            'notification' => [
                'sound' => 'default',
                'badge' => 1,
                'channel_id' => 'solde_track_notifications',
                'priority' => 'high'
            ],
            'direct_boot_ok' => true
        ]
    ]
];

echo "1️⃣ Configuration FCM :\n";
echo "Project ID: {$fcmProjectId}\n";
echo "URL: {$fcmUrl}\n";
echo "Access Token: " . substr($fcmAccessToken, 0, 50) . "...\n";
echo "FCM Token: {$fcmToken}\n\n";

echo "2️⃣ Envoi de la requête FCM...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fcmUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $fcmAccessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Code HTTP: {$httpCode}\n";
echo "Réponse: {$response}\n";

if ($error) {
    echo "Erreur cURL: {$error}\n";
}

echo "\n3️⃣ Informations de debug :\n";
echo "URL effective: " . $info['url'] . "\n";
echo "Temps de réponse: " . $info['total_time'] . "s\n";
echo "Taille de la requête: " . $info['request_size'] . " bytes\n";
echo "Taille de la réponse: " . $info['size_download'] . " bytes\n";

// Analyse de la réponse
if ($httpCode === 200) {
    echo "\n✅ Notification envoyée avec succès !\n";
} elseif ($httpCode === 400) {
    echo "\n❌ Erreur 400 - Requête malformée\n";
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo "Message d'erreur: " . $errorData['error']['message'] . "\n";
    }
} elseif ($httpCode === 401) {
    echo "\n❌ Erreur 401 - Token d'accès invalide ou expiré\n";
} elseif ($httpCode === 403) {
    echo "\n❌ Erreur 403 - Permission refusée\n";
} else {
    echo "\n❌ Erreur {$httpCode}\n";
}

echo "\n✅ Test terminé !\n";
