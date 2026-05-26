<?php
/**
 * Punto de entrada principal
 * Muestra login o dashboard según estado de sesión
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    // Si es admin, mostrar dashboard admin
    if (isAdmin()) {
        require_once __DIR__ . '/admin/dashboard.php';
    } else {
        // Si es usuario regular, mostrar dashboard usuario
        require_once __DIR__ . '/usuario/dashboard.php';
    }
} else {
    // Si no está logueado, mostrar login
    require_once __DIR__ . '/auth/login.php';
}
?>
