<?php
/**
 * Funciones para el dashboard personalizable
 * Este archivo contiene todas las funciones relacionadas con la gestión del dashboard
 */

// Prevenir acceso directo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Obtiene la configuración del dashboard para un usuario específico
 * 
 * @param int $usuario_id ID del usuario
 * @return array Configuración del dashboard
 */
function obtenerConfigDashboard($usuario_id) {
    try {
        $db = getDB();
        
        // Comprobar si el usuario tiene configuración personalizada
        $stmtConfig = $db->prepare("SELECT widgets FROM dashboard_config WHERE usuario_id = :usuario_id");
        $stmtConfig->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmtConfig->execute();
        
        if ($row = $stmtConfig->fetch(PDO::FETCH_ASSOC)) {
            // El usuario tiene configuración personalizada
            $widgets = json_decode($row['widgets'], true);
        } else {
            // Configuración por defecto - obtener todos los widgets activos
            $stmtWidgets = $db->prepare("SELECT codigo, tamano_predeterminado FROM dashboard_widgets WHERE activo = 1 ORDER BY orden_predeterminado");
            $stmtWidgets->execute();
            $defaultWidgets = $stmtWidgets->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir a formato esperado
            $widgets = [];
            foreach ($defaultWidgets as $widget) {
                $widgets[] = [
                    'codigo' => $widget['codigo'],
                    'tamano' => $widget['tamano_predeterminado'],
                    'activo' => true
                ];
            }
            
            // Guardar configuración por defecto para el usuario
            guardarConfigDashboard($usuario_id, $widgets);
        }
        
        return $widgets;
    } catch (PDOException $e) {
        error_log('Error al obtener configuración del dashboard: ' . $e->getMessage());
        return getDefaultWidgets();
    }
}

/**
 * Obtiene los widgets por defecto en caso de error
 * 
 * @return array Widgets por defecto
 */
function getDefaultWidgets() {
    return [
        ['codigo' => 'stats_cards', 'tamano' => 'col-12', 'activo' => true],
        ['codigo' => 'latest_appointments', 'tamano' => 'col-lg-6', 'activo' => true],
        ['codigo' => 'latest_patients', 'tamano' => 'col-lg-6', 'activo' => true]
    ];
}

/**
 * Guarda la configuración del dashboard para un usuario
 * 
 * @param int $usuario_id ID del usuario
 * @param array $widgets Configuración de widgets
 * @return bool Éxito de la operación
 */
function guardarConfigDashboard($usuario_id, $widgets) {
    try {
        $db = getDB();
        
        // Convertir array a JSON
        $widgetsJson = json_encode($widgets);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error en json_encode: ' . json_last_error_msg());
            return false;
        }
        
        // Verificar si ya existe una configuración para este usuario
        $stmtCheck = $db->prepare("SELECT id FROM dashboard_config WHERE usuario_id = :usuario_id");
        $stmtCheck->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmtCheck->execute();
        
        if ($stmtCheck->rowCount() > 0) {
            // Actualizar configuración existente
            $stmt = $db->prepare("UPDATE dashboard_config SET widgets = :widgets WHERE usuario_id = :usuario_id");
        } else {
            // Insertar nueva configuración
            $stmt = $db->prepare("INSERT INTO dashboard_config (usuario_id, widgets) VALUES (:usuario_id, :widgets)");
        }
        
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':widgets', $widgetsJson);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Error al guardar configuración del dashboard: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene todos los widgets disponibles
 * 
 * @return array Lista de widgets disponibles
 */
function obtenerWidgetsDisponibles() {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT * FROM dashboard_widgets WHERE activo = 1 ORDER BY orden_predeterminado");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error al obtener widgets disponibles: ' . $e->getMessage());
        return [];
    }
}

/**
 * Verifica si las tablas del dashboard existen
 * 
 * @return bool Verdadero si las tablas existen
 */
function verificarTablasDashboard() {
    try {
        $db = getDB();
        
        // Comprobar tabla dashboard_config
        $stmtConfig = $db->prepare("SHOW TABLES LIKE 'dashboard_config'");
        $stmtConfig->execute();
        
        // Comprobar tabla dashboard_widgets
        $stmtWidgets = $db->prepare("SHOW TABLES LIKE 'dashboard_widgets'");
        $stmtWidgets->execute();
        
        return ($stmtConfig->rowCount() > 0 && $stmtWidgets->rowCount() > 0);
    } catch (PDOException $e) {
        error_log('Error al verificar tablas del dashboard: ' . $e->getMessage());
        return false;
    }
}

/**
 * Renderiza un widget específico
 * 
 * @param string $widgetCode Código del widget
 * @param string $size Tamaño del widget (clase Bootstrap)
 * @return string HTML del widget
 */
