<?php
/**
 * Configuración de Base de Datos
 * Conexión PDO a MySQL
 */

// Ruta base de la aplicación (para URLs correctas)
// Se calcula dinámicamente en entorno local (incluye esquema y host)
// Intenta obtener la ruta del proyecto relativa a DOCUMENT_ROOT
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
$projectRoot = realpath(__DIR__ . '/..');
$basePath = '';
if ($documentRoot && $projectRoot) {
    $doc = str_replace('\\', '/', $documentRoot);
    $proj = str_replace('\\', '/', $projectRoot);
    $basePath = str_replace($doc, '', $proj);
}
if ($basePath === '') {
    $basePath = '/';
}
if (substr($basePath, 0, 1) !== '/') $basePath = '/' . $basePath;
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', rtrim($scheme . '://' . $host . $basePath, '/'));

// Datos de conexión - CAMBIAR según tu ambiente
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tickets_ayuda');
define('DB_CHARSET', 'utf8mb4');

// Configuración de correo electrónico
// Puedes sobrescribir estos valores en el servidor con variables de entorno o editando aquí.
define('MAIL_ENABLED', getenv('MAIL_ENABLED') !== false ? filter_var(getenv('MAIL_ENABLED'), FILTER_VALIDATE_BOOLEAN) : true);
define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: 'mail');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@tickets.local');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Sistema de Tickets');
define('MAIL_ADMIN_OVERRIDE', getenv('MAIL_ADMIN_OVERRIDE') ?: '');
define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: '');
define('MAIL_SMTP_PORT', getenv('MAIL_SMTP_PORT') ?: 587);
define('MAIL_SMTP_USERNAME', getenv('MAIL_SMTP_USERNAME') ?: '');
define('MAIL_SMTP_PASSWORD', getenv('MAIL_SMTP_PASSWORD') ?: '');
define('MAIL_SMTP_ENCRYPTION', getenv('MAIL_SMTP_ENCRYPTION') ?: 'tls');
define('MAIL_SMTP_AUTH', getenv('MAIL_SMTP_AUTH') !== false ? filter_var(getenv('MAIL_SMTP_AUTH'), FILTER_VALIDATE_BOOLEAN) : true);

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
