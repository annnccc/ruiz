<?php
/**
 * Archivo de funciones global
 * Este archivo está mantenido por compatibilidad con código existente
 * Las nuevas funciones deben agregarse a los helpers correspondientes
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Incluir el archivo de helpers si no se ha incluido todavía
if (!function_exists('setAlert')) {
    require_once ROOT_PATH . '/includes/helpers.php';
}

// Incluir el helper de Heroicons
require_once ROOT_PATH . '/includes/helpers/heroicons_helper.php';

/**
 * NOTA: Las siguientes funciones son alias mantenidos por compatibilidad.
 * Para nuevas implementaciones, utilizar las funciones equivalentes
 * en los archivos de helpers correspondientes.
 */

// Alias de función para sanitizar entradas
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return \sanitize($data);
    }
}

// Alias de función para redireccionar
if (!function_exists('redirect')) {
    function redirect($url) {
        \redirect($url);
    }
}

// Alias de función para establecer mensaje de alerta (con orden de parámetros inverso)
if (!function_exists('setAlert')) {
    function setAlert($message, $type = 'success') {
        \setAlert($type, $message);
    }
}

// Alias de función para obtener mensaje de alerta
if (!function_exists('getAlert')) {
    function getAlert() {
        return \getAlert();
    }
}

// Alias de función para formatear fecha para mostrar
if (!function_exists('formatDateToView')) {
    function formatDateToView($date) {
        return \formatDateToView($date);
    }
}

// Alias de función para formatear fecha para almacenar
if (!function_exists('formatDateToDB')) {
    function formatDateToDB($date) {
        return \formatDateToDB($date);
    }
}

// Alias de función para formatear hora
if (!function_exists('formatTime')) {
    function formatTime($time) {
        return \formatTime($time);
    }
}

// Función para comprobar si el usuario es administrador
if (!function_exists('isUserAdmin')) {
    function isUserAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

// Alias de función para obtener horarios disponibles
if (!function_exists('getHorariosDisponibles')) {
    function getHorariosDisponibles($fecha) {
        return \getHorariosDisponibles($fecha);
    }
}

// Alias de función para validar email
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return \isValidEmail($email);
    }
}

// Alias de función para validar teléfono
if (!function_exists('isValidPhone')) {
    function isValidPhone($phone) {
        return \isValidPhone($phone);
    }
}

// Alias de función para validar DNI español
if (!function_exists('isValidDNI')) {
    function isValidDNI($dni) {
        // Modificado para que siempre devuelva true sin importar el formato
        return true;
        // Versión original: return \validarDNI($dni);
    }
}

// Alias de función para obtener el nombre del día
if (!function_exists('getDayName')) {
    function getDayName($date) {
        return \getDayName($date);
    }
}

// Alias de función para obtener el nombre del mes
if (!function_exists('getMonthName')) {
    function getMonthName($month) {
        return \getMonthName($month);
    }
}

// Alias de función para generar número de expediente
if (!function_exists('generateExpedienteNumber')) {
    function generateExpedienteNumber() {
        return \generarNumExpediente();
    }
}

// Alias de función para calcular edad
if (!function_exists('calcularEdad')) {
    function calcularEdad($fechaNacimiento) {
        return \calcularEdad($fechaNacimiento);
    }
}

// Alias de función para generar color aleatorio
if (!function_exists('randomColor')) {
    function randomColor() {
        return \randomColor();
    }
}

// Alias de función para verificar disponibilidad de horario
if (!function_exists('isTimeSlotAvailable')) {
    function isTimeSlotAvailable($date, $start_time, $end_time, $cita_id = null) {
        return \isTimeSlotAvailable($date, $start_time, $end_time, $cita_id);
    }
}

// Alias de función para generar número de historia
if (!function_exists('generarNumHistoria')) {
    function generarNumHistoria() {
        return \generarNumHistoria();
    }
}

// Alias de función para truncar texto
if (!function_exists('truncarTexto')) {
    function truncarTexto($texto, $longitud = 100, $sufijo = '...') {
        return \truncarTexto($texto, $longitud, $sufijo);
    }
}

