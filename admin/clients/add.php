<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $prenom = sanitize($_POST['prenom']);
    $email = sanitize($_POST['email'] ?? '');
    $telephone = sanitize($_POST['telephone']);
    $telephone2 = sanitize($_POST['telephone2'] ?? '');
    $ville = sanitize($_POST['ville'] ?? '');
    $adresse = sanitize($_POST['adresse'] ?? '');
    $code_postal = sanitize($_POST['code_postal'] ?? '');
    $numero_permis = sanitize($_POST['numero_permis'] ?? '');
    $create_account = isset($_POST['create_account']);
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($telephone)) {
        $error = 'Veuillez remplir les champs obligatoires (nom, prénom, téléphone).';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif (!empty($email)) {
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT COUNT(*) FROM Clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Cette adresse email est déjà utilisée par un autre client.';
        }
    }
    
    if (empty($error)) {
        try {
            $db->beginTransaction();
            
            $id_utilisateur = null;
            
            // Créer un compte utilisateur si demandé
            if ($create_account && !empty($email)) {
                // Vérifier si l'email existe dans Utilisateurs
                $stmt = $db->prepare("SELECT id_utilisateur FROM Utilisateurs WHERE email = ?");
                $stmt->execute([$email]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    $id_utilisateur = $existingUser['id_utilisateur'];
                } else {
                    // Générer un mot de passe temporaire
                    $tempPassword = bin2hex(random_bytes(8)); // 16 caractères
                    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
                    
                    // Créer l'utilisateur avec le rôle Client
                    $stmt = $db->prepare("INSERT INTO Utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'Client')");
                    $stmt->execute([$prenom . ' ' . $nom, $email, $hashedPassword]);
                    $id_utilisateur = $db->lastInsertId();
                    
                    // TODO: Envoyer un email au client avec son mot de passe temporaire
                    // Pour l'instant, on l'affiche dans le message de succès
                    $success = "Client créé avec succès! Mot de passe temporaire: <strong class='font-mono bg-yellow-100 px-2 py-1 rounded'>$tempPassword</strong><br><span class='text-sm text-gray-600'>Le client doit changer ce mot de passe lors de sa première connexion.</span>";
                }
            }
            
            // Créer le client
            $stmt = $db->prepare("INSERT INTO Clients (nom, prenom, email, telephone, telephone2, ville, adresse, code_postal, numero_permis, id_utilisateur) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $telephone, $telephone2, $ville, $adresse, $code_postal, $numero_permis, $id_utilisateur]);
            
            $db->commit();
            
            if (empty($success)) {
                redirect('/admin/clients/index.php?msg=created');
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Ajouter un client';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="mb-6">
    <a href="index.php" class="text-blue-600 hover:underline flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Retour aux clients
    </a>
</div>

<?php if ($error): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-red-500"></i>
    <span><?= $error ?></span>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
    <div class="flex items-center gap-3 mb-2">
        <i class="fas fa-check-circle text-green-500"></i>
        <span>Client créé avec succès!</span>
    </div>
    <div class="mt-2 text-sm"><?= $success ?></div>
    <a href="index.php" class="inline-block mt-3 text-green-700 hover:text-green-800 font-medium">
        <i class="fas fa-arrow-right mr-1"></i> Voir la liste des clients
    </a>
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
                <h4 class="font-semibold text-blue-800">Politique de sécurité</h4>
                <p class="text-sm text-blue-700 mt-1">
                    L'administrateur ne définit pas le mot de passe du client. 
                    Si vous créez un compte, un mot de passe temporaire sera généré automatiquement 
                    que le client devra changer lors de sa première connexion.
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Ajouter un nouveau client</h2>
        
        <form method="POST" class="space-y-6">
            <!-- Identité -->
            <div>
                <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-user text-orange-500"></i> Identité
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                        <input type="text" name="prenom" required class="input-field" placeholder="Prénom du client">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input type="text" name="nom" required class="input-field" placeholder="Nom du client">
                    </div>
                </div>
            </div>
            
            <!-- Contact -->
            <div>
                <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-phone text-orange-500"></i> Contact
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="input-field" placeholder="client@email.com">
                        <p class="text-xs text-gray-500 mt-1">Nécessaire pour créer un compte client</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone principal *</label>
                        <input type="text" name="telephone" required class="input-field" placeholder="+212 XXX XXX XXX">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone secondaire</label>
                    <input type="text" name="telephone2" class="input-field" placeholder="+212 XXX XXX XXX">
                </div>
            </div>
            
            <!-- Adresse -->
            <div>
                <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-orange-500"></i> Adresse
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                        <input type="text" name="adresse" class="input-field" placeholder="Rue, numéro, quartier">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                        <input type="text" name="ville" class="input-field" placeholder="Casablanca">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                    <input type="text" name="code_postal" class="input-field" placeholder="20000">
                </div>
            </div>
            
            <!-- Permis -->
            <div>
                <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-id-card text-orange-500"></i> Permis de conduire
                </h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de permis</label>
                    <input type="text" name="numero_permis" class="input-field" placeholder="MA-2020-12345">
                </div>
            </div>
            
            <!-- Option compte client -->
            <div class="bg-gray-50 rounded-lg p-4 border">
                <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-user-circle text-orange-500"></i> Compte client
                </h4>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="create_account" class="mt-1 w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500" <?= empty($success) ? '' : 'disabled' ?>>
                    <div>
                        <span class="font-medium text-gray-700">Créer un compte client</span>
                        <p class="text-sm text-gray-500 mt-1">
                            Permet au client de se connecter à son espace personnel pour gérer ses réservations.
                            Un mot de passe temporaire sera généré automatiquement.
                        </p>
                    </div>
                </label>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-4 pt-4 border-t">
                <button type="submit" class="btn-primary flex items-center gap-2">
                    <i class="fas fa-plus"></i> Ajouter le client
                </button>
                <a href="index.php" class="btn-secondary flex items-center gap-2">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
