<?php
/**
 * Partial para mostrar un modal de confirmación
 * 
 * @param string $id ID del modal
 * @param string $titulo Título del modal
 * @param string $mensaje Mensaje del modal
 * @param string $urlConfirmar URL para confirmar la acción
 * @param string $textoBoton Texto del botón de confirmación
 * @param string $tipoBoton Tipo del botón (danger, warning, etc)
 * @param string $icono Clase del icono para el botón (deprecated)
 * @param string $icono_material Nombre del icono de Material Symbols
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Valores por defecto
$id = $id ?? 'modalConfirmar';
$titulo = $titulo ?? 'Confirmar Acción';
$mensaje = $mensaje ?? '¿Está seguro de que desea realizar esta acción?';
$textoBoton = $textoBoton ?? 'Confirmar';
$tipoBoton = $tipoBoton ?? 'danger';
$icono = $icono ?? 'fa-check';

// Mapeo de iconos de FontAwesome a Material Symbols
$iconos_mapeados = [
    'fa-check' => 'check',
    'fa-trash' => 'delete',
    'fa-times' => 'close',
    'fa-check-circle' => 'check_circle',
    'fa-calendar-times' => 'event_remove',
    'fa-calendar-check' => 'event_available',
    'fa-save' => 'save',
    'fa-edit' => 'edit'
];

// Usar icono_material si está definido, o intentar mapear desde $icono
$icono_material = $icono_material ?? $iconos_mapeados[str_replace('fas ', '', $icono)] ?? 'check';
?>

<div class="modal fade" id="<?= $id ?>" tabindex="-1" aria-labelledby="<?= $id ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $id ?>Label"><?= $titulo ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?= $mensaje ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <span class="material-symbols-rounded me-2">cancel</span>Cancelar
                </button>
                <a href="<?= $urlConfirmar ?>" class="btn btn-<?= $tipoBoton ?>">
                    <span class="material-symbols-rounded me-2"><?= $icono_material ?></span><?= $textoBoton ?>
                </a>
            </div>
        </div>
    </div>
</div> 