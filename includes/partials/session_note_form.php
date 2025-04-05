<?php
/**
 * Partial para mostrar el formulario de notas de sesión
 * 
 * @param array $cita Datos de la cita
 * @param string $modalId ID del modal (opcional)
 * @param bool $esEdicion Si es para editar una nota existente
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Valores por defecto
$modalId = $modalId ?? 'notaModal';
$esEdicion = $esEdicion ?? !empty($cita['nota_contenido']);
?>

<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalId ?>Label">
                    <span class="material-symbols-rounded me-2"><?= $esEdicion ? 'edit' : 'add_circle' ?></span>
                    <?= $esEdicion ? 'Editar' : 'Añadir' ?> Nota de Sesión
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/modules/pacientes/guardar_nota.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="cita_id" value="<?= $cita['id'] ?>">
                    <input type="hidden" name="paciente_id" value="<?= $cita['paciente_id'] ?>">
                    <?php if (!empty($cita['nota_id'])): ?>
                        <input type="hidden" name="nota_id" value="<?= $cita['nota_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha y Hora de la Sesión:</label>
                        <p class="form-control-plaintext">
                            <span class="material-symbols-rounded me-2 text-primary">calendar_today</span>
                            <?= formatDateToView($cita['fecha']) ?> | 
                            <span class="material-symbols-rounded me-2 text-primary">schedule</span>
                            <?= formatTime($cita['hora_inicio']) ?> - <?= formatTime($cita['hora_fin']) ?>
                        </p>
                    </div>
                    
                    <?php if (isset($cita['nombre']) && isset($cita['apellidos'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Paciente:</label>
                        <p class="form-control-plaintext">
                            <span class="material-symbols-rounded me-2 text-primary">person</span>
                            <?= htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo de la Consulta:</label>
                        <div class="form-control-plaintext bg-light p-2 rounded">
                            <?= nl2br(htmlspecialchars($cita['motivo'])) ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contenido-<?= $cita['id'] ?>" class="form-label required-field">Notas de la Sesión:</label>
                        <textarea 
                            class="form-control" 
                            id="contenido-<?= $cita['id'] ?>" 
                            name="contenido" 
                            rows="10" 
                            required
                            placeholder="Registre observaciones, avances, diagnósticos o cualquier información relevante sobre esta sesión."
                        ><?= htmlspecialchars($cita['nota_contenido'] ?? '') ?></textarea>
                        <div class="form-text">
                            <span class="material-symbols-rounded me-1">info</span>
                            Registre observaciones, avances, diagnósticos o cualquier información relevante sobre esta sesión.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <span class="material-symbols-rounded me-2">cancel</span>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-2">save</span><?= $esEdicion ? 'Actualizar' : 'Guardar' ?> Nota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 