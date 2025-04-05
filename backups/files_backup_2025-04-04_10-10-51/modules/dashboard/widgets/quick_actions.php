<?php
/**
 * Widget de acciones rápidas para el dashboard
 * Muestra botones de acceso rápido a las acciones más frecuentes
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
                    <span class="material-symbols-rounded text-primary me-2">speed</span>
                    Acciones rápidas
                </h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="<?= BASE_URL ?>/modules/citas/create.php" class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                    <span class="material-symbols-rounded text-primary">add_task</span>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-body">Nueva cita</h6>
                                    <p class="mb-0 small text-muted">Programar una nueva cita</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= BASE_URL ?>/modules/pacientes/create.php" class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                    <span class="material-symbols-rounded text-success">person_add</span>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-body">Nuevo paciente</h6>
                                    <p class="mb-0 small text-muted">Registrar un nuevo paciente</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= BASE_URL ?>/modules/calendario/" class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                    <span class="material-symbols-rounded text-info">calendar_month</span>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-body">Calendario</h6>
                                    <p class="mb-0 small text-muted">Ver calendario de citas</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= BASE_URL ?>/modules/configuracion/" class="card border-0 shadow-sm h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                    <span class="material-symbols-rounded text-warning">settings</span>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-body">Configuración</h6>
                                    <p class="mb-0 small text-muted">Ajustes del sistema</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> 