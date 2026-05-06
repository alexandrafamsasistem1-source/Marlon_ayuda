<?php
/**
 * Ver detalle de ticket del usuario
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar que esté logueado
requireLogin();

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

// Obtener respuestas
$respuestas = getTicketResponses($ticket_id);

// Procesar nueva respuesta (solo usuario propietario)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticket['usuario_id'] === $usuario_id) {
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (empty($mensaje)) {
        $error = 'El mensaje no puede estar vacío.';
    } elseif (strlen($mensaje) < 5) {
        $error = 'El mensaje debe tener al menos 5 caracteres.';
    } else {
        $result = addResponseToTicket($ticket_id, $usuario_id, $mensaje);
        
        if ($result['success']) {
            $success = 'Respuesta enviada correctamente.';
            // Recargar respuestas
            $respuestas = getTicketResponses($ticket_id);
            $_POST = [];
        } else {
            $error = $result['error'] ?? 'Error al enviar respuesta.';
        }
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
                        <span class="<?php echo $estadoClass; ?>" style="font-size: 1.1em;">
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
            </div>
        </div>

        <!-- Respuestas -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-comments"></i> Respuestas (<?php echo count($respuestas); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($respuestas)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay respuestas aún. El administrador responderá pronto.
                    </div>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($respuestas as $respuesta): ?>
                            <div class="card mb-3 <?php echo $respuesta['rol'] === 'admin' ? 'border-success' : 'border-secondary'; ?>">
                                <div class="card-header">
                                    <strong><?php echo sanitize($respuesta['usuario_nombre']); ?></strong>
                                    <?php if ($respuesta['rol'] === 'admin'): ?>
                                        <span class="badge bg-success">Administrador</span>
                                    <?php endif; ?>
                                    <small class="text-muted float-end">
                                        <?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br(sanitize($respuesta['mensaje'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enviar respuesta (solo usuario propietario) -->
        <?php if ($ticket['usuario_id'] === $usuario_id && $ticket['estado'] !== 'Cerrado'): ?>
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-reply"></i> Agregar Respuesta
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo sanitize($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <textarea class="form-control" name="mensaje" rows="4"
                                      placeholder="Escribe tu respuesta aquí..."
                                      required><?php echo isset($_POST['mensaje']) ? sanitize($_POST['mensaje']) : ''; ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane"></i> Enviar Respuesta
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

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
                    <small class="text-muted">Fecha de Creación</small>
                    <p class="mb-0"><?php echo date('d/m/Y H:i:s', strtotime($ticket['fecha_creacion'])); ?></p>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Última Actualización</small>
                    <p class="mb-0"><?php echo date('d/m/Y H:i:s', strtotime($ticket['fecha_ultima_actualizacion'])); ?></p>
                </div>
            </div>
        </div>

        <a href="/usuario/dashboard.php" class="btn btn-secondary w-100">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
