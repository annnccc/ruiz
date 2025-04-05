<?php
/**
 * Widget de últimas citas para el dashboard
 * Muestra una tabla con las citas más recientes
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
                    <span class="material-symbols-rounded text-primary me-2">calendar_month</span>
                    Últimas citas
                </h5>
                <a href="<?= BASE_URL ?>/modules/citas/list.php" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimas_citas)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No hay citas recientes</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimas_citas as $cita): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle avatar-sm me-2">
                                                <?= strtoupper(substr($cita['nombre'], 0, 1) . substr($cita['apellidos'], 0, 1)) ?>
                                            </div>
                                            <div class="fw-medium"><?= htmlspecialchars($cita['apellidos'] . ', ' . $cita['nombre']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= formatDateToView($cita['fecha']) ?> <small class="text-muted"><?= formatTime($cita['hora_inicio']) ?></small></td>
                                    <td>
                                        <span class="badge <?= getEstadoCitaClass($cita['estado']) ?>"><?= ucfirst(htmlspecialchars($cita['estado'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver">
                                                <?= heroicon_outline('eye', 'heroicon-sm') ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 