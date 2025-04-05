<?php
/**
 * API para marcar notificaciones como leídas
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
    'message' => 'Parámetros no válidos'
];

// Verificar si la tabla existe, si no crearla
try {
    $db = getDB();
    
    // Comprobar si la tabla existe
    $stmt = $db->prepare("
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'notificaciones_leidas'
    ");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Crear la tabla
        $db->exec("
            CREATE TABLE notificaciones_leidas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                cita_id INT NOT NULL,
                fecha_lectura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (usuario_id),
                INDEX (cita_id),
                UNIQUE KEY unique_notification (usuario_id, cita_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    // Obtener ID del usuario actual
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    
    // Verificar si se trata de una sola notificación o todas
    if (isset($_POST['all']) && $_POST['all'] == 1) {
        // Marcar todas las notificaciones como leídas
        // Primero obtenemos todas las citas pendientes que no están marcadas como leídas
        $hoy = date('Y-m-d');
        $proximos_dias = date('Y-m-d', strtotime('+7 days'));
        
        $stmt = $db->prepare("
            SELECT c.id 
            FROM citas c
            LEFT JOIN notificaciones_leidas nl ON c.id = nl.cita_id AND nl.usuario_id = :usuario_id
            WHERE c.fecha BETWEEN :hoy AND :proximos_dias
            AND nl.id IS NULL
            AND c.estado = 'pendiente'
        ");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':hoy', $hoy);
        $stmt->bindParam(':proximos_dias', $proximos_dias);
        $stmt->execute();
        $citas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $count = 0;
        if (!empty($citas)) {
            foreach ($citas as $cita_id) {
                try {
                    $insert = $db->prepare("
                        INSERT INTO notificaciones_leidas (usuario_id, cita_id)
                        VALUES (:usuario_id, :cita_id)
                        ON DUPLICATE KEY UPDATE fecha_lectura = CURRENT_TIMESTAMP
                    ");
                    $insert->bindParam(':usuario_id', $usuario_id);
                    $insert->bindParam(':cita_id', $cita_id);
                    $insert->execute();
                    $count++;
                } catch (PDOException $e) {
                    // Ignorar errores de duplicados
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }
        }
        
        $response = [
            'success' => true,
            'message' => "Se han marcado $count notificaciones como leídas",
            'count' => $count
        ];
    } elseif (isset($_POST['notification_id']) && isset($_POST['entity_type'])) {
        // Marcar una notificación específica como leída
        $notification_id = intval($_POST['notification_id']);
        $entity_type = $_POST['entity_type'];
        
        if ($entity_type == 'cita' && $notification_id > 0) {
            $insert = $db->prepare("
                INSERT INTO notificaciones_leidas (usuario_id, cita_id)
                VALUES (:usuario_id, :cita_id)
                ON DUPLICATE KEY UPDATE fecha_lectura = CURRENT_TIMESTAMP
            ");
            $insert->bindParam(':usuario_id', $usuario_id);
            $insert->bindParam(':cita_id', $notification_id);
            $insert->execute();
            
            $response = [
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'notification_id' => $notification_id
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Tipo de notificación no válido'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Parámetros no válidos'
        ];
    }
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Error al marcar notificación como leída: ' . $e->getMessage()
    ];
}

// Devolver respuesta JSON
echo json_encode($response);
exit; 