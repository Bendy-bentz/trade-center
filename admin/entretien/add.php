<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();
$error = '';

$vehicules = $db->query("SELECT * FROM Vehicules ORDER BY marque, modele")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehicule = $_POST['id_vehicule'];
    $type_entretien = sanitize($_POST['type_entretien']);
    $description = sanitize($_POST['description']);
    $cout = floatval($_POST['cout']);
    $date_entretien = $_POST['date_entretien'];
    $kilometrage = intval($_POST['kilometrage']) ?: null;
    
    $db->beginTransaction();
    try {
        // Ajouter l'entretien
        $stmt = $db->prepare("INSERT INTO Entretien_Maintenance (id_vehicule, type_entretien, description, cout, date_entretien, kilometrage) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_vehicule, $type_entretien, $description, $cout, $date_entretien, $kilometrage]);
        
        // Mettre à jour le kilométrage du véhicule si fourni
        if ($kilometrage) {
            $stmt = $db->prepare("UPDATE Vehicules SET kilometrage = ? WHERE id_vehicule = ? AND (kilometrage IS NULL OR kilometrage < ?)");
            $stmt->execute([$kilometrage, $id_vehicule, $kilometrage]);
        }
        
        $db->commit();
        redirect('/admin/entretien/index.php');
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Erreur: ' . $e->getMessage();
    }
}

$pageTitle = 'Ajouter un Entretien';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="index.php" class="text-gray-500 hover:text-gray-700 mr-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Ajouter un Entretien</h2>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= $error ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Véhicule *</label>
                <select name="id_vehicule" required class="input-field">
                    <option value="">Sélectionner un véhicule</option>
                    <?php foreach ($vehicules as $v): ?>
                        <option value="<?= $v['id_vehicule'] ?>"><?= $v['marque'] ?> <?= $v['modele'] ?> - <?= $v['immatriculation'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type d'entretien *</label>
                    <select name="type_entretien" required class="input-field">
                        <option value="Vidange">Vidange</option>
                        <option value="Révision">Révision</option>
                        <option value="Réparation">Réparation</option>
                        <option value="Contrôle technique">Contrôle technique</option>
                        <option value="Pneumatiques">Pneumatiques</option>
                        <option value="Freins">Freins</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" name="date_entretien" value="<?= date('Y-m-d') ?>" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Coût (MAD) *</label>
                    <input type="number" name="cout" step="0.01" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kilométrage</label>
                    <input type="number" name="kilometrage" class="input-field">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="input-field"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <a href="index.php" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
