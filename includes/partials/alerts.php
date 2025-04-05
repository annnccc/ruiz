<?php
/**
 * Partial para mostrar alertas
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}
?>

<?php if ($alert = getAlert()): ?>
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
        <?= $alert['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?> 