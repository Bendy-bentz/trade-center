<?php
require_once __DIR__ . '/../config/config.php';

 $db = getDB();
 $token = $_GET['token'] ?? '';

if ($token) {
    // Chercher l'utilisateur avec ce token
    $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Activer le compte et supprimer le token
        $stmt = $db->prepare("UPDATE Utilisateurs SET email_verified = 1, verification_token = NULL WHERE id_utilisateur = ?");
        $stmt->execute([$user['id_utilisateur']]);
        
        redirect('/auth/login.php?msg=verified');
    } else {
        die("Lien invalide ou expiré.");
    }
} else {
    redirect('/');
}
?>