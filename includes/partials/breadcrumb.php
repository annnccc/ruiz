<?php
/**
 * Partial para mostrar breadcrumbs (migas de pan)
 * 
 * @param array $breadcrumbs Array asociativo [nombre => url]
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Verificar si existe la variable $breadcrumbs, si no, usar la variable $items (por compatibilidad)
if (!isset($breadcrumbs) && isset($items)) {
    $breadcrumbs = $items;
} elseif (!isset($breadcrumbs)) {
    $breadcrumbs = []; // Si ninguna está definida, crear un array vacío
}
?>

<nav aria-label="breadcrumb" class="mt-2 mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>/">
                <span class="material-symbols-rounded me-1 small">home</span>Inicio
            </a>
        </li>
        
        <?php 
        $count = count($breadcrumbs);
        $i = 0;
        
        foreach ($breadcrumbs as $nombre => $url): 
            $i++;
            $activo = ($i === $count) ? ' active" aria-current="page' : '';
        ?>
            <li class="breadcrumb-item<?= $activo ?>">
                <?php if ($i === $count): ?>
                    <?= $nombre ?>
                <?php else: ?>
                    <a href="<?= $url ?>"><?= $nombre ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav> 