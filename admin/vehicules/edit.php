<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM Vehicules WHERE id_vehicule = ?");
$stmt->execute([$id]);
$vehicule = $stmt->fetch();

if (!$vehicule) redirect('/admin/vehicules/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE Vehicules SET marque=?, modele=?, immatriculation=?, annee=?, couleur=?, carburant=?, transmission=?, nb_places=?, prix_jour=?, id_categorie=?, etat=?, est_vedette=? WHERE id_vehicule=?");
    $stmt->execute([
        sanitize($_POST['marque']),
        sanitize($_POST['modele']),
        sanitize($_POST['immatriculation']),
        !empty($_POST['annee']) ? (int)$_POST['annee'] : null,
        sanitize($_POST['couleur'] ?? ''),
        $_POST['carburant'],
        $_POST['transmission'],
        (int)$_POST['nb_places'],
        (float)$_POST['prix_jour'],
        (int)$_POST['id_categorie'],
        $_POST['etat'],
        isset($_POST['est_vedette']) ? 1 : 0,
        $id
    ]);
    redirect('/admin/vehicules/index.php?msg=updated');
}

$categories = $db->query("SELECT * FROM Categories_Vehicules ORDER BY nom_categorie")->fetchAll();

$pageTitle = 'Modifier le véhicule';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Modifier le véhicule</h2>
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marque *</label>
                    <input type="text" name="marque" value="<?= $vehicule['marque'] ?>" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modèle *</label>
                    <input type="text" name="modele" value="<?= $vehicule['modele'] ?>" required class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Immatriculation *</label>
                    <input type="text" name="immatriculation" value="<?= $vehicule['immatriculation'] ?>" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Année</label>
                    <input type="number" name="annee" value="<?= $vehicule['annee'] ?>" min="1990" max="2030" class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
                    <input type="text" name="couleur" value="<?= $vehicule['couleur'] ?>" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carburant</label>
                    <select name="carburant" class="input-field">
                        <?php foreach (['Essence','Diesel','Électrique','Hybride'] as $opt): ?>
                        <option <?= $vehicule['carburant']==$opt?'selected':'' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transmission</label>
                    <select name="transmission" class="input-field">
                        <option <?= $vehicule['transmission']=='Manuelle'?'selected':'' ?>>Manuelle</option>
                        <option <?= $vehicule['transmission']=='Automatique'?'selected':'' ?>>Automatique</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Places</label>
                    <input type="number" name="nb_places" value="<?= $vehicule['nb_places'] ?>" min="2" max="9" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix/Jour *</label>
                    <input type="number" name="prix_jour" value="<?= $vehicule['prix_jour'] ?>" step="0.01" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                    <select name="id_categorie" required class="input-field">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id_categorie'] ?>" <?= $vehicule['id_categorie']==$c['id_categorie']?'selected':'' ?>><?= $c['nom_categorie'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">État</label>
                    <select name="etat" class="input-field">
                        <?php foreach (['Disponible','Loué','En maintenance'] as $opt): ?>
                        <option <?= $vehicule['etat']==$opt?'selected':'' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="est_vedette" <?= !empty($vehicule['est_vedette'])?'checked':'' ?> class="mr-2">
                        <span class="text-sm text-gray-700">Véhicule vedette</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <a href="index.php" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
