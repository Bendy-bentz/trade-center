
<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

 $db = getDB();
 $userId = getUserId();

// Récupérer les infos du client
 $stmt = $db->prepare("SELECT c.* FROM Clients c WHERE c.id_utilisateur = ?");
 $stmt->execute([$userId]);
 $client = $stmt->fetch();

if (!$client) {
    $user = getCurrentUser();
    if ($user) {
        // ANTI-CRASH : On vérifie si l'email existe déjà pour éviter l'erreur 1062 (qui cause le 403)
        $check = $db->prepare("SELECT id_client FROM Clients WHERE email = ?");
        $check->execute([$user['email']]);
        $existing = $check->fetch();

        if ($existing) {
            // L'email existe déjà, on le lie à ce compte utilisateur
            $db->prepare("UPDATE Clients SET id_utilisateur = ? WHERE id_client = ?")->execute([$userId, $existing['id_client']]);
        } else {
            // Nouveau client, on l'insère
            $db->prepare("INSERT INTO Clients (nom, email, id_utilisateur) VALUES (?, ?, ?)")->execute([
                $user['nom'] ?? 'Client', 
                $user['email'], 
                $userId
            ]);
        }
        // On recharge la page une fois que la base de données est propre
        header("Location: " . BASE_URL . "/client/index.php");
        exit();
    } else {
        session_destroy();
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}
// Statistiques
 $nbReservations = $db->prepare("SELECT COUNT(*) FROM Reservations WHERE id_client = ?");
 $nbReservations->execute([$client['id_client']]);
 $nbReservations = $nbReservations->fetchColumn();

 $reservationsEnCours = $db->prepare("SELECT COUNT(*) FROM Reservations WHERE id_client = ? AND statut = 'En cours'");
 $reservationsEnCours->execute([$client['id_client']]);
 $reservationsEnCours = $reservationsEnCours->fetchColumn();

// TOTAL DÉPENSÉ (en Dollars)
 $totalDepense = $db->prepare("SELECT COALESCE(SUM(p.montant), 0) FROM Paiements p 
JOIN Contrats c ON p.id_contrat = c.id_contrat 
JOIN Reservations r ON c.id_reservation = r.id_reservation 
WHERE r.id_client = ? AND p.recu = TRUE");
 $totalDepense->execute([$client['id_client']]);
 $totalDepense = $totalDepense->fetchColumn();

 $pointsFidelite = $nbReservations * 100;

// Dernières réservations (Ajout de v.prix_jour pour le calcul)
 $dernieresResa = $db->prepare("SELECT r.*, v.marque, v.modele, v.immatriculation, v.image, cv.nom_categorie, v.prix_jour
FROM Reservations r 
JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
LEFT JOIN Categories_Vehicules cv ON v.id_categorie = cv.id_categorie
WHERE r.id_client = ? 
ORDER BY r.date_reservation DESC LIMIT 5");
 $dernieresResa->execute([$client['id_client']]);
 $dernieresResa = $dernieresResa->fetchAll();

// Véhicules recommandés
 $vehiculesRecommandes = $db->query("SELECT v.*, cv.nom_categorie FROM Vehicules v 
LEFT JOIN Categories_Vehicules cv ON v.id_categorie = cv.id_categorie
WHERE v.etat = 'Disponible' ORDER BY RAND() LIMIT 3")->fetchAll();

 $pageTitle = 'Mon Espace';
// ... LE RESTE DE VOTRE CODE HTML RESTE EXACTEMENT IDENTIQUE ...
 $pageTitle = 'Mon Espace';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<!-- Welcome Banner -->
<!-- Welcome Banner avec Image de Fond -->
<div class="relative rounded-2xl shadow-xl overflow-hidden mb-8 h-64 md:h-auto">
    
    <!-- IMAGE DE FOND -->
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>/assets/images/3d-car-vibrant-city-night.jpg" 
             alt="Background" 
             class="w-full h-full object-cover">
        <!-- Overlay Gradient Sombre (pour la lisibilité) -->
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/80 to-transparent"></div>
    </div>

    <!-- CONTENU -->
    <div class="relative z-10 p-8 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold mb-2 text-white">
            Bienvenue, <span class="text-orange-500"><?= getUserName() ?></span> ! 👋
        </h1>
        <p class="text-gray-300 mb-8 text-lg max-w-xl">
            Gérez vos réservations et découvrez nos véhicules disponibles pour votre prochaine aventure.
        </p>
        <a href="reserver.php" class="inline-flex items-center bg-orange-500 text-white px-8 py-3 rounded-lg font-bold hover:bg-orange-600 transition shadow-lg shadow-orange-500/30 transform hover:scale-105">
            <i class="fas fa-car mr-2"></i>
            Réserver un véhicule
        </a>
    </div>

    <!-- Décoration optionnelle -->
    <div class="absolute bottom-0 right-0 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl -mr-32 -mb-32 z-0"></div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Total Réservations -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-alt text-blue-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-bold">Total Réservations</p>
                <p class="text-2xl font-bold text-gray-800"><?= $nbReservations ?></p>
            </div>
        </div>
    </div>

    <!-- En Cours -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-car-side text-green-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-bold">En cours</p>
                <p class="text-2xl font-bold text-gray-800"><?= $reservationsEnCours ?></p>
            </div>
        </div>
    </div>

    <!-- Total Dépensé (en $) -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet text-orange-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-bold">Total Dépensé</p>
                <p class="text-2xl font-bold text-gray-800">$ <?= number_format($totalDepense, 0, ',', ' ') ?></p>
            </div>
        </div>
    </div>

    <!-- Points Fidélité -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-lg transition">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-star text-purple-500 text-xl"></i>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase font-bold">Points Fidélité</p>
                <p class="text-2xl font-bold text-gray-800"><?= $pointsFidelite ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Colonne Principale : Réservations -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Mes Réservations Récentes</h3>
            <a href="reservations.php" class="text-orange-500 hover:text-orange-600 text-sm font-semibold flex items-center gap-1">
                Voir tout <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        
        <?php if (empty($dernieresResa)): ?>
        <div class="p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-times text-gray-300 text-3xl"></i>
            </div>
            <p class="text-gray-500 mb-6">Vous n'avez pas encore de réservation.</p>
            <a href="reserver.php" class="inline-flex items-center bg-gray-900 hover:bg-gray-800 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-plus mr-2"></i>Faire ma première réservation
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase bg-gray-50 border-b">
                        <th class="px-6 py-3 font-semibold">Véhicule</th>
                        <th class="px-6 py-3 font-semibold">Période</th>
                        <th class="px-6 py-3 font-semibold">Statut</th>
                        <th class="px-6 py-3 font-semibold text-right">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                                       <?php foreach ($dernieresResa as $r): ?>
                    <?php 
                        // Gestion des statuts
                        $statusClass = $r['statut'] == 'En cours' ? 'bg-green-100 text-green-700' : 
                                      ($r['statut'] == 'Confirmée' ? 'bg-blue-100 text-blue-700' : 
                                      ($r['statut'] == 'En attente' ? 'bg-yellow-100 text-yellow-700' : 
                                      ($r['statut'] == 'Terminée' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-600')));
                        
                        // CALCUL DU MONTANT (Correction erreur colonne manquante)
                        // On utilise ?? 0 pour éviter l'erreur si la colonne n'existe pas
                        $prix = $r['prix_total'] ?? 0;
                        
                        // Si prix est 0, on calcule manuellement
                        if ($prix <= 0 && isset($r['prix_jour'])) {
                            $nbJours = max(1, (strtotime($r['date_fin']) - strtotime($r['date_debut'])) / 86400);
                            $prix = $nbJours * $r['prix_jour'];
                        }
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($r['image'] ?? 'default.jpg') ?>" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm"><?= $r['marque'] ?> <?= $r['modele'] ?></p>
                                    <p class="text-xs text-gray-400"><?= $r['nom_categorie'] ?? '' ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">
                                <div class="font-medium text-gray-700"><?= formatDate($r['date_debut']) ?></div>
                                <div class="text-gray-400 text-xs">au <?= formatDate($r['date_fin']) ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="<?= $statusClass ?> px-2.5 py-1 rounded-full text-xs font-bold">
                                <?= $r['statut'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <!-- AFFICHAGE EN DOLLAR -->
                            <span class="font-bold text-gray-900">$ <?= number_format($prix, 2) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        
        <!-- Véhicules Recommandés -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                <i class="fas fa-thumbs-up text-orange-500"></i>
                <h3 class="font-bold text-gray-800">Recommandés</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <?php foreach ($vehiculesRecommandes as $v): ?>
                <a href="reserver.php" class="flex gap-3 p-4 hover:bg-gray-50 transition group">
                    <div class="w-16 h-12 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                        <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($v['image'] ?? 'default.jpg') ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 group-hover:text-orange-500 transition truncate"><?= $v['marque'] ?> <?= $v['modele'] ?></p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-400"><?= $v['nom_categorie'] ?? '' ?></span>
                            <!-- PRIX EN DOLLAR -->
                            <p class="text-orange-500 font-bold text-sm">$ <?= number_format($v['prix_jour'], 0) ?><span class="text-xs text-gray-400 font-normal">/j</span></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="p-4 border-t">
                <a href="reserver.php" class="block text-center text-orange-500 hover:text-white bg-white hover:bg-orange-500 border border-orange-500 py-2 rounded-lg font-semibold transition text-sm">
                    Voir tous les véhicules
                </a>
            </div>
        </div>

        <!-- Besoin d'aide -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-headset text-gray-500"></i>
                </div>
                <h3 class="font-bold text-gray-800">Besoin d'aide ?</h3>
            </div>
            <p class="text-sm text-gray-400 mb-4">Notre équipe est disponible 24/7.</p>
            <div class="space-y-2 text-sm">
                <a href="tel:+50912345678" class="flex items-center gap-2 text-gray-600 hover:text-orange-500 transition">
                    <i class="fas fa-phone w-4"></i>
                    <span>+509 12 34 56 78</span>
                </a>
                <a href="mailto:contact@tradecenter.ht" class="flex items-center gap-2 text-gray-600 hover:text-orange-500 transition">
                    <i class="fas fa-envelope w-4"></i>
                    <span>contact@tradecenter.ht</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>