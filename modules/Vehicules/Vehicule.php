<?php
class VehiculeModule {
    private $db;
    public function __construct() { $this->db = getDB(); }
    
    public function getAll($filtre = null) {
        $sql = "SELECT v.*, c.nom_categorie FROM Vehicules v LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie";
        if ($filtre === 'disponibles') $sql .= " WHERE v.etat = 'Disponible'";
        elseif ($filtre === 'loues') $sql .= " WHERE v.etat = 'Loué'";
        elseif ($filtre === 'maintenance') $sql .= " WHERE v.etat = 'En maintenance'";
        elseif ($filtre === 'vedettes') $sql .= " WHERE v.est_vedette = TRUE";
        return $this->db->query($sql . " ORDER BY v.id_vehicule DESC")->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT v.*, c.nom_categorie FROM Vehicules v LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie WHERE v.id_vehicule = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO Vehicules (marque, modele, immatriculation, annee, couleur, carburant, transmission, nb_places, prix_jour, id_categorie, etat, est_vedette) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([sanitize($data['marque']), sanitize($data['modele']), sanitize($data['immatriculation']), $data['annee']??null, $data['couleur']??'', $data['carburant'], $data['transmission'], $data['nb_places'], $data['prix_jour'], $data['id_categorie'], $data['etat'], isset($data['est_vedette'])?1:0]);
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE Vehicules SET marque=?, modele=?, immatriculation=?, annee=?, couleur=?, carburant=?, transmission=?, nb_places=?, prix_jour=?, id_categorie=?, etat=?, est_vedette=? WHERE id_vehicule=?");
        return $stmt->execute([sanitize($data['marque']), sanitize($data['modele']), sanitize($data['immatriculation']), $data['annee']??null, $data['couleur']??'', $data['carburant'], $data['transmission'], $data['nb_places'], $data['prix_jour'], $data['id_categorie'], $data['etat'], isset($data['est_vedette'])?1:0, $id]);
    }
    
    public function delete($id) {
        return $this->db->prepare("DELETE FROM Vehicules WHERE id_vehicule = ?")->execute([$id]);
    }
    
    public function getCategories() {
        return $this->db->query("SELECT * FROM Categories_Vehicules ORDER BY nom_categorie")->fetchAll();
    }
}
?>