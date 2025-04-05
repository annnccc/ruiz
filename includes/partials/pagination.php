<?php
/**
 * Partial para mostrar controles de paginación
 * 
 * @param int $pagina_actual Página actual
 * @param int $total_paginas Total de páginas
 * @param string $url_base URL base para los enlaces (sin parámetros)
 * @param array $parametros Parámetros adicionales para mantener en la URL
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Evitar mostrar paginación si solo hay una página
if ($total_paginas <= 1) {
    return;
}

// Preparar cadena de parámetros adicionales
$params_str = '';
if (!empty($parametros)) {
    foreach ($parametros as $key => $value) {
        if ($key !== 'pagina' && !empty($value)) {
            $params_str .= "&$key=" . urlencode($value);
        }
    }
}
?>

<nav aria-label="Navegación de páginas">
    <ul class="pagination justify-content-center">
        <!-- Botón Anterior -->
        <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $url_base ?>?pagina=<?= $pagina_actual - 1 . $params_str ?>" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        
        <!-- Números de página -->
        <?php 
        $rango = 2; // Cantidad de páginas a mostrar antes y después de la actual
        
        // Determinar rango de páginas a mostrar
        $inicio_rango = max(1, $pagina_actual - $rango);
        $fin_rango = min($total_paginas, $pagina_actual + $rango);
        
        // Si el inicio del rango no es 1, mostrar puntos suspensivos
        if ($inicio_rango > 1): 
        ?>
            <li class="page-item">
                <a class="page-link" href="<?= $url_base ?>?pagina=1<?= $params_str ?>">1</a>
            </li>
            <?php if ($inicio_rango > 2): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Mostrar páginas en el rango -->
        <?php for ($i = $inicio_rango; $i <= $fin_rango; $i++): ?>
            <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                <a class="page-link" href="<?= $url_base ?>?pagina=<?= $i . $params_str ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        
        <!-- Si el fin del rango no es la última página, mostrar puntos suspensivos -->
        <?php if ($fin_rango < $total_paginas): ?>
            <?php if ($fin_rango < $total_paginas - 1): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= $url_base ?>?pagina=<?= $total_paginas . $params_str ?>"><?= $total_paginas ?></a>
            </li>
        <?php endif; ?>
        
        <!-- Botón Siguiente -->
        <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $url_base ?>?pagina=<?= $pagina_actual + 1 . $params_str ?>" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav> 