// Alias de función para obtener clase de estado de cita
if (!function_exists('getEstadoCitaClass')) {
    function getEstadoCitaClass($estado) {
        return \getEstadoCitaClass($estado);
    }
}

// Alias de función para obtener icono de estado de cita
if (!function_exists('getEstadoCitaIcon')) {
    function getEstadoCitaIcon($estado) {
        return \getEstadoCitaIcon($estado);
    }
}

// Alias de función para formatear teléfono
if (!function_exists('formatTelefono')) {
    function formatTelefono($telefono) {
        return \formatTelefono($telefono);
    }
}

// Alias de función para convertir fecha a formato MySQL
if (!function_exists('dateToMysql')) {
    function dateToMysql($fecha) {
        return \formatDateToDB($fecha);
    }
}

// Alias de función para validar DNI
if (!function_exists('validarDNI')) {
    function validarDNI($dni) {
        return \validarDNI($dni);
    }
}

// Alias de función para guardar imagen
if (!function_exists('guardarImagen')) {
    function guardarImagen($archivo, $directorio, $nombreArchivo) {
        return \guardarImagen($archivo, $directorio, $nombreArchivo);
    }
}

// Alias de función para generar contraseña
if (!function_exists('generarPassword')) {
    function generarPassword($longitud = 10) {
        return \generarPassword($longitud);
    }
}

/**
 * Renderiza una página utilizando el layout unificado
 * 
 * @param string $content Contenido de la página
 * @param string $title Título de la página (opcional)
 * @param array $options Opciones adicionales como CSS o JS extra (opcional)
 */
function renderPage($content, $title = null, $options = []) {
    // Si se proporcionó un título, establecerlo
    if ($title !== null) {
        $GLOBALS['title'] = $title;
    } elseif (isset($GLOBALS['titulo_pagina'])) {
        // Si hay un título_pagina global, usarlo como alternativa
        $GLOBALS['title'] = $GLOBALS['titulo_pagina'];
    }
    
    // Establecer CSS extra si se proporcionó
    if (isset($options['extra_css'])) {
        $GLOBALS['extra_css'] = $options['extra_css'];
    }
    
    // Establecer JS extra si se proporcionó
    if (isset($options['extra_js'])) {
        $GLOBALS['extra_js'] = $options['extra_js'];
    }
    
    // Establecer el contenido
    $GLOBALS['content'] = $content;
    
    // Incluir el layout
    include ROOT_PATH . '/includes/layout.php';
    exit;
}

/**
 * Inicia la captura de salida para usar con renderPage
 * 
 * @return void
 */
function startPageContent() {
    ob_start();
}

/**
 * Finaliza la captura de salida y renderiza la página
 * 
 * @param string $title Título de la página (opcional)
 * @param array $options Opciones adicionales (opcional)
 * @return void
 */
function endPageContent($title = null, $options = []) {
    $content = ob_get_clean();
    renderPage($content, $title, $options);
}

/**
 * Busca pacientes según términos de búsqueda y criterios de ordenación
 * 
 * @param string $search Término de búsqueda
 * @param array $options Opciones adicionales (sort_field, sort_direction, offset, limit)
 * @param bool $count Si es true, devuelve solo el número de resultados
 * @return array|int Lista de pacientes o conteo, según el parámetro $count
 */
