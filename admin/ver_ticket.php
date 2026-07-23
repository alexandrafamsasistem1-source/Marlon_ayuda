<?php
/**
 * Admin - Ver detalle de ticket y responder
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificaciones de Sesión
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

// Obtener Ticket
$ticket = getTicketById($ticket_id);

if (!$ticket) {
    header('HTTP/1.1 404 Not Found');
    die('Ticket no encontrado.');
}

$urgencia_actual = $ticket['urgencia'] ?? 'Media';
$respuestas = getTicketResponses($ticket_id);
$admins = getAllAdmins();

$error = '';
$success = '';

// Procesamiento de Formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Accion: Cambiar estado / asignacion / urgencia
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

            $estado_anterior = $ticket['estado'] ?? '';
            $asignado_anterior = $ticket['asignado_a'] ?? null;
            $urgencia_anterior = $ticket['urgencia'] ?? 'Media';

            if (empty($error) && updateTicketStatus($ticket_id, $nuevo_estado, $asignado_id)) {
                
                // Bitácora 1: Estado
                if ($nuevo_estado !== $estado_anterior) {
                    $descEstado = "Cambió el estado de '" . $estado_anterior . "' a '" . $nuevo_estado . "'";
                    registrarHistorialTicket($ticket_id, $admin_id, 'cambio_estado', $descEstado);
                }

                // Bitácora 2: Reasignación
                $old_asig = $asignado_anterior ? (string)$asignado_anterior : null;
                $new_asig = $asignado_id ? (string)$asignado_id : null;

                if ($old_asig !== $new_asig) {
                    $nombre_nuevo_admin = 'Sin asignar';
                    if ($new_asig) {
                        foreach ($admins as $adm) {
                            if ((string)$adm['id'] === $new_asig) {
                                $nombre_nuevo_admin = $adm['nombre'];
                                break;
                            }
                        }
                    }
                    $descReasig = "Reasignó el ticket a " . $nombre_nuevo_admin;
                    registrarHistorialTicket($ticket_id, $admin_id, 'reasignacion', $descReasig);
                }

                // Bitácora 3: Urgencia
                if ($urgencia !== null) {
                    try {
                        $pdo = getDB();
                        $hasUrg = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'urgencia'")->fetchColumn();
                        if (!$hasUrg) {
                            $pdo->exec("ALTER TABLE tickets ADD COLUMN urgencia VARCHAR(20) DEFAULT 'Media'");
                        }
                        $pdo->prepare('UPDATE tickets SET urgencia = ? WHERE id = ?')->execute([$urgencia, $ticket_id]);

                        if (strtolower($urgencia) !== strtolower($urgencia_anterior)) {
                            $descUrg = "Cambió la urgencia a '" . $urgencia . "'";
                            registrarHistorialTicket($ticket_id, $admin_id, 'cambio_urgencia', $descUrg);
                        }
                    } catch (Exception $e) {
                        // Continuar
                    }
                }

                $success = 'Ticket actualizado correctamente.';
                $ticket = getTicketById($ticket_id);
            } else {
                $error = $error ?: 'Error al actualizar el ticket.';
            }
        }
    // Acción: Responder Ticket
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
                registrarHistorialTicket($ticket_id, $admin_id, 'respuesta', 'Respondió al ticket');
                $respuestas = getTicketResponses($ticket_id);
                $_POST = [];
            } else {
                $error = $result['error'] ?? 'Error al enviar respuesta.';
            }
        }
    }
}

// Cargar Historial
$historial = function_exists('getTicketHistory') ? getTicketHistory($ticket_id) : [];

// Cargar Cabecera de Bootstrap y Estilos
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">

    <!-- Mensajes de Alerta -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- ========================================================= -->
        <!-- COLUMNA IZQUIERDA: DETALLES DEL TICKET Y CONVERSACIÓN     -->
        <!-- ========================================================= -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <!-- Banner Verde del Titulo -->
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #0c5737;">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-ticket-alt me-2"></i>Ticket #<?php echo $ticket['id']; ?>
                    </h5>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                    </span>
                </div>

                <div class="card-body p-4">
                    <!-- Titulo y Urgencia -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0 text-uppercase">
                            <?php echo sanitize($ticket['titulo'] ?? $ticket['asunto'] ?? 'Sin Título'); ?>
                        </h4>
                        <span class="badge bg-warning text-dark px-3 py-2 fw-semibold" style="font-size: 0.85rem;">
                            Urgencia: <?php echo sanitize($ticket['urgencia'] ?? 'Media'); ?>
                        </span>
                    </div>
                    <!-- Datos del Ticket -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <span class="text-muted d-block small">Estado Actual:</span>
                            <span class="badge bg-secondary px-3 py-2 mt-1"><?php echo sanitize($ticket['estado']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">Ubicación:</span>
                            <span class="fw-bold text-dark">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <?php echo sanitize($ticket['ubicacion'] ?? 'No especificada'); ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">Usuario que Reporta:</span>
                            <strong class="text-dark"><?php echo sanitize($ticket['usuario_nombre'] ?? 'Usuario'); ?></strong><br>
                            <small class="text-muted"><?php echo sanitize($ticket['usuario_email'] ?? ''); ?></small>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">Asignado a:</span>
                            <strong class="text-dark"><?php echo sanitize($ticket['asignado_nombre'] ?? 'Sin asignar'); ?></strong>
                            <?php if (!empty($ticket['asignado_nombre'])): ?>
                                <span class="badge bg-info text-dark ms-1">Admin</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="text-muted">

                    <!-- Descripción del problema -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Descripción:</label>
                        <div class="p-3 bg-light rounded border text-break">
                            <?php echo nl2br(sanitize($ticket['descripcion'])); ?>
                        </div>
                    </div>

                  
                    <!-- Conversación / Mensajes -->
                    <h5 class="fw-bold mb-3">Conversación</h5>
                    <div class="mb-4">
                        <?php if (empty($respuestas)): ?>
                            <div class="alert alert-light border text-muted">No hay mensajes en esta conversación aún.</div>
                        <?php else: ?>
                            <?php foreach ($respuestas as $resp): ?>
                                <div class="card mb-2 bg-light border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark">
                                                <?php echo sanitize($resp['autor_nombre'] ?? $resp['usuario_nombre'] ?? $resp['nombre'] ?? 'Usuario'); ?>
                                            </strong>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($resp['fecha_creacion'])); ?></small>
                                        </div>
                                        <p class="mb-0 text-secondary"><?php echo nl2br(sanitize($resp['mensaje'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Caja de Respuesta -->
                    <form method="POST">
                        <input type="hidden" name="action" value="responder">
                        <div class="mb-3">
                            <textarea class="form-control" name="mensaje" rows="3" placeholder="Escribe tu respuesta aquí..." required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn text-white fw-bold px-4" style="background-color: #0c5737;">
                                <i class="fas fa-paper-plane me-1"></i> Enviar Respuesta
                            </button>
                            <button type="reset" class="btn btn-light border">Cancelar</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- COLUMNA DERECHA: CAMBIAR ESTADO, HISTORIAL Y VOLVER       -->
        <!-- ========================================================= -->
        <div class="col-lg-4">
            
            <!-- 1. Tarjeta: Cambiar Estado -->
            <div class="card shadow mb-4">
                <div class="card-header text-white" style="background-color: #0c5737;">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-1"></i> Cambiar Estado
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
                                            <?php echo ($ticket['asignado_a'] && (string)$ticket['asignado_a'] === (string)$admin['id']) ? 'selected' : ''; ?>>
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

                        <button type="submit" class="btn w-100 fw-semibold text-dark" style="background-color: #cbb26a;">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>

            <!-- 2. Tarjeta: Historial de Cambios -->
            <div class="card shadow mb-4">
                <div class="card-header text-white" style="background-color: #0c5737;">
                    <h5 class="mb-0" style="font-size: 1.05rem;">
                        <i class="fas fa-history me-1"></i> Historial de Cambios
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historial)): ?>
                        <div class="p-3 text-muted small text-center">
                            No hay registros en la bitácora de este ticket.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($historial as $evento): ?>
                                <div class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="small text-dark">
                                            <?php echo sanitize($evento['autor_nombre'] ?? 'Sistema'); ?>
                                        </strong>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                            <?php echo date('d/m/Y H:i', strtotime($evento['fecha_creacion'])); ?>
                                        </span>
                                    </div>
                                    <div class="text-secondary small mt-1" style="font-size: 0.85rem;">
                                        <i class="fas fa-angle-right text-muted me-1"></i>
                                        <?php echo sanitize($evento['descripcion']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Botón Volver -->
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-secondary w-100 mb-4">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>