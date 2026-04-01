<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/client/reservations.php');
}

 $db = getDB();
 $userId = getUserId();

// Récupérer l'ID du client lié à l'utilisateur
 $stmt = $db->prepare("SELECT id_client FROM Clients WHERE id_utilisateur = ?");
 $stmt->execute([$userId]);
 $client = $stmt->fetch();
 $idClient = $client['id_client'] ?? 0;

 $idReservation = (int)$_POST['id_reservation'];
 $idVehicule = (int)$_POST['id_vehicule'];
 $note = (int)$_POST['note'];
 $commentaire = sanitize($_POST['commentaire'] ?? '');

// Sécurité : Vérifier que la réservation appartient bien à ce client, qu'elle est "Terminée", et qu'il n'a pas déjà laissé un avis
 $stmt = $db->prepare("SELECT r.id_reservation, r.statut FROM Reservations r 
                      WHERE r.id_reservation = ? AND r.id_client = ? AND r.id_vehicule = ? AND r.statut = 'Terminée'");
 $stmt->execute([$idReservation, $idClient, $idVehicule]);
 $reservation = $stmt->fetch();

if (!$reservation) {
    $_SESSION['error_review'] = "Vous ne pouvez pas laisser un avis pour cette réservation.";
    redirect('/client/reservations.php');
}

// Vérifier si un avis existe déjà
 $stmt = $db->prepare("SELECT id_avis FROM Avis WHERE id_reservation = ?");
 $stmt->execute([$idReservation]);
if ($stmt->fetch()) {
    $_SESSION['error_review'] = "Vous avez déjà laissé un avis pour cette location.";
    redirect('/client/reservations.php');
}

if ($note < 1 || $note > 5) {
    $_SESSION['error_review'] = "La note doit être entre 1 et 5.";
    redirect('/client/reservations.php');
}

// Insérer l'avis
 $stmt = $db->prepare("INSERT INTO Avis (id_reservation, id_vehicule, id_client, note, commentaire) VALUES (?, ?, ?, ?, ?)");
 $stmt->execute([$idReservation, $idVehicule, $idClient, $note, $commentaire]);

 $_SESSION['success_review'] = "Merci pour votre avis !";
redirect('/client/reservations.php');