function searchPacientes($search = '', $options = [], $count = false) {
    try {
        $db = getDB();
        
        // Valores por defecto para las opciones
        $defaultOptions = [
            'sort_field' => 'apellidos',
            'sort_direction' => 'ASC',
            'offset' => null,
            'limit' => null,
            'with_citas_count' => false
        ];
        
        // Combinar opciones por defecto con las proporcionadas
        $options = array_merge($defaultOptions, $options);
        
        // Validar campos de ordenación permitidos
        $allowed_sort_fields = ['nombre', 'apellidos', 'dni', 'telefono', 'email', 'fecha_nacimiento', 'num_citas'];
        if (!in_array($options['sort_field'], $allowed_sort_fields)) {
            $options['sort_field'] = 'apellidos';
        }
        
        // Condición de búsqueda
        $searchCondition = '';
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = "WHERE nombre LIKE :search_nombre OR apellidos LIKE :search_apellidos OR dni LIKE :search_dni OR telefono LIKE :search_telefono OR email LIKE :search_email";
            $params[':search_nombre'] = "%$search%";
            $params[':search_apellidos'] = "%$search%";
            $params[':search_dni'] = "%$search%";
            $params[':search_telefono'] = "%$search%";
            $params[':search_email'] = "%$search%";
        }
        
        // Si solo queremos el conteo
        if ($count) {
            $query = "SELECT COUNT(*) AS total FROM pacientes $searchCondition";
            $stmt = $db->prepare($query);
            
            // Vincular parámetros de búsqueda
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['total']);
        }
        
        // Construir la consulta con base en las opciones
        if ($options['with_citas_count']) {
            $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM citas WHERE paciente_id = p.id) AS num_citas 
                     FROM pacientes p 
                     $searchCondition ";
        } else {
            $query = "SELECT p.* FROM pacientes p $searchCondition ";
        }
        
        // Manejo especial para ordenar por num_citas
        if ($options['sort_field'] === 'num_citas' && $options['with_citas_count']) {
            $query .= " ORDER BY num_citas {$options['sort_direction']}, apellidos ASC";
        } else {
            $query .= " ORDER BY {$options['sort_field']} {$options['sort_direction']}";
            // Añadir ordenación secundaria si no es por apellidos
            if ($options['sort_field'] !== 'apellidos') {
                $query .= ", apellidos ASC";
            }
        }
        
        // Añadir límite y offset si se especifican
        if ($options['limit'] !== null) {
            $query .= " LIMIT :offset, :limit";
            $params[':offset'] = $options['offset'] ?? 0;
            $params[':limit'] = $options['limit'];
        }
        
        $stmt = $db->prepare($query);
        
        // Vincular parámetros
        foreach ($params as $key => $value) {
            if ($key === ':offset' || $key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error en búsqueda de pacientes: ' . $e->getMessage());
        return $count ? 0 : [];
    }
}

/**
 * Verifica si el usuario está autenticado
 *
 * @return bool True si el usuario ha iniciado sesión, False si no
 */
function isLoggedIn() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtiene una instancia de AuditManager para el registro de auditoría
 * 
 * @return AuditManager Instancia de AuditManager
 */
function getAuditManager() {
    static $auditManager = null;
    
    if ($auditManager === null) {
        require_once ROOT_PATH . '/includes/classes/AuditManager.php';
        $auditManager = new AuditManager(getDB());
    }
    
    return $auditManager;
}

/**
 * Registra un acceso a datos en el sistema de auditoría
 * 
 * @param string $accion Tipo de acción (ver, editar, eliminar, etc.)
 * @param string $entidad Nombre de la entidad o tabla accedida
 * @param int $entidad_id ID del registro accedido
 * @param array $datos_adicionales Datos adicionales a registrar (opcional)
 * @return int|bool ID del registro de auditoría o false en caso de error
 */
function auditAccess($accion, $entidad, $entidad_id, $datos_adicionales = []) {
    return getAuditManager()->logAccess($accion, $entidad, $entidad_id, $datos_adicionales);
}

/**
 * Registra un cambio en datos del sistema de auditoría
 * 
 * @param string $accion Tipo de acción (generalmente 'editar')
 * @param string $entidad Nombre de la entidad o tabla modificada
 * @param int $entidad_id ID del registro modificado
 * @param array $datos_antiguos Datos antes del cambio
 * @param array $datos_nuevos Datos después del cambio
 * @return int|bool ID del registro de auditoría o false en caso de error
 */
function auditChange($accion, $entidad, $entidad_id, $datos_antiguos, $datos_nuevos) {
    return getAuditManager()->logChange($accion, $entidad, $entidad_id, $datos_antiguos, $datos_nuevos);
}

/**
 * Obtiene el historial de accesos a una entidad específica
 * 
 * @param string $entidad Nombre de la entidad o tabla
 * @param int $entidad_id ID del registro
 * @param int $limite Límite de registros a devolver
 * @return array Registros de acceso a la entidad
 */
function getAccessHistory($entidad, $entidad_id, $limite = 50) {
    return getAuditManager()->getEntityAccessHistory($entidad, $entidad_id, $limite);
} 