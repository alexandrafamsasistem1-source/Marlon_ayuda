<?php
/**
 * Fachada de compatibilidad para las funciones legacy de la aplicación.
 *
 * Las páginas existentes mantienen esta ruta pública mientras la implementación
 * se organiza en src/Support/LegacyFunctions.php.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

function getAuthService() {
    static $authService = null;

    if ($authService === null) {
        if (!class_exists('App\\Services\\AuthService')) {
            require_once __DIR__ . '/../../src/Services/AuthService.php';
        }
        if (!class_exists('App\\Database\\Database')) {
            require_once __DIR__ . '/../../src/Database/Database.php';
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $authService = new \App\Services\AuthService(
            $baseUrl,
            \App\Database\Database::getInstance()
        );
    }

    return $authService;
}

function getDB() {
    static $database = null;

    if (!class_exists('App\\Database\\Database')) {
        require_once __DIR__ . '/../../src/Database/Database.php';
    }

    if ($database === null) {
        $database = \App\Database\Database::getInstance();
    }

    return $database->getConnection();
}

function getTicketService() {
    static $service = null;

    if ($service === null) {
        if (!class_exists('App\\Services\\TicketService')) {
            require_once __DIR__ . '/../../src/Services/TicketService.php';
        }
        $service = new \App\Services\TicketService(
            getTicketRepository(),
            getNotificationService()
        );
    }

    return $service;
}

function getTicketRepository() {
    static $repository = null;

    if ($repository === null) {
        if (!class_exists('App\\Repositories\\TicketRepository')) {
            require_once __DIR__ . '/../../src/Repositories/TicketRepository.php';
        }
        $repository = new \App\Repositories\TicketRepository(getDB());
    }

    return $repository;
}

function getNotificationService() {
    static $service = null;

    if ($service === null) {
        if (!class_exists('App\\Repositories\\NotificationRepository')) {
            require_once __DIR__ . '/../../src/Repositories/NotificationRepository.php';
        }
        if (!class_exists('App\\Services\\NotificationService')) {
            require_once __DIR__ . '/../../src/Services/NotificationService.php';
        }
        $service = new \App\Services\NotificationService(
            new \App\Repositories\NotificationRepository(getDB())
        );
    }

    return $service;
}

function getUserRepository() {
    static $repository = null;
    if ($repository === null) {
        if (!class_exists('App\\Repositories\\UserRepository')) {
            require_once __DIR__ . '/../../src/Repositories/UserRepository.php';
        }
        $repository = new \App\Repositories\UserRepository(getDB());
    }
    return $repository;
}

function getHistoryRepository() {
    static $repository = null;
    if ($repository === null) {
        if (!class_exists('App\\Repositories\\HistoryRepository')) {
            require_once __DIR__ . '/../../src/Repositories/HistoryRepository.php';
        }
        $repository = new \App\Repositories\HistoryRepository(getDB());
    }
    return $repository;
}

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return getAuthService()->isLoggedIn();
}

/**
 * Verificar si el usuario logueado tiene permisos de admin
 */
function isAdmin() {
    return getAuthService()->isAdmin();
}

/**
 * Verificar si el usuario logueado es superadmin
 */
function isSuperAdmin() {
    return getAuthService()->isSuperAdmin();
}

/**
 * Guardar una notificación flash en sesión.
 */
function setFlash($tipo, $mensaje, $titulo = '') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tipoPermitido = in_array($tipo, ['success', 'error', 'warning', 'info'], true) ? $tipo : 'info';
    $_SESSION['flash_notification'] = [
        'tipo' => $tipoPermitido,
        'mensaje' => (string)$mensaje,
        'titulo' => (string)$titulo,
    ];
}

/**
 * Obtener un token CSRF asociado a la sesión actual.
 */
function csrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Generar el campo oculto CSRF para formularios.
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . sanitize(csrfToken()) . '">';
}

/**
 * Validar un token CSRF enviado por POST.
 */
function isValidCsrfToken($token) {
    return is_string($token)
        && $token !== ''
        && hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token);
}

/**
 * Redirigir a login si no está autenticado
 */
