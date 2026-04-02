<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();

// Statistiques pour les rapports
$totalVehicules = $db->query("SELECT COUNT(*) FROM Vehicules")->fetchColumn();
$vehiculesDispo = $db->query("SELECT COUNT(*) FROM Vehicules WHERE etat = 'Disponible'")->fetchColumn();
$totalClients = $db->query("SELECT COUNT(*) FROM Clients")->fetchColumn();
$totalReservations = $db->query("SELECT COUNT(*) FROM Reservations")->fetchColumn();
$revenusTotaux = $db->query("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = TRUE")->fetchColumn();

// Réservations par mois
$resParMois = $db->query("SELECT MONTH(date_reservation) as mois, COUNT(*) as nb 
                          FROM Reservations 
                          WHERE YEAR(date_reservation) = YEAR(CURRENT_DATE()) 
                          GROUP BY MONTH(date_reservation) 
                          ORDER BY mois")->fetchAll();

$pageTitle = 'Rapports';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Rapports & Statistiques</h2>
</div>

<!-- Stats globales -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm">Véhicules</p>
        <p class="text-2xl font-bold text-gray-800"><?= $totalVehicules ?></p>
        <p class="text-green-600 text-sm"><?= $vehiculesDispo ?> disponibles</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm">Clients</p>
        <p class="text-2xl font-bold text-gray-800"><?= $totalClients ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm">Réservations</p>
        <p class="text-2xl font-bold text-gray-800"><?= $totalReservations ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm">Revenus totaux</p>
        <p class="text-2xl font-bold text-green-600"><?= formatPrice($revenusTotaux) ?></p>
    </div>
</div>

<!-- Rapports -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Réservations par statut -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Réservations par statut</h3>
        <?php
        $statuts = $db->query("SELECT statut, COUNT(*) as nb FROM Reservations GROUP BY statut")->fetchAll();
        $couleurs = [
            'En attente' => 'bg-yellow-500',
            'Confirmée' => 'bg-green-500',
            'En cours' => 'bg-blue-500',
            'Terminée' => 'bg-gray-500',
            'Annulée' => 'bg-red-500'
        ];
        ?>
        <?php foreach ($statuts as $s): ?>
        <div class="flex items-center mb-2">
            <span class="w-3 h-3 rounded-full <?= $couleurs[$s['statut']] ?? 'bg-gray-400' ?> mr-2"></span>
            <span class="flex-1"><?= $s['statut'] ?></span>
            <span class="font-medium"><?= $s['nb'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Véhicules par catégorie -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Véhicules par catégorie</h3>
        <?php
        $categories = $db->query("SELECT c.nom_categorie, COUNT(v.id_vehicule) as nb 
                                  FROM Categories_Vehicules c 
                                  LEFT JOIN Vehicules v ON c.id_categorie = v.id_categorie 
                                  GROUP BY c.id_categorie")->fetchAll();
        ?>
        <?php foreach ($categories as $cat): ?>
        <div class="flex items-center justify-between mb-2">
            <span><?= $cat['nom_categorie'] ?></span>
            <span class="font-medium"><?= $cat['nb'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Top véhicules -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Véhicules les plus loués</h3>
        <?php
        $topVehicules = $db->query("SELECT v.marque, v.modele, COUNT(r.id_reservation) as nb 
                                    FROM Vehicules v 
                                    JOIN Reservations r ON v.id_vehicule = r.id_vehicule 
                                    GROUP BY v.id_vehicule 
                                    ORDER BY nb DESC LIMIT 5")->fetchAll();
        ?>
        <?php if (empty($topVehicules)): ?>
            <p class="text-gray-500">Aucune donnée</p>
        <?php else: ?>
            <?php foreach ($topVehicules as $v): ?>
            <div class="flex items-center justify-between mb-2">
                <span><?= $v['marque'] ?> <?= $v['modele'] ?></span>
                <span class="font-medium"><?= $v['nb'] ?> locations</span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Revenus récents -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Derniers paiements reçus</h3>
        <?php
        $derniersPaiements = $db->query("SELECT p.montant, p.date_paiement, c.nom 
                                         FROM Paiements p 
                                         JOIN Contrats ct ON p.id_contrat = ct.id_contrat 
                                         JOIN Reservations r ON ct.id_reservation = r.id_reservation 
                                         JOIN Clients c ON r.id_client = c.id_client 
                                         WHERE p.recu = TRUE 
                                         ORDER BY p.date_paiement DESC LIMIT 5")->fetchAll();
        ?>
        <?php if (empty($derniersPaiements)): ?>
            <p class="text-gray-500">Aucun paiement</p>
        <?php else: ?>
            <?php foreach ($derniersPaiements as $p): ?>
            <div class="flex items-center justify-between mb-2">
                <span><?= $p['nom'] ?> - <?= formatDateTime($p['date_paiement']) ?></span>
                <span class="font-medium text-green-600"><?= formatPrice($p['montant']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Export -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Exporter les données</h3>
    <div class="flex flex-wrap gap-4">
        <button class="btn-secondary">Exporter réservations (CSV)</button>
        <button class="btn-secondary">Exporter clients (CSV)</button>
        <button class="btn-secondary">Exporter paiements (CSV)</button>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
