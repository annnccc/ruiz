<?php
/**
 * Widget de citas de hoy para el dashboard
 * Muestra una tabla con las citas programadas para el día actual
 */

// Evitar acceso directo
if (!defined('BASE_URL')) {
    exit('Acceso directo no permitido');
}
?>

<div class="<?= isset($widget['tamano']) ? $widget['tamano'] : 'col-lg-6' ?> mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <span class="material-symbols-rounded text-primary me-2">today</span>
                    Citas de hoy (<?= date('d/m/Y') ?>)
                </h5>
                <a href="<?= BASE_URL ?>/modules/citas/list.php?fecha=<?= date('Y-m-d') ?>" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($citas_para_hoy)): ?>
                <div class="text-center py-4 text-muted">
                    <span class="material-symbols-rounded d-block mb-3" style="font-size: 2.5rem;">event_busy</span>
                    <h5>No hay citas para hoy</h5>
                    <p>No hay citas programadas para el día de hoy.</p>
                    <a href="<?= BASE_URL ?>/modules/citas/create.php" class="btn btn-primary mt-2">
                        <span class="material-symbols-rounded me-1">add</span>
                        Nueva cita
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas_para_hoy as $cita): ?>
                                <tr>
                                    <td>
                                        <span class="fw-medium"><?= formatTime($cita['hora_inicio']) ?></span>
                                        <?php if (!empty($cita['hora_fin'])): ?>
                                            <span class="text-muted">- <?= formatTime($cita['hora_fin']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle avatar-sm me-2">
                                                <?= strtoupper(substr($cita['nombre'], 0, 1) . substr($cita['apellidos'], 0, 1)) ?>
                                            </div>
                                            <div class="fw-medium"><?= htmlspecialchars($cita['apellidos'] . ', ' . $cita['nombre']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= getEstadoCitaClass($cita['estado']) ?>"><?= ucfirst(htmlspecialchars($cita['estado'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </a>
                                            <?php if ($cita['estado'] == 'pendiente'): ?>
                                                <a href="<?= BASE_URL ?>/modules/citas/edit.php?id=<?= $cita['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                                    <span class="material-symbols-rounded">edit</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 