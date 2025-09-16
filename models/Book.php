<?php
require_once 'config/database.php';

class Book {
    private $conn;
    private $table_name = "books";

    public $id;
    public $titre;
    public $auteur;
    public $isbn;
    public $date_publication;
    public $genre;
    public $description;
    public $nombre_exemplaires;
    public $exemplaires_disponibles;
    public $image_url;
    public $langue;
    public $pages;
    public $editeur;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouveau livre
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET titre=:titre, auteur=:auteur, isbn=:isbn, 
                      date_publication=:date_publication, genre=:genre, 
                      description=:description, nombre_exemplaires=:nombre_exemplaires,
                      exemplaires_disponibles=:exemplaires_disponibles, 
                      image_url=:image_url, langue=:langue, pages=:pages, editeur=:editeur";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->auteur = htmlspecialchars(strip_tags($this->auteur));
        $this->isbn = htmlspecialchars(strip_tags($this->isbn));
        $this->genre = htmlspecialchars(strip_tags($this->genre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->langue = htmlspecialchars(strip_tags($this->langue));
        $this->editeur = htmlspecialchars(strip_tags($this->editeur));

        // Lier les paramètres
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":auteur", $this->auteur);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":date_publication", $this->date_publication);
        $stmt->bindParam(":genre", $this->genre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":nombre_exemplaires", $this->nombre_exemplaires);
        $stmt->bindParam(":exemplaires_disponibles", $this->exemplaires_disponibles);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":langue", $this->langue);
        $stmt->bindParam(":pages", $this->pages);
        $stmt->bindParam(":editeur", $this->editeur);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Obtenir un livre par ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND is_active = 1
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->titre = $row['titre'];
            $this->auteur = $row['auteur'];
            $this->isbn = $row['isbn'];
            $this->date_publication = $row['date_publication'];
            $this->genre = $row['genre'];
            $this->description = $row['description'];
            $this->nombre_exemplaires = $row['nombre_exemplaires'];
            $this->exemplaires_disponibles = $row['exemplaires_disponibles'];
            $this->image_url = $row['image_url'];
            $this->langue = $row['langue'];
            $this->pages = $row['pages'];
            $this->editeur = $row['editeur'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    // Obtenir tous les livres avec filtres
    public function getAll($filters = [], $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["is_active = 1"];
        $params = [];

        // Filtres
        if (!empty($filters['genre'])) {
            $where_conditions[] = "genre = :genre";
            $params[':genre'] = $filters['genre'];
        }

        if (!empty($filters['auteur'])) {
            $where_conditions[] = "auteur LIKE :auteur";
            $params[':auteur'] = '%' . $filters['auteur'] . '%';
        }

        if (!empty($filters['search'])) {
            $where_conditions[] = "(titre LIKE :search OR auteur LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['disponible']) && $filters['disponible']) {
            $where_conditions[] = "exemplaires_disponibles > 0";
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE " . $where_clause . "
                  ORDER BY created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        // Lier les paramètres de filtres
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter le nombre total de livres
    public function count($filters = []) {
        $where_conditions = ["is_active = 1"];
        $params = [];

        // Appliquer les mêmes filtres
        if (!empty($filters['genre'])) {
            $where_conditions[] = "genre = :genre";
            $params[':genre'] = $filters['genre'];
        }

        if (!empty($filters['auteur'])) {
            $where_conditions[] = "auteur LIKE :auteur";
            $params[':auteur'] = '%' . $filters['auteur'] . '%';
        }

        if (!empty($filters['search'])) {
            $where_conditions[] = "(titre LIKE :search OR auteur LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['disponible']) && $filters['disponible']) {
            $where_conditions[] = "exemplaires_disponibles > 0";
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE " . $where_clause;

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtenir tous les genres
    public function getGenres() {
        $query = "SELECT DISTINCT genre FROM " . $this->table_name . " 
                  WHERE is_active = 1 
                  ORDER BY genre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Mettre à jour un livre
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET titre=:titre, auteur=:auteur, isbn=:isbn, 
                      date_publication=:date_publication, genre=:genre, 
                      description=:description, nombre_exemplaires=:nombre_exemplaires,
                      exemplaires_disponibles=:exemplaires_disponibles, 
                      image_url=:image_url, langue=:langue, pages=:pages, editeur=:editeur
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->auteur = htmlspecialchars(strip_tags($this->auteur));
        $this->isbn = htmlspecialchars(strip_tags($this->isbn));
        $this->genre = htmlspecialchars(strip_tags($this->genre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->langue = htmlspecialchars(strip_tags($this->langue));
        $this->editeur = htmlspecialchars(strip_tags($this->editeur));

        // Lier les paramètres
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":auteur", $this->auteur);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":date_publication", $this->date_publication);
        $stmt->bindParam(":genre", $this->genre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":nombre_exemplaires", $this->nombre_exemplaires);
        $stmt->bindParam(":exemplaires_disponibles", $this->exemplaires_disponibles);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":langue", $this->langue);
        $stmt->bindParam(":pages", $this->pages);
        $stmt->bindParam(":editeur", $this->editeur);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Supprimer un livre (marquer comme inactif)
    public function delete() {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Vérifier si le livre est disponible
    public function isAvailable() {
        return $this->exemplaires_disponibles > 0;
    }

    // Réserver un exemplaire
    public function reserve() {
        if ($this->exemplaires_disponibles > 0) {
            $query = "UPDATE " . $this->table_name . " 
                      SET exemplaires_disponibles = exemplaires_disponibles - 1
                      WHERE id=:id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            
            if ($stmt->execute()) {
                $this->exemplaires_disponibles--;
                return true;
            }
        }
        return false;
    }

    // Libérer un exemplaire
    public function release() {
        if ($this->exemplaires_disponibles < $this->nombre_exemplaires) {
            $query = "UPDATE " . $this->table_name . " 
                      SET exemplaires_disponibles = exemplaires_disponibles + 1
                      WHERE id=:id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            
            if ($stmt->execute()) {
                $this->exemplaires_disponibles++;
                return true;
            }
        }
        return false;
    }

    // Vérifier si l'ISBN existe déjà
    public function isbnExists($isbn, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE isbn = :isbn";
        
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $query .= " LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":isbn", $isbn);
        
        if ($exclude_id) {
            $stmt->bindParam(":exclude_id", $exclude_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
