<?php
// Permitir CORS para desarrollo y peticiones cross-origin en Firefox
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir archivos necesarios
require_once '../../../includes/config.php';
require_once '../../../includes/db.php';

// Recibir datos GET o POST
$salaId = isset($_GET['sala_id']) ? $_GET['sala_id'] : (isset($_POST['sala_id']) ? $_POST['sala_id'] : null);
$lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : (isset($_POST['last_id']) ? intval($_POST['last_id']) : 0);

// ID del usuario (0 si no hay sesión)
$currentUser = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

// Verificar datos necesarios
if (!$salaId) {
    echo json_encode([
        'success' => false,
        'message' => 'Falta el ID de la sala'
    ]);
    exit;
}

try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Verificar si existe la tabla videoconsulta_signaling
    $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_signaling'");
    if ($stmt->rowCount() === 0) {
        // Tabla no existe, intentar con la tabla antigua
        $stmt = $db->query("SHOW TABLES LIKE 'videoconsulta_signals'");
        if ($stmt->rowCount() === 0) {
            // Ninguna tabla existe
            echo json_encode([
                'success' => false,
                'message' => 'Las tablas de señalización no existen'
            ]);
            exit;
        } else {
            // Usar la tabla vieja, que tiene una estructura diferente
            $tableName = 'videoconsulta_signals';
            
            // Obtener señales nuevas - Ya no filtramos por usuario_id para permitir que funcione la señalización
            $stmt = $db->prepare("
                SELECT id, sala_id, data
                FROM $tableName 
                WHERE sala_id = :sala_id 
                AND id > :last_id 
                ORDER BY id ASC
            ");
            
            $stmt->bindParam(':sala_id', $salaId);
            $stmt->bindParam(':last_id', $lastId);
            
            $stmt->execute();
            $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar como leídas
            if (!empty($signals)) {
                $ids = array_map(function($signal) {
                    return $signal['id'];
                }, $signals);
                
                $idList = implode(',', $ids);
                
                $db->query("UPDATE $tableName SET leido = 1 WHERE id IN ($idList)");
            }
            
            echo json_encode([
                'success' => true,
                'signals' => $signals,
                'table_used' => $tableName
            ]);
            exit;
        }
    } else {
        $tableName = 'videoconsulta_signaling';
    }
    
    // Obtener señales nuevas de la tabla nueva
    $stmt = $db->prepare("
        SELECT id, sala_id, data
        FROM $tableName 
        WHERE sala_id = :sala_id 
        AND id > :last_id 
        ORDER BY id ASC
    ");
    
    $stmt->bindParam(':sala_id', $salaId);
    $stmt->bindParam(':last_id', $lastId);
    $stmt->execute();
    
    $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'signals' => $signals,
        'table_used' => $tableName
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} 