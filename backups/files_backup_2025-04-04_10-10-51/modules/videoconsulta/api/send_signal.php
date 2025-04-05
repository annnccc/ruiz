<?php
/**
 * API para enviar señales WebRTC
 * 
 * Este script maneja la señalización entre peers para establecer la conexión WebRTC.
 */

// Permitir CORS para desarrollo y peticiones cross-origin en Firefox
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivos necesarios
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

// Recibir datos POST como JSON
$postData = json_decode(file_get_contents('php://input'), true);

// Verificar datos necesarios
if (!isset($postData['sala_id']) || !isset($postData['data'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan datos requeridos',
        'received' => $postData
    ]);
    exit;
}

// Extraer datos
$salaId = $postData['sala_id'];
$data = $postData['data'];

try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Verificar si existe la tabla videoconsulta_signaling
    $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_signaling'");
    if ($stmt->rowCount() === 0) {
        // Tabla no existe, intentar con la tabla antigua
        $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_signals'");
        if ($stmt->rowCount() === 0) {
            // Crear tabla videoconsulta_signaling si no existe
            $db->exec("
            CREATE TABLE IF NOT EXISTS `videoconsulta_signaling` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `sala_id` varchar(50) NOT NULL,
              `data` text NOT NULL,
              `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `sala_id` (`sala_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $tableName = 'videoconsulta_signaling';
        } else {
            // Usar la tabla vieja, que tiene una estructura diferente pero usaremos un valor fijo para usuario_id
            $tableName = 'videoconsulta_signals';
            $usuario_id = 0; // Usar un valor por defecto
            
            $stmt = $db->prepare("
                INSERT INTO $tableName (sala_id, usuario_id, data) 
                VALUES (:sala_id, :usuario_id, :data)
            ");
            
            $stmt->bindParam(':sala_id', $salaId);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':data', $data);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'table_used' => $tableName
                ]);
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al guardar señal: ' . $errorInfo[2],
                    'error_code' => $errorInfo[0]
                ]);
            }
            
            exit;
        }
    } else {
        $tableName = 'videoconsulta_signaling';
    }
    
    // Guardar el mensaje en la tabla videoconsulta_signaling
    $stmt = $db->prepare("
        INSERT INTO $tableName (sala_id, data) 
        VALUES (:sala_id, :data)
    ");
    
    $stmt->bindParam(':sala_id', $salaId);
    $stmt->bindParam(':data', $data);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'table_used' => $tableName
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar señal: ' . $errorInfo[2],
            'error_code' => $errorInfo[0]
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?> 