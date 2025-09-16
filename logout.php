<?php
require_once 'config/config.php';

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
redirect('index.php');
?>
