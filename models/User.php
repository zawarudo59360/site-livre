<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $mot_de_passe;
    public $role;
    public $date_inscription;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouvel utilisateur
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nom=:nom, prenom=:prenom, email=:email, 
                      mot_de_passe=:mot_de_passe, role=:role";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Hasher le mot de passe
        $this->mot_de_passe = password_hash($this->mot_de_passe, PASSWORD_DEFAULT);

        // Lier les paramètres
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":mot_de_passe", $this->mot_de_passe);
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Vérifier si l'email existe
    public function emailExists() {
        $query = "SELECT id, nom, prenom, email, mot_de_passe, role, date_inscription, is_active
                  FROM " . $this->table_name . " 
                  WHERE email = :email 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->mot_de_passe = $row['mot_de_passe'];
            $this->role = $row['role'];
            $this->date_inscription = $row['date_inscription'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    // Vérifier le mot de passe
    public function verifyPassword($password) {
        return password_verify($password, $this->mot_de_passe);
    }

    // Obtenir un utilisateur par ID
    public function getById($id) {
        $query = "SELECT id, nom, prenom, email, role, date_inscription, is_active
                  FROM " . $this->table_name . " 
                  WHERE id = :id AND is_active = 1
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->date_inscription = $row['date_inscription'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    // Mettre à jour le profil utilisateur
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nom=:nom, prenom=:prenom, email=:email
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->prenom = htmlspecialchars(strip_tags($this->prenom));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Changer le mot de passe
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET mot_de_passe=:mot_de_passe
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt->bindParam(":mot_de_passe", $hashed_password);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Obtenir tous les utilisateurs (admin)
    public function getAll($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT id, nom, prenom, email, role, date_inscription, is_active
                  FROM " . $this->table_name . " 
                  ORDER BY date_inscription DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter le nombre total d'utilisateurs
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Désactiver un utilisateur
    public function deactivate() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Obtenir le nom complet
    public function getFullName() {
        return $this->prenom . ' ' . $this->nom;
    }

    // Vérifier si l'utilisateur est admin
    public function isAdmin() {
        return $this->role === 'admin';
    }
}
?>
