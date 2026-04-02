<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();
$currentUserId = getUserId();

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Vérifier que ce n'est pas l'utilisateur actuel et pas un admin
    $stmt = $db->prepare("SELECT role FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if ($user && $user['role'] !== 'Admin' && $id !== $currentUserId) {
        $db->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?")->execute([$id]);
    }
    redirect('/admin/utilisateurs/index.php?msg=deleted');
}

// Messages
$msg = $_GET['msg'] ?? '';
$successMessage = '';
if ($msg === 'added') {
    $successMessage = 'Utilisateur ajouté avec succès!';
} elseif ($msg === 'updated') {
    $successMessage = 'Utilisateur mis à jour avec succès!';
} elseif ($msg === 'deleted') {
    $successMessage = 'Utilisateur supprimé avec succès!';
}

// Filtre par rôle
$filtre = $_GET['filtre'] ?? null;
$sql = "SELECT id_utilisateur, nom, email, role, photo, date_creation, derniere_connexion FROM Utilisateurs WHERE role IN ('Admin', 'Agent')";
if ($filtre) {
    $stmt = $db->prepare($sql . " AND role = ? ORDER BY id_utilisateur DESC");
    $stmt->execute([$filtre]);
    $utilisateurs = $stmt->fetchAll();
} else {
    $utilisateurs = $db->query($sql . " ORDER BY id_utilisateur DESC")->fetchAll();
}

// Statistiques
$totalAdmins = count(array_filter($utilisateurs, fn($u) => $u['role'] === 'Admin'));
$totalAgents = count(array_filter($utilisateurs, fn($u) => $u['role'] === 'Agent'));

$pageTitle = 'Gestion des Utilisateurs';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<?php if ($successMessage): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-500"></i>
    <span><?= $successMessage ?></span>
</div>
<?php endif; ?>

<!-- En-tête et statistiques -->
<div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Gestion des utilisateurs</h2>
        <p class="text-gray-500 text-sm mt-1">Administrez les comptes Admin et Agent</p>
    </div>
    <a href="add.php" class="btn-primary inline-flex items-center gap-2">
        <i class="fas fa-plus"></i> Nouvel utilisateur
    </a>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-purple-500"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $totalAdmins ?></p>
                <p class="text-sm text-gray-500">Administrateurs</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-tie text-blue-500"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $totalAgents ?></p>
                <p class="text-sm text-gray-500">Agents</p>
            </div>
        </div>
    </div>
</div>

<!-- Note de sécurité -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start gap-3">
        <i class="fas fa-shield-alt text-blue-500 mt-1"></i>
        <div class="text-sm text-blue-700">
            <strong>Protection de la vie privée:</strong> Les mots de passe des utilisateurs sont protégés et ne sont pas accessibles par l'administration.
            Chaque utilisateur gère son propre mot de passe via son profil ou la fonctionnalité de récupération.
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="mb-6 flex gap-2 flex-wrap">
    <a href="?filtre=Admin" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filtre=='Admin'?'bg-purple-500 text-white':'bg-white text-gray-600 hover:bg-gray-100 shadow' ?>">
        <i class="fas fa-user-shield mr-1"></i> Admins
    </a>
    <a href="?filtre=Agent" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filtre=='Agent'?'bg-blue-500 text-white':'bg-white text-gray-600 hover:bg-gray-100 shadow' ?>">
        <i class="fas fa-user-tie mr-1"></i> Agents
    </a>
    <a href="index.php" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= !$filtre?'bg-gray-700 text-white':'bg-white text-gray-600 hover:bg-gray-100 shadow' ?>">
        <i class="fas fa-users mr-1"></i> Tous
    </a>
</div>

<!-- Liste des utilisateurs -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if (empty($utilisateurs)): ?>
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 mb-4">Aucun utilisateur trouvé.</p>
            <a href="add.php" class="btn-primary inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Ajouter un utilisateur
            </a>
        </div>
    <?php else: ?>
        <!-- Vue bureau -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rôle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créé le</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sécurité</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($utilisateurs as $u): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($u['photo'])): ?>
                                    <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $u['photo'] ?>" 
                                         class="w-10 h-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-semibold text-sm">
                                        <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($u['nom']) ?></p>
                                    <?php if ($u['id_utilisateur'] === $currentUserId): ?>
                                    <span class="text-xs text-orange-500 font-medium">(Vous)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?= $u['role']=='Admin'?'bg-purple-100 text-purple-700':'bg-blue-100 text-blue-700' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-sm">
                            <?= $u['date_creation'] ? formatDateTime($u['date_creation']) : '-' ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 text-green-600 text-sm">
                                <i class="fas fa-lock"></i> Protégé
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="edit.php?id=<?= $u['id_utilisateur'] ?>" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['role'] !== 'Admin' && $u['id_utilisateur'] !== $currentUserId): ?>
                                <a href="?delete=<?= $u['id_utilisateur'] ?>" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')" 
                                   class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" 
                                   title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Vue mobile -->
        <div class="md:hidden divide-y divide-gray-200">
            <?php foreach ($utilisateurs as $u): ?>
            <div class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <?php if (!empty($u['photo'])): ?>
                        <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $u['photo'] ?>" 
                             class="w-12 h-12 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-semibold">
                            <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">
                            <?= htmlspecialchars($u['nom']) ?>
                            <?php if ($u['id_utilisateur'] === $currentUserId): ?>
                            <span class="text-xs text-orange-500 font-medium">(Vous)</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium
                        <?= $u['role']=='Admin'?'bg-purple-100 text-purple-700':'bg-blue-100 text-blue-700' ?>">
                        <?= $u['role'] ?>
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm mb-3">
                    <span class="text-green-600"><i class="fas fa-lock mr-1"></i> Mot de passe protégé</span>
                    <span class="text-gray-500"><?= $u['date_creation'] ? formatDate($u['date_creation']) : '-' ?></span>
                </div>
                <div class="flex gap-2 pt-3 border-t">
                    <a href="edit.php?id=<?= $u['id_utilisateur'] ?>" class="flex-1 btn-primary text-center text-sm py-2">
                        <i class="fas fa-edit mr-1"></i> Modifier
                    </a>
                    <?php if ($u['role'] !== 'Admin' && $u['id_utilisateur'] !== $currentUserId): ?>
                    <a href="?delete=<?= $u['id_utilisateur'] ?>" 
                       onclick="return confirm('Supprimer cet utilisateur ?')" 
                       class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm hover:bg-red-200 transition">
                        <i class="fas fa-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
