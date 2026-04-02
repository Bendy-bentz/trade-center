<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();
$id = $_GET['id'] ?? 0;

// Récupérer les infos du client avec son compte utilisateur
$stmt = $db->prepare("SELECT c.*, u.email as user_email, u.role, u.photo 
                      FROM Clients c 
                      LEFT JOIN Utilisateurs u ON c.id_utilisateur = u.id_utilisateur 
                      WHERE c.id_client = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    redirect('/admin/clients/index.php');
}

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
    
    try {
        // Mettre à jour les informations du client
        $stmt = $db->prepare("UPDATE Clients SET nom=?, prenom=?, email=?, telephone=?, telephone2=?, ville=?, adresse=?, code_postal=?, numero_permis=? WHERE id_client=?");
        $stmt->execute([$nom, $prenom, $email, $telephone, $telephone2, $ville, $adresse, $code_postal, $numero_permis, $id]);
        
        // Mettre à jour l'email dans la table utilisateurs si le client a un compte
        if (!empty($client['id_utilisateur']) && !empty($email)) {
            $db->prepare("UPDATE Utilisateurs SET email = ? WHERE id_utilisateur = ?")
               ->execute([$email, $client['id_utilisateur']]);
        }
        
        $success = 'Les informations du client ont été mises à jour avec succès.';
        
        // Recharger les données
        $stmt = $db->prepare("SELECT c.*, u.email as user_email, u.role, u.photo 
                              FROM Clients c 
                              LEFT JOIN Utilisateurs u ON c.id_utilisateur = u.id_utilisateur 
                              WHERE c.id_client = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Erreur lors de la mise à jour: ' . $e->getMessage();
    }
}

$pageTitle = 'Modifier un client';
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
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-500"></i>
    <span><?= $success ?></span>
</div>
<?php endif; ?>

<div class="max-w-4xl mx-auto">
    <!-- Informations de sécurité -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-alt text-blue-500"></i>
            </div>
            <div>
                <h4 class="font-semibold text-blue-800">Protection de la vie privée</h4>
                <p class="text-sm text-blue-700 mt-1">
                    Pour des raisons de sécurité, le mot de passe du client n'est pas accessible ni modifiable par l'administrateur.
                    Le client est le seul à pouvoir gérer son mot de passe via son profil ou la fonctionnalité "Mot de passe oublié".
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Photo et info rapide -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <?php if (!empty($client['photo'])): ?>
                    <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $client['photo'] ?>" 
                         alt="Photo" 
                         class="w-24 h-24 rounded-full object-cover mx-auto border-4 border-orange-100 shadow-lg">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-3xl font-bold mx-auto border-4 border-orange-100 shadow-lg">
                        <?= strtoupper(substr($client['prenom'], 0, 1) . substr($client['nom'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <h3 class="mt-4 text-lg font-semibold text-gray-800">
                    <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?>
                </h3>
                <p class="text-gray-500 text-sm">Client #<?= $client['id_client'] ?></p>
                
                <?php if (!empty($client['role'])): ?>
                <span class="inline-block mt-2 px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                    <i class="fas fa-check-circle mr-1"></i> Compte actif
                </span>
                <?php else: ?>
                <span class="inline-block mt-2 px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                    <i class="fas fa-user mr-1"></i> Sans compte
                </span>
                <?php endif; ?>
                
                <div class="mt-4 pt-4 border-t text-left space-y-2">
                    <p class="text-sm"><span class="text-gray-500">Inscrit le:</span> <span class="font-medium"><?= formatDate($client['date_inscription']) ?></span></p>
                    <?php if (!empty($client['email'])): ?>
                    <p class="text-sm"><span class="text-gray-500">Email:</span> <span class="font-medium break-all"><?= htmlspecialchars($client['email']) ?></span></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Statistiques du client -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h4 class="font-semibold text-gray-800 mb-4">Activité</h4>
                <?php
                $stmt = $db->prepare("SELECT COUNT(*) as total, 
                                      SUM(CASE WHEN statut = 'Terminée' THEN 1 ELSE 0 END) as terminees,
                                      SUM(CASE WHEN statut = 'En cours' THEN 1 ELSE 0 END) as en_cours
                                      FROM Reservations WHERE id_client = ?");
                $stmt->execute([$id]);
                $stats = $stmt->fetch();
                ?>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total réservations</span>
                        <span class="font-semibold text-gray-800"><?= $stats['total'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">En cours</span>
                        <span class="font-semibold text-blue-600"><?= $stats['en_cours'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Terminées</span>
                        <span class="font-semibold text-green-600"><?= $stats['terminees'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire principal -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Informations du client</h2>
                
                <form method="POST" class="space-y-6">
                    <!-- Identité -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-user text-orange-500"></i> Identité
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                                <input type="text" name="prenom" value="<?= htmlspecialchars($client['prenom']) ?>" required class="input-field">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                <input type="text" name="nom" value="<?= htmlspecialchars($client['nom']) ?>" required class="input-field">
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
                                <input type="email" name="email" value="<?= htmlspecialchars($client['email'] ?? '') ?>" class="input-field" placeholder="client@email.com">
                                <?php if (!empty($client['email'])): ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle"></i> Email de connexion au compte client
                                </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone principal *</label>
                                <input type="text" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" required class="input-field" placeholder="+212 XXX XXX XXX">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone secondaire</label>
                            <input type="text" name="telephone2" value="<?= htmlspecialchars($client['telephone2'] ?? '') ?>" class="input-field" placeholder="+212 XXX XXX XXX">
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
                                <input type="text" name="adresse" value="<?= htmlspecialchars($client['adresse'] ?? '') ?>" class="input-field" placeholder="Rue, numéro, quartier">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                                <input type="text" name="ville" value="<?= htmlspecialchars($client['ville'] ?? '') ?>" class="input-field" placeholder="Casablanca">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                                <input type="text" name="code_postal" value="<?= htmlspecialchars($client['code_postal'] ?? '') ?>" class="input-field" placeholder="20000">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pays</label>
                                <input type="text" name="pays" value="<?= htmlspecialchars($client['pays'] ?? 'Maroc') ?>" class="input-field" disabled>
                                <p class="text-xs text-gray-500 mt-1">Par défaut: Maroc</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Permis -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fas fa-id-card text-orange-500"></i> Permis de conduire
                        </h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de permis</label>
                            <input type="text" name="numero_permis" value="<?= htmlspecialchars($client['numero_permis'] ?? '') ?>" class="input-field" placeholder="MA-2020-12345">
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-4 pt-4 border-t">
                        <button type="submit" class="btn-primary flex items-center gap-2">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <a href="index.php" class="btn-secondary flex items-center gap-2">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Sécurité compte -->
            <?php if (!empty($client['id_utilisateur'])): ?>
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h4 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-green-500"></i> Sécurité du compte
                </h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Le client gère son propre mot de passe</p>
                            <p class="text-xs text-gray-500 mt-1">Il peut le modifier depuis son profil ou utiliser "Mot de passe oublié"</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <i class="fas fa-lock mr-1"></i> Sécurisé
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>