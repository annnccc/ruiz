<?php
/**
 * Helper para optimización de recursos
 * Centraliza la carga de imágenes y componentes
 */

/**
 * Genera el HTML para una imagen con lazy loading
 * 
 * @param string $src URL de la imagen
 * @param string $alt Texto alternativo
 * @param array $options Opciones adicionales (class, width, height, etc)
 * @return string HTML de la imagen optimizada
 */
function optimizedImage($src, $alt = '', $options = []) {
    // Valores por defecto
    $defaults = [
        'class' => 'img-responsive',
        'width' => null,
        'height' => null,
        'loading' => 'lazy', // "lazy" o "eager"
        'placeholder' => true,
        'sizes' => null, // Ej: '(max-width: 768px) 100vw, 50vw'
        'srcset' => null, // Ej: 'img-sm.jpg 500w, img-md.jpg 800w, img-lg.jpg 1200w'
        'ratio' => null, // Ej: '16:9', '4:3', '1:1', '21:9'
        'fit' => 'cover', // cover, contain
        'responsive_sizes' => false // Crear automáticamente srcset basado en convenciones de nombrado
    ];
    
    // Mezclar opciones por defecto con las proporcionadas
    $options = array_merge($defaults, $options);
    
    // Preparar clases CSS
    $classes = ['lazy-img'];
    if ($options['class']) {
        $classes = array_merge($classes, explode(' ', $options['class']));
    }
    $classAttr = 'class="' . implode(' ', $classes) . '"';
    
    // Preparar atributos width y height si existen
    $dimensionAttrs = '';
    if ($options['width']) {
        $dimensionAttrs .= ' width="' . htmlspecialchars($options['width']) . '"';
    }
    if ($options['height']) {
        $dimensionAttrs .= ' height="' . htmlspecialchars($options['height']) . '"';
    }
    
    // Preparar atributo de carga
    $loadingAttr = ' loading="' . htmlspecialchars($options['loading']) . '"';
    
    // Preparar srcset si existe
    $srcsetAttr = '';
    if ($options['srcset']) {
        $srcsetAttr = ' srcset="' . htmlspecialchars($options['srcset']) . '"';
    } elseif ($options['responsive_sizes']) {
        // Generar srcset automáticamente basado en el nombre del archivo
        $srcsetAttr = generateSrcset($src);
    }
    
    // Preparar sizes si existe
    $sizesAttr = '';
    if ($options['sizes']) {
        $sizesAttr = ' sizes="' . htmlspecialchars($options['sizes']) . '"';
    }
    
    // Comprobar si hay ratio para crear contenedor
    $useRatio = ($options['ratio'] !== null);
    $ratioClass = '';
    
    if ($useRatio) {
        switch ($options['ratio']) {
            case '1:1':
                $ratioClass = 'ratio-1x1';
                break;
            case '4:3':
                $ratioClass = 'ratio-4x3';
                break;
            case '16:9':
                $ratioClass = ''; // clase por defecto, no necesita especificarse
                break;
            case '21:9':
                $ratioClass = 'ratio-21x9';
                break;
            default:
                $ratioClass = '';
        }
    }
    
    $output = '';
    
    // Si usamos placeholder, envolver con div
    if ($options['placeholder']) {
        $output .= '<div class="img-placeholder' . ($useRatio ? ' img-ratio ' . $ratioClass : '') . '">';
    } elseif ($useRatio) {
        $output .= '<div class="img-ratio ' . $ratioClass . '">';
    }
    
    // Generar etiqueta img
    $output .= '<img ' . $classAttr . $dimensionAttrs . $loadingAttr;
    $output .= ' src="' . htmlspecialchars($src) . '"'; 
    $output .= ' alt="' . htmlspecialchars($alt) . '"';
    $output .= $srcsetAttr . $sizesAttr;
    $output .= ' data-src="' . htmlspecialchars($src) . '"';
    
    if ($options['fit']) {
        $output .= ' style="object-fit: ' . htmlspecialchars($options['fit']) . ';"';
    }
    
    $output .= '>';
    
    // Cerrar div si corresponde
    if ($options['placeholder'] || $useRatio) {
        $output .= '</div>';
    }
    
    return $output;
}

/**
 * Genera un conjunto de fuentes srcset basado en convenciones de nombrado
 * Asume que las imágenes siguen un patrón como "imagen.jpg", "imagen-sm.jpg", etc.
 * 
 * @param string $src URL de la imagen original
 * @return string Atributo srcset generado
 */
function generateSrcset($src) {
    $pathInfo = pathinfo($src);
    $dir = $pathInfo['dirname'] . '/';
    $filename = $pathInfo['filename'];
    $ext = isset($pathInfo['extension']) ? ('.' . $pathInfo['extension']) : '';
    
    // Definir tamaños y anchos
    $sizes = [
        'sm' => '500w',
        'md' => '800w',
        'lg' => '1200w'
    ];
    
    $srcset = [];
    
    // Agregar la imagen original sin sufijo
    $srcset[] = $src . ' 1200w';
    
    // Comprobar y agregar variantes si existen
    foreach ($sizes as $size => $width) {
        $sizeFile = $dir . $filename . '-' . $size . $ext;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . parse_url($sizeFile, PHP_URL_PATH))) {
            $srcset[] = $sizeFile . ' ' . $width;
        }
    }
    
    return implode(', ', $srcset);
}

