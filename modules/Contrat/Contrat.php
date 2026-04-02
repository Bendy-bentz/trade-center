<?php
/**
 * Module Contrat - CRUD
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../../config/config.php';

class ContratModule {
    
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Récupérer tous les contrats
     */
    public function getAll() {
        return $this->db->query("SELECT c.*, r.reference as reservation_ref, r.date_debut, r.date_fin,
                                 cl.nom as client_nom, cl.prenom as client_prenom,
                                 v.marque, v.modele, v.immatriculation
                                 FROM Contrats c 
                                 JOIN Reservations r ON c.id_reservation = r.id_reservation
                                 JOIN Clients cl ON r.id_client = cl.id_client
                                 JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
                                 ORDER BY c.date_creation DESC")->fetchAll();
    }
    
    /**
     * Récupérer un contrat par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT c.*, r.reference as reservation_ref, r.date_debut, r.date_fin,
                                    r.id_client, r.id_vehicule,
                                    cl.nom as client_nom, cl.prenom as client_prenom, cl.telephone, cl.email,
                                    v.marque, v.modele, v.immatriculation, v.prix_jour
                                    FROM Contrats c 
                                    JOIN Reservations r ON c.id_reservation = r.id_reservation
                                    JOIN Clients cl ON r.id_client = cl.id_client
                                    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule
                                    WHERE c.id_contrat = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer par réservation
     */
    public function getByReservation($idReservation) {
        $stmt = $this->db->prepare("SELECT * FROM Contrats WHERE id_reservation = ?");
        $stmt->execute([$idReservation]);
        return $stmt->fetch();
    }
    
    /**
     * Créer un contrat
     */
    public function create($data) {
        $numeroContrat = generateReference('CTR');
        
        // Calculer le montant total
        $stmt = $this->db->prepare("SELECT r.date_debut, r.date_fin, v.prix_jour 
                                    FROM Reservations r 
                                    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
                                    WHERE r.id_reservation = ?");
        $stmt->execute([$data['id_reservation']]);
        $res = $stmt->fetch();
        
        $nbJours = max(1, (strtotime($res['date_fin']) - strtotime($res['date_debut'])) / 86400);
        $montantTotal = ($nbJours * $res['prix_jour']) + ($data['caution'] ?? 0);
        
        $stmt = $this->db->prepare("INSERT INTO Contrats (numero_contrat, id_reservation, conditions, caution, montant_total) 
                                    VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $numeroContrat,
            (int)$data['id_reservation'],
            sanitize($data['conditions'] ?? ''),
            (float)($data['caution'] ?? 0),
            $montantTotal
        ]);
        
        return ['id' => $this->db->lastInsertId(), 'numero' => $numeroContrat, 'montant_total' => $montantTotal];
    }
    
    /**
     * Modifier un contrat
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE Contrats SET conditions=?, caution=?, montant_total=? WHERE id_contrat=?");
        return $stmt->execute([
            sanitize($data['conditions'] ?? ''),
            (float)($data['caution'] ?? 0),
            (float)$data['montant_total'],
            $id
        ]);
    }
    
    /**
     * Signer un contrat
     */
    public function signer($id, $signature) {
        $stmt = $this->db->prepare("UPDATE Contrats SET date_signature = NOW(), signature_client = ? WHERE id_contrat = ?");
        return $stmt->execute([$signature, $id]);
    }
    
    /**
     * Supprimer un contrat
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM Contrats WHERE id_contrat = ?");
        return $stmt->execute([$id]);
    }
}
