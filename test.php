<?php
/**
 * Página de prueba - Verificar que todo está funcionando
 * ELIMINAR ANTES DE PRODUCCIÓN
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Test - Verificación del Sistema';
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row">
    <div class="col-lg-12">
        <h2 class="mb-4">
            <i class="fas fa-flask-vial"></i> Verificación del Sistema
        </h2>

        <div class="alert alert-warning">
            <strong>⚠️ Advertencia:</strong> Esta página es solo para testing. Elimina el archivo `test.php` antes de instalar en producción.
        </div>

        <!-- Verificación de Base de Datos -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">✓ Conexión a Base de Datos</h5>
            </div>
            <div class="card-body">
                <?php 
                try {
                    $pdo = getDB();
                    $result = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                    if ($result) {
                        $data = $result->fetch();
                        echo '<div class="alert alert-success">';
                        echo '<i class="fas fa-check-circle"></i> ✓ <strong>Conexión exitosa</strong><br>';
                        echo 'Usuarios en BD: <strong>' . $data['total'] . '</strong>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<i class="fas fa-times-circle"></i> ✗ <strong>Error:</strong> ' . $e->getMessage();
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Verificación de Tablas -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">✓ Tablas de Base de Datos</h5>
            </div>
            <div class="card-body">
                <?php 
                try {
                    $pdo = getDB();
                    
                    $tables = [
                        'usuarios' => 'Tabla de usuarios',
                        'tickets' => 'Tabla de tickets',
                        'respuestas_ticket' => 'Tabla de respuestas'
                    ];

                    foreach ($tables as $table => $desc) {
                        $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
                        if ($result->rowCount() > 0) {
                            echo '<div class="alert alert-success mb-2">';
                            echo '<i class="fas fa-check"></i> ' . $desc . ' (<code>' . $table . '</code>)';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-danger mb-2">';
                            echo '<i class="fas fa-times"></i> <strong>FALTA:</strong> ' . $desc . ' (<code>' . $table . '</code>)';
                            echo '</div>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<i class="fas fa-times-circle"></i> Error: ' . $e->getMessage();
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Verificación de Credenciales de Prueba -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">✓ Usuarios de Prueba</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Contraseña</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        try {
                            $pdo = getDB();
                            $stmt = $pdo->query('SELECT email, rol, nombre FROM usuarios ORDER BY rol DESC');
                            $usuarios = $stmt->fetchAll();

                            if (count($usuarios) === 0) {
                                echo '<tr><td colspan="4" class="text-danger"><i class="fas fa-times"></i> <strong>NO HAY USUARIOS</strong> - Importa setup_database.sql</td></tr>';
                            } else {
                                foreach ($usuarios as $u) {
                                    echo '<tr>';
                                    echo '<td>' . $u['email'] . '</td>';
                                    echo '<td>';
                                    if ($u['rol'] === 'admin') {
                                        echo '<span class="badge bg-danger">Admin</span>';
                                    } else {
                                        echo '<span class="badge bg-info">Usuario</span>';
                                    }
                                    echo '</td>';
                                    echo '<td>';
                                    if ($u['email'] === 'admin@tickets.local') {
                                        echo '<code>admin123</code>';
                                    } elseif ($u['email'] === 'usuario@tickets.local') {
                                        echo '<code>usuario123</code>';
                                    } else {
                                        echo '<em class="text-muted">Custom</em>';
                                    }
                                    echo '</td>';
                                    echo '<td><span class="badge bg-success">Activo</span></td>';
                                    echo '</tr>';
                                }
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="4" class="text-danger">Error: ' . $e->getMessage() . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Verificación de Archivos -->
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">✓ Archivos Principales</h5>
            </div>
            <div class="card-body">
                <?php
                $archivos = [
                    'index.php' => 'Punto de entrada',
                    'logout.php' => 'Cerrar sesión',
                    'config/database.php' => 'Configuración BD',
                    'includes/functions.php' => 'Funciones principales',
                    'includes/header.php' => 'Navbar',
                    'includes/footer.php' => 'Footer',
                    '/proyecto_ayuda_app/auth/login.php' => 'Página de login',
                    '/proyecto_ayuda_app/auth/register.php' => 'Página de registro',
                    '/proyecto_ayuda_app/usuario/dashboard.php' => 'Dashboard usuario',
                    '/proyecto_ayuda_app/usuario/crear_ticket.php' => 'Crear ticket',
                    '/proyecto_ayuda_app/usuario/ver_ticket.php' => 'Ver ticket (usuario)',
                    '/proyecto_ayuda_app/admin/dashboard.php' => 'Panel admin',
                    '/proyecto_ayuda_app/admin/ver_ticket.php' => 'Ver ticket (admin)',
                    '/proyecto_ayuda_app/admin/reportes.php' => 'Reportes',
                    'assets/css/style.css' => 'Estilos CSS',
                    'assets/js/main.js' => 'JavaScript',
                ];

                $basePath = __DIR__;
                foreach ($archivos as $file => $desc) {
                    $fullPath = $basePath . '/' . $file;
                    $exists = file_exists($fullPath);
                    echo '<div class="alert ' . ($exists ? 'alert-success' : 'alert-danger') . ' mb-2">';
                    echo '<i class="fas fa-' . ($exists ? 'check' : 'times') . '"></i> ';
                    echo ($exists ? '✓' : '✗') . ' <code>' . $file . '</code> - ' . $desc;
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Estado de Sesión -->
        <div class="card shadow mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">✓ Estado de Sesión</h5>
            </div>
            <div class="card-body">
                <?php
                if (isLoggedIn()) {
                    echo '<div class="alert alert-success">';
                    echo '<i class="fas fa-check-circle"></i> <strong>Logueado:</strong> ' . sanitize(getUserName());
                    echo ' (' . sanitize(getUserRole()) . ')';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-info">';
                    echo '<i class="fas fa-info-circle"></i> No hay sesión activa.';
                    echo ' <a href="' . BASE_URL . '/proyecto_ayuda_app/auth/login.php" class="alert-link">Iniciar sesión</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Links de Prueba -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">🔗 Links de Acceso</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/auth/login.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-in-alt"></i> Página de Login
                    </a>
                    <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/auth/register.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus"></i> Página de Registro
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/admin/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt"></i> Panel Administrativo
                            </a>
                            <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/admin/reportes.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/usuario/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-home"></i> Mi Dashboard
                            </a>
                            <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/usuario/crear_ticket.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-plus-circle"></i> Crear Ticket
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/proyecto_ayuda_app/logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
