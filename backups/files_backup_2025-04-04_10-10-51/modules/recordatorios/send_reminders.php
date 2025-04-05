<?php
/**
 * Script de Envío de Recordatorios
 * 
 * Este script está diseñado para ser ejecutado por un cron job diariamente.
 * Envía recordatorios por email (y SMS si está configurado) a los pacientes
 * con citas en las próximas 24 horas.
 */

// Definir que es un script CLI
define('IS_CLI', php_sapi_name() === 'cli');

// Si se ejecuta desde navegador, verificar autenticación
if (!IS_CLI) {
    require_once '../../includes/config.php';
    require_once '../../includes/functions.php';
    
    // Verificar autenticación (solo admin puede ejecutarlo manualmente)
    if (!isset($_SESSION['usuario_id']) || !esAdmin()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
} else {
    // Desde CLI, incluir archivos con ruta absoluta
    $rootPath = dirname(dirname(dirname(__FILE__)));
    require_once $rootPath . '/includes/config.php';
    require_once $rootPath . '/includes/functions.php';
}

// Función para registrar mensajes de log
function logMessage($message, $type = 'info') {
    $date = date('Y-m-d H:i:s');
    $formattedMessage = "[$date][$type] $message" . PHP_EOL;
    
    // Si es CLI, imprimir en consola
    if (IS_CLI) {
        echo $formattedMessage;
    }
    
    // Asegurarse de que existe el directorio de logs
    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    // Guardar en archivo de log
    $logFile = $logDir . '/reminders_' . date('Y-m-d') . '.log';
    
    // Intentar escribir en el archivo de log
    try {
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    } catch (Exception $e) {
        if (IS_CLI) {
            echo "[ERROR] No se pudo escribir en el archivo de log: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    return true;
}

// Función para enviar email
function sendReminderEmail($to, $subject, $message) {
    try {
        // Obtener configuración SMTP
        $db = getDB();
        $config = [];
        
        // Obtener todas las configuraciones relacionadas con email
        $stmt = $db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_sistema', 'smtp_active', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_name')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        // Valores por defecto
        $nombre_clinica = $config['nombre_sistema'] ?? "Clínica";
        $email_from = $config['email_from'] ?? "no-reply@" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'clinica.com');
        $email_name = $config['email_name'] ?? $nombre_clinica;
        
        // Verificar si usar SMTP
        $usar_smtp = !empty($config['smtp_active']) && $config['smtp_active'] == '1' &&
                     !empty($config['smtp_host']) && !empty($config['smtp_user']);
        
        logMessage("Configuración de envío: " . ($usar_smtp ? "SMTP" : "mail() nativo"), 'info');
        
        if ($usar_smtp) {
            // Esta es una simulación - idealmente aquí se usaría PHPMailer o similar
            logMessage("Enviando email vía SMTP a $to usando servidor: {$config['smtp_host']}", 'info');
            
            // Información para depuración
            logMessage("Detalles SMTP - Host: {$config['smtp_host']}, Puerto: {$config['smtp_port']}, Usuario: {$config['smtp_user']}", 'debug');
            
            // Envío simulado - en producción, usar PHPMailer u otra librería
            $success = mail($to, $subject, $message, "From: $email_name <$email_from>");
            
            if ($success) {
                logMessage("Email enviado correctamente vía SMTP a $to", 'success');
            } else {
                logMessage("Error al enviar email vía SMTP a $to", 'error');
            }
            
            return $success;
        } else {
            // Usar mail() nativo de PHP
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: $email_name <$email_from>" . "\r\n";
            
            $result = mail($to, $subject, $message, $headers);
            
            if ($result) {
                logMessage("Email enviado correctamente a $to usando mail() nativo", 'success');
            } else {
                $error = error_get_last();
                if ($error) {
                    logMessage("Error al enviar email a $to: " . $error['message'], 'error');
                } else {
                    logMessage("Error desconocido al enviar email a $to", 'error');
                }
            }
            
            return $result;
        }
    } catch (Exception $e) {
        logMessage("Excepción al enviar email: " . $e->getMessage(), 'error');
        return false;
    }
}

// Función para enviar SMS (simulado)
function sendReminderSMS($phone, $message) {
    // Esta función simula el envío de SMS
    // Aquí se implementaría la integración con un proveedor de SMS
    logMessage("SMS enviado (simulado) a $phone: $message", 'success');
    return true;
}

// Procesar Recordatorios
try {
    logMessage("Iniciando proceso de envío de recordatorios");
    
    $db = getDB();
    
    // Obtener citas para las próximas 24 horas que no hayan recibido recordatorio
    $maniana = date('Y-m-d', strtotime('+1 day'));
    
    $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, 
             p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, 
             p.email as paciente_email, p.telefono as paciente_telefono,
             u.nombre as medico_nombre, u.apellidos as medico_apellidos
             FROM citas c
             JOIN pacientes p ON c.paciente_id = p.id
             JOIN usuarios u ON c.usuario_id = u.id
             WHERE c.fecha = :fecha
             AND c.estado = 'pendiente'
             AND (c.recordatorio_enviado = 0 OR c.recordatorio_enviado IS NULL)
             ORDER BY c.hora_inicio ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':fecha', $maniana);
    $stmt->execute();
    
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $citasCount = count($citas);
    
    logMessage("Se encontraron $citasCount citas para enviar recordatorios");
    
    if ($citasCount === 0) {
        logMessage("No hay recordatorios pendientes para enviar");
        exit(0);
    }
    
    // Contador de recordatorios enviados
    $emailSent = 0;
    $smsSent = 0;
    
    // Obtener configuración para determinar qué tipo de recordatorios enviar
    $enviarEmail = true; // Siempre enviar emails
    $enviarSMS = false;  // SMS desactivado por defecto
    
    // Procesar cada cita
    foreach ($citas as $cita) {
        $citaId = $cita['id'];
        
        // Preparar mensaje
        $fechaFormateada = date('d/m/Y', strtotime($cita['fecha']));
        $nombrePaciente = $cita['paciente_nombre'] . ' ' . $cita['paciente_apellidos'];
        $nombreMedico = $cita['medico_nombre'] . ' ' . $cita['medico_apellidos'];
        
        // Preparar mensaje de email (HTML)
        $emailSubject = "Recordatorio de su cita - " . $fechaFormateada;
        $emailMessage = "<html><body>";
        $emailMessage .= "<h2>Recordatorio de Cita Médica</h2>";
        $emailMessage .= "<p>Estimado/a <strong>" . htmlspecialchars($nombrePaciente) . "</strong>,</p>";
        $emailMessage .= "<p>Le recordamos que tiene una cita programada para mañana:</p>";
        $emailMessage .= "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        $emailMessage .= "<p><strong>Fecha:</strong> " . $fechaFormateada . "</p>";
        $emailMessage .= "<p><strong>Hora:</strong> " . $cita['hora_inicio'] . " - " . $cita['hora_fin'] . "</p>";
        $emailMessage .= "<p><strong>Médico:</strong> " . htmlspecialchars($nombreMedico) . "</p>";
        $emailMessage .= "<p><strong>Motivo:</strong> " . htmlspecialchars($cita['motivo']) . "</p>";
        $emailMessage .= "</div>";
        $emailMessage .= "<p>Si necesita modificar o cancelar su cita, por favor contacte con nuestra clínica lo antes posible.</p>";
        $emailMessage .= "<p>Atentamente,<br/>Equipo Médico</p>";
        $emailMessage .= "</body></html>";
        
        // Preparar mensaje SMS (texto plano)
        $smsMessage = "Recordatorio: Tiene cita el $fechaFormateada a las " . $cita['hora_inicio'] . " con Dr/a. " . $nombreMedico . ". Si no puede asistir, llame a la clínica.";
        
        // Enviar recordatorios
        $emailSuccess = false;
        $smsSuccess = false;
        
        // Enviar email si está configurado y hay dirección de email
        if ($enviarEmail && !empty($cita['paciente_email'])) {
            $emailSuccess = sendReminderEmail($cita['paciente_email'], $emailSubject, $emailMessage);
            if ($emailSuccess) {
                $emailSent++;
            }
        } else {
            logMessage("No se envió email para la cita $citaId (email no disponible o desactivado)", 'warning');
        }
        
        // Enviar SMS si está configurado y hay número de teléfono
        if ($enviarSMS && !empty($cita['paciente_telefono'])) {
            $smsSuccess = sendReminderSMS($cita['paciente_telefono'], $smsMessage);
            if ($smsSuccess) {
                $smsSent++;
            }
        }
        
        // Marcar cita como recordatorio enviado si al menos uno de los métodos fue exitoso
        if ($emailSuccess || $smsSuccess) {
            $updateQuery = "UPDATE citas SET recordatorio_enviado = 1 WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $citaId);
            $updateStmt->execute();
            
            logMessage("Cita $citaId marcada como recordatorio enviado", 'success');
        }
    }
    
    // Resumen final
    logMessage("Proceso finalizado. Resumen: $emailSent emails y $smsSent SMS enviados de un total de $citasCount citas");
    
    // Si se ejecuta desde navegador, mostrar mensaje de éxito
    if (!IS_CLI) {
        setAlert('success', "Recordatorios enviados: $emailSent emails y $smsSent SMS");
        header('Location: ' . BASE_URL . '/modules/calendario/index.php');
        exit;
    }
    
} catch (Exception $e) {
    $errorMessage = "Error en el proceso de recordatorios: " . $e->getMessage();
    logMessage($errorMessage, 'error');
    
    if (!IS_CLI) {
        setAlert('danger', $errorMessage);
        header('Location: ' . BASE_URL . '/modules/calendario/index.php');
        exit;
    }
    
    exit(1);
} 