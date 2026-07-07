<?php
/**
 * Logout - Destruir sesión
 */

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
require_once __DIR__ . '/config/database.php';

session_destroy();

// Redirigir a login
header('Location: ' . BASE_URL . '/auth/login.php?logout=1');
exit();
?>
