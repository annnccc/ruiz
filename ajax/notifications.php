<?php
/**
 * API para obtener notificaciones del sistema
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Establecer cabeceras para JSON
header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'notifications' => [],
        'unreadCount' => 0,
        'message' => 'No autorizado'
    ]);
    exit;
}

try {
    $db = getDB();
    $usuario_id = $_SESSION['usuario_id'];
    $notifications = [];
    $unreadCount = 0;
    
    // Obtener fecha actual
    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    $hora_actual = date('H:i:s');
    
    // 1. Citas para hoy
    $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
              p.nombre, p.apellidos
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              WHERE c.fecha = :hoy AND c.hora_inicio >= :hora_actual AND c.estado = 'pendiente'
              ORDER BY c.hora_inicio ASC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hoy', $hoy);
    $stmt->bindParam(':hora_actual', $hora_actual);
    $stmt->execute();
    $citasHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($citasHoy as $cita) {
        $notifications[] = [
            'id' => 'cita_hoy_' . $cita['id'],
            'title' => 'Cita pendiente hoy',
            'message' => sprintf('%s a las %s - %s', 
                         $cita['apellidos'] . ', ' . $cita['nombre'],
                         $cita['hora_inicio'],
                         $cita['motivo']),
            'link' => BASE_URL . '/modules/citas/view.php?id=' . $cita['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'read' => false,
            'type' => 'appointment'
        ];
        $unreadCount++;
    }
    
    // 2. Citas para mañana
    $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
              p.nombre, p.apellidos
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              WHERE c.fecha = :manana AND c.estado = 'pendiente'
              ORDER BY c.hora_inicio ASC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':manana', $manana);
    $stmt->execute();
    $citasManana = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($citasManana as $cita) {
        $notifications[] = [
            'id' => 'cita_manana_' . $cita['id'],
            'title' => 'Cita programada para mañana',
            'message' => sprintf('%s a las %s - %s', 
                         $cita['apellidos'] . ', ' . $cita['nombre'],
                         $cita['hora_inicio'],
                         $cita['motivo']),
            'link' => BASE_URL . '/modules/citas/view.php?id=' . $cita['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'read' => false,
            'type' => 'appointment'
        ];
        $unreadCount++;
    }
    
    // Devolver la respuesta
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unreadCount' => $unreadCount
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'notifications' => [],
        'unreadCount' => 0,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 