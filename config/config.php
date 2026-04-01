<?php
// METTEZ ÇA :
//define('BASE_URL', 'https://homogamous-shadily-margene.ngrok-free.dev/tradecenter');

// (Gardez l'ancien en commentaire pour le remettre quand vous arrêterez Ngrok)
 define('BASE_URL', 'http://localhost/tradecenter');
// Nom de l'application
define('APP_NAME', 'TradecenterEntreprise');

// Timezone
date_default_timezone_set('America/Port-au-Prince');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la connexion à la base de données
require_once __DIR__ . '/database.php';

// ==================== FONCTIONS UTILITAIRES ====================

function redirect($url) {
    // Correction : on vérifie si l'URL est déjà complète pour ne pas la doubler
    if (strpos($url, 'http') === 0) {
        header("Location: " . $url);
    } else {
        header("Location: " . BASE_URL . $url);
    }
    exit();
}


function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatPrice($price) {
    return '$' . number_format($price, 2, '.', ',');
}

function generateReference($prefix = 'REF') {
    return $prefix . '-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// ==================== GESTION DES SESSIONS ====================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Visiteur';
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function getUserPhoto() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT photo FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['photo'] ?? null;
}

function getUserPhotoUrl($default = null) {
    $photo = getUserPhoto();
    if ($photo && file_exists(__DIR__ . '/../assets/images/profiles/' . $photo)) {
        return BASE_URL . '/assets/images/profiles/' . $photo;
    }
    return $default;
}

// ==================== GESTION DES RÔLES ====================

function isAdmin() {
    return getUserRole() === 'Admin';
}

function isAgent() {
    return getUserRole() === 'Agent';
}

function isClient() {
    return getUserRole() === 'Client';
}

function isAdminOrAgent() {
    return isAdmin() || isAgent();
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('/index.php');
    }
}

function requireAdminOrAgent() {
    if (!isAdminOrAgent()) {
        redirect('/index.php');
    }
}

function requireClient() {
    if (!isClient()) {
        redirect('/auth/login.php');
    }
}

// ==================== FONCTIONS DE NAVIGATION ====================

function getDashboardUrl() {
    $role = getUserRole();
    switch ($role) {
        case 'Admin':
        case 'Agent':
            return '/admin/index.php'; // Correction : on renvoie juste le chemin, pas l'URL complète
        case 'Client':
            return '/client/index.php';
        default:
            return '/auth/login.php';
    }
}

function getDashboardPath() {
    $role = getUserRole();
    switch ($role) {
        case 'Admin':
        case 'Agent':
            return '/admin/index.php';
        case 'Client':
            return '/client/index.php';
        default:
            return '/auth/login.php';
    }
}

// ==================== FONCTIONS POUR VÉHICULES ====================

function getVehiculesVedettes($limit = 6) {
    $db = getDB();
    $stmt = $db->prepare("SELECT v.*, c.nom_categorie FROM Vehicules v 
                          LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie 
                          WHERE v.est_vedette = TRUE AND v.etat = 'Disponible' 
                          LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getAllVehiculesDisponibles() {
    $db = getDB();
    return $db->query("SELECT v.*, c.nom_categorie FROM Vehicules v 
                       LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie 
                       WHERE v.etat = 'Disponible' ORDER BY v.marque")->fetchAll();
}

// ==================== FONCTIONS POUR PARAMÈTRES ====================

function getParametre($cle) {
    $db = getDB();
    $stmt = $db->prepare("SELECT valeur FROM Parametres WHERE cle = ?");
    $stmt->execute([$cle]);
    $result = $stmt->fetch();
    return $result ? $result['valeur'] : null;
}
?>
<?php
// ==================== CONFIGURATION PHPMAILER (GOOGLE) ====================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'mompremierrubendy08@gmail.com'); // VOTRE VRAI EMAIL GMAIL
define('SMTP_PASS', 'wdwxhdwopkiyibee');       // LE MOT DE PASSE D'APPLICATION DE 16 LETTRES      // LE MOT DE PASSE D'APPLICATION DE 16 LETTRES
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'Trade Center Location');

// ==================== FONCTION D'ENVOI D'EMAIL ====================

function sendEmail($toEmail, $subject, $body, $toName = '') {
    // CORRECTION : On remonte d'un dossier (__DIR__) pour aller dans /includes
    require_once __DIR__ . '/../includes/PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/../includes/PHPMailer-master/src/SMTP.php';
    require_once __DIR__ . '/../includes/PHPMailer-master/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // ... le reste de la fonction reste exactement identique ...
    try {
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Expéditeur
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);

        // Destinataire
        if (!empty($toName)) {
            $mail->addAddress($toEmail, $toName);
        } else {
            $mail->addAddress($toEmail);
        }

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Version texte brut

        $mail->send();
        return true;
    } catch (Exception $e) {
        // En cas d'erreur, vous pouvez loguer l'erreur ou l'afficher en dev
        // error_log("Erreur PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}

?>
<?php
// ==================== FONCTIONS POUR LES AVIS ====================

function getStarsHtml($note) {
    $html = '<div class="flex items-center gap-1">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $note) {
            $html .= '<i class="fas fa-star text-orange-400"></i>';
        } else {
            $html .= '<i class="far fa-star text-gray-300"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}

function getAverageRating($idVehicule) {
    $db = getDB();
    $stmt = $db->prepare("SELECT AVG(note) as moyenne, COUNT(*) as total FROM Avis WHERE id_vehicule = ? AND statut = 'Publié'");
    $stmt->execute([$idVehicule]);
    $result = $stmt->fetch();
    return [
        'moyenne' => round($result['moyenne'] ?? 0, 1),
        'total' => $result['total'] ?? 0
    ];
}
?>