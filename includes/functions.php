<?php
/**
 * Funciones Reutilizables del Sistema
 */

// Obtener conexión a base de datos globalmente
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        // Si ya existe una conexión en el scope global (por includes previos), úsala
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $pdo = $GLOBALS['pdo'];
        } else {
            // Incluir el archivo de configuración y usar el PDO retornado
            $ret = require __DIR__ . '/../config/database.php';
            if ($ret instanceof PDO) {
                $pdo = $ret;
                // Guardar en global para futuros includes
                $GLOBALS['pdo'] = $pdo;
            } else {
                // Si no se obtuvo un PDO, lanzar excepción clara
                throw new RuntimeException('No se pudo obtener la conexión PDO desde config/database.php');
            }
        }
    }
    return $pdo;
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verificar si el usuario logueado es admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Redirigir a login si no está autenticado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Redirigir a dashboard si ya está logueado
 */
function requireLogout() {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Redirigir a dashboard si no es admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/usuario/dashboard.php');
        exit();
    }
}

/**
 * Obtener ID del usuario logueado
 */
function getUserId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtener rol del usuario logueado
 */
function getUserRole() {
    return $_SESSION['rol'] ?? null;
}

/**
 * Obtener nombre del usuario logueado
 */
function getUserName() {
    return $_SESSION['nombre'] ?? 'Usuario';
}

/**
 * Hash de contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Limpiar datos para seguridad XSS
 */
function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Obtener usuario por ID
 */
function getUserById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, nombre, email, rol, fecha_registro FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Obtener usuario por email
 */
function getUserByEmail($email) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Crear nuevo usuario
 */
function createUser($nombre, $email, $password, $rol = 'usuario') {
    $pdo = getDB();

    if (getUserByEmail($email)) {
        return ['success' => false, 'error' => 'El email ya existe'];
    }

    $passwordHashed = hashPassword($password);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)');

    if ($stmt->execute([$nombre, $email, $passwordHashed, $rol])) {
        return ['success' => true, 'usuario_id' => $pdo->lastInsertId()];
    } else {
        return ['success' => false, 'error' => 'Error al crear usuario'];
    }
}

/**
 * Obtener todos los tickets (para admin)
 */
function getAllTickets($limit = 50, $offset = 0) {
    $pdo = getDB();
    // Incluir columna 'urgencia' si existe
    $selectUrgencia = '';
    try {
        $hasUrg = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'urgencia'")->fetchColumn();
        if ($hasUrg) $selectUrgencia = ', t.urgencia';
    } catch (Exception $e) {
        $selectUrgencia = '';
    }

    $sql = "SELECT 
            t.id,
            t.asunto,
            t.asignado_a,
            t.estado,
            t.ubicacion,
            t.fecha_creacion" . $selectUrgencia . ",
            u.nombre as usuario_nombre,
            u.email as usuario_email,
            a.nombre as asignado_nombre
        FROM tickets t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN usuarios a ON t.asignado_a = a.id
        ORDER BY t.fecha_creacion DESC
        LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Obtener tickets del usuario actual
 */

    function getUserTickets($usuario_id, $limit = 50, $offset = 0) {
        $pdo = getDB();
        // Comprobar si existe la columna 'area' y seleccionar si está disponible
        $hasArea = false;
        try {
            $hasArea = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'area'")->fetchColumn();
        } catch (Exception $e) {
            $hasArea = false;
        }
        $selectArea = $hasArea ? ', area' : '';
        $sql = "SELECT id, asunto, estado, ubicacion, fecha_creacion$selectArea FROM tickets WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $limit, $offset]);
        return $stmt->fetchAll();
    }

/**
 * Obtener detalle de un ticket
 */
function getTicketById($ticket_id) {
    $pdo = getDB();
    // Seleccionar 'area' si existe en la tabla
    $hasArea = false;
    try {
        $hasArea = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'area'")->fetchColumn();
    } catch (Exception $e) {
        $hasArea = false;
    }
    $selectArea = $hasArea ? ', t.area' : '';
    $stmt = $pdo->prepare(
        "SELECT t.*$selectArea, u.nombre as usuario_nombre, u.email as usuario_email, a.nombre as asignado_nombre
        FROM tickets t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN usuarios a ON t.asignado_a = a.id
        WHERE t.id = ?"
    );
    $stmt->execute([$ticket_id]);
    return $stmt->fetch();
}

/**
 * Crear nuevo ticket
 */
function createTicket($usuario_id, $asunto, $descripcion, $ubicacion, $area = null) {
    $pdo = getDB();
    // Soporta opcionalmente el campo 'area' si la columna existe
    $params = [$usuario_id, $asunto, $descripcion, $ubicacion];
    $hasArea = false;
    try {
        $hasArea = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'area'")->fetchColumn();
    } catch (Exception $e) {
        $hasArea = false;
    }
    if ($hasArea) {
        $area = func_num_args() >= 5 ? func_get_arg(4) : null;
        $sql = 'INSERT INTO tickets (usuario_id, asunto, descripcion, ubicacion, area) VALUES (?, ?, ?, ?, ?)';
        $params[] = $area;
    } else {
        $sql = 'INSERT INTO tickets (usuario_id, asunto, descripcion, ubicacion) VALUES (?, ?, ?, ?)';
    }
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        return ['success' => true, 'ticket_id' => $pdo->lastInsertId()];
    } else {
        return ['success' => false, 'error' => 'Error al crear ticket'];
    }
}

