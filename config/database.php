<?php
/**
 * Configuración de Base de Datos
 * Conexión PDO a MySQL
 */

// Ruta base de la aplicación (para URLs correctas)
define('BASE_URL', '/proyecto_ayuda_app');

// Datos de conexión - CAMBIAR según tu ambiente
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tickets_ayuda');
define('DB_CHARSET', 'utf8mb4');

// Crear conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a base de datos: " . $e->getMessage());
}

// Retornar objeto PDO globalmente disponible
return $pdo;
?>
