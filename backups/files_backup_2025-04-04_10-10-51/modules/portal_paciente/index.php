<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar que el paciente esté autenticado
if (!isset($_SESSION['paciente_id'])) {
    header('Location: ' . BASE_URL . '/modules/portal_paciente/login.php');
    exit;
}

// Obtener información del paciente
$pacienteId = $_SESSION['paciente_id'];
$pacienteNombre = $_SESSION['paciente_nombre'];
$pacienteApellidos = $_SESSION['paciente_apellidos'];

try {
    $db = getDB();
    
    // Obtener todas las citas del paciente
    $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado, 
              'Médico' as medico_nombre, 'Asignado' as medico_apellidos,
              CASE 
                WHEN c.motivo LIKE '%consulta%' THEN 'consulta'
                WHEN c.motivo LIKE '%seguimiento%' THEN 'seguimiento'
                WHEN c.motivo LIKE '%tratamiento%' THEN 'tratamiento'
                ELSE 'otro'
              END AS tipo_cita,
              c.notas
              FROM citas c
              WHERE c.paciente_id = :paciente_id
              ORDER BY c.fecha DESC, c.hora_inicio DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':paciente_id', $pacienteId, PDO::PARAM_INT);
    $stmt->execute();
    
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separar citas por pasadas, hoy y futuras
    $citasPasadas = [];
    $citasHoy = [];
    $citasFuturas = [];
    $hoy = date('Y-m-d');
    
    foreach ($citas as $cita) {
        if ($cita['fecha'] < $hoy) {
            $citasPasadas[] = $cita;
        } elseif ($cita['fecha'] == $hoy) {
            $citasHoy[] = $cita;
        } else {
            $citasFuturas[] = $cita;
        }
    }
    
    // Obtener próxima cita
    $proximaCita = null;
    if (!empty($citasHoy)) {
        $proximaCita = $citasHoy[0];
    } elseif (!empty($citasFuturas)) {
        $proximaCita = $citasFuturas[0];
    }
    
    // Obtener historial médico resumido (últimas notas)
    $queryHistorial = "SELECT h.id, h.fecha, h.tipo, h.descripcion, h.diagnostico,
                      'Profesional' as medico_nombre, 'Asignado' as medico_apellidos
                      FROM historial_medico h
                      WHERE h.paciente_id = :paciente_id
                      ORDER BY h.fecha DESC
                      LIMIT 5";
    
    $stmtHistorial = $db->prepare($queryHistorial);
    $stmtHistorial->bindParam(':paciente_id', $pacienteId, PDO::PARAM_INT);
    $stmtHistorial->execute();
    
    $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Título de la página
$title = "Mi Portal - Clínica";

