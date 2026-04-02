<?php
/**
 * Callback pour les paiements MonCash et NatCash
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/payment_config.php';

// Logger les requêtes entrantes pour le debug
$debug = true;
$logDir = __DIR__ . '/../logs';
$logFile = $logDir . '/callback_' . date('Y-m-d') . '.log';

// Créer le dossier logs s'il n'existe pas
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

function logCallback($message) {
    global $logFile, $logDir;
    
    // Écrire seulement si le dossier existe et est accessible
    if (is_dir($logDir) && is_writable($logDir)) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }
}

// Obtenir le corps de la requête
$rawInput = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];

logCallback("=== Nouvelle requête callback ===");
logCallback("Méthode: " . $_SERVER['REQUEST_METHOD']);
logCallback("Headers: " . json_encode($headers));
logCallback("Body: " . $rawInput);

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Identifier la passerelle
$gateway = $_GET['gateway'] ?? null;
if (!$gateway) {
    // Essayer de détecter depuis les headers
    if (isset($headers['X-MonCash-Signature'])) {
        $gateway = 'moncash';
    } elseif (isset($headers['X-NatCash-Signature'])) {
        $gateway = 'natcash';
    }
}

logCallback("Gateway: " . $gateway);

// Parser les données
$data = json_decode($rawInput, true);
if (!$data) {
    // Peut-être des données de formulaire
    $data = $_POST;
}

$db = getDB();

try {
    switch (strtolower($gateway)) {
        case 'moncash':
            handleMonCashCallback($data, $db);
            break;
            
        case 'natcash':
            handleNatCashCallback($data, $db);
            break;
            
        default:
            logCallback("Passerelle non reconnue");
            http_response_code(400);
            echo json_encode(['error' => 'Unknown gateway']);
            exit;
    }
    
    // Réponse de succès
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    
} catch (Exception $e) {
    logCallback("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Traiter le callback MonCash
 */
function handleMonCashCallback($data, $db) {
    global $debug;
    
    logCallback("Traitement callback MonCash: " . json_encode($data));
    
    // Extraire les informations
    $transactionId = $data['transaction_id'] ?? $data['transactionId'] ?? null;
    $reference = $data['orderId'] ?? $data['order_id'] ?? null;
    $status = $data['status'] ?? null;
    $amount = $data['amount'] ?? 0;
    $phoneNumber = $data['phoneNumber'] ?? $data['payer_phone'] ?? null;
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    if (!$transactionId) {
        throw new Exception("ID de transaction manquant");
    }
    
    // Mapper le statut
    $statusMap = [
        'successful' => 'Confirmée',
        'completed' => 'Confirmée',
        'pending' => 'En attente',
        'failed' => 'Échouée',
        'cancelled' => 'Annulée'
    ];
    $mappedStatus = $statusMap[$status] ?? 'En attente';
    
    // Mettre à jour la transaction
    $stmt = $db->prepare("UPDATE Transactions_Mobile 
                          SET statut = ?, date_confirmation = NOW(), reponse_api = ? 
                          WHERE reference_externe = ?");
    $stmt->execute([$mappedStatus, json_encode($data), $transactionId]);
    
    // Trouver le paiement associé
    $stmt = $db->prepare("SELECT p.*, c.id_reservation 
                          FROM Transactions_Mobile tm 
                          JOIN Paiements p ON tm.id_paiement = p.id_paiement 
                          JOIN Contrats c ON p.id_contrat = c.id_contrat
                          WHERE tm.reference_externe = ?");
    $stmt->execute([$transactionId]);
    $paiement = $stmt->fetch();
    
    if ($paiement) {
        // Mettre à jour le paiement
        $stmt = $db->prepare("UPDATE Paiements 
                              SET statut_transaction = ?, recu = ?, date_confirmation = NOW() 
                              WHERE id_paiement = ?");
        $stmt->execute([
            $mappedStatus, 
            $mappedStatus === 'Confirmée' ? 1 : 0, 
            $paiement['id_paiement']
        ]);
        
        // Si confirmé, mettre à jour la réservation
        if ($mappedStatus === 'Confirmée') {
            $stmt = $db->prepare("UPDATE Reservations SET statut = 'Confirmée' WHERE id_reservation = ?");
            $stmt->execute([$paiement['id_reservation']]);
            
            logCallback("Réservation #{$paiement['id_reservation']} confirmée");
        }
    }
    
    logCallback("Callback MonCash traité avec succès");
}

/**
 * Traiter le callback NatCash
 */
function handleNatCashCallback($data, $db) {
    logCallback("Traitement callback NatCash: " . json_encode($data));
    
    // Extraire les informations
    $transactionId = $data['transaction_id'] ?? $data['payment_id'] ?? null;
    $reference = $data['order_id'] ?? $data['reference'] ?? null;
    $status = $data['status'] ?? null;
    $amount = $data['amount'] ?? 0;
    $phoneNumber = $data['phone_number'] ?? $data['payer_phone'] ?? null;
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    if (!$transactionId) {
        throw new Exception("ID de transaction manquant");
    }
    
    // Mapper le statut
    $statusMap = [
        'SUCCESS' => 'Confirmée',
        'COMPLETED' => 'Confirmée',
        'PENDING' => 'En attente',
        'FAILED' => 'Échouée',
        'CANCELLED' => 'Annulée'
    ];
    $mappedStatus = $statusMap[$status] ?? 'En attente';
    
    // Mettre à jour la transaction
    $stmt = $db->prepare("UPDATE Transactions_Mobile 
                          SET statut = ?, date_confirmation = NOW(), reponse_api = ? 
                          WHERE reference_externe = ?");
    $stmt->execute([$mappedStatus, json_encode($data), $transactionId]);
    
    // Trouver le paiement associé
    $stmt = $db->prepare("SELECT p.*, c.id_reservation 
                          FROM Transactions_Mobile tm 
                          JOIN Paiements p ON tm.id_paiement = p.id_paiement 
                          JOIN Contrats c ON p.id_contrat = c.id_contrat
                          WHERE tm.reference_externe = ?");
    $stmt->execute([$transactionId]);
    $paiement = $stmt->fetch();
    
    if ($paiement) {
        // Mettre à jour le paiement
        $stmt = $db->prepare("UPDATE Paiements 
                              SET statut_transaction = ?, recu = ?, date_confirmation = NOW() 
                              WHERE id_paiement = ?");
        $stmt->execute([
            $mappedStatus, 
            $mappedStatus === 'Confirmée' ? 1 : 0, 
            $paiement['id_paiement']
        ]);
        
        // Si confirmé, mettre à jour la réservation
        if ($mappedStatus === 'Confirmée') {
            $stmt = $db->prepare("UPDATE Reservations SET statut = 'Confirmée' WHERE id_reservation = ?");
            $stmt->execute([$paiement['id_reservation']]);
            
            logCallback("Réservation #{$paiement['id_reservation']} confirmée");
        }
    }
    
    logCallback("Callback NatCash traité avec succès");
}
?>
