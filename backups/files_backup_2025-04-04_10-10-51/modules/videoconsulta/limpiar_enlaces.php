<?php
/**
 * Script para limpiar enlaces de acceso caducados
 * 
 * Este script se puede ejecutar mediante una tarea programada (cron job)
 * para limpiar los enlaces de acceso que han expirado.
 */

// Solo permitir ejecución desde CLI o con autenticación administrativa
if (php_sapi_name() != 'cli') {
    // Si no es CLI, requerir autenticación como administrador
    require_once '../../includes/config.php';
    require_once '../../includes/functions.php';
    
    if (!isLoggedIn() || !esAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Acceso denegado';
        exit;
    }
} else {
    // En modo CLI, cargar configuración directamente
    $base_path = dirname(dirname(dirname(__FILE__)));
    require_once $base_path . '/includes/config.php';
    require_once $base_path . '/includes/functions.php';
    require_once $base_path . '/includes/db.php';
}

try {
    $db = getDB();
    
    // Obtener la fecha y hora actual
    $ahora = date('Y-m-d H:i:s');
    
    // Buscar videoconsultas con enlaces expirados
    $stmt = $db->prepare("
        SELECT id, enlace_acceso, fecha_expiracion
        FROM videoconsultas
        WHERE fecha_expiracion < :ahora
        AND enlace_acceso IS NOT NULL
    ");
    $stmt->bindParam(':ahora', $ahora);
    $stmt->execute();
    
    $caducados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($caducados) > 0) {
        // Limpiar los enlaces caducados
        $stmt = $db->prepare("
            UPDATE videoconsultas
            SET enlace_acceso = NULL
            WHERE fecha_expiracion < :ahora
            AND enlace_acceso IS NOT NULL
        ");
        $stmt->bindParam(':ahora', $ahora);
        $stmt->execute();
        
        $numActualizados = $stmt->rowCount();
        
        $mensaje = "Se han limpiado $numActualizados enlaces caducados.";
    } else {
        $mensaje = "No se encontraron enlaces caducados para limpiar.";
    }
    
    // Salida del resultado
    if (php_sapi_name() == 'cli') {
        echo $mensaje . "\n";
    } else {
        setAlert('success', $mensaje);
        header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php');
    }
    
} catch (PDOException $e) {
    $error = "Error al limpiar enlaces: " . $e->getMessage();
    
    if (php_sapi_name() == 'cli') {
        echo $error . "\n";
    } else {
        setAlert('danger', $error);
        header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php');
    }
} 