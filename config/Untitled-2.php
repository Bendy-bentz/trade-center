<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Vérifier si la table existe, sinon la créer
    try {
        $db->query("SELECT 1 FROM Methodes_Paiement LIMIT 1");
    } catch (PDOException $e) {
        // Créer la table si elle n'existe pas
        $db->exec("CREATE TABLE IF NOT EXISTS Methodes_Paiement (
            id_methode INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            icone VARCHAR(50) DEFAULT 'fa-wallet',
            actif BOOLEAN DEFAULT TRUE,
            frais_pourcentage DECIMAL(5,2) DEFAULT 0.00,
            frais_fixe DECIMAL(10,2) DEFAULT 0.00,
            ordre INT DEFAULT 0,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insérer les méthodes par défaut
        $db->exec("INSERT INTO Methodes_Paiement (nom, description, icone, actif, frais_pourcentage, ordre) VALUES
            ('Carte bancaire', 'Paiement sécurisé par carte bancaire (Visa, Mastercard)', 'fa-credit-card', TRUE, 2.50, 1),
            ('Espèces', 'Paiement en espèces au bureau', 'fa-money-bill-wave', TRUE, 0.00, 2),
            ('Virement bancaire', 'Virement direct sur notre compte bancaire', 'fa-university', TRUE, 0.00, 3),
            ('Chèque', 'Paiement par chèque bancaire', 'fa-money-check', TRUE, 0.00, 4),
            ('Mobile Money', 'Paiement via Orange Money, MTN Money, Wave', 'fa-mobile-alt', TRUE, 1.50, 5),
            ('PayPal', 'Paiement sécurisé via PayPal', 'fa-paypal', FALSE, 3.50, 6)");
    }
    
    if ($action === 'toggle_actif' && isset($_POST['id_methode'])) {
        $stmt = $db->prepare("UPDATE Methodes_Paiement SET actif = NOT actif WHERE id_methode = ?");
        $stmt->execute([$_POST['id_methode']]);
        redirect('/admin/methodes-paiement/index.php?success=toggled');
    }
    
    if ($action === 'ajouter') {
        $nom = sanitize($_POST['nom']);
        $description = sanitize($_POST['description'] ?? '');
        $icone = sanitize($_POST['icone'] ?? 'fa-wallet');
        $frais_pourcentage = (float)($_POST['frais_pourcentage'] ?? 0);
        $frais_fixe = (float)($_POST['frais_fixe'] ?? 0);
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre = (int)($_POST['ordre'] ?? 0);
        
        $stmt = $db->prepare("INSERT INTO Methodes_Paiement (nom, description, icone, actif, frais_pourcentage, frais_fixe, ordre) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $description, $icone, $actif, $frais_pourcentage, $frais_fixe, $ordre]);
        redirect('/admin/methodes-paiement/index.php?success=added');
    }
    
    if ($action === 'modifier' && isset($_POST['id_methode'])) {
        $id = (int)$_POST['id_methode'];
        $nom = sanitize($_POST['nom']);
        $description = sanitize($_POST['description'] ?? '');
        $icone = sanitize($_POST['icone'] ?? 'fa-wallet');
        $frais_pourcentage = (float)($_POST['frais_pourcentage'] ?? 0);
        $frais_fixe = (float)($_POST['frais_fixe'] ?? 0);
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre = (int)($_POST['ordre'] ?? 0);
        
        $stmt = $db->prepare("UPDATE Methodes_Paiement SET nom = ?, description = ?, icone = ?, actif = ?, frais_pourcentage = ?, frais_fixe = ?, ordre = ? WHERE id_methode = ?");
        $stmt->execute([$nom, $description, $icone, $actif, $frais_pourcentage, $frais_fixe, $ordre, $id]);
        redirect('/admin/methodes-paiement/index.php?success=updated');
    }
    
    if ($action === 'supprimer' && isset($_POST['id_methode'])) {
        $stmt = $db->prepare("DELETE FROM Methodes_Paiement WHERE id_methode = ?");
        $stmt->execute([$_POST['id_methode']]);
        redirect('/admin/methodes-paiement/index.php?success=deleted');
    }
}

// Récupérer toutes les méthodes de paiement
$methodes = $db->query("SELECT * FROM Methodes_Paiement ORDER BY ordre, nom")->fetchAll();

// Icônes disponibles
$icones = [
    'fa-credit-card' => 'Carte bancaire',
    'fa-money-bill-wave' => 'Billets',
    'fa-university' => 'Banque',
    'fa-money-check' => 'Chèque',
    'fa-mobile-alt' => 'Mobile',
    'fa-paypal' => 'PayPal',
    'fa-wallet' => 'Portefeuille',
    'fa-coins' => 'Pièces',
    'fa-hand-holding-usd' => 'Main',
    'fa-receipt' => 'Reçu'
];

$pageTitle = 'Méthodes de Paiement';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<!-- Page Title -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Méthodes de Paiement</h1>
    <p class="text-gray-500 mt-1">Configurez les modes de paiement acceptés</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Méthodes actives</p>
                <p class="text-2xl font-bold text-green-600"><?= count(array_filter($methodes, fn($m) => $m['actif'])) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Méthodes inactives</p>
                <p class="text-2xl font-bold text-gray-400"><?= count(array_filter($methodes, fn($m) => !$m['actif'])) ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-times-circle text-gray-400 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total méthodes</p>
                <p class="text-2xl font-bold text-blue-600"><?= count($methodes) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-credit-card text-blue-500 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bouton Ajouter -->
<div class="flex justify-end mb-6">
    <button onclick="openModal('addModal')" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-lg font-medium transition shadow-lg shadow-orange-500/30 flex items-center gap-2">
        <i class="fas fa-plus"></i>
        Ajouter une méthode
    </button>
</div>

<!-- Liste des méthodes -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-orange-50 px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800">Liste des méthodes de paiement</h3>
    </div>
    
    <?php if (empty($methodes)): ?>
        <div class="p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-credit-card text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-700 mb-2">Aucune méthode configurée</h3>
            <p class="text-gray-500 mb-4">Ajoutez des méthodes de paiement pour commencer</p>
            <button onclick="openModal('addModal')" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-lg font-medium transition">
                <i class="fas fa-plus mr-2"></i>Ajouter une méthode
            </button>
        </div>
    <?php else: ?>
        <div class="divide-y divide-gray-100">
            <?php foreach ($methodes as $m): ?>
            <div class="p-6 flex items-center justify-between hover:bg-gray-50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 <?= $m['actif'] ? 'bg-orange-100' : 'bg-gray-100' ?> rounded-xl flex items-center justify-center">
                        <i class="fas <?= $m['icone'] ?> <?= $m['actif'] ? 'text-orange-500' : 'text-gray-400' ?> text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($m['nom']) ?></h4>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($m['description']) ?></p>
                        <?php if ($m['frais_pourcentage'] > 0 || $m['frais_fixe'] > 0): ?>
                            <p class="text-xs text-orange-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Frais: <?= $m['frais_pourcentage'] ?>% + <?= formatPrice($m['frais_fixe']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Statut -->
                    <span class="px-3 py-1 rounded-lg text-sm font-medium <?= $m['actif'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500' ?>">
                        <?= $m['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                    
                    <!-- Ordre -->
                    <span class="text-xs text-gray-400">#<?= $m['ordre'] ?></span>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button onclick="editMethode(<?= htmlspecialchars(json_encode($m)) ?>)" class="p-2 text-gray-400 hover:text-orange-500 hover:bg-orange-50 rounded-lg transition" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <form method="POST" class="inline" onsubmit="return confirm('<?= $m['actif'] ? 'Désactiver' : 'Activer' ?> cette méthode ?')">
                            <input type="hidden" name="action" value="toggle_actif">
                            <input type="hidden" name="id_methode" value="<?= $m['id_methode'] ?>">
                            <button type="submit" class="p-2 text-gray-400 hover:<?= $m['actif'] ? 'text-orange-500' : 'text-green-500' ?> hover:bg-<?= $m['actif'] ? 'orange' : 'green' ?>-50 rounded-lg transition" title="<?= $m['actif'] ? 'Désactiver' : 'Activer' ?>">
                                <i class="fas <?= $m['actif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette méthode ?')">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id_methode" value="<?= $m['id_methode'] ?>">
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Ajouter -->
<div id="addModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="px-6 py-4 bg-orange-500 rounded-t-2xl">
            <h3 class="text-lg font-semibold text-white">Ajouter une méthode de paiement</h3>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="ajouter">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" name="nom" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500" placeholder="Ex: Carte bancaire">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500" placeholder="Description courte">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Icône</label>
                <select name="icone" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                    <?php foreach ($icones as $icon => $label): ?>
                        <option value="<?= $icon ?>"><?= $label ?> (<?= $icon ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais (%)</label>
                    <input type="number" name="frais_pourcentage" step="0.01" min="0" value="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais fixes ($)</label>
                    <input type="number" name="frais_fixe" step="0.01" min="0" value="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" name="ordre" min="0" value="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="actif" checked class="w-5 h-5 text-orange-500 rounded">
                        <span class="text-sm text-gray-700">Actif</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">Annuler</button>
                <button type="submit" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Modifier -->
<div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="px-6 py-4 bg-orange-500 rounded-t-2xl">
            <h3 class="text-lg font-semibold text-white">Modifier la méthode</h3>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id_methode" id="edit_id_methode">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" name="nom" id="edit_nom" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" id="edit_description" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Icône</label>
                <select name="icone" id="edit_icone" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                    <?php foreach ($icones as $icon => $label): ?>
                        <option value="<?= $icon ?>"><?= $label ?> (<?= $icon ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais (%)</label>
                    <input type="number" name="frais_pourcentage" id="edit_frais_pourcentage" step="0.01" min="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais fixes ($)</label>
                    <input type="number" name="frais_fixe" id="edit_frais_fixe" step="0.01" min="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" name="ordre" id="edit_ordre" min="0" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="actif" id="edit_actif" class="w-5 h-5 text-orange-500 rounded">
                        <span class="text-sm text-gray-700">Actif</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">Annuler</button>
                <button type="submit" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition">Modifier</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function editMethode(data) {
    document.getElementById('edit_id_methode').value = data.id_methode;
    document.getElementById('edit_nom').value = data.nom;
    document.getElementById('edit_description').value = data.description || '';
    document.getElementById('edit_icone').value = data.icone;
    document.getElementById('edit_frais_pourcentage').value = data.frais_pourcentage;
    document.getElementById('edit_frais_fixe').value = data.frais_fixe;
    document.getElementById('edit_ordre').value = data.ordre;
    document.getElementById('edit_actif').checked = data.actif == 1;
    openModal('editModal');
}

// Close modal on outside click
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal(modal.id);
    });
});
</script>

<!-- Alert Messages -->
<?php if (isset($_GET['success'])): ?>
<div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50" id="alert">
    <i class="fas fa-check-circle"></i>
    <?php if ($_GET['success'] === 'toggled'): ?>
        Statut modifié avec succès
    <?php elseif ($_GET['success'] === 'added'): ?>
        Méthode ajoutée avec succès
    <?php elseif ($_GET['success'] === 'updated'): ?>
        Méthode modifiée avec succès
    <?php elseif ($_GET['success'] === 'deleted'): ?>
        Méthode supprimée avec succès
    <?php endif; ?>
</div>
<script>
    setTimeout(() => document.getElementById('alert')?.remove(), 3000);
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
