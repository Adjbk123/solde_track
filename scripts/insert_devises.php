<?php

/**
 * Script simple pour insérer les devises par défaut
 * Usage: php scripts/insert_devises.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? 'solde_track';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n";
    
    // Vérifier si la table existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'devise'");
    if ($stmt->rowCount() === 0) {
        echo "❌ La table 'devise' n'existe pas. Veuillez d'abord créer la base de données.\n";
        exit(1);
    }
    
    // Lire et exécuter le fichier SQL
    $sqlFile = __DIR__ . '/../database/devises_default.sql';
    if (!file_exists($sqlFile)) {
        echo "❌ Fichier SQL non trouvé : {$sqlFile}\n";
        exit(1);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Diviser les requêtes (séparées par ;)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    $skipped = 0;
    
    foreach ($queries as $query) {
        if (empty($query) || strpos($query, 'INSERT INTO devise') === false) {
            continue;
        }
        
        try {
            $pdo->exec($query);
            $count++;
            echo "✅ Requête exécutée avec succès\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $skipped++;
                echo "⏭️  Devise déjà existante - ignorée\n";
            } else {
                echo "❌ Erreur : " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Vérifier le nombre total de devises
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM devise");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n🎉 Insertion terminée !\n";
    echo "📊 Statistiques :\n";
    echo "   • {$count} requêtes exécutées\n";
    echo "   • {$skipped} devises ignorées (déjà existantes)\n";
    echo "   • Total de devises en base : {$total}\n";
    
    // Afficher quelques devises populaires
    echo "\n💰 Devises populaires disponibles :\n";
    $stmt = $pdo->query("SELECT code, nom FROM devise WHERE code IN ('XOF', 'USD', 'EUR', 'GBP', 'JPY') ORDER BY code");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   • {$row['code']} - {$row['nom']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
    exit(1);
}
