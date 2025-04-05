<?php
/**
 * Layout principal que proporciona la estructura HTML completa
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
    <title><?= isset($title) ? $title . ' - ' : '' ?>Clínica Ruiz</title>
    
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    
    <!-- Opcional: CSS adicional -->
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include ROOT_PATH . '/includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <!-- Header -->
            <?php include ROOT_PATH . '/includes/header.php'; ?>
            
            <!-- Contenido de la página -->
            <?= $content ?>
        </div>
        
        <!-- Footer -->
        <?php include ROOT_PATH . '/includes/footer.php'; ?>
    </div>
</body>
</html> 