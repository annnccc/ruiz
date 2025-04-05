<?php
/**
 * API para actualizar fechas de citas mediante drag-and-drop
 * Este archivo maneja las peticiones AJAX para actualizar las fechas de citas
 * cuando son arrastradas y soltadas en el calendario
 */

// Configurar cabeceras
header('Content-Type: application/json');

// Comprobar que estamos en el contexto correcto
$relativePath = '../../';

// Incluir archivos necesarios
require_once $relativePath . 'includes/config.php';
require_once $relativePath . 'includes/db.php';
require_once $relativePath . 'includes/functions.php';

// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo de la petición 
$input = json_decode(file_get_contents('php://input'), true);

// Validar que existan los datos necesarios
if (!isset($input['id']) || !isset($input['start']) || !isset($input['end'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
    exit;
}

// Extraer y formatear los datos
$id = intval($input['id']);
$nuevaFecha = date('Y-m-d', strtotime($input['start'])); // Formato YYYY-MM-DD
$nuevaHoraInicio = date('H:i', strtotime($input['start'])); // Formato HH:MM
$nuevaHoraFin = date('H:i', strtotime($input['end'])); // Formato HH:MM

// Registrar actividad para depuración
error_log("Arrastrando cita ID: $id - Nueva fecha: $nuevaFecha, Hora inicio: $nuevaHoraInicio, Hora fin: $nuevaHoraFin");

try {
    $db = getDB();
    
    // Iniciar transacción para asegurar la integridad de los datos
    $db->beginTransaction();
    
    // Verificar si la cita existe
    $stmtCheck = $db->prepare("SELECT id, paciente_id, fecha FROM citas WHERE id = :id");
    $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    
    if ($stmtCheck->rowCount() === 0) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'La cita no existe']);
        exit;
    }
    
    $cita = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    $fechaAnterior = $cita['fecha'];
    
    // Verificar si hay conflictos con otras citas (en la nueva fecha y hora)
    $stmtConflict = $db->prepare(
        "SELECT id FROM citas 
         WHERE fecha = :fecha
         AND ((hora_inicio <= :hora_inicio AND hora_fin > :hora_inicio) 
              OR (hora_inicio < :hora_fin AND hora_fin >= :hora_fin)
              OR (hora_inicio >= :hora_inicio AND hora_fin <= :hora_fin))
         AND id != :id"
    );
    
    $stmtConflict->bindParam(':fecha', $nuevaFecha);
    $stmtConflict->bindParam(':hora_inicio', $nuevaHoraInicio);
    $stmtConflict->bindParam(':hora_fin', $nuevaHoraFin);
    $stmtConflict->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtConflict->execute();
    
    if ($stmtConflict->rowCount() > 0) {
        $db->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe otra cita en ese horario',
            'conflict' => true
        ]);
        exit;
    }
    
    // Calcular duración para validar
    $horaInicio = strtotime("1970-01-01 $nuevaHoraInicio");
    $horaFin = strtotime("1970-01-01 $nuevaHoraFin");
    $duracionMinutos = ($horaFin - $horaInicio) / 60;
    
    // Validar duración mínima (15 minutos)
    if ($duracionMinutos < 15) {
        $db->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'La duración mínima de una cita es de 15 minutos'
        ]);
        exit;
    }
    
    // Actualizar la cita
    $stmtUpdate = $db->prepare(
        "UPDATE citas 
         SET fecha = :fecha, hora_inicio = :hora_inicio, hora_fin = :hora_fin 
         WHERE id = :id"
    );
    
    $stmtUpdate->bindParam(':fecha', $nuevaFecha);
    $stmtUpdate->bindParam(':hora_inicio', $nuevaHoraInicio);
    $stmtUpdate->bindParam(':hora_fin', $nuevaHoraFin);
    $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmtUpdate->execute()) {
        // Registrar en historial de cambios
        $usuario_id = $_SESSION['usuario_id'];
        $fechaAnteriorFormateada = date('d/m/Y', strtotime($fechaAnterior));
        $nuevaFechaFormateada = date('d/m/Y', strtotime($nuevaFecha));
        
        $descripcion = "Cita reprogramada desde $fechaAnteriorFormateada a $nuevaFechaFormateada ($nuevaHoraInicio - $nuevaHoraFin)";
        
        $stmtLog = $db->prepare(
            "INSERT INTO historial (usuario_id, tipo, entidad_id, descripcion, fecha) 
             VALUES (:usuario_id, 'cita', :entidad_id, :descripcion, NOW())"
        );
        
        $stmtLog->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmtLog->bindParam(':entidad_id', $id, PDO::PARAM_INT);
        $stmtLog->bindParam(':descripcion', $descripcion);
        $stmtLog->execute();
        
        // Si la fecha cambia, puede afectar a notificaciones o recordatorios
        if ($fechaAnterior != $nuevaFecha) {
            // Aquí podríamos actualizar recordatorios o notificaciones si existieran
            error_log("La fecha de la cita ID: $id cambió de $fechaAnterior a $nuevaFecha");
        }
        
        // Confirmar la transacción
        $db->commit();
        
        // Responder con éxito
        echo json_encode([
            'success' => true,
            'message' => 'Cita reprogramada exitosamente',
            'data' => [
                'id' => $id,
                'fecha' => $nuevaFecha,
                'fecha_formateada' => $nuevaFechaFormateada,
                'hora_inicio' => $nuevaHoraInicio,
                'hora_fin' => $nuevaHoraFin
            ]
        ]);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la cita']);
    }
    
} catch (PDOException $e) {
    // Asegurarse de hacer rollback si hay transacción activa
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en update_date.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} 