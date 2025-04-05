<?php
// API para gestión de bonos
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Comprobar el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Obtener acción desde GET
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Procesar según método y acción
switch ($method) {
    case 'GET':
        // Obtener detalles de un bono específico o lista de bonos
        if ($action == 'get' && isset($_GET['id'])) {
            getBono($_GET['id']);
        } elseif ($action == 'list_patient' && isset($_GET['paciente_id'])) {
            getBonosPaciente($_GET['paciente_id']);
        } else {
            // Por defecto, listar todos los bonos
            listBonos();
        }
        break;
        
    case 'POST':
        // Crear un nuevo bono o actualizar estado
        if ($action == 'cambiar_estado') {
            cambiarEstadoBono();
        } else {
            // Por defecto, crear nuevo bono
            crearBono();
        }
        break;
        
    default:
        // Método no permitido
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        break;
}

// Funciones para manejo de bonos

/**
 * Crear un nuevo bono
 */
function crearBono() {
    global $conn;
    
    // Validar datos recibidos
    if (!isset($_POST['paciente_id']) || empty($_POST['paciente_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Se requiere un paciente']);
        return;
    }
    
    // Valores por defecto
    $numSesiones = isset($_POST['num_sesiones']) && !empty($_POST['num_sesiones']) ? $_POST['num_sesiones'] : 4;
    $monto = isset($_POST['monto']) && !empty($_POST['monto']) ? $_POST['monto'] : 180.00;
    $fechaCaducidad = isset($_POST['fecha_caducidad']) && !empty($_POST['fecha_caducidad']) ? $_POST['fecha_caducidad'] : null;
    $notas = isset($_POST['notas']) ? $_POST['notas'] : null;
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Preparar la consulta
        $query = "INSERT INTO bonos (paciente_id, num_sesiones_total, num_sesiones_disponibles, monto, fecha_caducidad, notas, creado_por) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "iiidssi", 
            $_POST['paciente_id'], 
            $numSesiones, 
            $numSesiones, // Inicialmente disponibles = total
            $monto,
            $fechaCaducidad,
            $notas,
            $_SESSION['usuario_id']
        );
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $bonoId = $conn->insert_id;
            
            // Confirmar la transacción
            $conn->commit();
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Bono creado correctamente', 
                'id' => $bonoId
            ]);
        } else {
            // Revertir la transacción en caso de error
            $conn->rollback();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Error al crear el bono: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        // Revertir la transacción en caso de excepción
        $conn->rollback();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al crear el bono: ' . $e->getMessage()
        ]);
    }
}

/**
 * Obtener detalles de un bono específico
 * @param int $id ID del bono
 */
function getBono($id) {
    global $conn;
    
    try {
        // Obtener información del bono
        $query = "SELECT b.*, CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre
                  FROM bonos b
                  JOIN pacientes p ON b.paciente_id = p.id
                  WHERE b.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Bono no encontrado']);
            return;
        }
        
        $bono = $result->fetch_assoc();
        
        // Obtener citas vinculadas a este bono
        $queryCitas = "SELECT c.*, CONCAT(u.nombre, ' ', u.apellidos) as profesional_nombre
                      FROM citas c
                      JOIN usuarios u ON c.profesional_id = u.id
                      WHERE c.bono_id = ?
                      ORDER BY c.fecha DESC, c.hora_inicio ASC";
        
        $stmtCitas = $conn->prepare($queryCitas);
        $stmtCitas->bind_param("i", $id);
        $stmtCitas->execute();
        $resultCitas = $stmtCitas->get_result();
        
        $citas = [];
        while ($row = $resultCitas->fetch_assoc()) {
            $citas[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'bono' => $bono,
            'citas' => $citas
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al obtener información del bono: ' . $e->getMessage()
        ]);
    }
}

/**
 * Listar bonos disponibles para un paciente específico
 * @param int $pacienteId ID del paciente
 */
function getBonosPaciente($pacienteId) {
    global $conn;
    
    try {
        $query = "SELECT * FROM bonos 
                  WHERE paciente_id = ? 
                  AND estado = 'activo' 
                  AND num_sesiones_disponibles > 0
                  ORDER BY fecha_compra DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $pacienteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bonos = [];
        while ($row = $result->fetch_assoc()) {
            $bonos[] = $row;
        }
        
        echo json_encode($bonos);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al obtener bonos del paciente: ' . $e->getMessage()
        ]);
    }
}

/**
 * Listar todos los bonos
 */
function listBonos() {
    global $conn;
    
    try {
        $query = "SELECT b.*, CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre 
                  FROM bonos b 
                  JOIN pacientes p ON b.paciente_id = p.id 
                  ORDER BY b.fecha_compra DESC";
                  
        $result = $conn->query($query);
        
        $bonos = [];
        while ($row = $result->fetch_assoc()) {
            $bonos[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'bonos' => $bonos
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al listar bonos: ' . $e->getMessage()
        ]);
    }
}

/**
 * Cambiar el estado de un bono
 */
function cambiarEstadoBono() {
    global $conn;
    
    // Validar datos recibidos
    if (!isset($_POST['bono_id']) || empty($_POST['bono_id']) || 
        !isset($_POST['estado']) || empty($_POST['estado'])) {
        echo json_encode(['status' => 'error', 'message' => 'Se requiere ID de bono y nuevo estado']);
        return;
    }
    
    $bonoId = $_POST['bono_id'];
    $nuevoEstado = $_POST['estado'];
    $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : '';
    
    // Validar que el estado sea válido
    $estadosValidos = ['activo', 'consumido', 'caducado', 'cancelado'];
    if (!in_array($nuevoEstado, $estadosValidos)) {
        echo json_encode(['status' => 'error', 'message' => 'Estado no válido']);
        return;
    }
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Registrar el cambio de estado
        $query = "UPDATE bonos SET 
                  estado = ?,
                  notas = CONCAT(IFNULL(notas, ''), '\n[" . date('Y-m-d H:i:s') . "] Cambio de estado a \"" . $nuevoEstado . "\". Motivo: " . $motivo . "')
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $nuevoEstado, $bonoId);
        
        if ($stmt->execute()) {
            // Si se cambia a cancelado, también marcar las citas pendientes asociadas al bono
            if ($nuevoEstado === 'cancelado') {
                $queryCitas = "UPDATE citas SET 
                              estado = 'cancelada',
                              notas = CONCAT(IFNULL(notas, ''), '\n[" . date('Y-m-d H:i:s') . "] Cancelada automáticamente por cancelación del bono #" . $bonoId . "')
                              WHERE bono_id = ? AND estado IN ('pendiente', 'confirmada')";
                
                $stmtCitas = $conn->prepare($queryCitas);
                $stmtCitas->bind_param("i", $bonoId);
                $stmtCitas->execute();
            }
            
            // Confirmar la transacción
            $conn->commit();
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Estado del bono actualizado correctamente'
            ]);
        } else {
            // Revertir la transacción en caso de error
            $conn->rollback();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Error al actualizar el estado del bono: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        // Revertir la transacción en caso de excepción
        $conn->rollback();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al actualizar el estado del bono: ' . $e->getMessage()
        ]);
    }
} 