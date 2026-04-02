<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin(); // Il suffit d'être connecté

 $db = getDB();
 $id = intval($_GET['id'] ?? 0);

// 1. Récupérer les infos
 $stmt = $db->prepare("SELECT r.*, 
                      c.nom as client_nom, 
                      c.prenom as client_prenom, 
                      c.adresse, 
                      c.telephone, 
                      c.email,
                      v.marque, v.modele, v.immatriculation, v.carburant, v.prix_jour
                      FROM Reservations r
                      JOIN Clients c ON r.id_client = c.id_client
                      JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
                      WHERE r.id_reservation = ?");
 $stmt->execute([$id]);
 $res = $stmt->fetch();

if (!$res) die("Réservation introuvable.");

// 2. Vérification des droits
if (isClient()) {
    // Si c'est un client, on vérifie que c'est SA réservation
    $stmtMe = $db->prepare("SELECT id_client FROM Clients WHERE id_utilisateur = ?");
    $stmtMe->execute([$_SESSION['user_id']]);
    $myId = $stmtMe->fetchColumn();
    
    if ($res['id_client'] != $myId) {
        die("Accès refusé : Ce contrat ne vous appartient pas.");
    }
}
// Si c'est un Admin ou Agent, on laisse passer.

// 3. Calcul du prix
 $total = $res['prix_total'] ?? 0;
if ($total <= 0) {
    $days = max(1, (strtotime($res['date_fin']) - strtotime($res['date_debut'])) / 86400);
    $total = $days * $res['prix_jour'];
}
?>
<!-- ... Le reste du code HTML reste identique ... -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat #<?= $id ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; }
        .page-container { max-width: 21cm; margin: 0 auto; background: white; padding: 2.5cm; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        @media print { body { background: white; padding: 0; } .page-container { box-shadow: none; margin: 0; padding: 0; width: 100%; max-width: 100%; } .no-print { display: none !important; } }
        header { border-bottom: 3px solid #f97316; padding-bottom: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        h1 { color: #1f2937; margin: 0; font-size: 24px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .box { border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; background: #f9fafb; }
        .box h3 { margin-top: 0; color: #f97316; font-size: 14px; text-transform: uppercase; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; width: 35%; font-weight: 600; color: #374151; }
        .total-row td { font-size: 18px; font-weight: bold; color: #f97316; border-bottom: none; background: #fff7ed; }
        .signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; text-align: center; }
        .sign-line { border-top: 1px solid #000; margin-top: 60px; padding-top: 5px; font-weight: bold; }
        .print-btn { position: fixed; bottom: 20px; right: 20px; background: #f97316; color: white; border: none; padding: 15px 25px; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-size: 16px; }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn no-print">🖨️ Imprimer / PDF</button>

    <div class="page-container">
        <header>
            <div>
                <h1>CONTRAT DE LOCATION</h1>
                <p style="margin:5px 0 0 0; color:#6b7280;">Référence : #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div style="text-align: right">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" style="height:50px;">
            </div>
        </header>

        <div class="info-grid">
            <div class="box">
                <h3>Le Loueur</h3>
                <p><strong>Trade Center Location</strong></p>
                <p>123 Avenue Mohammed V<br>Casablanca, Maroc<br>Tél: +212 5XX-XXXXXX</p>
            </div>
            <div class="box">
                <h3>Le Locataire</h3>
                <!-- CORRECTION ICI : utilisation de client_prenom et client_nom -->
                <p><strong><?= htmlspecialchars($res['client_prenom'] . ' ' . $res['client_nom']) ?></strong></p>
                <p>
                    <?= htmlspecialchars($res['adresse'] ?? 'Adresse non renseignée') ?><br>
                    <?= htmlspecialchars($res['telephone'] ?? '') ?><br>
                    Email: <?= htmlspecialchars($res['email'] ?? '') ?>
                </p>
            </div>
        </div>

        <div class="box" style="margin-bottom: 30px;">
            <h3>Véhicule</h3>
            <p><strong><?= htmlspecialchars($res['marque'] . ' ' . $res['modele']) ?></strong> (<?= htmlspecialchars($res['immatriculation']) ?>)</p>
        </div>

        <table>
            <tr>
                <th>Date de départ</th>
                <td><?= date('d/m/Y H:i', strtotime($res['date_debut'])) ?></td>
            </tr>
            <tr>
                <th>Date de retour</th>
                <td><?= date('d/m/Y H:i', strtotime($res['date_fin'])) ?></td>
            </tr>
            <tr>
                <th>Carburant</th>
                <td><?= htmlspecialchars($res['carburant']) ?></td>
            </tr>
            <tr class="total-row">
                <td>Montant Total</td>
                <td>$ <?= number_format($total, 2) ?></td>
            </tr>
        </table>

        <p style="font-size: 10px; color: #6b7280; margin-top: 40px;">
            Le locataire déclare avoir pris connaissance des conditions générales de location et les accepter.
        </p>

        <div class="signatures">
            <div>
                <div class="sign-line">Le Loueur</div>
            </div>
            <div>
                <div class="sign-line">Le Locataire</div>
            </div>
        </div>
    </div>

</body>
</html>