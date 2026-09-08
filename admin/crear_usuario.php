<?php
/**
 * Página para Crear, Editar y Gestionar Usuarios
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
$editingUser = null;
$userId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

// Obtener usuario a editar si existe
if ($userId > 0) {
    $editingUser = getUserById($userId);
    if (!$editingUser) {
        $error = 'Usuario no encontrado.';
        $editingUser = null;
        $userId = null;
    }
}

// Manejar eliminación de usuario mediante POST protegido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.', 'Solicitud inválida');
        header('Location: ' . BASE_URL . '/admin/crear_usuario.php');
        exit;
    }

    $deleteId = (int)($_POST['user_id'] ?? 0);
    if ($deleteId > 0 && $deleteId !== getUserId()) {  // No permitir eliminar su propio usuario
        $result = deleteUser($deleteId);
        if ($result['success']) {
            $success = $result['message'];
            header("Location: " . BASE_URL . "/admin/crear_usuario.php");
            exit;
        } else {
            $error = $result['error'];
        }
    } elseif ($deleteId === getUserId()) {
        $error = 'No puedes eliminar tu propia cuenta.';
    }
}

// Manejar POST (crear o actualizar usuario)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.', 'Solicitud inválida');
        header('Location: ' . BASE_URL . '/admin/crear_usuario.php');
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $rol = trim($_POST['rol'] ?? 'usuario');
    $area = trim($_POST['area'] ?? '');
    $editId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    $allowedRoles = ['usuario', 'admin'];
    if (isSuperAdmin()) {
        $allowedRoles[] = 'superadmin';
    }

    // Lista de áreas permitidas actualizada
    $allowedAreas = ['Administracion', 'Produccion', 'Juridica', 'Cartera', 'Gestion Humana'];

    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es requerido.';
    } elseif (empty($email)) {
        $error = 'El email es requerido.';
    } elseif (!isValidEmail($email)) {
        $error = 'El email no es válido.';
    } elseif (!in_array($rol, $allowedRoles, true)) {
        $error = 'Rol no válido.';
    } elseif (empty($area) || !in_array($area, $allowedAreas, true)) {
        $error = 'Debes seleccionar un área válida.';
    } else {
        // Validación de contraseña para creación
        if ($editId === null) {
            $validaPass = validarPassword($password);
            if (empty($password)) {
                $error = 'La contraseña es requerida.';
            } elseif ($validaPass !== true) {
                $error = $validaPass;
            } elseif ($password !== $password_confirm) {
                $error = 'Las contraseñas no coinciden.';
            }
        } 
        // Validación de contraseña para edición (si se ingresó una nueva)
        elseif ($editId !== null && $password !== '') {
            $validaPass = validarPassword($password);
            if ($validaPass !== true) {
                $error = $validaPass;
            } elseif ($password !== $password_confirm) {
                $error = 'Las contraseñas no coinciden.';
            }
        }

        // Si no hay errores, proceder a guardar
        if (empty($error)) {
            if ($editId !== null) {
                // Actualizar usuario existente (SE CORRIGIÓ EL ORDEN DE $area Y $password)
                $result = updateUser($editId, $nombre, $email, $rol, $area, $password ?: null);
                if ($result['success']) {
                    setFlash('success', $result['message']);
                    header("Location: " . BASE_URL . "/admin/crear_usuario.php");
                    exit;
                } else {
                    $error = $result['error'];
                }
            } else {
                // Crear nuevo usuario
                $result = createUser($nombre, $email, $password, $rol, $area);
                if ($result['success']) {
                    $success = 'Usuario creado correctamente con su área asignada.';
                    // Limpiar formulario
                    $_POST = [];
                } else {
                    $error = $result['error'] ?? 'No se pudo crear el usuario.';
                }
            }
        }
    }
}

$pageTitle = $userId ? 'Editar Usuario' : 'Crear Usuario';
$allUsers = getAllUsers(100, 0);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Estilos para contraseña y tabla con Scroll Horizontal y Vertical -->
<style>
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
    color: #0b6b47;
}

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

/* Contenedor con Scroll Horizontal y Vertical */
.table-responsive-scroll {
    max-height: 450px;
    overflow-x: auto !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
}

/* Encabezado fijo al hacer scroll vertical */
.table-responsive-scroll table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background-color: #f8f9fa;
    box-shadow: inset 0 -1px 0 #dee2e6;
}

.user-form-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

.user-form-actions .btn {
    margin-left: 0 !important;
}

@media (max-width: 576px) {
    .user-form-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 0.65rem;
    }

    .user-form-actions .btn {
        width: 100%;
        margin: 0 !important;
    }
}
</style>

