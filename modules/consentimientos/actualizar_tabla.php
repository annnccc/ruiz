<?php
/**
 * Script para actualizar la tabla consentimientos_enviados
 * Añade la columna fecha_caducidad si no existe
 */

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Requiere autenticación y rol de administrador
requiereLogin();
if ($_SESSION['usuario_rol'] !== 'admin') {
    setAlert('danger', 'Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.');
    redirect(BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Actualización de Tabla Consentimientos";

// Iniciar el buffer de salida
startPageContent();

// Conexión a la base de datos
try {
    $db = getDB();
    
    // Verificar si la columna ya existe
    $stmt = $db->query("SHOW COLUMNS FROM consentimientos_enviados LIKE 'fecha_caducidad'");
    $columnaExiste = $stmt->rowCount() > 0;
    
    if ($columnaExiste) {
        echo '<div class="container-fluid py-4">';
        echo '<div class="alert alert-info">';
        echo 'La columna <strong>fecha_caducidad</strong> ya existe en la tabla <strong>consentimientos_enviados</strong>.';
        echo '</div>';
    } else {
        // Añadir la columna fecha_caducidad
        $db->exec("ALTER TABLE consentimientos_enviados ADD COLUMN fecha_caducidad timestamp NULL DEFAULT NULL AFTER fecha_firma");
        
        // Actualizar los registros existentes: establecer fecha_caducidad 7 días después de fecha_envio
        $db->exec("UPDATE consentimientos_enviados SET fecha_caducidad = DATE_ADD(fecha_envio, INTERVAL 7 DAY) WHERE estado = 'pendiente'");
        
        echo '<div class="container-fluid py-4">';
        echo '<div class="alert alert-success">';
        echo '<p><strong>¡Tabla actualizada correctamente!</strong></p>';
        echo '<p>Se ha añadido la columna <strong>fecha_caducidad</strong> a la tabla <strong>consentimientos_enviados</strong>.</p>';
        echo '<p>Los consentimientos pendientes han sido actualizados con una fecha de caducidad de 7 días después de su envío.</p>';
        echo '</div>';
    }
    
    echo '<a href="' . BASE_URL . '/modules/consentimientos/listar.php" class="btn btn-primary">';
    echo '<span class="material-symbols-rounded me-2">list</span>Volver a Consentimientos';
    echo '</a>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div class="container-fluid py-4">';
    echo '<div class="alert alert-danger">';
    echo 'Error al actualizar la tabla: ' . $e->getMessage();
    echo '</div>';
    echo '<a href="' . BASE_URL . '/modules/consentimientos/listar.php" class="btn btn-primary">';
    echo '<span class="material-symbols-rounded me-2">list</span>Volver a Consentimientos';
    echo '</a>';
    echo '</div>';
}

// Finalizar el buffer y mostrar la página
endPageContent($pageTitle);
?> 