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

<div class="row ticket-row">
    <div class="col-lg-9">
        <!-- Información del Ticket -->
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-ticket-alt"></i> Ticket 
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
                    <div class="responses-green">
                        <?php foreach ($respuestas_admin as $respuesta): ?>
                            <div class="card mb-2 bg-transparent border-0">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark"><?php echo sanitize($respuesta['usuario_nombre']); ?></strong>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-secondary"><?php echo nl2br(sanitize($respuesta['mensaje'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <small class="text-muted d-block mt-2">
                    Este chat es solo de lectura .
                </small>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-lg-3 d-flex flex-column">
        <div class="card shadow mb-3 flex-grow-1 sidebar-card">
            <div class="card-header text-white">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="info-panel flex-grow-1">
                    <div class="info-row">
                        <div class="info-label">ID del Ticket</div>
                        <div class="info-value d-flex align-items-center gap-2">
                            <code class="ticket-id">#<?php echo $ticket['id']; ?></code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('#<?php echo $ticket['id']; ?>')" title="Copiar ID">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Área</div>
                        <div class="info-value">
                            <span class="badge badge-area"><?php echo sanitize($area); ?></span>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Fecha de Creación</div>
                        <div class="info-value"><i class="far fa-calendar-alt text-muted me-2"></i><?php echo date('d/m/Y H:i:s', strtotime($ticket['fecha_creacion'])); ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Última Actualización</div>
                        <div class="info-value"><i class="fas fa-history text-muted me-2"></i><?php echo date('d/m/Y H:i:s', strtotime($ticket['fecha_ultima_actualizacion'])); ?></div>
                    </div>
                </div>

                <div class="info-decor" aria-hidden="true">
                    <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                    <div class="info-decor__number"><?php echo $ticket['id']; ?></div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0">
                <a href="<?php echo BASE_URL; ?>/usuario/dashboard.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