<div class="card shadow mb-4 <?php echo $userId ? 'edit-user-card' : ''; ?>">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0">
            <i class="fas fa-user-plus me-1"></i> <?php echo $userId ? 'Editar Usuario' : 'Crear Usuario'; ?>
            <?php if ($userId): ?>
                <span class="badge bg-light text-success ms-2 align-middle">Modo edición</span>
            <?php endif; ?>
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

        <form method="POST" class="row g-3" novalidate>
            <?php echo csrfField(); ?>
            <?php if ($userId): ?>
                <input type="hidden" name="user_id" value="<?php echo (int)$userId; ?>">
            <?php endif; ?>
            
            <div class="col-md-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" required 
                       value="<?php echo ($editingUser && isset($editingUser['nombre'])) ? sanitize($editingUser['nombre']) : (isset($_POST['nombre']) ? sanitize($_POST['nombre']) : ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required 
                       value="<?php echo ($editingUser && isset($editingUser['email'])) ? sanitize($editingUser['email']) : (isset($_POST['email']) ? sanitize($_POST['email']) : ''); ?>">
            </div>
            
            <!-- Campo Selección de Área con las nuevas opciones -->
            <div class="col-md-3">
                <label class="form-label">Área</label>
                <select class="form-select" name="area" required>
                    <option value="">-- Seleccionar Área --</option>
                    <?php 
                        $currentArea = ($editingUser && isset($editingUser['area'])) ? $editingUser['area'] : (isset($_POST['area']) ? $_POST['area'] : '');
                    ?>
                    <option value="Administracion" <?php echo ($currentArea === 'Administracion') ? 'selected' : ''; ?>>Administración</option>
                    <option value="Produccion" <?php echo ($currentArea === 'Produccion') ? 'selected' : ''; ?>>Producción</option>
                    <option value="Juridica" <?php echo ($currentArea === 'Juridica') ? 'selected' : ''; ?>>Jurídica</option>
                    <option value="Cartera" <?php echo ($currentArea === 'Cartera') ? 'selected' : ''; ?>>Cartera</option>
                    <option value="Gestion Humana" <?php echo ($currentArea === 'Gestion Humana') ? 'selected' : ''; ?>>Gestión Humana</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Rol</label>
                <select class="form-select" name="rol" required>
                    <option value="usuario" <?php 
                        $currentRol = ($editingUser && isset($editingUser['rol'])) ? $editingUser['rol'] : (isset($_POST['rol']) ? $_POST['rol'] : 'usuario');
                        echo ($currentRol === 'usuario') ? 'selected' : ''; 
                    ?>>Usuario</option>
                    <option value="admin" <?php echo ($currentRol === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <?php if (isSuperAdmin()): ?>
                        <option value="superadmin" <?php echo ($currentRol === 'superadmin') ? 'selected' : ''; ?>>Superadmin</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Campo Contraseña -->
            <div class="col-md-6">
                <label class="form-label">
                    Contraseña
                    <?php if ($userId): ?>
                        <small class="text-muted">(dejar en blanco para no cambiar)</small>
                    <?php endif; ?>
                </label>
                <div class="password-wrapper">
                    <input type="password" class="form-control" id="password" name="password" <?php echo !$userId ? 'required' : ''; ?>>
                    <button type="button" class="toggle-password-btn" data-target="password" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <!-- Caja de Alerta de Requisitos -->
                <div id="password-alert-box" class="password-alert-box">
                    <strong><i class="fas fa-exclamation-circle me-1"></i> Requisitos de contraseña:</strong>
                    <ul>
                        <li>Mínimo 8 caracteres</li>
                        <li>Al menos una letra</li>
                        <li>Al menos un número</li>
                    </ul>
                </div>
            </div>

            <!-- Campo Confirmar Contraseña -->
            <div class="col-md-6">
                <label class="form-label">Confirmar contraseña</label>
                <div class="password-wrapper">
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" <?php echo !$userId ? 'required' : ''; ?>>
                    <button type="button" class="toggle-password-btn" data-target="password_confirm" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="col-12 user-form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $userId ? 'Actualizar usuario' : 'Crear usuario'; ?>
                </button>
                <?php if ($userId): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/crear_usuario.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar edición
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-secondary">
                    Volver al Panel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Usuarios -->
<div class="card shadow card-tabla-usuarios">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-list-ul me-2"></i>Gestionar Usuarios (<?php echo count($allUsers); ?>)
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($allUsers)): ?>
            <!-- Contenedor con Scrollbar personalizada -->
            <div class="table-responsive-scroll">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-nowrap">
                            <th scope="col" style="width: 80px;"><i class="fas fa-id-card me-1"></i> ID</th>
                            <th scope="col"><i class="fas fa-user me-1"></i> Nombre</th>
                            <th scope="col"><i class="fas fa-envelope me-1"></i> Email</th>
                            <th scope="col"><i class="fas fa-building me-1"></i> Área</th>
                            <th scope="col"><i class="fas fa-shield-alt me-1"></i> Rol</th>
                            <th scope="col"><i class="fas fa-calendar-alt me-1"></i> Registro</th>
                            <th scope="col" class="text-center" style="width: 170px;"><i class="fas fa-cog me-1"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $user): ?>
                            <tr>
                                <td class="fw-bold"><?php echo (int)$user['id']; ?></td>
                                <td class="text-nowrap"><?php echo sanitize($user['nombre']); ?></td>
                                <td class="text-nowrap"><?php echo sanitize($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php 
                                            $areaDisplay = [
                                                'Administracion' => 'Administración',
                                                'Produccion' => 'Producción',
                                                'Juridica' => 'Jurídica',
                                                'Cartera' => 'Cartera',
                                                'Gestion Humana' => 'Gestión Humana'
                                            ];
                                            echo sanitize($areaDisplay[$user['area']] ?? ($user['area'] ?? 'Sin área')); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-role <?php 
                                        if ($user['rol'] === 'superadmin') echo 'badge-role-superadmin';
                                        elseif ($user['rol'] === 'admin') echo 'badge-role-admin';
                                        else echo 'badge-role-user';
                                    ?>">
                                        <?php echo ucfirst($user['rol']); ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?>
                                    </small>
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>/admin/crear_usuario.php?edit=<?php echo (int)$user['id']; ?>" 
                                           class="btn btn-primary" title="Editar usuario">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if ($user['id'] !== getUserId()): ?>
                                            <form method="POST" class="d-inline">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    data-swal-confirm="¿Seguro que quieres eliminar este usuario? Se perderá toda su información."
                                                    data-swal-title="Eliminar usuario"
                                                    data-swal-confirm-text="Sí, eliminar"
                                                    title="Eliminar usuario">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary" disabled title="No puedes eliminar tu propia cuenta">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-3">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> No hay usuarios registrados.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const alertBox = document.getElementById('password-alert-box');

    // Validación y alerta de contraseña
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