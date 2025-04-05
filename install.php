<?php
// Desactivar la configuración actual para evitar errores
define('INSTALL_MODE', true);

// Definir constantes de base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'clinica');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializar variables
$error = "";
$success = "";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Leer el archivo SQL
        $sql = file_get_contents('clinica.sql');
        
        if (!$sql) {
            throw new Exception("No se pudo leer el archivo SQL.");
        }
        
        // Conectar a MySQL (sin seleccionar base de datos)
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Ejecutar múltiples consultas
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            if (trim($statement) !== '') {
                $pdo->exec($statement);
            }
        }
        
        $success = "¡Instalación completada con éxito! La base de datos ha sido creada y configurada.";
        
        // Redirigir al inicio después de 3 segundos
        header("refresh:3;url=index.php");
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Gestión de Clínica</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/favicon.svg">
    <link rel="icon" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/lib/fontawesome/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .install-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 15px;
        }
        .logo-img {
            width: 100px;
            margin: 0 auto 20px;
            display: block;
        }
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="install-header">
                <img src="assets/img/logo.png" alt="Logo" class="logo-img">
                <h1 class="h3 mb-3 fw-normal">Instalación del Sistema</h1>
                <p class="text-muted">Complete la instalación para comenzar a utilizar el sistema de gestión de clínica</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="material-symbols-rounded me-2">error</span><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="material-symbols-rounded me-2">check_circle</span><?= $success ?>
                <p class="mt-2 mb-0">Redirigiendo al inicio en 3 segundos...</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><span class="material-symbols-rounded me-2">database</span>Configuración de Base de Datos</h5>
                    <div class="alert alert-info">
                        <strong>Conexión configurada:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Host: <?= DB_HOST ?></li>
                            <li>Puerto: <?= DB_PORT ?></li>
                            <li>Usuario: <?= DB_USER ?></li>
                            <li>Contraseña: <?= str_repeat('*', strlen(DB_PASS)) ?></li>
                            <li>Base de datos: <?= DB_NAME ?></li>
                        </ul>
                    </div>
                    
                    <form method="post" action="">
                        <div class="alert alert-warning">
                            <span class="material-symbols-rounded me-2">warning</span>
                            <strong>Advertencia:</strong> Esta acción creará la base de datos y todas las tablas necesarias. 
                            Si la base de datos ya existe, se sobrescribirán los datos.
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span class="material-symbols-rounded me-2">auto_fix</span>Instalar Base de Datos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 