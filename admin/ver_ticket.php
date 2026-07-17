<?php
/**
 * Admin - Ver detalle de ticket y responder
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$pageTitle = 'Ver Ticket (Admin)';
$admin_id = getUserId();
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener ticket
$ticket = getTicketById($ticket_id);

$urgencia_actual = $ticket['urgencia'] ?? 'Media';

if (!$ticket) {
    header('HTTP/1.1 404 Not Found');
    die('Ticket no encontrado.');
}

// Obtener respuestas
$respuestas = getTicketResponses($ticket_id);

// Obtener lista de admins
$admins = getAllAdmins();

// Procesar formularios
$error = '';
$success = '';

// Procesar cambio de estado/asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cambiar_estado') {
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';
        $asignado_a = $_POST['asignado_a'] ?? null;
        $urgencia = $_POST['urgencia'] ?? null;

        if (empty($nuevo_estado)) {
            $error = 'Debes seleccionar un estado.';
        } elseif (!in_array($nuevo_estado, ['Nuevo', 'En proceso', 'Resuelto', 'Cerrado'])) {
            $error = 'Estado inválido.';
        } else {
            $asignado_id = null;
            if (isSuperAdmin() && $asignado_a && $asignado_a !== 'ninguno') {
                $asignado_id = $asignado_a;
            } elseif ($asignado_a && $asignado_a !== 'ninguno') {
                $error = 'Solo el superadmin puede asignar tickets.';
            }

            if (empty($error) && updateTicketStatus($ticket_id, $nuevo_estado, $asignado_id)) {
                // Intentar guardar urgencia si viene
                if ($urgencia !== null) {
                    try {
                        $pdo = getDB();
                        $hasUrg = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'urgencia'")->fetchColumn();
                        if (!$hasUrg) {
                            // Añadir columna si no existe (varchar corta)
                            $pdo->exec("ALTER TABLE tickets ADD COLUMN urgencia VARCHAR(20) DEFAULT 'Media'");
                        }
                        $pdo->prepare('UPDATE tickets SET urgencia = ? WHERE id = ?')->execute([$urgencia, $ticket_id]);
                    } catch (Exception $e) {
                        // No interrumpir si falla el cambio de columna
                    }
                }
                $success = 'Ticket actualizado correctamente.';
                // Recargar ticket
                $ticket = getTicketById($ticket_id);
                $urgencia_actual = $ticket['urgencia'] ?? 'Media';
            } else {
                $error = 'Error al actualizar el ticket.';
            }
        }
    } elseif ($_POST['action'] === 'responder') {
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (empty($mensaje)) {
            $error = 'El mensaje no puede estar vacío.';
        } elseif (strlen($mensaje) < 5) {
            $error = 'El mensaje debe tener al menos 5 caracteres.';
        } else {
            $result = addResponseToTicket($ticket_id, $admin_id, $mensaje);
            
            if ($result['success']) {
                $success = 'Respuesta enviada correctamente.';
                $respuestas = getTicketResponses($ticket_id);
                $_POST = [];
            } else {
                $error = $result['error'] ?? 'Error al enviar respuesta.';
            }
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
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title mb-0"><?php echo sanitize($ticket['asunto']); ?></h5>
                    <div class="text-end">
                        <span class="badge bg-light text-dark me-1"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></span>
                        <?php
                        $urg = $ticket['urgencia'] ?? 'Media';
                        $urgClass = 'bg-secondary';
                        switch (strtolower($urg)) {
                            case 'alta': case 'high': $urgClass = 'bg-danger'; break;
                            case 'baja': case 'low': $urgClass = 'bg-success'; break;
                            default: $urgClass = 'bg-warning text-dark'; break;
                        }
                        ?>
                        <span class="badge <?php echo $urgClass; ?>">Urgencia: <?php echo sanitize(ucfirst($urg)); ?></span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Estado Actual:</small><br>
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
                        <small class="text-muted">Usuario que Reporta:</small><br>
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
                <div class="bg-light p-3 rounded border mb-3">
                    <?php echo nl2br(sanitize($ticket['descripcion'])); ?>
                </div>

                <!-- Conversación y respuesta integrada -->
                <h6>Conversación</h6>
                <div class="mb-3">
                    <?php if (empty($respuestas)): ?>
                        <div class="small text-muted">No hay respuestas aún. Usa el formulario para responder.</div>
                    <?php else: ?>
                        <div class="list-group mb-2">
                            <?php foreach ($respuestas as $respuesta): ?>
                                <div class="list-group-item <?php echo $respuesta['rol'] === 'admin' ? 'list-group-item-success' : ''; ?>">
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

                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="responder">
                        <div class="mb-2">
                            <textarea class="form-control" name="mensaje" rows="4" placeholder="Escribe tu respuesta aquí..." required><?php echo isset($_POST['mensaje']) ? sanitize($_POST['mensaje']) : ''; ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-palette-primary">
                                <i class="fas fa-paper-plane"></i> Enviar Respuesta
                            </button>
                            <a href="<?php echo BASE_URL; ?>/admin/ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn btn-light">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Respuestas y formulario integrados en la sección de Descripción (eliminados los bloques duplicados) -->

    </div>

    <!-- Sidebar - Cambiar Estado -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-tasks"></i> Cambiar Estado
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="cambiar_estado">

                    <div class="mb-3">
                        <label for="nuevo_estado" class="form-label">Nuevo Estado:</label>
                        <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="Nuevo" <?php echo $ticket['estado'] === 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                            <option value="En proceso" <?php echo $ticket['estado'] === 'En proceso' ? 'selected' : ''; ?>>En proceso</option>
                            <option value="Resuelto" <?php echo $ticket['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                            <option value="Cerrado" <?php echo $ticket['estado'] === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                        </select>
                    </div>

                    <?php if (isSuperAdmin()): ?>
                        <div class="mb-3">
                            <label for="asignado_a" class="form-label">Asignar a:</label>
                            <select class="form-select" id="asignado_a" name="asignado_a">
                                <option value="ninguno">-- Sin asignar --</option>
                                <?php foreach ($admins as $admin): ?>
                                    <option value="<?php echo $admin['id']; ?>" 
                                        <?php echo ($ticket['asignado_a'] && $ticket['asignado_a'] === $admin['id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitize($admin['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="urgencia" class="form-label">Urgencia / Prioridad:</label>
                        <select class="form-select" id="urgencia" name="urgencia">
                            <?php $curUrg = $ticket['urgencia'] ?? 'Media'; ?>
                            <option value="Baja" <?php echo strtolower($curUrg) === 'baja' ? 'selected' : ''; ?>>Baja</option>
                            <option value="Media" <?php echo strtolower($curUrg) === 'media' ? 'selected' : ''; ?>>Media</option>
                            <option value="Alta" <?php echo strtolower($curUrg) === 'alta' ? 'selected' : ''; ?>>Alta</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-palette-accent w-100">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-secondary w-100">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
