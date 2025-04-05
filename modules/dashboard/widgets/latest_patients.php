<?php
/**
 * Widget de pacientes recientes para el dashboard
 * Muestra una tabla con los pacientes añadidos recientemente
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
                    <span class="material-symbols-rounded text-primary me-2">group</span>
                    Pacientes recientes
                </h5>
                <a href="<?= BASE_URL ?>/modules/pacientes/list.php" class="btn btn-sm btn-primary">
                    Ver todos
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimos_pacientes)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">No hay pacientes recientes</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimos_pacientes as $paciente): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle avatar-sm me-2">
                                                <?= strtoupper(substr($paciente['nombre'], 0, 1) . substr($paciente['apellidos'], 0, 1)) ?>
                                            </div>
                                            <div class="fw-medium"><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><span class="material-symbols-rounded me-1 small text-muted">call</span> <?= htmlspecialchars($paciente['telefono']) ?></div>
                                        <div class="small text-muted"><span class="material-symbols-rounded me-1 small">mail</span> <?= htmlspecialchars($paciente['email']) ?></div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $paciente['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver">
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