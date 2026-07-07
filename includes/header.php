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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/">
                <i class="fas fa-ticket-alt"></i> Tickets Ayuda
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav align-items-center me-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item d-flex align-items-center me-3">
                            <span class="nav-link text-info p-0">
                                <i class="fas fa-user-circle"></i>
                                <span class="ms-2"><?php echo sanitize(getUserName()); ?></span>
                            </span>
                            <?php if (isSuperAdmin()): ?>
                                <span class="badge bg-warning text-dark ms-2">Superadmin</span>
                            <?php elseif (isAdmin()): ?>
                                <span class="badge bg-secondary ms-2">Admin</span>
                            <?php endif; ?>
                        </li>

                        <?php if (isAdmin()): ?>
                            <?php $pendingNotifications = getUnreadNotificationsCount(getUserId()); ?>
                            <?php $recentNotifications = getNotificationsForUser(getUserId(), 10); ?>
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link dropdown-toggle position-relative p-0" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell fa-lg"></i>
                                    <?php if ($pendingNotifications > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php echo $pendingNotifications; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu p-2" aria-labelledby="notifDropdown" style="min-width:320px; max-width:420px;">
                                    <li class="px-2">
                                        <small class="text-muted">Tienes <?php echo $pendingNotifications; ?> notificación(es) pendiente(s).</small>
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
                                                $displaySubject = strlen($preview['asunto']) > 80 ? substr($preview['asunto'], 0, 77) . '...' : $preview['asunto'];
                                            ?>
                                            <li>
                                                <a class="dropdown-item d-flex justify-content-between align-items-start" href="<?php echo BASE_URL; ?>/admin/ver_ticket.php?id=<?php echo (int)$ticketId; ?>">
                                                    <div>
                                                        <div class="<?php echo $n['leida'] ? 'text-muted' : 'fw-bold'; ?> mb-1">
                                                            <?php echo sanitize($displaySubject); ?>
                                                        </div>
                                                        <small class="text-muted"><?php echo sanitize($preview['usuario']); ?> — <?php echo date('d/m/Y H:i', strtotime($preview['fecha'])); ?></small>
                                                    </div>
                                                    <?php if (!$n['leida']): ?>
                                                        <span class="badge bg-danger ms-2">Nuevo</span>
                                                    <?php endif; ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li><span class="dropdown-item text-muted">No hay notificaciones aún.</span></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="text-center px-2">
                                        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php?marcar_leidas=1" class="btn btn-sm btn-outline-secondary">Marcar como leídas</a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Admin links moved to the right for cleaner layout -->
                        <?php else: ?>
                            <li class="nav-item me-2">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/usuario/dashboard.php">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link dropdown-toggle" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-shield"></i> Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminMenu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/crear_usuario.php">
                                            <i class="fas fa-user-plus me-2"></i> Crear Usuario
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reportes.php">
                                            <i class="fas fa-chart-bar me-2"></i> Reportes
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                                            <i class="fas fa-tachometer-alt me-2"></i> Panel
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/register.php">
                                <i class="fas fa-user-plus"></i> Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal (flex-grow para que footer baje) -->
    <main class="flex-grow-1">
        <div class="container mt-4">
