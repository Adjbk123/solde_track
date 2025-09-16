<?php

require 'vendor/autoload.php';

use App\Service\PushNotificationService;
use Psr\Log\NullLogger;

echo "🧪 Test du service Symfony PushNotificationService\n";
echo "================================================\n\n";

try {
    // Créer le service
    $logger = new NullLogger();
    $pushService = new PushNotificationService($logger);
    
    // Token FCM fourni par l'utilisateur
    $fcmToken = 'eIX09ivBR_yP0WoxBaSVTC:APA91bFHrJV7oXXRYRmHuZdMlnTr6R1oBgqgmPUz1c3Mh4GugCCQiBiSwmCyPqMUDT6IYbhm9XStUhK7FIjDUulLWvtg5szhui7FWxOjRhpZfs6bp21Bi-c';
    
    echo "🔑 Token FCM: " . substr($fcmToken, 0, 20) . "...\n";
    
    // Test 1: Test de connexion
    echo "\n1️⃣ Test de connexion Firebase...\n";
    $connectionTest = $pushService->testConnection();
    echo "Résultat: " . json_encode($connectionTest, JSON_PRETTY_PRINT) . "\n";
    
    // Test 2: Notification simple
    echo "\n2️⃣ Test de notification simple...\n";
    $result = $pushService->sendNotification(
        $fcmToken,
        '🧪 Test Service Symfony',
        'Notification de test via le service Symfony !',
        [
            'type' => 'test',
            'service' => 'symfony',
            'timestamp' => time()
        ]
    );
    
    if ($result) {
        echo "✅ Notification envoyée avec succès !\n";
    } else {
        echo "❌ Échec de l'envoi de la notification\n";
    }
    
    // Test 3: Notification de motivation
    echo "\n3️⃣ Test de notification de motivation...\n";
    $result = $pushService->sendMotivationNotification(
        $fcmToken,
        'Adjibako',
        'XOF'
    );
    
    if ($result) {
        echo "✅ Notification de motivation envoyée avec succès !\n";
    } else {
        echo "❌ Échec de l'envoi de la notification de motivation\n";
    }
    
    // Test 4: Notification de revenu
    echo "\n4️⃣ Test de notification de revenu...\n";
    $result = $pushService->sendIncomeNotification(
        $fcmToken,
        'Adjibako',
        50000,
        'XOF'
    );
    
    if ($result) {
        echo "✅ Notification de revenu envoyée avec succès !\n";
    } else {
        echo "❌ Échec de l'envoi de la notification de revenu\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🏁 Tests terminés !\n";
