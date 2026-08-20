<?php
/**
 * Script Worker: Procesador de cola de correos en segundo plano.
 * Diseñado para ejecutarse vía CLI, Cron Job o Tarea Programada de Windows.
 */

if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    // Descomentar en producción si deseas restringir el acceso solo a consola:
    // http_response_code(403);
    // die('Acceso denegado: Este script solo puede ejecutarse desde la consola o cron.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$limiteLote = 10;

try {
        $destinatariosAdmin = getTicketNotificationRecipients();

        if (empty($destinatariosAdmin)) {
            throw new RuntimeException('No hay destinatarios admin válidos para procesar la cola de tickets.');
        }

    // 1. Obtener correos pendientes que no hayan superado el máximo de intentos (usando fecha_creacion)
    $stmt = $pdo->prepare("
        SELECT id, destinatario, asunto, cuerpo, intentos, max_intentos 
        FROM cola_correos 
        WHERE estado = 'pendiente' AND intentos < max_intentos 
        ORDER BY fecha_creacion ASC 
        LIMIT :limite
    ");
    $stmt->bindValue(':limite', $limiteLote, PDO::PARAM_INT);
    $stmt->execute();

    $correosPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($correosPendientes)) {
        exit(0);
    }

    $exitos = 0;
    $fallos = 0;

    foreach ($correosPendientes as $correo) {
        $id = $correo['id'];
        $nuevoIntento = $correo['intentos'] + 1;

        // 2. Marcar como procesando e incrementar el contador de intentos
        $updateEstado = $pdo->prepare("
            UPDATE cola_correos 
            SET estado = 'procesando', intentos = ? 
            WHERE id = ?
        ");
        $updateEstado->execute([$nuevoIntento, $id]);

        // 3. Intentar el envío SMTP
            // 3. Intentar el envío SMTP hacia todos los admins activos
            $esExitoso = false;
            $errores = [];

            foreach ($destinatariosAdmin as $destinatarioAdmin) {
                $resultado = enviarCorreoSMTP(
                    $destinatarioAdmin,
                    $correo['asunto'],
                    $correo['cuerpo']
                );

                $resultadoExitoso = is_array($resultado) ? ($resultado['exito'] ?? false) : (bool)$resultado;
                if ($resultadoExitoso) {
                    $esExitoso = true;
                } else {
                    $errores[] = is_array($resultado) ? ($resultado['error'] ?? 'Error desconocido') : 'Error al enviar por SMTP';
                }
            }

            $mensajeError = empty($errores) ? 'Error al enviar por SMTP' : implode(' | ', array_unique($errores));

        // 4. Actualizar según resultado usando fecha_procesado y ultimo_error
        if ($esExitoso) {
            $pdo->prepare("
                UPDATE cola_correos 
                SET estado = 'enviado', 
                    fecha_procesado = NOW(), 
                    ultimo_error = NULL 
                WHERE id = ?
            ")->execute([$id]);
            $exitos++;
        } else {
            // Si superó el máximo de intentos queda 'fallido'; si no, vuelve a 'pendiente' para reintentar luego
            $estadoFinal = ($nuevoIntento >= $correo['max_intentos']) ? 'fallido' : 'pendiente';

            $pdo->prepare("
                UPDATE cola_correos 
                SET estado = ?, 
                    fecha_procesado = NOW(), 
                    ultimo_error = ? 
                WHERE id = ?
            ")->execute([$estadoFinal, $mensajeError, $id]);
            $fallos++;
        }
    }

    echo sprintf("[%s] Procesados: %d | Exitosos: %d | Fallidos: %d\n", 
        date('Y-m-d H:i:s'), 
        count($correosPendientes), 
        $exitos, 
        $fallos
    );

} catch (Exception $e) {
    error_log("Error en worker cron/procesar_correos.php: " . $e->getMessage());
    echo "Error ejecutando el procesador de correos: " . $e->getMessage() . "\n";
    exit(1);
}