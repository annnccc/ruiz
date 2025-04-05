<?php
/**
 * Plantilla para nuevos módulos
 * 
 * Este archivo sirve como ejemplo para mantener consistencia en la estructura
 * del proyecto y evitar cargas duplicadas de archivos y funciones.
 */

// Cargar configuración - Solo necesita cargarse una vez
// Esto cargará automáticamente la base de datos y los helpers
require_once __DIR__ . '/../includes/config.php';

// Requerir autenticación si es necesario
// requiereLogin();

// Preparar variables para la vista
$titulo_pagina = "Título del Módulo";
$breadcrumbs = [
    'Sección' => BASE_URL . '/modules/seccion/list.php',
    'Subsección' => '#'
];

// Lógica del módulo
try {
    // Obtener conexión a la base de datos
    $db = getDB();
    
    // Tu código aquí...
    
} catch (PDOException $e) {
    setAlert('danger', 'Error: ' . $e->getMessage());
}

// Iniciar captura del contenido de la página
startPageContent();
?>

<!-- Contenido de la página -->
<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">description</span><?= $titulo_pagina ?>
        </h1>
        <div>
            <!-- Botones de acción -->
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include_once ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include_once ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Contenido principal -->
    <div class="row">
        <div class="col-12">
            <!-- Tu código aquí -->
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar página
endPageContent($titulo_pagina);
?> 