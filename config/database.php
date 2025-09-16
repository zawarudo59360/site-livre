<?php
class Database {
    // Option 1: Avec le nouvel utilisateur
    private $host = '192.168.100.4';
    private $db_name = 'site_livre';
    private $username = 'site_livre_user'; // Nouvel utilisateur
    private $password = 'sio2024';
    private $conn;

    // Option 2: En localhost si MySQL est sur la même machine
    // private $host = 'localhost';
    // private $username = 'root';
    // private $password = 'sio2024';

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
        } catch(PDOException $exception) {
            throw new Exception("Erreur de connexion: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
}
?>