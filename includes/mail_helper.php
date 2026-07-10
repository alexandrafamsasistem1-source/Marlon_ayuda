<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Cargamos de manera segura las dependencias de Composer desde la raíz
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Definición de la Factoría para centralizar la configuración de producción
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

// 3. Inicializamos la configuración con tus credenciales de Gmail para Producción
// ⚠️ IMPORTANTE: Cambia los valores de abajo por tus datos reales
TransportFactory::setConfig('gmail', [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,
    'username'  => 'rodriguez.bless14@gmail.com',                 // El Gmail que enviará los correos
    'password'  => 'zegsuteeezaqiryo',   // La contraseña de 16 letras sin espacios que te dio Google
    'className' => 'Smtp'
]);

/**
 * Función que procesa y envía el correo al Superadmin mediante Gmail
 */
function notificarNuevoTicket($nombre, $gmail, $asunto, $descripcion, $ubicacion, $area, $ticketId = null) {
    $mail = new PHPMailer(true);

    try {
        // Obtenemos la configuración de producción de Gmail
        $config = TransportFactory::getConfig('gmail');
        if (!$config) {
            throw new Exception("La configuración de Gmail no ha sido inicializada.");
        }

        // --- Configuración Servidor SMTP (Gmail) ---
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Requerido por Gmail
        $mail->Port       = $config['port'];                // Puerto seguro 587

        // --- Remitente y Destinatario ---
        // ⚠️ El remitente DEBE ser el mismo correo de Gmail configurado en Username
        $mail->setFrom($config['username'], 'Alex_app Tickets');
        
        // ⚠️ Pon aquí el correo de la persona o administrador que debe recibir las alertas de soporte
        $mail->addAddress('alexandrafams.asistem1@gmail.com', 'Super Administrador'); 

        // --- Configuración de Contenido ---
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $idVisual = $ticketId ? "#{$ticketId}" : "Nuevo";
        $mail->Subject = "⚠️ Ticket de Ayuda Creado [{$idVisual}] - {$asunto}";

        // Estructura HTML limpia con diseño adaptado
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
                <div style='background-color: #006547; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 20px;'>Se ha generado un nuevo ticket de soporte</h2>
                </div>
                <div style='padding: 20px; background-color: #ffffff;'>
                    <p><strong>Solicitante:</strong> {$nombre} (<a href='mailto:{$gmail}'>{$gmail}</a>)</p>
                    <p><strong>Ubicación:</strong> {$ubicacion}</p>
                    <p><strong>Área Destino:</strong> {$area}</p>
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

        $mail->send();
        return true;
    } catch (Exception $e) {
        // En producción guardamos los errores en el log del servidor para no interrumpir al usuario
        error_log("Error PHPMailer al enviar notificación de ticket: " . $mail->ErrorInfo);
        return false;
    }
}