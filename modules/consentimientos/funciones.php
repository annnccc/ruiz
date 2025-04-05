<?php
// Usar la constante ROOT_PATH para rutas absolutas si está definida
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
}

require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';

/**
 * Envía un email utilizando SMTP configurable o mail() nativo
 * @param string $destinatario Email del destinatario
 * @param string $asunto Asunto del email
 * @param string $mensaje Contenido del email (HTML)
 * @param string $titulo Título del email (opcional, por defecto "Consentimiento Informado")
 * @return bool True si se envió correctamente, false si hubo un error
 */
function enviarEmail($destinatario, $asunto, $mensaje, $titulo = "Consentimiento Informado") {
    try {
        // Obtener la configuración del sistema
        $db = getDB();
        $config = [];
        
        // Consultar todas las configuraciones relacionadas con email
        $stmt = $db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_sistema', 'smtp_active', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_name')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }
        
        // Valores por defecto
        $nombre_clinica = $config['nombre_sistema'] ?? "Clínica";
        $email_from = $config['email_from'] ?? "no-reply@" . $_SERVER['HTTP_HOST'];
        $email_name = $config['email_name'] ?? $nombre_clinica;
        
        // Formatear el mensaje para HTML
        $mensajeHTML = nl2br($mensaje);
        
        // Añadir estilos al email
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">';
        $html .= '<h2 style="color: #0d6efd;">' . htmlspecialchars($titulo) . '</h2>';
        $html .= '<div style="margin: 15px 0;">' . $mensajeHTML . '</div>';
        $html .= '<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">';
        $html .= '<p style="color: #666; font-size: 0.9em;">Este mensaje ha sido enviado desde el sistema de gestión de ' . htmlspecialchars($nombre_clinica) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Verificar si usar SMTP o mail() nativo
        $usar_smtp = !empty($config['smtp_active']) && $config['smtp_active'] == '1' && 
                     !empty($config['smtp_host']) && !empty($config['smtp_user']);
        
        // Si no hay configuración SMTP o está desactivada, usar mail() nativo
        if (!$usar_smtp) {
            // Configurar cabeceras
            $headers = "From: " . $email_name . " <" . $email_from . ">\r\n";
            $headers .= "Reply-To: " . $email_from . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            // Enviar email usando mail() de PHP
            return mail($destinatario, $asunto, $html, $headers);
        } else {
            // Usar SMTP - Aquí iría un código usando una librería como PHPMailer
            // Por ahora, hacemos un fallback a mail() nativo
            $headers = "From: " . $email_name . " <" . $email_from . ">\r\n";
            $headers .= "Reply-To: " . $email_from . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            // Usar mail() como fallback hasta implementar PHPMailer
            return mail($destinatario, $asunto, $html, $headers);
            
            /* 
            // Ejemplo de código con PHPMailer (necesitaría ser instalado)
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->Port = $config['smtp_port'];
            $mail->setFrom($email_from, $email_name);
            $mail->addAddress($destinatario);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $html;
            return $mail->send();
            */
        }
    } catch (Exception $e) {
        // En caso de error, intentar enviar con mail() nativo como último recurso
        error_log("Error al enviar email: " . $e->getMessage());
        
        // Crear cabeceras simples
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($destinatario, $asunto, $mensaje, $headers);
    }
}

/**
 * Genera un token único para el consentimiento
 */
function generarTokenConsentimiento() {
    return bin2hex(random_bytes(32));
}

/**
 * Envía un consentimiento informado al paciente
 */
