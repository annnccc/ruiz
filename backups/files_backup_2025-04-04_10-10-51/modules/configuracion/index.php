<?php
// Auto-detector de problemas y redireccionador
require_once '../../includes/config.php';

// Redirección a la página de lista por defecto
if (isset($_GET['repair']) && $_GET['repair'] == '1') {
    // Si se solicita explícitamente la reparación
    header('Location: corregir_tabla.php');
    exit();
}

try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Comprobar la estructura de la tabla
    $stmt = $db->query("DESCRIBE configuracion");
    $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si la tabla tiene la estructura antigua
    if (in_array('nombre_clinica', $columnas) && !in_array('clave', $columnas)) {
        // Redireccionar a la página de corrección
        header('Location: list_fix.php');
        exit();
    } else {
        // Redireccionar a la página de lista normal
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    // Si hay un error en la consulta, probablemente la estructura es incorrecta
    header('Location: list_fix.php');
    exit();
}

// Por si acaso falla la redirección
header('Location: list.php');
exit(); 