<?php
// Habilitar visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener el término de búsqueda
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

echo "<h1>Prueba de búsqueda directa</h1>";
echo "<p>Buscando: " . htmlspecialchars($search) . " (Página: $page)</p>";

// Construir la condición de búsqueda
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = "WHERE nombre LIKE :search_nombre OR apellidos LIKE :search_apellidos OR dni LIKE :search_dni OR telefono LIKE :search_telefono OR email LIKE :search_email";
}

try {
    $db = getDB();
    
    // Obtener total de registros
    $query = "SELECT COUNT(*) AS total FROM pacientes $searchCondition";
    $stmt = $db->prepare($query);
    
    if (!empty($search)) {
        $stmt->bindValue(':search_nombre', "%$search%");
        $stmt->bindValue(':search_apellidos', "%$search%");
        $stmt->bindValue(':search_dni', "%$search%");
        $stmt->bindValue(':search_telefono', "%$search%");
        $stmt->bindValue(':search_email', "%$search%");
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_results = $result['total'];
    
    echo "<p>Total de resultados: $total_results</p>";
    
    // Obtener pacientes
    $query = "SELECT * FROM pacientes $searchCondition ORDER BY apellidos ASC, nombre ASC LIMIT :offset, :records_per_page";
    $stmt = $db->prepare($query);
    
    if (!empty($search)) {
        $stmt->bindValue(':search_nombre', "%$search%");
        $stmt->bindValue(':search_apellidos', "%$search%");
        $stmt->bindValue(':search_dni', "%$search%");
        $stmt->bindValue(':search_telefono', "%$search%");
        $stmt->bindValue(':search_email', "%$search%");
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Resultados:</h2>";
    
    if (empty($pacientes)) {
        echo "<p>No se encontraron pacientes</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Apellidos</th><th>DNI</th><th>Teléfono</th><th>Email</th><th>Fecha Nacimiento</th></tr>";
        
        foreach ($pacientes as $paciente) {
            echo "<tr>";
            echo "<td>" . $paciente['id'] . "</td>";
            echo "<td>" . htmlspecialchars($paciente['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($paciente['apellidos']) . "</td>";
            echo "<td>" . htmlspecialchars($paciente['dni']) . "</td>";
            echo "<td>" . htmlspecialchars($paciente['telefono']) . "</td>";
            echo "<td>" . htmlspecialchars($paciente['email']) . "</td>";
            echo "<td>" . (isset($paciente['fecha_nacimiento']) ? formatDateToView($paciente['fecha_nacimiento']) : '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<h2>Estructura de la tabla pacientes:</h2>";
    $query = "DESCRIBE pacientes";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th></tr>";
    
    foreach ($estructura as $campo) {
        echo "<tr>";
        echo "<td>" . $campo['Field'] . "</td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . $campo['Default'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "<h2>Error en la base de datos:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "<h2>Error general:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
} 