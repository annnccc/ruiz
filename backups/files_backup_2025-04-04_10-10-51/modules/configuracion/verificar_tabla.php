<?php
require_once '../../includes/config.php';

try {
    $db = getDB();
    
    // Verificar si la tabla existe
    $stmt = $db->query("SHOW TABLES LIKE 'configuracion'");
    if ($stmt->rowCount() == 0) {
        // Crear la tabla si no existe
        $sql = "CREATE TABLE IF NOT EXISTS configuracion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            clave VARCHAR(50) NOT NULL UNIQUE,
            valor TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        
        // Insertar configuración por defecto
        $configuracion_default = [
            'nombre_sistema' => 'Clínica Ruiz',
            'email_contacto' => '',
            'telefono_contacto' => '',
            'direccion' => '',
            'color_primario' => '#6366f1',
            'color_secundario' => '#0ea5e9',
            'logo' => 'assets/img/logo.png',
            'favicon' => 'assets/img/favicon.ico'
        ];
        
        $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)");
        foreach ($configuracion_default as $clave => $valor) {
            $stmt->execute(['clave' => $clave, 'valor' => $valor]);
        }
        
        echo "Tabla de configuración creada e inicializada correctamente.";
    } else {
        // Verificar si existen todas las configuraciones necesarias
        $stmt = $db->query("SELECT clave FROM configuracion");
        $configuraciones_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $configuraciones_requeridas = [
            'nombre_sistema' => 'Clínica Ruiz',
            'email_contacto' => '',
            'telefono_contacto' => '',
            'direccion' => '',
            'color_primario' => '#6366f1',
            'color_secundario' => '#0ea5e9',
            'logo' => 'assets/img/logo.png',
            'favicon' => 'assets/img/favicon.ico'
        ];
        
        $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)");
        foreach ($configuraciones_requeridas as $clave => $valor) {
            if (!in_array($clave, $configuraciones_existentes)) {
                $stmt->execute(['clave' => $clave, 'valor' => $valor]);
            }
        }
        
        echo "Configuraciones verificadas y actualizadas correctamente.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 