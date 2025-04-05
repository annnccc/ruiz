<?php
/**
 * API para verificar si existe la tabla notificaciones_leidas
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Establecer cabeceras para JSON
header('Content-Type: application/json');

// Inicializar respuesta
$response = [
    'exists' => false,
    'message' => ''
];

try {
    $db = getDB();
    
    // Comprobar si la tabla existe
    $stmt = $db->prepare("
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'notificaciones_leidas'
    ");
    $stmt->execute();
    
    $response['exists'] = ($stmt->rowCount() > 0);
    $response['message'] = $response['exists'] 
        ? 'La tabla notificaciones_leidas existe' 
        : 'La tabla notificaciones_leidas no existe';
    
} catch (PDOException $e) {
    $response['message'] = 'Error al verificar la tabla: ' . $e->getMessage();
}

// Devolver respuesta JSON
echo json_encode($response);
exit;
?> 