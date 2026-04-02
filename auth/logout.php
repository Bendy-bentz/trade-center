<?php
require_once __DIR__ . '/../config/config.php';

// SÉCURITÉ : Forcer le navigateur à vider le cache de la page précédente
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// On détruit la session
session_start();
session_destroy();
session_unset();

// On redirige vers l'accueil
header("Location: " . BASE_URL . "/index.php?msg=disconnected");
exit();