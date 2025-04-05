<?php
/**
 * Archivo principal para cargar todos los helpers
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Función para establecer mensajes de alerta
 * 
 * @param string $type Tipo de alerta (success, danger, warning, info)
 * @param string $message Mensaje de la alerta
 * @return void
 */
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Función para obtener mensajes de alerta
 * 
 * @return array|null Array con tipo y mensaje de alerta o null si no hay alertas
 */
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

/**
 * Función para redireccionar
 * 
 * @param string $url URL a la que redireccionar
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Función para generar un número único de historia
 * 
 * @return string Número de historia clínica
 */
function generarNumHistoria() {
    $prefijo = 'HC-';
    $secuencial = sprintf('%05d', mt_rand(1, 99999));
    return $prefijo . $secuencial;
}

// Cargar todos los helpers
$helpers = [
    'date_helper.php',
    'validation_helper.php',
    'ui_helper.php',
    'file_helper.php',
    'security_helper.php'
];

// Cargar cada helper
foreach ($helpers as $helper) {
    require_once ROOT_PATH . '/includes/helpers/' . $helper;
}
?> 