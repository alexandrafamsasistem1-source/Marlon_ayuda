<?php
/**
 * Dashboard del Usuario
 * Muestra lista de tickets del usuario actual y opción crear nuevo
 */

session_start();
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

// Obtener tickets del usuario
$tickets = getUserTickets($usuario_id, 100, 0);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-list"></i> Mis Tickets de Ayuda
    </h2>
    <a href="<?php echo BASE_URL; ?>/usuario/crear_ticket.php" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Crear Nuevo Ticket
    </a>
</div>

<?php if (empty($tickets)): ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle"></i> 
        <strong>No tienes tickets aún.</strong>
        <a href="<?php echo BASE_URL; ?>/usuario/crear_ticket.php" class="alert-link">Crea uno ahora</a> para reportar tu problema.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Asunto</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
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
                            <strong><?php echo sanitize($ticket['asunto']); ?></strong>
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
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?>
                            </small>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/usuario/ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
