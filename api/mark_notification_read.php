<?php
/**
 * API para marcar una notificación como leída (AJAX)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar que sea admin y esté logueado
if (!isAdmin() || !isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener ID de notificación
$notif_id = isset($_POST['notif_id']) ? (int)$_POST['notif_id'] : null;

if (!$notif_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de notificación requerido']);
    exit;
}

try {
    $pdo = getDB();
    
    // Verificar que la notificación pertenece al usuario actual
    $stmt = $pdo->prepare('SELECT id FROM notificaciones WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$notif_id, getUserId()]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Notificación no encontrada o no autorizada']);
        exit;
    }
    
    // Marcar como leída
    $stmt = $pdo->prepare('UPDATE notificaciones SET leida = 1, fecha_lectura = NOW() WHERE id = ?');
    $stmt->execute([$notif_id]);
    
    // Recalcular el conteo de no leídas
    $pendingCount = getUnreadNotificationsCount(getUserId());
    
    echo json_encode([
        'success' => true,
        'message' => 'Notificación marcada como leída',
        'pending_count' => $pendingCount
    ]);
    
} catch (Exception $e) {
    error_log('Error marcando notificación como leída: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>
