<?php
/**
 * Punto de entrada principal
 * Redirige a login o dashboard según estado de sesión
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    // Si es admin, ir a dashboard admin
    if (isAdmin()) {
        header('Location: /admin/dashboard.php');
    } else {
        // Si es usuario regular, ir a dashboard usuario
        header('Location: /usuario/dashboard.php');
    }
    exit();
} else {
    // Si no está logueado, ir a login
    header('Location: /auth/login.php');
    exit();
}
?>
