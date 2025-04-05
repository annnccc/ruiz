<?php
/**
 * Funciones para manejar el envío de recordatorios de citas
 */

// Definir ROOT_PATH si no está definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
}

require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';

/**
 * Envía un recordatorio de cita por email
 * @param int $cita_id ID de la cita
 * @return bool True si se envió correctamente, false si hubo un error
 */
function enviarRecordatorioCita($cita_id) {
    $db = getDB();
    
    // Obtener datos de la cita
    $query = "SELECT c.*, p.nombre, p.apellidos, p.email, s.nombre as servicio_nombre 
              FROM citas c 
              JOIN pacientes p ON c.paciente_id = p.id 
              LEFT JOIN servicios s ON c.servicio_id = s.id 
              WHERE c.id = :cita_id";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cita_id', $cita_id);
    $stmt->execute();
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita || empty($cita['email'])) {
        return false;
    }
    
    // Obtener la configuración de los recordatorios
    $configQuery = "SELECT * FROM recordatorios_config WHERE activo = 1 LIMIT 1";
    $configStmt = $db->query($configQuery);
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        return false;
    }
    
    // Preparar el mensaje
    $mensaje = $config['mensaje_email'];
    $asunto = $config['asunto_email'];
    
    // Reemplazar variables
    $mensaje = str_replace('{paciente}', $cita['nombre'] . ' ' . $cita['apellidos'], $mensaje);
    $mensaje = str_replace('{fecha}', formatDateToView($cita['fecha']), $mensaje);
    $mensaje = str_replace('{hora}', $cita['hora_inicio'], $mensaje);
    $mensaje = str_replace('{servicio}', $cita['servicio_nombre'], $mensaje);
    
    // Obtener el nombre de la clínica
    $nombre_clinica = "Clínica";
    try {
        $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'nombre_sistema' LIMIT 1");
        if ($nombre = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $nombre_clinica = $nombre;
        }
    } catch (PDOException $e) {
        // Usar valor por defecto
    }
    $mensaje = str_replace('{clinica}', $nombre_clinica, $mensaje);
    
    // Formatear el mensaje para HTML
    $mensajeHTML = nl2br($mensaje);
    
    // Añadir estilos al email
    $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">';
    $html .= '<h2 style="color: #0d6efd;">Recordatorio de su cita</h2>';
    $html .= '<div style="margin: 15px 0;">' . $mensajeHTML . '</div>';
    $html .= '<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">';
    $html .= '<p><strong>Fecha:</strong> ' . formatDateToView($cita['fecha']) . '</p>';
    $html .= '<p><strong>Hora:</strong> ' . $cita['hora_inicio'] . ' - ' . $cita['hora_fin'] . '</p>';
    if (!empty($cita['servicio_nombre'])) {
        $html .= '<p><strong>Servicio:</strong> ' . htmlspecialchars($cita['servicio_nombre']) . '</p>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    // Cargar la función enviarEmail desde el módulo de consentimientos si no está ya disponible
    if (!function_exists('enviarEmail')) {
        require_once ROOT_PATH . '/modules/consentimientos/funciones.php';
    }
    
    // Usar la función enviarEmail que funciona con los consentimientos, indicando que es un recordatorio
    $success = enviarEmail($cita['email'], $asunto, $mensaje, "Recordatorio de su cita");
    
    // Registrar el envío en la base de datos
    $stmt = $db->prepare("INSERT INTO recordatorios_enviados (cita_id, tipo, fecha_envio, estado, mensaje_error) 
                          VALUES (:cita_id, 'email', NOW(), :estado, :error)");
    $estado = $success ? 'enviado' : 'error';
    $error = $success ? null : 'Error al enviar email';
    
    $stmt->bindParam(':cita_id', $cita_id);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':error', $error);
    $stmt->execute();
    
    return $success;
}

/**
 * Busca citas que necesitan recordatorio y envía los emails
 * @return array Array con los resultados de los envíos
 */
function procesarRecordatoriosPendientes() {
    $db = getDB();
    $resultados = [
        'total' => 0,
        'enviados' => 0,
        'errores' => 0,
        'detalles' => []
    ];
    
    // Obtener configuración activa
    $configQuery = "SELECT * FROM recordatorios_config WHERE activo = 1 LIMIT 1";
    $configStmt = $db->query($configQuery);
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        return $resultados;
    }
    
    // Calcular fecha para la que vamos a enviar recordatorios
    $fecha_recordatorio = date('Y-m-d', strtotime("+{$config['dias_anticipacion']} days"));
    
    // Obtener citas para esa fecha que no tengan recordatorio enviado
    $query = "SELECT c.* FROM citas c 
              LEFT JOIN recordatorios_enviados r ON c.id = r.cita_id
              JOIN pacientes p ON c.paciente_id = p.id
              WHERE c.fecha = :fecha AND c.estado != 'cancelada' 
              AND p.email IS NOT NULL AND p.email != ''
              AND r.id IS NULL";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':fecha', $fecha_recordatorio);
    $stmt->execute();
    
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultados['total'] = count($citas);
    
    foreach ($citas as $cita) {
        $success = enviarRecordatorioCita($cita['id']);
        
        if ($success) {
            $resultados['enviados']++;
        } else {
            $resultados['errores']++;
        }
        
        $resultados['detalles'][] = [
            'cita_id' => $cita['id'],
            'fecha' => $cita['fecha'],
            'resultado' => $success ? 'enviado' : 'error'
        ];
    }
    
    // Obtener estadísticas de recordatorios
    $query = "SELECT COUNT(*) as total FROM recordatorios_enviados WHERE estado = 'error'";
    $stmt = $db->query($query);
    $total_errores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $resultados;
}

/**
 * Envía un recordatorio manual para una cita específica
 * Permite enviar recordatorios individuales sin importar la fecha programada
 * @param int $cita_id ID de la cita
 * @return bool True si se envió correctamente, false si hubo un error
 */
function enviarRecordatorioManual($cita_id) {
    // Utilizamos la misma función de envío pero marcamos como manual en la descripción
    $resultado = enviarRecordatorioCita($cita_id);
    
    if ($resultado) {
        // Actualizamos para indicar que fue manual, pero manteniendo el tipo como 'email'
        $db = getDB();
        $stmt = $db->prepare("UPDATE recordatorios_enviados SET mensaje_error = 'Enviado manualmente' WHERE cita_id = :cita_id ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(':cita_id', $cita_id);
        $stmt->execute();
    }
    
    return $resultado;
} 