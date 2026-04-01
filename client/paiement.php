<?php
/**
 * Page de paiement - Client
 * TradecenterEntreprise
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/payment_config.php';
requireClient();

// NEUTRALISÉ : On n'importe plus la classe Stripe pour éviter les erreurs fatales
// require_once __DIR__ . '/../includes/stripe-php-master/init.php';

 $db = getDB();
 $userId = getUserId();

// Récupérer l'ID client
 $client = $db->prepare("SELECT c.*, u.email FROM Clients c JOIN Utilisateurs u ON c.id_utilisateur = u.id_utilisateur WHERE c.id_utilisateur = ?");
 $client->execute([$userId]);
 $client = $client->fetch();

if (!$client) {
    redirect('/client/index.php');
}

 $idClient = $client['id_client'];

// Récupérer l'ID de réservation depuis l'URL
 $idReservation = isset($_GET['reservation']) ? (int)$_GET['reservation'] : 0;

// Vérifier que la réservation existe et appartient au client
 $stmt = $db->prepare("SELECT r.*, v.marque, v.modele, v.immatriculation, v.prix_jour, v.image,
                      DATEDIFF(r.date_fin, r.date_debut) as nb_jours
                      FROM Reservations r 
                      JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
                      WHERE r.id_reservation = ? AND r.id_client = ? AND r.statut IN ('En attente', 'Confirmée')");
 $stmt->execute([$idReservation, $idClient]);
 $reservation = $stmt->fetch();

if (!$reservation) {
    redirect('/client/reservations.php?error=not_found');
}

 $montantTotal = $reservation['nb_jours'] * $reservation['prix_jour'];

// Récupérer les méthodes de paiement actives
 $methodesPaiement = getActivePaymentMethods();

// Traitement du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMethode = (int)$_POST['id_methode'];
    $telephone = sanitize($_POST['telephone'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Récupérer les détails de la méthode
    $stmt = $db->prepare("SELECT * FROM Methodes_Paiement WHERE id_methode = ? AND actif = TRUE");
    $stmt->execute([$idMethode]);
    $methode = $stmt->fetch();
    
    if (!$methode) {
        $error = "Méthode de paiement invalide.";
    } else {
        // Calculer les frais
        $fraisPourcentage = (float)$methode['frais_pourcentage'] ?? 0;
        $fraisFixe = (float)$methode['frais_fixe'] ?? 0;
        $frais = ($montantTotal * $fraisPourcentage / 100) + $fraisFixe;
        $montantAvecFrais = $montantTotal + $frais;
        
        // Créer ou récupérer le contrat
        $stmt = $db->prepare("SELECT * FROM Contrats WHERE id_reservation = ?");
        $stmt->execute([$idReservation]);
        $contrat = $stmt->fetch();
        
        if (!$contrat) {
            $numeroContrat = generateReference('CTR');
            $stmt = $db->prepare("INSERT INTO Contrats (numero_contrat, id_reservation, montant_total, caution) VALUES (?, ?, ?, ?)");
            $stmt->execute([$numeroContrat, $idReservation, $montantTotal, 0]);
            $idContrat = $db->lastInsertId();
        } else {
            $idContrat = $contrat['id_contrat'];
        }
        
        // Créer le paiement
        $referencePaiement = generateReference('PAY');
        $stmt = $db->prepare("INSERT INTO Paiements (reference_paiement, id_contrat, mode_paiement, montant, id_methode, telephone_paiement, frais_transaction, statut_transaction, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'En attente', ?)");
        $stmt->execute([
            $referencePaiement,
            $idContrat,
            $methode['nom'],
            $montantAvecFrais,
            $idMethode,
            $telephone,
            $frais,
            $notes
        ]);
        $idPaiement = $db->lastInsertId();
        
        // Traiter selon le type de paiement
        if (in_array($methode['code'], ['moncash', 'natcash'])) {
            // --- PAIEMENT MOBILE MONEY ---
            try {
                $gateway = PaymentGatewayFactory::create($methode['code']);
                $result = $gateway->createPayment(
                    $montantAvecFrais,
                    $telephone,
                    $referencePaiement,
                    "Réservation #{$reservation['reference']}"
                );
                
                if ($result['success']) {
                    $stmt = $db->prepare("UPDATE Paiements SET transaction_id = ? WHERE id_paiement = ?");
                    $stmt->execute([$result['transaction_id'], $idPaiement]);
                    
                    $stmt = $db->prepare("INSERT INTO Transactions_Mobile (id_paiement, fournisseur, reference_externe, numero_telephone, montant, statut) 
                                          VALUES (?, ?, ?, ?, ?, 'En attente')");
                    $stmt->execute([
                        $idPaiement,
                        ucfirst($methode['code']),
                        $result['transaction_id'],
                        $telephone,
                        $montantAvecFrais
                    ]);
                    
                    redirect('/client/paiement-confirmation.php?id=' . $idPaiement . '&transaction=' . $result['transaction_id']);
                } else {
                    $error = $result['error'] ?? "Erreur lors de l'initiation du paiement.";
                }
            } catch (Exception $e) {
                $error = "Erreur de connexion au service de paiement: " . $e->getMessage();
            }
            
        // ====================================================================
        // BLOC STRIPE NEUTRALISÉ POUR LA SOUTENANCE
        // ====================================================================
        } elseif ($methode['code'] === 'stripe') {
            // On affiche juste un message d'erreur propre sans appeler l'API
            $error = "Le paiement par carte bancaire sera bientôt disponible. Veuillez choisir une autre méthode de paiement (MonCash, Espèces, Virement).";
        // ====================================================================

        } else {
            // --- PAIEMENT CLASSIQUE ---
            redirect('/client/paiement-confirmation.php?id=' . $idPaiement);
        }
    }
}

 $pageTitle = 'Paiement';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<!-- Page Title -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800 text-center">Paiement de la réservation</h1>
    <p class="text-gray-500 mt-1 text-center">Complétez votre paiement pour confirmer la réservation</p>
</div>

<!-- Conteneur centré avec largeur maximale -->
<div class="max-w-3xl mx-auto items-center">
      <!-- Récapitulatif de la réservation -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="bg-orange-100 px-6 py-4">
            <h3 class="text-lg font-semibold text-gray-800 text-center">Récapitulatif</h3>
        </div>
        
        <div class="p-6 items-center">
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <div class="w-full md:w-40 h-28 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0 shadow-inner border border-gray-200 image-center">
                    <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($reservation['image'] ?? 'default.jpg') ?>" 
                         alt="<?= htmlspecialchars($reservation['marque'] ?? 'Véhicule') ?>" 
                         class="w-full h-full object-cover">
                </div>
                
                <div class="flex-1">
                    <div class="mb-2">
                        <h4 class="text-xl font-bold text-gray-800"><?= $reservation['marque'] ?> <?= $reservation['modele'] ?></h4>
                        <p class="text-sm text-gray-500"><?= $reservation['immatriculation'] ?></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm mt-4">
                        <div>
                            <span class="text-gray-400 block text-xs uppercase">Référence</span>
                            <span class="font-medium text-gray-700"><?= $reservation['reference'] ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs uppercase">Durée</span>
                            <span class="font-medium text-gray-700"><?= $reservation['nb_jours'] ?> jour(s)</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs uppercase">Début</span>
                            <span class="font-medium text-gray-700"><?= formatDate($reservation['date_debut']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs uppercase">Retour</span>
                            <span class="font-medium text-gray-700"><?= formatDate($reservation['date_fin']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t flex justify-between items-center">
                <div>
                    <span class="text-gray-600 font-medium">Total à payer</span>
                    <p class="text-xs text-gray-400" id="fees-display"></p>
                </div>
                <span class="text-3xl font-extrabold text-orange-500" id="total-display">$ <?= number_format($montantTotal, 2) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Erreurs -->
<?php if (isset($error)): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center max-w-2xl mx-auto">
    <i class="fas fa-exclamation-circle mr-3"></i> <?= $error ?>
</div>
<?php endif; ?>

       <!-- Formulaire -->
    <div class="max-w-2xl mx-auto">
        <form method="POST" id="paymentForm" class="space-y-6">
            
            <!-- Méthodes de paiement -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Méthode de paiement</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($methodesPaiement as $methode): ?>
                    
                    <?php 
                    $logoFile = $methode['image'] ?? ($methode['logo'] ?? '');
                    if (empty($logoFile)) {
                        $logoFile = strtolower($methode['code']) . '.jpg';
                    }
                    $logoSrc = BASE_URL . '/assets/images/payments/' . $logoFile;
                    ?>
                    
                    <label class="block p-4 border-2 rounded-xl cursor-pointer transition hover:border-orange-300 payment-method group" 
                           data-code="<?= $methode['code'] ?>" 
                           data-fees-percent="<?= $methode['frais_pourcentage'] ?? 0 ?>"
                           data-fees-fixed="<?= $methode['frais_fixe'] ?? 0 ?>">
                        
                        <div class="flex flex-row lg:flex-col items-center gap-4 lg:text-center">
                            
                            <input type="radio" name="id_methode" value="<?= $methode['id_methode'] ?>" class="hidden" required>
                            
                            <div class="w-24 h-14 lg:w-32 lg:h-16 rounded-lg bg-white border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-sm p-1">
                                <?php if (!empty($logoFile)): ?>
                                    <img src="<?= htmlspecialchars($logoSrc) ?>" 
                                         alt="<?= htmlspecialchars($methode['nom']) ?>" 
                                         class="w-full h-full object-contain"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <i class="fas fa-credit-card text-2xl text-gray-300" style="display:none;"></i>
                                <?php else: ?>
                                    <i class="fas fa-credit-card text-2xl text-gray-300"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-1 min-w-0 flex flex-col lg:items-center">
                                <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($methode['nom']) ?></p>
                                <?php if ($methode['frais_pourcentage'] > 0): ?>
                                    <p class="text-xs text-orange-500">+<?= $methode['frais_pourcentage'] ?>% frais</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full check-circle flex items-center justify-center flex-shrink-0 self-start lg:self-center">
                                <i class="fas fa-check text-white text-xs hidden"></i>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Section Mobile -->
            <div id="mobilePaymentSection" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hidden">
                <div class="w-full lg:w-2/3 lg:mx-auto">
                    <label class="block text-sm font-bold text-gray-700 mb-2 text-center lg:text-left">Numéro de téléphone</label>
                    <input type="tel" name="telephone" id="telephone" 
                           placeholder="+509 37XX XXXX"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm text-center lg:text-left">
                    <p class="text-xs text-gray-500 mt-2 text-center" id="phoneHint">Entrez le numéro lié à votre compte.</p>
                </div>
            </div>
            
            <!-- Instructions -->
            <div id="paymentInstructions" class="bg-blue-50 border border-blue-200 text-blue-700 p-4 rounded-lg hidden">
                <p class="text-sm" id="instructionText"></p>
            </div>

            <!-- Bouton Submit -->
            <div class="text-center">
                <button type="submit" class="w-full sm:w-auto lg:w-1/2 bg-orange-500 hover:bg-orange-600 text-white py-3.5 px-6 rounded-xl font-bold transition shadow-lg inline-flex items-center justify-center gap-2" id="submitBtn">
                    <i class="fas fa-lock"></i> Payer $ <span id="btnAmount"><?= number_format($montantTotal, 2) ?></span>
                </button>
            </div>
            
        </form>
    </div>
<script>
const baseAmount = <?= $montantTotal ?>;
const paymentMethods = document.querySelectorAll('.payment-method');
const mobileSection = document.getElementById('mobilePaymentSection');
const phoneInput = document.getElementById('telephone');
const phoneHint = document.getElementById('phoneHint');
const instructionSection = document.getElementById('paymentInstructions');
const instructionText = document.getElementById('instructionText');
const totalDisplay = document.getElementById('total-display');
const feesDisplay = document.getElementById('fees-display');
const btnAmount = document.getElementById('btnAmount');
const submitBtn = document.getElementById('submitBtn');

const instructions = {
    'moncash': 'Après avoir cliqué sur "Procéder au paiement", vous recevrez une notification sur votre téléphone MonCash. Veuillez confirmer la transaction en entrant votre code PIN.',
    'natcash': 'Après avoir cliqué sur "Procéder au paiement", vous recevrez une demande de paiement NatCash. Confirmez avec votre code secret pour valider.',
    'card': 'Vous serez redirigé vers une page sécurisée pour entrer les détails de votre carte bancaire.',
    'cash': 'Votre réservation sera confirmée après réception du paiement en espèces dans nos bureaux.',
    'transfer': 'Les coordonnées bancaires vous seront communiquées par email pour effectuer le virement.',
    'stripe': 'Le paiement par carte bancaire international sera bientôt disponible.'
};

const phoneHints = {
    'moncash': 'Entrez votre numéro MonCash (format: +509 37XX XXXX)',
    'natcash': 'Entrez votre numéro NatCash (format: +509 4X XX XXXX)'
};

paymentMethods.forEach(method => {
    method.addEventListener('click', function() {
        paymentMethods.forEach(m => {
            m.classList.remove('border-orange-500', 'bg-orange-50');
            m.querySelector('.check-circle').classList.remove('bg-orange-500', 'border-orange-500');
            m.querySelector('.check-circle i').classList.add('hidden');
            m.querySelector('input').checked = false;
        });
        
        this.classList.add('border-orange-500', 'bg-orange-50');
        this.querySelector('.check-circle').classList.add('bg-orange-500', 'border-orange-500');
        this.querySelector('.check-circle i').classList.remove('hidden');
        this.querySelector('input').checked = true;
        
        const code = this.dataset.code;
        const feesPercent = parseFloat(this.dataset.feesPercent) || 0;
        const feesFixed = parseFloat(this.dataset.feesFixed) || 0;
        
        const fees = (baseAmount * feesPercent / 100) + feesFixed;
        const total = baseAmount + fees;
        
        totalDisplay.textContent = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        btnAmount.textContent = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        if (fees > 0) {
            feesDisplay.textContent = 'Frais de transaction: $' + fees.toFixed(2);
            feesDisplay.classList.remove('hidden');
        } else {
            feesDisplay.classList.add('hidden');
        }
        
        const isMobile = ['moncash', 'natcash'].includes(code);
        if (isMobile) {
            mobileSection.classList.remove('hidden');
            phoneInput.required = true;
            phoneHint.textContent = phoneHints[code] || 'Entrez votre numéro de téléphone';
        } else {
            mobileSection.classList.add('hidden');
            phoneInput.required = false;
        }

        if (instructions[code]) {
            instructionText.textContent = instructions[code];
            instructionSection.classList.remove('hidden');
        } else {
            instructionSection.classList.add('hidden');
        }
    });
});

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const selectedMethod = document.querySelector('input[name="id_methode"]:checked');
    if (!selectedMethod) {
        e.preventDefault();
        alert('Veuillez sélectionner une méthode de paiement');
        return;
    }
    
    const code = selectedMethod.closest('.payment-method').dataset.code;
    if (['moncash', 'natcash'].includes(code) && !phoneInput.value.trim()) {
        e.preventDefault();
        alert('Veuillez entrer votre numéro de téléphone');
        return;
    }
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
    submitBtn.disabled = true;
});
</script>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>