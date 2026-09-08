<?php
/**
 * Header con Navbar Bootstrap
 * Se incluye al principio de cada página con include
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

// Detectar si estamos en la página de cambiar contraseña o si el usuario debe cambiarla obligatoriamente
$currentPage = basename($_SERVER['PHP_SELF']);
$isPasswordChangePage = ($currentPage === 'cambiar_password.php' || !empty($_SESSION['debe_cambiar_pass']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' - Tickets de Ayuda' : 'Sistema de Tickets'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <?php $cssVersion = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo $cssVersion; ?>">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-success" href="<?php echo BASE_URL; ?>/">
                <i class="fas fa-ticket-alt me-1"></i> Tickets Ayuda
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center gap-2 gap-lg-0">
                    <?php if (isLoggedIn()): ?>
                        <!-- Información del Usuario -->
                        <li class="nav-item d-flex align-items-center flex-wrap py-1 py-lg-0 me-lg-3">
                            <span class="nav-link text-dark p-0 d-inline-flex align-items-center">
                                <i class="fas fa-user-circle me-2 text-secondary fs-5"></i>
                                <span class="fw-semibold"><?php echo sanitize(getUserName()); ?></span>
                            </span>
                            <?php if (isSuperAdmin()): ?>
                                <span class="badge badge-role badge-role-superadmin ms-2">Superadmin</span>
                            <?php elseif (isAdmin()): ?>
                                <span class="badge badge-role badge-role-admin ms-2">Admin</span>
                            <?php endif; ?>
                        </li>

                        <!-- Notificaciones y Dashboard (Ocultos si está en la vista de cambio de contraseña) -->
                        <?php if (!$isPasswordChangePage): ?>
                            <?php if (isAdmin()): ?>
                                <?php $pendingNotifications = getUnreadNotificationsCount(getUserId()); ?>
                                <?php $recentNotifications = getNotificationsForUser(getUserId(), 10); ?>
                                <li class="nav-item dropdown py-1 py-lg-0 me-lg-2">
                                    <a class="nav-link dropdown-toggle position-relative d-inline-block py-1 px-2" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell fa-lg"></i>
                                        <?php if ($pendingNotifications > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?php echo $pendingNotifications; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-start dropdown-menu-lg-start p-2 shadow" aria-labelledby="notifDropdown" style="min-width: 280px; max-width: 380px;">
                                        <li class="px-2 py-1">
                                            <small class="text-muted fw-bold">Tienes <?php echo $pendingNotifications; ?> notificación(es) pendiente(s).</small>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if (!empty($recentNotifications)): ?>
                                            <?php foreach ($recentNotifications as $n): ?>
                                                <?php
                                                    $preview = getNotificationPreview($n);
                                                    $ticketId = $preview['ticket_id'];
                                                    if (!$ticketId) {
                                                        continue;
                                                    }
                                                    $displaySubject = strlen($preview['asunto']) > 60 ? substr($preview['asunto'], 0, 57) . '...' : $preview['asunto'];
                                                ?>
                                                <li class="notification-item mb-1" data-notif-id="<?php echo (int)$n['id']; ?>">
                                                    <div class="dropdown-item d-flex justify-content-between align-items-start gap-2 p-2 rounded">
                                                        <a href="<?php echo BASE_URL; ?>/admin/ver_ticket.php?id=<?php echo (int)$ticketId; ?>" class="flex-grow-1 text-decoration-none text-reset">
                                                            <div class="<?php echo $n['leida'] ? 'text-muted' : 'fw-bold'; ?> small mb-1">
                                                                <?php echo sanitize($displaySubject); ?>
                                                            </div>
                                                            <small class="text-muted d-block" style="font-size: 0.75rem;"><?php echo sanitize($preview['usuario']); ?> — <?php echo date('d/m/Y H:i', strtotime($preview['fecha'])); ?></small>
                                                        </a>
                                                        <div class="d-flex gap-1 flex-shrink-0 align-items-center">
                                                            <?php if (!$n['leida']): ?>
                                                                <span class="badge notif-badge-new">Nuevo</span>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm notif-close-btn mark-as-read-btn p-0 text-muted" title="Marcar como leída" aria-label="Marcar como leída" data-notif-id="<?php echo (int)$n['id']; ?>">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li><span class="dropdown-item text-muted small">No hay notificaciones pendientes.</span></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="text-center px-2 py-1">
                                            <form method="POST" action="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="marcar_leidas">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Marcar todas como leídas</button>
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <li class="nav-item py-1 py-lg-0 me-lg-2">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/usuario/dashboard.php">
                                        <i class="fas fa-home me-1"></i> Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <!-- Acciones del lado derecho (Admin / Logout / Login) -->
                <ul class="navbar-nav ms-auto align-items-lg-center border-top border-lg-0 pt-2 pt-lg-0 gap-1 gap-lg-0">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin() && !$isPasswordChangePage): ?>
                            <li class="nav-item dropdown me-lg-2">
                                <a class="nav-link dropdown-toggle py-1" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-shield me-1"></i> Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="adminMenu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/crear_usuario.php">
                                            <i class="fas fa-user-plus me-2 text-success"></i> Crear Usuario
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reportes.php">
                                            <i class="fas fa-chart-bar me-2 text-primary"></i> Reportes
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/historial_mensual.php">
                                            <i class="fas fa-history me-2 text-info"></i> Historial mensual
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                            <i class="fas fa-tachometer-alt me-2 text-secondary"></i> Panel
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-danger fw-semibold py-1" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link py-1" href="<?php echo BASE_URL; ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1" href="<?php echo BASE_URL; ?>/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i> Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Variable global para JavaScript -->
    <script>
        const baseUrl = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Contenido principal (flex-grow para que footer baje) -->
    <main class="flex-grow-1">
        <div class="container mt-4">