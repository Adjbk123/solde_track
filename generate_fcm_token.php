<?php

// Script pour générer un token d'accès FCM à partir de la clé privée
echo "🔑 Génération du token d'accès FCM\n";
echo "==================================\n\n";

// Configuration
$privateKey = 'aXL0nwLbCNUuDmHujGEIlmFUdNZnHDVQPJY2zEmhNIU'; // Clé fournie par l'utilisateur
$clientEmail = 'firebase-adminsdk-fbsvc@soldetrack.iam.gserviceaccount.com';
$projectId = 'soldetrack';

echo "1️⃣ Configuration :\n";
echo "Project ID: {$projectId}\n";
echo "Client Email: {$clientEmail}\n";
echo "Clé privée: " . substr($privateKey, 0, 20) . "...\n\n";

// Vérifier si la clé privée est complète
if (strlen($privateKey) < 100) {
    echo "❌ ERREUR: La clé privée semble incomplète.\n";
    echo "Une clé privée Firebase complète fait généralement plus de 1000 caractères.\n";
    echo "Veuillez copier la clé complète depuis Firebase Console.\n\n";
    
    echo "📋 Instructions :\n";
    echo "1. Allez sur Firebase Console > Project Settings > Service Accounts\n";
    echo "2. Cliquez sur 'Generate new private key'\n";
    echo "3. Copiez TOUTE la clé privée (incluant -----BEGIN PRIVATE KEY----- et -----END PRIVATE KEY-----)\n";
    echo "4. Remplacez la variable \$privateKey dans ce script\n\n";
    
    exit(1);
}

// Générer le JWT
echo "2️⃣ Génération du JWT...\n";

try {
    // Header
    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];
    
    // Payload
    $now = time();
    $payload = [
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600, // 1 heure
        'iat' => $now
    ];
    
    // Encoder en base64
    $headerEncoded = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));
    
    // Signature
    $signature = '';
    $data = $headerEncoded . '.' . $payloadEncoded;
    
    // Charger la clé privée
    $privateKeyResource = openssl_pkey_get_private($privateKey);
    if (!$privateKeyResource) {
        throw new Exception('Impossible de charger la clé privée: ' . openssl_error_string());
    }
    
    // Signer
    $signatureResult = openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
    if (!$signatureResult) {
        throw new Exception('Erreur lors de la signature: ' . openssl_error_string());
    }
    
    $signatureEncoded = base64url_encode($signature);
    
    // JWT final
    $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    
    echo "✅ JWT généré avec succès !\n";
    echo "Token: " . substr($jwt, 0, 50) . "...\n\n";
    
    // Échanger le JWT contre un token d'accès
    echo "3️⃣ Échange du JWT contre un token d'accès...\n";
    
    $tokenData = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Code HTTP: {$httpCode}\n";
    
    if ($httpCode === 200) {
        $tokenResponse = json_decode($response, true);
        $accessToken = $tokenResponse['access_token'] ?? null;
        
        if ($accessToken) {
            echo "✅ Token d'accès généré avec succès !\n";
            echo "Token: " . substr($accessToken, 0, 50) . "...\n";
            echo "Type: " . ($tokenResponse['token_type'] ?? 'N/A') . "\n";
            echo "Expires in: " . ($tokenResponse['expires_in'] ?? 'N/A') . " secondes\n\n";
            
            echo "4️⃣ Mise à jour de la configuration...\n";
            echo "Copiez ce token dans config/packages/notification.yaml :\n";
            echo "app.fcm_access_token: '{$accessToken}'\n\n";
            
        } else {
            echo "❌ Erreur: Token d'accès non trouvé dans la réponse\n";
            echo "Réponse: {$response}\n";
        }
    } else {
        echo "❌ Erreur lors de l'échange du token\n";
        echo "Réponse: {$response}\n";
        if ($error) {
            echo "Erreur cURL: {$error}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

// Fonction pour encoder en base64 URL-safe
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

echo "\n✅ Script terminé !\n";
