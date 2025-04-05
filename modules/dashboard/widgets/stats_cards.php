<?php
/**
 * Widget de estadísticas generales para el dashboard
 * Muestra información sobre pacientes y citas en tarjetas
 */

// Evitar acceso directo
if (!defined('BASE_URL')) {
    exit('Acceso directo no permitido');
}
?>

<div class="<?= isset($widget['tamano']) ? $widget['tamano'] : 'col-12' ?> mb-4">
    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-primary">person</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Pacientes</h6>
                            <h2 class="mb-0 fw-bold"><?= $total_pacientes ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-success">calendar_month</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Citas totales</h6>
                            <h2 class="mb-0 fw-bold"><?= $total_citas ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-warning">pending</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Pendientes</h6>
                            <h2 class="mb-0 fw-bold"><?= $citas_pendientes ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-info">today</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Citas hoy</h6>
                            <h2 class="mb-0 fw-bold"><?= $citas_hoy ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 