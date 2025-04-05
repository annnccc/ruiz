<?php
/**
 * Funciones de autenticación y autorización
 * 
 * Este archivo contiene funciones para manejar la autenticación y autorización
 * de usuarios en el sistema.
 */

// Incluir functions.php si no está incluido ya
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

/**
 * Verifica si el usuario actual tiene permisos de administrador
 * 
 * @return bool True si el usuario es administrador, false en caso contrario
 */
function isAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Verifica si el usuario actual es un médico
 * 
 * @return bool True si el usuario es médico, false en caso contrario
 */
function isMedico() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'medico';
}

/**
 * Verifica si el usuario actual es un paciente
 * 
 * @return bool True si el usuario es paciente, false en caso contrario
 */
function isPaciente() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'paciente';
}

/**
 * Verifica si el usuario tiene permisos para acceder a un recurso
 * 
 * @param string $recurso Identificador del recurso a verificar
 * @return bool True si tiene permisos, false en caso contrario
 */
function tienePermiso($recurso) {
    // Si es admin, tiene acceso a todo
    if (isAdmin()) {
        return true;
    }
    
    // Definir permisos por rol
    $permisos = [
        'medico' => [
            'pacientes_ver', 'pacientes_buscar', 'citas_gestionar', 
            'expedientes_ver', 'expedientes_editar', 'videoconsulta_acceder'
        ],
        'paciente' => [
            'citas_ver', 'perfil_editar', 'videoconsulta_acceder'
        ],
        'admin' => ['*'] // Admin tiene todos los permisos
    ];
    
    // Verificar si el rol actual tiene el permiso
    $rol = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '';
    if (!empty($rol) && isset($permisos[$rol])) {
        return in_array($recurso, $permisos[$rol]) || in_array('*', $permisos[$rol]);
    }
    
    return false;
}

/**
 * Verificar permisos y redirigir si no tiene acceso
 * 
 * @param string $recurso Identificador del recurso a verificar
 * @param string $redirectUrl URL a la que redirigir si no tiene permisos
 */
function verificarPermiso($recurso, $redirectUrl = '/index.php') {
    if (!tienePermiso($recurso)) {
        $_SESSION['error'] = 'No tienes permisos para acceder a esta sección.';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Cerrar la sesión del usuario
 */
function logout() {
    // Destruir variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    session_destroy();
    
    // Redireccionar al login
    header('Location: /login.php');
    exit;
} 