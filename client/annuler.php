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
 $idReservation = intval($_GET['id'] ?? 0);

if ($idReservation > 0) {
    // Vérifier que la réservation appartient au client et est annulable
    $stmt = $db->prepare("SELECT * FROM Reservations WHERE id_reservation = ? AND id_client = ? AND statut = 'En attente'");
    $stmt->execute([$idReservation, $idClient]);
    $res = $stmt->fetch();

    if ($res) {
        // Mettre à jour le statut
        $update = $db->prepare("UPDATE Reservations SET statut = 'Annulée' WHERE id_reservation = ?");
        $update->execute([$idReservation]);
        
        // Libérer le véhicule
        $db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")->execute([$res['id_vehicule']]);
        
        $_SESSION['success'] = "Réservation annulée avec succès.";
    } else {
        $_SESSION['error'] = "Impossible d'annuler cette réservation.";
    }
}

redirect('/client/reservations.php');
?>