<?php
/**
 * Dashboard Admin - Ver todos los tickets
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que sea admin
requireAdmin();

if (isset($_GET['marcar_leidas'])) {
    markNotificationsAsRead(getUserId());
}

$pageTitle = 'Panel de Administración';

// Parámetros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_ubicacion = $_GET['ubicacion'] ?? '';
$filtro_urgencia = $_GET['urgencia'] ?? '';

// Obtener todos los tickets
$tickets = getAllTickets(200, 0);

// Aplicar filtros si existen (incluye urgencia)
if ($filtro_estado || $filtro_ubicacion || $filtro_urgencia) {
    $tickets = array_filter($tickets, function($ticket) use ($filtro_estado, $filtro_ubicacion, $filtro_urgencia) {
        $coindice_estado = !$filtro_estado || ($ticket['estado'] ?? '') === $filtro_estado;
        $coincide_ubicacion = !$filtro_ubicacion || ($ticket['ubicacion'] ?? '') === $filtro_ubicacion;
        $coincide_urgencia = !$filtro_urgencia || (strtolower(($ticket['urgencia'] ?? '')) === strtolower($filtro_urgencia));
        return $coindice_estado && $coincide_ubicacion && $coincide_urgencia;
    });
}

// Estadísticas
$stats_estado = countTicketsByStatus();
$stats_ubicacion = countTicketsByLocation();
$total_tickets = countTotalTickets();
$notificaciones_pendientes = getUnreadNotificationsCount(getUserId());
$notificaciones_recientes = getNotificationsForUser(getUserId(), 10);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
/* Color personalizado para estado "Nuevo" */
.badge.bg-nuevo{background-color:#c8d6bd;color:#16321f}
</style>

<!-- Notificaciones movidas al dropdown de la cabecera. -->

<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
            <i class="fas fa-table"></i> Todos los Tickets
        </h4>
        <!-- Reportes button removed -->
    </div>
    <div class="card-body">

        <!-- Filtros: Estado / Ubicación / Urgencia -->
        <div class="row mb-3">
            <div class="col-12">
                <form method="GET" class="d-flex gap-2">
                    <select class="form-select" name="estado" onchange="this.form.submit()">
                        <option value="">-- Filtrar por Estado --</option>
                        <option value="Nuevo" <?php echo $filtro_estado === 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                        <option value="En proceso" <?php echo $filtro_estado === 'En proceso' ? 'selected' : ''; ?>>En proceso</option>
                        <option value="Resuelto" <?php echo $filtro_estado === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        <option value="Cerrado" <?php echo $filtro_estado === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>

                    <select class="form-select" name="ubicacion" onchange="this.form.submit()">
                        <option value="">-- Filtrar por Ubicación --</option>
                        <option value="Finca El Jardín" <?php echo $filtro_ubicacion === 'Finca El Jardín' ? 'selected' : ''; ?>>Finca El Jardín</option>
                        <option value="San Ignacio" <?php echo $filtro_ubicacion === 'San Ignacio' ? 'selected' : ''; ?>>San Ignacio</option>
                    </select>

                    <select class="form-select" name="urgencia" onchange="this.form.submit()">
                        <option value="">-- Filtrar por Urgencia --</option>
                        <option value="Alta" <?php echo strtolower($filtro_urgencia) === 'alta' ? 'selected' : ''; ?>>Alta</option>
                        <option value="Media" <?php echo strtolower($filtro_urgencia) === 'media' ? 'selected' : ''; ?>>Media</option>
                        <option value="Baja" <?php echo strtolower($filtro_urgencia) === 'baja' ? 'selected' : ''; ?>>Baja</option>
                    </select>
                </form>
            </div>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay tickets que mostrar.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-clean table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Asunto</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Asignado a</th>
                            <th>Urgencia</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">#<?php echo $ticket['id']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo sanitize($ticket['usuario_nombre']); ?></strong>
                                 
                                </td>
                                <td>
                                    <?php echo sanitize(substr($ticket['asunto'], 0, 30)); ?>
                                    <?php if (strlen($ticket['asunto']) > 30) echo '...'; ?>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo sanitize($ticket['ubicacion']); ?>
                                </td>
                                <td>
                                    <?php 
                                    $estadoClass = '';
                                    switch($ticket['estado']) {
                                        case 'Nuevo':
                                            $estadoClass = 'badge-status nuevo';
                                            break;
                                        case 'En proceso':
                                            $estadoClass = 'badge bg-info';
                                            break;
                                        case 'Resuelto':
                                            $estadoClass = 'badge bg-success';
                                            break;
                                        case 'Cerrado':
                                            $estadoClass = 'badge-status cerrado';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $estadoClass; ?>">
                                        <?php echo $ticket['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ticket['asignado_a']): ?>
                                        <small><?php echo sanitize($ticket['asignado_nombre']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">No asignado</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $urg = $ticket['urgencia'] ?? 'Media';
                                    $urgClass = 'badge bg-warning text-dark';
                                    if (strtolower($urg) === 'alta') $urgClass = 'badge bg-danger';
                                    if (strtolower($urg) === 'baja') $urgClass = 'badge bg-success';
                                    ?>
                                    <span class="<?php echo $urgClass; ?>"><?php echo sanitize(ucfirst($urg)); ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> 
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
