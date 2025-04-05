<?php
/**
 * Partial para mostrar una tarjeta con información de paciente
 * 
 * @param array $paciente Datos del paciente
 * @param bool $mostrar_acciones Si se deben mostrar botones de acción
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

// Edad del paciente
$edad = calcularEdad($paciente['fecha_nacimiento']);
?>

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Información del Paciente</h5>
        <?php if ($mostrar_acciones): ?>
        <div>
            <a href="<?= BASE_URL ?>/modules/pacientes/edit.php?id=<?= $paciente['id'] ?>" class="btn btn-sm btn-primary">
                <span class="material-symbols-rounded me-1">edit</span>Editar
            </a>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="text-center mb-4">
            <div class="bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 100px; height: 100px">
                <span class="material-symbols-rounded display-4 text-primary">account_circle</span>
            </div>
            <h4 class="mt-3"><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></h4>
            <p class="text-muted"><?= isset($paciente['numero_historia']) ? htmlspecialchars($paciente['numero_historia']) : 'Sin número de historia' ?></p>
        </div>
        
        <div class="row g-3">
            <div class="col-md-6">
                <p class="mb-1 text-muted small">DNI/NIE</p>
                <p class="mb-3 fw-bold"><?= $paciente['dni'] !== null ? htmlspecialchars($paciente['dni']) : 'No disponible' ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Fecha de Nacimiento</p>
                <p class="mb-3 fw-bold"><?= formatDateToView($paciente['fecha_nacimiento']) ?> (<?= $edad ?> años)</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Teléfono</p>
                <p class="mb-3 fw-bold"><?= $paciente['telefono'] !== null ? htmlspecialchars($paciente['telefono']) : 'No disponible' ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Email</p>
                <p class="mb-3 fw-bold"><?= empty($paciente['email']) ? 'No disponible' : htmlspecialchars($paciente['email']) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Sexo</p>
                <p class="mb-3 fw-bold"><?= $paciente['sexo'] == 'M' ? 'Masculino' : 'Femenino' ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Fecha de Registro</p>
                <p class="mb-3 fw-bold"><?= formatDateToView($paciente['fecha_registro']) ?></p>
            </div>
        </div>
        
        <?php if ($mostrar_acciones): ?>
        <div class="mt-4">
            <a href="<?= BASE_URL ?>/modules/citas/create.php?paciente_id=<?= $paciente['id'] ?>" class="btn btn-success w-100">
                <span class="material-symbols-rounded me-2">calendar_add_on</span>Nueva Cita
            </a>
        </div>
        <?php endif; ?>
    </div>
</div> 