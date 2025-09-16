<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Book.php';

$database = new Database();
$db = $database->getConnection();
$book = new Book($db);

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : DEFAULT_PAGE_SIZE;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$genre = isset($_GET['genre']) ? sanitizeInput($_GET['genre']) : '';
$auteur = isset($_GET['auteur']) ? sanitizeInput($_GET['auteur']) : '';
$disponible = isset($_GET['disponible']) ? (bool)$_GET['disponible'] : false;

// Construire les filtres
$filters = [];
if (!empty($search)) $filters['search'] = $search;
if (!empty($genre)) $filters['genre'] = $genre;
if (!empty($auteur)) $filters['auteur'] = $auteur;
if ($disponible) $filters['disponible'] = true;

// Récupérer les livres
$books = $book->getAll($filters, $page, $limit);
$total_books = $book->count($filters);
$total_pages = ceil($total_books / $limit);

// Récupérer les genres pour le filtre
$genres = $book->getGenres();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Bibliothèque de Mangas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book-open me-2"></i><?php echo APP_NAME; ?>
                <small class="d-block text-light opacity-75" style="font-size: 0.7rem;">Bibliothèque de Mangas</small>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="reservations.php">
                                <i class="fas fa-calendar-check me-1"></i>Mes réservations
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-cog me-1"></i>Administration
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Mon profil
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Connexion
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section bg-gradient-primary py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-3">Bienvenue sur <?php echo APP_NAME; ?></h1>
                    <p class="lead mb-4">Découvrez notre collection de mangas et réservez vos séries favorites en quelques clics.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <?php if (!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-light btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Créer un compte
                            </a>
                        <?php endif; ?>
                        <a href="#catalogue" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-search me-2"></i>Explorer le catalogue
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <div class="manga-illustration">
                            <i class="fas fa-book-open fa-8x mb-3 opacity-75"></i>
                            <div class="floating-elements">
                                <i class="fas fa-star fa-2x position-absolute" style="top: 20%; left: 10%; animation: float 3s ease-in-out infinite;"></i>
                                <i class="fas fa-heart fa-2x position-absolute" style="top: 30%; right: 15%; animation: float 3s ease-in-out infinite 1s;"></i>
                                <i class="fas fa-magic fa-2x position-absolute" style="bottom: 20%; left: 20%; animation: float 3s ease-in-out infinite 2s;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="container py-4">
        <div class="row text-center">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-book fa-2x text-primary mb-2"></i>
                    <h4 class="fw-bold"><?php echo $total_books; ?></h4>
                    <p class="text-muted mb-0">Mangas disponibles</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-layer-group fa-2x text-success mb-2"></i>
                    <h4 class="fw-bold"><?php echo count($genres); ?></h4>
                    <p class="text-muted mb-0">Genres différents</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <h4 class="fw-bold"><?php echo isLoggedIn() ? 'Connecté' : 'Invité'; ?></h4>
                    <p class="text-muted mb-0">Statut</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4 class="fw-bold">24/7</h4>
                    <p class="text-muted mb-0">Disponible</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="container my-4" id="catalogue">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Rechercher des Mangas</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Rechercher</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Titre, auteur, série..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="genre" class="form-label">Genre</label>
                        <select class="form-select" id="genre" name="genre">
                            <option value="">Tous les genres</option>
                            <?php foreach ($genres as $g): ?>
                                <option value="<?php echo htmlspecialchars($g); ?>" 
                                        <?php echo $genre === $g ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="auteur" class="form-label">Auteur/Mangaka</label>
                        <input type="text" class="form-control" id="auteur" name="auteur" 
                               placeholder="Nom de l'auteur" value="<?php echo htmlspecialchars($auteur); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Rechercher
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="disponible" name="disponible" 
                                   value="1" <?php echo $disponible ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="disponible">
                                <i class="fas fa-check-circle me-1"></i>Afficher seulement les mangas disponibles
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste des mangas -->
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-book-open me-2"></i>Collection de Mangas</h2>
            <span class="badge bg-primary fs-6"><?php echo $total_books; ?> manga(s) trouvé(s)</span>
        </div>

        <?php if (empty($books)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Aucun manga trouvé</h4>
                <p class="text-muted">Essayez de modifier vos critères de recherche ou explorez nos genres populaires.</p>
                <div class="mt-4">
                    <a href="?genre=Shonen" class="btn btn-outline-primary me-2">Shonen</a>
                    <a href="?genre=Seinen" class="btn btn-outline-primary me-2">Seinen</a>
                    <a href="?genre=Action" class="btn btn-outline-primary me-2">Action</a>
                    <a href="?genre=Fantasy" class="btn btn-outline-primary">Fantasy</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($books as $book_item): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 book-card">
                            <?php if (!empty($book_item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($book_item['image_url']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($book_item['titre']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-book fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($book_item['titre']); ?></h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($book_item['auteur']); ?>
                                </p>
                                <p class="card-text">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($book_item['genre']); ?></span>
                                    <?php if (!empty($book_item['tome'])): ?>
                                        <span class="badge bg-info ms-1">Tome <?php echo $book_item['tome']; ?></span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if (!empty($book_item['serie'])): ?>
                                    <p class="card-text small text-muted">
                                        <i class="fas fa-layer-group me-1"></i>Série: <?php echo htmlspecialchars($book_item['serie']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($book_item['description'])): ?>
                                    <p class="card-text small text-muted">
                                        <?php echo htmlspecialchars(substr($book_item['description'], 0, 100)) . '...'; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('Y', strtotime($book_item['date_publication'])); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-copy me-1"></i>
                                            <?php echo $book_item['exemplaires_disponibles']; ?>/<?php echo $book_item['nombre_exemplaires']; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="book-details.php?id=<?php echo $book_item['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Voir détails
                                        </a>
                                        
                                        <?php if (isLoggedIn() && $book_item['exemplaires_disponibles'] > 0): ?>
                                            <button class="btn btn-success btn-sm" 
                                                    onclick="reserveBook(<?php echo $book_item['id']; ?>)">
                                                <i class="fas fa-calendar-plus me-1"></i>Réserver
                                            </button>
                                        <?php elseif (!isLoggedIn()): ?>
                                            <a href="login.php" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-sign-in-alt me-1"></i>Connectez-vous pour réserver
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-times me-1"></i>Indisponible
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination des livres">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Votre bibliothèque de mangas en ligne pour découvrir et réserver vos séries favorites.</p>
                    <div class="mt-3">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-discord"></i></a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.</p>
                    <p class="text-muted small">Fait avec ❤️ pour les amateurs de mangas</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function reserveBook(bookId) {
            if (confirm('Êtes-vous sûr de vouloir réserver ce manga ?')) {
                // Rediriger vers la page de réservation
                window.location.href = 'reserve.php?book_id=' + bookId;
            }
        }

        // Animation pour les éléments flottants
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter l'animation CSS pour les éléments flottants
            const style = document.createElement('style');
            style.textContent = `
                @keyframes float {
                    0%, 100% { transform: translateY(0px); }
                    50% { transform: translateY(-20px); }
                }
                .stat-card {
                    padding: 1rem;
                    border-radius: 10px;
                    transition: transform 0.3s ease;
                }
                .stat-card:hover {
                    transform: translateY(-5px);
                }
                .manga-illustration {
                    position: relative;
                }
                .floating-elements {
                    position: relative;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>
