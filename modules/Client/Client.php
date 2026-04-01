<?php
/**
 * Module Client - CRUD
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../../config/config.php';

class ClientModule {
    
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Récupérer tous les clients
     */
    public function getAll() {
        return $this->db->query("SELECT c.*, COUNT(r.id_reservation) as nb_reservations 
                                 FROM Clients c 
                                 LEFT JOIN Reservations r ON c.id_client = r.id_client 
                                 GROUP BY c.id_client 
                                 ORDER BY c.id_client DESC")->fetchAll();
    }
    
    /**
     * Récupérer un client par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM Clients WHERE id_client = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer un client par ID utilisateur
     */
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM Clients WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Ajouter un client
     */
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO Clients (nom, prenom, email, telephone, telephone2, adresse, ville, code_postal, pays, numero_permis, id_utilisateur) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            sanitize($data['nom']),
            sanitize($data['prenom']),
            sanitize($data['email'] ?? ''),
            sanitize($data['telephone']),
            sanitize($data['telephone2'] ?? ''),
            sanitize($data['adresse'] ?? ''),
            sanitize($data['ville'] ?? ''),
            sanitize($data['code_postal'] ?? ''),
            sanitize($data['pays'] ?? 'Maroc'),
            sanitize($data['numero_permis'] ?? ''),
            $data['id_utilisateur'] ?? null
        ]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Modifier un client
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE Clients SET nom=?, prenom=?, email=?, telephone=?, telephone2=?, adresse=?, ville=?, code_postal=?, pays=?, numero_permis=? WHERE id_client=?");
        return $stmt->execute([
            sanitize($data['nom']),
            sanitize($data['prenom']),
            sanitize($data['email'] ?? ''),
            sanitize($data['telephone']),
            sanitize($data['telephone2'] ?? ''),
            sanitize($data['adresse'] ?? ''),
            sanitize($data['ville'] ?? ''),
            sanitize($data['code_postal'] ?? ''),
            sanitize($data['pays'] ?? 'Maroc'),
            sanitize($data['numero_permis'] ?? ''),
            $id
        ]);
    }
    
    /**
     * Supprimer un client
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM Clients WHERE id_client = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Récupérer les réservations d'un client
     */
    public function getReservations($idClient) {
        $stmt = $this->db->prepare("SELECT r.*, v.marque, v.modele, v.immatriculation, v.prix_jour 
                                    FROM Reservations r 
                                    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
                                    WHERE r.id_client = ? 
                                    ORDER BY r.date_reservation DESC");
        $stmt->execute([$idClient]);
        return $stmt->fetchAll();
    }
    
    /**
     * Statistiques d'un client
     */
    public function getStats($idClient) {
        $stats = [];
        
        // Nombre total de réservations
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Reservations WHERE id_client = ?");
        $stmt->execute([$idClient]);
        $stats['total_reservations'] = $stmt->fetchColumn();
        
        // Réservations en cours
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Reservations WHERE id_client = ? AND statut = 'En cours'");
        $stmt->execute([$idClient]);
        $stats['en_cours'] = $stmt->fetchColumn();
        
        // Total dépensé
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(p.montant), 0) FROM Paiements p 
                                    JOIN Contrats c ON p.id_contrat = c.id_contrat 
                                    JOIN Reservations r ON c.id_reservation = r.id_reservation 
                                    WHERE r.id_client = ? AND p.recu = TRUE");
        $stmt->execute([$idClient]);
        $stats['total_depense'] = $stmt->fetchColumn();
        
        return $stats;
    }
}