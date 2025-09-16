<?php

require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;

echo "🧪 Test Firebase avec le vrai token FCM\n";
echo "=======================================\n\n";

try {
    // Configuration Firebase
    $firebaseConfigPath = __DIR__ . '/config/firebase/firebase-service-account.json';
    $factory = (new Factory)->withServiceAccount($firebaseConfigPath);
    $messaging = $factory->createMessaging();
    
    // Token FCM fourni par l'utilisateur
    $fcmToken = 'eIX09ivBR_yP0WoxBaSVTC:APA91bFHrJV7oXXRYRmHuZdMlnTr6R1oBgqgmPUz1c3Mh4GugCCQiBiSwmCyPqMUDT6IYbhm9XStUhK7FIjDUulLWvtg5szhui7FWxOjRhpZfs6bp21Bi-c';
    echo "🔑 Token FCM: " . substr($fcmToken, 0, 20) . "...\n";
    
    // Créer et envoyer la notification
    $notification = Notification::create(
        '🎉 Test SoldeTrack',
        'Notification de test avec la nouvelle implémentation Firebase !'
    );
    
    $message = CloudMessage::withTarget('token', $fcmToken)
        ->withNotification($notification)
        ->withData([
            'type' => 'test',
            'timestamp' => time(),
            'service' => 'kreait/firebase-php',
            'version' => '2.0'
        ]);
    
    echo "📤 Envoi de la notification...\n";
    $messaging->send($message);
    
    echo "✅ Notification envoyée avec succès !\n";
    echo "🎉 La nouvelle implémentation Firebase fonctionne parfaitement !\n";
    echo "📱 Vérifiez votre appareil pour voir la notification\n";
    
} catch (MessagingException $e) {
    echo "❌ Erreur Firebase: " . $e->getMessage() . "\n";
    echo "🔍 Code d'erreur: " . $e->getCode() . "\n";
    
    if ($e->getCode() === 400) {
        echo "💡 Le token FCM semble invalide ou expiré\n";
    } elseif ($e->getCode() === 401) {
        echo "💡 Problème d'authentification Firebase\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test terminé\n";
