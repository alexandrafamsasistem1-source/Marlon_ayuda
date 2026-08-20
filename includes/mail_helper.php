<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (!class_exists('TransportFactory')) {
    class TransportFactory {
        private static array $configs = [];

        public static function setConfig(string $name, array $config): void {
            self::$configs[$name] = $config;
        }

        public static function getConfig(string $name): ?array {
            return self::$configs[$name] ?? null;
        }
    }
}

TransportFactory::setConfig('gmail', [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,
    'username'  => 'rodriguez.bless14@gmail.com',
    'password'  => 'zegsuteeezaqiryo',
    'className' => 'Smtp'
]);

/**
 * Obtener destinatarios válidos para notificaciones de tickets.
 */
function getTicketNotificationRecipients(): array {
    $pdo = getDB();
    $recipients = [];

    if (defined('MAIL_ADMIN_OVERRIDE') && trim((string)MAIL_ADMIN_OVERRIDE) !== '') {
        $overrideEmails = array_filter(array_map('trim', explode(',', (string)MAIL_ADMIN_OVERRIDE)), 'isValidEmail');
        $recipients = array_values(array_unique($overrideEmails));
    }

    if (empty($recipients)) {
        $stmt = $pdo->prepare('SELECT email FROM usuarios WHERE rol IN ("admin", "superadmin") AND activo = 1 AND email IS NOT NULL AND email <> ""');
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($emails as $email) {
            $email = trim((string)$email);
            if (isValidEmail($email)) {
                $recipients[] = $email;
            }
        }

        $recipients = array_values(array_unique($recipients));
    }

    return $recipients;
}

/**
 * Encola un correo del ticket usando el email del creador como referencia.
 */
function queueTicketNotificationEmail(string $creatorEmail, string $subject, string $body): bool {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        return false;
    }

    $pdo = getDB();
    $creatorEmail = trim((string)$creatorEmail);

    if ($creatorEmail === '' || !isValidEmail($creatorEmail)) {
        error_log('No se pudo encolar el ticket porque el email del creador es inválido.');
        return false;
    }

    $stmt = $pdo->prepare("\n        INSERT INTO cola_correos (destinatario, asunto, cuerpo, estado, intentos)\n        VALUES (:destinatario, :asunto, :cuerpo, 'pendiente', 0)\n    ");

    return $stmt->execute([
        ':destinatario' => $creatorEmail,
        ':asunto' => trim($subject),
        ':cuerpo' => $body,
    ]);
}

/**
 * Guarda el correo en la cola sin bloquear la navegación del usuario.
 */
function notificarNuevoTicket($nombre, $gmail, $asunto, $descripcion, $ubicacion, $area, $ticketId = null) {
    $idVisual = $ticketId ? "#{$ticketId}" : "Nuevo";
    $asuntoCorreo = "⚠️ Ticket de Ayuda Creado [{$idVisual}] - {$asunto}";

    $cuerpoHTML = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #006547; color: white; padding: 20px; text-align: center;'>
                <h2 style='margin: 0; font-size: 20px;'>Se ha generado un nuevo ticket de soporte</h2>
            </div>
            <div style='padding: 20px; background-color: #ffffff;'>
                <p><strong>Solicitante:</strong> " . htmlspecialchars($nombre) . " (<a href='mailto:" . htmlspecialchars($gmail) . "'>" . htmlspecialchars($gmail) . "</a>)</p>
                <p><strong>Ubicación:</strong> " . htmlspecialchars($ubicacion) . "</p>
                <p><strong>Área Destino:</strong> " . htmlspecialchars($area) . "</p>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <div style='background-color: #f8fafc; padding: 15px; border-left: 4px solid #BCCBB0; border-radius: 4px;'>
                    <strong style='color: #0d0d0e;'>Descripción del problema:</strong><br>
                    " . nl2br(htmlspecialchars($descripcion)) . "
                </div>
            </div>
            <div style='background-color: #f1f5f9; padding: 12px; text-align: center; font-size: 11px; color: #64748b;'>
                Este mensaje fue enviado automáticamente por el sistema de soporte de Alex_app Support.
            </div>
        </div>
    ";

        return queueTicketNotificationEmail($gmail, $asuntoCorreo, $cuerpoHTML);
}

/**
 * Realiza el envío SMTP y retorna un arreglo con el resultado y detalle del error si ocurre.
 */
function enviarCorreoSMTP($destinatario, $asunto, $cuerpo): array {
    $mail = new PHPMailer(true);

    try {
        $config = TransportFactory::getConfig('gmail');
        if (!$config) {
            throw new Exception("La configuración de Gmail no ha sido inicializada.");
        }

        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];

        $mail->setFrom($config['username'], 'Alex_app Tickets');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;

        $mail->send();
        return ['exito' => true, 'error' => null];
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log("Error PHPMailer: " . $errorMsg);
        return ['exito' => false, 'error' => $errorMsg];
    }
}