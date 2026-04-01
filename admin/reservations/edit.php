<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

 $db = getDB();
 $id = (int)($_GET['id'] ?? 0);

 $r = $db->prepare("SELECT * FROM Reservations WHERE id_reservation = ?");
 $r->execute([$id]);
 $r = $r->fetch();

if (!$r) redirect('/admin/reservations/index.php');

 $clients = $db->query("SELECT id_client, nom, prenom FROM Clients ORDER BY nom")->fetchAll();
 $vehicules = $db->query("SELECT id_vehicule, marque, modele, immatriculation FROM Vehicules")->fetchAll();

 $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['statut'];
    $oldStatus = $r['statut'];
    $idVehicule = (int)$_POST['id_vehicule'];
    $dateDebut = $_POST['date_debut'];
    $dateFin = $_POST['date_fin'];
    $idClient = (int)$_POST['id_client'];
    $notes = sanitize($_POST['notes'] ?? '');

    // Vérifier la disponibilité (exclure la réservation actuelle)
    $stmt = $db->prepare("SELECT COUNT(*) FROM Reservations 
        WHERE id_vehicule = ? AND id_reservation != ? AND statut IN ('En attente', 'Confirmée', 'En cours')
        AND (
            (? BETWEEN date_debut AND date_fin) 
            OR (? BETWEEN date_debut AND date_fin) 
            OR (date_debut BETWEEN ? AND ?)
            OR (date_fin BETWEEN ? AND ?)
        )");
    $stmt->execute([$idVehicule, $id, $dateDebut, $dateFin, $dateDebut, $dateFin, $dateDebut, $dateFin]);
    
    if ($stmt->fetchColumn() > 0) {
        $error = "Ce véhicule n'est pas disponible pour ces dates.";
    } else {
        // Recalculer le prix total
        $vehiculeInfo = $db->prepare("SELECT prix_jour FROM Vehicules WHERE id_vehicule = ?");
        $vehiculeInfo->execute([$idVehicule]);
        $prixJour = $vehiculeInfo->fetchColumn();
        
        $nbJours = max(1, (strtotime($dateFin) - strtotime($dateDebut)) / 86400);
        $prixTotal = $nbJours * $prixJour;

        // Mise à jour
        $stmt = $db->prepare("UPDATE Reservations SET id_client=?, id_vehicule=?, date_debut=?, date_fin=?, statut=?, notes=?, prix_total=? WHERE id_reservation=?");
        $stmt->execute([
            $idClient, $idVehicule,
            $dateDebut, $dateFin, $newStatus, $notes, $prixTotal, $id
        ]);
        
        // Gestion état véhicule
        // Si on passe à Terminée ou Annulée -> Disponible
        if (in_array($newStatus, ['Terminée', 'Annulée'])) {
            $db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")->execute([$idVehicule]);
        } 
        // Si on passe à Confirmée ou En cours -> Loué
        elseif (in_array($newStatus, ['En cours', 'Confirmée'])) {
            $db->prepare("UPDATE Vehicules SET etat = 'Loué' WHERE id_vehicule = ?")->execute([$idVehicule]);
        }
        // Si on revient à En attente -> Disponible (optionnel, selon votre logique)
        elseif ($newStatus == 'En attente') {
             $db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")->execute([$idVehicule]);
        }

        redirect('/admin/reservations/index.php?msg=updated');
    }
}

 $pageTitle = 'Modifier la réservation';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="max-w-2xl mx-auto">
    <?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= $error ?>
    </div>
    <?php endif; ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
                    <select name="id_client" required class="w-full border-gray-300 rounded-lg p-2.5">
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id_client'] ?>" <?= $r['id_client']==$c['id_client']?'selected':'' ?>><?= $c['prenom'] ?> <?= $c['nom'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule *</label>
                    <select name="id_vehicule" required class="w-full border-gray-300 rounded-lg p-2.5">
                        <?php foreach ($vehicules as $v): ?>
                        <option value="<?= $v['id_vehicule'] ?>" <?= $r['id_vehicule']==$v['id_vehicule']?'selected':'' ?>><?= $v['marque'] ?> <?= $v['modele'] ?> (<?= $v['immatriculation'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début *</label>
                    <input type="date" name="date_debut" value="<?= $r['date_debut'] ?>" required class="w-full border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin *</label>
                    <input type="date" name="date_fin" value="<?= $r['date_fin'] ?>" required class="w-full border-gray-300 rounded-lg p-2.5">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="statut" class="w-full border-gray-300 rounded-lg p-2.5">
                    <?php foreach (['En attente','Confirmée','En cours','Terminée','Annulée'] as $s): ?>
                    <option <?= $r['statut']==$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg p-2.5"><?= htmlspecialchars($r['notes']) ?></textarea>
            </div>
             <!-- Indication prix actuel -->
             <div class="bg-gray-50 p-3 rounded-lg text-sm text-gray-600">
                Prix actuel en base : <strong class="text-orange-600"><?= number_format($r['prix_total'], 0, ',', ' ') ?> MAD</strong>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <a href="index.php" class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition">Annuler</a>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold transition">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>