<?php
/**
 * Página de Login
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Si ya está logueado, mostrar dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        include __DIR__ . '/../admin/dashboard.php';
    } else {
        include __DIR__ . '/../usuario/dashboard.php';
    }
    exit();
}

$pageTitle = 'Login';

// Variable para mensajes
$error = '';
$success = '';

// Si hay parámetro de logout
if (isset($_GET['logout'])) {
    $success = 'Sesión cerrada correctamente.';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas
    if (empty($email)) {
        $error = 'El email es requerido.';
    } elseif (empty($password)) {
        $error = 'La contraseña es requerida.';
    } else {
        // Buscar usuario por email
        $usuario = getUserByEmail($email);

        if (!$usuario) {
            $error = 'Email o contraseña incorrectos.';
        } elseif (!verifyPassword($password, $usuario['password'])) {
            $error = 'Email o contraseña incorrectos.';
        } else {
            // Verificar si el usuario está activo
            if (!$usuario['activo']) {
                $error = 'Usuario desactivado. Contacta al administrador.';
            } else {
                // Login exitoso - guardar sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];

                // Redirigir al dashboard correspondiente
                if ($usuario['rol'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: /usuario/dashboard.php');
                }
                exit();
            }
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row justify-content-center mb-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <h2 class="card-title text-center mb-1">
                    <i class="fas fa-ticket-alt text-primary"></i>
                </h2>
                <h3 class="card-title text-center mb-4">Iniciar Sesión</h3>

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
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" 
                               required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>

                <div class="text-center">
                    <p class="text-muted mb-0">
                        ¿No tienes cuenta? 
                        <a href="/auth/register.php" class="text-decoration-none">
                            Registrarse aquí
                        </a>
                    </p>
                </div>

                <!-- Credenciales de prueba -->
                <div class="alert alert-info mt-4 small">
                    <strong>Pruebas:</strong><br>
                    <strong>Admin:</strong> admin@tickets.local / admin123<br>
                    <strong>Usuario:</strong> usuario@tickets.local / usuario123
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
