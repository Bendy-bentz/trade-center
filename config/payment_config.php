<?php
// 1. Charger les variables d'environnement si ce n'est pas déjà fait
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// 2. Définir BASE_URL (avec la protection)
if (!defined('BASE_URL')) {
    define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost/tradecenter');
}

// ... Le reste de votre fichier (MonCash, NatCash, etc.)

// Configuration MonCash
define('MONCASH_CLIENT_ID', $_ENV['MONCASH_CLIENT_ID'] ?? '');
define('MONCASH_CLIENT_SECRET', $_ENV['MONCASH_CLIENT_SECRET'] ?? '');
define('MONCASH_SANDBOX', filter_var($_ENV['MONCASH_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('MONCASH_API_URL', MONCASH_SANDBOX 
    ? 'https://moncashbutton.digicelgroup.com/Moncash-middleware/rs/v1'
    : 'https://moncashbutton.digicelgroup.com/Moncash-middleware/rs/v1');

// Configuration NatCash
define('NATCASH_MERCHANT_ID', $_ENV['NATCASH_MERCHANT_ID'] ?? '');
define('NATCASH_API_KEY', $_ENV['NATCASH_API_KEY'] ?? '');
define('NATCASH_SANDBOX', filter_var($_ENV['NATCASH_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('NATCASH_API_URL', NATCASH_SANDBOX 
    ? 'https://sandbox.api.natcash.ht/v1'
    : 'https://api.natcash.ht/v1');

// Configuration Stripe (Récupérée depuis .env)
define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');

// Configuration générale des paiements
define('PAYMENT_CURRENCY', 'USD');
define('PAYMENT_TIMEOUT', 1800); // 30 minutes
// Assurez-vous que BASE_URL est défini quelque part avant ce fichier
define('PAYMENT_CALLBACK_URL', BASE_URL . '/paiements/callback.php');
define('PAYMENT_RETURN_URL', BASE_URL . '/client/reservations.php');

// Mode simulation (TRUE = test sans vraie API, FALSE = utilisation réelle de l'API)
define('PAYMENT_SIMULATION_MODE', true);

/**
 * Vérifier si on est en mode simulation
 */
function isSimulationMode() {
    // Mode simulation si les identifiants sont vides OU si PAYMENT_SIMULATION_MODE est TRUE
    $moncashNotConfigured = (empty(MONCASH_CLIENT_ID) || empty(MONCASH_CLIENT_SECRET));
    $natcashNotConfigured = (empty(NATCASH_MERCHANT_ID) || empty(NATCASH_API_KEY));
    
    return PAYMENT_SIMULATION_MODE || ($moncashNotConfigured && $natcashNotConfigured);
}

// ... LE RESTE DE VOTRE CODE PHP (LES CLASSES) RESTE IDENTIQUE ...
// (Je n'ai pas recopié toutes les classes ici pour aller plus vite, 
// mais ne supprimez pas les classes MonCashGateway, NatCashGateway, etc. 
// Gardez-les telles quelles.)

/**
 * Classe abstraite pour les passerelles de paiement
 */
abstract class PaymentGateway {
    protected $config;
    protected $db;
    protected $simulationMode;
    
    public function __construct($config = []) {
        $this->config = $config;
        $this->db = getDB();
        $this->simulationMode = isSimulationMode();
    }
    
    abstract public function createPayment($amount, $phoneNumber, $orderId, $description = '');
    abstract public function verifyPayment($transactionId);
    abstract public function getPaymentStatus($transactionId);
    abstract public function refund($transactionId, $amount = null);
    
    protected function log($message, $level = 'info') {
        $logDir = __DIR__ . '/../logs';
        $logFile = $logDir . '/payment_' . date('Y-m-d') . '.log';
        $logMessage = date('Y-m-d H:i:s') . " [{$level}] " . get_class($this) . ": {$message}\n";
        
        // Créer le dossier logs s'il n'existe pas
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Écrire dans le fichier seulement si le dossier existe et est accessible
        if (is_dir($logDir) && is_writable($logDir)) {
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
    
    /**
     * Générer un ID de transaction simulé
     */
    protected function generateSimulationTransactionId() {
        return 'SIM_' . strtoupper(uniqid()) . '_' . time();
    }
    
    protected function saveTransaction($data) {
        $stmt = $this->db->prepare("INSERT INTO Transactions_Mobile 
            (id_paiement, fournisseur, reference_externe, numero_telephone, montant, statut, reponse_api) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['id_paiement'],
            $data['fournisseur'],
            $data['reference_externe'],
            $data['numero_telephone'],
            $data['montant'],
            $data['statut'],
            json_encode($data['reponse_api'] ?? null)
        ]);
        return $this->db->lastInsertId();
    }
    
    protected function updateTransactionStatus($referenceExterne, $statut, $reponseApi = null) {
        $stmt = $this->db->prepare("UPDATE Transactions_Mobile 
            SET statut = ?, date_confirmation = NOW(), reponse_api = ? 
            WHERE reference_externe = ?");
        return $stmt->execute([$statut, json_encode($reponseApi), $referenceExterne]);
    }
}

/**
 * Classe MonCash - Digicel Haïti
 */
class MonCashGateway extends PaymentGateway {
    private $accessToken;
    
    public function __construct() {
        parent::__construct([
            'client_id' => MONCASH_CLIENT_ID,
            'client_secret' => MONCASH_CLIENT_SECRET,
            'api_url' => MONCASH_API_URL,
            'sandbox' => MONCASH_SANDBOX
        ]);
    }
    
    /**
     * Obtenir le token d'accès OAuth
     */
    private function getAccessToken() {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        // Mode simulation - retourner un token fictif
        if ($this->simulationMode) {
            $this->accessToken = 'sim_token_' . uniqid();
            return $this->accessToken;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']),
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $this->accessToken = $data['access_token'];
            return $this->accessToken;
        }
        
        $this->log("Erreur d'authentification MonCash: {$response}", 'error');
        return null;
    }
    
    /**
     * Créer un paiement MonCash
     */
    public function createPayment($amount, $phoneNumber, $orderId, $description = '') {
        // Nettoyer le numéro de téléphone
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Mode simulation
        if ($this->simulationMode) {
            $transactionId = $this->generateSimulationTransactionId();
            
            $this->log("SIMULATION - Création paiement MonCash: orderId={$orderId}, amount={$amount}, phone={$phoneNumber}");
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'reference' => $orderId,
                'payment_url' => null,
                'message' => 'Paiement initié avec succès (MODE SIMULATION)',
                'simulation' => true
            ];
        }
        
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Erreur d\'authentification MonCash'];
        }
        
        $payload = [
            'amount' => (int)($amount * 100), // MonCash utilise les centimes
            'phoneNumber' => $phoneNumber,
            'orderId' => $orderId,
            'description' => $description ?: "Paiement Tradecenter #{$orderId}"
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/create-transaction',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->log("Création paiement MonCash: orderId={$orderId}, response={$response}");
        
        if ($httpCode === 200 || $httpCode === 201) {
            $data = json_decode($response, true);
            
            return [
                'success' => true,
                'transaction_id' => $data['transaction_id'] ?? $data['payment_token'] ?? null,
                'reference' => $data['reference'] ?? $orderId,
                'payment_url' => $data['payment_url'] ?? null,
                'message' => 'Paiement initié avec succès'
            ];
        }
        
        $errorData = json_decode($response, true);
        return [
            'success' => false,
            'error' => $errorData['message'] ?? 'Erreur lors de la création du paiement',
            'code' => $httpCode
        ];
    }
    
    /**
     * Vérifier un paiement MonCash
     */
    public function verifyPayment($transactionId) {
        // Mode simulation
        if ($this->simulationMode) {
            $this->log("SIMULATION - Vérification paiement MonCash: {$transactionId}");
            
            // Simuler un paiement confirmé
            return [
                'success' => true,
                'status' => 'Confirmée',
                'amount' => 0,
                'transaction_id' => $transactionId,
                'reference' => null,
                'payer_phone' => null,
                'timestamp' => date('Y-m-d H:i:s'),
                'raw_data' => ['simulation' => true],
                'simulation' => true
            ];
        }
        
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Erreur d\'authentification MonCash'];
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/retrieve-transaction/' . $transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->log("Vérification paiement MonCash: transactionId={$transactionId}, response={$response}");
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            return [
                'success' => true,
                'status' => $this->mapStatus($data['status'] ?? 'pending'),
                'amount' => ($data['amount'] ?? 0) / 100,
                'transaction_id' => $data['transaction_id'] ?? $transactionId,
                'reference' => $data['reference'] ?? null,
                'payer_phone' => $data['payer_phone'] ?? null,
                'timestamp' => $data['timestamp'] ?? null,
                'raw_data' => $data
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Transaction non trouvée',
            'code' => $httpCode
        ];
    }
    
    /**
     * Obtenir le statut d'un paiement
     */
    public function getPaymentStatus($transactionId) {
        $result = $this->verifyPayment($transactionId);
        return $result['status'] ?? 'unknown';
    }
    
    /**
     * Rembourser un paiement
     */
    public function refund($transactionId, $amount = null) {
        // Mode simulation
        if ($this->simulationMode) {
            $this->log("SIMULATION - Remboursement MonCash: {$transactionId}");
            return ['success' => true, 'message' => 'Remboursement simulé effectué'];
        }
        
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Erreur d\'authentification MonCash'];
        }
        
        $payload = ['transactionId' => $transactionId];
        if ($amount) {
            $payload['amount'] = (int)($amount * 100);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/refund-transaction',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            return ['success' => true, 'message' => 'Remboursement effectué'];
        }
        
        return ['success' => false, 'error' => 'Erreur lors du remboursement'];
    }
    
    /**
     * Mapper les statuts MonCash vers nos statuts internes
     */
    private function mapStatus($moncashStatus) {
        $statusMap = [
            'successful' => 'Confirmée',
            'completed' => 'Confirmée',
            'pending' => 'En attente',
            'failed' => 'Échouée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée'
        ];
        return $statusMap[$moncashStatus] ?? 'En attente';
    }
}

/**
 * Classe NatCash - National Bank Haïti
 */
class NatCashGateway extends PaymentGateway {
    
    public function __construct() {
        parent::__construct([
            'merchant_id' => NATCASH_MERCHANT_ID,
            'api_key' => NATCASH_API_KEY,
            'api_url' => NATCASH_API_URL,
            'sandbox' => NATCASH_SANDBOX
        ]);
    }
    
    /**
     * Créer un paiement NatCash
     */
    public function createPayment($amount, $phoneNumber, $orderId, $description = '') {
        // Nettoyer le numéro de téléphone
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Mode simulation
        if ($this->simulationMode) {
            $transactionId = $this->generateSimulationTransactionId();
            
            $this->log("SIMULATION - Création paiement NatCash: orderId={$orderId}, amount={$amount}, phone={$phoneNumber}");
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'reference' => $orderId,
                'payment_url' => null,
                'message' => 'Paiement initié avec succès (MODE SIMULATION)',
                'simulation' => true
            ];
        }
        
        $payload = [
            'merchant_id' => $this->config['merchant_id'],
            'amount' => $amount,
            'currency' => PAYMENT_CURRENCY,
            'phone_number' => $phoneNumber,
            'order_id' => $orderId,
            'description' => $description ?: "Paiement Tradecenter #{$orderId}",
            'callback_url' => PAYMENT_CALLBACK_URL . '?gateway=natcash',
            'return_url' => PAYMENT_RETURN_URL . '?order=' . $orderId
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/payment/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->config['api_key'],
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->log("Création paiement NatCash: orderId={$orderId}, response={$response}");
        
        if ($httpCode === 200 || $httpCode === 201) {
            $data = json_decode($response, true);
            
            return [
                'success' => true,
                'transaction_id' => $data['transaction_id'] ?? $data['payment_id'] ?? null,
                'reference' => $data['reference'] ?? $orderId,
                'payment_url' => $data['payment_url'] ?? null,
                'message' => 'Paiement initié avec succès'
            ];
        }
        
        $errorData = json_decode($response, true);
        return [
            'success' => false,
            'error' => $errorData['message'] ?? 'Erreur lors de la création du paiement',
            'code' => $httpCode
        ];
    }
    
    /**
     * Vérifier un paiement NatCash
     */
    public function verifyPayment($transactionId) {
        // Mode simulation
        if ($this->simulationMode) {
            $this->log("SIMULATION - Vérification paiement NatCash: {$transactionId}");
            
            // Simuler un paiement confirmé
            return [
                'success' => true,
                'status' => 'Confirmée',
                'amount' => 0,
                'transaction_id' => $transactionId,
                'reference' => null,
                'payer_phone' => null,
                'timestamp' => date('Y-m-d H:i:s'),
                'raw_data' => ['simulation' => true],
                'simulation' => true
            ];
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/payment/status/' . $transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->config['api_key'],
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->log("Vérification paiement NatCash: transactionId={$transactionId}, response={$response}");
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            return [
                'success' => true,
                'status' => $this->mapStatus($data['status'] ?? 'pending'),
                'amount' => $data['amount'] ?? 0,
                'transaction_id' => $data['transaction_id'] ?? $transactionId,
                'reference' => $data['reference'] ?? null,
                'payer_phone' => $data['payer_phone'] ?? null,
                'timestamp' => $data['timestamp'] ?? null,
                'raw_data' => $data
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Transaction non trouvée',
            'code' => $httpCode
        ];
    }
    
    /**
     * Obtenir le statut d'un paiement
     */
    public function getPaymentStatus($transactionId) {
        $result = $this->verifyPayment($transactionId);
        return $result['status'] ?? 'unknown';
    }
    
    /**
     * Rembourser un paiement NatCash
     */
    public function refund($transactionId, $amount = null) {
        // Mode simulation
        if ($this->simulationMode) {
            $this->log("SIMULATION - Remboursement NatCash: {$transactionId}");
            return ['success' => true, 'message' => 'Remboursement simulé effectué'];
        }
        
        $payload = ['transaction_id' => $transactionId];
        if ($amount) {
            $payload['amount'] = $amount;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->config['api_url'] . '/payment/refund',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->config['api_key'],
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            return ['success' => true, 'message' => 'Remboursement effectué'];
        }
        
        return ['success' => false, 'error' => 'Erreur lors du remboursement'];
    }
    
    /**
     * Mapper les statuts NatCash vers nos statuts internes
     */
    private function mapStatus($natcashStatus) {
        $statusMap = [
            'SUCCESS' => 'Confirmée',
            'COMPLETED' => 'Confirmée',
            'PENDING' => 'En attente',
            'FAILED' => 'Échouée',
            'CANCELLED' => 'Annulée',
            'REFUNDED' => 'Remboursée'
        ];
        return $statusMap[$natcashStatus] ?? 'En attente';
    }
}

/**
 * Factory pour créer les passerelles de paiement
 */
class PaymentGatewayFactory {
    
    public static function create($gateway) {
        switch (strtolower($gateway)) {
            case 'moncash':
                return new MonCashGateway();
            case 'natcash':
                return new NatCashGateway();
            default:
                throw new Exception("Passerelle de paiement non supportée: {$gateway}");
        }
    }
}

/**
 * Fonction utilitaire pour obtenir les méthodes de paiement actives
 */
function getActivePaymentMethods() {
    $db = getDB();
    return $db->query("SELECT * FROM Methodes_Paiement WHERE actif = TRUE ORDER BY ordre, nom")->fetchAll();
}


