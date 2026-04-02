<?php
/**
 * Page de confirmation de paiement - Client
 */
require_once __DIR__ . '/../config/config.php';
// Assurez-vous que ce fichier existe et contient PaymentGatewayFactory et isSimulationMode()
if (file_exists(__DIR__ . '/../config/payment_config.php')) {
    require_once __DIR__ . '/../config/payment_config.php';
}

requireClient();

 $db = getDB();
 $userId = getUserId();

 $idPaiement = isset($_GET['id']) ? (int)$_GET['id'] : 0;
 $transactionId = $_GET['transaction'] ?? null;

// Récupérer les détails du paiement
 $stmt = $db->prepare("SELECT p.*, c.numero_contrat, c.montant_total, 
                      r.reference as reservation_ref, r.date_debut, r.date_fin, r.statut as reservation_statut, r.id_reservation,
                      v.marque, v.modele, v.immatriculation,
                      mp.nom as methode_nom, mp.code as methode_code
                      FROM Paiements p
                      JOIN Contrats c ON p.id_contrat = c.id_contrat
                      JOIN Reservations r ON c.id_reservation = r.id_reservation
                      JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
                      LEFT JOIN Methodes_Paiement mp ON p.id_methode = mp.id_methode
                      WHERE p.id_paiement = ?");
 $stmt->execute([$idPaiement]);
 $paiement = $stmt->fetch();

if (!$paiement) {
    redirect('/client/reservations.php?error=not_found');
}

// Vérifier que le client est le propriétaire
 $stmt = $db->prepare("SELECT cl.id_client FROM Paiements p
                      JOIN Contrats c ON p.id_contrat = c.id_contrat
                      JOIN Reservations r ON c.id_reservation = r.id_reservation
                      JOIN Clients cl ON r.id_client = cl.id_client
                      JOIN Utilisateurs u ON cl.id_utilisateur = u.id_utilisateur
                      WHERE p.id_paiement = ? AND u.id_utilisateur = ?");
 $stmt->execute([$idPaiement, $userId]);
 $ownerCheck = $stmt->fetch();

if (!$ownerCheck) {
    redirect('/client/reservations.php?error=unauthorized');
}

// Définir si on est en simulation (si la fonction n'existe pas, on met false)
 $simulationMode = function_exists('isSimulationMode') ? isSimulationMode() : false;

// Traitement du statut
if ($transactionId && in_array($paiement['methode_code'], ['moncash', 'natcash'])) {
    
    if ($simulationMode) {
        // SIMULATION : Confirmer automatiquement
        $stmt = $db->prepare("UPDATE Paiements SET statut_transaction = 'Confirmée', recu = TRUE, date_confirmation = NOW() WHERE id_paiement = ?");
        $stmt->execute([$idPaiement]);
        
        $stmt = $db->prepare("UPDATE Transactions_Mobile SET statut = 'Confirmée', date_confirmation = NOW() WHERE id_paiement = ?");
        $stmt->execute([$idPaiement]);
        
        $stmt = $db->prepare("UPDATE Reservations SET statut = 'Confirmée' WHERE id_reservation = ?");
        $stmt->execute([$paiement['id_reservation']]);
        
        $paiement['statut_transaction'] = 'Confirmée';
        $paiement['recu'] = 1;
    } else {
        // MODE RÉEL : Vérifier via API (si factory existe)
        if (class_exists('PaymentGatewayFactory')) {
            try {
                $gateway = PaymentGatewayFactory::create($paiement['methode_code']);
                $result = $gateway->verifyPayment($transactionId);
                if ($result['success'] && $result['status'] === 'Confirmée') {
                    $stmt = $db->prepare("UPDATE Paiements SET statut_transaction = 'Confirmée', recu = TRUE, date_confirmation = NOW() WHERE id_paiement = ?");
                    $stmt->execute([$idPaiement]);
                    $stmt = $db->prepare("UPDATE Reservations SET statut = 'Confirmée' WHERE id_reservation = ?");
                    $stmt->execute([$paiement['id_reservation']]);
                    $paiement['statut_transaction'] = 'Confirmée';
                }
            } catch (Exception $e) {
                error_log("Erreur API Paiement: " . $e->getMessage());
            }
        }
    }
}

 $pageTitle = 'Confirmation';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<div class="max-w-3xl mx-auto">
    <?php if ($paiement['statut_transaction'] === 'Confirmée' || $paiement['recu']): ?>
    <!-- SUCCÈS -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-green-500 px-8 py-12 text-center">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                <i class="fas fa-check text-green-500 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Paiement confirmé !</h1>
            <p class="text-green-100">Votre réservation est validée.</p>
        </div>
        
        <div class="p-8">
            <!-- Récapitulatif -->
            <div class="grid grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Référence</p>
                    <p class="font-bold text-gray-800"><?= htmlspecialchars($paiement['reference_paiement']) ?></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Véhicule</p>
                    <p class="font-bold text-gray-800"><?= $paiement['marque'] ?> <?= $paiement['modele'] ?></p>
                </div>
            </div>
            
            <!-- Total Payé -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8 text-center">
                <p class="text-sm text-gray-500 mb-1">Total payé</p>
                <!-- FORMAT DOLLAR -->
                <p class="text-3xl font-extrabold text-gray-900">$ <?= number_format($paiement['montant'], 2) ?></p>
                <?php if ($paiement['frais_transaction'] > 0): ?>
                <p class="text-xs text-gray-400 mt-1">(inclut $<?= number_format($paiement['frais_transaction'], 2) ?> de frais)</p>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="flex gap-4">
                <a href="<?= BASE_URL ?>/client/reservations.php" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-xl font-bold text-center transition">
                    <i class="fas fa-list mr-2"></i>Mes réservations
                </a>
                <a href="<?= BASE_URL ?>/client/index.php" class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 py-3 rounded-xl font-bold text-center transition">
                    <i class="fas fa-home mr-2"></i>Accueil
                </a>
            </div>
        </div>
    </div>
    
    <?php elseif ($paiement['statut_transaction'] === 'Échouée'): ?>
    <!-- ÉCHEC -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-red-500 px-8 py-12 text-center">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                <i class="fas fa-times text-red-500 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Paiement échoué</h1>
            <p class="text-red-100">Une erreur est survenue.</p>
        </div>
        
        <div class="p-8 text-center">
            <p class="text-gray-600 mb-6">Solde insuffisant ou transaction annulée.</p>
            <div class="flex gap-4">
                <a href="<?= BASE_URL ?>/client/paiement.php?reservation=<?= $paiement['id_reservation'] ?>" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-xl font-bold transition">
                    <i class="fas fa-redo mr-2"></i>Réessayer
                </a>
                <a href="<?= BASE_URL ?>/client/reservations.php" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-50 transition">
                    Annuler
                </a>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- EN ATTENTE -->
    <div class="bg-gray-800 rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-800 px-8 py-12 text-center">
            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                <i class="fas fa-clock text-orange-500 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-100 mb-2">Paiement en attente</h1>
            <p class="text-gray-100">Veuillez valider la transaction sur votre téléphone.</p>
        </div>
        
        <div class="p-8">
            <!-- Instructions Mobiles -->
            <?php if (in_array($paiement['methode_code'], ['moncash', 'natcash'])): ?>
            <div class="bg-orange-50 rounded-xl p-6 mb-6">
                <h3 class="font-bold text-orange-800 mb-3 text-sm uppercase tracking-wide">Instructions <?= htmlspecialchars($paiement['methode_nom']) ?></h3>
                <ol class="text-orange-700 space-y-3 text-sm">
                    <li class="flex items-start gap-3">
                        <span class="bg-orange-200 text-orange-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</span>
                        <span>Ouvrez l'application <strong><?= htmlspecialchars($paiement['methode_nom']) ?></strong>.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="bg-orange-200 text-orange-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
                        <span>Validez la demande de <strong>$ <?= number_format($paiement['montant'], 2) ?></strong>.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="bg-orange-200 text-orange-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</span>
                        <span>Entrez votre code PIN.</span>
                    </li>
                </ol>
            </div>
            <?php endif; ?>
            
            <!-- Récapitulatif -->
            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500">Référence</p>
                        <p class="font-bold text-gray-800"><?= htmlspecialchars($paiement['reference_paiement']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Montant</p>
                        <!-- FORMAT DOLLAR -->
                        <p class="font-bold text-xl text-orange-500">$ <?= number_format($paiement['montant'], 2) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Bouton Refresh -->
            <div class="text-center">
                <button onclick="location.reload()" class="bg-gray-900 hover:bg-gray-800 text-white px-8 py-3 rounded-xl font-bold transition">
                    <i class="fas fa-sync-alt mr-2"></i>Vérifier le statut
                </button>
                <p class="text-xs text-gray-400 mt-4">Actualisation automatique dans 5s...</p>
            </div>
            
            <!-- Auto-refresh -->
            <script>
            setTimeout(function() {
                location.reload();
            }, 5000);
            </script>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>