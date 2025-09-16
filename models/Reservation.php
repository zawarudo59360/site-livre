<?php
require_once 'config/database.php';

class Reservation {
    private $conn;
    private $table_name = "reservations";

    public $id;
    public $user_id;
    public $book_id;
    public $date_reservation;
    public $date_debut;
    public $date_fin;
    public $statut;
    public $date_retour;
    public $commentaires;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer une nouvelle réservation
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, book_id=:book_id, 
                      date_debut=:date_debut, date_fin=:date_fin, 
                      commentaires=:commentaires";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->commentaires = htmlspecialchars(strip_tags($this->commentaires));

        // Lier les paramètres
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":date_debut", $this->date_debut);
        $stmt->bindParam(":date_fin", $this->date_fin);
        $stmt->bindParam(":commentaires", $this->commentaires);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Obtenir une réservation par ID
    public function getById($id) {
        $query = "SELECT r.*, u.nom, u.prenom, u.email, b.titre, b.auteur, b.genre, b.image_url
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books b ON r.book_id = b.id
                  WHERE r.id = :id AND r.is_active = 1
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->book_id = $row['book_id'];
            $this->date_reservation = $row['date_reservation'];
            $this->date_debut = $row['date_debut'];
            $this->date_fin = $row['date_fin'];
            $this->statut = $row['statut'];
            $this->date_retour = $row['date_retour'];
            $this->commentaires = $row['commentaires'];
            $this->is_active = $row['is_active'];
            
            // Ajouter les informations du livre et de l'utilisateur
            $this->user_name = $row['prenom'] . ' ' . $row['nom'];
            $this->user_email = $row['email'];
            $this->book_title = $row['titre'];
            $this->book_author = $row['auteur'];
            $this->book_genre = $row['genre'];
            $this->book_image = $row['image_url'];
            
            return true;
        }
        return false;
    }

    // Obtenir toutes les réservations avec filtres
    public function getAll($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["r.is_active = 1"];
        $params = [];

        // Filtres
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "r.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['book_id'])) {
            $where_conditions[] = "r.book_id = :book_id";
            $params[':book_id'] = $filters['book_id'];
        }

        if (!empty($filters['statut'])) {
            $where_conditions[] = "r.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT r.*, u.nom, u.prenom, u.email, b.titre, b.auteur, b.genre, b.image_url
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books b ON r.book_id = b.id
                  WHERE " . $where_clause . "
                  ORDER BY r.date_reservation DESC
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

    // Compter le nombre total de réservations
    public function count($filters = []) {
        $where_conditions = ["is_active = 1"];
        $params = [];

        // Appliquer les mêmes filtres
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['book_id'])) {
            $where_conditions[] = "book_id = :book_id";
            $params[':book_id'] = $filters['book_id'];
        }

        if (!empty($filters['statut'])) {
            $where_conditions[] = "statut = :statut";
            $params[':statut'] = $filters['statut'];
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

    // Mettre à jour le statut d'une réservation
    public function updateStatus($new_status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET statut = :statut";
        
        // Si le statut devient "terminee", ajouter la date de retour
        if ($new_status === 'terminee') {
            $query .= ", date_retour = NOW()";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":statut", $new_status);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Annuler une réservation
    public function cancel() {
        $query = "UPDATE " . $this->table_name . " 
                  SET statut = 'annulee', is_active = 0
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Vérifier si l'utilisateur a déjà une réservation active pour ce livre
    public function hasActiveReservation($user_id, $book_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND book_id = :book_id 
                  AND statut IN ('en_attente', 'confirmee', 'en_cours')
                  AND is_active = 1
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":book_id", $book_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Obtenir les réservations en retard
    public function getOverdueReservations() {
        $query = "SELECT r.*, u.nom, u.prenom, u.email, b.titre, b.auteur
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN books b ON r.book_id = b.id
                  WHERE r.statut = 'en_cours' 
                  AND r.date_fin < CURDATE()
                  AND r.is_active = 1
                  ORDER BY r.date_fin ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtenir les statistiques des réservations
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut = 'confirmee' THEN 1 ELSE 0 END) as confirmee,
                    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                    SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) as terminee,
                    SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as annulee,
                    SUM(CASE WHEN statut = 'en_cours' AND date_fin < CURDATE() THEN 1 ELSE 0 END) as en_retard
                  FROM " . $this->table_name . " 
                  WHERE is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Vérifier si la réservation est en cours
    public function isEnCours() {
        $now = date('Y-m-d');
        return $this->statut === 'en_cours' && 
               $this->date_debut <= $now && 
               $this->date_fin >= $now;
    }

    // Vérifier si la réservation est en retard
    public function isEnRetard() {
        $now = date('Y-m-d');
        return $this->statut === 'en_cours' && $this->date_fin < $now;
    }

    // Calculer le nombre de jours de retard
    public function getJoursRetard() {
        if (!$this->isEnRetard()) {
            return 0;
        }
        
        $date_fin = new DateTime($this->date_fin);
        $now = new DateTime();
        $diff = $now->diff($date_fin);
        
        return $diff->days;
    }
}
?>
