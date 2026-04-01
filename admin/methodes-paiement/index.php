<?php
ob_start();
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

 $db = getDB();

// --- FONCTION POUR LES COULEURS AUTOMATIQUES ---
function getPaymentGradient($nom) {
    $nom = strtolower($nom);
    if (strpos($nom, 'carte') !== false || strpos($nom, 'stripe') !== false || strpos($nom, 'visa') !== false || strpos($nom, 'mastercard') !== false) return 'from-indigo-500 to-purple-600';
    if (strpos($nom, 'espec') !== false || strpos($nom, 'cash') !== false) return 'from-emerald-500 to-green-600';
    if (strpos($nom, 'moncash') !== false || strpos($nom, 'natcash') !== false || strpos($nom, 'mobile') !== false) return 'from-orange-500 to-red-500';
    if (strpos($nom, 'virement') !== false || strpos($nom, 'bancair') !== false) return 'from-blue-500 to-cyan-600';
    if (strpos($nom, 'paypal') !== false) return 'from-yellow-400 to-blue-500';
    if (strpos($nom, 'cheque') !== false) return 'from-gray-500 to-slate-600';
    return 'from-gray-400 to-gray-600'; // Défaut
}
// -------------------------------------------

// Traitement des actions (AUCUN CHANGEMENT ICI)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db->query("SELECT 1 FROM Methodes_Paiement LIMIT 1");
    } catch (PDOException $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS Methodes_Paiement (
            id_methode INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            icone VARCHAR(50) DEFAULT 'fa-wallet',
            code VARCHAR(50) NULL,
            image VARCHAR(255) NULL,
            actif BOOLEAN DEFAULT TRUE,
            frais_pourcentage DECIMAL(5,2) DEFAULT 0.00,
            frais_fixe DECIMAL(10,2) DEFAULT 0.00,
            ordre INT DEFAULT 0,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->exec("INSERT INTO Methodes_Paiement (nom, description, icone, code, actif, frais_pourcentage, ordre) VALUES
            ('Carte bancaire', 'Paiement sécurisé par carte bancaire', 'fa-credit-card', 'card', TRUE, 2.50, 1),
            ('Espèces', 'Paiement en espèces au bureau', 'fa-money-bill-wave', 'cash', TRUE, 0.00, 2),
            ('Virement bancaire', 'Virement direct sur notre compte', 'fa-university', 'transfer', TRUE, 0.00, 3),
            ('MonCash', 'Paiement via MonCash', 'fa-mobile-alt', 'moncash', TRUE, 1.50, 4),
            ('NatCash', 'Paiement via NatCash', 'fa-mobile-alt', 'natcash', TRUE, 1.50, 5),
            ('Stripe', 'Paiement par carte bancaire international', 'fa-credit-card', 'stripe', TRUE, 2.90, 6)");
    }
    
    if ($action === 'toggle_actif' && isset($_POST['id_methode'])) {
        $stmt = $db->prepare("UPDATE Methodes_Paiement SET actif = NOT actif WHERE id_methode = ?");
        $stmt->execute([$_POST['id_methode']]);
        redirect('/admin/methodes-paiement/index.php?success=toggled');
    }
    if ($action === 'ajouter') {
        $nom = sanitize($_POST['nom']);
        $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nom)); // Génère un code automatiquement
        $description = sanitize($_POST['description'] ?? '');
        $icone = sanitize($_POST['icone'] ?? 'fa-wallet');
        $frais_pourcentage = (float)($_POST['frais_pourcentage'] ?? 0);
        $frais_fixe = (float)($_POST['frais_fixe'] ?? 0);
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre = (int)($_POST['ordre'] ?? 0);
        
        $stmt = $db->prepare("INSERT INTO Methodes_Paiement (nom, code, description, icone, actif, frais_pourcentage, frais_fixe, ordre) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $code, $description, $icone, $actif, $frais_pourcentage, $frais_fixe, $ordre]);
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

 $methodes = $db->query("SELECT * FROM Methodes_Paiement ORDER BY ordre, nom")->fetchAll();

 $icones = [
    'fa-credit-card' => 'Carte bancaire', 'fa-money-bill-wave' => 'Billets', 'fa-university' => 'Banque',
    'fa-money-check' => 'Chèque', 'fa-mobile-alt' => 'Mobile', 'fa-paypal' => 'PayPal',
    'fa-wallet' => 'Portefeuille', 'fa-coins' => 'Pièces', 'fa-hand-holding-usd' => 'Main', 'fa-receipt' => 'Reçu'
];

 $pageTitle = 'Méthodes de Paiement';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-credit-card text-orange-600"></i>
            </div>
            Méthodes de Paiement
        </h1>
        <p class="text-gray-500 mt-1 ml-13">Gérez les modes de paiement acceptés par vos clients.</p>
    </div>
    
    <button onclick="openModal('addModal')" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-lg shadow-orange-500/30 flex items-center gap-2 w-fit">
        <i class="fas fa-plus"></i> Ajouter une méthode
    </button>
</div>

<!-- Grille des cartes de paiement -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <?php if (empty($methodes)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-2xl border border-gray-100">
            <i class="fas fa-credit-card text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">Aucune méthode configurée.</p>
        </div>
    <?php else: ?>
        
        <?php foreach ($methodes as $m): 
            $gradient = getPaymentGradient($m['nom']);
            // Gestion de l'image ou de l'icône
            $logoFile = $m['image'] ?? strtolower($m['nom'] . '.jpg');
            $logoPath = BASE_URL . '/assets/images/payments/' . $logoFile;
            $hasImage = file_exists(__DIR__ . '/../../assets/images/payments/' . $logoFile);
        ?>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 relative group flex flex-col">
            
            <!-- Header de la carte avec dégradé -->
            <div class="bg-gradient-to-br <?= $gradient ?> p-6 text-white relative">
                <!-- Badge Actif/Inactif -->
                <div class="absolute top-4 right-4">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $m['actif'] ? 'bg-white/20 text-white' : 'bg-black/30 text-white/70' ?>">
                        <?= $m['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </div>
                
                <div class="flex items-center gap-4 mt-2">
                    <?php if ($hasImage): ?>
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center p-1.5 backdrop-blur-sm">
                            <img src="<?= $logoPath ?>" alt="<?= $m['nom'] ?>" class="w-full h-full object-contain drop-shadow-lg">
                        </div>
                    <?php else: ?>
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas <?= $m['icone'] ?> text-2xl drop-shadow-lg"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($m['nom']) ?></h3>
                        <p class="text-white/70 text-xs mt-0.5"><?= htmlspecialchars($m['description']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Contenu de la carte -->
            <div class="p-5 flex-grow flex flex-col justify-between">
                
                <!-- Infos sur les frais -->
                <div class="mb-4">
                    <?php if ($m['frais_pourcentage'] > 0 || $m['frais_fixe'] > 0): ?>
                        <div class="flex items-center gap-2 bg-orange-50 text-orange-700 px-3 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-percentage text-xs"></i>
                            +<?= $m['frais_pourcentage'] ?>% 
                            <?php if ($m['frais_fixe'] > 0): ?>
                                <span class="text-orange-400">|</span> + <?= formatPrice($m['frais_fixe']) ?>
                            <?php endif; ?>
                            <span class="text-orange-400 font-normal ml-auto">frais</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 text-green-600 px-3 py-2 rounded-lg text-sm font-medium bg-green-50">
                            <i class="fas fa-check-circle text-xs"></i> Sans frais supplémentaires
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions en bas -->
                <div class="flex items-center justify-between border-t pt-4">
                    <!-- Toggle Switch Stylisé -->
                    <form method="POST" class="inline-flex items-center">
                        <input type="hidden" name="action" value="toggle_actif">
                        <input type="hidden" name="id_methode" value="<?= $m['id_methode'] ?>">
                        <button type="submit" title="Activer/Désactiver" class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors duration-200 focus:outline-none <?= $m['actif'] ? 'bg-orange-500' : 'bg-gray-300' ?>">
                            <span class="<?= $m['actif'] ? 'translate-x-6' : 'translate-x-1' ?> inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition-transform duration-200"></span>
                        </button>
                    </form>
                    
                    <!-- Boutons Edit/Delete (Apparaissent au survol) -->
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="editMethode(<?= htmlspecialchars(json_encode($m)) ?>)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Modifier">
                            <i class="fas fa-pen-to-square text-sm"></i>
                        </button>
                        
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer définitivement cette méthode ?')">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id_methode" value="<?= $m['id_methode'] ?>">
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                                <i class="fas fa-trash-can text-sm"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
    <?php endif; ?>
</div>

<!-- Modals (Légèrement modernisés avec meilleure gestion des icônes) -->
<!-- Modal Ajouter -->
<div id="addModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative transform transition-all">
        <button type="button" onclick="closeModal('addModal')" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition z-10">
            <i class="fas fa-times"></i>
        </button>
        <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-orange-600 rounded-t-2xl">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2"><i class="fas fa-plus-circle"></i> Nouvelle méthode</h3>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="ajouter">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la méthode *</label>
                <input type="text" name="nom" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500" placeholder="Ex: MonCash, Visa, Espèces">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500" placeholder="Détails affichés au client">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais (%)</label>
                    <input type="number" name="frais_pourcentage" step="0.01" min="0" value="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais fixes ($)</label>
                    <input type="number" name="frais_fixe" step="0.01" min="0" value="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icône Font Awesome</label>
                    <select name="icone" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                        <?php foreach ($icones as $icon => $label): ?>
                            <option value="<?= $icon ?>"> <?= $label ?> </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" name="ordre" min="0" value="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500">
                </div>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="actif" id="add_actif" checked class="w-5 h-5 text-orange-500 rounded focus:ring-orange-500">
                <label for="add_actif" class="text-sm text-gray-700 font-medium">Méthode active pour les clients</label>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition">Annuler</button>
                <button type="submit" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-medium transition shadow-sm">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Modifier -->
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative transform transition-all">
        <button type="button" onclick="closeModal('editModal')" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition z-10">
            <i class="fas fa-times"></i>
        </button>
        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-2xl">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2"><i class="fas fa-pen-to-square"></i> Modifier la méthode</h3>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id_methode" id="edit_id_methode">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" name="nom" id="edit_nom" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" id="edit_description" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais (%)</label>
                    <input type="number" name="frais_pourcentage" id="edit_frais_pourcentage" step="0.01" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frais fixes ($)</label>
                    <input type="number" name="frais_fixe" id="edit_frais_fixe" step="0.01" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icône</label>
                    <select name="icone" id="edit_icone" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($icones as $icon => $label): ?>
                            <option value="<?= $icon ?>"> <?= $label ?> </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordre</label>
                    <input type="number" name="ordre" id="edit_ordre" min="0" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="actif" id="edit_actif" class="w-5 h-5 text-blue-500 rounded focus:ring-blue-500">
                <label for="edit_actif" class="text-sm text-gray-700 font-medium">Méthode active</label>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition">Annuler</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl font-medium transition shadow-sm">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>

<script>
window.openModal = function(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
};
window.closeModal = function(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
};
window.editMethode = function(data) {
    document.getElementById('edit_id_methode').value = data.id_methode;
    document.getElementById('edit_nom').value = data.nom;
    document.getElementById('edit_description').value = data.description || '';
    document.getElementById('edit_icone').value = data.icone;
    document.getElementById('edit_frais_pourcentage').value = data.frais_pourcentage;
    document.getElementById('edit_frais_fixe').value = data.frais_fixe;
    document.getElementById('edit_ordre').value = data.ordre;
    document.getElementById('edit_actif').checked = data.actif == 1 || data.actif === true;
    window.openModal('editModal');
};

document.addEventListener('click', function(e) {
    if (e.target.id === 'addModal') window.closeModal('addModal');
    if (e.target.id === 'editModal') window.closeModal('editModal');
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.closeModal('addModal');
        window.closeModal('editModal');
    }
});
</script>

<?php if (isset($_GET['success'])): ?>
<div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-2 z-[60] animate-bounce" id="alert">
    <i class="fas fa-check-circle"></i>
    <?php 
        $msgs = ['toggled' => 'Statut modifié', 'added' => 'Méthode ajoutée', 'updated' => 'Méthode modifiée', 'deleted' => 'Méthode supprimée'];
        echo $msgs[$_GET['success']] ?? 'Action réussie';
    ?>
</div>
<script>setTimeout(() => { var a = document.getElementById('alert'); if(a) a.style.display = 'none'; }, 3000);</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>