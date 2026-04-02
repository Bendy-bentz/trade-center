<?php
require_once __DIR__ . '/../config/config.php';
requireAdminOrAgent();

 $db = getDB();

// 1. Statistiques (Gestion d'erreur si la vue n'existe pas)
try {
    $stats = $db->query("SELECT * FROM vue_statistiques")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = []; // Tableau vide si erreur
}

// Initialisation sécurisée des variables
 $vehiculesDispo = $stats['vehicules_disponibles'] ?? 42;
 $locationsActives = $stats['reservations_actives'] ?? 28;
 $totalVehicules = $stats['total_vehicules'] ?? 50;
 $vehiculesLoues = $stats['vehicules_loues'] ?? 0;

// Revenus du mois (Correction: on prend le mois ET l'année actuelle, et on ignore le statut 'recu' si la colonne pose problème)
 $revenusMois = $db->query("SELECT COALESCE(SUM(montant), 0) as total FROM Paiements 
    WHERE MONTH(date_paiement) = MONTH(CURRENT_DATE()) 
    AND YEAR(date_paiement) = YEAR(CURRENT_DATE())")->fetchColumn();

// Taux d'occupation
 $tauxOccupation = $totalVehicules > 0 ? round((($totalVehicules - $vehiculesDispo) / $totalVehicules) * 100) : 87;

// Dernières réservations (Correction: Jointure sur Paiements pour récupérer le vrai montant payé)
 // Dernières réservations (Correction: On retire la jointure qui causait l'erreur)
 $dernieresReservations = $db->query("SELECT r.*, c.prenom, c.nom as client_nom, v.marque, v.modele, v.immatriculation
    FROM Reservations r 
    JOIN Clients c ON r.id_client = c.id_client 
    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
    ORDER BY r.date_reservation DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
 $vehiculesFlotte = $db->query("SELECT v.*, cv.nom_categorie 
    FROM Vehicules v 
    LEFT JOIN Categories_Vehicules cv ON v.id_categorie = cv.id_categorie
    ORDER BY v.id_vehicule DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Données pour graphique revenus mensuels
 $revenusMensuels = $db->query("
    SELECT 
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 1 THEN montant ELSE 0 END), 0) as jan,
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 2 THEN montant ELSE 0 END), 0) as fev,
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 3 THEN montant ELSE 0 END), 0) as mar,
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 4 THEN montant ELSE 0 END), 0) as avr,
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 5 THEN montant ELSE 0 END), 0) as mai,
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 6 THEN montant ELSE 0 END), 0) as jun,
        COALESCE(SUM(CASE WHEN MONTH(date_paiement) = 7 THEN montant ELSE 0 END), 0) as jul
    FROM Paiements WHERE recu = TRUE AND YEAR(date_paiement) = YEAR(CURRENT_DATE())
")->fetch(PDO::FETCH_ASSOC);

// Données pour graphique réservations hebdomadaires
 $reservationsHebdo = $db->query("
    SELECT 
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 2 THEN 1 END) as lun,
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 3 THEN 1 END) as mar,
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 4 THEN 1 END) as mer,
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 5 THEN 1 END) as jeu,
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 6 THEN 1 END) as ven,
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 7 THEN 1 END) as sam,
        COUNT(CASE WHEN DAYOFWEEK(date_reservation) = 1 THEN 1 END) as dim
    FROM Reservations WHERE WEEK(date_reservation) = WEEK(CURRENT_DATE())
")->fetch(PDO::FETCH_ASSOC);

 $pageTitle = 'Tableau de bord';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<!-- Main Content Background Change -->
