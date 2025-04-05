<?php
/**
 * Widget de próximas citas para el dashboard
 * Muestra una tabla con las citas próximas (no incluye las de hoy)
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
                    <span class="material-symbols-rounded text-primary me-2">event_upcoming</span>
                    Próximas citas
                </h5>
                <a href="<?= BASE_URL ?>/modules/citas/list.php" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($proximas_citas)): ?>
                <div class="text-center py-4 text-muted">
                    <span class="material-symbols-rounded d-block mb-3" style="font-size: 2.5rem;">calendar_month</span>
                    <h5>No hay próximas citas</h5>
                    <p>No hay citas programadas para los próximos días.</p>
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
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximas_citas as $cita): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?= formatDateToView($cita['fecha']) ?></div>
                                        <div class="small text-muted"><?= formatTime($cita['hora_inicio']) ?></div>
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
                                            <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver">
                                                <?= heroicon_outline('eye', 'heroicon-sm') ?>
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