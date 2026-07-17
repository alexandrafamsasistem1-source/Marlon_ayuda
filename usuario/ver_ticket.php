<?php
/**
 * Ver detalle de ticket del usuario
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que esté logueado
if (!isLoggedIn()) {
    include __DIR__ . '/../auth/login.php';
    exit();
}

$pageTitle = 'Ver Ticket';
$usuario_id = getUserId();
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener ticket
$ticket = getTicketById($ticket_id);

// Verificar que existe y que pertenece al usuario o es admin
if (!$ticket) {
    header('HTTP/1.1 404 Not Found');
    die('Ticket no encontrado.');
}

if ($ticket['usuario_id'] !== $usuario_id && !isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    die('No tienes permiso para ver este ticket.');
}

// Mostrar conversación en modo solo lectura (respuestas del equipo admin)
$respuestas = getTicketResponses($ticket_id);
$respuestas_admin = [];
foreach ($respuestas as $respuesta) {
    if (in_array($respuesta['rol'], ['admin', 'superadmin'], true)) {
        $respuestas_admin[] = $respuesta;
    }
}

// Preferir área persistida; si no existe, derivar por ubicación
$area = 'Administración';
if (!empty($ticket['area'])) {
    $area = $ticket['area'] === 'Poscosecha' ? 'Poscosecha' : 'Administración';
} else {
    if (isset($ticket['ubicacion']) && $ticket['ubicacion'] === 'Finca El Jardín') {
        $area = 'Poscosecha';
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Información del Ticket -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-ticket-alt"></i> Ticket #<?php echo $ticket['id']; ?>
                    </h4>
                    <span class="badge bg-light text-dark">
                        <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-3"><?php echo sanitize($ticket['asunto']); ?></h5>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <small class="text-muted">Estado:</small><br>
                        <?php 
                        $estadoClass = 're-state-chip re-state-chip--default';
                        switch($ticket['estado']) {
                            case 'Nuevo':
                                $estadoClass = 're-state-chip re-state-chip--nuevo';
                                break;
                            case 'En proceso':
                                $estadoClass = 're-state-chip re-state-chip--proceso';
                                break;
                            case 'Resuelto':
                                $estadoClass = 're-state-chip re-state-chip--resuelto';
                                break;
                            case 'Cerrado':
                                $estadoClass = 're-state-chip re-state-chip--cerrado';
                                break;
                        }
                        ?>
                        <span class="<?php echo $estadoClass; ?>">
                            <?php echo $ticket['estado']; ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Ubicación:</small><br>
                        <i class="fas fa-map-marker-alt text-danger"></i>
                        <strong><?php echo sanitize($ticket['ubicacion']); ?></strong>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Usuario:</small><br>
                        <strong><?php echo sanitize($ticket['usuario_nombre']); ?></strong><br>
                        <small class="text-muted"><?php echo sanitize($ticket['usuario_email']); ?></small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Asignado a:</small><br>
                        <?php if ($ticket['asignado_a']): ?>
                            <strong><?php echo sanitize($ticket['asignado_nombre']); ?></strong> 
                            <span class="badge bg-info">Admin</span>
                        <?php else: ?>
                            <em class="text-muted">No asignado</em>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>

                <h6>Descripción:</h6>
                <div class="bg-light p-3 rounded border">
                    <?php echo nl2br(sanitize($ticket['descripcion'])); ?>
                </div>

                <hr>

                <h6>Respuestas del Soporte</h6>
                <?php if (empty($respuestas_admin)): ?>
                    <div class="bg-light p-3 rounded border text-muted">
                        Aún no hay respuestas del equipo de soporte.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($respuestas_admin as $respuesta): ?>
                            <div class="list-group-item list-group-item-success">
                                <div class="d-flex w-100 justify-content-between">
                                    <strong><?php echo sanitize($respuesta['usuario_nombre']); ?></strong>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?></small>
                                </div>
                                <div class="mt-1">
                                    <?php echo nl2br(sanitize($respuesta['mensaje'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <small class="text-muted d-block mt-2">
                    Este chat es solo de lectura para usuarios.
                </small>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="card shadow mb-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">ID del Ticket</small>
                    <p class="mb-0"><code>#<?php echo $ticket['id']; ?></code></p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Área</small>
                    <p class="mb-0"><strong><?php echo sanitize($area); ?></strong></p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Fecha de Creación</small>
                    <p class="mb-0"><?php echo date('d/m/Y H:i:s', strtotime($ticket['fecha_creacion'])); ?></p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Última Actualización</small>
                    <p class="mb-0"><?php echo date('d/m/Y H:i:s', strtotime($ticket['fecha_ultima_actualizacion'])); ?></p>
                </div>
            </div>
        </div>

        <a href="<?php echo BASE_URL; ?>/usuario/dashboard.php" class="btn btn-secondary w-100">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
