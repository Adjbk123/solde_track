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
    
    // Récupérer le token FCM de l'utilisateur depuis la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=solde_track', 'root', '');
    $stmt = $pdo->prepare("SELECT fcm_token FROM user WHERE id = 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['fcm_token']) {
        throw new \Exception("Token FCM non trouvé pour l'utilisateur");
    }
    
    $fcmToken = $user['fcm_token'];
    echo "✅ Token FCM récupéré: " . substr($fcmToken, 0, 20) . "...\n";
    
    // Créer et envoyer la notification
    $notification = Notification::create(
        '🧪 Test Firebase',
        'Notification de test avec kreait/firebase-php !'
    );
    
    $message = CloudMessage::withTarget('token', $fcmToken)
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
        echo "💡 Le token FCM semble invalide ou expiré\n";
    } elseif ($e->getCode() === 401) {
        echo "💡 Problème d'authentification Firebase\n";
    }
} catch (\Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test terminé\n";
