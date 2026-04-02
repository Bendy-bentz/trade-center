<?php
require_once __DIR__ . '/../config/config.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    redirect(getDashboardUrl());
}

 $db = getDB();
 $error = '';
 $success = '';

// Gestion des messages (Succès email, reset, etc.)
 $msg = $_GET['msg'] ?? '';
if ($msg === 'verify_sent') {
    $success = "Un email de confirmation vous a été envoyé.";
} elseif ($msg === 'verified') {
    $success = "Félicitations ! Votre compte est activé. Connectez-vous.";
} elseif ($msg === 'reset_success') {
    $success = "Mot de passe modifié avec succès ! Connectez-vous.";
}

// TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM Utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        
        // Connexion OK ...
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['nom'];
        
        // Mise à jour date connexion
        $db->prepare("UPDATE Utilisateurs SET derniere_connexion = NOW() WHERE id_utilisateur = ?")->execute([$user['id_utilisateur']]);

        // VRAIE REDIRECTION RESTAURÉE
        redirect(getDashboardUrl());
        
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}

 $pageTitle = 'Connexion';
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
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    
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
                  <span class="text-orange-500"> Trade Center Location</span>
                </span>
            </a>
            <p class="text-white mt-2 text-sm">Votre partenaire de confiance</p>
        </div>

        <!-- Carte Formulaire -->
        <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-2xl p-8 md:p-10">
            
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Connexion</h2>
            </div>

            <!-- Message de Succès (Email envoyé / Compte activé) -->
            <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-lg mb-6 text-sm flex items-center">
                <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
            </div>
            <?php endif; ?>

            <!-- Message d'Erreur -->
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- L'attribut action est vide pour envoyer à cette même page -->
            <form action="" method="POST" class="space-y-5">
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition bg-gray-50"
                               placeholder="votre@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <!-- Mot de passe -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Mot de passe</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition bg-gray-50"
                               placeholder="enter votre mot de passe">
                    </div>
                </div>

                <!-- Options -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                        <span class="ml-2 text-gray-600">Se souvenir</span>
                    </label>
                    <a href="forgot-password.php" class="text-orange-500 hover:underline font-medium">Oublié ?</a>
                </div>

                <!-- Bouton -->
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-bold transition shadow-lg flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <!-- Lien Inscription -->
            <div class="mt-6 text-center text-sm">
                <span class="text-gray-500">Pas encore de compte ?</span>
                <a href="register.php" class="font-bold text-orange-500 hover:text-orange-600 ml-1">S'inscrire</a>
            </div>
        </div>
        
        <!-- Retour accueil -->
        <div class="text-center mt-6">
            <a href="<?= BASE_URL ?>/index.php" class="text-gray-300 hover:text-white text-sm transition">
                <i class="fas fa-arrow-left mr-1"></i> Retour à l'accueil
            </a>
        </div>
    </div>

</body>
</html>