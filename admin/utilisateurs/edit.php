<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM Utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/admin/utilisateurs/index.php');
}

$error = '';
$success = '';

// Empêcher de modifier son propre rôle (pour éviter de se retirer les droits admin)
$currentUserId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email']);
    $role = $_POST['role'];
    
    // Validation
    if (empty($nom) || empty($email) || empty($role)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE email = ? AND id_utilisateur != ?");
        $stmt->execute([$email, $id]);
        
        if ($stmt->fetch()) {
            $error = 'Cette adresse email est déjà utilisée par un autre utilisateur.';
        } else {
            // Empêcher l'admin de modifier son propre rôle
            if ($id === $currentUserId && $role !== $user['role']) {
                $error = 'Vous ne pouvez pas modifier votre propre rôle.';
            } else {
                try {
                    $stmt = $db->prepare("UPDATE Utilisateurs SET nom=?, email=?, role=? WHERE id_utilisateur=?");
                    $stmt->execute([$nom, $email, $role, $id]);
                    
                    $success = 'Utilisateur mis à jour avec succès!';
                    
                    // Recharger les données
                    $stmt = $db->prepare("SELECT * FROM Utilisateurs WHERE id_utilisateur = ?");
                    $stmt->execute([$id]);
                    $user = $stmt->fetch();
                    
                } catch (Exception $e) {
                    $error = 'Erreur lors de la mise à jour: ' . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = 'Modifier l\'utilisateur';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="mb-6">
    <a href="index.php" class="text-blue-600 hover:underline flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Retour aux utilisateurs
    </a>
</div>

<?php if ($error): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-red-500"></i>
    <span><?= $error ?></span>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-500"></i>
    <span><?= $success ?></span>
</div>
<?php endif; ?>

<div class="max-w-3xl mx-auto">
    <!-- Information sécurité -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-alt text-blue-500"></i>
            </div>
            <div>
                <h4 class="font-semibold text-blue-800">Protection de la vie privée</h4>
                <p class="text-sm text-blue-700 mt-1">
                    Pour des raisons de sécurité, le mot de passe de l'utilisateur n'est pas accessible ni modifiable par l'administrateur.
                    L'utilisateur peut changer son mot de passe via son profil ou la fonctionnalité "Mot de passe oublié" sur la page de connexion.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info rapide -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <?php if (!empty($user['photo'])): ?>
                    <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $user['photo'] ?>" 
                         alt="Photo" 
                         class="w-20 h-20 rounded-full object-cover mx-auto border-4 border-orange-100 shadow-lg">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-2xl font-bold mx-auto border-4 border-orange-100 shadow-lg">
                        <?= strtoupper(substr($user['nom'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <h3 class="mt-4 text-lg font-semibold text-gray-800">
                    <?= htmlspecialchars($user['nom']) ?>
                </h3>
                <p class="text-gray-500 text-sm">Utilisateur #<?= $user['id_utilisateur'] ?></p>
                
                <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-medium
                    <?= $user['role'] === 'Admin' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' ?>">
                    <i class="fas fa-user-shield mr-1"></i> <?= $user['role'] ?>
                </span>
                
                <div class="mt-4 pt-4 border-t text-left space-y-2">
                    <p class="text-sm"><span class="text-gray-500">Email:</span></p>
                    <p class="text-sm font-medium break-all"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-sm mt-3"><span class="text-gray-500">Créé le:</span></p>
                    <p class="text-sm font-medium"><?= formatDate($user['date_creation']) ?></p>
                </div>
            </div>
            
            <!-- Sécurité -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-lock text-green-500"></i> Sécurité
                </h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Mot de passe</p>
                    <p class="text-xs text-gray-500 mt-1">L'utilisateur gère son propre mot de passe</p>
                    <div class="mt-3 flex items-center gap-2 text-green-600 text-sm">
                        <i class="fas fa-check-circle"></i>
                        <span>Protégé</span>
                    </div>
                </div>
                
                <?php if ($id !== $currentUserId): ?>
                <a href="../auth/forgot-password.php" class="block mt-4 text-sm text-orange-500 hover:text-orange-600 text-center">
                    <i class="fas fa-key mr-1"></i> Envoyer un lien de réinitialisation
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulaire principal -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Modifier l'utilisateur</h2>
                
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required class="input-field pl-10">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse email *</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="input-field pl-10">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">L'email servira d'identifiant de connexion</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                        <div class="relative">
                            <i class="fas fa-user-tag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <select name="role" required class="input-field pl-10" <?= $id === $currentUserId ? 'disabled' : '' ?>>
                                <option value="Agent" <?= $user['role'] === 'Agent' ? 'selected' : '' ?>>Agent</option>
                                <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ($id === $currentUserId): ?>
                            <input type="hidden" name="role" value="<?= $user['role'] ?>">
                            <?php endif; ?>
                        </div>
                        <?php if ($id === $currentUserId): ?>
                        <p class="text-xs text-yellow-600 mt-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Vous ne pouvez pas modifier votre propre rôle
                        </p>
                        <?php endif; ?>
                        <div class="mt-2 space-y-1 text-xs text-gray-500">
                            <p><span class="font-medium text-orange-600">Admin:</span> Accès complet à toutes les fonctionnalités</p>
                            <p><span class="font-medium text-blue-600">Agent:</span> Gestion des réservations, clients et véhicules</p>
                        </div>
                    </div>
                    
                    <!-- Note mot de passe -->
                    <div class="bg-gray-50 rounded-lg p-4 border">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-key text-gray-400 mt-1"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Mot de passe</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Le mot de passe n'est pas modifiable depuis cette page. 
                                    L'utilisateur peut le changer depuis son profil ou utiliser "Mot de passe oublié" sur la page de connexion.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="index.php" class="btn-secondary flex items-center gap-2">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit" class="btn-primary flex items-center gap-2">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
