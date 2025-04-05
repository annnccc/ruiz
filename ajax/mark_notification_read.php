<?php
/**
 * API para marcar una notificación como leída
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

// Verificar si se recibió el ID de la notificación
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta el ID de la notificación']);
    exit;
}

$notificationId = $_POST['id'];
$usuario_id = $_SESSION['usuario_id'];

try {
    // En una implementación completa, aquí se guardaría en la base de datos
    // que esta notificación fue leída por este usuario
    
    // Para este ejemplo, simplemente devolvemos éxito
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 