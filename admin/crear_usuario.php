<?php
/**
 * Página separada para Crear Usuario
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que sea admin
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $rol = trim($_POST['rol'] ?? 'usuario');

    $allowedRoles = ['usuario', 'admin'];
    if (isSuperAdmin()) {
        $allowedRoles[] = 'superadmin';
    }

    if (empty($nombre)) {
        $error = 'El nombre es requerido.';
    } elseif (empty($email)) {
        $error = 'El email es requerido.';
    } elseif (!isValidEmail($email)) {
        $error = 'El email no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!in_array($rol, $allowedRoles, true)) {
        $error = 'Rol no válido.';
    } else {
        $result = createUser($nombre, $email, $password, $rol);
        if ($result['success']) {
            $success = 'Usuario creado correctamente.';
        } else {
            $error = $result['error'] ?? 'No se pudo crear el usuario.';
        }
    }
}

$pageTitle = 'Crear Usuario';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0">
            <i class="fas fa-user-plus"></i> Crear Usuario
        </h4>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" required value="<?php echo isset($_POST['nombre']) ? sanitize($_POST['nombre']) : ''; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Rol</label>
                <select class="form-select" name="rol" required>
                    <option value="usuario" <?php echo (($_POST['rol'] ?? 'usuario') === 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                    <option value="admin" <?php echo (($_POST['rol'] ?? 'usuario') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <?php if (isSuperAdmin()): ?>
                        <option value="superadmin" <?php echo (($_POST['rol'] ?? 'usuario') === 'superadmin') ? 'selected' : ''; ?>>Superadmin</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" name="password_confirm" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Crear usuario
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-secondary ms-2">Volver al Panel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
