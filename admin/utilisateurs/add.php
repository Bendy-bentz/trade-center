<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/email_config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';
$tempPassword = '';
$userCreated = false;

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
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Cette adresse email est déjà utilisée.';
        } else {
            // Générer un mot de passe temporaire sécurisé
            $tempPassword = bin2hex(random_bytes(8)); // 16 caractères
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            try {
                $stmt = $db->prepare("INSERT INTO Utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $email, $hashedPassword, $role]);
                $userCreated = true;
                
                // TODO: Envoyer l'email avec le mot de passe temporaire
                // Pour l'instant, on l'affiche dans l'interface
                
                $success = "Utilisateur créé avec succès!";
                
            } catch (Exception $e) {
                $error = 'Erreur lors de la création: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Ajouter un utilisateur';
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

<?php if ($userCreated): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-6 rounded-lg mb-6">
    <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-check-circle text-green-500 text-xl"></i>
        <span class="font-semibold text-lg"><?= $success ?></span>
    </div>
    
    <div class="bg-white rounded-lg p-4 border border-green-200 mb-4">
        <h4 class="font-medium text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-key text-orange-500"></i> Mot de passe temporaire
        </h4>
        <div class="bg-gray-100 rounded-lg p-4 flex items-center justify-between">
            <code class="text-xl font-mono text-gray-800"><?= $tempPassword ?></code>
            <button onclick="copyPassword('<?= $tempPassword ?>')" class="btn-secondary text-sm px-3 py-1">
                <i class="fas fa-copy mr-1"></i> Copier
            </button>
        </div>
    </div>
    
    <div class="space-y-2 text-sm">
        <p class="flex items-start gap-2">
            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
            <span><strong>Important:</strong> Transmettez ce mot de passe à l'utilisateur de manière sécurisée.</span>
        </p>
        <p class="flex items-start gap-2">
            <i class="fas fa-shield-alt text-green-500 mt-0.5"></i>
            <span>L'utilisateur devra changer ce mot de passe lors de sa première connexion.</span>
        </p>
    </div>
    
    <div class="flex gap-3 mt-4">
        <a href="index.php" class="btn-primary flex items-center gap-2">
            <i class="fas fa-list"></i> Voir la liste
        </a>
        <a href="add.php" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-plus"></i> Ajouter un autre
        </a>
    </div>
</div>
<?php else: ?>

<!-- Information sécurité -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-shield-alt text-blue-500"></i>
        </div>
        <div>
            <h4 class="font-semibold text-blue-800">Politique de sécurité</h4>
            <p class="text-sm text-blue-700 mt-1">
                L'administrateur ne définit pas le mot de passe de l'utilisateur. 
                Un mot de passe temporaire sera généré automatiquement et devra être communiqué à l'utilisateur.
                Celui-ci devra le changer lors de sa première connexion.
            </p>
        </div>
    </div>
</div>

<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Ajouter un utilisateur</h2>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="nom" required class="input-field pl-10" placeholder="Nom de l'utilisateur">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse email *</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="email" name="email" required class="input-field pl-10" placeholder="utilisateur@email.com">
                </div>
                <p class="text-xs text-gray-500 mt-1">L'email servira d'identifiant de connexion</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                <div class="relative">
                    <i class="fas fa-user-tag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <select name="role" required class="input-field pl-10">
                        <option value="">-- Sélectionner un rôle --</option>
                        <option value="Agent">Agent</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="mt-2 space-y-1 text-xs text-gray-500">
                    <p><span class="font-medium text-orange-600">Admin:</span> Accès complet à toutes les fonctionnalités</p>
                    <p><span class="font-medium text-blue-600">Agent:</span> Gestion des réservations, clients et véhicules</p>
                </div>
            </div>
            
            <!-- Notice mot de passe -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-key text-yellow-600 mt-0.5"></i>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium">Mot de passe automatique</p>
                        <p class="mt-1">Un mot de passe temporaire sera généré automatiquement. Vous pourrez le copier et le transmettre à l'utilisateur.</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="index.php" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-plus"></i> Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function copyPassword(password) {
    navigator.clipboard.writeText(password).then(() => {
        alert('Mot de passe copié dans le presse-papiers!');
    }).catch(() => {
        // Fallback
        const input = document.createElement('input');
        input.value = password;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Mot de passe copié!');
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
