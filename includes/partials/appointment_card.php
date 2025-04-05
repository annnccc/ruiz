<?php
/**
 * Partial para mostrar una tarjeta con información de cita
 * 
 * @param array $cita Datos de la cita
 * @param bool $mostrar_acciones Si se deben mostrar botones de acción
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}
?>

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Información de la Cita</h5>
        <span class="badge <?= getEstadoCitaClass($cita['estado']) ?>">
            <span class="material-symbols-rounded small me-1"><?= getEstadoCitaIcon($cita['estado']) ?></span>
            <?= ucfirst($cita['estado']) ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4 text-center">
                <div class="bg-light rounded p-3">
                    <p class="text-muted mb-1 small">Fecha</p>
                    <div class="mb-2">
                        <span class="material-symbols-rounded me-2 text-primary">calendar_today</span>
                        <?= formatDateToView($cita['fecha']) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-light rounded p-3">
                    <p class="text-muted mb-1 small">Hora</p>
                    <div class="mb-2">
                        <span class="material-symbols-rounded me-2 text-primary">schedule</span>
                        <?= formatTime($cita['hora_inicio']) ?> - <?= formatTime($cita['hora_fin']) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-light rounded p-3">
                    <p class="text-muted mb-1 small">Duración</p>
                    <?php
                    $hora_inicio = strtotime($cita['hora_inicio']);
                    $hora_fin = strtotime($cita['hora_fin']);
                    $duracion = ($hora_fin - $hora_inicio) / 60;
                    ?>
                    <div class="mb-3">
                        <span class="material-symbols-rounded me-2 text-primary">timelapse</span>
                        <?= $duracion ?> minutos
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <h6 class="text-muted mb-2">Motivo de la Consulta</h6>
            <div class="p-3 bg-light rounded">
                <p class="mb-0"><?= nl2br(htmlspecialchars($cita['motivo'])) ?></p>
            </div>
        </div>
        
        <?php if (!empty($cita['nota_contenido'])): ?>
        <div class="mb-4">
            <h6 class="text-muted mb-2">Notas de la Sesión</h6>
            <div class="p-3 bg-light rounded">
                <p class="mb-0"><?= nl2br(htmlspecialchars($cita['nota_contenido'])) ?></p>
            </div>
            <div class="mt-2 text-end">
                <small class="text-muted">Última actualización: <?= formatDateToView($cita['nota_fecha_actualizacion']) ?> <?= substr($cita['nota_fecha_actualizacion'], 11, 5) ?></small>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Fecha de Creación</p>
                <p class="mb-0"><?= formatDateToView($cita['fecha_creacion']) ?> <?= substr($cita['fecha_creacion'], 11, 5) ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1 text-muted small">Última Actualización</p>
                <p class="mb-0"><?= formatDateToView($cita['ultima_actualizacion']) ?> <?= substr($cita['ultima_actualizacion'], 11, 5) ?></p>
            </div>
        </div>
        
        <?php if ($mostrar_acciones && $cita['estado'] !== 'cancelada'): ?>
        <div class="d-flex flex-wrap mt-3 gap-2">
            <?php if ($cita['estado'] === 'pendiente'): ?>
            <a href="<?= BASE_URL ?>/modules/citas/mark_completed.php?id=<?= $cita['id'] ?>" class="btn btn-sm btn-success">
                <span class="material-symbols-rounded">task_alt</span> Marcar como Completada
            </a>
            
            <a href="<?= BASE_URL ?>/modules/citas/cancel.php?id=<?= $cita['id'] ?>" class="btn btn-sm btn-danger">
                <span class="material-symbols-rounded">cancel</span> Cancelar Cita
            </a>
            <?php elseif ($cita['estado'] === 'completada'): ?>
            <a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#sessionNoteModal">
                <span class="material-symbols-rounded"><?= !empty($cita['nota_contenido']) ? 'edit_note' : 'note_add' ?></span> <?= !empty($cita['nota_contenido']) ? 'Editar Nota' : 'Añadir Nota' ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div> 