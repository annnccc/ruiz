<?php
/**
 * API para guardar y gestionar notas del dashboard
 * Recibe una petición AJAX y realiza operaciones CRUD en las notas
 */

// Configurar cabeceras
header('Content-Type: application/json');

// Comprobar que estamos en el contexto correcto
$relativePath = '../../';
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

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['usuario_id'];

// Verificar que la acción existe
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

// Preparar conexión a la base de datos
try {
    $db = getDB();
    
    // Verificar si la tabla existe
    $stmt = $db->prepare("SHOW TABLES LIKE 'dashboard_notas'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Crear tabla si no existe
        $db->exec("CREATE TABLE IF NOT EXISTS `dashboard_notas` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `usuario_id` int(11) NOT NULL,
            `contenido` text NOT NULL,
            `color` varchar(20) DEFAULT 'primary',
            `creado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `actualizado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_usuario_id` (`usuario_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    // Definir acciones
    $action = $_POST['action'];
    
    // Crear nota
    if ($action === 'create') {
        // Validar datos
        if (!isset($_POST['contenido']) || empty($_POST['contenido'])) {
            echo json_encode(['success' => false, 'message' => 'El contenido de la nota es obligatorio']);
            exit;
        }
        
        // Obtener y sanitizar datos
        $contenido = trim($_POST['contenido']);
        $color = isset($_POST['color']) ? $_POST['color'] : 'primary';
        
        // Limitar colores permitidos
        $coloresPermitidos = ['primary', 'success', 'warning', 'danger', 'info'];
        if (!in_array($color, $coloresPermitidos)) {
            $color = 'primary';
        }
        
        // Insertar en la base de datos
        $stmt = $db->prepare("INSERT INTO dashboard_notas (usuario_id, contenido, color) VALUES (:usuario_id, :contenido, :color)");
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':color', $color);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota creada correctamente', 'id' => $db->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear la nota']);
        }
    }
    
    // Actualizar nota
    elseif ($action === 'update') {
        // Validar datos
        if (!isset($_POST['id']) || !isset($_POST['contenido']) || empty($_POST['contenido'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        
        // Obtener y sanitizar datos
        $id = intval($_POST['id']);
        $contenido = trim($_POST['contenido']);
        $color = isset($_POST['color']) ? $_POST['color'] : 'primary';
        
        // Limitar colores permitidos
        $coloresPermitidos = ['primary', 'success', 'warning', 'danger', 'info'];
        if (!in_array($color, $coloresPermitidos)) {
            $color = 'primary';
        }
        
        // Verificar que la nota pertenece al usuario
        $stmt = $db->prepare("SELECT id FROM dashboard_notas WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'No se encontró la nota o no tienes permiso para editarla']);
            exit;
        }
        
        // Actualizar en la base de datos
        $stmt = $db->prepare("UPDATE dashboard_notas SET contenido = :contenido, color = :color WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':color', $color);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota actualizada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la nota']);
        }
    }
    
    // Eliminar nota
    elseif ($action === 'delete') {
        // Validar datos
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID de nota no especificado']);
            exit;
        }
        
        // Obtener y sanitizar datos
        $id = intval($_POST['id']);
        
        // Verificar que la nota pertenece al usuario
        $stmt = $db->prepare("SELECT id FROM dashboard_notas WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'No se encontró la nota o no tienes permiso para eliminarla']);
            exit;
        }
        
        // Eliminar de la base de datos
        $stmt = $db->prepare("DELETE FROM dashboard_notas WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Nota eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la nota']);
        }
    }
    
    // Acción no reconocida
    else {
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} 