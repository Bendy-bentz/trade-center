<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

 $db = getDB();
 $userId = getUserId();

 $user = getCurrentUser();
 $stmt = $db->prepare("SELECT * FROM Clients WHERE id_utilisateur = ?");
 $stmt->execute([$userId]);
 $client = $stmt->fetch();

 $success = '';
 $error = '';

// Créer le dossier d'upload s'il n'existe pas (CHEMIN CORRIGÉ : un seul ../ )
 $uploadDir = __DIR__ . '/../assets/images/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Upload de la photo de profil
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['photo'];
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $tmpName = $file['tmp_name'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.';
        } elseif ($fileSize > $maxSize) {
            $error = 'Le fichier est trop volumineux. Maximum 5MB.';
        } else {
            // Générer un nom unique
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $fileName;
            
            // Supprimer l'ancienne photo si elle existe
            if (!empty($user['photo']) && file_exists($uploadDir . $user['photo'])) {
                unlink($uploadDir . $user['photo']);
            }
            
            if (move_uploaded_file($tmpName, $destination)) {
                // Redimensionner l'image si GD est disponible
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
                                
                                // Preserve transparency for PNG/GIF
                                if ($fileType === 'image/png' || $fileType === 'image/gif') {
                                    imagealphablending($thumb, false);
                                    imagesavealpha($thumb, true);
                                    $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                                    imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
                                }
                                
                                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                
                                switch ($fileType) {
                                    case 'image/jpeg':
                                        imagejpeg($thumb, $destination, 85);
                                        break;
                                    case 'image/png':
                                        imagepng($thumb, $destination);
                                        break;
                                    case 'image/gif':
                                        imagegif($thumb, $destination);
                                        break;
                                    case 'image/webp':
                                        imagewebp($thumb, $destination, 85);
                                        break;
                                }
                                
                                imagedestroy($source);
                                imagedestroy($thumb);
                            }
                        }
                    }
                }
                
                // Mettre à jour la base de données
                $db->prepare("UPDATE Utilisateurs SET photo = ? WHERE id_utilisateur = ?")->execute([$fileName, $userId]);
                $user['photo'] = $fileName;
                $success = 'Photo de profil mise à jour avec succès !';
            } else {
                $error = 'Erreur lors de l\'upload de la photo.';
            }
        }
    }
    
    // Mise à jour des informations
    if (isset($_POST['nom'])) {
        $nom = sanitize($_POST['nom']);
        $telephone = sanitize($_POST['telephone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $adresse = sanitize($_POST['adresse'] ?? '');
        $ville = sanitize($_POST['ville'] ?? '');
        
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        if (!empty($email)) {
            $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE email = ? AND id_utilisateur != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error = 'Cette adresse email est déjà utilisée par un autre compte.';
            }
        }
        
        if (empty($error)) {
            // Mise à jour utilisateur
            $db->prepare("UPDATE Utilisateurs SET nom = ?, email = ? WHERE id_utilisateur = ?")->execute([$nom, $email, $userId]);
            
            // Mise à jour client
            if ($client) {
                $db->prepare("UPDATE Clients SET nom = ?, telephone = ?, email = ?, adresse = ?, ville = ? WHERE id_utilisateur = ?")
                   ->execute([$nom, $telephone, $email, $adresse, $ville, $userId]);
            } else {
                $db->prepare("INSERT INTO Clients (nom, telephone, email, adresse, ville, id_utilisateur) VALUES (?, ?, ?, ?, ?, ?)")
                   ->execute([$nom, $telephone, $email, $adresse, $ville, $userId]);
            }
            
            // Mot de passe
            if (!empty($_POST['current_password']) || !empty($_POST['new_password'])) {
                // Vérifier l'ancien mot de passe
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
            
            // Recharger les données
            $user = getCurrentUser();
            $stmt = $db->prepare("SELECT * FROM Clients WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            $client = $stmt->fetch();
            
            $_SESSION['user_name'] = $user['nom'];
        }
    }
}

// Suppression de la photo
if (isset($_GET['delete_photo'])) {
    if (!empty($user['photo']) && file_exists($uploadDir . $user['photo'])) {
        unlink($uploadDir . $user['photo']);
    }
    $db->prepare("UPDATE Utilisateurs SET photo = NULL WHERE id_utilisateur = ?")->execute([$userId]);
    $user['photo'] = null;
    redirect('/client/profil.php?msg=photo_deleted');
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
    <?= $success ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
    <i class="fas fa-exclamation-circle mr-2"></i>
    <?= $error ?>
</div>
<?php endif; ?>

<div class="max-w-4xl mx-auto">
    <!-- Photo de profil -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-camera text-orange-500"></i> Photo de profil
        </h3>
        <div class="flex items-center gap-6">
            <div class="relative">
                <?php if (!empty($user['photo'])): ?>
                    <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $user['photo'] ?>" 
                         alt="Photo de profil" 
                         class="w-32 h-32 rounded-full object-cover border-4 border-orange-100 shadow-lg">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-4xl font-bold border-4 border-orange-100 shadow-lg">
                        <?= strtoupper(substr($user['nom'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Changer la photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        <p class="text-xs text-gray-500 mt-1">Formats acceptés: JPG, PNG, GIF, WEBP. Taille max: 5MB</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary text-sm px-4 py-2">
                            <i class="fas fa-upload mr-1"></i> Télécharger
                        </button>
                        <?php if (!empty($user['photo'])): ?>
                        <a href="?delete_photo=1" onclick="return confirm('Supprimer la photo de profil ?')" 
                           class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm hover:bg-red-200 transition">
                            <i class="fas fa-trash mr-1"></i> Supprimer
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Informations personnelles -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-user text-orange-500"></i> Informations personnelles
        </h3>
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="tel" name="telephone" value="<?= htmlspecialchars($client['telephone'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                           placeholder="+212 XXX XXX XXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input type="text" name="ville" value="<?= htmlspecialchars($client['ville'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input type="text" name="adresse" value="<?= htmlspecialchars($client['adresse'] ?? '') ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="btn-primary px-6 py-2">
                    <i class="fas fa-save mr-1"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    <!-- Sécurité - Changement de mot de passe -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-shield-alt text-orange-500"></i> Sécurité
        </h3>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                <div class="text-sm text-blue-700">
                    <strong>Votre mot de passe est protégé.</strong> 
                    Personne d'autre que vous ne peut le voir ou le modifier. 
                    Pour des raisons de sécurité, vous devez entrer votre mot de passe actuel pour le changer.
                </div>
            </div>
        </div>
        
        <form method="POST" class="space-y-6" id="passwordForm">
            <div class="border-t pt-6">
                <h4 class="font-medium text-gray-800 mb-4">Changer le mot de passe</h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 pr-10"
                                   placeholder="Entrez votre mot de passe actuel">
                            <button type="button" onclick="togglePassword('current_password')" 
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="current_password-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                            <div class="relative">
                                <input type="password" name="new_password" id="new_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 pr-10"
                                       placeholder="Minimum 6 caractères">
                                <button type="button" onclick="togglePassword('new_password')" 
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="new_password-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                            <div class="relative">
                                <input type="password" name="confirm_password" id="confirm_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 pr-10"
                                       placeholder="Retapez le mot de passe">
                                <button type="button" onclick="togglePassword('confirm_password')" 
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indicateur de force du mot de passe -->
                    <div id="strength-container" class="hidden">
                        <div class="flex gap-1 mb-1" id="strength-bars">
                            <div class="h-1.5 flex-1 bg-gray-200 rounded transition-colors"></div>
                            <div class="h-1.5 flex-1 bg-gray-200 rounded transition-colors"></div>
                            <div class="h-1.5 flex-1 bg-gray-200 rounded transition-colors"></div>
                            <div class="h-1.5 flex-1 bg-gray-200 rounded transition-colors"></div>
                        </div>
                        <p class="text-xs text-gray-500" id="strength-text">Force du mot de passe</p>
                    </div>
                    
                    <div id="match-indicator" class="hidden">
                        <p class="text-sm flex items-center gap-2" id="match-text"></p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between items-center pt-4 border-t">
                <a href="../auth/forgot-password.php" class="text-sm text-orange-500 hover:text-orange-600">
                    <i class="fas fa-key mr-1"></i> Mot de passe oublié ?
                </a>
                <button type="submit" class="btn-primary px-6 py-2">
                    <i class="fas fa-lock mr-1"></i> Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
const newPasswordInput = document.getElementById('new_password');
const confirmPasswordInput = document.getElementById('confirm_password');
const strengthContainer = document.getElementById('strength-container');
const matchIndicator = document.getElementById('match-indicator');
const matchText = document.getElementById('match-text');

if (newPasswordInput) {
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length > 0) {
            strengthContainer.classList.remove('hidden');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) strength++;
            
            const bars = document.querySelectorAll('#strength-bars > div');
            const text = document.getElementById('strength-text');
            
            bars.forEach((bar, index) => {
                bar.className = 'h-1.5 flex-1 rounded transition-colors';
                if (index < strength) {
                    if (strength === 1) bar.classList.add('bg-red-400');
                    else if (strength === 2) bar.classList.add('bg-yellow-400');
                    else if (strength === 3) bar.classList.add('bg-blue-400');
                    else bar.classList.add('bg-green-400');
                } else {
                    bar.classList.add('bg-gray-200');
                }
            });
            
            const messages = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
            const colors = ['text-red-500', 'text-red-400', 'text-yellow-500', 'text-blue-500', 'text-green-500'];
            text.textContent = messages[strength];
            text.className = 'text-xs ' + colors[strength];
        } else {
            strengthContainer.classList.add('hidden');
        }
        
        checkPasswordMatch();
    });
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
}

function checkPasswordMatch() {
    const password = newPasswordInput.value;
    const confirm = confirmPasswordInput.value;
    
    if (confirm.length > 0) {
        matchIndicator.classList.remove('hidden');
        
        if (password === confirm) {
            matchText.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> <span class="text-green-600">Les mots de passe correspondent</span>';
        } else {
            matchText.innerHTML = '<i class="fas fa-times-circle text-red-500"></i> <span class="text-red-600">Les mots de passe ne correspondent pas</span>';
        }
    } else {
        matchIndicator.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>