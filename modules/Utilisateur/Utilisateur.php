<?php
/**
 * Module Utilisateur - CRUD
 * TradecenterEntreprise
 */

require_once __DIR__ . '/../../config/config.php';

class UtilisateurModule {
    
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Récupérer tous les utilisateurs
     */
    public function getAll($filtreRole = null) {
        $sql = "SELECT id_utilisateur, nom, email, role, date_creation, derniere_connexion FROM Utilisateurs";
        
        if ($filtreRole) {
            $sql .= " WHERE role = ?";
            $stmt = $this->db->prepare($sql . " ORDER BY id_utilisateur DESC");
            $stmt->execute([$filtreRole]);
            return $stmt->fetchAll();
        }
        
        return $this->db->query($sql . " ORDER BY id_utilisateur DESC")->fetchAll();
    }
    
    /**
     * Récupérer un utilisateur par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM Utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer par email
     */
    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM Utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Créer un utilisateur
     */
    public function create($data) {
        // Vérifier si l'email existe déjà
        if ($this->getByEmail($data['email'])) {
            return ['error' => 'Cet email est déjà utilisé'];
        }
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("INSERT INTO Utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            sanitize($data['nom']),
            sanitize($data['email']),
            $hashedPassword,
            $data['role'] ?? 'Client'
        ]);
        
        return ['id' => $this->db->lastInsertId()];
    }
    
    /**
     * Modifier un utilisateur
     */
    public function update($id, $data) {
        $user = $this->getById($id);
        if (!$user) return false;
        
        // Vérifier si le nouvel email n'est pas utilisé par un autre
        if ($data['email'] != $user['email']) {
            $existing = $this->getByEmail($data['email']);
            if ($existing) {
                return ['error' => 'Cet email est déjà utilisé'];
            }
        }
        
        if (!empty($data['password'])) {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE Utilisateurs SET nom=?, email=?, mot_de_passe=?, role=? WHERE id_utilisateur=?");
            $result = $stmt->execute([
                sanitize($data['nom']),
                sanitize($data['email']),
                $hashedPassword,
                $data['role'],
                $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE Utilisateurs SET nom=?, email=?, role=? WHERE id_utilisateur=?");
            $result = $stmt->execute([
                sanitize($data['nom']),
                sanitize($data['email']),
                $data['role'],
                $id
            ]);
        }
        
        return $result;
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete($id) {
        // Ne pas supprimer les admins
        $user = $this->getById($id);
        if (!$user || $user['role'] == 'Admin') {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Authentification
     */
    public function login($email, $password) {
        $user = $this->getByEmail($email);
        
        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            return ['error' => 'Email ou mot de passe incorrect'];
        }
        
        // Mettre à jour la dernière connexion
        $this->db->prepare("UPDATE Utilisateurs SET derniere_connexion = NOW() WHERE id_utilisateur = ?")
                 ->execute([$user['id_utilisateur']]);
        
        // Créer la session
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        session_destroy();
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword($id, $oldPassword, $newPassword) {
        $user = $this->getById($id);
        
        if (!password_verify($oldPassword, $user['mot_de_passe'])) {
            return ['error' => 'Mot de passe actuel incorrect'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE Utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }
    
    /**
     * Récupérer les admins
     */
    public function getAdmins() {
        return $this->getAll('Admin');
    }
    
    /**
     * Récupérer les agents
     */
    public function getAgents() {
        return $this->getAll('Agent');
    }
    
    /**
     * Récupérer les clients (utilisateurs avec rôle Client)
     */
    public function getClients() {
        return $this->getAll('Client');
    }
    
    /**
     * Statistiques
     */
    public function getStats() {
        $stats = [];
        
        $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM Utilisateurs GROUP BY role");
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row['role']] = $row['count'];
        }
        
        return $stats;
    }
}

?>