function requireLogin() {
    getAuthService()->requireLogin();
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
    getAuthService()->requireAdmin();
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
    return getAuthService()->hashPassword((string)$password);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return getAuthService()->verifyPassword((string)$password, (string)$hash);
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
 * Obtener usuario por ID (Incluye el campo área)
 */
function getUserById($id) {
    return getUserRepository()->findById((int)$id);
}

/**
 * Obtener usuario por email
 */
function getUserByEmail($email) {
    return getUserRepository()->findByEmail((string)$email);
}

/**
 * Crear nuevo usuario (Incluye área)
 */
function createUser($nombre, $email, $password, $rol = 'usuario', $area = 'Administracion', $debe_cambiar_password = 1) {
    if (getUserByEmail($email)) {
        return ['success' => false, 'error' => 'El email ya existe'];
    }

    $passwordHashed = hashPassword($password);
    $userId = getUserRepository()->create(
        (string)$nombre,
        (string)$email,
        $passwordHashed,
        (string)$rol,
        (string)$area,
        (int)$debe_cambiar_password
    );
    return $userId > 0
        ? ['success' => true, 'usuario_id' => $userId]
        : ['success' => false, 'error' => 'Error al crear usuario'];
}

/**
 * Obtener todos los tickets (para admin)
 */
function getAllTickets($limit = 50, $offset = 0, $estado = null) {
    return getTicketRepository()->findAll((int)$limit, (int)$offset, $estado);
}

/**
 * Obtener tickets del usuario actual
 */
function getUserTickets($usuario_id, $limit = 50, $offset = 0, $estado = null, $fechaDesde = null, $fechaHasta = null) {
    return getTicketRepository()->findByUser(
            (int)$usuario_id,
            (int)$limit,
            (int)$offset,
            $estado,
            $fechaDesde,
            $fechaHasta
        );
}

/**
 * Obtener detalle de un ticket
 */
function getTicketById($ticket_id) {
    return getTicketService()->findById((int)$ticket_id);
}

/**
 * Asegurar que exista la tabla de notificaciones
 */
function ensureNotificationsTable() {
    return getNotificationService()->ensureTable();
}

/**
 * Crear notificación interna para admins cuando se genera un ticket
 */
function createTicketNotification($ticket_id, $usuario_id, $asunto, $ubicacion, $area = null) {
    if (!ensureNotificationsTable()) {
        return 0;
    }

    $admins = getAllAdmins();
    if (empty($admins)) {
        return 0;
    }

    $usuario = getUserById($usuario_id);
    $usuarioNombre = $usuario['nombre'] ?? 'Usuario';
    $adminIds = array_map(static function ($admin) {
        return (int)$admin['id'];
    }, $admins);

    return getNotificationService()->createTicketNotification(
        (int)$ticket_id,
        $adminIds,
        $usuarioNombre,
        (string)$asunto,
        (string)$ubicacion,
        $area
    );
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

    if (!ensureNotificationsTable()) {
        return 0;
    }

    return getNotificationService()->getUnread((int)$usuario_id);
}

/**
 * Obtener notificaciones recientes para un usuario
 */
function getNotificationsForUser($usuario_id, $limit = 10, $unreadOnly = true) {
    if (!$usuario_id) {
        return [];
    }

    if (!ensureNotificationsTable()) {
        return [];
    }

    return getNotificationService()->forUser((int)$usuario_id, (int)$limit, (bool)$unreadOnly);
}

/**
 * Resolver el contenido visible de una notificación usando el ticket vinculado.
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

    if (!ensureNotificationsTable()) {
        return false;
    }

    return getNotificationService()->markAsRead((int)$usuario_id);
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
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
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

    $recipients = [];

    if (defined('MAIL_ADMIN_OVERRIDE') && trim(MAIL_ADMIN_OVERRIDE) !== '') {
        $overrideEmails = array_filter(array_map('trim', explode(',', MAIL_ADMIN_OVERRIDE)), 'isValidEmail');
        $recipients = array_values($overrideEmails);
    } else {
        $superAdmins = getUserRepository()->findAdministratorEmails('superadmin');

        foreach ($superAdmins as $superAdmin) {
            if (!empty($superAdmin['email']) && isValidEmail($superAdmin['email'])) {
                $recipients[] = $superAdmin['email'];
            }
        }

        if (empty($recipients)) {
            $admins = getUserRepository()->findAdministratorEmails();
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
    try {
        $ticketId = getTicketService()->createTicket(
            (int)$usuario_id,
            (string)$asunto,
            (string)$descripcion,
            (string)$ubicacion,
            $area
        );

        if ($ticketId > 0) {
            sendAdminNewTicketEmail($ticketId, $asunto, $descripcion, $usuario_id, $ubicacion, $area);
        }

        return ['success' => true, 'ticket_id' => $ticketId];
    } catch (Throwable $exception) {
        error_log('Error al crear ticket: ' . $exception->getMessage());
        return ['success' => false, 'error' => 'Error al crear ticket'];
    }
}

/**
 * Actualizar estado de ticket y opcionalmente el tipo de problema (Software/Hardware)
 */
function updateTicketStatus($ticket_id, $estado, $asignado_a = null, $tipo_problema = null) {
    try {
        return getTicketService()->updateWorkflow(
            (int)$ticket_id,
            (string)$estado,
            $asignado_a === null ? null : (int)$asignado_a,
            $tipo_problema
        );
    } catch (Throwable $exception) {
        error_log('Error al actualizar ticket: ' . $exception->getMessage());
        return false;
    }
}

/**
 * Agregar respuesta a ticket
 */
function addResponseToTicket($ticket_id, $usuario_id, $mensaje) {
    try {
        if (!getTicketService()->addResponse((int)$ticket_id, (int)$usuario_id, (string)$mensaje)) {
            return ['success' => false, 'error' => 'No autorizado para responder tickets'];
        }

        return ['success' => true];
    } catch (Throwable $exception) {
        error_log('Error al agregar respuesta: ' . $exception->getMessage());
        return ['success' => false, 'error' => 'Error al agregar respuesta'];
    }
}

/**
 * Obtener respuestas de un ticket
 */
function getTicketResponses($ticket_id) {
    try {
        return getTicketService()->getResponses((int)$ticket_id);
    } catch (Throwable $exception) {
        error_log('Error al obtener respuestas: ' . $exception->getMessage());
        return [];
    }
}

/**
 * Eliminar una respuesta por su id (solo admins)
 */
function deleteResponse($response_id, $actor_id) {
    try {
        $result = getTicketService()->deleteResponse((int)$response_id, (int)$actor_id);
        if ($result === 'unauthorized') {
            return ['success' => false, 'error' => 'No autorizado'];
        }
        if ($result === 'not_found') {
            return ['success' => false, 'error' => 'Respuesta no encontrada'];
        }
        if ($result === 'error') {
            return ['success' => false, 'error' => 'Error al eliminar respuesta'];
        }

        if (function_exists('registrarHistorialTicket')) {
            registrarHistorialTicket((int)$result, (int)$actor_id, 'eliminar_respuesta', 'Eliminó una respuesta en la conversación');
        }

        return ['success' => true];
    } catch (Throwable $exception) {
        error_log('Error al eliminar respuesta: ' . $exception->getMessage());
        return ['success' => false, 'error' => 'Error al eliminar respuesta'];
    }
}

/**
 * Contar tickets totales
 */
function countTotalTickets() {
    return getTicketRepository()->countTotal();
}

/**
 * Contar tickets por estado
 */
function countTicketsByStatus() {
    return getTicketRepository()->countByStatus();
}

/**
 * Contar tickets por ubicación
 */
function countTicketsByLocation() {
    return getTicketRepository()->countByLocation();
}

/**
 * Obtener lista de todos los admins
 */
function getAllAdmins() {
    return getUserRepository()->findAdministrators();
}

/**
 * Obtener todos los usuarios (Incluye área)
 */
function getAllUsers($limit = 100, $offset = 0) {
    return getUserRepository()->findAll((int)$limit, (int)$offset);
}

/**
 * Contar total de usuarios
 */
function countAllUsers() {
    return getUserRepository()->countAll();
}

/**
 * Actualizar datos de un usuario (Incluye área y sincronización inmediata de sesión)
 */
function updateUser($user_id, $nombre, $email, $rol = 'usuario', $area = 'Administracion', $password = null) {
    // Normalizar y validar áreas permitidas
    $area = trim($area);
    $areasPermitidas = ['Administracion', 'Produccion', 'Poscosecha', 'Juridica', 'Cartera', 'Gestion Humana'];
    if (!in_array($area, $areasPermitidas, true)) {
       $area = 'Administracion';
    }

    // Verificar que el email no esté en uso por otro usuario
    if (getUserRepository()->emailBelongsToOther((string)$email, (int)$user_id)) {
        return [
            'success' => false,
            'error' => 'El email ya está registrado por otro usuario.'
        ];
    }
    
    try {
        getUserRepository()->update(
            (int)$user_id,
            (string)$nombre,
            (string)$email,
            (string)$rol,
            (string)$area,
            !empty($password) ? hashPassword($password) : null
        );

        // Sincronizar datos de la sesión activa inmediatamente si coinciden con el usuario modificado
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] === (int)$user_id) {
            $_SESSION['nombre'] = $nombre;
            $_SESSION['rol'] = $rol;
            $_SESSION['area'] = $area;
            if (isset($_SESSION['user'])) {
                $_SESSION['user']['nombre'] = $nombre;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['rol'] = $rol;
                $_SESSION['user']['area'] = $area;
            }
        }
        
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
            $countSuperAdmins = getUserRepository()->countSuperAdmins();
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
        
        getTicketRepository()->unassignUserTickets((int)$user_id);
        
        // Eliminar notificaciones del usuario
        getUserRepository()->deleteNotifications((int)$user_id);
        
        getTicketRepository()->deleteUserResponses((int)$user_id);
        
        getUserRepository()->delete((int)$user_id);
        
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
    return getTicketRepository()->isOwnedByUser((int)$ticket_id, (int)$usuario_id);
}

