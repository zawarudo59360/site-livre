<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Book.php';
require_once 'models/Reservation.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$error_message = '';
$success_message = '';

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
$reservation = new Reservation($db);
$has_active_reservation = $reservation->hasActiveReservation($_SESSION['user_id'], $book_id);

if ($has_active_reservation) {
    $error_message = 'Vous avez déjà une réservation active pour ce livre.';
}

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    $date_debut = sanitizeInput($_POST['date_debut']);
    $date_fin = sanitizeInput($_POST['date_fin']);
    $commentaires = sanitizeInput($_POST['commentaires']);

    // Validation
    if (empty($date_debut) || empty($date_fin)) {
        $error_message = 'Veuillez sélectionner les dates de début et de fin.';
    } elseif (strtotime($date_debut) < strtotime('today')) {
        $error_message = 'La date de début ne peut pas être dans le passé.';
    } elseif (strtotime($date_fin) <= strtotime($date_debut)) {
        $error_message = 'La date de fin doit être postérieure à la date de début.';
    } elseif (!$book->isAvailable()) {
        $error_message = 'Ce livre n\'est plus disponible.';
    } elseif ($has_active_reservation) {
        $error_message = 'Vous avez déjà une réservation active pour ce livre.';
    } else {
        // Créer la réservation
        $reservation = new Reservation($db);
        $reservation->user_id = $_SESSION['user_id'];
        $reservation->book_id = $book_id;
        $reservation->date_debut = $date_debut;
        $reservation->date_fin = $date_fin;
        $reservation->commentaires = $commentaires;

        if ($reservation->create()) {
            // Réserver un exemplaire du livre
            $book->reserve();
            
            $success_message = 'Réservation créée avec succès ! Vous recevrez une confirmation par email.';
        } else {
            $error_message = 'Erreur lors de la création de la réservation.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver - <?php echo htmlspecialchars($book->titre); ?> - <?php echo APP_NAME; ?></title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="reservations.php">
                            <i class="fas fa-calendar-check me-1"></i>Mes réservations
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="fas fa-cog me-1"></i>Administration
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
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
                <li class="breadcrumb-item"><a href="book-details.php?id=<?php echo $book->id; ?>"><?php echo htmlspecialchars($book->titre); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Réservation</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Informations du livre -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Livre à réserver</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($book->image_url)): ?>
                            <img src="<?php echo htmlspecialchars($book->image_url); ?>" 
                                 class="img-fluid rounded mb-3" alt="<?php echo htmlspecialchars($book->titre); ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center rounded mb-3" style="height: 200px;">
                                <i class="fas fa-book fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h6><?php echo htmlspecialchars($book->titre); ?></h6>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($book->auteur); ?>
                        </p>
                        <p class="mb-2">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($book->genre); ?></span>
                        </p>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="text-muted mb-1">Disponibles</h6>
                                    <h5 class="text-success mb-0"><?php echo $book->exemplaires_disponibles; ?></h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted mb-1">Total</h6>
                                <h5 class="text-primary mb-0"><?php echo $book->nombre_exemplaires; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire de réservation -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Nouvelle réservation</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <div class="text-center mt-4">
                                <a href="reservations.php" class="btn btn-primary">
                                    <i class="fas fa-calendar-check me-2"></i>Voir mes réservations
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_debut" class="form-label">
                                            <i class="fas fa-calendar-alt me-2"></i>Date de début
                                        </label>
                                        <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                               required min="<?php echo date('Y-m-d'); ?>"
                                               value="<?php echo isset($_POST['date_debut']) ? htmlspecialchars($_POST['date_debut']) : date('Y-m-d'); ?>">
                                        <div class="form-text">La réservation ne peut pas commencer dans le passé</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="date_fin" class="form-label">
                                            <i class="fas fa-calendar-check me-2"></i>Date de fin
                                        </label>
                                        <input type="date" class="form-control" id="date_fin" name="date_fin" 
                                               required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                               value="<?php echo isset($_POST['date_fin']) ? htmlspecialchars($_POST['date_fin']) : date('Y-m-d', strtotime('+14 days')); ?>">
                                        <div class="form-text">Durée maximale recommandée : 14 jours</div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="commentaires" class="form-label">
                                        <i class="fas fa-comment me-2"></i>Commentaires (optionnel)
                                    </label>
                                    <textarea class="form-control" id="commentaires" name="commentaires" rows="3" 
                                              placeholder="Ajoutez des commentaires sur votre réservation..."><?php echo isset($_POST['commentaires']) ? htmlspecialchars($_POST['commentaires']) : ''; ?></textarea>
                                </div>

                                <!-- Informations importantes -->
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle me-2"></i>Informations importantes :</h6>
                                    <ul class="mb-0">
                                        <li>La réservation est gratuite et sans engagement</li>
                                        <li>Vous recevrez une confirmation par email</li>
                                        <li>Vous pouvez annuler votre réservation jusqu'à 24h avant le début</li>
                                        <li>En cas de retard, des frais peuvent s'appliquer</li>
                                    </ul>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="book-details.php?id=<?php echo $book->id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Retour
                                    </a>
                                    <button type="submit" name="reserve" class="btn btn-success btn-lg">
                                        <i class="fas fa-calendar-plus me-2"></i>Confirmer la réservation
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
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
    <script>
        // Validation côté client
        document.getElementById('date_debut').addEventListener('change', function() {
            const dateDebut = new Date(this.value);
            const dateFin = document.getElementById('date_fin');
            const minDateFin = new Date(dateDebut);
            minDateFin.setDate(minDateFin.getDate() + 1);
            
            dateFin.min = minDateFin.toISOString().split('T')[0];
            
            // Si la date de fin est antérieure à la nouvelle date de début, la mettre à jour
            if (new Date(dateFin.value) <= dateDebut) {
                dateFin.value = minDateFin.toISOString().split('T')[0];
            }
        });

        document.getElementById('date_fin').addEventListener('change', function() {
            const dateFin = new Date(this.value);
            const dateDebut = new Date(document.getElementById('date_debut').value);
            
            if (dateFin <= dateDebut) {
                alert('La date de fin doit être postérieure à la date de début.');
                this.value = '';
            }
        });
    </script>
</body>
</html>
