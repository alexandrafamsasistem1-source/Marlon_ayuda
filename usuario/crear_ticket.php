<?php
/**
 * Crear nuevo ticket
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

$pageTitle = 'Crear Ticket';
$usuario_id = getUserId();
$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $gmail = trim($_POST['gmail'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
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
    } else {
        // Crear ticket
        $result = createTicket($usuario_id, $asunto, $descripcion, $ubicacion);

        if ($result['success']) {
            echo '<script>alert("¡Ticket creado exitosamente!"); window.location.href="' . BASE_URL . '/usuario/dashboard.php";</script>';
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

                <!-- Mensaje de error -->
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
                                   value="<?php echo isset($_POST['nombre']) ? sanitize($_POST['nombre']) : sanitize($usuario['nombre'] ?? ''); ?>"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label for="gmail" class="form-label">
                                <i class="fas fa-envelope"></i> Email:
                            </label>
                            <input type="email" class="form-control" id="gmail" name="gmail"
                                   value="<?php echo isset($_POST['gmail']) ? sanitize($_POST['gmail']) : sanitize($usuario['email'] ?? ''); ?>"
                                   required>
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
