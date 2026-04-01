<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

 $db = getDB();
 $userId = getUserId();

// Récupérer l'ID client
 $client = $db->prepare("SELECT id_client FROM Clients WHERE id_utilisateur = ?");
 $client->execute([$userId]);
 $client = $client->fetch();

if (!$client) {
    redirect('/client/index.php');
}

 $idClient = $client['id_client'];

// Récupérer les véhicules disponibles
 $vehicules = $db->query("SELECT v.*, c.nom_categorie FROM Vehicules v 
    LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie 
    WHERE v.etat = 'Disponible' ORDER BY v.marque")->fetchAll();

// Traitement du formulaire
 $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idVehicule = (int)$_POST['id_vehicule'];
    $dateDebut = $_POST['date_debut'];
    $dateFin = $_POST['date_fin'];
    
    // Vérifier la disponibilité
    $stmt = $db->prepare("SELECT COUNT(*) FROM Reservations 
        WHERE id_vehicule = ? AND statut IN ('En attente', 'Confirmée', 'En cours')
        AND (
            (? BETWEEN date_debut AND date_fin) 
            OR (? BETWEEN date_debut AND date_fin) 
            OR (date_debut BETWEEN ? AND ?)
            OR (date_fin BETWEEN ? AND ?)
        )");
    $stmt->execute([$idVehicule, $dateDebut, $dateFin, $dateDebut, $dateFin, $dateDebut, $dateFin]);
    
    if ($stmt->fetchColumn() > 0) {
        $error = "Ce véhicule n'est pas disponible pour ces dates.";
    } else {
        // Création de la réservation
        $reference = generateReference('RES');
        $stmt = $db->prepare("INSERT INTO Reservations (reference, id_client, id_vehicule, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, ?, 'En attente')");
        $stmt->execute([$reference, $idClient, $idVehicule, $dateDebut, $dateFin]);
        
        // --- MODIFICATION ICI ---
        // On récupère l'ID de la réservation qui vient d'être créée
        $idNouvelleReservation = $db->lastInsertId();
        
        // On redirige vers la page de paiement
        redirect('/client/paiement.php?reservation=' . $idNouvelleReservation);
    }
}

 $pageTitle = 'Réserver un véhicule';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<?php if ($error): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center">
    <i class="fas fa-exclamation-circle mr-3"></i> <?= $error ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    
    <!-- Colonne Gauche : Liste Véhicules -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">1. Choisissez un véhicule</h3>
        </div>
        <div class="p-4 space-y-4 max-h-[600px] overflow-y-auto">
            <?php if (empty($vehicules)): ?>
                <p class="text-gray-400 text-center py-8">Aucun véhicule disponible actuellement.</p>
            <?php else: ?>
                <?php foreach ($vehicules as $v): ?>
                <label class="block p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-orange-500 hover:shadow-md transition vehicle-option group">
                    <input type="radio" name="vehicule_select" value="<?= $v['id_vehicule'] ?>" 
                           class="hidden" 
                           data-marque="<?= $v['marque'] ?>" 
                           data-modele="<?= $v['modele'] ?>" 
                           data-prix="<?= $v['prix_jour'] ?>"
                           data-image="<?= $v['image'] ?>">
                    
                    <div class="flex gap-4 items-center">
                        <!-- Image Véhicule -->
                        <div class="w-28 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 relative">
                            <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($v['image'] ?? 'default.jpg') ?>" 
                                 alt="<?= $v['marque'] ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <div class="absolute top-2 left-2">
                                <span class="bg-black/70 text-white text-[10px] px-2 py-0.5 rounded-full backdrop-blur-sm"><?= $v['nom_categorie'] ?></span>
                            </div>
                        </div>
                        
                        <!-- Infos Véhicule -->
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-gray-900 truncate"><?= $v['marque'] ?> <?= $v['modele'] ?></h4>
                            <p class="text-xs text-gray-400 mt-0.5"><?= $v['immatriculation'] ?> • <?= $v['carburant'] ?></p>
                            <div class="flex items-baseline gap-1 mt-2">
                                <span class="text-lg font-extrabold text-orange-500">$ <?= number_format($v['prix_jour'], 0, ',', ' ') ?></span>
                                <span class="text-xs text-gray-400">/ jour</span>
                            </div>
                        </div>
                        
                        <!-- Indicateur sélection -->
                        <div class="text-orange-500 opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne Droite : Formulaire -->
    <div id="form-section" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-6">2. Détails de la réservation</h3>
        
        <form method="POST" id="reservationForm">
            <input type="hidden" name="id_vehicule" id="id_vehicule" required>
            
            <!-- Récap Véhicule Sélectionné -->
            <div id="vehicule-selectionne" class="mb-6 p-4 bg-gray-50 rounded-xl border hidden">
                <p class="text-xs text-gray-400 mb-1">Véhicule sélectionné :</p>
                <div class="flex items-center gap-3">
                    <img id="selected-img" src="" class="w-16 h-12 rounded object-cover bg-gray-200">
                    <div>
                        <p class="font-bold text-gray-800" id="vehicule-nom">-</p>
                        <p class="text-orange-500 font-bold text-sm" id="vehicule-prix">-</p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Début</label>
                        <input type="date" name="date_debut" id="date_debut" required min="<?= date('Y-m-d') ?>" 
                               class="w-full border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Retour</label>
                        <input type="date" name="date_fin" id="date_fin" required min="<?= date('Y-m-d') ?>" 
                               class="w-full border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-500">Durée :</span>
                        <span class="font-medium text-gray-700" id="nb-jours">0 jour(s)</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="font-bold text-gray-800">Total estimé</span>
                        <span class="font-extrabold text-xl text-orange-500" id="total-estime">$ 0</span>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white py-3 rounded-lg font-bold transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" id="submitBtn" disabled>
                    Confirmer la réservation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- BOUTON FLOTTANT MOBILE -->
<div id="mobile-next-btn" class="fixed bottom-0 left-0 right-0 bg-white p-4 border-t border-gray-200 shadow-2xl z-50 hidden">
    <button type="button" onclick="scrollToForm()" class="w-full bg-orange-500 text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2 animate-bounce">
        Finaliser <i class="fas fa-arrow-down"></i>
    </button>
</div>

<script>
// Sélection du véhicule
document.querySelectorAll('.vehicle-option').forEach(option => {
    option.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        
        // Reset styles
        document.querySelectorAll('.vehicle-option').forEach(o => {
            o.classList.remove('border-orange-500', 'bg-orange-50', 'ring-2', 'ring-orange-200');
            o.querySelector('.fa-check-circle').classList.add('opacity-0');
        });
        
        // Apply selected style
        this.classList.add('border-orange-500', 'bg-orange-50', 'ring-2', 'ring-orange-200');
        this.querySelector('.fa-check-circle').classList.remove('opacity-0');
        
        // Update form values
        document.getElementById('id_vehicule').value = radio.value;
        document.getElementById('vehicule-nom').textContent = radio.dataset.marque + ' ' + radio.dataset.modele;
        document.getElementById('vehicule-prix').textContent = '$ ' + radio.dataset.prix + ' /jour';
        
        const imgSrc = radio.dataset.image || 'default.jpg';
        document.getElementById('selected-img').src = '<?= BASE_URL ?>/uploads/vehicules/' + imgSrc;
        
        document.getElementById('vehicule-selectionne').classList.remove('hidden');
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('mobile-next-btn').classList.remove('hidden');
        
        calculateTotal();
    });
});

// Calcul du total
const dateDebut = document.getElementById('date_debut');
const dateFin = document.getElementById('date_fin');

function calculateTotal() {
    const debut = new Date(dateDebut.value);
    const fin = new Date(dateFin.value);
    const diffTime = Math.abs(fin - debut);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
    
    const nbJours = diffDays > 0 ? diffDays : 0;
    document.getElementById('nb-jours').textContent = nbJours + ' jour(s)';
    
    const selectedRadio = document.querySelector('input[name="vehicule_select"]:checked');
    if (selectedRadio && nbJours > 0) {
        const total = nbJours * parseFloat(selectedRadio.dataset.prix);
        document.getElementById('total-estime').textContent = '$ ' + total.toLocaleString('en-US');
    } else {
        document.getElementById('total-estime').textContent = '$ 0';
    }
}

dateDebut.addEventListener('change', function() {
    dateFin.min = this.value;
    calculateTotal();
});
dateFin.addEventListener('change', calculateTotal);

function scrollToForm() {
    document.getElementById('form-section').scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => {
        document.getElementById('mobile-next-btn').classList.add('hidden');
    }, 500);
}
</script>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>