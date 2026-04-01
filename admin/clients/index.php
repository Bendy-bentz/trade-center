<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

// Message de succès
$msg = $_GET['msg'] ?? '';
$successMessage = '';
if ($msg === 'created') {
    $successMessage = 'Client ajouté avec succès!';
}

// Récupérer tous les clients avec leur info compte
$clients = $db->query("SELECT c.*, u.role, u.photo,
                       COUNT(r.id_reservation) as nb_reservations,
                       SUM(CASE WHEN r.statut IN ('En cours', 'Confirmée') THEN 1 ELSE 0 END) as reservations_actives
                       FROM Clients c 
                       LEFT JOIN Reservations r ON c.id_client = r.id_client 
                       LEFT JOIN Utilisateurs u ON c.id_utilisateur = u.id_utilisateur
                       GROUP BY c.id_client 
                       ORDER BY c.date_inscription DESC")->fetchAll();

// Statistiques
$totalClients = count($clients);
$clientsAvecCompte = count(array_filter($clients, fn($c) => !empty($c['role'])));
$clientsActifs = count(array_filter($clients, fn($c) => $c['reservations_actives'] > 0));

$pageTitle = 'Clients';
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
        <h2 class="text-2xl font-bold text-gray-800">Gestion des clients</h2>
        <p class="text-gray-500 text-sm mt-1">Gérez les informations de vos clients</p>
    </div>
    <a href="add.php" class="btn-primary inline-flex items-center gap-2">
        <i class="fas fa-plus"></i> Nouveau client
    </a>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-500"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $totalClients ?></p>
                <p class="text-sm text-gray-500">Total clients</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-check text-green-500"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $clientsAvecCompte ?></p>
                <p class="text-sm text-gray-500">Avec compte</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-car text-orange-500"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $clientsActifs ?></p>
                <p class="text-sm text-gray-500">Avec réservations actives</p>
            </div>
        </div>
    </div>
</div>

<!-- Note de sécurité -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start gap-3">
        <i class="fas fa-shield-alt text-blue-500 mt-1"></i>
        <div class="text-sm text-blue-700">
            <strong>Protection de la vie privée:</strong> Les mots de passe des clients sont protégés et ne sont pas accessibles par l'administration.
            Les clients gèrent leurs propres mots de passe via leur profil ou la fonctionnalité de récupération.
        </div>
    </div>
</div>

<!-- Liste des clients -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if (empty($clients)): ?>
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-3xl text-gray-400"></i>
            </div>
            <p class="text-gray-500 mb-4">Aucun client enregistré.</p>
            <a href="add.php" class="btn-primary inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Ajouter un client
            </a>
        </div>
    <?php else: ?>
        <!-- Vue bureau -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ville</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Réservations</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Compte</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($clients as $c): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($c['photo'])): ?>
                                    <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $c['photo'] ?>" 
                                         class="w-10 h-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-semibold text-sm">
                                        <?= strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></p>
                                    <p class="text-xs text-gray-500">Client #<?= $c['id_client'] ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-800"><?= htmlspecialchars($c['telephone']) ?></p>
                            <?php if (!empty($c['email'])): ?>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($c['email']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= htmlspecialchars($c['ville'] ?? '-') ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                                    <?= $c['nb_reservations'] ?> total
                                </span>
                                <?php if ($c['reservations_actives'] > 0): ?>
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                    <?= $c['reservations_actives'] ?> active<?= $c['reservations_actives'] > 1 ? 's' : '' ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if (!empty($c['role'])): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                <i class="fas fa-check-circle"></i> Actif
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                                <i class="fas fa-user"></i> Sans compte
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="edit.php?id=<?= $c['id_client'] ?>" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                   title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../reservations/add.php?client=<?= $c['id_client'] ?>" 
                                   class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" 
                                   title="Nouvelle réservation">
                                    <i class="fas fa-plus-circle"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Vue mobile -->
        <div class="md:hidden divide-y divide-gray-200">
            <?php foreach ($clients as $c): ?>
            <div class="p-4">
                <div class="flex items-center gap-3 mb-3">
                    <?php if (!empty($c['photo'])): ?>
                        <img src="<?= BASE_URL ?>/assets/images/profiles/<?= $c['photo'] ?>" 
                             class="w-12 h-12 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-semibold">
                            <?= strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($c['telephone']) ?></p>
                    </div>
                    <?php if (!empty($c['role'])): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">
                        <i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($c['ville'] ?? '-') ?>
                    </span>
                    <span class="text-gray-500">
                        <i class="fas fa-car mr-1"></i> <?= $c['nb_reservations'] ?> réservation<?= $c['nb_reservations'] > 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="flex gap-2 mt-3 pt-3 border-t">
                    <a href="edit.php?id=<?= $c['id_client'] ?>" class="flex-1 btn-primary text-center text-sm py-2">
                        <i class="fas fa-edit mr-1"></i> Modifier
                    </a>
                    <a href="../reservations/add.php?client=<?= $c['id_client'] ?>" class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center rounded-lg text-sm py-2">
                        <i class="fas fa-plus mr-1"></i> Réservation
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
