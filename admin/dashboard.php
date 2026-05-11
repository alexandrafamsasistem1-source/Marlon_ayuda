<?php
/**
 * Dashboard Admin - Ver todos los tickets
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que sea admin
if (!isLoggedIn()) {
    include __DIR__ . '/../auth/login.php';
    exit();
}
if (!isAdmin()) {
    include __DIR__ . '/../usuario/dashboard.php';
    exit();
}

$pageTitle = 'Panel de Administración';

// Parámetros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_ubicacion = $_GET['ubicacion'] ?? '';

// Obtener todos los tickets
$tickets = getAllTickets(200, 0);

// Aplicar filtros si existen
if ($filtro_estado || $filtro_ubicacion) {
    $tickets = array_filter($tickets, function($ticket) use ($filtro_estado, $filtro_ubicacion) {
        $coindice_estado = !$filtro_estado || $ticket['estado'] === $filtro_estado;
        $coincide_ubicacion = !$filtro_ubicacion || $ticket['ubicacion'] === $filtro_ubicacion;
        return $coindice_estado && $coincide_ubicacion;
    });
}

// Estadísticas
$stats_estado = countTicketsByStatus();
$stats_ubicacion = countTicketsByLocation();
$total_tickets = countTotalTickets();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Tickets</h5>
                <h2><?php echo $total_tickets; ?></h2>
            </div>
        </div>
    </div>
    <?php foreach ($stats_estado as $stat): ?>
        <div class="col-md-3 mb-3">
            <div class="card text-white" style="background-color: 
                <?php 
                    switch($stat['estado']) {
                        case 'Nuevo': echo '#ffc107'; break;
                        case 'En proceso': echo '#17a2b8'; break;
                        case 'Resuelto': echo '#28a745'; break;
                        case 'Cerrado': echo '#6c757d'; break;
                    }
                ?>;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $stat['estado']; ?></h5>
                    <h2><?php echo $stat['cantidad']; ?></h2>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
            <i class="fas fa-table"></i> Todos los Tickets
        </h4>
        <a href="<?php echo BASE_URL; ?>/admin/reportes.php" class="btn btn-sm btn-info">
            <i class="fas fa-chart-bar"></i> Ver Reportes
        </a>
    </div>
    <div class="card-body">

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <select class="form-select" name="estado" onchange="this.form.submit()">
                        <option value="">-- Filtrar por Estado --</option>
                        <option value="Nuevo" <?php echo $filtro_estado === 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                        <option value="En proceso" <?php echo $filtro_estado === 'En proceso' ? 'selected' : ''; ?>>En proceso</option>
                        <option value="Resuelto" <?php echo $filtro_estado === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        <option value="Cerrado" <?php echo $filtro_estado === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </form>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <select class="form-select" name="ubicacion" onchange="this.form.submit()">
                        <option value="">-- Filtrar por Ubicación --</option>
                        <option value="Finca El Jardín" <?php echo $filtro_ubicacion === 'Finca El Jardín' ? 'selected' : ''; ?>>Finca El Jardín</option>
                        <option value="San Ignacio" <?php echo $filtro_ubicacion === 'San Ignacio' ? 'selected' : ''; ?>>San Ignacio</option>
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
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Asunto</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Asignado a</th>
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
                                    <strong><?php echo sanitize($ticket['usuario_nombre']); ?></strong><br>
                                    <small class="text-muted"><?php echo sanitize($ticket['usuario_email']); ?></small>
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
                                            $estadoClass = 'badge bg-warning text-dark';
                                            break;
                                        case 'En proceso':
                                            $estadoClass = 'badge bg-info';
                                            break;
                                        case 'Resuelto':
                                            $estadoClass = 'badge bg-success';
                                            break;
                                        case 'Cerrado':
                                            $estadoClass = 'badge bg-secondary';
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
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Ver
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
