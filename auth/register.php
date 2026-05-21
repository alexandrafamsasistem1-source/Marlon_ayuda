<?php
/**
 * Página de Registro
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Si ya está logueado, redirigir
requireLogout();

$pageTitle = 'Registro';

// Variables
$error = '';
$success = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es requerido.';
    } elseif (strlen($nombre) < 3) {
        $error = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (empty($email)) {
        $error = 'El email es requerido.';
    } elseif (!isValidEmail($email)) {
        $error = 'El email no es válido.';
    } elseif (empty($password)) {
        $error = 'La contraseña es requerida.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Intentar crear usuario
        $result = createUser($nombre, $email, $password, 'usuario');

        if ($result['success']) {
            $success = 'Registro exitoso. Puedes iniciar sesión ahora.';
            // Limpiar formulario
            $_POST = [];
        } else {
            $error = $result['error'] ?? 'Error al registrar usuario.';
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Inline critical auth styles (override cached CSS) -->
<style>
:root{--primary:#0b6b47;--primary-dark:#085033;--accent:#c9b07a}
.navbar{background:#fff !important;box-shadow:0 2px 6px rgba(0,0,0,0.06)!important}
.navbar-brand{background:var(--primary);color:#fff !important;padding:.45rem .9rem;border-radius:.4rem;display:inline-block}
.card{border-radius:.6rem}
.card .card-body{padding:2rem}
.card-title .fa-user-plus{color:var(--primary)}
.btn-primary{background:var(--primary)!important;border-color:var(--primary)!important}
.btn-primary:hover{background:var(--primary-dark)!important;border-color:var(--primary-dark)!important}
body{background:#f7f8f6}
footer{background:#fff;color:#666}
</style>

<div class="auth-wrapper">
<div class="row justify-content-center mb-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <h2 class="card-title text-center mb-1">
                    <i class="fas fa-user-plus text-primary"></i>
                </h2>
                <h3 class="card-title text-center mb-4">Crear Cuenta</h3>

                <!-- Mensaje de éxito -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo sanitize($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Mensaje de error -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre"
                               value="<?php echo isset($_POST['nombre']) ? sanitize($_POST['nombre']) : ''; ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Confirmar Contraseña:</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </button>
                </form>

                <div class="text-center">
                    <p class="text-muted mb-0">
                        ¿Ya tienes cuenta?
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-decoration-none">
                            Inicia sesión aquí
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</div>
