<?php
/**
 * API para enviar mensajes de chat en la videoconsulta
 */

// Incluir archivos necesarios
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/db.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['videoconsulta_id']) || !isset($input['mensaje']) || empty(trim($input['mensaje']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$videoconsulta_id = (int)$input['videoconsulta_id'];
$mensaje = trim($input['mensaje']);
$usuario_id = $_SESSION['usuario_id'];

try {
    $db = getDB();
    
    // Verificar que la videoconsulta existe y el usuario tiene acceso
    $query = "
        SELECT v.*, c.paciente_id, c.usuario_id as medico_id
        FROM videoconsultas v
        JOIN citas c ON v.cita_id = c.id
        WHERE v.id = :videoconsulta_id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':videoconsulta_id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoconsulta) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Videoconsulta no encontrada']);
        exit;
    }
    
    // Verificar que el usuario tiene acceso a esta videoconsulta
    $tieneAcceso = false;
    
    if (esAdmin()) {
        $tieneAcceso = true;
    } else if ($videoconsulta['medico_id'] == $usuario_id) {
        $tieneAcceso = true;
    } else {
        // Para pacientes, verificar por email
        $stmt = $db->prepare("SELECT id FROM pacientes WHERE id = :paciente_id AND email = :email");
        $stmt->bindParam(':paciente_id', $videoconsulta['paciente_id'], PDO::PARAM_INT);
        $stmt->bindParam(':email', $_SESSION['usuario_email'], PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $tieneAcceso = true;
        }
    }
    
    if (!$tieneAcceso) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene permiso para enviar mensajes en esta videoconsulta']);
        exit;
    }
    
    // Verificar si existe la tabla
    $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_mensajes'");
    if ($stmt->rowCount() === 0) {
        // Crear la tabla si no existe
        $db->exec("
        CREATE TABLE IF NOT EXISTS `videoconsulta_mensajes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `videoconsulta_id` int(11) NOT NULL,
          `usuario_id` int(11) NOT NULL,
          `mensaje` text NOT NULL,
          `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `videoconsulta_id` (`videoconsulta_id`),
          KEY `usuario_id` (`usuario_id`),
          CONSTRAINT `fk_mensaje_videoconsulta` FOREIGN KEY (`videoconsulta_id`) REFERENCES `videoconsultas` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_mensaje_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    // Insertar el mensaje
    $stmt = $db->prepare("
        INSERT INTO videoconsulta_mensajes (videoconsulta_id, usuario_id, mensaje)
        VALUES (:videoconsulta_id, :usuario_id, :mensaje)
    ");
    
    $stmt->bindParam(':videoconsulta_id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);
    $stmt->execute();
    
    $mensaje_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Mensaje enviado correctamente',
        'mensaje_id' => $mensaje_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje: ' . $e->getMessage()]);
}
?> 