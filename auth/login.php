<?php
/**
 * Página de Login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Si ya está logueado, respetar la obligación de cambiar contraseña
if (isLoggedIn()) {
    if ((int)($_SESSION['debe_cambiar_password'] ?? 0) === 1) {
        setFlash('warning', 'Debes cambiar tu contraseña para continuar.', 'Acción requerida');
        header('Location: ' . BASE_URL . '/auth/cambiar_password.php');
        exit();
    }

    if (isAdmin()) {
        include __DIR__ . '/../admin/dashboard.php';
    } else {
        include __DIR__ . '/../usuario/dashboard.php';
    }
    exit();
}

$pageTitle = 'Login';

// Si hay parámetro de logout
if (isset($_GET['logout'])) {
    setFlash('success', 'Sesión cerrada correctamente.', 'Hasta luego');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'La sesión del formulario expiró. Inténtalo nuevamente.', 'Solicitud inválida');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }

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
            setFlash('error', 'Email o contraseña incorrectos.', 'No fue posible iniciar sesión');
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit();
        } elseif (!verifyPassword($password, $usuario['password'])) {
            setFlash('error', 'Email o contraseña incorrectos.', 'No fue posible iniciar sesión');
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit();
        } else {
            // Verificar si el usuario está activo
            if (!$usuario['activo']) {
                setFlash('error', 'Usuario desactivado. Contacta al administrador.', 'Acceso denegado');
                header('Location: ' . BASE_URL . '/auth/login.php');
                exit();
            } else {
                // Login exitoso - guardar sesión
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['debe_cambiar_password'] = (int)$usuario['debe_cambiar_password'];

                if ($_SESSION['debe_cambiar_password'] === 1) {
                    setFlash('warning', 'Debes cambiar tu contraseña antes de continuar.', 'Acción requerida');
                    header('Location: ' . BASE_URL . '/auth/cambiar_password.php');
                    exit;
                }

                setFlash('success', 'Has iniciado sesión correctamente.', 'Bienvenido');

                // Redirigir al dashboard correspondiente
                if (in_array($usuario['rol'], ['admin', 'superadmin'], true)) {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . '/usuario/dashboard.php');
                }
                exit();
            }
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
.card-title .fa-ticket-alt{color:var(--primary)}
.btn-primary{background:var(--primary)!important;border-color:var(--primary)!important}
.btn-primary:hover{background:var(--primary-dark)!important;border-color:var(--primary-dark)!important}
body{background:#f7f8f6}
footer{background:#fff;color:#666}
.auth-card-hero{margin-bottom:1.25rem;padding:0.75rem 0}
.auth-card-hero img{display:block;max-width:360px;width:100%;height:auto;margin:0 auto;object-fit:contain}

/* Wrapper relativo para el ojito dentro del input */
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper .form-control {
    padding-right: 2.75rem;
}

.toggle-password-btn {
    position: absolute;
    top: 50%;
    right: 0.65rem;
    transform: translateY(-50%);
    border: 0;
    padding: 0;
    background: transparent;
    color: #6c757d;
    line-height: 1;
    cursor: pointer;
}

.toggle-password-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
    font-size: 1.1rem;
    z-index: 10;
    transition: color 0.2s ease;
}

.toggle-password-icon:hover {
    color: var(--primary);
}

/* Estilo de la caja de alerta roja en alto contraste */
.password-alert-box {
    display: none;
    background-color: #fff0f1;
    border: 1px solid #f8d7da;
    border-left: 4px solid #dc3545;
    border-radius: 0.375rem;
    padding: 0.65rem 0.85rem;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #842029;
    box-shadow: 0 2px 5px rgba(220, 53, 69, 0.08);
}

.password-alert-box ul {
    margin-bottom: 0;
    padding-left: 1.2rem;
    margin-top: 0.25rem;
}
</style>

<div class="auth-wrapper">
<div class="row justify-content-center mb-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="auth-card-hero text-center">
                    <img src="<?php echo BASE_URL; ?>/assets/img/logo_6.png" alt="Alex app support">
                </div>
                <h3 class="card-title text-center mb-4">Iniciar Sesión</h3>

                <form method="POST" novalidate>
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" 
                               required>
                    </div>

                    <!-- Campo de Contraseña con Ojito Flotante Integrado -->
                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña:</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button type="button" class="toggle-password-btn" data-target="password" aria-label="Mostrar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <!-- Caja de Alerta Roja -->
                        <div id="password-alert-box" class="password-alert-box">
                            <strong><i class="fas fa-exclamation-circle me-1"></i> Requisitos de contraseña:</strong>
                            <ul>
                                <li>Mínimo 8 caracteres</li>
                                <li>Al menos una letra</li>
                                <li>Al menos un número</li>
                            </ul>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>

                <div class="text-center">
                    <p class="text-muted mb-0">
                        ¿No tienes cuenta? 
                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-decoration-none">
                            Registrarse aquí
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const alertBox = document.getElementById('password-alert-box');

    // Validacion y alerta roja
    if (passwordInput && alertBox) {
        passwordInput.addEventListener('input', function () {
            const val = this.value;
            const esValido = val.length >= 8 && /[a-zA-Z]/.test(val) && /[0-9]/.test(val);

            if (val.length === 0 || esValido) {
                alertBox.style.display = 'none';
            } else {
                alertBox.style.display = 'block';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</div>