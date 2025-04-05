<?php
/**
 * API para finalizar una videoconsulta
 * 
 * Este script actualiza el estado de una videoconsulta a 'finalizada'
 * y registra la duración y fecha de finalización.
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

if (!$input || !isset($input['videoconsulta_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de videoconsulta no especificado']);
    exit;
}

$videoconsulta_id = (int)$input['videoconsulta_id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    $db = getDB();
    
    // Verificar que la videoconsulta existe y el usuario tiene acceso
    $query = "
        SELECT v.*, c.paciente_id, c.usuario_id as medico_id,
               v.fecha_inicio
        FROM videoconsultas v
        JOIN citas c ON v.cita_id = c.id
        WHERE v.id = :videoconsulta_id
        AND v.estado IN ('programada', 'en_curso')
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':videoconsulta_id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoconsulta) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Videoconsulta no encontrada o ya finalizada']);
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
        echo json_encode(['success' => false, 'message' => 'No tiene permiso para finalizar esta videoconsulta']);
        exit;
    }
    
    // Calcular duración de la videoconsulta
    $fecha_fin = date('Y-m-d H:i:s');
    $duracion = null;
    
    if ($videoconsulta['fecha_inicio']) {
        $inicio = new DateTime($videoconsulta['fecha_inicio']);
        $fin = new DateTime($fecha_fin);
        $intervalo = $inicio->diff($fin);
        $duracion = ($intervalo->h * 60) + $intervalo->i; // Duración en minutos
    }
    
    // Actualizar el estado de la videoconsulta
    $stmt = $db->prepare("
        UPDATE videoconsultas 
        SET estado = 'finalizada', 
            fecha_fin = :fecha_fin,
            duracion = :duracion
        WHERE id = :id
    ");
    
    $stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
    $stmt->bindParam(':duracion', $duracion, PDO::PARAM_INT);
    $stmt->bindParam(':id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Videoconsulta finalizada correctamente',
        'duracion' => $duracion
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al finalizar la videoconsulta: ' . $e->getMessage()]);
}
?> 