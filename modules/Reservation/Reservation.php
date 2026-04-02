<?php
/**
 * Module Réservation - CRUD
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../../config/config.php';

class ReservationModule {
    
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Récupérer toutes les réservations
     */
    public function getAll($filtre = null) {
        $sql = "SELECT r.*, c.prenom, c.nom as client_nom, c.telephone, v.marque, v.modele, v.immatriculation, v.prix_jour 
                FROM Reservations r 
                JOIN Clients c ON r.id_client = c.id_client 
                JOIN Vehicules v ON r.id_vehicule = v.id_vehicule";
        
        if ($filtre) {
            $sql .= " WHERE r.statut = ?";
        }
        
        $sql .= " ORDER BY r.date_reservation DESC";
        
        if ($filtre) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$filtre]);
            return $stmt->fetchAll();
        }
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Récupérer une réservation par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT r.*, c.prenom, c.nom as client_nom, c.telephone, c.email as client_email,
                                    v.marque, v.modele, v.immatriculation, v.prix_jour 
                                    FROM Reservations r 
                                    JOIN Clients c ON r.id_client = c.id_client 
                                    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
                                    WHERE r.id_reservation = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer par référence
     */
    public function getByReference($reference) {
        $stmt = $this->db->prepare("SELECT * FROM Reservations WHERE reference = ?");
        $stmt->execute([$reference]);
        return $stmt->fetch();
    }
    
    /**
     * Créer une réservation
     */
    public function create($data) {
        $reference = generateReference('RES');
        
        $stmt = $this->db->prepare("INSERT INTO Reservations (reference, id_client, id_vehicule, id_utilisateur, date_debut, date_fin, statut, notes) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reference,
            (int)$data['id_client'],
            (int)$data['id_vehicule'],
            $data['id_utilisateur'] ?? null,
            $data['date_debut'],
            $data['date_fin'],
            $data['statut'] ?? 'En attente',
            sanitize($data['notes'] ?? '')
        ]);
        
        $idReservation = $this->db->lastInsertId();
        
        // Mettre à jour l'état du véhicule si confirmée ou en cours
        if (in_array($data['statut'] ?? '', ['Confirmée', 'En cours'])) {
            $this->db->prepare("UPDATE Vehicules SET etat = 'Loué' WHERE id_vehicule = ?")
                     ->execute([(int)$data['id_vehicule']]);
        }
        
        return ['id' => $idReservation, 'reference' => $reference];
    }
    
    /**
     * Modifier une réservation
     */
    public function update($id, $data) {
        // Récupérer l'ancienne réservation
        $old = $this->getById($id);
        if (!$old) return false;
        
        $stmt = $this->db->prepare("UPDATE Reservations SET id_client=?, id_vehicule=?, date_debut=?, date_fin=?, statut=?, notes=? WHERE id_reservation=?");
        $result = $stmt->execute([
            (int)$data['id_client'],
            (int)$data['id_vehicule'],
            $data['date_debut'],
            $data['date_fin'],
            $data['statut'],
            sanitize($data['notes'] ?? ''),
            $id
        ]);
        
        // Gérer l'état du véhicule selon le statut
        $this->updateVehiculeEtat($data['id_vehicule'], $data['statut']);
        
        return $result;
    }
    
    /**
     * Annuler une réservation
     */
    public function cancel($id) {
        $reservation = $this->getById($id);
        if (!$reservation) return false;
        
        $stmt = $this->db->prepare("UPDATE Reservations SET statut = 'Annulée' WHERE id_reservation = ?");
        $result = $stmt->execute([$id]);
        
        // Libérer le véhicule
        $this->db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")
                 ->execute([$reservation['id_vehicule']]);
        
        return $result;
    }
    
    /**
     * Supprimer une réservation
     */
    public function delete($id) {
        $reservation = $this->getById($id);
        if (!$reservation) return false;
        
        // Libérer le véhicule si nécessaire
        if (in_array($reservation['statut'], ['Confirmée', 'En cours'])) {
            $this->db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")
                     ->execute([$reservation['id_vehicule']]);
        }
        
        $stmt = $this->db->prepare("DELETE FROM Reservations WHERE id_reservation = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Changer le statut
     */
    public function changeStatut($id, $statut) {
        $reservation = $this->getById($id);
        if (!$reservation) return false;
        
        $stmt = $this->db->prepare("UPDATE Reservations SET statut = ? WHERE id_reservation = ?");
        $result = $stmt->execute([$statut, $id]);
        
        $this->updateVehiculeEtat($reservation['id_vehicule'], $statut);
        
        return $result;
    }
    
    /**
     * Mettre à jour l'état du véhicule
     */
    private function updateVehiculeEtat($idVehicule, $statut) {
        if (in_array($statut, ['En cours', 'Confirmée'])) {
            $this->db->prepare("UPDATE Vehicules SET etat = 'Loué' WHERE id_vehicule = ?")
                     ->execute([$idVehicule]);
        } elseif (in_array($statut, ['Terminée', 'Annulée'])) {
            $this->db->prepare("UPDATE Vehicules SET etat = 'Disponible' WHERE id_vehicule = ?")
                     ->execute([$idVehicule]);
        }
    }
    
    /**
     * Calculer le montant d'une réservation
     */
    public function calculerMontant($id) {
        $reservation = $this->getById($id);
        if (!$reservation) return 0;
        
        $nbJours = max(1, (strtotime($reservation['date_fin']) - strtotime($reservation['date_debut'])) / 86400);
        return $nbJours * $reservation['prix_jour'];
    }
    
    /**
     * Récupérer les réservations d'un client
     */
    public function getByClient($idClient) {
        $stmt = $this->db->prepare("SELECT r.*, v.marque, v.modele, v.immatriculation, v.prix_jour 
                                    FROM Reservations r 
                                    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
                                    WHERE r.id_client = ? 
                                    ORDER BY r.date_reservation DESC");
        $stmt->execute([$idClient]);
        return $stmt->fetchAll();
    }
    
    /**
     * Vérifier disponibilité
     */
    public function verifierDisponibilite($idVehicule, $dateDebut, $dateFin, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM Reservations 
                WHERE id_vehicule = ? AND statut IN ('Confirmée', 'En cours')
                AND ((? BETWEEN date_debut AND date_fin) 
                     OR (? BETWEEN date_debut AND date_fin) 
                     OR (date_debut BETWEEN ? AND ?))";
        
        $params = [$idVehicule, $dateDebut, $dateFin, $dateDebut, $dateFin];
        
        if ($excludeId) {
            $sql .= " AND id_reservation != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }
}
