<?php
// Forzar a PHP a mostrar cualquier error en pantalla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/mail_helper.php';

echo "<h2>🧪 Probando conexión real con Gmail...</h2>";

// Creamos una instancia limpia de PHPMailer solo para la prueba de pantalla
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(true);

try {
    // Activamos el modo de diagnóstico avanzado (Muestra toda la conversación con Gmail)
    $mail->SMTPDebug = 2; 
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    // Configura AQUÍ temporalmente los mismos datos que pusiste en tu mail_helper:
    $mail->Username   = 'rodriguez.bless14@gmail.com'; 
    $mail->Password   = 'zegsuteeezaqiryo'; 
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($mail->Username, 'Prueba Alex_app');
    $mail->addAddress($mail->Username, 'Administrador'); // Se lo mandamos a uno mismo para probar

    $mail->isHTML(true);
    $mail->Subject = "Prueba Directa de Servidor Local";
    $mail->Body    = "Si lees esto, la conexión de red y credenciales están perfectas.";

    $mail->send();
    echo "<br><span style='color:green; font-weight:bold;'>¡ÉXITO! El correo de prueba salió hacia Gmail sin problemas.</span>";
} catch (Exception $e) {
    echo "<br><span style='color:red; font-weight:bold;'>❌ EL ENVIÓ FALLÓ. Detalle del error:</span><br>";
    echo "<pre>" . htmlspecialchars($mail->ErrorInfo) . "</pre>";
}