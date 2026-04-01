<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();

$id = $_GET['id'] ?? 0;

// Ne pas supprimer son propre compte
if ($id == getUserId()) {
    redirect('/admin/utilisateurs/index.php');
}

// Vérifier si c'est un client
$stmt = $db->prepare("SELECT id_client FROM Clients WHERE id_utilisateur = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

$db->beginTransaction();
try {
    if ($client) {
        // Supprimer les données liées au client
        // (les réservations, contrats, paiements doivent être gérés selon les contraintes FK)
        $stmt = $db->prepare("DELETE FROM Clients WHERE id_utilisateur = ?");
        $stmt->execute([$id]);
    }
    
    // Supprimer l'utilisateur
    $stmt = $db->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$id]);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}

redirect('/admin/utilisateurs/index.php');
