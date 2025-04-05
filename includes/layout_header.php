<?php
/**
 * Cabecera del layout principal
 * Contiene la estructura HTML de apertura
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Cargar helper para optimización de recursos
require_once ROOT_PATH . '/includes/helper-resource-loader.php';

// Definir recursos principales en grupos para optimizar carga
$coreStyles = [
    ['type' => 'style', 'url' => BASE_URL . '/assets/lib/bootstrap/css/bootstrap.min.css'],
    ['type' => 'style', 'url' => BASE_URL . '/assets/css/style.css'],
    ['type' => 'style', 'url' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200'],
    ['type' => 'style', 'url' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200']
];

$fontStyles = [
    ['type' => 'style', 'url' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap'],
    ['type' => 'style', 'url' => BASE_URL . '/assets/lib/fontawesome/css/all.min.css']
];

$optionalStyles = [
    ['type' => 'style', 'url' => BASE_URL . '/assets/lib/flatpickr/dist/flatpickr.min.css']
];

$coreScripts = [
    ['type' => 'script', 'url' => BASE_URL . '/assets/lib/bootstrap/js/bootstrap.bundle.min.js', 'defer' => true],
    ['type' => 'script', 'url' => BASE_URL . '/assets/js/common.js', 'defer' => true]
];

// Determinar tema actual (claro/oscuro)
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo_pagina) ? $titulo_pagina . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/favicon.svg">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
    
    <!-- Recursos críticos - Cargados con prioridad -->
    <?= loadResourceGroup($coreStyles, 'core-styles') ?>
    
    <!-- Precarga de recursos críticos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Carga directa de la hoja de estilo de iconos para accesibilidad -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/icon-accessibility.css">
    
    <!-- Recursos secundarios - Cargados de forma optimizada -->
    <?= registerResources($fontStyles, 'font-styles') ?>
    
    <!-- Recursos opcionales, cargados bajo demanda -->
    <?= registerResources($optionalStyles, 'optional-styles') ?>
    
    <!-- Scripts principales -->
    <?= registerResources($coreScripts, 'core-scripts') ?>
    
    <!-- Carga directa de common.js para garantizar que esté disponible -->
    <script src="<?= BASE_URL ?>/assets/js/common.js"></script>
    
    <!-- Script para activar optimización y lazy loading -->
    <script>
        // Detectar soporte para IntersectionObserver
        const hasIntersectionObserver = 'IntersectionObserver' in window;
        
        // Agregar clases condicionales al documento según características soportadas
        document.documentElement.classList.add(
            hasIntersectionObserver ? 'has-intersection-observer' : 'no-intersection-observer'
        );
        
        // Cargar recursos críticos de forma diferida
        document.addEventListener('DOMContentLoaded', function() {
            // Disparar evento para cargar fuentes
            const fontResourcesEvent = new CustomEvent('resource:request', {
                detail: {
                    resources: <?= json_encode($fontStyles) ?>,
                    groupId: 'font-styles'
                }
            });
            document.dispatchEvent(fontResourcesEvent);
        });
    </script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<div class="wrapper">
    <!-- Sidebar -->
    <?php include ROOT_PATH . '/includes/sidebar.php'; ?>
    
    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Header -->
        <?php include ROOT_PATH . '/includes/header.php'; ?> 