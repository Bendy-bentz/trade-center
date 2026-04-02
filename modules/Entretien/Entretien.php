<?php
/**
 * Module Entretien - CRUD
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../../config/config.php';

class EntretienModule {
    
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Récupérer tous les entretiens
     */
    public function getAll() {
        return $this->db->query("SELECT e.*, v.marque, v.modele, v.immatriculation 
                                 FROM Entretien_Maintenance e 
                                 JOIN Vehicules v ON e.id_vehicule = v.id_vehicule 
                                 ORDER BY e.date_entretien DESC")->fetchAll();
    }
    
    /**
     * Récupérer par véhicule
     */
    public function getByVehicule($idVehicule) {
        $stmt = $this->db->prepare("SELECT * FROM Entretien_Maintenance WHERE id_vehicule = ? ORDER BY date_entretien DESC");
        $stmt->execute([$idVehicule]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer un entretien par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT e.*, v.marque, v.modele, v.immatriculation 
                                    FROM Entretien_Maintenance e 
                                    JOIN Vehicules v ON e.id_vehicule = v.id_vehicule 
                                    WHERE e.id_entretien = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Créer un entretien
     */
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO Entretien_Maintenance (id_vehicule, date_entretien, type, description, cout, kilometrage, garage, prochaine_revision) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$data['id_vehicule'],
            $data['date_entretien'],
            sanitize($data['type']),
            sanitize($data['description'] ?? ''),
            (float)$data['cout'],
            !empty($data['kilometrage']) ? (int)$data['kilometrage'] : null,
            sanitize($data['garage'] ?? ''),
            $data['prochaine_revision'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Modifier un entretien
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE Entretien_Maintenance SET id_vehicule=?, date_entretien=?, type=?, description=?, cout=?, kilometrage=?, garage=?, prochaine_revision=? WHERE id_entretien=?");
        return $stmt->execute([
            (int)$data['id_vehicule'],
            $data['date_entretien'],
            sanitize($data['type']),
            sanitize($data['description'] ?? ''),
            (float)$data['cout'],
            !empty($data['kilometrage']) ? (int)$data['kilometrage'] : null,
            sanitize($data['garage'] ?? ''),
            $data['prochaine_revision'] ?? null,
            $id
        ]);
    }
    
    /**
     * Supprimer un entretien
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM Entretien_Maintenance WHERE id_entretien = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Coût total des entretiens
     */
    public function getTotalCout($idVehicule = null) {
        if ($idVehicule) {
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(cout), 0) FROM Entretien_Maintenance WHERE id_vehicule = ?");
            $stmt->execute([$idVehicule]);
        } else {
            $stmt = $this->db->query("SELECT COALESCE(SUM(cout), 0) FROM Entretien_Maintenance");
        }
        return $stmt->fetchColumn();
    }
    
    /**
     * Entretiens à venir (prochaine_revision proche)
     */
    public function getAVenir($jours = 30) {
        $stmt = $this->db->prepare("SELECT e.*, v.marque, v.modele, v.immatriculation 
                                    FROM Entretien_Maintenance e 
                                    JOIN Vehicules v ON e.id_vehicule = v.id_vehicule 
                                    WHERE e.prochaine_revision BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                                    ORDER BY e.prochaine_revision");
        $stmt->execute([$jours]);
        return $stmt->fetchAll();
    }
}
