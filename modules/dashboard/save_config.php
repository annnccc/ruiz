<?php
/**
 * API para guardar la configuración del dashboard
 * Recibe una petición AJAX con la configuración y la guarda en la base de datos
 */

// Evitar cualquier salida de errores
error_reporting(0);

// Capturar la salida
ob_start();

// Configurar cabeceras
header('Content-Type: application/json');

// Comprobar que estamos en el contexto correcto
define('BASE_URL', '../../');

// Definir constante para control de errores
define('DEBUG_MODE', false); // Cambiar a true para depuración

// Incluir archivos necesarios
require_once BASE_URL . 'includes/config.php';
require_once BASE_URL . 'includes/db.php';
require_once BASE_URL . 'includes/functions.php';
require_once __DIR__ . '/dashboard_functions.php';

// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    ob_end_clean(); // Limpiar buffer antes de la salida JSON
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Limpiar buffer antes de la salida JSON
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos de la petición
    $json_input = file_get_contents('php://input');
    if (empty($json_input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $data = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    
    // Verificar que los datos son válidos
    if (!isset($data['widgets']) || !is_array($data['widgets'])) {
        throw new Exception('Datos de widgets inválidos o no presentes');
    }
    
    // Validar formato de widgets
    $widgets = [];
    foreach ($data['widgets'] as $widget) {
        // Verificar si tiene los campos requeridos
        if (
            isset($widget['codigo']) && is_string($widget['codigo']) &&
            isset($widget['tamano']) && is_string($widget['tamano']) &&
            isset($widget['activo'])
        ) {
            // Convertir explícitamente activo a booleano
            $activo = filter_var($widget['activo'], FILTER_VALIDATE_BOOLEAN);
            
            $widgets[] = [
                'codigo' => $widget['codigo'],
                'tamano' => $widget['tamano'],
                'activo' => $activo
            ];
        }
    }
    
    if (empty($widgets)) {
        throw new Exception('No se encontraron widgets válidos');
    }
    
    // Guardar configuración
    $usuario_id = $_SESSION['usuario_id'];
    $resultado = guardarConfigDashboard($usuario_id, $widgets);
    
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Devolver respuesta
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Configuración guardada correctamente']);
    } else {
        throw new Exception('Error al guardar la configuración en la base de datos');
    }
} catch (Exception $e) {
    // Registrar el error
    error_log('Error en save_config.php: ' . $e->getMessage());
    
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Devolver respuesta de error
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 