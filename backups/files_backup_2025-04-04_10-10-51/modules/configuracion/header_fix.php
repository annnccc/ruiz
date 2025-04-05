<?php
// Este archivo asegura que los recursos se carguen correctamente incluso si hay problemas con la base de datos

// Definir constantes esenciales si no están definidas
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ruiz');
}

// Función para cargar recursos sin depender de la base de datos
function mostrarHeadRecursos() {
    $base_url = BASE_URL;
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Configuración del Sistema</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="' . $base_url . '/assets/css/bootstrap.min.css">
        <!-- Material Symbols -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
        <!-- Custom CSS -->
        <link rel="stylesheet" href="' . $base_url . '/assets/css/style.css">
        <style>
            body {
                background-color: #f8f9fa;
                font-family: \'Poppins\', sans-serif;
                padding-top: 20px;
            }
            .card {
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }
            .card-header {
                border-radius: 10px 10px 0 0 !important;
                font-weight: 600;
            }
            .btn-icon {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .btn-icon .material-symbols-rounded {
                margin-right: 5px;
            }
        </style>
    </head>
    <body>';
}

function mostrarFooterRecursos() {
    $base_url = BASE_URL;
    echo '<script src="' . $base_url . '/assets/js/bootstrap.bundle.min.js"></script>
    <script src="' . $base_url . '/assets/js/common.js"></script>
    </body>
    </html>';
}
?> 