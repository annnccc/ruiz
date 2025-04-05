<?php
/**
 * API para obtener señales WebRTC
 * 
 * Este script permite a los clientes obtener mensajes de señalización
 * pendientes para establecer la conexión WebRTC.
 */

// Incluir archivos necesarios
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/db.php';

// Ya no verificamos autenticación para permitir acceso a las señales
// Comentamos o eliminamos el siguiente bloque:
/*
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}
*/

// Verificar parámetros requeridos
if (!isset($_GET['sala_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de sala no especificado']);
    exit;
}

$sala_id = $_GET['sala_id'];
// Usar usuario_id de la sesión o un valor por defecto si no hay sesión
$usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : null;

try {
    $db = getDB();
    
    // Verificar si existe la tabla
    $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_signals'");
    if ($stmt->rowCount() === 0) {
        // Crear la tabla si no existe
        $db->exec("
        CREATE TABLE IF NOT EXISTS `videoconsulta_signals` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `sala_id` varchar(50) NOT NULL,
          `usuario_id` int(11) NOT NULL,
          `data` text NOT NULL,
          `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `leido` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `sala_id` (`sala_id`),
          KEY `usuario_id` (`usuario_id`),
          KEY `leido` (`leido`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Tabla recién creada, no hay mensajes
        if ($type) {
            echo json_encode(['success' => true, 'signal' => null]);
        } else {
            echo json_encode(['success' => true, 'signals' => []]);
        }
        exit;
    }
    
    // Si se solicita un tipo específico (por ejemplo, 'offer')
    if ($type) {
        $stmt = $db->prepare("
            SELECT * FROM videoconsulta_signals 
            WHERE sala_id = :sala_id 
            AND data LIKE :type
            AND leido = 0
            ORDER BY fecha ASC
            LIMIT 1
        ");
        
        $type_pattern = '%"type":"' . $type . '"%';
        $stmt->bindParam(':sala_id', $sala_id, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type_pattern, PDO::PARAM_STR);
        $stmt->execute();
        
        $signal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($signal) {
            // Marcar como leído
            $updateStmt = $db->prepare("UPDATE videoconsulta_signals SET leido = 1 WHERE id = :id");
            $updateStmt->bindParam(':id', $signal['id'], PDO::PARAM_INT);
            $updateStmt->execute();
            
            echo json_encode(['success' => true, 'signal' => $signal]);
        } else {
            echo json_encode(['success' => true, 'signal' => null]);
        }
    } else {
        // Obtener todos los mensajes no leídos para la sala
        $stmt = $db->prepare("
            SELECT * FROM videoconsulta_signals 
            WHERE sala_id = :sala_id 
            AND leido = 0
            ORDER BY fecha ASC
            LIMIT 10
        ");
        
        $stmt->bindParam(':sala_id', $sala_id, PDO::PARAM_STR);
        $stmt->execute();
        
        $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($signals)) {
            // Marcar como leídos
            $ids = array_column($signals, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $updateStmt = $db->prepare("UPDATE videoconsulta_signals SET leido = 1 WHERE id IN ($placeholders)");
            foreach ($ids as $i => $id) {
                $updateStmt->bindValue($i + 1, $id, PDO::PARAM_INT);
            }
            $updateStmt->execute();
        }
        
        echo json_encode(['success' => true, 'signals' => $signals]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener señales: ' . $e->getMessage()]);
}
?> 