/**
 * Eliminar ticket (y sus respuestas) de forma segura
 */
function deleteTicket($ticket_id) {
    try {
        return getTicketRepository()->delete((int)$ticket_id);
    } catch (Throwable $exception) {
        error_log('Error eliminando ticket: ' . $exception->getMessage());
        return false;
    }
}

/**
 * Obtener el historial de tickets resueltos o cerrados por mes, año y administrador asignado
 */
function getResolvedTicketsByMonth($year, $month, $assignedUserId = null) {
    try {
        return getTicketRepository()
            ->getResolvedByMonth((int)$year, (int)$month, $assignedUserId === null ? null : (int)$assignedUserId);
    } catch (Throwable $exception) {
        error_log("Error en getResolvedTicketsByMonth: " . $exception->getMessage());
        return [];
    }
}

/**
 * Registrar un evento en el historial de un ticket
 */
function registrarHistorialTicket($ticket_id, $usuario_id, $tipo_accion, $descripcion) {
    try {
        return getHistoryRepository()->record(
            (int)$ticket_id,
            (int)$usuario_id,
            (string)$tipo_accion,
            (string)$descripcion
        );
    } catch (Throwable $exception) {
        error_log("Error al registrar historial: " . $exception->getMessage());
        return false;
    }
}

/**
 * Obtener la línea de tiempo/historial de un ticket específico
 */
function getTicketHistory($ticket_id) {
    try {
        return getHistoryRepository()->findByTicket((int)$ticket_id);
    } catch (Throwable $exception) {
        error_log("Error al obtener historial: " . $exception->getMessage());
        return [];
    }
}

function encolarCorreo(PDO $pdo, string $destinatario, string $asunto, string $cuerpo): bool {
    if (!class_exists('App\\Repositories\\MailQueueRepository')) {
        require_once __DIR__ . '/../../src/Repositories/MailQueueRepository.php';
    }

    return (new \App\Repositories\MailQueueRepository($pdo))
        ->enqueue($destinatario, $asunto, $cuerpo);
}

function updatePasswordAndClearFlag($usuario_id, $nueva_password) {
    return getUserRepository()->updatePassword(
        (int)$usuario_id,
        hashPassword($nueva_password)
    );
}

/**
 * Valida requisitos mínimos de seguridad para la contraseña
 */
function validarPassword($password) {
    if (strlen($password) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        return "La contraseña debe contener al menos una letra.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "La contraseña debe contener al menos un número.";
    }
    return true;
}
?>