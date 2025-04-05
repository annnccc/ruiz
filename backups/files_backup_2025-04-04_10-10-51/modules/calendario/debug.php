<?php
// Script para mostrar la estructura de la tabla de citas
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo 'Acceso no autorizado';
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Depuración de estructura de tablas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        h2, h3, h4 { margin-top: 20px; }
    </style>
</head>
<body>";

try {
    $db = getDB();
    
    // Obtener estructura de la tabla citas
    echo "<h2>Estructura de la tabla: citas</h2>";
    
    $query = "DESCRIBE citas";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] === null ? 'NULL' : $column['Default']) . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Mostrar algunas filas de ejemplo
    echo "<h3>Ejemplos de datos en la tabla citas</h3>";
    
    $query = "SELECT * FROM citas LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "<table>";
        
        // Cabeceras
        echo "<tr>";
        foreach (array_keys($rows[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        // Datos
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . (is_null($value) ? 'NULL' : htmlspecialchars((string)$value)) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No hay datos en la tabla citas</p>";
    }
    
    // Probar una consulta simple sin JOIN
    echo "<h3>Consulta simple de citas sin JOIN</h3>";
    
    $query = "SELECT * FROM citas ORDER BY fecha DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $simpleCitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($simpleCitas) > 0) {
        echo "<p>La consulta simple funciona correctamente.</p>";
    } else {
        echo "<p>No se encontraron resultados en la consulta simple.</p>";
    }
    
    // Probar la consulta problemática
    echo "<h3>Consulta con JOIN a pacientes</h3>";
    
    try {
        $query = "SELECT c.id, c.fecha, p.nombre 
                  FROM citas c 
                  JOIN pacientes p ON c.paciente_id = p.id 
                  LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $joinResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($joinResults) > 0) {
            echo "<p>La consulta JOIN con pacientes funciona correctamente.</p>";
        } else {
            echo "<p>No se encontraron resultados en la consulta JOIN con pacientes.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error en consulta JOIN con pacientes: " . $e->getMessage() . "</p>";
    }
    
    // Probar la consulta problemática con usuarios
    echo "<h3>Consulta con JOIN a usuarios (problemática)</h3>";
    
    try {
        $query = "SELECT c.id, c.fecha, u.nombre 
                  FROM citas c 
                  JOIN usuarios u ON c.usuario_id = u.id 
                  LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $joinResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($joinResults) > 0) {
            echo "<p>La consulta JOIN con usuarios funciona correctamente.</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Fecha</th><th>Nombre Usuario</th></tr>";
            
            foreach ($joinResults as $row) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['fecha'] . "</td>";
                echo "<td>" . $row['nombre'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No se encontraron resultados en la consulta JOIN con usuarios.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error en consulta JOIN con usuarios: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error general: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?> 