<?php
require_once __DIR__ . '/../config/config.php';
requireAdminOrAgent();

 $db = getDB();
 $userId = getUserId();
 $user = getCurrentUser();

 $success = '';
 $error = '';

// Créer le dossier d'upload s'il n'existe pas
 $uploadDir = __DIR__ . '/../../assets/images/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Traitement de l'upload de la photo (Formulaire dédié)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    // Vérification CSRF à ajouter ici si implémenté
    
    if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['photo'];
        $tmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Sécurité : Vérifier le type MIME réel du fichier
        $fileMimeType = mime_content_type($tmpName);
        
        // Sécurité : Vérifier l'extension du fichier
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            $error = 'Format de fichier non autorisé (Type MIME détecté : ' . htmlspecialchars($fileMimeType) . ').';
        } elseif (!in_array($extension, $allowedExtensions)) {
            $error = 'Extension de fichier non autorisée.';
        } elseif ($fileSize > $maxSize) {
            $error = 'Le fichier est trop volumineux. Maximum 5MB.';
        } else {
            // Nom de fichier sécurisé
            $fileName = 'admin_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destination = $uploadDir . $fileName;
            
            // Supprimer l'ancienne photo si elle existe
            if (!empty($user['photo']) && file_exists($uploadDir . $user['photo'])) {
                unlink($uploadDir . $user['photo']);
            }
            
            if (move_uploaded_file($tmpName, $destination)) {
                // Redimensionner si nécessaire (GD)
                if (function_exists('getimagesize')) {
                    $imageInfo = @getimagesize($destination);
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                        $maxDimension = 300;
                        
                        if ($width > $maxDimension || $height > $maxDimension) {
                            $ratio = min($maxDimension / $width, $maxDimension / $height);
                            $newWidth = (int)($width * $ratio);
                            $newHeight = (int)($height * $ratio);
                            
                            $source = @imagecreatefromstring(file_get_contents($destination));
                            if ($source) {
                                $thumb = imagecreatetruecolor($newWidth, $newHeight);
                                
                                // Gestion transparence PNG/GIF
                                if ($fileMimeType === 'image/png' || $fileMimeType === 'image/gif') {
                                    imagealphablending($thumb, false);
                                    imagesavealpha($thumb, true);
                                    $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                                    imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
                                }
                                
                                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                
                                switch ($fileMimeType) {
                                    case 'image/jpeg': imagejpeg($thumb, $destination, 85); break;
                                    case 'image/png': imagepng($thumb, $destination); break;
                                    case 'image/gif': imagegif($thumb, $destination); break;
                                    case 'image/webp': imagewebp($thumb, $destination, 85); break;
                                }
                                
                                imagedestroy($source);
                                imagedestroy($thumb);
                            }
                        }
                    }
                }
                
                $db->prepare("UPDATE Utilisateurs SET photo = ? WHERE id_utilisateur = ?")->execute([$fileName, $userId]);
                $user['photo'] = $fileName; // Mise à jour de la variable locale pour l'affichage immédiat
                $success = 'Photo de profil mise à jour avec succès !';
            } else {
                $error = 'Erreur lors de l\'upload de la photo.';
            }
        }
    } else {
         $error = 'Erreur lors de l\'envoi du fichier (Code : ' . $_FILES['photo']['error'] . ').';
    }
}

// Traitement des informations personnelles et mot de passe (Formulaire dédié)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'])) {
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email'] ?? '');
    
    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE email = ? AND id_utilisateur != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $error = 'Cette adresse email est déjà utilisée par un autre compte.';
        }
    }
    
    if (empty($error)) {
        $db->prepare("UPDATE Utilisateurs SET nom = ?, email = ? WHERE id_utilisateur = ?")->execute([$nom, $email, $userId]);
        
        // Changement de mot de passe
        if (!empty($_POST['current_password']) || !empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                $error = 'Veuillez entrer votre mot de passe actuel pour le changer.';
            } elseif (!password_verify($_POST['current_password'], $user['mot_de_passe'])) {
                $error = 'Le mot de passe actuel est incorrect.';
            } elseif (empty($_POST['new_password'])) {
                $error = 'Veuillez entrer un nouveau mot de passe.';
            } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
                $error = 'Les nouveaux mots de passe ne correspondent pas.';
            } elseif (strlen($_POST['new_password']) < 6) {
                $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
            } else {
                $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $db->prepare("UPDATE Utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?")->execute([$hashed, $userId]);
                $success = 'Profil et mot de passe mis à jour avec succès !';
            }
        } else {
            $success = 'Profil mis à jour avec succès !';
        }
        
        // Recharger les données utilisateur
        $user = getCurrentUser();
        $_SESSION['user_name'] = $user['nom'];
    }
}

// Suppression de la photo
if (isset($_GET['delete_photo'])) {
    if (!empty($user['photo']) && file_exists($uploadDir . $user['photo'])) {
        unlink($uploadDir . $user['photo']);
    }
    $db->prepare("UPDATE Utilisateurs SET photo = NULL WHERE id_utilisateur = ?")->execute([$userId]);
    redirect('/admin/profil.php?msg=photo_deleted');
}

if (isset($_GET['msg']) && $_GET['msg'] === 'photo_deleted') {
    $success = 'Photo de profil supprimée.';
}

 $pageTitle = 'Mon Profil';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<?php if ($success): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
    <i class="fas fa-check-circle mr-2"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
    <i class="fas fa-exclamation-circle mr-2"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="max-w-4xl mx-auto">
    <!-- En-tête profil -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <div class="relative">
                <?php if (!empty($user['photo'])): ?>
                    <img src="<?= BASE_URL ?>/assets/images/profiles/<?= htmlspecialchars($user['photo']) ?>" 
                         alt="Photo de profil" 
                         class="w-28 h-28 rounded-full object-cover border-4 border-orange-100 shadow-lg">
                <?php else: ?>
                    <div class="w-28 h-28 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-4xl font-bold border-4 border-orange-100 shadow-lg">
                        <?= strtoupper(substr($user['nom'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($user['nom']) ?></h2>
                <p class="text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                <div class="mt-2">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        <?= $user['role'] === 'Admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                        <i class="fas fa-user-shield mr-1"></i> <?= htmlspecialchars($user['role']) ?>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Membre depuis</p>
                <p class="font-medium text-gray-700"><?= formatDate($user['date_creation']) ?></p>
            </div>
        </div>
    </div>

    <!-- Photo de profil -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-camera text-orange-500"></i> Photo de profil
        </h3>
        <!-- Ajout de l'input hidden pour forcer la détection dans le POST -->
        <form method="POST" enctype="multipart/form