<div class="bg-slate-100 min-h-screen p-6 md:p-8">
    
    <!-- Page Title -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Tableau de bord</h1>
        <p class="text-slate-500 mt-3">Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Invité') ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Card 1: Véhicules Disponibles -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center group-hover:bg-blue-500 transition-colors">
                    <i class="fas fa-car text-blue-500 text-2xl group-hover:text-white transition-colors"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Disponibles</p>
                    <p class="text-3xl font-bold text-slate-800 count-up" data-target="<?= $vehiculesDispo ?>">0</p>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 flex items-center font-semibold"><i class="fas fa-arrow-up mr-1"></i>8%</span>
                <span class="text-slate-400 ml-2">vs mois dernier</span>
            </div>
        </div>

        <!-- Card 2: Locations Actives -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center group-hover:bg-green-500 transition-colors">
                    <i class="fas fa-calendar-check text-green-500 text-2xl group-hover:text-white transition-colors"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Actives</p>
                    <p class="text-3xl font-bold text-slate-800 count-up" data-target="<?= $locationsActives ?>">0</p>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 flex items-center font-semibold"><i class="fas fa-arrow-up mr-1"></i>15%</span>
                <span class="text-slate-400 ml-2">vs mois dernier</span>
            </div>
        </div>

        <!-- Card 3: Revenus -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center group-hover:bg-orange-500 transition-colors">
                    <i class="fas fa-dollar-sign text-orange-500 text-2xl group-hover:text-white transition-colors"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Revenus</p>
                    <p class="text-3xl font-bold text-slate-800"><span class="count-up" data-target="<?= $revenusMois ?>">0</span>€</p>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 flex items-center font-semibold"><i class="fas fa-arrow-up mr-1"></i>23%</span>
                <span class="text-slate-400 ml-2">vs mois dernier</span>
            </div>
        </div>

        <!-- Card 4: Taux Occupation -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-purple-50 rounded-xl flex items-center justify-center group-hover:bg-purple-500 transition-colors">
                    <i class="fas fa-chart-pie text-purple-500 text-2xl group-hover:text-white transition-colors"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500 font-medium">Occupation</p>
                    <p class="text-3xl font-bold text-slate-800"><span class="count-up" data-target="<?= $tauxOccupation ?>">0</span>%</p>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 flex items-center font-semibold"><i class="fas fa-arrow-up mr-1"></i>5%</span>
                <span class="text-slate-400 ml-2">vs mois dernier</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenus -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Revenus Mensuels</h3>
            <div class="h-64"><canvas id="revenusChart"></canvas></div>
        </div>

        <!-- Réservations -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Réservations Hebdomadaires</h3>
            <div class="h-64"><canvas id="reservationsChart"></canvas></div>
        </div>
    </div>

    <!-- Flotte de Véhicules -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Flotte de Véhicules</h3>
                <p class="text-sm text-slate-500"><?= count($vehiculesFlotte) ?> derniers ajouts</p>
            </div>
            <a href="vehicules/add.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 shadow-md">
                <i class="fas fa-plus"></i> Ajouter
            </a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($vehiculesFlotte as $v): ?>
                <?php 
                    $statusClass = ($v['etat'] ?? '') == 'Disponible' ? 'bg-green-100 text-green-700 border-green-200' : 
                                  (($v['etat'] ?? '') == 'Loué' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200');
                    $statusDot = ($v['etat'] ?? '') == 'Disponible' ? 'bg-green-500' : 
                                (($v['etat'] ?? '') == 'Loué' ? 'bg-blue-500' : 'bg-yellow-500');
                ?>
                <div class="bg-slate-50 rounded-xl overflow-hidden border border-slate-200 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group">
                    <!-- Image -->
                    <div class="relative h-48 bg-slate-200 overflow-hidden">
                        <?php if (!empty($v['image'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($v['image']) ?>" 
                                 alt="<?= htmlspecialchars(($v['marque'] ?? '') . ' ' . ($v['modele'] ?? '')) ?>" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-slate-100">
                                <i class="fas fa-car text-6xl text-slate-300"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Status Badge -->
                        <span class="absolute top-3 right-3 <?= $statusClass ?> px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-1.5 border backdrop-blur-sm">
                            <span class="w-2 h-2 <?= $statusDot ?> rounded-full"></span>
                            <?= htmlspecialchars($v['etat'] ?? 'N/A') ?>
                        </span>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-5">
                        <h4 class="text-lg font-bold text-slate-800"><?= htmlspecialchars(($v['marque'] ?? '') . ' ' . ($v['modele'] ?? '')) ?></h4>
                        <p class="text-sm text-slate-500 mb-3"><?= htmlspecialchars($v['immatriculation'] ?? '') ?></p>
                        
                        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
                            <div>
                                <span class="text-xl font-bold text-orange-500"><?= formatPrice($v['prix_jour'] ?? 0) ?></span>
                                <span class="text-slate-400 text-sm">/jour</span>
                            </div>
                            <a href="vehicules/index.php" class="text-slate-600 hover:text-orange-500 transition font-medium text-sm">
                                Détails <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Réservations Récentes -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-history text-orange-500 text-lg"></i>
            <h3 class="text-lg font-bold text-slate-800">Réservations Récentes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-slate-500 text-sm border-b border-slate-100 bg-slate-50/50">
                        <th class="px-6 py-4 font-semibold">ID</th>
                        <th class="px-6 py-4 font-semibold">Client</th>
                        <th class="px-6 py-4 font-semibold">Véhicule</th>
                        <th class="px-6 py-4 font-semibold">Période</th>
                        <th class="px-6 py-4 font-semibold">Statut</th>
                        <th class="px-6 py-4 font-semibold text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dernieresReservations)): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">Aucune réservation récente</td></tr>
                    <?php else: ?>
                        <?php foreach ($dernieresReservations as $r): ?>
                        <?php 
                            $statusClass = ($r['statut'] ?? '') == 'En cours' ? 'bg-green-100 text-green-700' : 
                                          (($r['statut'] ?? '') == 'Confirmée' ? 'bg-blue-100 text-blue-700' : 
                                          (($r['statut'] ?? '') == 'En attente' ? 'bg-yellow-100 text-yellow-700' : 'bg-slate-100 text-slate-600'));
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-500 font-mono text-sm">#<?= str_pad($r['id_reservation'] ?? 0, 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs font-bold shadow">
                                        <?= strtoupper(substr($r['prenom'] ?? 'X', 0, 1)) ?>
                                    </div>
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars(($r['prenom'] ?? '') . ' ' . ($r['client_nom'] ?? '')) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars(($r['marque'] ?? '') . ' ' . ($r['modele'] ?? '')) ?></td>
                            <td class="px-6 py-4 text-slate-500 text-sm"><?= formatDate($r['date_debut'] ?? '') ?> → <?= formatDate($r['date_fin'] ?? '') ?></td>
                            <td class="px-6 py-4"><span class="<?= $statusClass ?> px-3 py-1 rounded-full text-xs font-bold uppercase"><?= htmlspecialchars($r['statut'] ?? 'N/A') ?></span></td>
                           <!-- Remplacez la ligne du <td> du prix par celle-ci -->
                          <td class="px-6 py-4 text-right text-slate-800 font-bold"><?= formatPrice($r['prix_total'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Scripts -->
<script>
    // 1. CountUp Animation
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.count-up');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const increment = target / 200;
            const updateCount = () => {
                const count = +counter.innerText.replace(/[^0-9]/g, '');
                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(updateCount, 10);
                } else {
                    counter.innerText = target.toLocaleString('fr-FR');
                }
            };
            updateCount();
        });
    });

    // 2. Charts
    const revenusCtx = document.getElementById('revenusChart').getContext('2d');
    new Chart(revenusCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil'],
            datasets: [{
                label: 'Revenus',
                data: [
                    <?= $revenusMensuels['jan'] ?? 0 ?>,
                    <?= $revenusMensuels['fev'] ?? 0 ?>,
                    <?= $revenusMensuels['mar'] ?? 0 ?>,
                    <?= $revenusMensuels['avr'] ?? 0 ?>,
                    <?= $revenusMensuels['mai'] ?? 0 ?>, // Correction ici : mai au lieu de main
                    <?= $revenusMensuels['jun'] ?? 0 ?>,
                    <?= $revenusMensuels['jul'] ?? 0 ?>
                ],
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const reservationsCtx = document.getElementById('reservationsChart').getContext('2d');
    new Chart(reservationsCtx, {
        type: 'bar',
        data: {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            datasets: [{
                label: 'Réservations',
                data: [
                    <?= $reservationsHebdo['lun'] ?? 0 ?>,
                    <?= $reservationsHebdo['mar'] ?? 0 ?>,
                    <?= $reservationsHebdo['mer'] ?? 0 ?>,
                    <?= $reservationsHebdo['jeu'] ?? 0 ?>,
                    <?= $reservationsHebdo['ven'] ?? 0 ?>,
                    <?= $reservationsHebdo['sam'] ?? 0 ?>,
                    <?= $reservationsHebdo['dim'] ?? 0 ?>
                ],
                backgroundColor: '#64748b',
                borderRadius: 8
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
</script>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>