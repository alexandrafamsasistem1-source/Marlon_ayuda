<?php
/**
 * Dashboard del Usuario
 * Muestra lista de tickets del usuario actual y opción crear nuevo
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que esté logueado (usuario normal)
if (!isLoggedIn()) {
    include __DIR__ . '/../auth/login.php';
    exit();
}
if (isAdmin()) {
    include __DIR__ . '/../admin/dashboard.php';
    exit();
}

$pageTitle = 'Mis Tickets';
$usuario_id = getUserId();

// Manejo de eliminación de ticket (desde este dashboard)
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ticket') {
    $ticketToDelete = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    if ($ticketToDelete > 0) {
        // Solo admin o propietario
        if (isAdmin() || isTicketOwnedByUser($ticketToDelete, $usuario_id)) {
            if (deleteTicket($ticketToDelete)) {
                $success = 'Ticket eliminado correctamente.';
            } else {
                $error = 'No se pudo eliminar el ticket. Intenta de nuevo.';
            }
        } else {
            $error = 'No tienes permiso para eliminar este ticket.';
        }
    } else {
        $error = 'ID de ticket inválido.';
    }
}

// Obtener tickets del usuario
$tickets = getUserTickets($usuario_id, 100, 0);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="hero-banner mb-4">
    <div class="meta">
        <h2>
            <i class="fas fa-list me-2"></i> Mis Tickets de Ayuda
        </h2>
        <div class="lead">Gestiona tus solicitudes</div>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/usuario/crear_ticket.php" class="btn btn-banner">
            <i class="fas fa-plus-circle"></i> Crear Nuevo Ticket
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<?php if (empty($tickets)): ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle"></i> 
        <strong>No tienes tickets aún.</strong>
        <a href="<?php echo BASE_URL; ?>/usuario/crear_ticket.php" class="alert-link">Crea uno ahora</a> para reportar tu problema.
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-clean align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">ID</th>
                                    <th>Usuario / Email</th>
                                    <th>Asunto</th>
                                    <th style="width:140px">Ubicación</th>
                                    <th style="width:120px">Área</th>
                                    <th style="width:120px">Estado</th>
                                    <th style="width:120px">Fecha</th>
                                    <th style="width:120px" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">#<?php echo $ticket['id']; ?></span></td>
                                        <td class="user-cell">
                                            <strong><?php echo sanitize($ticket['usuario_nombre'] ?? getUserName()); ?></strong>
                                            <small><?php echo sanitize($ticket['usuario_email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo sanitize($ticket['asunto']); ?></strong>
                                            <div class="text-muted small mt-1"><?php echo sanitize(mb_substr($ticket['descripcion'] ?? '', 0, 100)); ?><?php echo (mb_strlen($ticket['descripcion'] ?? '')>100)?'...':''; ?></div>
                                        </td>
                                        <td><i class="fas fa-map-marker-alt text-danger"></i> <?php echo sanitize($ticket['ubicacion']); ?></td>
                                        <td>
                                            <?php $areaVal = $ticket['area'] ?? null; ?>
                                            <?php if ($areaVal): ?>
                                                <?php $areaClass = $areaVal === 'Poscosecha' ? 'badge bg-success' : 'badge bg-primary'; ?>
                                                <span class="<?php echo $areaClass; ?>"><?php echo sanitize($areaVal === 'Poscosecha' ? 'Poscosecha' : 'Administración'); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $estadoClass = 're-state-chip re-state-chip--default';
                                            switch ($ticket['estado']) {
                                                case 'Nuevo': $estadoClass = 're-state-chip re-state-chip--nuevo'; break;
                                                case 'En proceso': $estadoClass = 're-state-chip re-state-chip--proceso'; break;
                                                case 'Resuelto': $estadoClass = 're-state-chip re-state-chip--resuelto'; break;
                                                case 'Cerrado': $estadoClass = 're-state-chip re-state-chip--cerrado'; break;
                                            }
                                            ?>
                                            <span class="<?php echo $estadoClass; ?>"><?php echo $ticket['estado']; ?></span>
                                        </td>
                                        <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></small></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?php echo BASE_URL; ?>/usuario/ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-success action-btn" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form method="POST" onsubmit="return confirm('¿Eliminar este ticket? Esta acción no se puede deshacer.');" style="display:inline-block">
                                                    <input type="hidden" name="action" value="delete_ticket">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                    <button type="submit" class="btn btn-danger action-btn" title="Eliminar">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
