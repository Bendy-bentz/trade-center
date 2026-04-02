<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

// Récupérer tous les contrats
$contrats = $db->query("SELECT c.*, r.reference as reservation_ref, r.date_debut, r.date_fin,
                        cl.nom as client_nom, cl.prenom as client_prenom,
                        v.marque, v.modele, v.immatriculation
                        FROM Contrats c 
                        JOIN Reservations r ON c.id_reservation = r.id_reservation
                        JOIN Clients cl ON r.id_client = cl.id_client
                        JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
                        ORDER BY c.date_creation DESC")->fetchAll();

$pageTitle = 'Contrats';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Contrats de location</h2>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if (empty($contrats)): ?>
        <p class="text-gray-500 text-center py-8">Aucun contrat pour le moment.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Contrat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Véhicule</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($contrats as $c): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-medium"><?= $c['numero_contrat'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $c['client_prenom'] ?> <?= $c['client_nom'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $c['marque'] ?> <?= $c['modele'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?= formatDate($c['date_debut']) ?> → <?= formatDate($c['date_fin']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium"><?= formatPrice($c['montant_total']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($c['date_signature']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Signé</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="<?= BASE_URL ?>/admin/contrats/view.php?id=<?= $c['id_contrat'] ?>" class="text-blue-600 hover:underline">Voir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
