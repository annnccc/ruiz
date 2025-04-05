<?php
/**
 * Módulo de Videoconsulta - Cancelar videoconsulta
 * 
 * Esta página permite cancelar una videoconsulta programada.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar autenticación y permisos
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Solo los médicos pueden cancelar videoconsultas
if ($_SESSION['tipo_usuario'] != 'medico') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para cancelar videoconsultas.']);
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Obtener datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);

// Verificar datos
if (!isset($input['videoconsulta_id']) || empty($input['videoconsulta_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de videoconsulta no especificado.']);
    exit;
}

// Obtener usuario actual
$usuario_id = $_SESSION['usuario_id'];
$videoconsulta_id = (int) $input['videoconsulta_id'];

try {
    $db = getDB();
    
    // Verificar que la videoconsulta existe y pertenece al médico
    $stmt = $db->prepare("
        SELECT * FROM videoconsultas 
        WHERE id = :id 
        AND medico_id = :usuario_id
        AND estado = 'programada'
    ");
    
    $stmt->bindParam(':id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoconsulta) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'La videoconsulta no existe, no le pertenece o no está en estado "programada".']);
        exit;
    }
    
    // Cancelar la videoconsulta
    $stmt = $db->prepare("
        UPDATE videoconsultas 
        SET estado = 'cancelada' 
        WHERE id = :id
    ");
    
    $stmt->bindParam(':id', $videoconsulta_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Responder con éxito
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Videoconsulta cancelada correctamente.']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al cancelar la videoconsulta: ' . $e->getMessage()]);
} 