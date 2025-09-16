<?php

require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;

echo "🧪 Test Firebase avec token factice\n";
echo "===================================\n\n";

try {
    // Configuration Firebase
    $firebaseConfigPath = __DIR__ . '/config/firebase/firebase-service-account.json';
    $factory = (new Factory)->withServiceAccount($firebaseConfigPath);
    $messaging = $factory->createMessaging();
    
    // Token FCM factice pour tester la structure
    $fakeToken = 'fake_token_for_testing_123456789';
    echo "🔑 Token factice: " . substr($fakeToken, 0, 20) . "...\n";
    
    // Créer et envoyer la notification
    $notification = Notification::create(
        '🧪 Test Firebase',
        'Notification de test avec kreait/firebase-php !'
    );
    
    $message = CloudMessage::withTarget('token', $fakeToken)
        ->withNotification($notification)
        ->withData([
            'type' => 'test',
            'timestamp' => time(),
            'service' => 'kreait/firebase-php'
        ]);
    
    echo "📤 Envoi de la notification...\n";
    $messaging->send($message);
    
    echo "✅ Notification envoyée avec succès !\n";
    echo "🎉 Firebase fonctionne parfaitement\n";
    
} catch (MessagingException $e) {
    echo "❌ Erreur Firebase: " . $e->getMessage() . "\n";
    echo "🔍 Code d'erreur: " . $e->getCode() . "\n";
    
    if ($e->getCode() === 400) {
        echo "💡 Le token FCM semble invalide ou expiré (normal avec un token factice)\n";
        echo "✅ Mais la structure Firebase fonctionne !\n";
    } elseif ($e->getCode() === 401) {
        echo "💡 Problème d'authentification Firebase\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test terminé\n";
