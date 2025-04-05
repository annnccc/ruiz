<?php
/**
 * Layout público que proporciona la estructura HTML para áreas públicas
 * como login del portal del paciente, registro, etc.
 * 
 * @param string $title Título de la página
 * @param string $content Contenido principal de la página (opcional)
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?>Portal del Paciente - Clínica Ruiz</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Flatpickr -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/lib/flatpickr/dist/flatpickr.min.css">
    
    <!-- Select2 -->
    <link href="<?= BASE_URL ?>/assets/lib/select2/css/select2.min.css" rel="stylesheet" />
    <link href="<?= BASE_URL ?>/assets/lib/select2/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <!-- Opcional: CSS adicional -->
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="bg-light">
    <div class="wrapper">
        <!-- Contenido principal -->
        <div class="main-content w-100 m-0">
            <!-- Contenido de la página -->
            <?= $content ?>
            
            <!-- Footer público -->
            <footer class="footer py-3 bg-white">
                <div class="container-fluid">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-md-6 text-center text-md-start">
                            <p class="mb-0 text-muted">
                                &copy; <?= date('Y') ?> Clínica Ruiz Arrieta - Todos los derechos reservados
                            </p>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <p class="mb-0 text-muted">
                                <a href="<?= BASE_URL ?>/privacidad.php" class="text-decoration-none text-muted">Política de privacidad</a>
                                <span class="mx-2">|</span>
                                <a href="<?= BASE_URL ?>/terminos.php" class="text-decoration-none text-muted">Términos de uso</a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="<?= BASE_URL ?>/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="<?= BASE_URL ?>/assets/lib/jquery/jquery-3.6.0.min.js"></script>
    
    <!-- Flatpickr -->
    <script src="<?= BASE_URL ?>/assets/lib/flatpickr/dist/flatpickr.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/lib/flatpickr/dist/l10n/es.js"></script>
    
    <!-- Select2 -->
    <script src="<?= BASE_URL ?>/assets/lib/select2/js/select2.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/lib/select2/js/i18n/es.js"></script>
    
    <!-- Common JS -->
    <script src="<?= BASE_URL ?>/assets/js/common.js"></script>
    
    <!-- Opcional: JavaScript adicional -->
    <?php if (isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
</body>
</html> 