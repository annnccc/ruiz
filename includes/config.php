<?php
/**
 * Archivo de configuración principal
 * Contiene constantes y configuraciones generales del sistema
 */

// Prevenir acceso directo al archivo
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Definir la ruta raíz del proyecto
define('ROOT_PATH', dirname(__DIR__));

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'puwmiewv_clinica');
define('DB_USER', 'puwmiewv_clinica');
define('DB_PASS', 'Disorder12*');

// Configuración del sitio
define('APP_NAME', 'Clínica Ruiz');
define('APP_VERSION', '1.0.0');

// Determinar la URL base desde la configuración en la BD o usar el valor predeterminado
try {
    // Solo intentar leer de la BD si ya está instalada
    if (file_exists(ROOT_PATH . '/.installed')) {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'site_url' LIMIT 1");
        $stmt->execute();
        $site_url = $stmt->fetchColumn();
        
        // Usar el valor de la base de datos si existe, sino usar el predeterminado
        define('BASE_URL', !empty($site_url) ? rtrim($site_url, '/') : '/ruiz');
    } else {
        define('BASE_URL', '/ruiz');
    }
} catch (Exception $e) {
    // Si hay un error, usar el valor predeterminado
    define('BASE_URL', '/ruiz');
}

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    // Configurar parámetros de sesión para mejorar persistencia
    ini_set('session.gc_maxlifetime', 28800); // 8 horas de vida para la sesión
    ini_set('session.cookie_lifetime', 28800); // 8 horas de vida para la cookie
    
    // Configurar nombre de cookie seguro y asegurar que el path sea correcto
    session_name('CLINICA_SESS');
    session_set_cookie_params([
        'lifetime' => 28800,
        'path' => '/',
        'domain' => '',
        'secure' => false, // cambiar a true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Iniciar la sesión
    session_start();
    
    // Registrar información de depuración
    if (isset($_SESSION['usuario_id'])) {
        error_log("Sesión iniciada en config.php: Usuario ID=" . $_SESSION['usuario_id'] . ", Rol=" . ($_SESSION['usuario_rol'] ?? 'no definido'));
    } else {
        error_log("Sesión iniciada en config.php: No hay usuario autenticado");
    }
}

// Cargar archivos base del sistema
require_once ROOT_PATH . '/includes/db.php';      // Conexión a la base de datos y funciones relacionadas
require_once ROOT_PATH . '/includes/helpers.php'; // Sistema de helpers
require_once ROOT_PATH . '/includes/functions.php'; // Funciones generales

// Comprobar si la base de datos existe, si no, redirigir a instalación
if (!defined('INSTALL_MODE')) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT 1 FROM configuracion LIMIT 1");
        $stmt->execute();
    } catch (PDOException $e) {
        // Si la tabla no existe, probablemente la base de datos no está instalada
        if (strpos($_SERVER['REQUEST_URI'], 'install.php') === false) {
            redirect(BASE_URL . '/install.php');
        }
    }
}

/**
 * Verificar si el usuario está autenticado
 * Si no lo está, redirige al login
 */
function requiereLogin() {
    // Verificar si hay una sesión de usuario o de paciente
    if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['paciente_id'])) {
        setAlert('warning', 'Debes iniciar sesión para acceder a esta página');
        redirect(BASE_URL . '/login.php');
    }
    
    // Registro de depuración para ayudar a diagnosticar el problema
    if (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
        $usuario_rol = $_SESSION['usuario_rol'] ?? 'no definido';
        error_log("Usuario autenticado: ID $usuario_id, Rol: $usuario_rol");
    }
}

/**
 * Verificar si el usuario tiene rol de administrador
 * Si no lo tiene, redirige al dashboard
 */
function requiereAdmin() {
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        setAlert('danger', 'No tienes permisos para acceder a esta página');
        redirect(BASE_URL . '/index.php');
    }
}

/**
 * Obtener el nombre del usuario actual
 */
function getNombreUsuario() {
    return $_SESSION['usuario_nombre'] ?? 'Invitado';
}

/**
 * Verificar si el usuario actual es administrador
 */
function esAdmin() {
    $es_admin = isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
    if ($es_admin) {
        error_log("Usuario es admin: ID {$_SESSION['usuario_id']}, Rol: {$_SESSION['usuario_rol']}");
    } else {
        if (isset($_SESSION['usuario_rol'])) {
            error_log("Usuario NO es admin: ID {$_SESSION['usuario_id']}, Rol: {$_SESSION['usuario_rol']}");
        } else {
            error_log("Usuario sin rol definido");
        }
    }
    return $es_admin;
}

/**
 * Obtener el ID del usuario actual
 */
function getUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Verificar si el usuario actual es un paciente
 */
function isPaciente() {
    return isset($_SESSION['paciente_id']) && !empty($_SESSION['paciente_id']);
}

// Configuración de mensajes de error para depuración - Solo definir si no existe
if (!function_exists('mostrarAlertas')) {
    function mostrarAlertas() {
        if (isset($_SESSION['alert'])) {
            $alert = $_SESSION['alert'];
            unset($_SESSION['alert']);
            echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
            echo $alert['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}
?> 