<?php
/**
 * Logout - Destruir sesión
 */

session_start();
session_destroy();

// Redirigir a login
header('Location: /auth/login.php?logout=1');
exit();
?>
