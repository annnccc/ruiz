<?php
require_once '../../includes/config.php';

// Prevenir acceso directo sin ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de cita no proporcionado']);
    exit;
}

$id = (int)$_GET['id'];

try {
    // Obtener conexión a la base de datos
    $db = getDB();
    
    // Consultar la cita junto con los datos del paciente
    $query = "SELECT c.*, CONCAT(p.nombre, ' ', p.apellidos) as paciente 
              FROM citas c 
              JOIN pacientes p ON c.paciente_id = p.id 
              WHERE c.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Cita no encontrada']);
        exit;
    }
    
    // Formatear datos para la respuesta
    $response = [
        'id' => $cita['id'],
        'paciente' => $cita['paciente'],
        'fecha' => formatDateToView($cita['fecha']),
        'hora_inicio' => $cita['hora_inicio'],
        'hora_fin' => $cita['hora_fin'],
        'motivo' => $cita['motivo'],
        'estado' => $cita['estado'],
        'notas' => $cita['notas']
    ];
    
    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]);
}
?> 