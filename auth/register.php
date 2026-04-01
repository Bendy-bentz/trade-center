<?php
require_once __DIR__ . '/../config/config.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    redirect(getDashboardUrl());
}

 $db = getDB();
 $error = '';

// --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telephone = sanitize($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validations
    if (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si email existe déjà
        $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Hashage du mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Génération du token de vérification
            $verificationToken = bin2hex(random_bytes(32));

            // Insertion en BDD
            $stmt = $db->prepare("INSERT INTO Utilisateurs (nom, email, mot_de_passe, role, telephone, email_verified, verification_token) 
                                  VALUES (?, ?, ?, 'Client', ?, 0, ?)");
            
            if ($stmt->execute([$nom, $email, $hashedPassword, $telephone, $verificationToken])) {
                
                // --- ENVOI EMAIL VIA PHPMAILER (GOOGLE) ---
                $verifyLink = BASE_URL . "/auth/verify.php?token=" . $verificationToken;
                
                $emailBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <div style='text-align:center; margin-bottom:20px;'>
                        <h1 style='color:#f97316; margin:0;'>Trade Center</h1>
                    </div>
                    <h2 style='color: #333;'>Bienvenue sur Trade Center !</h2>
                    <p>Bonjour <strong>" . htmlspecialchars($nom) . "</strong>,</p>
                    <p>Merci de vous être inscrit. Veuillez cliquer sur le bouton ci-dessous pour activer votre compte :</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$verifyLink' style='display: inline-block; padding: 12px 25px; background-color: #f97316; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Vérifier mon email
                        </a>
                    </div>
                    <p style='color: #888; font-size: 12px;'>Si le bouton ne fonctionne pas, copiez ce lien : <br>$verifyLink</p>
                    <p style='color: #888; font-size: 12px;'>Si vous n'avez pas créé de compte, ignorez cet email.</p>
                </div>
                ";

                // Utilisation de la nouvelle fonction sendEmail() du config.php
                if (sendEmail($email, "Confirmez votre inscription - Trade Center", $emailBody, $nom)) {
                    redirect('/auth/login.php?msg=verify_sent');
                } else {
                    // Si l'envoi échoue (mauvais mot de passe Gmail, etc.)
                    redirect('/auth/login.php?msg=verify_error');
                }
            } else {
                $error = "Erreur lors de l'inscription.";
            }
        }
    }
}

 $pageTitle = 'Inscription';
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
        </div>

        <!-- Carte Formulaire -->
        <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-2xl p-8 md:p-10">
            
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Inscription</h2>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- L'Action est vide pour envoyer à la même page -->
            <form action="" method="POST" class="space-y-4">
                
                <!-- Nom -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Nom complet</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                               placeholder="Jean Dupont">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Adresse Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                               placeholder="votre@email.com">
                    </div>
                </div>

                <!-- Téléphone -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Téléphone</label>
                    <div class="relative">
                        <i class="fas fa-phone absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="tel" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                               placeholder="+33 6 00 00 00 00">
                    </div>
                </div>

                <!-- Mot de passe -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Mot de passe</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                               placeholder="Min. 6 caractères">
                    </div>
                </div>

                <!-- Confirmer -->
                <div>
                    <label class="block text-sm font-bold text-orange-500 mb-1">Confirmer le mot de passe</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="confirm_password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                               placeholder="Retapez le mot de passe">
                    </div>
                </div>

                <!-- Conditions -->
                <div class="flex items-start text-sm pt-2">
                    <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500">
                    <span class="ml-2 text-gray-500">J'accepte les <a href="conditions-utilisation.php" class="text-orange-500 hover:underline font-medium">CGV</a>.</span>
                </div>

                <!-- Bouton -->
                <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-bold transition shadow-lg flex items-center justify-center gap-2 mt-4">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </form>

            <!-- Lien Connexion -->
            <div class="mt-6 text-center text-sm">
                <span class="text-gray-500">Déjà un compte ?</span>
                <a href="login.php" class="font-bold text-orange-500 hover:text-orange-600 ml-1">Se connecter</a>
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