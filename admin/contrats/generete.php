<?php
// Augmenter la mémoire pour TCPDF
ini_set('memory_limit', '256M');

require_once __DIR__ . '/../../config/config.php';

// Sécurité
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

// Inclusion TCPDF
if (file_exists(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    die("Erreur : Impossible de trouver TCPDF.");
}

 $idReservation = intval($_GET['id'] ?? 0);
if ($idReservation <= 0) die("ID invalide.");

 $db = getDB();

 $stmt = $db->prepare("
    SELECT r.*, 
           c.id_client, c.nom as client_nom, c.prenom as client_prenom, c.adresse as client_adresse, 
           c.telephone as client_tel, c.email as client_email, c.cin,
           v.marque, v.modele, v.immatriculation, v.carburant, v.prix_jour, v.image
    FROM Reservations r
    JOIN Clients c ON r.id_client = c.id_client
    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
    WHERE r.id_reservation = ?
");
 $stmt->execute([$idReservation]);
 $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) die("Réservation introuvable.");

// Sécurité Client
if (isClient()) {
    $stmtUser = $db->prepare("SELECT id_client FROM Clients WHERE id_utilisateur = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $clientId = $stmtUser->fetchColumn();
    if ($clientId != $reservation['id_client']) die("Accès refusé.");
}

// --- PRÉPARATION ---
 $prixTotal = $reservation['prix_total'] ?? 0;
if ($prixTotal <= 0) {
    $nbJours = max(1, (strtotime($reservation['date_fin']) - strtotime($reservation['date_debut'])) / 86400);
    $prixTotal = $nbJours * $reservation['prix_jour'];
}

 $dateDebut = date('d/m/Y H:i', strtotime($reservation['date_debut']));
 $dateFin = date('d/m/Y H:i', strtotime($reservation['date_fin']));

// --- PDF ---
class MYPDF extends TCPDF {
    public function Header() {
        // Chemin ABSOLU du logo pour TCPDF
        $logoFile = __DIR__ . '/../../assets/images/logo.png';
        if (file_exists($logoFile)) {
            $this->Image($logoFile, 15, 10, 45, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $this->SetDrawColor(249, 115, 22);
        $this->SetLineWidth(0.8);
        $this->Line(15, 25, 195, 25);
        $this->SetXY(15, 27);
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 10, 'CONTRAT DE LOCATION', 0, false, 'R', false, '', 0, false, 'T', 'M');
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Trade Center Location - Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

 $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
 $pdf->SetCreator(PDF_CREATOR);
 $pdf->SetAuthor('Trade Center');
 $pdf->SetTitle('Contrat N° ' . $idReservation);
 $pdf->SetMargins(15, 35, 15);
 $pdf->SetHeaderMargin(10);
 $pdf->SetFooterMargin(10);
 $pdf->SetFont('helvetica', '', 10);
 $pdf->AddPage();

// --- HTML ---
// Note: On utilise des styles simples compatibles TCPDF (pas de flexbox/grid)
 $html = '
<style>
    h1 { color: #1f2937; font-size: 14px; border-bottom: 2px solid #f97316; padding-bottom: 4px; margin-top: 15px; margin-bottom: 8px; text-transform: uppercase; }
    .info-box { background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 8px; margin-bottom: 10px; }
    .label { font-weight: bold; color: #6b7280; font-size: 9px; }
    .value { font-size: 10px; color: #111827; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 5px; vertical-align: top; }
</style>

<div class="info-box">
    <table>
        <tr>
            <td width="50%"><span class="label">N° CONTRAT</span><br><span class="value" style="font-size: 12px; font-weight: bold;">' . str_pad($idReservation, 5, '0', STR_PAD_LEFT) . '</span></td>
            <td width="50%" align="right"><span class="label">DATE</span><br><span class="value">' . date('d/m/Y') . '</span></td>
        </tr>
    </table>
</div>

<h1>1. Parties au contrat</h1>
<table border="0" cellpadding="4">
    <tr>
        <td width="50%" style="border: 1px solid #e5e7eb; background-color: #fcfcfc;">
            <p style="color:#f97316; font-weight:bold; font-size: 10px; margin:0 0 5px 0;">LOUEUR</p>
            <strong style="font-size: 11px;">Trade Center Location</strong><br>
            <span style="font-size: 9px; color: #4b5563;">123 Avenue Mohammed V<br>Casablanca, Maroc</span>
        </td>
        <td width="50%" style="border: 1px solid #e5e7eb;">
            <p style="color:#f97316; font-weight:bold; font-size: 10px; margin:0 0 5px 0;">LOCATAIRE</p>
            <strong style="font-size: 11px;">' . htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']) . '</strong><br>
            <span style="font-size: 9px; color: #4b5563;">CIN: ' . htmlspecialchars($reservation['cin'] ?? 'N/C') . '<br>Tél: ' . htmlspecialchars($reservation['client_tel']) . '</span>
        </td>
    </tr>
</table>

<h1>2. Véhicule loué</h1>
<table border="1" cellpadding="5" style="border-color:#e5e7eb; font-size: 10px;">
    <tr style="background-color:#f9fafb;">
        <td width="30%">Véhicule</td>
        <td width="70%"><strong>' . htmlspecialchars($reservation['marque'] . ' ' . $reservation['modele']) . '</strong></td>
    </tr>
    <tr>
        <td>Immatriculation</td>
        <td>' . htmlspecialchars($reservation['immatriculation']) . '</td>
    </tr>
    <tr>
        <td>Carburant</td>
        <td>' . htmlspecialchars($reservation['carburant']) . '</td>
    </tr>
</table>

<h1>3. Durée et Tarif</h1>
<table border="1" cellpadding="5" style="border-color:#e5e7eb; font-size: 10px;">
    <tr>
        <td width="30%">Date de départ</td>
        <td>' . $dateDebut . '</td>
    </tr>
    <tr>
        <td>Date de retour</td>
        <td>' . $dateFin . '</td>
    </tr>
    <tr style="background-color:#fff7ed;">
        <td><strong>MONTANT TOTAL</strong></td>
        <td><span style="color:#f97316; font-weight:bold; font-size:14px;">$ ' . number_format($prixTotal, 2) . '</span></td>
    </tr>
</table>

<h1>4. Signatures</h1>
<br><br><br>
<table border="0">
    <tr>
        <td width="45%" align="center">
            <div style="border-top: 1px solid #000; width: 80%; margin: auto; padding-top: 5px;"><strong>Le Loueur</strong></div>
        </td>
        <td width="10%"></td>
        <td width="45%" align="center">
            <div style="border-top: 1px solid #000; width: 80%; margin: auto; padding-top: 5px;"><strong>Le Locataire</strong></div>
        </td>
    </tr>
</table>
';

 $pdf->writeHTML($html, true, false, true, false, '');
 $pdf->Output('Contrat_TradeCenter_' . $idReservation . '.pdf', 'I');
exit;
?>