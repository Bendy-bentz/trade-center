<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();

$id = $_GET['id'] ?? 0;

// Vérifier si le véhicule a des réservations
$stmt = $db->prepare("SELECT COUNT(*) FROM Reservations WHERE id_vehicule = ?");
$stmt->execute([$id]);

if ($stmt->fetchColumn() > 0) {
    // Marquer comme indisponible au lieu de supprimer
    $stmt = $db->prepare("UPDATE Vehicules SET etat = 'Indisponible' WHERE id_vehicule = ?");
    $stmt->execute([$id]);
} else {
    // Supprimer l'image si elle existe
    $stmt = $db->prepare("SELECT image FROM Vehicules WHERE id_vehicule = ?");
    $stmt->execute([$id]);
    $vehicule = $stmt->fetch();
    
    if ($vehicule && $vehicule['image']) {
        $imagePath = __DIR__ . '/../../assets/images/vehicules/' . $vehicule['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Supprimer le véhicule
    $stmt = $db->prepare("DELETE FROM Vehicules WHERE id_vehicule = ?");
    $stmt->execute([$id]);
}

redirect('/admin/vehicules/index.php');
