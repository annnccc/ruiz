<?php
/**
 * Script para crear la tabla notificaciones_leidas
 * Este script debe ejecutarse una sola vez para crear la estructura necesaria
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo '<div class="alert alert-danger">Acceso denegado. Debe ser administrador para ejecutar este script.</div>';
    exit;
}

// Establecer cabeceras para HTML
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Creación de la tabla de notificaciones_leidas</h1>";

try {
    $db = getDB();
    
    // Comprobar si la tabla existe
    $stmt = $db->prepare("
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'notificaciones_leidas'
    ");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Crear la tabla
        $db->exec("
            CREATE TABLE notificaciones_leidas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                cita_id INT NOT NULL,
                fecha_lectura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (usuario_id),
                INDEX (cita_id),
                UNIQUE KEY unique_notification (usuario_id, cita_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "<div style='color: green; margin: 20px 0;'>
            <p>✅ Tabla 'notificaciones_leidas' creada correctamente.</p>
            <p>Estructura:</p>
            <ul>
                <li>id - Identificador único</li>
                <li>usuario_id - ID del usuario que ha leído la notificación</li>
                <li>cita_id - ID de la cita relacionada con la notificación</li>
                <li>fecha_lectura - Fecha y hora en que se leyó la notificación</li>
            </ul>
        </div>";
    } else {
        echo "<div style='color: blue; margin: 20px 0;'>
            <p>ℹ️ La tabla 'notificaciones_leidas' ya existe en la base de datos.</p>
        </div>";
    }
    
    echo "<div style='margin: 20px 0;'>
        <a href='" . BASE_URL . "/index.php' style='padding: 10px 15px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px;'>Volver al panel principal</a>
    </div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; margin: 20px 0;'>
        <p>❌ Error al crear la tabla: " . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
    
    echo "<div style='margin: 20px 0;'>
        <a href='" . BASE_URL . "/index.php' style='padding: 10px 15px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px;'>Volver al panel principal</a>
    </div>";
}
?> 