/**
 * Actualizar estado de ticket
 */
function updateTicketStatus($ticket_id, $estado, $asignado_a = null) {
    $pdo = getDB();

    if ($asignado_a !== null) {
        $stmt = $pdo->prepare('
            UPDATE tickets
            SET estado = ?, asignado_a = ?, fecha_ultima_actualizacion = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$estado, $asignado_a, $ticket_id]);
    } else {
        $stmt = $pdo->prepare('
            UPDATE tickets
            SET estado = ?, fecha_ultima_actualizacion = NOW()
            WHERE id = ?
        ');
        return $stmt->execute([$estado, $ticket_id]);
    }
}

/**
 * Agregar respuesta a ticket
 */
function addResponseToTicket($ticket_id, $usuario_id, $mensaje) {
    $pdo = getDB();
    $stmt = $pdo->prepare('
        INSERT INTO respuestas_ticket (ticket_id, usuario_id, mensaje)
        VALUES (?, ?, ?)
    ');

    if ($stmt->execute([$ticket_id, $usuario_id, $mensaje])) {
        // Actualizar fecha de última actualización del ticket
        $pdo->prepare('UPDATE tickets SET fecha_ultima_actualizacion = NOW() WHERE id = ?')->execute([$ticket_id]);
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Error al agregar respuesta'];
    }
}

/**
 * Obtener respuestas de un ticket
 */
function getTicketResponses($ticket_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare('
        SELECT 
            r.id,
            r.mensaje,
            r.fecha_creacion,
            u.nombre as usuario_nombre,
            u.rol
        FROM respuestas_ticket r
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.ticket_id = ?
        ORDER BY r.fecha_creacion ASC
    ');
    $stmt->execute([$ticket_id]);
    return $stmt->fetchAll();
}

/**
 * Contar tickets totales
 */
function countTotalTickets() {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM tickets');
    return $stmt->fetch()['total'] ?? 0;
}

/**
 * Contar tickets por estado
 */
function countTicketsByStatus() {
    $pdo = getDB();
    $stmt = $pdo->query('
        SELECT estado, COUNT(*) as cantidad
        FROM tickets
        GROUP BY estado
    ');
    return $stmt->fetchAll();
}

/**
 * Contar tickets por ubicación
 */
function countTicketsByLocation() {
    $pdo = getDB();
    $stmt = $pdo->query('
        SELECT ubicacion, COUNT(*) as cantidad
        FROM tickets
        GROUP BY ubicacion
    ');
    return $stmt->fetchAll();
}

/**
 * Obtener lista de todos los admins
 */
function getAllAdmins() {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT id, nombre, email FROM usuarios WHERE rol = "admin" AND activo = 1');
    return $stmt->fetchAll();
}

/**
 * Verificar si un ticket pertenece al usuario
 */
function isTicketOwnedByUser($ticket_id, $usuario_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT 1 FROM tickets WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$ticket_id, $usuario_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Eliminar ticket (y sus respuestas) de forma segura
 * Solo borra si existe y la llamada se realiza como admin o propietario
 */
function deleteTicket($ticket_id) {
    $pdo = getDB();
    try {
        $pdo->beginTransaction();
        // Borrar respuestas asociadas
        $stmt = $pdo->prepare('DELETE FROM respuestas_ticket WHERE ticket_id = ?');
        $stmt->execute([$ticket_id]);

        // Borrar el ticket
        $stmt = $pdo->prepare('DELETE FROM tickets WHERE id = ?');
        $result = $stmt->execute([$ticket_id]);

        $pdo->commit();
        return (bool)$result;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}

?>