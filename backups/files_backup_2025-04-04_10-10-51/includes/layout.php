<?php
/**
 * Layout principal unificado
 * 
 * Este archivo centraliza la estructura HTML completa y la carga de recursos
 * para evitar duplicaciones y asegurar que los scripts se carguen una sola vez.
 * 
 * @param string $title Título de la página
 * @param string $content Contenido principal (opcional)
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Título predeterminado si no se especifica
$title = $title ?? ($titulo_pagina ?? 'Clínica Ruiz');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Clínica Ruiz</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
    
    <!-- jQuery (primero para que esté disponible para otros scripts) -->
    <script src="<?= BASE_URL ?>/assets/lib/jquery/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Google Fonts - Poppins/Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/lib/fontawesome/css/all.min.css">
    
    <!-- Flatpickr (para datepicker) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/lib/flatpickr/dist/flatpickr.min.css">
    
    <!-- Select2 (para selects avanzados) -->
    <link href="<?= BASE_URL ?>/assets/lib/select2/css/select2.min.css" rel="stylesheet" />
    <link href="<?= BASE_URL ?>/assets/lib/select2/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    
    <!-- Opcional: CSS adicional -->
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body data-theme="light" class="d-flex flex-column min-vh-100">

<div class="wrapper">
    <!-- Sidebar -->
    <?php include ROOT_PATH . '/includes/sidebar.php'; ?>
    
    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Header -->
        <?php include ROOT_PATH . '/includes/header.php'; ?>
        
        <!-- Contenido de la página -->
        <div class="content">
            <?php if (isset($content)): ?>
                <?= $content ?>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer class="footer bg-light border-top">
            <div class="container-fluid px-4 py-2">
                <div class="d-flex justify-content-between small">
                    <div>
                        <span class="text-muted fs-6">&copy; <?= date('Y') ?> Clínica Ruiz</span>
                    </div>
                    <div>
                        <span class="text-muted fs-6">v1.0</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Contenedor de notificaciones toast -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- SCRIPTS - Todos los JS centralizados aquí -->
<!-- Bootstrap JS Bundle with Popper -->
<script src="<?= BASE_URL ?>/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 para notificaciones modernas -->
<script src="<?= BASE_URL ?>/assets/lib/sweetalert2/sweetalert2.min.js"></script>

<!-- Flatpickr (para datepicker) -->
<script src="<?= BASE_URL ?>/assets/lib/flatpickr/dist/flatpickr.min.js"></script>
<script src="<?= BASE_URL ?>/assets/lib/flatpickr/dist/l10n/es.js"></script>

<!-- Select2 -->
<script src="<?= BASE_URL ?>/assets/lib/select2/js/select2.min.js"></script>

<!-- FullCalendar -->
<script src='<?= BASE_URL ?>/assets/lib/fullcalendar/fullcalendar.min.js'></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/common.js"></script>

<script>
    // Activar los tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Configuración de Flatpickr en español
    flatpickr.localize(flatpickr.l10ns.es);
    
    // Inicializar datepickers
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelectorAll('.datepicker').length > 0) {
            flatpickr('.datepicker', {
                dateFormat: "d/m/Y",
                allowInput: true
            });
        }
    });
</script>

<!-- Opcional: JS adicional -->
<?php if (isset($extra_js)): ?>
    <?= $extra_js ?>
<?php endif; ?>

</body>
</html> 