// Iniciar buffer de salida
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Cabecera con información de usuario -->
    <div class="card border-0 bg-gradient-primary text-white shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Bienvenido/a, <?= htmlspecialchars($pacienteNombre) ?></h1>
                    <p class="mb-0">Portal de Paciente - Acceso a sus citas e información médica</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/modules/portal_paciente/logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Panel izquierdo: Información personal y próxima cita -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Mi Información
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3"><?= htmlspecialchars($pacienteApellidos . ', ' . $pacienteNombre) ?></h6>
                    <p class="text-muted small">
                        <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($_SESSION['paciente_email']) ?>
                    </p>
                    <hr>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/portal_paciente/perfil.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user-edit me-2"></i>Editar Mis Datos
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Acciones rápidas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2 text-primary"></i>Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/portal_paciente/solicitud_cita.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-plus me-2"></i>Solicitar Nueva Cita
                        </a>
                        <a href="<?= BASE_URL ?>/modules/portal_paciente/documentos.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-file-alt me-2"></i>Mis Documentos
                        </a>
                        <a href="<?= BASE_URL ?>/modules/portal_paciente/historial_completo.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-clipboard-list me-2"></i>Ver Historial Completo
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($proximaCita): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>Próxima Cita
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge rounded-pill bg-primary mb-2">
                            <?= date('d/m/Y', strtotime($proximaCita['fecha'])) ?>
                        </span>
                        <h6><?= $proximaCita['hora_inicio'] ?> - <?= $proximaCita['hora_fin'] ?></h6>
                        <p class="mb-1">
                            <i class="fas fa-user-md me-2 text-muted"></i>
                            Dr. <?= $proximaCita['medico_apellidos'] . ', ' . $proximaCita['medico_nombre'] ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-stethoscope me-2 text-muted"></i>
                            Consulta general
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clipboard me-2 text-muted"></i>
                            <?= $proximaCita['motivo'] ?>
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/portal_paciente/calendar_sync.php?id=<?= $proximaCita['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-calendar-plus me-2"></i>Añadir a Calendario
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-4">
                    <i class="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                    <h6>No tiene citas programadas</h6>
                    <p class="text-muted small">Contacte con la clínica para agendar una nueva cita.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Panel principal: Pestañas con citas e historial -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="citas-tab" data-bs-toggle="tab" data-bs-target="#citas-tab-pane" type="button" role="tab" aria-controls="citas-tab-pane" aria-selected="true">
                                <i class="fas fa-calendar-alt me-2"></i>Mis Citas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial-tab-pane" type="button" role="tab" aria-controls="historial-tab-pane" aria-selected="false">
                                <i class="fas fa-clipboard-list me-2"></i>Mi Historial
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="calendario-tab" data-bs-toggle="tab" data-bs-target="#calendario-tab-pane" type="button" role="tab" aria-controls="calendario-tab-pane" aria-selected="false">
                                <i class="fas fa-calendar me-2"></i>Calendario
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="myTabContent">
                        <!-- Tab: Mis Citas -->
                        <div class="tab-pane fade show active p-4" id="citas-tab-pane" role="tabpanel" aria-labelledby="citas-tab" tabindex="0">
                            <!-- Citas futuras -->
                            <h6 class="text-uppercase small fw-bold mb-3">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>Próximas Citas
                            </h6>
                            
                            <?php if (!empty($citasFuturas) || !empty($citasHoy)): ?>
                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Médico</th>
                                            <th>Motivo</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_merge($citasHoy, $citasFuturas) as $cita): ?>
                                        <tr>
                                            <td>
                                                <?php if ($cita['fecha'] == $hoy): ?>
                                                <span class="badge bg-primary">Hoy</span>
                                                <?php else: ?>
                                                <?= date('d/m/Y', strtotime($cita['fecha'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $cita['hora_inicio'] ?> - <?= $cita['hora_fin'] ?></td>
                                            <td>Dr. <?= htmlspecialchars($cita['medico_apellidos'] . ', ' . $cita['medico_nombre']) ?></td>
                                            <td><?= htmlspecialchars($cita['motivo']) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                switch ($cita['estado']) {
                                                    case 'pendiente':
                                                        $badgeClass = 'bg-warning';
                                                        break;
                                                    case 'completada':
                                                        $badgeClass = 'bg-success';
                                                        break;
                                                    case 'cancelada':
                                                        $badgeClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $badgeClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= ucfirst($cita['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/modules/portal_paciente/calendar_sync.php?id=<?= $cita['id'] ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-calendar-plus"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/modules/portal_paciente/cita_detalle.php?id=<?= $cita['id'] ?>" class="btn btn-outline-secondary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-4" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                No tiene citas programadas próximamente.
                            </div>
                            <?php endif; ?>
                            
                            <!-- Citas pasadas -->
                            <h6 class="text-uppercase small fw-bold mb-3">
                                <i class="fas fa-history me-2 text-secondary"></i>Citas Pasadas
                            </h6>
                            
                            <?php if (!empty($citasPasadas)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Médico</th>
                                            <th>Motivo</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($citasPasadas, 0, 5) as $cita): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($cita['fecha'])) ?></td>
                                            <td><?= $cita['hora_inicio'] ?> - <?= $cita['hora_fin'] ?></td>
                                            <td>Dr. <?= htmlspecialchars($cita['medico_apellidos'] . ', ' . $cita['medico_nombre']) ?></td>
                                            <td><?= htmlspecialchars($cita['motivo']) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = '';
                                                switch ($cita['estado']) {
                                                    case 'pendiente':
                                                        $badgeClass = 'bg-warning';
                                                        break;
                                                    case 'completada':
                                                        $badgeClass = 'bg-success';
                                                        break;
                                                    case 'cancelada':
                                                        $badgeClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $badgeClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= ucfirst($cita['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/modules/portal_paciente/cita_detalle.php?id=<?= $cita['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <?php if (count($citasPasadas) > 5): ?>
                                <div class="text-center p-3">
                                    <a href="<?= BASE_URL ?>/modules/portal_paciente/historial_citas.php" class="btn btn-link text-decoration-none">
                                        <i class="fas fa-history me-1"></i>Ver todas las citas anteriores
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay registros de citas pasadas.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab: Mi Historial -->
                        <div class="tab-pane fade p-4" id="historial-tab-pane" role="tabpanel" aria-labelledby="historial-tab" tabindex="0">
                            <h6 class="text-uppercase small fw-bold mb-3">
                                <i class="fas fa-clipboard-list me-2 text-primary"></i>Resumen de Historial Médico
                            </h6>
                            
                            <?php if (!empty($historial)): ?>
                            <div class="mb-4">
                                <?php foreach ($historial as $registro): ?>
                                <div class="card mb-3 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="card-title"><?= htmlspecialchars($registro['tipo']) ?></h6>
                                            <span class="text-muted small"><?= date('d/m/Y', strtotime($registro['fecha'])) ?></span>
                                        </div>
                                        <p class="card-text"><?= htmlspecialchars($registro['descripcion']) ?></p>
                                        <?php if (!empty($registro['diagnostico'])): ?>
                                        <div class="bg-light p-2 rounded">
                                            <p class="mb-0 small"><strong>Diagnóstico:</strong> <?= htmlspecialchars($registro['diagnostico']) ?></p>
                                        </div>
                                        <?php endif; ?>
                                        <div class="text-end mt-2 text-muted small">
                                            Dr. <?= htmlspecialchars($registro['medico_apellidos'] . ', ' . $registro['medico_nombre']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="text-center p-3">
                                    <a href="<?= BASE_URL ?>/modules/portal_paciente/historial_completo.php" class="btn btn-link text-decoration-none">
                                        <i class="fas fa-clipboard-list me-1"></i>Ver historial clínico completo
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay registros en su historial médico.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tab: Calendario -->
                        <div class="tab-pane fade p-4" id="calendario-tab-pane" role="tabpanel" aria-labelledby="calendario-tab" tabindex="0">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS adicional -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" rel="stylesheet">

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/locales/es.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preparar eventos para el calendario
    var eventos = [];
    
    <?php foreach (array_merge($citasPasadas, $citasHoy, $citasFuturas) as $cita): ?>
    var color = '';
    <?php if ($cita['estado'] === 'completada'): ?>
    color = '#2ecc71';
    <?php elseif ($cita['estado'] === 'cancelada'): ?>
    color = '#e74c3c';
    <?php elseif ($cita['fecha'] === $hoy): ?>
    color = '#3498db';
    <?php else: ?>
    color = '#f39c12';
    <?php endif; ?>
    
    eventos.push({
        id: '<?= $cita['id'] ?>',
        title: '<?= addslashes($cita['motivo']) ?>',
        start: '<?= $cita['fecha'] ?>T<?= $cita['hora_inicio'] ?>',
        end: '<?= $cita['fecha'] ?>T<?= $cita['hora_fin'] ?>',
        backgroundColor: color,
        borderColor: color,
        url: '<?= BASE_URL ?>/modules/portal_paciente/cita_detalle.php?id=<?= $cita['id'] ?>'
    });
    <?php endforeach; ?>
    
    // Inicializar calendario
    var calendarEl = document.getElementById('calendario-tab-pane');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        themeSystem: 'bootstrap5',
        height: 'auto',
        events: eventos,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            list: 'Lista'
        }
    });
    
    // Renderizar cuando se active la pestaña
    document.querySelector('#calendario-tab').addEventListener('shown.bs.tab', function (e) {
        calendar.render();
    });
});
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = <<<EOT
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
    
    .tab-content {
        min-height: 600px;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.25rem;
    }
    
    .nav-tabs .nav-link.active {
        border-bottom: 2px solid #007bff;
        color: #007bff;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .nav-tabs .nav-link {
            padding: 0.75rem 0.5rem;
            font-size: 0.9rem;
        }
    }
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal para portal de pacientes
include ROOT_PATH . '/includes/layouts/portal.php';
?> 