<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'clinica';
$username = 'root';
$password = 'root';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Reseteo de Contraseña</h1>";
    
    // Nueva contraseña para el administrador
    $nueva_password = 'admin123';
    $hash_password = password_hash($nueva_password, PASSWORD_BCRYPT);
    
    // Actualizar la contraseña del usuario administrador
    $stmt = $db->prepare("UPDATE usuarios SET password = :password WHERE email = 'admin@clinica.com'");
    $stmt->bindParam(':password', $hash_password);
    $stmt->execute();
    
    $filas_afectadas = $stmt->rowCount();
    
    if ($filas_afectadas > 0) {
        echo "<p style='color:green'>✓ Contraseña restablecida con éxito para el usuario admin@clinica.com.</p>";
        echo "<p>Nueva contraseña: <strong>admin123</strong></p>";
        echo "<p>Hash generado: $hash_password</p>";
    } else {
        echo "<p style='color:orange'>⚠ No se encontró el usuario admin@clinica.com o la contraseña ya estaba establecida al mismo valor.</p>";
    }
    
    // Verificamos los usuarios existentes después del reseteo
    $stmt = $db->query("SELECT id, nombre, email, rol, activo FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usuarios) > 0) {
        echo "<h2>Usuarios existentes en el sistema:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($usuario['id']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['rol']) . "</td>";
            echo "<td>" . ($usuario['activo'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<p><a href='login.php' style='display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir a la página de login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 