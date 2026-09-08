<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Debes iniciar sesión para continuar.', 'Acceso requerido');
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$pageTitle = 'Cambiar contraseña';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.', 'Solicitud inválida');
        header('Location: ' . BASE_URL . '/auth/cambiar_password.php');
        exit;
    }

    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    // Validar requerimientos de seguridad con la función de backend
    $valida = validarPassword($password_nueva);

    if ($valida !== true) {
        setFlash('error', $valida, 'Contraseña inválida');
        header('Location: ' . BASE_URL . '/auth/cambiar_password.php');
        exit;
    } elseif ($password_nueva !== $password_confirmar) {
        setFlash('error', 'Las contraseñas no coinciden.', 'Revisa los datos');
        header('Location: ' . BASE_URL . '/auth/cambiar_password.php');
        exit;
    } else {
        $updated = updatePasswordAndClearFlag($_SESSION['usuario_id'], $password_nueva);

        if ($updated) {
            $_SESSION['debe_cambiar_password'] = 0;
            setFlash('success', 'Tu contraseña fue actualizada correctamente.', 'Contraseña actualizada');

            if (isAdmin()) {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/usuario/dashboard.php');
            }
            exit;
        }

        setFlash('error', 'No se pudo actualizar la contraseña. Inténtalo nuevamente.', 'Error');
        header('Location: ' . BASE_URL . '/auth/cambiar_password.php');
        exit;
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    .auth-wrapper {
        min-height: calc(100vh - 180px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 0;
    }

    .auth-card {
        border-radius: 1rem;
        overflow: hidden;
        border: 0;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .auth-card .card-body {
        padding: 2rem;
    }

    .btn-primary {
        background: #0b6b47 !important;
        border-color: #0b6b47 !important;
    }

    .btn-primary:hover {
        background: #085033 !important;
        border-color: #085033 !important;
    }

    /* Wrapper relativo para el ojito dentro del input */
    .password-wrapper {
        position: relative;
        width: 100%;
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
        color: #0b6b47;
    }

    /* Caja de Alerta Roja (Alto contraste) */
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
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Cambiar contraseña</h3>
                            <p class="text-muted mb-0">Debes actualizar tu contraseña para continuar.</p>
                        </div>

                        <form method="POST" novalidate>
                            <?php echo csrfField(); ?>
                            <!-- Campo Nueva Contraseña con Ojito Integrado -->
                            <div class="mb-3">
                                <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                                <div class="password-wrapper">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_nueva" 
                                           name="password_nueva" 
                                           required 
                                           autocomplete="new-password"
                                           placeholder="Nueva contraseña">
                                </div>

                                <!-- Alerta Dinámica -->
                                <div id="password-alert-box" class="password-alert-box">
                                    <strong><i class="fas fa-exclamation-circle me-1"></i> Requisitos de contraseña:</strong>
                                    <ul>
                                        <li>Mínimo 8 caracteres</li>
                                        <li>Al menos una letra</li>
                                        <li>Al menos un número</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Campo Confirmar Contraseña con Ojito Integrado -->
                            <div class="mb-4">
                                <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                                <div class="password-wrapper">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmar" 
                                           name="password_confirmar" 
                                           required 
                                           autocomplete="new-password"
                                           placeholder="Repite la contraseña">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-key me-2"></i>Guardar contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password_nueva');
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