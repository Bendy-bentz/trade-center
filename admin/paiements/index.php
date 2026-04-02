<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'marquer_recu' && isset($_POST['id_paiement'])) {
        $stmt = $db->prepare("UPDATE Paiements SET recu = TRUE WHERE id_paiement = ?");
        $stmt->execute([$_POST['id_paiement']]);
        redirect('/admin/paiements/index.php?success=marked');
    }
    
    if ($action === 'ajouter_paiement') {
        $id_contrat = (int)$_POST['id_contrat'];
        $mode_paiement = sanitize($_POST['mode_paiement']);
        $montant = (float)$_POST['montant'];
        $notes = sanitize($_POST['notes'] ?? '');
        $recu = isset($_POST['recu']) ? 1 : 0;
        
        $stmt = $db->prepare("INSERT INTO Paiements (id_contrat, mode_paiement, montant, recu, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_contrat, $mode_paiement, $montant, $recu, $notes]);
        redirect('/admin/paiements/index.php?success=added');
    }
}

// Filtres
$filtre_mode = $_GET['mode'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_periode = $_GET['periode'] ?? '';

// Construction de la requête
$whereConditions = [];
$params = [];

if ($filtre_mode) {
    $whereConditions[] = "p.mode_paiement = ?";
    $params[] = $filtre_mode;
}

if ($filtre_statut === 'recu') {
    $whereConditions[] = "p.recu = TRUE";
} elseif ($filtre_statut === 'attente') {
    $whereConditions[] = "p.recu = FALSE";
}

if ($filtre_periode === 'aujourdhui') {
    $whereConditions[] = "DATE(p.date_paiement) = CURDATE()";
} elseif ($filtre_periode === 'semaine') {
    $whereConditions[] = "YEARWEEK(p.date_paiement) = YEARWEEK(CURDATE())";
} elseif ($filtre_periode === 'mois') {
    $whereConditions[] = "MONTH(p.date_paiement) = MONTH(CURDATE()) AND YEAR(p.date_paiement) = YEAR(CURDATE())";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Récupérer tous les paiements
$sql = "SELECT p.*, c.numero_contrat, r.reference, 
        cl.nom as client_nom, cl.prenom as client_prenom,
        v.marque, v.modele
        FROM Paiements p 
        JOIN Contrats c ON p.id_contrat = c.id_contrat 
        JOIN Reservations r ON c.id_reservation = r.id_reservation
        JOIN Clients cl ON r.id_client = cl.id_client
        JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
        $whereClause
        ORDER BY p.date_paiement DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll();

// Statistiques
$totalRecu = $db->query("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = TRUE")->fetchColumn();
$totalEnAttente = $db->query("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = FALSE")->fetchColumn();
$nbPaiementsRecus = $db->query("SELECT COUNT(*) FROM Paiements WHERE recu = TRUE")->fetchColumn();
$nbPaiementsEnAttente = $db->query("SELECT COUNT(*) FROM Paiements WHERE recu = FALSE")->fetchColumn();

// Statistiques par mode de paiement
$statsParMode = $db->query("SELECT mode_paiement, COUNT(*) as nb, SUM(montant) as total FROM Paiements GROUP BY mode_paiement")->fetchAll();

// Contrats sans paiement pour le formulaire
$contratsSansPaiement = $db->query("SELECT c.id_contrat, c.numero_contrat, c.montant_total, 
                                    CONCAT(cl.prenom, ' ', cl.nom) as client_nom
                                    FROM Contrats c 
                                    JOIN Reservations r ON c.id_reservation = r.id_reservation
                                    JOIN Clients cl ON r.id_client = cl.id_client
                                    LEFT JOIN Paiements p ON c.id_contrat = p.id_contrat
                                    WHERE p.id_contrat IS NULL")->fetchAll();

$pageTitle = 'Paiements';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<!-- Page Title -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gestion des Paiements</h1>
    <p class="text-gray-500 mt-1">Suivi et gestion des transactions financières</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Reçu -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Reçu</p>
                <p class="text-2xl font-bold text-green-600"><?= formatPrice($totalRecu) ?></p>
                <p class="text-sm text-gray-400 mt-1"><?= $nbPaiementsRecus ?> paiement(s)</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- En Attente -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">En Attente</p>
                <p class="text-2xl font-bold text-orange-500"><?= formatPrice($totalEnAttente) ?></p>
                <p class="text-sm text-gray-400 mt-1"><?= $nbPaiementsEnAttente ?> paiement(s)</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-orange-500 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Général -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Général</p>
                <p class="text-2xl font-bold text-blue-600"><?= formatPrice($totalRecu + $totalEnAttente) ?></p>
                <p class="text-sm text-gray-400 mt-1"><?= count($paiements) ?> transaction(s)</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-dollar-sign text-blue-500 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Taux de récupération -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Taux de Récupération</p>
                <?php 
                $tauxRecup = ($totalRecu + $totalEnAttente) > 0 
                    ? round(($totalRecu / ($totalRecu + $totalEnAttente)) * 100) 
                    : 0;
                ?>
                <p class="text-2xl font-bold text-purple-600"><?= $tauxRecup ?>%</p>
                <p class="text-sm text-gray-400 mt-1">Des paiements reçus</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-pie text-purple-500 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <!-- Filtre Mode -->
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Mode de paiement</label>
            <select name="mode" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Tous les modes</option>
                <option value="Carte bancaire" <?= $filtre_mode === 'Carte bancaire' ? 'selected' : '' ?>>Carte bancaire</option>
                <option value="Espèces" <?= $filtre_mode === 'Espèces' ? 'selected' : '' ?>>Espèces</option>
                <option value="Virement bancaire" <?= $filtre_mode === 'Virement bancaire' ? 'selected' : '' ?>>Virement bancaire</option>
                <option value="Chèque" <?= $filtre_mode === 'Chèque' ? 'selected' : '' ?>>Chèque</option>
                <option value="Mobile Money" <?= $filtre_mode === 'Mobile Money' ? 'selected' : '' ?>>Mobile Money</option>
            </select>
        </div>

        <!-- Filtre Statut -->
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
            <select name="statut" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Tous les statuts</option>
                <option value="recu" <?= $filtre_statut === 'recu' ? 'selected' : '' ?>>Reçus</option>
                <option value="attente" <?= $filtre_statut === 'attente' ? 'selected' : '' ?>>En attente</option>
            </select>
        </div>

        <!-- Filtre Période -->
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-500 mb-1">Période</label>
            <select name="periode" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Toutes les périodes</option>
                <option value="aujourdhui" <?= $filtre_periode === 'aujourdhui' ? 'selected' : '' ?>>Aujourd'hui</option>
                <option value="semaine" <?= $filtre_periode === 'semaine' ? 'selected' : '' ?>>Cette semaine</option>
                <option value="mois" <?= $filtre_periode === 'mois' ? 'selected' : '' ?>>Ce mois</option>
            </select>
        </div>

        <!-- Boutons -->
        <div class="flex items-end gap-2 pt-5">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-medium transition">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
            <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
                Réinitialiser
            </a>
        </div>
    </form>
</div>

<!-- Stats par mode de paiement -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Graphique par mode -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Répartition par mode de paiement</h3>
        <div class="space-y-4">
            <?php foreach ($statsParMode as $stat): ?>
            <?php 
            $pourcentage = ($totalRecu + $totalEnAttente) > 0 
                ? round(($stat['total'] / ($totalRecu + $totalEnAttente)) * 100) 
                : 0;
            $modeColors = [
                'Carte bancaire' => 'bg-blue-500',
                'Espèces' => 'bg-green-500',
                'Virement bancaire' => 'bg-purple-500',
                'Chèque' => 'bg-yellow-500',
                'Mobile Money' => 'bg-pink-500'
            ];
            $color = $modeColors[$stat['mode_paiement']] ?? 'bg-gray-500';
            ?>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-medium text-gray-700"><?= $stat['mode_paiement'] ?></span>
                    <span class="text-sm text-gray-500"><?= $stat['nb'] ?> paiement(s) - <?= formatPrice($stat['total']) ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="<?= $color ?> h-3 rounded-full transition-all duration-500" style="width: <?= $pourcentage ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($statsParMode)): ?>
                <p class="text-gray-500 text-center py-4">Aucun paiement enregistré</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ajouter un paiement -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ajouter un paiement</h3>
        
        <?php if (empty($contratsSansPaiement)): ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-500">Tous les contrats ont été payés</p>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_paiement">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contrat</label>
                        <select name="id_contrat" required class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                            <option value="">Sélectionner un contrat</option>
                            <?php foreach ($contratsSansPaiement as $c): ?>
                                <option value="<?= $c['id_contrat'] ?>">
                                    <?= $c['numero_contrat'] ?> - <?= $c['client_nom'] ?> (<?= formatPrice($c['montant_total']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                        <select name="mode_paiement" required class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                            <option value="Carte bancaire">Carte bancaire</option>
                            <option value="Espèces">Espèces</option>
                            <option value="Virement bancaire">Virement bancaire</option>
                            <option value="Chèque">Chèque</option>
                            <option value="Mobile Money">Mobile Money</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Montant ($)</label>
                        <input type="number" name="montant" step="0.01" required 
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500"
                            placeholder="0.00">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="recu" id="recu" checked class="w-4 h-4 text-orange-500 rounded">
                        <label for="recu" class="text-sm text-gray-700">Paiement reçu</label>
                    </div>

                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2.5 rounded-lg font-medium transition">
                        <i class="fas fa-plus mr-2"></i>Ajouter le paiement
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Liste des paiements -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 bg-orange-50 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fas fa-credit-card text-orange-500 text-xl"></i>
            <h3 class="text-lg font-semibold text-gray-800">Historique des Paiements</h3>
        </div>
        <span class="text-sm text-gray-500"><?= count($paiements) ?> paiement(s)</span>
    </div>

    <?php if (empty($paiements)): ?>
        <div class="p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-credit-card text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Aucun paiement trouvé</h3>
            <p class="text-gray-500">Les paiements apparaîtront ici une fois enregistrés</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-500 text-sm border-b border-gray-100">
                        <th class="px-6 py-4 font-medium">Référence</th>
                        <th class="px-6 py-4 font-medium">Client</th>
                        <th class="px-6 py-4 font-medium">Véhicule</th>
                        <th class="px-6 py-4 font-medium">Mode</th>
                        <th class="px-6 py-4 font-medium text-right">Montant</th>
                        <th class="px-6 py-4 font-medium">Date</th>
                        <th class="px-6 py-4 font-medium">Statut</th>
                        <th class="px-6 py-4 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paiements as $p): ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-medium text-gray-800"><?= $p['reference_paiement'] ?></span>
                            <br>
                            <span class="text-xs text-gray-400">Contrat: <?= $p['numero_contrat'] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    <?= strtoupper(substr($p['client_prenom'], 0, 1) . substr($p['client_nom'], 0, 1)) ?>
                                </div>
                                <span class="text-gray-700"><?= $p['client_prenom'] ?> <?= $p['client_nom'] ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-car text-orange-500"></i>
                                <span class="text-gray-700"><?= $p['marque'] ?> <?= $p['modele'] ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $modeIcons = [
                                'Carte bancaire' => 'fa-credit-card',
                                'Espèces' => 'fa-money-bill-wave',
                                'Virement bancaire' => 'fa-university',
                                'Chèque' => 'fa-money-check',
                                'Mobile Money' => 'fa-mobile-alt'
                            ];
                            $icon = $modeIcons[$p['mode_paiement']] ?? 'fa-wallet';
                            ?>
                            <span class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 rounded-lg text-sm">
                                <i class="fas <?= $icon ?> text-gray-500"></i>
                                <?= $p['mode_paiement'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-orange-500"><?= formatPrice($p['montant']) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-700"><?= formatDate($p['date_paiement']) ?></div>
                            <div class="text-xs text-gray-400"><?= date('H:i', strtotime($p['date_paiement'])) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($p['recu']): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-600 rounded-lg text-sm font-medium">
                                    <i class="fas fa-check-circle"></i> Reçu
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 text-orange-600 rounded-lg text-sm font-medium">
                                    <i class="fas fa-clock"></i> En attente
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if (!$p['recu']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Marquer ce paiement comme reçu ?')">
                                    <input type="hidden" name="action" value="marquer_recu">
                                    <input type="hidden" name="id_paiement" value="<?= $p['id_paiement'] ?>">
                                    <button type="submit" class="text-green-600 hover:text-green-700 transition p-2" title="Marquer comme reçu">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Alert Messages -->
<?php if (isset($_GET['success'])): ?>
<div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50" id="alert">
    <i class="fas fa-check-circle"></i>
    <?php if ($_GET['success'] === 'marked'): ?>
        Paiement marqué comme reçu avec succès
    <?php elseif ($_GET['success'] === 'added'): ?>
        Paiement ajouté avec succès
    <?php endif; ?>
</div>
<script>
    setTimeout(() => document.getElementById('alert')?.remove(), 3000);
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
