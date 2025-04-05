<?php
/**
 * API para marcar todas las notificaciones como leídas
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Establecer cabeceras para JSON
header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

try {
    // En una implementación completa, aquí se guardaría en la base de datos
    // que todas las notificaciones fueron leídas por este usuario
    
    // Para este ejemplo, simplemente devolvemos éxito
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 