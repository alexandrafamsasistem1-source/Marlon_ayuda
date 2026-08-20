<?php
/**
 * Crear nuevo ticket
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
// 1. IMPORTAMOS EL HELPER DE CORREO AQUÍ
require_once __DIR__ . '/../includes/mail_helper.php'; 

// Verificar que esté logueado (usuario normal)
if (!isLoggedIn()) {
    include __DIR__ . '/../auth/login.php';
    exit();
}
if (isAdmin()) {
    include __DIR__ . '/../admin/dashboard.php';
    exit();
}

$pageTitle = 'Crear Ticket';
$usuario_id = getUserId();
$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = getUserById($usuario_id);
    $nombre = trim($usuario['nombre'] ?? '');
    $gmail = trim($usuario['email'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $area = trim($_POST['area'] ?? 'Administracion');
    $ubicacion = trim($_POST['ubicacion'] ?? '');

    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es requerido.';
    } elseif (empty($gmail)) {
        $error = 'El email es requerido.';
    } elseif (!isValidEmail($gmail)) {
        $error = 'El email no es válido.';
    } elseif (empty($asunto)) {
        $error = 'El asunto es requerido.';
    } elseif (strlen($asunto) < 5) {
        $error = 'El asunto debe tener al menos 5 caracteres.';
    } elseif (empty($descripcion)) {
        $error = 'La descripción es requerida.';
    } elseif (strlen($descripcion) < 10) {
        $error = 'La descripción debe tener al menos 10 caracteres.';
    } elseif (!in_array($ubicacion, ['Finca El Jardín', 'San Ignacio'])) {
        $error = 'Debes seleccionar una ubicación válida.';
    } elseif (!in_array($area, ['Administracion', 'Poscosecha'])) {
        $error = 'Debes seleccionar un área válida.';
    } else {
        // Crear ticket
        $result = createTicket($usuario_id, $asunto, $descripcion, $ubicacion, $area);

        if ($result['success']) {
            $usuarioActual = getUserById($usuario_id);
            $nombreTicket = trim($usuarioActual['nombre'] ?? $nombre);
            $emailTicket = trim($usuarioActual['email'] ?? $gmail);

            // Encolar notificación automática para todos los destinatarios válidos de tickets
            $mailEnviado = notificarNuevoTicket(
                $nombreTicket,
                $emailTicket,
                $asunto,
                $descripcion,
                $ubicacion,
                $area,
                $result['ticket_id'] ?? null
            );

            if (!$mailEnviado) {
                error_log('No se pudo enviar la notificación automática de nuevo ticket al superadmin.');
            }

            echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const toast = document.createElement("div");
                    toast.className = "ticket-success-toast";
                    toast.innerHTML = \'<div class="ticket-toast-icon"><i class="fas fa-check-circle"></i></div><div class="ticket-toast-content"><strong>¡Ticket creado exitosamente!</strong><span>Tu solicitud fue enviada correctamente.</span></div>\';
                    document.body.appendChild(toast);

                    requestAnimationFrame(function () {
                        toast.classList.add("show");
                    });

                    setTimeout(function () {
                        toast.classList.remove("show");
                        setTimeout(function () {
                            window.location.href = "' . BASE_URL . '/usuario/dashboard.php";
                        }, 250);
                    }, 2200);
                });
            </script>';
            exit();
        } else {
            $error = $result['error'] ?? 'Error al crear el ticket.';
        }
    }
}

// Obtener datos del usuario actual para prellenar
$usuario = getUserById($usuario_id);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-plus-circle"></i> Crear Nuevo Ticket de Ayuda
                </h4>
            </div>
            <div class="card-body">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user"></i> Nombre Completo:
                            </label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   value="<?php echo sanitize($usuario['nombre'] ?? ''); ?>"
                                   readonly required>
                            
                        </div>
                        <div class="col-md-6">
                            <label for="gmail" class="form-label">
                                <i class="fas fa-envelope"></i> Email:
                            </label>
                            <input type="email" class="form-control" id="gmail" name="gmail"
                                   value="<?php echo sanitize($usuario['email'] ?? ''); ?>"
                                   readonly required>
                            
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="asunto" class="form-label">
                            <i class="fas fa-heading"></i> Asunto:
                        </label>
                        <input type="text" class="form-control" id="asunto" name="asunto"
                               placeholder="Resumen breve del problema"
                               value="<?php echo isset($_POST['asunto']) ? sanitize($_POST['asunto']) : ''; ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="ubicacion" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Ubicación:
                        </label>
                        <select class="form-select" id="ubicacion" name="ubicacion" required>
                            <option value="">-- Selecciona una ubicación --</option>
                            <option value="Finca El Jardín" <?php echo (isset($_POST['ubicacion']) && $_POST['ubicacion'] === 'Finca El Jardín') ? 'selected' : ''; ?>>
                                Finca El Jardín
                            </option>
                            <option value="San Ignacio" <?php echo (isset($_POST['ubicacion']) && $_POST['ubicacion'] === 'San Ignacio') ? 'selected' : ''; ?>>
                                San Ignacio
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="area" class="form-label">
                            <i class="fas fa-layer-group"></i> Área:
                        </label>
                        <select class="form-select" id="area" name="area" required>
                            <option value="">-- Selecciona un área --</option>
                            <option value="Administracion" <?php echo (isset($_POST['area']) && $_POST['area'] === 'Administracion') ? 'selected' : ''; ?>>Administración</option>
                            <option value="Poscosecha" <?php echo (isset($_POST['area']) && $_POST['area'] === 'Poscosecha') ? 'selected' : ''; ?>>Poscosecha</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-file-alt"></i> Descripción del Problema:
                        </label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="6"
                                  placeholder="Describe detalladamente el problema que necesitas ayuda..."
                                  required><?php echo isset($_POST['descripcion']) ? sanitize($_POST['descripcion']) : ''; ?></textarea>
                        <small class="form-text text-muted">Mínimo 10 caracteres. Sé lo más específico posible.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Enviar Ticket
                        </button>
                        <a href="<?php echo BASE_URL; ?>/usuario/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>