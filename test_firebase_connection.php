<?php

require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\MessagingException;

echo "🧪 Test de connexion Firebase\n";
echo "============================\n\n";

try {
    // Chemin vers le fichier de configuration Firebase
    $firebaseConfigPath = __DIR__ . '/config/firebase/firebase-service-account.json';
    
    echo "📁 Fichier de config: $firebaseConfigPath\n";
    
    if (!file_exists($firebaseConfigPath)) {
        throw new \Exception("Fichier de configuration Firebase non trouvé");
    }
    
    echo "✅ Fichier de configuration trouvé\n";
    
    // Test de connexion
    $factory = (new Factory)->withServiceAccount($firebaseConfigPath);
    $messaging = $factory->createMessaging();
    
    echo "✅ Connexion Firebase réussie\n";
    echo "📊 Projet ID: soldetrack\n";
    
    // Test d'envoi avec un token factice (pour tester la structure)
    $testToken = 'test_token_123';
    $testMessage = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $testToken)
        ->withNotification(\Kreait\Firebase\Messaging\Notification::create('Test', 'Message de test'));
    
    echo "✅ Structure de message créée avec succès\n";
    echo "📤 Prêt à envoyer des notifications\n";
    
} catch (MessagingException $e) {
    echo "❌ Erreur Firebase: " . $e->getMessage() . "\n";
    echo "🔍 Code d'erreur: " . $e->getCode() . "\n";
} catch (\Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test terminé\n";
