<?php
require_once '../../includes/config.php';

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar si se proporcionaron los parámetros necesarios
if (!isset($_POST['id']) || !isset($_POST['pagada'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

$id = (int)$_POST['id'];
$pagada = (int)$_POST['pagada'];

// Validar que pagada sea 0 o 1
if ($pagada !== 0 && $pagada !== 1) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'El valor de pagada debe ser 0 o 1']);
    exit;
}

try {
    $db = new Database();
    
    // Verificar que la cita exista
    $db->query("SELECT id FROM citas WHERE id = :id");
    $db->bind(':id', $id);
    $db->execute();
    
    if ($db->rowCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
        exit;
    }
    
    // Actualizar el estado de pago
    $db->query("UPDATE citas SET pagada = :pagada WHERE id = :id");
    $db->bind(':pagada', $pagada);
    $db->bind(':id', $id);
    
    if ($db->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado de pago actualizado correctamente']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de pago']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} 