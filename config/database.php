<?php
// 1. Charger les variables d'environnement
// __DIR__ est C:\wamp64\www\tradecenter\config
// On ajoute /.. pour remonter à C:\wamp64\www\tradecenter
require_once __DIR__ . '/../vendor/autoload.php';

 $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
 $dotenv->load();

// 2. Récupérer les configs
 $host = $_ENV['DB_HOST'] ?? 'localhost';
 $dbname = $_ENV['DB_NAME'] ?? 'tradecenter_enterprise';
 $user = $_ENV['DB_USER'] ?? 'root';
 $pass = $_ENV['DB_PASS'] ?? '';

// Définir BASE_URL si utilisé ailleurs
if (!defined('BASE_URL')) {
    define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost/tradecenter');
}

// ... Le reste de votre code (fonction getDB, etc.) reste identique ...
/**
 * Fonction de connexion à la base de données
 */
function getDB() {
    global $host, $dbname, $user, $pass;
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, $user, $pass, $options);
            
        } catch (PDOException $e) {
            // Log l'erreur mais ne pas afficher les détails en production
            error_log("Erreur DB: " . $e->getMessage());
            die("Impossible de se connecter à la base de données.");
        }
    }
    
    return $pdo;
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>