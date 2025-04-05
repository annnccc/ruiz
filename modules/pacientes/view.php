<?php
/**
 * Módulo de Visualización de Paciente
 * Muestra los detalles de un paciente específico y su historial de citas
 */

require_once '../../includes/config.php';

// Requerir autenticación
//requiereLogin();

// Verificar si se proporcionó un ID de paciente
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'ID de paciente no válido');
    redirect(BASE_URL . '/modules/pacientes/list.php');
}

$paciente_id = (int)$_GET['id'];

try {
    $db = getDB();
    
    // Obtener datos del paciente
    $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = :id");
    $stmt->bindParam(':id', $paciente_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'Paciente no encontrado');
        redirect(BASE_URL . '/modules/pacientes/list.php');
    }
    
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener las últimas 5 citas del paciente
    $stmt = $db->prepare("
        SELECT c.*
        FROM citas c
        WHERE c.paciente_id = :paciente_id
        ORDER BY c.fecha DESC, c.hora_inicio DESC
        LIMIT 5
    ");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    $ultimas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todas las citas del paciente para el historial
    $stmt = $db->prepare("
        SELECT c.*
        FROM citas c
        WHERE c.paciente_id = :paciente_id
        ORDER BY c.fecha DESC, c.hora_inicio DESC
    ");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    $todas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener bonos activos del paciente
    $stmt = $db->prepare("
        SELECT b.*, 
               (b.num_sesiones_total - b.num_sesiones_disponibles) AS sesiones_utilizadas
        FROM bonos b
        WHERE b.paciente_id = :paciente_id
        ORDER BY b.estado ASC, b.fecha_compra DESC
    ");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    $bonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener la primera cita del paciente para calcular tiempo en consulta
    $stmt = $db->prepare("
        SELECT fecha 
        FROM citas 
        WHERE paciente_id = :paciente_id 
        ORDER BY fecha ASC 
        LIMIT 1
    ");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    $primera_cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular el número total de sesiones
    $total_sesiones = count($todas_citas);
    
    // Obtener la fecha de la última cita
    $ultima_cita_fecha = !empty($todas_citas) ? $todas_citas[0]['fecha'] : null;
    
    // Calcular frecuencia de visitas (solo si hay más de 1 cita)
    $frecuencia_visitas = '';
    if ($total_sesiones > 1 && !empty($primera_cita) && !empty($primera_cita['fecha'])) {
        $fecha_primera = new DateTime($primera_cita['fecha']);
        $fecha_ultima = new DateTime($ultima_cita_fecha);
        $dias_totales = $fecha_primera->diff($fecha_ultima)->days;
        
        if ($dias_totales > 0) {
            // Promedio de días entre citas
            $promedio_dias = round($dias_totales / ($total_sesiones - 1));
            
            if ($promedio_dias > 30) {
                $meses = round($promedio_dias / 30, 1);
                $frecuencia_visitas = "Aprox. cada " . $meses . " mes" . ($meses != 1 ? "es" : "");
            } else {
                $frecuencia_visitas = "Aprox. cada " . $promedio_dias . " día" . ($promedio_dias != 1 ? "s" : "");
            }
        }
    }
    
    // Calcular tiempo en consulta si existe primera cita
    $tiempo_en_consulta = '';
    if (!empty($primera_cita) && !empty($primera_cita['fecha'])) {
        $fecha_primera_cita = new DateTime($primera_cita['fecha']);
        $hoy = new DateTime();
        $diferencia = $fecha_primera_cita->diff($hoy);
        
        if ($diferencia->y > 0) {
            $tiempo_en_consulta = $diferencia->y . ' año' . ($diferencia->y > 1 ? 's' : '') . ' y ' . 
                                  $diferencia->m . ' mes' . ($diferencia->m > 1 ? 'es' : '');
        } else if ($diferencia->m > 0) {
            $tiempo_en_consulta = $diferencia->m . ' mes' . ($diferencia->m > 1 ? 'es' : '') . ' y ' . 
                                  $diferencia->d . ' día' . ($diferencia->d > 1 ? 's' : '');
        } else {
            $tiempo_en_consulta = $diferencia->d . ' día' . ($diferencia->d > 1 ? 's' : '');
        }
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos del paciente: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/pacientes/list.php');
}

// Título y breadcrumbs para la página
$titulo_pagina = "Detalle del Paciente";
$breadcrumbs = [
    'Pacientes' => BASE_URL . '/modules/pacientes/list.php',
    $paciente['nombre'] . ' ' . $paciente['apellidos'] => '#'
];

// Iniciar el buffer de salida
startPageContent();
?>

<!-- Carga directa de Material Symbols para garantizar los iconos -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">person</span><?= $titulo_pagina ?>
        </h1>
        <div>
            <a href="<?= BASE_URL ?>/modules/pacientes/list.php" class="btn btn-outline-secondary">
                <span class="material-symbols-rounded me-2">arrow_back</span>Volver
            </a>
            <a href="<?= BASE_URL ?>/modules/consentimientos/enviar.php?paciente_id=<?= $paciente['id'] ?>" class="btn btn-info">
                <span class="material-symbols-rounded me-2">approval</span>Enviar Consentimiento
            </a>
            <a href="<?= BASE_URL ?>/modules/pacientes/edit.php?id=<?= $paciente['id'] ?>" class="btn btn-primary">
                <span class="material-symbols-rounded me-2">edit</span>Editar
            </a>
            <a href="<?= BASE_URL ?>/modules/citas/create.php?paciente_id=<?= $paciente['id'] ?>" class="btn btn-success">
                <span class="material-symbols-rounded me-2">add_task</span>Nueva Cita
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="row">
        <!-- Información básica del paciente -->
        <div class="col-lg-5 mb-4">
            <?php 
            // Incluir tarjeta de paciente
            $mostrar_acciones = true;
            include ROOT_PATH . '/includes/partials/patient_card.php';
            ?>
        </div>
        
        <!-- Información de contacto y médica -->
        <div class="col-lg-7 mb-4">
            <div class="row h-100">
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Dirección</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <p class="mb-1 text-muted small">Dirección Completa</p>
                                    <p class="mb-3 fw-bold"><?= isset($paciente['direccion']) ? htmlspecialchars($paciente['direccion']) : 'No disponible' ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Código Postal</p>
                                    <p class="mb-3 fw-bold"><?= isset($paciente['codigo_postal']) ? htmlspecialchars($paciente['codigo_postal']) : 'No disponible' ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Ciudad</p>
                                    <p class="mb-3 fw-bold"><?= isset($paciente['ciudad']) ? htmlspecialchars($paciente['ciudad']) : 'No disponible' ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Provincia</p>
                                    <p class="mb-3 fw-bold"><?= isset($paciente['provincia']) ? htmlspecialchars($paciente['provincia']) : 'No disponible' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Información Médica</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php if (!empty($tiempo_en_consulta)): ?>
                                <div class="col-md-12">
                                    <p class="mb-1 text-muted small">Tiempo en consulta</p>
                                    <p class="mb-3 fw-bold">
                                        <span class="material-symbols-rounded align-middle me-1 text-primary">schedule</span>
                                        <?= $tiempo_en_consulta ?> 
                                        <small class="text-muted">(desde <?= date('d/m/Y', strtotime($primera_cita['fecha'])) ?>)</small>
                                    </p>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-12">
                                    <p class="mb-1 text-muted small">Total de sesiones</p>
                                    <p class="mb-3 fw-bold">
                                        <span class="material-symbols-rounded align-middle me-1 text-primary">event_available</span>
                                        <?= $total_sesiones ?> sesion<?= $total_sesiones != 1 ? 'es' : '' ?>
                                    </p>
                                </div>
                                
                                <?php if (!empty($ultima_cita_fecha)): ?>
                                <div class="col-md-12">
                                    <p class="mb-1 text-muted small">Última sesión</p>
                                    <p class="mb-3 fw-bold">
                                        <span class="material-symbols-rounded align-middle me-1 text-primary">event</span>
                                        <?= date('d/m/Y', strtotime($ultima_cita_fecha)) ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($frecuencia_visitas)): ?>
                                <div class="col-md-12">
                                    <p class="mb-1 text-muted small">Frecuencia de visitas</p>
                                    <p class="mb-3 fw-bold">
                                        <span class="material-symbols-rounded align-middle me-1 text-primary">calendar_view_week</span>
                                        <?= $frecuencia_visitas ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-12">
                                    <p class="mb-1 text-muted small">Notas</p>
                                    <p><?= empty($paciente['notas']) ? 'Sin notas adicionales' : nl2br(htmlspecialchars($paciente['notas'])) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 fw-bold">Email</p>
                                    <p><?= $paciente['email'] ? htmlspecialchars($paciente['email']) : '<span class="text-muted">No disponible</span>' ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 fw-bold">Consentimiento</p>
                                    <?php if (isset($paciente['consentimiento_firmado']) && $paciente['consentimiento_firmado'] == 1): ?>
                                    <div class="d-flex align-items-center">
                                        <span class="material-symbols-rounded text-success me-2">check_circle</span>
                                        <span>Firmado</span>
                                    </div>
                                    <?php else: ?>
                                    <div class="d-flex align-items-center">
                                        <span class="material-symbols-rounded text-danger me-2">cancel</span>
                                        <span>No firmado</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de Bonos -->
                <?php if (!empty($bonos)): ?>
                <div class="col-12 mt-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Bonos de Sesiones</h5>
                            <a href="<?= BASE_URL ?>/modules/bono/create.php?paciente_id=<?= $paciente_id ?>" class="btn btn-sm btn-success">
                                <span class="material-symbols-rounded">add</span> Nuevo Bono
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha de compra</th>
                                            <th>Sesiones</th>
                                            <th>Disponibles</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bonos as $bono): ?>
                                            <?php 
                                            $estadoBadgeClass = '';
                                            switch ($bono['estado']) {
                                                case 'activo':
                                                    $estadoBadgeClass = 'bg-success';
                                                    break;
                                                case 'consumido':
                                                    $estadoBadgeClass = 'bg-secondary';
                                                    break;
                                                case 'caducado':
                                                    $estadoBadgeClass = 'bg-danger';
                                                    break;
                                                case 'cancelado':
                                                    $estadoBadgeClass = 'bg-warning text-dark';
                                                    break;
                                            }
                                            ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($bono['fecha_compra'])) ?></td>
                                                <td><?= $bono['num_sesiones_total'] ?></td>
                                                <td>
                                                    <span class="fw-bold <?= $bono['num_sesiones_disponibles'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= $bono['num_sesiones_disponibles'] ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($bono['monto'], 2, ',', '.') ?> €</td>
                                                <td>
                                                    <span class="badge <?= $estadoBadgeClass ?>">
                                                        <?= ucfirst($bono['estado']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>/modules/bono/view.php?id=<?= $bono['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <span class="material-symbols-rounded">visibility</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <!-- Sección de Historial de Sesiones -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><span class="material-symbols-rounded me-2">history</span>Historial de Sesiones</h5>
                        <a href="<?= BASE_URL ?>/modules/citas/create.php?paciente_id=<?= $paciente['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <span class="material-symbols-rounded me-2">add</span>Nueva Sesión
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                    <th>Motivo</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todas_citas)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No hay sesiones registradas para este paciente</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($todas_citas as $cita): ?>
                                        <tr>
                                            <td><?= formatDateToView($cita['fecha']) ?></td>
                                            <td><?= formatTime($cita['hora_inicio']) ?> - <?= formatTime($cita['hora_fin']) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                $iconName = '';
                                                switch ($cita['estado']) {
                                                    case 'pendiente':
                                                        $badgeClass = 'badge-pendiente';
                                                        $iconName = 'pending';
                                                        break;
                                                    case 'completada':
                                                        $badgeClass = 'badge-completada';
                                                        $iconName = 'check_circle';
                                                        break;
                                                    case 'cancelada':
                                                        $badgeClass = 'badge-cancelada';
                                                        $iconName = 'cancel';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <span class="material-symbols-rounded icon-xs me-1"><?= $iconName ?></span>
                                                    <?= ucfirst($cita['estado']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($cita['motivo']) ?></td>
                                            <td class="text-end">
                                                <div class="table-actions">
                                                    <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver Detalle">
                                                        <span class="material-symbols-rounded">visibility</span>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/modules/citas/edit.php?id=<?= $cita['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                                        <span class="material-symbols-rounded">edit</span>
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
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 