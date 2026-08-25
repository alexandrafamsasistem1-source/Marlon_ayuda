<?php
/**
 * Página de Registro
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    // Validar contraseña con la función helper
    $validaPass = validarPassword($password);

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
    } elseif ($validaPass !== true) {
        $error = $validaPass;
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

/* Wrapper relativo para el ojito dentro del input */
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper .form-control {
    padding-right: 2.5rem;
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
                <div class="auth-card-hero text-center mb-4">
                    <img src="<?php echo BASE_URL; ?>/assets/img/logo_6.png" alt="Alex app support" style="max-width: 180px; width: 100%; height: auto;">
                </div>

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

                    <!-- Contraseña con Ojito Integrado -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña:</label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required
                                   placeholder="Crea una contraseña">
                            <i class="fas fa-eye toggle-password-icon" data-target="password"></i>
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

                    <!-- Confirmar Contraseña con Ojito Integrado -->
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Confirmar Contraseña:</label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirm" 
                                   name="password_confirm" 
                                   required
                                   placeholder="Repite la contraseña">
                            <i class="fas fa-eye toggle-password-icon" data-target="password_confirm"></i>
                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const alertBox = document.getElementById('password-alert-box');

    // Funcionalidad para los ojitos
    document.querySelectorAll('.toggle-password-icon').forEach(function (icon) {
        icon.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            if (targetInput) {
                const isPassword = targetInput.type === 'password';
                targetInput.type = isPassword ? 'text' : 'password';
                this.classList.toggle('fa-eye', !isPassword);
                this.classList.toggle('fa-eye-slash', isPassword);
            }
        });
    });

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