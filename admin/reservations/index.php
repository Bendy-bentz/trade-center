<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

 $db = getDB();

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Libérer le véhicule si nécessaire
    $stmt = $db->prepare("SELECT id_vehicule, statut FROM Reservations WHERE id_reservation = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if ($res && in_array($res['statut'], ['Confirmée', 'En cours'])) {
        $db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")->execute([$res['id_vehicule']]);
    }
    $db->prepare("DELETE FROM Reservations WHERE id_reservation = ?")->execute([$id]);
    redirect('/admin/reservations/index.php?msg=deleted');
}

// Changement de statut rapide
if (isset($_GET['statut']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatut = $_GET['statut'];
    
    // Récupérer la réservation
    $stmt = $db->prepare("SELECT id_vehicule, statut FROM Reservations WHERE id_reservation = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    
    if ($res) {
        // Mettre à jour le statut
        $db->prepare("UPDATE Reservations SET statut = ? WHERE id_reservation = ?")->execute([$newStatut, $id]);
        
        // Mettre à jour l'état du véhicule
        if (in_array($newStatut, ['En cours', 'Confirmée'])) {
            $db->prepare("UPDATE Vehicules SET etat = 'Loué' WHERE id_vehicule = ?")->execute([$res['id_vehicule']]);
        } elseif (in_array($newStatut, ['Terminée', 'Annulée'])) {
            $db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")->execute([$res['id_vehicule']]);
        }
    }
    redirect('/admin/reservations/index.php?msg=updated');
}

// Filtre
 $filtre = $_GET['filtre'] ?? null;
 $sql = "SELECT r.*, c.prenom, c.nom as client_nom, c.telephone, v.marque, v.modele, v.immatriculation, v.prix_jour 
        FROM Reservations r 
        JOIN Clients c ON r.id_client = c.id_client 
        JOIN Vehicules v ON r.id_vehicule = v.id_vehicule";

if ($filtre) {
    $stmt = $db->prepare($sql . " WHERE r.statut = ? ORDER BY r.date_reservation DESC");
    $stmt->execute([$filtre]);
    $reservations = $stmt->fetchAll();
} else {
    $reservations = $db->query($sql . " ORDER BY r.date_reservation DESC")->fetchAll();
}

 $pageTitle = 'Gestion des Réservations';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<?php if (isset($_GET['msg'])): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center">
    <i class="fas fa-check-circle mr-2"></i> Opération réussie
</div>
<?php endif; ?>

<div class="mb-6 flex justify-between items-center flex-wrap gap-4">
    <div class="flex gap-2 flex-wrap">
        <a href="?filtre=En attente" class="px-4 py-2 rounded-lg text-xs font-bold transition <?= $filtre=='En attente'?'bg-yellow-500 text-white shadow':'bg-white hover:bg-gray-50 border' ?>">En attente</a>
        <a href="?filtre=Confirmée" class="px-4 py-2 rounded-lg text-xs font-bold transition <?= $filtre=='Confirmée'?'bg-green-500 text-white shadow':'bg-white hover:bg-gray-50 border' ?>">Confirmées</a>
        <a href="?filtre=En cours" class="px-4 py-2 rounded-lg text-xs font-bold transition <?= $filtre=='En cours'?'bg-blue-500 text-white shadow':'bg-white hover:bg-gray-50 border' ?>">En cours</a>
        <a href="?filtre=Terminée" class="px-4 py-2 rounded-lg text-xs font-bold transition <?= $filtre=='Terminée'?'bg-gray-500 text-white shadow':'bg-white hover:bg-gray-50 border' ?>">Terminées</a>
        <a href="index.php" class="px-4 py-2 rounded-lg text-xs font-bold bg-white hover:bg-gray-50 border">Toutes</a>
    </div>
    <a href="add.php" class="bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold transition inline-flex items-center gap-2">
        <i class="fas fa-plus"></i> Nouvelle réservation
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Référence</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Véhicule</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dates</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 bg-white">
            <?php foreach ($reservations as $r): ?>
            <?php $nbJours = max(1, (strtotime($r['date_fin']) - strtotime($r['date_debut'])) / 86400); ?>
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-mono text-sm text-gray-500">#<?= str_pad($r['id_reservation'], 5, '0', STR_PAD_LEFT) ?></td>
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-900"><?= $r['prenom'] ?> <?= $r['client_nom'] ?></div>
                    <div class="text-xs text-gray-400"><?= $r['telephone'] ?></div>
                </td>
                <td class="px-6 py-4 text-gray-700">
                    <div class="font-medium"><?= $r['marque'] ?> <?= $r['modele'] ?></div>
                    <div class="text-xs text-gray-400"><?= $r['immatriculation'] ?></div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div><?= formatDate($r['date_debut']) ?> → <?= formatDate($r['date_fin']) ?></div>
                    <div class="text-xs text-gray-400">(<?= $nbJours ?>j)</div>
                </td>
                <td class="px-6 py-4 font-bold text-gray-800"><?= formatPrice($nbJours * $r['prix_jour']) ?></td>
                <td class="px-6 py-4">
                    <?php
                    $statusClass = 'bg-yellow-100 text-yellow-700';
                    if ($r['statut'] == 'Confirmée') $statusClass = 'bg-green-100 text-green-700';
                    if ($r['statut'] == 'En cours') $statusClass = 'bg-blue-100 text-blue-700';
                    if ($r['statut'] == 'Terminée') $statusClass = 'bg-gray-100 text-gray-700';
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusClass ?>">
                        <?= $r['statut'] ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <!-- Changements de statut rapides (Texte) -->
                        <?php if ($r['statut'] == 'En attente'): ?>
                        <a href="?statut=Confirmée&id=<?= $r['id_reservation'] ?>" class="text-green-600 hover:text-green-800 text-xs font-medium">Confirmer</a>
                        <?php endif; ?>
                        <?php if ($r['statut'] == 'Confirmée'): ?>
                        <a href="?statut=En cours&id=<?= $r['id_reservation'] ?>" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Démarrer</a>
                        <?php endif; ?>
                        <?php if ($r['statut'] == 'En cours'): ?>
                        <a href="?statut=Terminée&id=<?= $r['id_reservation'] ?>" class="text-gray-600 hover:text-gray-800 text-xs font-medium">Terminer</a>
                        <?php endif; ?>

                        <!-- Séparateur si besoin -->
                        <?php if (in_array($r['statut'], ['En attente', 'Confirmée', 'En cours'])): ?>
                        <span class="text-gray-300">|</span>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/admin/contrats/view.php?id=<?= $r['id_reservation'] ?>" target="_blank" class="text-red-500 hover:text-red-700 p-2" title="Voir / Imprimer Contrat">
                         <i class="fas fa-file-pdf text-lg"></i>
                        </a>
                        <!-- Edit & Delete (Icônes) -->
                        <a href="edit.php?id=<?= $r['id_reservation'] ?>" class="text-gray-400 hover:text-blue-600 transition p-1" title="Modifier">
                            <i class="fas fa-edit text-lg"></i>
                        </a>
                        <a href="?delete=<?= $r['id_reservation'] ?>" onclick="return confirm('Supprimer cette réservation ?')" class="text-gray-400 hover:text-red-600 transition p-1" title="Supprimer">
                            <i class="fas fa-trash text-lg"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>