<?php
// Configuración general
define('APP_NAME', 'ClinicSys');
define('APP_VERSION', '1.0.0');

// Configuración de la zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'clinica');

// Configuración de rutas
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/ruiz');
define('ROOT_PATH', dirname(__FILE__, 2));

// Configuración de sesión
session_start();

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar archivos necesarios
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';

// Inicializar la base de datos
$db = new Database();

// Comprobar si la base de datos existe, si no, redirigir a instalación
if (!defined('INSTALL_MODE')) {
    try {
        $db->query("SELECT 1 FROM configuracion LIMIT 1");
        $db->execute();
    } catch (PDOException $e) {
        // Si la tabla no existe, probablemente la base de datos no está instalada
        if (strpos($_SERVER['REQUEST_URI'], 'install.php') === false) {
            redirect(BASE_URL . '/install.php');
        }
    }
} 