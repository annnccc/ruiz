<?php
/**
 * Instalador del módulo de dashboard personalizable
 * Este script crea las tablas necesarias y añade los widgets iniciales
 */

// En lugar de redefinir BASE_URL, usamos una ruta relativa
$relativePath = '../';

// Incluir archivos necesarios
require_once $relativePath . 'includes/config.php';
require_once $relativePath . 'includes/db.php';
require_once $relativePath . 'includes/functions.php';

// Comprobar si el usuario tiene permisos
if (!esAdmin()) {
    echo "No tienes permisos para realizar esta operación.";
    exit;
}

// Obtener una conexión a la base de datos
try {
    $db = getDB();
    
    // Comenzar una transacción
    $db->beginTransaction();
    
    // Leer y ejecutar el archivo SQL de esquema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $queries = explode(';', $schema);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
        }
    }
    
    // Confirmar la transacción
    $db->commit();
    
    // Mostrar mensaje de éxito
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>¡Instalación completada!</h4>";
    echo "<p>Las tablas para el dashboard personalizable han sido creadas correctamente.</p>";
    echo "<p><a href='" . BASE_URL . "' style='color: #155724; text-decoration: underline;'>Volver al dashboard</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    // Revertir en caso de error solo si hay una transacción activa
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Mostrar mensaje de error
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Error durante la instalación</h4>";
    echo "<p>No se han podido crear las tablas: " . $e->getMessage() . "</p>";
    echo "<p><a href='" . BASE_URL . "' style='color: #721c24; text-decoration: underline;'>Volver al dashboard</a></p>";
    echo "</div>";
} 