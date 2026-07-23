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
 * Verificar si el usuario logueado tiene permisos de admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['admin', 'superadmin'], true);
}

/**
 * Verificar si el usuario logueado es superadmin
 */
function isSuperAdmin() {
    return isLoggedIn() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'superadmin';
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
 * Asegurar que exista la tabla de notificaciones
 */
function ensureNotificationsTable() {
    $pdo = getDB();

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'notificaciones'");
        if ($stmt->fetch()) {
            return true;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS notificaciones (
                id INT PRIMARY KEY AUTO_INCREMENT,
                usuario_id INT NOT NULL,
                tipo VARCHAR(50) NOT NULL DEFAULT 'ticket_nuevo',
                mensaje TEXT NOT NULL,
                referencia_id INT NULL,
                leida TINYINT(1) NOT NULL DEFAULT 0,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_lectura TIMESTAMP NULL,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                INDEX idx_usuario_leida (usuario_id, leida),
                INDEX idx_fecha_creacion (fecha_creacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        error_log('Error creando tabla de notificaciones: ' . $e->getMessage());
        return false;
    }
}

/**
 * Crear notificación interna para admins cuando se genera un ticket
 */
function createTicketNotification($ticket_id, $usuario_id, $asunto, $ubicacion, $area = null) {
    if (!ensureNotificationsTable()) {
        return 0;
    }

    $pdo = getDB();
    $admins = getAllAdmins();
    if (empty($admins)) {
        return 0;
    }

    $usuario = getUserById($usuario_id);
    $usuarioNombre = $usuario['nombre'] ?? 'Usuario';
    $areaTexto = $area ? $area : 'Sin área';
    $mensaje = "Nuevo ticket #{$ticket_id}: {$asunto} ({$ubicacion} - {$areaTexto}) enviado por {$usuarioNombre}";

    $created = 0;
    foreach ($admins as $admin) {
        $stmt = $pdo->prepare('INSERT INTO notificaciones (usuario_id, tipo, mensaje, referencia_id) VALUES (?, ?, ?, ?)');
        if ($stmt->execute([$admin['id'], 'ticket_nuevo', $mensaje, $ticket_id])) {
            $created++;
        }
    }

    return $created;
}

/**
 * Obtener el número de notificaciones no leídas del usuario actual
 */
function getUnreadNotificationsCount($usuario_id = null) {
    if ($usuario_id === null) {
        $usuario_id = getUserId();
    }

    if (!$usuario_id) {
        return 0;
    }

    ensureNotificationsTable();
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0');
    $stmt->execute([$usuario_id]);
    return (int)($stmt->fetch()['total'] ?? 0);
}

/**
 * Obtener notificaciones recientes para un usuario
 * @param int $usuario_id - ID del usuario
 * @param int $limit - Límite de notificaciones a devolver
 * @param bool $unreadOnly - Si es true, solo devuelve notificaciones no leídas
 */
function getNotificationsForUser($usuario_id, $limit = 10, $unreadOnly = true) {
    if (!$usuario_id) {
        return [];
    }

    ensureNotificationsTable();
    $pdo = getDB();
    
    $condition = $unreadOnly ? 'AND leida = 0' : '';
    $stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE usuario_id = ? ' . $condition . ' ORDER BY fecha_creacion DESC LIMIT ?');
    $stmt->execute([$usuario_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Resolver el contenido visible de una notificación usando el ticket vinculado.
 * Devuelve un array con: ticket_id, usuario, asunto, fecha.
 */
function getNotificationPreview($notification) {
    $default = [
        'ticket_id' => null,
        'usuario' => 'Usuario',
        'asunto' => 'Notificación',
        'fecha' => $notification['fecha_creacion'] ?? null,
    ];

    if (empty($notification['referencia_id'])) {
        return $default;
    }

    $ticket = getTicketById((int)$notification['referencia_id']);
    if (!$ticket) {
        return $default;
    }

    $asunto = $ticket['asunto'] ?? null;
    $usuario = $ticket['usuario_nombre'] ?? null;

    return [
        'ticket_id' => (int)$ticket['id'],
        'usuario' => $usuario ?: 'Usuario',
        'asunto' => $asunto ?: 'Notificación',
        'fecha' => $notification['fecha_creacion'] ?? null,
    ];
}

/**
 * Marcar notificaciones como leídas
 */
function markNotificationsAsRead($usuario_id = null) {
    if ($usuario_id === null) {
        $usuario_id = getUserId();
    }

    if (!$usuario_id) {
        return false;
    }

    ensureNotificationsTable();
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE notificaciones SET leida = 1, fecha_lectura = NOW() WHERE usuario_id = ? AND leida = 0');
    return $stmt->execute([$usuario_id]);
}

/**
 * Enviar un correo usando la configuración definida para mail o SMTP.
 */
function sendMailMessage($to, $subject, $message, $replyToEmail = null, $fromAddress = null, $fromName = null) {
    if (empty($to)) {
        return false;
    }

    if (!is_array($to)) {
        $to = [$to];
    }

    $fromAddress = $fromAddress ?: (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@tickets.local');
    $fromName = $fromName ?: (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Sistema de Tickets');
    $replyToEmail = $replyToEmail ?: $fromAddress;

    $driver = strtolower((string)(defined('MAIL_DRIVER') ? MAIL_DRIVER : 'mail'));
    $useSmtp = $driver === 'smtp' && defined('MAIL_SMTP_HOST') && trim((string)MAIL_SMTP_HOST) !== '';

    if ($useSmtp) {
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            error_log('No se encontró autoload de PHPMailer para SMTP.');
            return false;
        }

        require_once $autoloadPath;

        $sent = false;
        foreach ($to as $recipient) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = MAIL_SMTP_HOST;
                $mail->Port = (int)MAIL_SMTP_PORT;
                $mail->SMTPAuth = (bool)MAIL_SMTP_AUTH;
                $mail->Username = MAIL_SMTP_USERNAME;
                $mail->Password = MAIL_SMTP_PASSWORD;
                $encryption = trim((string)MAIL_SMTP_ENCRYPTION);
                $mail->SMTPSecure = $encryption !== '' ? $encryption : false;
                $mail->setFrom($fromAddress, $fromName);
                $mail->addReplyTo($replyToEmail, $fromName);
                $mail->addAddress($recipient);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AltBody = strip_tags($message);
                $mail->CharSet = 'UTF-8';
                $mail->send();
                $sent = true;
            } catch (Exception $e) {
                error_log('No se pudo enviar correo SMTP a ' . $recipient . ': ' . $e->getMessage());
            }
        }

        return $sent;
    }

    if (!function_exists('mail')) {
        error_log('PHP mail() no está disponible en este entorno.');
        return false;
    }

    $headers = "From: {$fromName} <{$fromAddress}>\r\n";
    $headers .= "Reply-To: {$replyToEmail}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $sent = false;
    foreach ($to as $recipient) {
        $result = @mail($recipient, $subject, wordwrap($message, 70), $headers);
        if ($result) {
            $sent = true;
        } else {
            error_log('No se pudo enviar correo a ' . $recipient);
        }
    }

    return $sent;
}

/**
 * Enviar correo de aviso a los admins cuando se cree un ticket
 */
function sendAdminNewTicketEmail($ticket_id, $asunto, $descripcion, $usuario_id, $ubicacion, $area = null) {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        return false;
    }

    $pdo = getDB();
    $recipients = [];

    if (defined('MAIL_ADMIN_OVERRIDE') && trim(MAIL_ADMIN_OVERRIDE) !== '') {
        $overrideEmails = array_filter(array_map('trim', explode(',', MAIL_ADMIN_OVERRIDE)), 'isValidEmail');
        $recipients = array_values($overrideEmails);
    } else {
        $stmt = $pdo->prepare('SELECT email FROM usuarios WHERE rol = ? AND activo = 1 AND email IS NOT NULL');
        $stmt->execute(['superadmin']);
        $superAdmins = $stmt->fetchAll();

        foreach ($superAdmins as $superAdmin) {
            if (!empty($superAdmin['email']) && isValidEmail($superAdmin['email'])) {
                $recipients[] = $superAdmin['email'];
            }
        }

        if (empty($recipients)) {
            $stmt = $pdo->prepare('SELECT email FROM usuarios WHERE rol IN ("admin", "superadmin") AND activo = 1 AND email IS NOT NULL');
            $stmt->execute();
            $admins = $stmt->fetchAll();
            foreach ($admins as $admin) {
                if (!empty($admin['email']) && isValidEmail($admin['email'])) {
                    $recipients[] = $admin['email'];
                }
            }
        }
    }

    if (empty($recipients)) {
        error_log('No hay destinatarios válidos para la notificación por correo del ticket #' . $ticket_id);
        return false;
    }

    $usuario = getUserById($usuario_id);
    $usuarioNombre = $usuario['nombre'] ?? 'Usuario';
    $areaTexto = $area ? $area : 'Sin área';
    $subject = 'Nuevo ticket pendiente #' . $ticket_id;
    $message = "Hola,\n\nSe ha creado un nuevo ticket en el sistema y requiere revisión.\n\n" .
        "Ticket #: {$ticket_id}\n" .
        "Usuario: {$usuarioNombre}\n" .
        "Asunto: {$asunto}\n" .
        "Ubicación: {$ubicacion}\n" .
        "Área: {$areaTexto}\n\n" .
        "Descripción:\n{$descripcion}\n\n" .
        "Puedes revisarlo aquí: " . BASE_URL . "/admin/ver_ticket.php?id={$ticket_id}";

    $replyToEmail = isset($usuario['email']) && isValidEmail($usuario['email']) ? $usuario['email'] : 'no-reply@tickets.local';
    $fromAddress = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@tickets.local';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Sistema de Tickets';

    $sent = false;
    foreach ($recipients as $recipient) {
        $result = sendMailMessage($recipient, $subject, $message, $replyToEmail, $fromAddress, $fromName);
        if ($result) {
            $sent = true;
        } else {
            error_log('No se pudo enviar correo a ' . $recipient . ' para el ticket #' . $ticket_id);
        }
    }

    return $sent;
}

/**
 * Crear nuevo ticket
 */
function createTicket($usuario_id, $asunto, $descripcion, $ubicacion, $area = null) {
    $pdo = getDB();
    $params = [$usuario_id, $asunto, $descripcion, $ubicacion];
    $hasArea = false;
    try {
        $hasArea = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'area'")->fetchColumn();
    } catch (Exception $e) {
        $hasArea = false;
    }

    if ($hasArea) {
        $areaValue = $area;
        $sql = 'INSERT INTO tickets (usuario_id, asunto, descripcion, ubicacion, area) VALUES (?, ?, ?, ?, ?)';
        $params[] = $areaValue;
    } else {
        $sql = 'INSERT INTO tickets (usuario_id, asunto, descripcion, ubicacion) VALUES (?, ?, ?, ?)';
    }

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        $ticket_id = (int)$pdo->lastInsertId();

        if ($ticket_id > 0) {
            createTicketNotification($ticket_id, $usuario_id, $asunto, $ubicacion, $area);
            sendAdminNewTicketEmail($ticket_id, $asunto, $descripcion, $usuario_id, $ubicacion, $area);
        }

        return ['success' => true, 'ticket_id' => $ticket_id];
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

    $stmtRol = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ? AND activo = 1 LIMIT 1');
    $stmtRol->execute([$usuario_id]);
    $rolUsuario = $stmtRol->fetchColumn();

    if (!in_array($rolUsuario, ['admin', 'superadmin'], true)) {
        return ['success' => false, 'error' => 'No autorizado para responder tickets'];
    }

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
    $stmt = $pdo->query('SELECT id, nombre, email, rol FROM usuarios WHERE rol IN ("admin", "superadmin") AND activo = 1');
    return $stmt->fetchAll();
}

/**
 * Obtener todos los usuarios
 */
function getAllUsers($limit = 100, $offset = 0) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, nombre, email, rol, fecha_registro, activo FROM usuarios ORDER BY fecha_registro DESC LIMIT ? OFFSET ?');
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Contar total de usuarios
 */
function countAllUsers() {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
    return (int)($stmt->fetch()['total'] ?? 0);
}

/**
 * Actualizar datos de un usuario
 */
function updateUser($user_id, $nombre, $email, $rol = null, $password = null) {
    $pdo = getDB();
    
    // Verificar que el email no esté en uso por otro usuario
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        return [
            'success' => false,
            'error' => 'El email ya está registrado por otro usuario.'
        ];
    }
    
    try {
        if ($password) {
            $sql = 'UPDATE usuarios SET nombre = ?, email = ?, rol = ?, password = ? WHERE id = ?';
            $params = [$nombre, $email, $rol, hashPassword($password), $user_id];
        } else {
            $sql = 'UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?';
            $params = [$nombre, $email, $rol, $user_id];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return [
            'success' => true,
            'message' => 'Usuario actualizado correctamente.'
        ];
    } catch (PDOException $e) {
        error_log('Error actualizando usuario: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al actualizar el usuario.'
        ];
    }
}

/**
 * Eliminar un usuario
 */
function deleteUser($user_id) {
    $pdo = getDB();
    
    // Verificar que no sea el único superadmin
    if (!isSuperAdmin()) {
        $user = getUserById($user_id);
        if ($user && $user['rol'] === 'superadmin') {
            $countSuperAdmins = $pdo->query('SELECT COUNT(*) as total FROM usuarios WHERE rol = "superadmin"')->fetch()['total'] ?? 0;
            if ($countSuperAdmins <= 1) {
                return [
                    'success' => false,
                    'error' => 'No se puede eliminar el único superadmin del sistema.'
                ];
            }
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Reasignar tickets del usuario a NULL
        $stmt = $pdo->prepare('UPDATE tickets SET usuario_id = NULL WHERE usuario_id = ?');
        $stmt->execute([$user_id]);
        
        // Eliminar notificaciones del usuario
        $stmt = $pdo->prepare('DELETE FROM notificaciones WHERE usuario_id = ?');
        $stmt->execute([$user_id]);
        
        // Eliminar respuestas del usuario
        $stmt = $pdo->prepare('DELETE FROM respuestas_ticket WHERE usuario_id = ?');
        $stmt->execute([$user_id]);
        
        // Eliminar el usuario
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Usuario eliminado correctamente.'
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Error eliminando usuario: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Error al eliminar el usuario.'
        ];
    }
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
/**
 * Obtener el historial de tickets resueltos o cerrados por mes, año y administrador asignado
 */
function getResolvedTicketsByMonth($year, $month, $assignedUserId = null) {
    $pdo = getDB();
    
    // Comprobar si existe la columna 'area' tal como haces en getUserTickets
    $selectArea = '';
    try {
        $hasArea = (bool)$pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'area'")->fetchColumn();
        if ($hasArea) $selectArea = ', t.area';
    } catch (Exception $e) {
        $selectArea = '';
    }

    $sql = "SELECT 
                t.id,
                t.asunto,
                t.estado,
                t.ubicacion,
                t.fecha_creacion,
                t.fecha_ultima_actualizacion AS fecha_resolucion" . $selectArea . ",
                u.nombre as usuario_nombre,
                a.nombre as asignado_nombre
            FROM tickets t
            LEFT JOIN usuarios u ON t.usuario_id = u.id
            LEFT JOIN usuarios a ON t.asignado_a = a.id
            WHERE t.estado IN ('Resuelto', 'Cerrado')
              AND YEAR(t.fecha_ultima_actualizacion) = ?
              AND MONTH(t.fecha_ultima_actualizacion) = ?
              " . ($assignedUserId !== null ? "AND t.asignado_a = ?" : "") . "
            ORDER BY t.fecha_ultima_actualizacion DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $params = [(int)$year, (int)$month];
        if ($assignedUserId !== null) {
            $params[] = (int)$assignedUserId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error en getResolvedTicketsByMonth: " . $e->getMessage());
        return [];
    }
}
/**
 * Registrar un evento en el historial de un ticket
 */
function registrarHistorialTicket($ticket_id, $usuario_id, $tipo_accion, $descripcion) {
    $pdo = getDB();
    $sql = "INSERT INTO historial_tickets (ticket_id, usuario_id, tipo_accion, descripcion) 
            VALUES (?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$ticket_id, $usuario_id, $tipo_accion, $descripcion]);
    } catch (Exception $e) {
        error_log("Error al registrar historial: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener la línea de tiempo/historial de un ticket específico
 */
function getTicketHistory($ticket_id) {
    $pdo = getDB();
    $sql = "SELECT 
                h.id,
                h.tipo_accion,
                h.descripcion,
                h.fecha_creacion,
                u.nombre AS autor_nombre,
                u.rol AS autor_rol
            FROM historial_tickets h
            INNER JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.ticket_id = ?
            ORDER BY h.fecha_creacion ASC";
            
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ticket_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error al obtener historial: " . $e->getMessage());
        return [];
    }
}
?>

