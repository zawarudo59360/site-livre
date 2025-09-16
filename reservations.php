<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Reservation.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$database = new Database();
$db = $database->getConnection();
$reservation = new Reservation($db);

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$statut = isset($_GET['statut']) ? sanitizeInput($_GET['statut']) : '';

// Construire les filtres
$filters = ['user_id' => $_SESSION['user_id']];
if (!empty($statut)) {
    $filters['statut'] = $statut;
}

// Récupérer les réservations
$reservations = $reservation->getAll($filters, $page, $limit);
$total_reservations = $reservation->count($filters);
$total_pages = ceil($total_reservations / $limit);

// Traitement de l'annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    
    if ($reservation->getById($reservation_id)) {
        // Vérifier que c'est bien la réservation de l'utilisateur connecté
        if ($reservation->user_id == $_SESSION['user_id']) {
            if ($reservation->cancel()) {
                // Libérer l'exemplaire du livre
                require_once 'models/Book.php';
                $book = new Book($db);
                if ($book->getById($reservation->book_id)) {
                    $book->release();
                }
                
                $success_message = 'Réservation annulée avec succès.';
                // Recharger la page pour mettre à jour la liste
                redirect('reservations.php');
            } else {
                $error_message = 'Erreur lors de l\'annulation de la réservation.';
            }
        } else {
            $error_message = 'Vous ne pouvez pas annuler cette réservation.';
        }
    } else {
        $error_message = 'Réservation non trouvée.';
    }
}

$error_message = isset($error_message) ? $error_message : '';
$success_message = isset($success_message) ? $success_message : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link active" href="reservations.php">
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
                <li class="breadcrumb-item active" aria-current="page">Mes réservations</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-calendar-check me-2"></i>Mes réservations</h1>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-plus me-2"></i>Nouvelle réservation
            </a>
        </div>

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
        <?php endif; ?>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="statut" class="form-label">Statut</label>
                        <select class="form-select" id="statut" name="statut">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmee" <?php echo $statut === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                            <option value="en_cours" <?php echo $statut === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="terminee" <?php echo $statut === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                            <option value="annulee" <?php echo $statut === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-filter me-1"></i>Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des réservations -->
        <?php if (empty($reservations)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Aucune réservation trouvée</h4>
                <p class="text-muted">Vous n'avez pas encore de réservations ou elles ne correspondent pas aux filtres sélectionnés.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-book me-2"></i>Découvrir des livres
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($reservations as $res): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($res['titre']); ?></h6>
                                <span class="badge status-<?php echo str_replace('_', '-', $res['statut']); ?>">
                                    <?php 
                                    $statut_labels = [
                                        'en_attente' => 'En attente',
                                        'confirmee' => 'Confirmée',
                                        'en_cours' => 'En cours',
                                        'terminee' => 'Terminée',
                                        'annulee' => 'Annulée'
                                    ];
                                    echo $statut_labels[$res['statut']] ?? $res['statut'];
                                    ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Auteur</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($res['auteur']); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Genre</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($res['genre']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Date de début</small>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($res['date_debut'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Date de fin</small>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($res['date_fin'])); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Date de réservation</small>
                                    <p class="mb-0">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y à H:i', strtotime($res['date_reservation'])); ?>
                                    </p>
                                </div>
                                
                                <?php if (!empty($res['commentaires'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Commentaires</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($res['commentaires']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($res['statut'] === 'en_cours' && strtotime($res['date_fin']) < time()): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>En retard !</strong> Cette réservation est en retard.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <a href="book-details.php?id=<?php echo $res['book_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Voir le livre
                                    </a>
                                    
                                    <?php if (in_array($res['statut'], ['en_attente', 'confirmee'])): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="cancelReservation(<?php echo $res['id']; ?>, '<?php echo htmlspecialchars($res['titre']); ?>')">
                                            <i class="fas fa-times me-1"></i>Annuler
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination des réservations">
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

    <!-- Modal de confirmation d'annulation -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer l'annulation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir annuler la réservation de <strong id="bookTitle"></strong> ?</p>
                    <p class="text-muted">Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="reservation_id" id="reservationId">
                        <button type="submit" name="cancel_reservation" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Confirmer l'annulation
                        </button>
                    </form>
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
        function cancelReservation(reservationId, bookTitle) {
            document.getElementById('reservationId').value = reservationId;
            document.getElementById('bookTitle').textContent = bookTitle;
            
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();
        }
    </script>
</body>
</html>
