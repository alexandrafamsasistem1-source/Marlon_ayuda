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
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    if (strlen($password_nueva) < 8) {
        setFlash('error', 'La nueva contraseña debe tener al menos 8 caracteres.', 'Contraseña inválida');
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
        background: #0b6b47;
        border-color: #0b6b47;
    }

    .btn-primary:hover {
        background: #085033;
        border-color: #085033;
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
                            <div class="mb-3">
                                <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="password_nueva" name="password_nueva" required minlength="8" autocomplete="new-password">
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" required minlength="8" autocomplete="new-password">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
