<?php
/**
 * Helper para funciones relacionadas con la interfaz de usuario
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Trunca un texto a una longitud específica
 * 
 * @param string $texto Texto a truncar
 * @param int $longitud Longitud máxima
 * @param string $sufijo Sufijo a añadir si se trunca
 * @return string Texto truncado
 */
function truncarTexto($texto, $longitud = 100, $sufijo = '...') {
    if (empty($texto) || strlen($texto) <= $longitud) {
        return $texto;
    }
    return substr($texto, 0, $longitud) . $sufijo;
}

/**
 * Devuelve la clase CSS para un estado de cita
 * 
 * @param string $estado Estado de la cita
 * @return string Clase CSS para el estado
 */
function getEstadoCitaClass($estado) {
    switch ($estado) {
        case 'pendiente':
            return 'bg-warning text-dark';
        case 'completada':
            return 'bg-success';
        case 'cancelada':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

/**
 * Función alternativa para obtener clase CSS de estado (compatibilidad)
 * 
 * @param string $estado Estado de la cita
 * @return string Clase CSS para el estado
 */
function getStatusClass($estado) {
    switch ($estado) {
        case 'pendiente':
        case 'programada':
            return 'bg-warning';
        case 'completada':
        case 'atendida':
            return 'bg-success';
        case 'cancelada':
            return 'bg-danger';
        case 'en_curso':
            return 'bg-info';
        case 'confirmada':
            return 'bg-primary';
        default:
            return 'bg-secondary';
    }
}

/**
 * Devuelve el icono para un estado de cita
 * 
 * @param string $estado Estado de la cita
 * @return string Nombre del icono de Material Symbols
 */
function getEstadoCitaIcon($estado) {
    switch ($estado) {
        case 'pendiente':
            return 'pending';
        case 'completada':
            return 'check_circle';
        case 'cancelada':
            return 'cancel';
        default:
            return 'help';
    }
}

/**
 * Genera un color aleatorio para eventos de calendario
 * 
 * @return string Color en formato hexadecimal
 */
function randomColor() {
    $colors = [
        '#3498db', // Azul
        '#2ecc71', // Verde
        '#9b59b6', // Morado
        '#e74c3c', // Rojo
        '#f39c12', // Naranja
        '#1abc9c', // Turquesa
        '#34495e', // Azul oscuro
        '#16a085', // Verde oscuro
        '#27ae60', // Verde fuerte
        '#d35400', // Naranja oscuro
        '#c0392b', // Rojo oscuro
        '#8e44ad'  // Morado oscuro
    ];
    return $colors[array_rand($colors)];
}

/**
 * Muestra mensajes de alerta y los elimina de la sesión
 * 
 * @return string HTML con los mensajes de alerta o cadena vacía
 */
function mostrarAlertas() {
    $html = '';
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $html = '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
        $html .= $alert['message'];
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
        unset($_SESSION['alert']);
    }
    return $html;
}

/**
 * Genera un campo de formulario con etiqueta y mensajes de error
 * 
 * @param string $tipo Tipo de input (text, email, number, etc)
 * @param string $nombre Nombre del campo
 * @param string $etiqueta Texto de la etiqueta
 * @param array $atributos Atributos adicionales para el campo
 * @param array $errores Array de errores de validación
 * @return string HTML del campo de formulario
 */
function generarCampo($tipo, $nombre, $etiqueta, $atributos = [], $errores = []) {
    $id = $atributos['id'] ?? $nombre;
    $valor = $atributos['value'] ?? ($_POST[$nombre] ?? '');
    $placeholder = $atributos['placeholder'] ?? $etiqueta;
    $required = isset($atributos['required']) && $atributos['required'] ? 'required' : '';
    $clase = 'form-control ' . ($atributos['class'] ?? '');
    $clase .= isset($errores[$nombre]) ? ' is-invalid' : '';
    
    // Eliminar atributos que ya hemos procesado
    unset($atributos['id'], $atributos['value'], $atributos['placeholder'], $atributos['required'], $atributos['class']);
    
    // Construir string de atributos adicionales
    $atributos_str = '';
    foreach ($atributos as $key => $value) {
        $atributos_str .= " $key=\"$value\"";
    }
    
    $html = '<div class="mb-3">';
    $html .= "<label for=\"$id\" class=\"form-label\">$etiqueta";
    if ($required) {
        $html .= ' <span class="text-danger">*</span>';
    }
    $html .= "</label>";
    
    if ($tipo === 'textarea') {
        $html .= "<textarea id=\"$id\" name=\"$nombre\" class=\"$clase\" placeholder=\"$placeholder\" $required $atributos_str>$valor</textarea>";
    } else {
        $html .= "<input type=\"$tipo\" id=\"$id\" name=\"$nombre\" value=\"$valor\" class=\"$clase\" placeholder=\"$placeholder\" $required $atributos_str>";
    }
    
    if (isset($errores[$nombre])) {
        $html .= "<div class=\"invalid-feedback\">{$errores[$nombre]}</div>";
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera un breadcrumb (miga de pan) para la navegación
 * 
 * @param array $items Array de items [nombre => url]
 * @return string HTML del breadcrumb
 */
function generarBreadcrumb($items) {
    $html = '<nav aria-label="breadcrumb" class="mb-4">';
    $html .= '<ol class="breadcrumb">';
    
    // Añadir ítem de inicio
    $html .= '<li class="breadcrumb-item"><a href="'.BASE_URL.'/" class="d-flex align-items-center"><span class="material-symbols-rounded icon-sm">home</span></a></li>';
    
    foreach ($items as $nombre => $url) {
        if(is_array($url)) { // Comprobar si es un array multidimensional
            continue; // Saltar este elemento
        }
        $html .= '<li class="breadcrumb-item'.(($url === '#') ? ' active' : '').'">';
        $html .= ($url !== '#') ? '<a href="'.htmlspecialchars($url).'">'.htmlspecialchars($nombre).'</a>' : htmlspecialchars($nombre);
        $html .= '</li>';
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Genera los controles de paginación
 * 
 * @param int $pagina Página actual
 * @param int $total_paginas Total de páginas
 * @param string $base_url URL base para los enlaces
 * @param array $params Parámetros adicionales para la URL
 * @return string HTML de la paginación
 */
function generarPaginacion($pagina, $total_paginas, $base_url, $params = []) {
    if ($total_paginas <= 1) {
        return '';
    }
    
    // Construir string de parámetros
    $params_str = '';
    foreach ($params as $key => $value) {
        if ($key !== 'pagina') { // Excluir el parámetro de página
            $params_str .= "&$key=$value";
        }
    }
    
    $html = '<nav aria-label="Navegación de páginas">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Botón Anterior
    $anterior_disabled = $pagina <= 1 ? 'disabled' : '';
    $html .= "<li class=\"page-item $anterior_disabled\">";
    $html .= "<a class=\"page-link\" href=\"$base_url?pagina=" . ($pagina - 1) . "$params_str\" aria-label=\"Anterior\">";
    $html .= '<span aria-hidden="true">&laquo;</span>';
    $html .= '</a></li>';
    
    // Números de página
    $rango = 2; // Número de páginas a mostrar antes y después de la actual
    
    for ($i = max(1, $pagina - $rango); $i <= min($total_paginas, $pagina + $rango); $i++) {
        $active = $i === $pagina ? 'active' : '';
        $html .= "<li class=\"page-item $active\"><a class=\"page-link\" href=\"$base_url?pagina=$i$params_str\">$i</a></li>";
    }
    
    // Botón Siguiente
    $siguiente_disabled = $pagina >= $total_paginas ? 'disabled' : '';
    $html .= "<li class=\"page-item $siguiente_disabled\">";
    $html .= "<a class=\"page-link\" href=\"$base_url?pagina=" . ($pagina + 1) . "$params_str\" aria-label=\"Siguiente\">";
    $html .= '<span aria-hidden="true">&raquo;</span>';
    $html .= '</a></li>';
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

?> 