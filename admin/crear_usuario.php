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

// Manejar eliminación de usuario
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $rol = trim($_POST['rol'] ?? 'usuario');
    $editId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    $allowedRoles = ['usuario', 'admin'];
    if (isSuperAdmin()) {
        $allowedRoles[] = 'superadmin';
    }

    // Validaciones
    if (empty($nombre)) {
        $error = 'El nombre es requerido.';
    } elseif (empty($email)) {
        $error = 'El email es requerido.';
    } elseif (!isValidEmail($email)) {
        $error = 'El email no es válido.';
    } elseif ($editId === null && strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($editId === null && $password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($editId !== null && $password !== '' && strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($editId !== null && $password !== '' && $password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!in_array($rol, $allowedRoles, true)) {
        $error = 'Rol no válido.';
    } else {
        if ($editId !== null) {
            // Actualizar usuario existente
            $result = updateUser($editId, $nombre, $email, $rol, $password ?: null);
            if ($result['success']) {
                $success = $result['message'];
                header("Location: " . BASE_URL . "/admin/crear_usuario.php");
                exit;
            } else {
                $error = $result['error'];
            }
        } else {
            // Crear nuevo usuario
            if ($password !== $password_confirm) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                $result = createUser($nombre, $email, $password, $rol);
                if ($result['success']) {
                    $success = 'Usuario creado correctamente.';
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

<div class="card shadow mb-4 <?php echo $userId ? 'edit-user-card' : ''; ?>">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0">
            <i class="fas fa-user-plus"></i> <?php echo $userId ? 'Editar Usuario' : 'Crear Usuario'; ?>
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

        <form method="POST" class="row g-3">
            <?php if ($userId): ?>
                <input type="hidden" name="user_id" value="<?php echo (int)$userId; ?>">
            <?php endif; ?>
            
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" required 
                       value="<?php echo ($editingUser && isset($editingUser['nombre'])) ? sanitize($editingUser['nombre']) : (isset($_POST['nombre']) ? sanitize($_POST['nombre']) : ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required 
                       value="<?php echo ($editingUser && isset($editingUser['email'])) ? sanitize($editingUser['email']) : (isset($_POST['email']) ? sanitize($_POST['email']) : ''); ?>">
            </div>
            <div class="col-md-4">
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
            <div class="col-md-6">
                <label class="form-label">
                    Contraseña
                    <?php if ($userId): ?>
                        <small class="text-muted">(dejar en blanco para no cambiar)</small>
                    <?php endif; ?>
                </label>
                <input type="password" class="form-control" name="password" <?php echo !$userId ? 'required' : ''; ?>>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" name="password_confirm" <?php echo !$userId ? 'required' : ''; ?>>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $userId ? 'Actualizar usuario' : 'Crear usuario'; ?>
                </button>
                <?php if ($userId): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/crear_usuario.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-times"></i> Cancelar edición
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-secondary ms-2">
                    Volver al Panel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Usuarios -->
<div class="card shadow">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-list"></i> Gestionar Usuarios (<?php echo count($allUsers); ?>)
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($allUsers)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th><i class="fas fa-id-card"></i> ID</th>
                            <th><i class="fas fa-user"></i> Nombre</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-shield"></i> Rol</th>
                            <th><i class="fas fa-calendar"></i> Registro</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $user): ?>
                            <tr>
                                <td class="fw-bold"><?php echo (int)$user['id']; ?></td>
                                <td><?php echo sanitize($user['nombre']); ?></td>
                                <td><?php echo sanitize($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-role <?php 
                                        if ($user['rol'] === 'superadmin') echo 'badge-role-superadmin';
                                        elseif ($user['rol'] === 'admin') echo 'badge-role-admin';
                                        else echo 'badge-role-user';
                                    ?>">
                                        <?php echo ucfirst($user['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo BASE_URL; ?>/admin/crear_usuario.php?edit=<?php echo (int)$user['id']; ?>" 
                                           class="btn btn-primary" title="Editar usuario">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <?php if ($user['id'] !== getUserId()): ?>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="if(confirm('¿Seguro que quieres eliminar este usuario? Se perderá toda su información.')) window.location.href='<?php echo BASE_URL; ?>/admin/crear_usuario.php?delete=<?php echo (int)$user['id']; ?>'" 
                                                    title="Eliminar usuario">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
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
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay usuarios registrados.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
