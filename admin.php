<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Book.php';
require_once 'models/User.php';
require_once 'models/Reservation.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Récupérer les statistiques
$book = new Book($db);
$user = new User($db);
$reservation = new Reservation($db);

$total_books = $book->count();
$total_users = $user->count();
$reservation_stats = $reservation->getStats();
$overdue_reservations = $reservation->getOverdueReservations();

// Paramètres pour les listes
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$tab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'dashboard';

// Récupérer les données selon l'onglet
$books = [];
$users = [];
$reservations = [];

if ($tab === 'books') {
    $books = $book->getAll([], $page, $limit);
} elseif ($tab === 'users') {
    $users = $user->getAll($page, $limit);
} elseif ($tab === 'reservations') {
    $reservations = $reservation->getAll([], $page, $limit);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?php echo APP_NAME; ?></title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">
                            <i class="fas fa-cog me-1"></i>Administration
                        </a>
                    </li>
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
                <li class="breadcrumb-item active" aria-current="page">Administration</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-cog me-2"></i>Panneau d'administration</h1>
            <div class="btn-group" role="group">
                <a href="admin-books.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Ajouter un livre
                </a>
            </div>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>" 
                        id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab"
                        onclick="window.location.href='?tab=dashboard'">
                    <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'books' ? 'active' : ''; ?>" 
                        id="books-tab" data-bs-toggle="tab" data-bs-target="#books" type="button" role="tab"
                        onclick="window.location.href='?tab=books'">
                    <i class="fas fa-books me-2"></i>Livres
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'users' ? 'active' : ''; ?>" 
                        id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab"
                        onclick="window.location.href='?tab=users'">
                    <i class="fas fa-users me-2"></i>Utilisateurs
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'reservations' ? 'active' : ''; ?>" 
                        id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button" role="tab"
                        onclick="window.location.href='?tab=reservations'">
                    <i class="fas fa-calendar-check me-2"></i>Réservations
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Tableau de bord -->
            <div class="tab-pane fade <?php echo $tab === 'dashboard' ? 'show active' : ''; ?>" id="dashboard" role="tabpanel">
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <i class="fas fa-books fa-3x mb-3"></i>
                                <h3><?php echo $total_books; ?></h3>
                                <p>Livres en stock</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card dashboard-card" style="background: linear-gradient(135deg, #198754, #146c43);">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <h3><?php echo $total_users; ?></h3>
                                <p>Utilisateurs inscrits</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card dashboard-card" style="background: linear-gradient(135deg, #0dcaf0, #0aa2c0);">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                <h3><?php echo $reservation_stats['total']; ?></h3>
                                <p>Réservations totales</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card dashboard-card" style="background: linear-gradient(135deg, #dc3545, #b02a37);">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                <h3><?php echo count($overdue_reservations); ?></h3>
                                <p>Réservations en retard</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détail des réservations -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statut des réservations</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h4 class="text-warning"><?php echo $reservation_stats['en_attente'] ?? 0; ?></h4>
                                            <small class="text-muted">En attente</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h4 class="text-info"><?php echo $reservation_stats['confirmee'] ?? 0; ?></h4>
                                            <small class="text-muted">Confirmées</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h4 class="text-success"><?php echo $reservation_stats['en_cours'] ?? 0; ?></h4>
                                            <small class="text-muted">En cours</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3 mb-3">
                                        <div class="border rounded p-3">
                                            <h4 class="text-secondary"><?php echo $reservation_stats['terminee'] ?? 0; ?></h4>
                                            <small class="text-muted">Terminées</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Réservations en retard</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($overdue_reservations)): ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <p>Aucune réservation en retard</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach (array_slice($overdue_reservations, 0, 5) as $overdue): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($overdue['titre']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($overdue['prenom'] . ' ' . $overdue['nom']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-danger">
                                                    <?php 
                                                    $days_overdue = (new DateTime())->diff(new DateTime($overdue['date_fin']))->days;
                                                    echo $days_overdue . ' jour(s)';
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($overdue_reservations) > 5): ?>
                                        <div class="text-center mt-3">
                                            <a href="?tab=reservations&filter=overdue" class="btn btn-outline-danger btn-sm">
                                                Voir toutes (<?php echo count($overdue_reservations); ?>)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Livres -->
            <div class="tab-pane fade <?php echo $tab === 'books' ? 'show active' : ''; ?>" id="books" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Gestion des livres</h5>
                    <a href="admin-books.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Ajouter un livre
                    </a>
                </div>
                
                <?php if (empty($books)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun livre trouvé</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Auteur</th>
                                    <th>Genre</th>
                                    <th>Disponibles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book_item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book_item['titre']); ?></td>
                                        <td><?php echo htmlspecialchars($book_item['auteur']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($book_item['genre']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $book_item['exemplaires_disponibles'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $book_item['exemplaires_disponibles']; ?>/<?php echo $book_item['nombre_exemplaires']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin-books.php?id=<?php echo $book_item['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Utilisateurs -->
            <div class="tab-pane fade <?php echo $tab === 'users' ? 'show active' : ''; ?>" id="users" role="tabpanel">
                <h5>Gestion des utilisateurs</h5>
                
                <?php if (empty($users)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun utilisateur trouvé</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Inscription</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user_item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user_item['prenom'] . ' ' . $user_item['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user_item['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                <?php echo ucfirst($user_item['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user_item['date_inscription'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user_item['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $user_item['is_active'] ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user_item['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deactivateUser(<?php echo $user_item['id']; ?>, '<?php echo htmlspecialchars($user_item['prenom'] . ' ' . $user_item['nom']); ?>')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Réservations -->
            <div class="tab-pane fade <?php echo $tab === 'reservations' ? 'show active' : ''; ?>" id="reservations" role="tabpanel">
                <h5>Gestion des réservations</h5>
                
                <?php if (empty($reservations)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucune réservation trouvée</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Utilisateur</th>
                                    <th>Dates</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $res): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($res['titre']); ?></td>
                                        <td><?php echo htmlspecialchars($res['prenom'] . ' ' . $res['nom']); ?></td>
                                        <td>
                                            <small>
                                                <?php echo date('d/m/Y', strtotime($res['date_debut'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($res['date_fin'])); ?>
                                            </small>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="updateReservationStatus(<?php echo $res['id']; ?>, '<?php echo $res['statut']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
        function deactivateUser(userId, userName) {
            if (confirm('Êtes-vous sûr de vouloir désactiver l\'utilisateur "' + userName + '" ?')) {
                // Ici vous pouvez ajouter une requête AJAX pour désactiver l'utilisateur
                alert('Fonctionnalité à implémenter');
            }
        }

        function updateReservationStatus(reservationId, currentStatus) {
            // Ici vous pouvez ajouter une modal pour changer le statut
            alert('Fonctionnalité à implémenter - Réservation ID: ' + reservationId + ', Statut actuel: ' + currentStatus);
        }
    </script>
</body>
</html>
