<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

// Récupérer tous les entretiens
$entretiens = $db->query("SELECT e.*, v.marque, v.modele, v.immatriculation 
                          FROM Entretien_Maintenance e 
                          JOIN Vehicules v ON e.id_vehicule = v.id_vehicule 
                          ORDER BY e.date_entretien DESC")->fetchAll();

$totalCout = $db->query("SELECT COALESCE(SUM(cout), 0) FROM Entretien_Maintenance")->fetchColumn();

$pageTitle = 'Entretien';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<!-- Stats -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <p class="text-gray-500 text-sm">Coût total des entretiens</p>
    <p class="text-2xl font-bold text-blue-600"><?= formatPrice($totalCout) ?></p>
</div>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Historique d'entretien</h2>
    <a href="add.php" class="btn-primary">+ Nouvel entretien</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if (empty($entretiens)): ?>
        <p class="text-gray-500 text-center py-8">Aucun entretien enregistré.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Véhicule</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Garage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coût</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prochaine</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($entretiens as $e): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="font-medium"><?= $e['marque'] ?> <?= $e['modele'] ?></p>
                            <p class="text-gray-500 text-sm"><?= $e['immatriculation'] ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $e['type'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= formatDate($e['date_entretien']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $e['garage'] ?? '-' ?></td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium"><?= formatPrice($e['cout']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?= $e['prochaine_revision'] ? formatDate($e['prochaine_revision']) : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>