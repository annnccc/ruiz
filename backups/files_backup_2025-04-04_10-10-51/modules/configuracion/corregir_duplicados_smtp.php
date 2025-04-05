<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere autenticación y derechos de administrador
requiereLogin();
if (!esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta página.');
    redirect(BASE_URL);
}

try {
    $db = getDB();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Listar claves SMTP que podrían estar duplicadas
    $claves_smtp = ['smtp_active', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_name'];
    
    // Para cada clave, buscar duplicados y corregir
    $log = [];
    foreach ($claves_smtp as $clave) {
        // Verificar si hay duplicados
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM configuracion WHERE clave = :clave");
        $stmt->bindParam(':clave', $clave);
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        if ($total > 1) {
            // Hay duplicados, necesitamos corregir
            $log[] = "La clave '$clave' tiene $total entradas duplicadas.";
            
            // Obtener el ID del primer registro (el que conservaremos)
            $stmt = $db->prepare("SELECT id FROM configuracion WHERE clave = :clave ORDER BY id ASC LIMIT 1");
            $stmt->bindParam(':clave', $clave);
            $stmt->execute();
            $id_conservar = $stmt->fetchColumn();
            
            // Obtener el valor más reciente (que podría estar en cualquier duplicado)
            $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = :clave ORDER BY id DESC LIMIT 1");
            $stmt->bindParam(':clave', $clave);
            $stmt->execute();
            $valor_reciente = $stmt->fetchColumn();
            
            // Actualizar el primer registro con el valor más reciente
            $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE id = :id");
            $stmt->bindParam(':valor', $valor_reciente);
            $stmt->bindParam(':id', $id_conservar);
            $stmt->execute();
            
            // Eliminar duplicados (todos excepto el primero)
            $stmt = $db->prepare("DELETE FROM configuracion WHERE clave = :clave AND id != :id");
            $stmt->bindParam(':clave', $clave);
            $stmt->bindParam(':id', $id_conservar);
            $stmt->execute();
            $eliminados = $stmt->rowCount();
            
            $log[] = "Se actualizó el registro con ID $id_conservar y se eliminaron $eliminados duplicados.";
        } else {
            $log[] = "La clave '$clave' no tiene duplicados.";
        }
    }
    
    // Confirmar cambios
    $db->commit();
    
    // Registrar log y mostrar mensaje de éxito
    $mensaje_log = implode("<br>", $log);
    setAlert('success', "Corrección completada con éxito.<br><small>$mensaje_log</small>");
    
} catch (PDOException $e) {
    // Revertir cambios en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setAlert('danger', 'Error al corregir duplicados: ' . $e->getMessage());
}

// Redirigir de vuelta a la configuración SMTP
redirect(BASE_URL . '/modules/configuracion/list.php#email');
?> 