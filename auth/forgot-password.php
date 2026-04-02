<?php
require_once __DIR__ . '/../config/config.php';
// Ligne Resend supprimée et remplacée par l'utilisation de sendEmail() du config.php

 $error = '';
 $success = '';

if (isLoggedIn()) {
    redirect(getDashboardUrl());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } else {
        $db = getDB();
        
        // Vérifier si l'utilisateur existe
        $stmt = $db->prepare("SELECT id_utilisateur, nom FROM Utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Générer un token sécurisé
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Enregistrer le token dans la BDD
            $update = $db->prepare("UPDATE Utilisateurs SET reset_token = ?, reset_expiry = ? WHERE id_utilisateur = ?");
            $update->execute([$token, $expiry, $user['id_utilisateur']]);

            // Construire le lien
            $resetLink = BASE_URL . "/auth/reset-password.php?token=" . $token;

            // Contenu HTML de l'email
            $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <div style='text-align:center; margin-bottom:20px;'>
                    <h1 style='color:#f97316; margin:0;'>Trade Center</h1>
                </div>
                <h2 style='color: #333;'>Mot de passe oublié ?</h2>
                <p>Bonjour <strong>" . htmlspecialchars($user['nom']) . "</strong>,</p>
                <p>Nous avons reçu une demande de réinitialisation pour votre compte. Cliquez sur le bouton ci-dessous :</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' style='display: inline-block; padding: 12px 25px; background-color: #f97316; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        Réinitialiser mon mot de passe
                    </a>
                </div>
                <p style='color: #888; font-size: 12px;'>Ce lien expire dans 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
            </div>
            ";

            // --- ENVOI VIA PHPMAILER (GMAIL) ---
            if (sendEmail($email, "Réinitialisation de votre mot de passe - Trade Center", $emailBody, $user['nom'])) {
                $success = 'Un email de réinitialisation a été envoyé (si ce compte existe).';
            } else {
                // En cas d'erreur SMTP (mauvais mot de passe d'application, etc.)
                $error = "Erreur lors de l'envoi de l'email via Gmail. Vérifiez la configuration SMTP.";
            }
        } else {
            // Sécurité : message vague pour ne pas révéler si l'email existe
            $success = 'Un email de réinitialisation a été envoyé (si ce compte existe).';
        }
    }
}

 $pageTitle = 'Mot de passe oublié';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Trade Center</title>
    
    <!-- Tailwind CSS Local -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/output.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gray-900">

    <!-- ZONE IMAGE DE FOND -->
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>/assets/images/black-white-view-adventure-time-with-off-road-vehicle-rough-terrain.jpg" 
             alt="Fond" 
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/70 to-black/50"></div>
    </div>

    <!-- CONTENU -->
    <div class="relative z-10 w-full max-w-md px-6 py-8">
        
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= BASE_URL ?>/index.php">
                <span class="text-4xl font-extrabold text-white tracking-tight">
                   <span class="text-orange-500"> Trade Center</span>
                </span>
            </a>
            <p class="text-white mt-2 text-sm">Mot de passe oublié</p>
        </div>

        <!-- Carte Formulaire -->
        <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-2xl p-8 md:p-10">
            
            <!-- Message de Succès -->
            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 text-sm flex items-center">
                <i class="fas fa-check-circle mr-3"></i> <?= $success ?>
            </div>
            <?php endif; ?>

            <!-- Message d'Erreur -->
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 text-sm flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-5">
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Adresse Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition bg-gray-50"
                               placeholder="votre@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <!-- Bouton -->
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-bold transition shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i> Envoyer le lien
                </button>
            </form>

            <!-- Lien Retour -->
            <div class="mt-6 text-center text-sm">
                <a href="login.php" class="text-gray-500 hover:text-orange-500 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Retour à la connexion
                </a>
            </div>
        </div>
    </div>

</body>
</html>