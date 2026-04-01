<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

// Suppression
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM Vehicules WHERE id_vehicule = ?");
    $stmt->execute([(int)$_GET['delete']]);
    redirect('/admin/vehicules/index.php?msg=deleted');
}

// Récupérer les véhicules avec filtre
$filtre = $_GET['filtre'] ?? null;
$sql = "SELECT v.*, c.nom_categorie FROM Vehicules v 
        LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie";

if ($filtre === 'disponibles') {
    $sql .= " WHERE v.etat = 'Disponible'";
} elseif ($filtre === 'loues') {
    $sql .= " WHERE v.etat = 'Loué'";
} elseif ($filtre === 'maintenance') {
    $sql .= " WHERE v.etat = 'En maintenance'";
}

$sql .= " ORDER BY v.id_vehicule DESC";
$vehicules = $db->query($sql)->fetchAll();

$pageTitle = 'Gestion des Véhicules';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<?php if (isset($_GET['msg'])): ?>
<div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 flex items-center gap-2">
    <i class="fas fa-check-circle"></i>
    Opération réussie
</div>
<?php endif; ?>

<!-- Filtres et Actions -->
<div class="mb-6 flex justify-between items-center flex-wrap gap-4">
    <div class="flex gap-2 flex-wrap">
        <a href="?filtre=disponibles" class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 <?= $filtre=='disponibles'?'bg-green-500 text-white shadow-lg':'bg-white hover:bg-gray-100 text-gray-700 border' ?>">
            <span class="w-2 h-2 bg-green-400 rounded-full"></span>
            Disponibles
        </a>
        <a href="?filtre=loues" class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 <?= $filtre=='loues'?'bg-blue-500 text-white shadow-lg':'bg-white hover:bg-gray-100 text-gray-700 border' ?>">
            <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
            Loués
        </a>
        <a href="?filtre=maintenance" class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 <?= $filtre=='maintenance'?'bg-yellow-500 text-white shadow-lg':'bg-white hover:bg-gray-100 text-gray-700 border' ?>">
            <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
            En maintenance
        </a>
        <a href="index.php" class="px-4 py-2 rounded-lg text-sm font-medium transition bg-white hover:bg-gray-100 text-gray-700 border">
            Tous
        </a>
    </div>
    <a href="add.php" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-lg font-semibold transition flex items-center gap-2 shadow-lg shadow-orange-500/30">
        <i class="fas fa-plus"></i>
        Ajouter un véhicule
    </a>
</div>

<!-- Statistiques rapides -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN etat = 'Disponible' THEN 1 ELSE 0 END) as dispo,
        SUM(CASE WHEN etat = 'Loué' THEN 1 ELSE 0 END) as loues,
        SUM(CASE WHEN etat = 'En maintenance' THEN 1 ELSE 0 END) as maintenance
        FROM Vehicules")->fetch();
    ?>
    <div class="bg-white p-4 rounded-lg border flex items-center gap-3">
        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-car text-gray-500"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
            <p class="text-xs text-gray-500">Total véhicules</p>
        </div>
    </div>
    <div class="bg-white p-4 rounded-lg border flex items-center gap-3">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-check text-green-500"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-green-600"><?= $stats['dispo'] ?></p>
            <p class="text-xs text-gray-500">Disponibles</p>
        </div>
    </div>
    <div class="bg-white p-4 rounded-lg border flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-key text-blue-500"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-blue-600"><?= $stats['loues'] ?></p>
            <p class="text-xs text-gray-500">En location</p>
        </div>
    </div>
    <div class="bg-white p-4 rounded-lg border flex items-center gap-3">
        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-tools text-yellow-500"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-yellow-600"><?= $stats['maintenance'] ?></p>
            <p class="text-xs text-gray-500">En maintenance</p>
        </div>
    </div>
</div>

<!-- Grille des véhicules -->
<?php if (empty($vehicules)): ?>
<div class="bg-white rounded-lg shadow p-12 text-center">
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-car text-gray-400 text-3xl"></i>
    </div>
    <p class="text-gray-500 text-lg">Aucun véhicule trouvé</p>
    <a href="add.php" class="inline-block mt-4 bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-lg font-semibold transition">
        Ajouter un véhicule
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($vehicules as $v): ?>
    <?php 
        $statusClass = $v['etat'] == 'Disponible' ? 'bg-green-100 text-green-700' : 
                      ($v['etat'] == 'Loué' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700');
        $statusDot = $v['etat'] == 'Disponible' ? 'bg-green-500' : 
                    ($v['etat'] == 'Loué' ? 'bg-blue-500' : 'bg-yellow-500');
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group">
        <!-- Image -->
        <div class="relative bg-gray-100 h-44">
            <?php if (!empty($v['image'])): ?>
                <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($v['image']) ?>" 
                     alt="<?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?>" 
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                     onerror="this.parentElement.innerHTML = '<div class=\'w-full h-44 flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200\'><i class=\'fas fa-car text-5xl text-gray-300\'></i></div>';">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                    <i class="fas fa-car text-5xl text-gray-300"></i>
                </div>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <span class="absolute top-3 right-3 <?= $statusClass ?> px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 <?= $statusDot ?> rounded-full"></span>
                <?= $v['etat'] ?>
            </span>
            
            <!-- Vedette Badge -->
            <?php if (!empty($v['est_vedette'])): ?>
            <span class="absolute top-3 left-3 bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-bold">
                ⭐ Vedette
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Content -->
        <div class="p-4">
            <!-- Title & Category -->
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-bold text-gray-800"><?= $v['marque'] ?> <?= $v['modele'] ?></h4>
                    <p class="text-xs text-gray-500"><?= $v['immatriculation'] ?> • <?= $v['annee'] ?? 'N/A' ?></p>
                </div>
                <span class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded-full">
                    <?= $v['nom_categorie'] ?? 'Standard' ?>
                </span>
            </div>
            
            <!-- Specs -->
            <div class="flex items-center gap-3 mt-3 text-xs text-gray-500">
                <span class="flex items-center gap-1">
                    <i class="fas fa-users text-orange-400"></i> <?= $v['nb_places'] ?>
                </span>
                <span class="flex items-center gap-1">
                    <i class="fas fa-gas-pump text-orange-400"></i> <?= substr($v['carburant'], 0, 5) ?>
                </span>
                <span class="flex items-center gap-1">
                    <i class="fas fa-cog text-orange-400"></i> <?= substr($v['transmission'], 0, 4) ?>
                </span>
            </div>
            
            <!-- Price -->
            <div class="mt-3 pt-3 border-t border-gray-100">
                <span class="text-lg font-bold text-orange-500"><?= formatPrice($v['prix_jour']) ?></span>
                <span class="text-gray-400 text-xs">/jour</span>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-2 mt-4">
                <a href="edit.php?id=<?= $v['id_vehicule'] ?>" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-600 text-center py-2 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-edit mr-1"></i> Modifier
                </a>
                <a href="?delete=<?= $v['id_vehicule'] ?>" 
                   onclick="return confirm('Supprimer ce véhicule ?')" 
                   class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 text-center py-2 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-trash mr-1"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
