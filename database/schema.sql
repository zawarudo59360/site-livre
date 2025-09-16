-- Base de données pour le site de gestion de bibliothèque manga
CREATE DATABASE IF NOT EXISTS site_livre CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE site_livre;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('utilisateur', 'admin') DEFAULT 'utilisateur',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Table des mangas
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    auteur VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    date_publication DATE NOT NULL,
    genre ENUM(
        'Shonen', 'Shojo', 'Seinen', 'Josei', 'Kodomomuke', 
        'Action', 'Aventure', 'Comédie', 'Drame', 'Fantasy', 
        'Horreur', 'Mystère', 'Romance', 'Science-fiction', 'Sport',
        'Super-héros', 'Thriller', 'Yaoi', 'Yuri', 'Autre'
    ) NOT NULL,
    description TEXT,
    nombre_exemplaires INT NOT NULL DEFAULT 1,
    exemplaires_disponibles INT NOT NULL DEFAULT 1,
    image_url VARCHAR(500),
    langue VARCHAR(50) DEFAULT 'Français',
    pages INT,
    editeur VARCHAR(255),
    tome INT DEFAULT 1,
    serie VARCHAR(255),
    statut_serie ENUM('En cours', 'Terminé', 'En pause', 'Annulé') DEFAULT 'En cours',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_titre (titre),
    INDEX idx_auteur (auteur),
    INDEX idx_genre (genre),
    INDEX idx_isbn (isbn),
    INDEX idx_disponible (exemplaires_disponibles),
    INDEX idx_serie (serie),
    INDEX idx_tome (tome),
    FULLTEXT idx_search (titre, auteur, description, serie)
);

-- Table des réservations
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'en_cours', 'terminee', 'annulee') DEFAULT 'en_attente',
    date_retour TIMESTAMP NULL,
    commentaires TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_book (book_id),
    INDEX idx_statut (statut),
    INDEX idx_dates (date_debut, date_fin),
    CONSTRAINT chk_date_fin CHECK (date_fin > date_debut)
);

-- Table des sessions (optionnel, pour une meilleure gestion des sessions)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Insertion d'un utilisateur administrateur par défaut
INSERT INTO users (nom, prenom, email, mot_de_passe, role) VALUES 
('Admin', 'Système', 'admin@site-livre.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insertion de mangas populaires
INSERT INTO books (titre, auteur, isbn, date_publication, genre, description, nombre_exemplaires, exemplaires_disponibles, pages, editeur, tome, serie, statut_serie) VALUES 
('One Piece', 'Eiichiro Oda', '9784088725097', '1997-07-22', 'Shonen', 'Luffy rêve de devenir le Roi des Pirates en trouvant le trésor légendaire One Piece.', 5, 5, 192, 'Glénat', 1, 'One Piece', 'En cours'),
('Naruto', 'Masashi Kishimoto', '9782723442275', '1999-09-21', 'Shonen', 'Naruto Uzumaki, un jeune ninja qui rêve de devenir Hokage, le chef de son village.', 4, 4, 192, 'Kana', 1, 'Naruto', 'Terminé'),
('Dragon Ball', 'Akira Toriyama', '9782723442275', '1984-12-03', 'Shonen', 'Les aventures de Son Goku, un jeune garçon doté d\'une force surhumaine.', 3, 3, 192, 'Glénat', 1, 'Dragon Ball', 'Terminé'),
('Attack on Titan', 'Hajime Isayama', '9782355920000', '2009-09-09', 'Seinen', 'L\'humanité vit dans une cité entourée d\'immenses murs pour se protéger des Titans.', 3, 3, 200, 'Pika', 1, 'Attack on Titan', 'Terminé'),
('Demon Slayer', 'Koyoharu Gotouge', '9784088810014', '2016-02-15', 'Shonen', 'Tanjiro Kamado devient un chasseur de démons pour sauver sa sœur transformée en démon.', 4, 4, 192, 'Panini', 1, 'Demon Slayer', 'Terminé'),
('My Hero Academia', 'Kohei Horikoshi', '9784088802637', '2014-07-07', 'Shonen', 'Dans un monde où 80% de la population possède des super-pouvoirs, Izuku Midoriya rêve de devenir un héros.', 3, 3, 192, 'Ki-oon', 1, 'My Hero Academia', 'En cours'),
('Death Note', 'Tsugumi Ohba', '9784088736212', '2003-12-01', 'Shonen', 'Light Yagami trouve un carnet qui permet de tuer quiconque en y inscrivant son nom.', 2, 2, 200, 'Kana', 1, 'Death Note', 'Terminé'),
('Tokyo Ghoul', 'Sui Ishida', '9784088795455', '2011-09-08', 'Seinen', 'Ken Kaneki devient un hybride humain-ghoul après une rencontre fatale.', 3, 3, 200, 'Glénat', 1, 'Tokyo Ghoul', 'Terminé'),
('One Punch Man', 'ONE', '9784088802637', '2012-06-14', 'Seinen', 'Saitama, un héros si puissant qu\'il peut vaincre n\'importe quel ennemi d\'un seul coup de poing.', 2, 2, 200, 'Kurokawa', 1, 'One Punch Man', 'En cours'),
('Fullmetal Alchemist', 'Hiromu Arakawa', '9784088736212', '2001-08-12', 'Shonen', 'Les frères Elric utilisent l\'alchimie pour tenter de ressusciter leur mère décédée.', 4, 4, 192, 'Kurokawa', 1, 'Fullmetal Alchemist', 'Terminé'),
('Bleach', 'Tite Kubo', '9784088736212', '2001-08-07', 'Shonen', 'Ichigo Kurosaki devient un Shinigami pour protéger les vivants des Hollows.', 3, 3, 192, 'Glénat', 1, 'Bleach', 'Terminé'),
('Hunter x Hunter', 'Yoshihiro Togashi', '9784088736212', '1998-03-16', 'Shonen', 'Gon Freecss part à la recherche de son père, un légendaire Hunter.', 2, 2, 192, 'Kana', 1, 'Hunter x Hunter', 'En pause'),
('Your Name', 'Makoto Shinkai', '9784088736212', '2016-06-18', 'Romance', 'Deux adolescents échangent leurs corps et tombent amoureux sans jamais s\'être rencontrés.', 2, 2, 200, 'Pika', 1, 'Your Name', 'Terminé'),
('A Silent Voice', 'Yoshitoki Oima', '9784088736212', '2013-08-07', 'Drame', 'Un ancien harceleur tente de se racheter en aidant sa victime sourde.', 2, 2, 192, 'Ki-oon', 1, 'A Silent Voice', 'Terminé'),
('Spy x Family', 'Tatsuya Endo', '9784088736212', '2019-03-25', 'Comédie', 'Un espion doit créer une famille factice pour accomplir sa mission.', 3, 3, 192, 'Kurokawa', 1, 'Spy x Family', 'En cours');