function renderizarWidget($widgetCode, $size = 'col-lg-6') {
    // Ruta al archivo del widget
    $widgetFile = __DIR__ . '/widgets/' . $widgetCode . '.php';
    
    // Comprobar si existe el archivo
    if (!file_exists($widgetFile)) {
        return '<div class="' . $size . ' mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">
                            <span class="material-symbols-rounded d-block mb-3" style="font-size: 2.5rem;">error</span>
                            <h5>Widget no disponible</h5>
                            <p>El widget solicitado no está disponible.</p>
                        </div>
                    </div>
                </div>';
    }
    
    // Capturar salida del widget
    ob_start();
    include $widgetFile;
    $widgetContent = ob_get_clean();
    
    return $widgetContent;
}

/**
 * Insertar widgets predeterminados
 */
function insertarWidgetsPredeterminados() {
    try {
        $db = getDB();
        
        // Listado de widgets disponibles
        $widgets = [
            [
                'codigo' => 'welcome_banner',
                'nombre' => 'Banner de Bienvenida',
                'descripcion' => 'Muestra un saludo personalizado con la fecha actual y el clima en Madrid',
                'icono' => 'waving_hand',
                'tamano_predeterminado' => 'col-12',
                'orden_predeterminado' => 1,
                'activo' => 1
            ],
            [
                'codigo' => 'stats_cards',
                'nombre' => 'Estadísticas Generales',
                'descripcion' => 'Muestra estadísticas generales de pacientes y citas',
                'icono' => 'dashboard',
                'tamano_predeterminado' => 'col-12',
                'orden_predeterminado' => 5,
                'activo' => 1
            ],
            [
                'codigo' => 'appointments_today',
                'nombre' => 'Citas para Hoy',
                'descripcion' => 'Muestra las citas programadas para hoy',
                'icono' => 'today',
                'tamano_predeterminado' => 'col-md-6',
                'orden_predeterminado' => 10,
                'activo' => 1
            ],
            [
                'codigo' => 'upcoming_appointments',
                'nombre' => 'Próximas Citas',
                'descripcion' => 'Muestra las próximas citas programadas',
                'icono' => 'date_range',
                'tamano_predeterminado' => 'col-md-6',
                'orden_predeterminado' => 15,
                'activo' => 1
            ],
            [
                'codigo' => 'latest_patients',
                'nombre' => 'Últimos Pacientes',
                'descripcion' => 'Muestra los últimos pacientes añadidos',
                'icono' => 'group',
                'tamano_predeterminado' => 'col-md-6',
                'orden_predeterminado' => 20,
                'activo' => 1
            ],
            [
                'codigo' => 'latest_appointments',
                'nombre' => 'Últimas Citas',
                'descripcion' => 'Muestra las últimas citas registradas',
                'icono' => 'schedule',
                'tamano_predeterminado' => 'col-md-6',
                'orden_predeterminado' => 25,
                'activo' => 1
            ],
            [
                'codigo' => 'quick_actions',
                'nombre' => 'Acciones Rápidas',
                'descripcion' => 'Accesos directos a acciones comunes',
                'icono' => 'bolt',
                'tamano_predeterminado' => 'col-md-6 col-lg-4',
                'orden_predeterminado' => 30,
                'activo' => 1
            ],
            [
                'codigo' => 'notes',
                'nombre' => 'Notas Rápidas',
                'descripcion' => 'Permite crear y gestionar notas rápidas',
                'icono' => 'sticky_note_2',
                'tamano_predeterminado' => 'col-md-6 col-lg-8',
                'orden_predeterminado' => 35,
                'activo' => 1
            ]
        ];
        
        // Insertar los widgets en la base de datos
        foreach ($widgets as $widget) {
            $stmt = $db->prepare("
                INSERT INTO dashboard_widgets 
                    (codigo, nombre, descripcion, icono, tamano_predeterminado, orden_predeterminado, activo) 
                VALUES 
                    (:codigo, :nombre, :descripcion, :icono, :tamano, :orden, :activo)
                ON DUPLICATE KEY UPDATE 
                    nombre = VALUES(nombre),
                    descripcion = VALUES(descripcion),
                    icono = VALUES(icono),
                    tamano_predeterminado = VALUES(tamano_predeterminado),
                    orden_predeterminado = VALUES(orden_predeterminado),
                    activo = VALUES(activo)
            ");
            
            $stmt->bindParam(':codigo', $widget['codigo']);
            $stmt->bindParam(':nombre', $widget['nombre']);
            $stmt->bindParam(':descripcion', $widget['descripcion']);
            $stmt->bindParam(':icono', $widget['icono']);
            $stmt->bindParam(':tamano', $widget['tamano_predeterminado']);
            $stmt->bindParam(':orden', $widget['orden_predeterminado']);
            $stmt->bindParam(':activo', $widget['activo']);
            
            $stmt->execute();
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Error al insertar widgets predeterminados: ' . $e->getMessage());
        return false;
    }
}

// ... rest of the code ... 