/**
 * Carga un componente HTML de forma perezosa
 * 
 * @param string $url URL del componente
 * @param array $options Opciones adicionales
 * @return string HTML para el componente
 */
function lazyComponent($url, $options = []) {
    $defaults = [
        'class' => '',
        'placeholder_text' => 'Cargando...',
        'min_height' => null,
        'eager' => false, // Si es true, se carga inmediatamente
        'fallback' => null // Contenido a mostrar si el componente no se puede cargar
    ];
    
    $options = array_merge($defaults, $options);
    
    $classes = ['lazy-component'];
    if ($options['class']) {
        $classes = array_merge($classes, explode(' ', $options['class']));
    }
    $classAttr = 'class="' . implode(' ', $classes) . '"';
    
    $styleAttr = '';
    if ($options['min_height']) {
        $styleAttr = ' style="min-height: ' . htmlspecialchars($options['min_height']) . 'px;"';
    }
    
    $output = '<div ' . $classAttr . $styleAttr . ' data-component-url="' . htmlspecialchars($url) . '">';
    
    if ($options['eager']) {
        // Si es eager, incluir el contenido directamente a través de PHP
        ob_start();
        include $_SERVER['DOCUMENT_ROOT'] . parse_url($url, PHP_URL_PATH);
        $componentContent = ob_get_clean();
        $output .= $componentContent;
    } else {
        // Si es lazy, mostrar placeholder
        $output .= '<div class="text-center p-3">';
        $output .= '<div class="spinner-border spinner-border-sm" role="status"></div>';
        $output .= '<span class="ms-2">' . htmlspecialchars($options['placeholder_text']) . '</span>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Carga y agrupa scripts y estilos para reducir peticiones HTTP
 * 
 * @param array $resources Array de recursos a cargar
 * @param string $groupId ID opcional para referenciar el grupo
 * @return string HTML para cargar los recursos
 */
function loadResourceGroup($resources, $groupId = null) {
    $scripts = [];
    $styles = [];
    
    // Separar recursos por tipo
    foreach ($resources as $resource) {
        if (!isset($resource['type']) || !isset($resource['url'])) {
            continue;
        }
        
        if ($resource['type'] === 'script') {
            $scripts[] = $resource;
        } elseif ($resource['type'] === 'style') {
            $styles[] = $resource;
        }
    }
    
    $output = "<!-- Resource Group" . ($groupId ? ": {$groupId}" : "") . " -->\n";
    
    // Cargar estilos
    foreach ($styles as $style) {
        $output .= '<link rel="stylesheet" href="' . htmlspecialchars($style['url']) . '">';
    }
    
    // Cargar scripts
    foreach ($scripts as $script) {
        $async = isset($script['async']) && $script['async'] ? ' async' : '';
        $defer = isset($script['defer']) && $script['defer'] ? ' defer' : '';
        
        $output .= '<script src="' . htmlspecialchars($script['url']) . '"' . $async . $defer . '></script>';
    }
    
    return $output;
}

/**
 * Registra recursos para cargar mediante el optimizador de recursos JS
 * 
 * @param array $resources Array de recursos
 * @param string $groupId ID opcional del grupo
 * @return string HTML para registrar los recursos
 */
function registerResources($resources, $groupId = null) {
    $resourcesJson = json_encode($resources);
    $groupJson = $groupId ? json_encode($groupId) : 'null';
    
    $output = "<script>\n";
    $output .= "document.addEventListener('DOMContentLoaded', function() {\n";
    $output .= "    if (typeof ResourceOptimizer !== 'undefined') {\n";
    $output .= "        ResourceOptimizer.enqueue({$resourcesJson}, {$groupJson});\n";
    $output .= "    }\n";
    $output .= "});\n";
    $output .= "</script>\n";
    
    return $output;
}

/**
 * Inicializa la carga perezosa de elementos dentro de una sección
 * 
 * @param string $content El contenido HTML
 * @param array $options Opciones adicionales
 * @return string HTML con la sección lazy
 */
function lazySection($content, $options = []) {
    $defaults = [
        'class' => '',
        'min_height' => 200,
        'id' => 'lazy-section-' . uniqid()
    ];
    
    $options = array_merge($defaults, $options);
    
    $classes = ['lazy-section'];
    if ($options['class']) {
        $classes = array_merge($classes, explode(' ', $options['class']));
    }
    $classAttr = 'class="' . implode(' ', $classes) . '"';
    
    $idAttr = ' id="' . htmlspecialchars($options['id']) . '"';
    
    $styleAttr = '';
    if ($options['min_height']) {
        $styleAttr = ' style="min-height: ' . htmlspecialchars($options['min_height']) . 'px;"';
    }
    
    $output = '<section ' . $classAttr . $idAttr . $styleAttr . '>';
    $output .= $content;
    $output .= '</section>';
    
    return $output;
} 