function enviarConsentimiento($paciente_id, $modelo_id, $email = null) {
    try {
        $db = getDB();
        
        // Verificar si el paciente existe
        $stmt = $db->prepare("SELECT nombre, email FROM pacientes WHERE id = ?");
        $stmt->execute([$paciente_id]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paciente) {
            return [
                'exito' => false,
                'mensaje' => 'Paciente no encontrado'
            ];
        }
        
        // Usar email del paciente si no se proporciona uno específico
        if (empty($email)) {
            $email = $paciente['email'];
        }
        
        if (empty($email)) {
            return [
                'exito' => false,
                'mensaje' => 'No hay dirección de email disponible para este paciente'
            ];
        }
        
        // Verificar si el modelo existe
        $stmt = $db->prepare("SELECT nombre FROM consentimientos_modelos WHERE id = ? AND activo = 1");
        $stmt->execute([$modelo_id]);
        $modelo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$modelo) {
            return [
                'exito' => false,
                'mensaje' => 'Modelo de consentimiento no encontrado o inactivo'
            ];
        }
        
        // Generar token único
        $token = generarTokenConsentimiento();
        
        // Calcular fecha de caducidad (7 días después)
        $fecha_caducidad = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        // Registrar consentimiento
        $stmt = $db->prepare("
            INSERT INTO consentimientos_enviados 
            (paciente_id, modelo_id, token, email, enviado_por, fecha_caducidad) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $paciente_id, 
            $modelo_id, 
            $token, 
            $email, 
            $_SESSION['usuario_id'],
            $fecha_caducidad
        ]);
        
        $consentimiento_id = $db->lastInsertId();
        
        // Forzar URL específica para consentimientos
        $base_url = 'https://app.ruizarrietapsicologia.com/ruiz';
        
        // Construir URL para firma
        $url_firma = $base_url . '/publico/consentimiento.php?token=' . $token;
        
        // Enviar email al paciente
        $asunto = "Solicitud de firma de Consentimiento Informado";
        $mensaje = "
        <p>Estimado/a {$paciente['nombre']},</p>
        <p>Le enviamos este correo para solicitarle su consentimiento informado para <strong>{$modelo['nombre']}</strong>.</p>
        <p>Por favor haga clic en el siguiente enlace para ver y firmar el documento:</p>
        <p><a href='{$url_firma}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ver y firmar documento</a></p>
        <p>O copie y pegue esta dirección en su navegador: {$url_firma}</p>
        <p><strong>IMPORTANTE:</strong> Este enlace caducará en 7 días. Por favor, complete el proceso antes de esa fecha.</p>
        <p>Si tiene alguna duda, no dude en contactarnos.</p>
        <p>Atentamente,<br>El equipo médico</p>
        ";
        
        $enviado = enviarEmail($email, $asunto, $mensaje);
        
        if ($enviado) {
            return [
                'exito' => true,
                'mensaje' => 'Consentimiento enviado correctamente. Caducará en 7 días.',
                'id' => $consentimiento_id,
                'url' => $url_firma
            ];
        } else {
            // Si falla el envío, marcar como caducado para no dejar tokens activos sin email
            $stmt = $db->prepare("
                UPDATE consentimientos_enviados 
                SET estado = 'caducado' 
                WHERE id = ?
            ");
            $stmt->execute([$consentimiento_id]);
            
            return [
                'exito' => false,
                'mensaje' => 'Error al enviar el email. Verifique la dirección de correo.'
            ];
        }
    } catch (PDOException $e) {
        return [
            'exito' => false,
            'mensaje' => 'Error en la base de datos: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtiene un consentimiento por su token
 */
function obtenerConsentimientoPorToken($token) {
    try {
        $db = getDB();
        
        // Primero verificar si hay consentimientos caducados
        verificarConsentimientosCaducados();
        
        $stmt = $db->prepare("
            SELECT ce.*, cm.nombre as modelo_nombre, cm.contenido, 
                   p.nombre as paciente_nombre, p.apellidos as paciente_apellidos
            FROM consentimientos_enviados ce
            JOIN consentimientos_modelos cm ON ce.modelo_id = cm.id
            JOIN pacientes p ON ce.paciente_id = p.id
            WHERE ce.token = ? AND ce.estado = 'pendiente'
        ");
        $stmt->execute([$token]);
        
        $consentimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si el consentimiento existe, verificar que no haya caducado
        if ($consentimiento) {
            $ahora = new DateTime();
            $caducidad = new DateTime($consentimiento['fecha_caducidad']);
            
            if ($ahora > $caducidad) {
                // Actualizar el estado a caducado
                $db->prepare("UPDATE consentimientos_enviados SET estado = 'caducado' WHERE id = ?")->execute([$consentimiento['id']]);
                return false; // Consentimiento caducado
            }
        }
        
        return $consentimiento;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Registra la firma de un consentimiento
 */
function registrarFirmaConsentimiento($consentimiento_id, $firma_imagen, $nombre_firmante) {
    try {
        $db = getDB();
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Insertar firma
        $stmt = $db->prepare("
            INSERT INTO consentimientos_firmas 
            (consentimiento_id, firma_imagen, nombre_firmante) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$consentimiento_id, $firma_imagen, $nombre_firmante]);
        
        // Actualizar estado del consentimiento
        $stmt = $db->prepare("
            UPDATE consentimientos_enviados 
            SET estado = 'firmado', 
                fecha_firma = NOW(), 
                ip_firma = ? 
            WHERE id = ?
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR'], $consentimiento_id]);
        
        // Confirmar transacción
        $db->commit();
        
        return true;
    } catch (PDOException $e) {
        // Revertir en caso de error
        $db->rollBack();
        return false;
    }
}

/**
 * Lista los consentimientos de un paciente
 */
function listarConsentimientosPaciente($paciente_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT ce.*, cm.nombre as modelo_nombre, u.nombre as usuario_nombre
            FROM consentimientos_enviados ce
            JOIN consentimientos_modelos cm ON ce.modelo_id = cm.id
            JOIN usuarios u ON ce.enviado_por = u.id
            WHERE ce.paciente_id = ?
            ORDER BY ce.fecha_envio DESC
        ");
        $stmt->execute([$paciente_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Obtiene los modelos de consentimiento activos
 */
function obtenerModelosConsentimiento() {
    try {
        $db = getDB();
        
        $stmt = $db->query("
            SELECT * FROM consentimientos_modelos 
            WHERE activo = 1 
            ORDER BY nombre
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Procesa las variables de reemplazo en el contenido del consentimiento
 */
function procesarContenidoConsentimiento($contenido, $paciente, $fecha = null) {
    if ($fecha === null) {
        $fecha = time();
    } elseif (!is_numeric($fecha)) {
        $fecha = strtotime($fecha);
    }
    
    // Obtener datos adicionales del paciente si no están completos
    if (!isset($paciente['direccion']) || !isset($paciente['poblacion'])) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
            $stmt->execute([$paciente['paciente_id']]);
            $pacienteCompleto = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pacienteCompleto) {
                $paciente = array_merge($paciente, $pacienteCompleto);
            }
        } catch (PDOException $e) {
            // Continuar con los datos disponibles
        }
    }
    
    // Datos del paciente
    $nombre_completo = isset($paciente['nombre']) && isset($paciente['apellidos']) 
        ? $paciente['nombre'] . ' ' . $paciente['apellidos'] 
        : $paciente['paciente_nombre'] . ' ' . $paciente['paciente_apellidos'];
    
    $direccion = isset($paciente['direccion']) ? $paciente['direccion'] : '';
    if (isset($paciente['poblacion']) && !empty($paciente['poblacion'])) {
        $direccion .= ', ' . $paciente['poblacion'];
    }
    if (isset($paciente['codigo_postal']) && !empty($paciente['codigo_postal'])) {
        $direccion .= ', CP ' . $paciente['codigo_postal'];
    }
    
    // Formatear fecha en varias modalidades
    $dia = date('d', $fecha);
    $mes = date('m', $fecha);
    $anio = date('Y', $fecha);
    $nombre_mes = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
        '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
        '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
    ][$mes];
    
    // Reemplazar variables
    $reemplazos = [
        '{NOMBRE_PACIENTE}' => $nombre_completo,
        '{DIRECCION}' => $direccion,
        '{DIA}' => $dia,
        '{MES}' => $nombre_mes,
        '{ANO}' => $anio,
        '{AÑO}' => $anio,
        '{FECHA}' => date('d/m/Y', $fecha)
    ];
    
    // Si hay DNI disponible, añadirlo
    if (isset($paciente['dni']) && !empty($paciente['dni'])) {
        $reemplazos['{DNI_PACIENTE}'] = $paciente['dni'];
    } else {
        $reemplazos['{DNI_PACIENTE}'] = '________________';
    }
    
    // Reemplazar todas las variables
    return str_replace(array_keys($reemplazos), array_values($reemplazos), $contenido);
}

function procesarFirmaConsentimiento($token, $firma, $nombre_firmante) {
    try {
        $db = getDB();
        
        // Actualizar estado del consentimiento
        $stmt = $db->prepare("UPDATE consentimientos_enviados SET estado = 'firmado', fecha_firma = NOW(), ip_firma = :ip WHERE token = :token");
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Obtener la ID del consentimiento y del paciente
        $stmt = $db->prepare("SELECT id, paciente_id FROM consentimientos_enviados WHERE token = :token");
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        $consentimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$consentimiento) {
            return false;
        }
        
        // Guardar firma
        $stmt = $db->prepare("INSERT INTO consentimientos_firmas (consentimiento_id, firma_imagen, nombre_firmante, fecha_firma) VALUES (:consentimiento_id, :firma, :nombre, NOW())");
        $stmt->bindValue(':consentimiento_id', $consentimiento['id']);
        $stmt->bindValue(':firma', $firma);
        $stmt->bindValue(':nombre', $nombre_firmante);
        
        $result = $stmt->execute();
        
        // Actualizar el campo de consentimiento firmado en la tabla de pacientes
        if ($result) {
            $stmt = $db->prepare("UPDATE pacientes SET consentimiento_firmado = 1 WHERE id = :paciente_id");
            $stmt->bindValue(':paciente_id', $consentimiento['paciente_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        
        return $result;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Marca automáticamente como caducados los consentimientos pendientes
 * cuya fecha de caducidad ya ha pasado
 */
function verificarConsentimientosCaducados() {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            UPDATE consentimientos_enviados 
            SET estado = 'caducado' 
            WHERE estado = 'pendiente' 
            AND fecha_caducidad IS NOT NULL 
            AND fecha_caducidad < NOW()
        ");
        
        $stmt->execute();
        
        return $stmt->rowCount(); // Número de consentimientos actualizados
    } catch (PDOException $e) {
        return 0;
    }
} 