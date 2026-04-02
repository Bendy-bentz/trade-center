<?php
require_once __DIR__ . '/../config/config.php';

 $db = getDB();
 $error = '';
 $success = '';
 $validToken = false;
 $userData = null;

// 1. Vérification du token
 $token = $_GET['token'] ?? '';

if ($token) {
    // On s'assure de récupérer l'ID correctement
    $stmt = $db->prepare("SELECT id_utilisateur, reset_expiry, email FROM Utilisateurs WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Vérifier l'expiration
        if (strtotime($user['reset_expiry']) > time()) {
            $validToken = true;
            $userData = $user;
        } else {
            $error = "Ce lien a expiré. Veuillez refaire une demande.";
        }
    } else {
        $error = "Lien invalide.";
    }
} else {
    $error = "Aucun token fourni.";
}

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $userId = $userData['id_utilisateur'];

    if (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Hashage
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Mise à jour sécurisée
        try {
            $stmt = $db->prepare("UPDATE Utilisateurs 
                                  SET mot_de_passe = ?, reset_token = NULL, reset_expiry = NULL, email_verified = 1 
                                  WHERE id_utilisateur = ?");
            
            $stmt->execute([$hashed, $userId]);

            // Vérifier si la mise à jour a affecté une ligne
            if ($stmt->rowCount() > 0) {
                redirect('/auth/login.php?msg=reset_success');
            } else {
                // Si rowCount est 0, c'est que l'ID n'existe pas ou données identiques
                $error = "Erreur : Aucune modification effectuée (Utilisateur introuvable).";
            }

        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}

 $pageTitle = 'Nouveau mot de passe';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Trade Center</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; } </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gray-900">

    <!-- FOND -->
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>/assets/images/black-white-view-adventure-time-with-off-road-vehicle-rough-terrain.jpg" 
             alt="Fond" class="w-full h-full object-cover">
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
        </div>

        <!-- Carte -->
        <div class="bg-white/95 backdrop-blur-lg rounded-2xl shadow-2xl p-8 md:p-10">
            
            <!-- Affichage des erreurs -->
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-600 p-4 rounded-lg mb-6 text-sm flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <!-- Affichage du formulaire si Token Valide -->
            <?php if ($validToken): ?>
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-key text-orange-500 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Nouveau mot de passe</h2>
                    <p class="text-gray-500 text-sm mt-1">Pour le compte : <strong><?= htmlspecialchars($userData['email']) ?></strong></p>
                </div>

                <form action="" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-orange-500 mb-1">Nouveau mot de passe</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="password" required
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                                   placeholder="Min. 6 caractères">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-orange-500 mb-1">Confirmer</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="confirm_password" required
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 transition bg-gray-50"
                                   placeholder="Retapez le mot de passe">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-bold transition shadow-lg">
                        <i class="fas fa-save mr-2"></i> Enregistrer le mot de passe
                    </button>
                </form>

            <!-- Sinon (Token Invalide) -->
            <?php else: ?>
                <div class="text-center py-6">
                    <i class="fas fa-times-circle text-red-500 text-5xl mb-4"></i>
                    <h2 class="text-xl font-bold text-gray-800">Oups !</h2>
                    <p class="text-gray-500 mt-2">Ce lien n'est pas valide ou a expiré.</p>
                    <a href="forgot-password.php" class="mt-6 inline-block text-orange-500 hover:underline font-medium">
                        <i class="fas fa-redo mr-1"></i> Demander un nouveau lien
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>