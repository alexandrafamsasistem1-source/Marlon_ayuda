<?php
/**
 * Logout - Destruir sesión
 */

session_start();
require_once __DIR__ . '/config/database.php';

session_destroy();

// Redirigir a login
header('Location: ' . BASE_URL . '/auth/login.php?logout=1');
exit();
?>
