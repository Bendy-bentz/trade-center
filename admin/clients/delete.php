<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();

$id = $_GET['id'] ?? 0;

// Récupérer le client pour avoir l'id_utilisateur
$stmt = $db->prepare("SELECT id_utilisateur FROM Clients WHERE id_client = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if ($client) {
    $db->beginTransaction();
    try {
        // Supprimer le client
        $stmt = $db->prepare("DELETE FROM Clients WHERE id_client = ?");
        $stmt->execute([$id]);
        
        // Supprimer l'utilisateur associé
        $stmt = $db->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$client['id_utilisateur']]);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
    }
}

redirect('/admin/clients/index.php');
