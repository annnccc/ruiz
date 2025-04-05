<?php
/**
 * API para obtener mensajes de chat en la videoconsulta
 * 
 * Permite recibir mensajes nuevos o no leídos para el chat durante la videoconsulta.
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

// Verificar parámetros requeridos
if (!isset($_GET['videoconsulta_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de videoconsulta no especificado']);
    exit;
}

$videoconsulta_id = (int)$_GET['videoconsulta_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
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
        echo json_encode(['success' => false, 'message' => 'No tiene permiso para acceder a esta videoconsulta']);
        exit;
    }
    
    // Verificar si existe la tabla
    $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_mensajes'");
    if ($stmt->rowCount() === 0) {
        // La tabla no existe, no hay mensajes
        echo json_encode(['success' => true, 'messages' => []]);
        exit;
    }
    
    // Obtener mensajes nuevos (posteriores al último ID recibido)
    $stmt = $db->prepare("
        SELECT m.*, u.nombre, u.apellidos 
        FROM videoconsulta_mensajes m
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.videoconsulta_id = :videoconsulta_id
        AND m.id > :last_id
        ORDER BY m.fecha ASC
    ");
    
    $stmt->bindParam(':videoconsulta_id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->bindParam(':last_id', $last_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $mensajes]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener mensajes: ' . $e->getMessage()]);
}
?> 