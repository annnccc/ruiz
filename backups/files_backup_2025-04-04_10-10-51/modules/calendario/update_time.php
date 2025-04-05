<?php
/**
 * API para actualizar horarios de citas mediante redimensionamiento
 * Este archivo maneja las peticiones AJAX para actualizar los horarios de citas
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

$id = intval($input['id']);
$fecha = substr($input['start'], 0, 10); // Mantener la misma fecha
$nuevaHoraInicio = substr($input['start'], 11, 5); // Extraer hora de inicio (HH:MM)
$nuevaHoraFin = substr($input['end'], 11, 5); // Extraer hora de fin (HH:MM)

// Registrar actividad para depuración
error_log("Redimensionando cita ID: $id - Hora inicio: $nuevaHoraInicio, Hora fin: $nuevaHoraFin");

try {
    $db = getDB();
    
    // Iniciar transacción para asegurar la integridad de los datos
    $db->beginTransaction();
    
    // Verificar si la cita existe
    $stmtCheck = $db->prepare("SELECT id, paciente_id FROM citas WHERE id = :id");
    $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    
    if ($stmtCheck->rowCount() === 0) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'La cita no existe']);
        exit;
    }
    
    $cita = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    // Validar duración mínima y máxima
    $horaInicio = strtotime("1970-01-01 $nuevaHoraInicio");
    $horaFin = strtotime("1970-01-01 $nuevaHoraFin");
    $duracionMinutos = ($horaFin - $horaInicio) / 60;
    
    // La duración debe ser de al menos 15 minutos y máximo 3 horas
    if ($duracionMinutos < 15) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'La duración mínima de una cita es de 15 minutos']);
        exit;
    }
    
    if ($duracionMinutos > 180) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'La duración máxima de una cita es de 3 horas']);
        exit;
    }
    
    // Verificar si hay conflictos con otras citas 
    $stmtConflict = $db->prepare(
        "SELECT id FROM citas 
         WHERE fecha = :fecha
         AND ((hora_inicio <= :hora_inicio AND hora_fin > :hora_inicio) 
              OR (hora_inicio < :hora_fin AND hora_fin >= :hora_fin)
              OR (hora_inicio >= :hora_inicio AND hora_fin <= :hora_fin))
         AND id != :id"
    );
    
    $stmtConflict->bindParam(':fecha', $fecha);
    $stmtConflict->bindParam(':hora_inicio', $nuevaHoraInicio);
    $stmtConflict->bindParam(':hora_fin', $nuevaHoraFin);
    $stmtConflict->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtConflict->execute();
    
    if ($stmtConflict->rowCount() > 0) {
        $db->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'La nueva duración genera conflicto con otra cita',
            'conflict' => true
        ]);
        exit;
    }
    
    // Actualizar la cita
    $stmtUpdate = $db->prepare(
        "UPDATE citas 
         SET hora_inicio = :hora_inicio, hora_fin = :hora_fin 
         WHERE id = :id"
    );
    
    $stmtUpdate->bindParam(':hora_inicio', $nuevaHoraInicio);
    $stmtUpdate->bindParam(':hora_fin', $nuevaHoraFin);
    $stmtUpdate->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmtUpdate->execute()) {
        // Registrar en historial de cambios
        $usuario_id = $_SESSION['usuario_id'];
        $descripcion = "Duración de cita modificada mediante calendario para el horario de $nuevaHoraInicio a $nuevaHoraFin";
        
        $stmtLog = $db->prepare(
            "INSERT INTO historial (usuario_id, tipo, entidad_id, descripcion, fecha) 
             VALUES (:usuario_id, 'cita', :entidad_id, :descripcion, NOW())"
        );
        
        $stmtLog->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmtLog->bindParam(':entidad_id', $id, PDO::PARAM_INT);
        $stmtLog->bindParam(':descripcion', $descripcion);
        $stmtLog->execute();
        
        // Confirmar la transacción
        $db->commit();
        
        // Responder con éxito
        echo json_encode([
            'success' => true,
            'message' => 'Horario de cita actualizado exitosamente',
            'data' => [
                'id' => $id,
                'fecha' => $fecha,
                'hora_inicio' => $nuevaHoraInicio,
                'hora_fin' => $nuevaHoraFin,
                'duracion' => $duracionMinutos
            ]
        ]);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el horario de la cita']);
    }
    
} catch (PDOException $e) {
    // Asegurarse de hacer rollback si hay transacción activa
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error en update_time.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} 