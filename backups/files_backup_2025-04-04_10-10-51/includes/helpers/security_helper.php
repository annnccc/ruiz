<?php
/**
 * Helper para funciones relacionadas con seguridad
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Genera una contraseña aleatoria segura
 * 
 * @param int $longitud Longitud de la contraseña
 * @return string Contraseña generada
 */
function generarPassword($longitud = 10) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=';
    $password = '';
    $max = strlen($caracteres) - 1;
    
    for ($i = 0; $i < $longitud; $i++) {
        $password .= $caracteres[random_int(0, $max)];
    }
    
    return $password;
}

/**
 * Genera un hash seguro para una contraseña
 * 
 * @param string $password Contraseña a hashear
 * @return string Hash de la contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica si una contraseña coincide con un hash
 * 
 * @param string $password Contraseña a verificar
 * @param string $hash Hash almacenado
 * @return bool True si coincide, false en caso contrario
 */
function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera un token CSRF para proteger formularios
 * 
 * @return string Token CSRF
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica si un token CSRF es válido
 * 
 * @param string $token Token a verificar
 * @return bool True si es válido, false en caso contrario
 */
function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    $result = hash_equals($_SESSION['csrf_token'], $token);
    
    // Regenerar token después de verificar para prevenir reutilización
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    return $result;
}

/**
 * Verifica si el usuario tiene una sesión iniciada
 * 
 * @return bool True si tiene sesión, false en caso contrario
 */
function estaLogueado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario actual es administrador
 * Esta función ya está definida en includes/config.php, por lo que se evita su redeclaración
 * 
 * @return bool True si es administrador, false en caso contrario
 */
// La función esAdmin() ya está declarada en includes/config.php
// por lo que no se vuelve a declarar aquí para evitar duplicidades

/**
 * Redirige a la página de login si el usuario no está logueado
 * Esta función ya está definida en includes/config.php, por lo que se evita su redeclaración
 * 
 * @param string $mensaje Mensaje opcional a mostrar
 * @return void
 */
// La función requiereLogin() ya está declarada en includes/config.php
// por lo que no se vuelve a declarar aquí para evitar duplicidades

/**
 * Verifica si el usuario tiene un rol específico
 * 
 * @param string|array $roles Rol o roles permitidos
 * @param string $mensaje Mensaje opcional a mostrar
 * @return void
 */
function requiereRol($roles, $mensaje = 'No tiene permisos para acceder a esta página') {
    requiereLogin();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($_SESSION['usuario_rol'], $roles)) {
        setAlert('danger', $mensaje);
        redirect(BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Registra un intento de inicio de sesión fallido
 * 
 * @param string $usuario Nombre de usuario
 * @param string $ip Dirección IP
 * @return void
 */
function registrarIntentoFallido($usuario, $ip) {
    $db = getDB();
    
    // Registrar el intento fallido
    $stmt = $db->prepare("
        INSERT INTO intentos_login (usuario, ip, fecha)
        VALUES (:usuario, :ip, NOW())
    ");
    
    $stmt->bindParam(':usuario', $usuario);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
}

/**
 * Verifica si una IP o usuario ha excedido el límite de intentos de login
 * 
 * @param string $usuario Nombre de usuario
 * @param string $ip Dirección IP
 * @param int $limite Número máximo de intentos
 * @param int $ventana Ventana de tiempo en minutos
 * @return bool True si ha excedido el límite, false en caso contrario
 */
function haExcedidoIntentos($usuario, $ip, $limite = 5, $ventana = 15) {
    $db = getDB();
    
    // Contar intentos fallidos en la ventana de tiempo
    $stmt = $db->prepare("
        SELECT COUNT(*) as intentos
        FROM intentos_login
        WHERE (usuario = :usuario OR ip = :ip)
        AND fecha >= DATE_SUB(NOW(), INTERVAL :ventana MINUTE)
    ");
    
    $stmt->bindParam(':usuario', $usuario);
    $stmt->bindParam(':ip', $ip);
    $stmt->bindParam(':ventana', $ventana, PDO::PARAM_INT);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['intentos'] >= $limite;
}

/**
 * Limpia los intentos de login fallidos antiguos
 * 
 * @param int $horas Horas de antigüedad
 * @return void
 */
function limpiarIntentosFallidos($horas = 24) {
    $db = getDB();
    
    $stmt = $db->prepare("
        DELETE FROM intentos_login
        WHERE fecha < DATE_SUB(NOW(), INTERVAL :horas HOUR)
    ");
    
    $stmt->bindParam(':horas', $horas, PDO::PARAM_INT);
    $stmt->execute();
}
?> 