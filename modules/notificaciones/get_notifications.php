<?php
/**
 * API para obtener notificaciones de citas pendientes y próximas
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación
requiereLogin();

// Establecer cabeceras para JSON
header('Content-Type: application/json');

// Inicializar respuesta
$response = [
    'success' => false,
    'notifications' => [],
    'total_unread' => 0,
    'message' => ''
];

try {
    $db = getDB();
    $notifications = [];
    $total_unread = 0;
    
    // Obtener ID del usuario actual
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    
    // Obtener la fecha actual y de mañana
    $hoy = date('Y-m-d');
    $manana = date('Y-m-d', strtotime('+1 day'));
    $hora_actual = date('H:i:s');
    $proxima_hora = date('H:i:s', strtotime('+2 hours'));
    
    // 1. Citas para hoy que aún no han pasado
    $stmt = $db->prepare("
        SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.estado, c.notas,
               p.id AS paciente_id, p.nombre, p.apellidos, p.telefono,
               s.nombre AS servicio_nombre,
               (SELECT MIN(id) FROM notificaciones_leidas WHERE usuario_id = :usuario_id AND cita_id = c.id) AS leida_id
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        LEFT JOIN servicios s ON c.servicio_id = s.id
        WHERE c.fecha = :hoy 
          AND c.estado = 'pendiente'
          AND c.hora_inicio >= :hora_actual
        ORDER BY c.hora_inicio ASC
        LIMIT 10
    ");
    $stmt->bindParam(':hoy', $hoy);
    $stmt->bindParam(':hora_actual', $hora_actual);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $citas_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($citas_hoy as $cita) {
        $is_read = !empty($cita['leida_id']);
        if (!$is_read) $total_unread++;
        
        // Determinar si es urgente (próxima en 2 horas)
        $es_urgente = ($cita['fecha'] == $hoy && strtotime($cita['hora_inicio']) <= strtotime($proxima_hora));
        
        $notifications[] = [
            'id' => 'cita_' . $cita['id'],
            'type' => $es_urgente ? 'urgent' : 'today',
            'title' => 'Cita hoy a las ' . formatTime($cita['hora_inicio']),
            'description' => $cita['apellidos'] . ', ' . $cita['nombre'] . 
                             ($cita['servicio_nombre'] ? ' - ' . $cita['servicio_nombre'] : ''),
            'time' => formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']),
            'url' => BASE_URL . '/modules/citas/view.php?id=' . $cita['id'],
            'icon' => $es_urgente ? 'event_upcoming' : 'today',
            'is_read' => $is_read,
            'entity_id' => $cita['id'],
            'entity_type' => 'cita'
        ];
    }
    
    // 2. Citas para mañana
    $stmt = $db->prepare("
        SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.estado, c.notas,
               p.id AS paciente_id, p.nombre, p.apellidos, p.telefono,
               s.nombre AS servicio_nombre,
               (SELECT MIN(id) FROM notificaciones_leidas WHERE usuario_id = :usuario_id AND cita_id = c.id) AS leida_id
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        LEFT JOIN servicios s ON c.servicio_id = s.id
        WHERE c.fecha = :manana 
          AND c.estado = 'pendiente'
        ORDER BY c.hora_inicio ASC
        LIMIT 5
    ");
    $stmt->bindParam(':manana', $manana);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $citas_manana = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($citas_manana as $cita) {
        $is_read = !empty($cita['leida_id']);
        if (!$is_read) $total_unread++;
        
        $notifications[] = [
            'id' => 'cita_' . $cita['id'],
            'type' => 'upcoming',
            'title' => 'Cita mañana a las ' . formatTime($cita['hora_inicio']),
            'description' => $cita['apellidos'] . ', ' . $cita['nombre'] . 
                             ($cita['servicio_nombre'] ? ' - ' . $cita['servicio_nombre'] : ''),
            'time' => formatDateToView($cita['fecha']) . ' ' . formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']),
            'url' => BASE_URL . '/modules/citas/view.php?id=' . $cita['id'],
            'icon' => 'event',
            'is_read' => $is_read,
            'entity_id' => $cita['id'],
            'entity_type' => 'cita'
        ];
    }
    
    // 3. Citas pendientes de confirmación (si existe esta funcionalidad)
    $stmt = $db->prepare("
        SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.estado, c.notas,
               p.id AS paciente_id, p.nombre, p.apellidos, p.telefono,
               s.nombre AS servicio_nombre,
               (SELECT MIN(id) FROM notificaciones_leidas WHERE usuario_id = :usuario_id AND cita_id = c.id) AS leida_id
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        LEFT JOIN servicios s ON c.servicio_id = s.id
        WHERE c.fecha >= :hoy 
          AND c.fecha <= :fecha_limite
          AND c.estado = 'pendiente'
        ORDER BY c.fecha ASC, c.hora_inicio ASC
        LIMIT 5
    ");
    $fecha_limite = date('Y-m-d', strtotime('+7 days'));
    $stmt->bindParam(':hoy', $hoy);
    $stmt->bindParam(':fecha_limite', $fecha_limite);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $citas_por_confirmar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($citas_por_confirmar as $cita) {
        $is_read = !empty($cita['leida_id']);
        if (!$is_read) $total_unread++;
        
        $notifications[] = [
            'id' => 'cita_confirm_' . $cita['id'],
            'type' => 'pending',
            'title' => 'Cita por confirmar',
            'description' => $cita['apellidos'] . ', ' . $cita['nombre'] . 
                             ($cita['servicio_nombre'] ? ' - ' . $cita['servicio_nombre'] : ''),
            'time' => formatDateToView($cita['fecha']) . ' ' . formatTime($cita['hora_inicio']) . ' - ' . formatTime($cita['hora_fin']),
            'url' => BASE_URL . '/modules/citas/view.php?id=' . $cita['id'],
            'icon' => 'notification_important',
            'is_read' => $is_read,
            'entity_id' => $cita['id'],
            'entity_type' => 'cita'
        ];
    }
    
    // Ordenar notificaciones: primero urgentes, luego hoy, luego mañana
    usort($notifications, function($a, $b) {
        $priority = ['urgent' => 1, 'today' => 2, 'upcoming' => 3, 'pending' => 4];
        $p1 = $priority[$a['type']] ?? 5;
        $p2 = $priority[$b['type']] ?? 5;
        
        if ($p1 === $p2) {
            // Si tienen la misma prioridad, ordenar por no leídas primero
            if ($a['is_read'] !== $b['is_read']) {
                return $a['is_read'] ? 1 : -1;
            }
            // Si ambas son leídas o no leídas, ordenar por tiempo
            return strcmp($a['time'], $b['time']);
        }
        
        return $p1 - $p2;
    });
    
    // Preparar la respuesta
    $response = [
        'success' => true,
        'notifications' => $notifications,
        'total_unread' => $total_unread,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'notifications' => [],
        'total_unread' => 0,
        'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
    ];
}

// Devolver respuesta JSON
echo json_encode($response);
exit; 