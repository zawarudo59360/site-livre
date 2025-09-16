<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Book.php';
require_once 'models/Reservation.php';

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$book_id) {
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();
$book = new Book($db);

if (!$book->getById($book_id)) {
    redirect('index.php');
}

// Vérifier si l'utilisateur a déjà une réservation active pour ce livre
$has_active_reservation = false;
if (isLoggedIn()) {
    $reservation = new Reservation($db);
    $has_active_reservation = $reservation->hasActiveReservation($_SESSION['user_id'], $book_id);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book->titre); ?> - <?php echo APP_NAME; ?></title>
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
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
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

    <!-- Contenu principal -->
    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($book->titre); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Image du livre -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <?php if (!empty($book->image_url)): ?>
                        <img src="<?php echo htmlspecialchars($book->image_url); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($book->titre); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                            <i class="fas fa-book fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($book->genre); ?></span>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="text-muted mb-1">Disponibles</h6>
                                    <h4 class="text-success mb-0"><?php echo $book->exemplaires_disponibles; ?></h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted mb-1">Total</h6>
                                <h4 class="text-primary mb-0"><?php echo $book->nombre_exemplaires; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails du livre -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title mb-0"><?php echo htmlspecialchars($book->titre); ?></h1>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-user text-primary me-2"></i>Auteur</h5>
                                <p class="fs-5"><?php echo htmlspecialchars($book->auteur); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-calendar text-primary me-2"></i>Date de publication</h5>
                                <p class="fs-5"><?php echo date('d/m/Y', strtotime($book->date_publication)); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($book->description)): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-align-left text-primary me-2"></i>Description</h5>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($book->description)); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <?php if (!empty($book->isbn)): ?>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-barcode text-primary me-2"></i>ISBN</h5>
                                    <p><?php echo htmlspecialchars($book->isbn); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($book->langue)): ?>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-language text-primary me-2"></i>Langue</h5>
                                    <p><?php echo htmlspecialchars($book->langue); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($book->pages)): ?>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-file-alt text-primary me-2"></i>Pages</h5>
                                    <p><?php echo $book->pages; ?> pages</p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($book->editeur)): ?>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-building text-primary me-2"></i>Éditeur</h5>
                                    <p><?php echo htmlspecialchars($book->editeur); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <?php if (isLoggedIn()): ?>
                                <?php if ($book->isAvailable() && !$has_active_reservation): ?>
                                    <a href="reserve.php?book_id=<?php echo $book->id; ?>" 
                                       class="btn btn-success btn-lg">
                                        <i class="fas fa-calendar-plus me-2"></i>Réserver ce livre
                                    </a>
                                <?php elseif ($has_active_reservation): ?>
                                    <button class="btn btn-warning btn-lg" disabled>
                                        <i class="fas fa-exclamation-triangle me-2"></i>Vous avez déjà réservé ce livre
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg" disabled>
                                        <i class="fas fa-times me-2"></i>Livre indisponible
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Connectez-vous pour réserver
                                </a>
                            <?php endif; ?>
                            
                            <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations supplémentaires -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations de réservation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <h6>Durée de prêt</h6>
                                    <p class="text-muted mb-0">14 jours maximum</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                    <h6>Réservation</h6>
                                    <p class="text-muted mb-0">Gratuite et sans engagement</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <i class="fas fa-undo fa-2x text-info mb-2"></i>
                                    <h6>Prolongation</h6>
                                    <p class="text-muted mb-0">Possible si pas de demande</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Votre bibliothèque en ligne pour découvrir et réserver des livres.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
