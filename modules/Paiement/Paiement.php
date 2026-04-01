<?php
/**
 * Module Paiement - CRUD
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../../config/config.php';

class PaiementModule {
    
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Récupérer tous les paiements
     */
    public function getAll() {
        return $this->db->query("SELECT p.*, c.numero_contrat, r.reference, 
                                 cl.nom as client_nom, cl.prenom as client_prenom
                                 FROM Paiements p 
                                 JOIN Contrats c ON p.id_contrat = c.id_contrat 
                                 JOIN Reservations r ON c.id_reservation = r.id_reservation
                                 JOIN Clients cl ON r.id_client = cl.id_client
                                 ORDER BY p.date_paiement DESC")->fetchAll();
    }
    
    /**
     * Récupérer par contrat
     */
    public function getByContrat($idContrat) {
        $stmt = $this->db->prepare("SELECT * FROM Paiements WHERE id_contrat = ? ORDER BY date_paiement DESC");
        $stmt->execute([$idContrat]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer un paiement par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM Paiements WHERE id_paiement = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Créer un paiement
     */
    public function create($data) {
        $reference = generateReference('PAY');
        
        $stmt = $this->db->prepare("INSERT INTO Paiements (reference_paiement, id_contrat, mode_paiement, montant, recu, notes) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reference,
            (int)$data['id_contrat'],
            $data['mode_paiement'],
            (float)$data['montant'],
            isset($data['recu']) ? 1 : 0,
            sanitize($data['notes'] ?? '')
        ]);
        
        return ['id' => $this->db->lastInsertId(), 'reference' => $reference];
    }
    
    /**
     * Modifier un paiement
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE Paiements SET mode_paiement=?, montant=?, recu=?, notes=? WHERE id_paiement=?");
        return $stmt->execute([
            $data['mode_paiement'],
            (float)$data['montant'],
            isset($data['recu']) ? 1 : 0,
            sanitize($data['notes'] ?? ''),
            $id
        ]);
    }
    
    /**
     * Marquer comme reçu
     */
    public function marquerRecu($id) {
        $stmt = $this->db->prepare("UPDATE Paiements SET recu = TRUE WHERE id_paiement = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Supprimer un paiement
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM Paiements WHERE id_paiement = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Total des paiements reçus
     */
    public function getTotalRecu() {
        return $this->db->query("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = TRUE")->fetchColumn();
    }
    
    /**
     * Total des paiements en attente
     */
    public function getTotalEnAttente() {
        return $this->db->query("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = FALSE")->fetchColumn();
    }
    
    /**
     * Revenus du mois
     */
    public function getRevenusMois() {
        return $this->db->query("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = TRUE AND MONTH(date_paiement) = MONTH(CURRENT_DATE())")->fetchColumn();
    }
    
    /**
     * Revenus par période
     */
    public function getRevenusPeriode($dateDebut, $dateFin) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant), 0) FROM Paiements WHERE recu = TRUE AND date_paiement BETWEEN ? AND ?");
        $stmt->execute([$dateDebut, $dateFin]);
        return $stmt->fetchColumn();
    }
}
