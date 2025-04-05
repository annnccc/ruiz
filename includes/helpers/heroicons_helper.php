<?php
/**
 * Helper para Heroicons
 * 
 * Funciones para facilitar el uso de iconos Heroicons en el proyecto
 */

if (!defined('HEROICONS_PATH')) {
    define('HEROICONS_PATH', ROOT_PATH . '/assets/icons/heroicons');
}

/**
 * Obtiene el código SVG de un icono y lo devuelve con las clases CSS necesarias
 * 
 * @param string $name Nombre del icono
 * @param string $style Estilo del icono (outline o solid)
 * @param string $class Clases CSS adicionales
 * @return string Código SVG del icono
 */
function heroicon($name, $style = 'outline', $class = '') {
    $default_classes = 'heroicon';
    $classes = $default_classes . ' ' . $class;
    
    // Dos formas de cargar el icono
    // 1. Directamente desde archivos locales (si existen)
    $filepath = HEROICONS_PATH . "/{$style}/{$name}.svg";
    
    if (file_exists($filepath)) {
        $svg = file_get_contents($filepath);
        // Reemplazar la clase en el SVG y asegurar que width/height sean currentColor
        $svg = str_replace('<svg ', '<svg class="' . $classes . '" ', $svg);
        // Asegurar que no tenga tamaños fijos que sobreescriban nuestros estilos
        $svg = preg_replace('/width="[^"]*"/', '', $svg);
        $svg = preg_replace('/height="[^"]*"/', '', $svg);
        return $svg;
    }
    
    // 2. Desde CDN si no existe localmente
    $cdn_base = "https://heroicons.com";
    $cdn_url = "{$cdn_base}/{$style}/{$name}.svg";
    
    // Intentar cargar desde CDN
    $context = stream_context_create([
        'http' => [
            'timeout' => 2 // Timeout de 2 segundos
        ]
    ]);
    
    $svg = @file_get_contents($cdn_url, false, $context);
    
    if ($svg !== false) {
        // Reemplazar la clase y eliminar atributos de tamaño fijo
        $svg = str_replace('<svg ', '<svg class="' . $classes . '" ', $svg);
        $svg = preg_replace('/width="[^"]*"/', '', $svg);
        $svg = preg_replace('/height="[^"]*"/', '', $svg);
        return $svg;
    }
    
    // 3. Si todo falla, mostrar un placeholder o icono de error
    return '<svg class="' . $classes . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" /></svg>';
}

/**
 * Función más corta para cargar iconos outline (los más comunes)
 * 
 * @param string $name Nombre del icono
 * @param string $class Clases CSS adicionales
 * @return string Código SVG del icono
 */
function heroicon_outline($name, $class = '') {
    return heroicon($name, 'outline', $class);
}

/**
 * Función más corta para cargar iconos solid
 * 
 * @param string $name Nombre del icono
 * @param string $class Clases CSS adicionales
 * @return string Código SVG del icono
 */
function heroicon_solid($name, $class = '') {
    return heroicon($name, 'solid', $class);
}

/**
 * Incluye los estilos de Heroicons en la página
 */
function include_heroicons_styles() {
    echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/heroicons.css">';
} 