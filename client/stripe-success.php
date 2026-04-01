<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/payment_config.php';
requireClient();

// IMPORTATION MANUELLE DE STRIPE (Exactement comme dans paiement.php)
require_once __DIR__ . '/../includes/stripe-php-master/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

 $sessionId = $_GET['session_id'] ?? null;
 $payId = (int)($_GET['pay_id'] ?? 0);

if ($sessionId && $payId) {
    try {
        // Récupérer les infos de la session Stripe
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        // Si le paiement est validé par Stripe
        if ($session->payment_status === 'paid') {
            $db = getDB();
            
            // 1. Mettre à jour le statut du paiement
            $stmt = $db->prepare("UPDATE Paiements SET statut_transaction = 'Payé', date_paiement = NOW() WHERE id_paiement = ? AND statut_transaction = 'En attente'");
            $stmt->execute([$payId]);

            // 2. Mettre à jour le statut de la réservation
            $stmt = $db->prepare("UPDATE Reservations r 
                                  JOIN Contrats c ON r.id_reservation = c.id_reservation 
                                  JOIN Paiements p ON c.id_contrat = p.id_contrat
                                  SET r.statut = 'Confirmée' 
                                  WHERE p.id_paiement = ?");
            $stmt->execute([$payId]);
        }
    } catch (Exception $e) {
        // En cas d'erreur avec l'API Stripe, on redirige quand même vers la page de confirmation
        // sans valider le paiement en base
    }
}

// Rediriger vers la page de confirmation standard du site
redirect('/client/paiement-confirmation.php?id=' . $payId . '&success=1');
?>