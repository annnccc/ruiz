<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Obtener médicos y sus citas para la línea de tiempo
try {
    $db = getDB();
    
    // Verificar si se ha seleccionado ver por sala o por médico
    $vistaRecurso = isset($_GET['vista']) && $_GET['vista'] === 'salas' ? 'salas' : 'medicos';
    
    // Obtener lista de médicos/profesionales
    $queryMedicos = "SELECT id, nombre, apellidos 
                     FROM usuarios 
                     WHERE rol = 'medico' OR rol = 'admin'
                     ORDER BY apellidos, nombre";
    
    $stmtMedicos = $db->prepare($queryMedicos);
    $stmtMedicos->execute();
    $medicos = $stmtMedicos->fetchAll(PDO::FETCH_ASSOC);
    
    // Definir rango de fechas para las consultas
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d', strtotime('+7 days'));
    
    // Obtener lista de salas (simulado, podría venir de una tabla)
    $salas = [
        ['id' => 1, 'nombre' => 'Consulta 1', 'tipo' => 'Consulta general'],
        ['id' => 2, 'nombre' => 'Consulta 2', 'tipo' => 'Especialista'],
        ['id' => 3, 'nombre' => 'Sala tratamiento 1', 'tipo' => 'Tratamientos'],
        ['id' => 4, 'nombre' => 'Sala de espera', 'tipo' => 'Espera'],
        ['id' => 5, 'nombre' => 'Quirófano', 'tipo' => 'Cirugía menor']
    ];
    
    // Obtener citas para cada médico
    $eventos = [];
    $recursosTimeline = [];
    
    if ($vistaRecurso === 'medicos') {
        foreach ($medicos as $index => $medico) {
            // Añadir médico como recurso para la timeline
            $recursosTimeline[] = [
                'id' => 'medico-' . $medico['id'],
                'title' => $medico['apellidos'] . ', ' . $medico['nombre'],
                'subtitle' => 'Médico'
            ];
            
            // Obtener citas asignadas a este médico
            // Nota: Esta consulta podría no funcionar si no existe la columna usuario_id en la tabla citas
            $queryCitas = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado,
                           p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, c.notas
                           FROM citas c
                           JOIN pacientes p ON c.paciente_id = p.id
                           WHERE c.usuario_id = :medico_id
                           AND c.fecha BETWEEN :fecha_inicio AND :fecha_fin
                           ORDER BY c.fecha ASC, c.hora_inicio ASC";
            
            $stmtCitas = $db->prepare($queryCitas);
            $stmtCitas->bindParam(':medico_id', $medico['id'], PDO::PARAM_INT);
            $stmtCitas->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
            $stmtCitas->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
            $stmtCitas->execute();
            $citas = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear citas como eventos para el calendario
            foreach ($citas as $cita) {
                $color = '';
                switch ($cita['estado']) {
                    case 'pendiente': $color = '#f39c12'; break; // Naranja
                    case 'completada': $color = '#2ecc71'; break; // Verde
                    case 'cancelada': $color = '#e74c3c'; break; // Rojo
                    default: $color = '#3498db'; // Azul
                }
                
                // Obtener nombre de la sala (si existe)
                $nombreSala = '';
                foreach ($salas as $sala) {
                    if ($sala['id'] == ($cita['sala_id'] ?? 0)) {
                        $nombreSala = $sala['nombre'];
                        break;
                    }
                }
                
                $eventos[] = [
                    'id' => $cita['id'],
                    'resourceId' => 'medico-' . $medico['id'],
                    'title' => htmlspecialchars($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre'] . ' - ' . $cita['motivo']),
                    'start' => $cita['fecha'] . 'T' . $cita['hora_inicio'],
                    'end' => $cita['fecha'] . 'T' . $cita['hora_fin'],
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'estado' => $cita['estado'],
                        'paciente' => htmlspecialchars($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre']),
                        'fecha' => date('d/m/Y', strtotime($cita['fecha'])),
                        'hora_inicio' => $cita['hora_inicio'],
                        'hora_fin' => $cita['hora_fin'],
                        'motivo' => htmlspecialchars($cita['motivo']),
                        'notas' => htmlspecialchars($cita['notas'] ?? ''),
                        'medico' => $medico['apellidos'] . ', ' . $medico['nombre'],
                        'sala' => $nombreSala
                    ]
                ];
            }
        }
    } else {
        // Vista por salas
        foreach ($salas as $sala) {
            // Añadir sala como recurso para la timeline
            $recursosTimeline[] = [
                'id' => 'sala-' . $sala['id'],
                'title' => $sala['nombre'],
                'subtitle' => $sala['tipo']
            ];
            
            // Obtener citas asignadas a esta sala
            $queryCitas = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado, 
                          p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, c.notas
                          FROM citas c
                          JOIN pacientes p ON c.paciente_id = p.id
                          WHERE c.sala = :sala_id
                          ORDER BY c.fecha ASC, c.hora_inicio ASC";
            
            $stmtCitas = $db->prepare($queryCitas);
            $stmtCitas->bindParam(':sala_id', $sala['id'], PDO::PARAM_INT);
            $stmtCitas->execute();
            $citas = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear citas como eventos para el calendario
            foreach ($citas as $cita) {
                $color = '';
                switch ($cita['estado']) {
                    case 'pendiente': $color = '#f39c12'; break; // Naranja
                    case 'completada': $color = '#2ecc71'; break; // Verde
                    case 'cancelada': $color = '#e74c3c'; break; // Rojo
                    default: $color = '#3498db'; // Azul
                }
                
                $eventos[] = [
                    'id' => $cita['id'],
                    'resourceId' => 'sala-' . $sala['id'],
                    'title' => htmlspecialchars($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre'] . ' - ' . $cita['motivo']),
                    'start' => $cita['fecha'] . 'T' . $cita['hora_inicio'],
                    'end' => $cita['fecha'] . 'T' . $cita['hora_fin'],
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'estado' => $cita['estado'],
                        'paciente' => htmlspecialchars($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre']),
                        'fecha' => date('d/m/Y', strtotime($cita['fecha'])),
                        'hora_inicio' => $cita['hora_inicio'],
                        'hora_fin' => $cita['hora_fin'],
                        'motivo' => htmlspecialchars($cita['motivo']),
                        'notas' => htmlspecialchars($cita['notas'] ?? ''),
                        'medico' => 'No asignado',
                        'sala' => $sala['nombre']
                    ]
                ];
            }
        }
    }
    
    // Convertir a formato JSON para JavaScript
    $eventos_json = json_encode($eventos);
    $recursos_json = json_encode($recursosTimeline);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener las citas: ' . $e->getMessage());
    $eventos = [];
    $eventos_json = '[]';
    $recursos_json = '[]';
}

// Título de la página y breadcrumbs
$title = $vistaRecurso === 'medicos' ? "Timeline de Citas por Médico" : "Timeline de Citas por Sala";
$breadcrumbs = [
    'Calendario' => BASE_URL . '/modules/calendario/index.php',
    'Timeline' => '#'
];

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-stream me-2 text-primary"></i>Timeline de Citas</h1>
            <p class="text-muted small mb-0">Visualiza las citas organizadas por <?= $vistaRecurso === 'medicos' ? 'médico' : 'sala' ?></p>
        </div>
        <div>
            <div class="btn-group me-2">
                <a href="<?= BASE_URL ?>/modules/calendario/timeline.php" class="btn <?= $vistaRecurso === 'medicos' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                    <i class="fas fa-user-md me-1"></i> Por Médico
                </a>
                <a href="<?= BASE_URL ?>/modules/calendario/timeline.php?vista=salas" class="btn <?= $vistaRecurso === 'salas' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                    <i class="fas fa-door-open me-1"></i> Por Sala
                </a>
            </div>
            <a href="<?= BASE_URL ?>/modules/calendario/index.php" class="btn btn-outline-primary btn-sm me-2">
                <i class="fas fa-calendar-alt me-1"></i> Vista Calendario
            </a>
            <a href="<?= BASE_URL ?>/modules/citas/create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nueva Cita
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Panel de Timeline -->
    <div class="row">
        <!-- Panel lateral con leyendas y filtros -->
        <div class="col-lg-3 col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2 text-primary"></i>Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Leyenda -->
                    <h6 class="text-uppercase text-muted small mb-3">Estados</h6>
                    <div class="mb-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-pendiente" checked>
                            <label class="form-check-label" for="filtro-pendiente">
                                <span class="badge bg-warning me-1">&nbsp;</span> Pendientes
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-completada" checked>
                            <label class="form-check-label" for="filtro-completada">
                                <span class="badge bg-success me-1">&nbsp;</span> Completadas
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="filtro-cancelada" checked>
                            <label class="form-check-label" for="filtro-cancelada">
                                <span class="badge bg-danger me-1">&nbsp;</span> Canceladas
                            </label>
                        </div>
                    </div>
                    
                    <!-- Rango de fechas -->
                    <h6 class="text-uppercase text-muted small mb-3">Período</h6>
                    <div class="mb-4">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="date" class="form-control" id="fecha-inicio" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="date" class="form-control" id="fecha-fin" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                        <div class="d-grid mt-2">
                            <button id="btn-aplicar-rango" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-filter me-1"></i> Aplicar Filtro
                            </button>
                        </div>
                    </div>
                    
                    <!-- Acciones rápidas -->
                    <h6 class="text-uppercase text-muted small mb-3">Acciones Rápidas</h6>
                    <div class="d-grid gap-2">
                        <button id="btn-hoy" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar-day me-2"></i>Ir a Hoy
                        </button>
                        <button id="btn-actualizar" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar Timeline
                        </button>
                        <button id="btn-exportar" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download me-2"></i>Exportar Vista
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Timeline principal -->
        <div class="col-lg-9 col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div id="timeline" style="min-height: 700px;"></div>
                    
                    <?php if (empty($eventos)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3 text-muted">
                            <i class="fas fa-stream fa-4x opacity-25"></i>
                        </div>
                        <h5>No hay citas programadas</h5>
                        <p class="text-muted">Comienza creando una nueva cita con el botón "Nueva Cita"</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalle de Cita -->
<div class="modal fade" id="citaModal" tabindex="-1" aria-labelledby="citaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="citaModalLabel">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>Detalle de la Cita
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="cita-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando información...</p>
                </div>
                <div id="cita-content" style="display: none;">
                    <div class="mb-4 pb-3 border-bottom">
                        <h6 class="text-muted mb-1">Paciente</h6>
                        <h5 id="cita-paciente" class="mb-0"></h5>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Fecha</h6>
                                <p id="cita-fecha" class="mb-0 fs-5"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Horario</h6>
                                <p id="cita-horario" class="mb-0 fs-5"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Médico asignado</h6>
                                <p id="cita-medico" class="mb-0"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Sala</h6>
                                <p id="cita-sala" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Motivo</h6>
                        <p id="cita-motivo" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Estado</h6>
                        <p id="cita-estado" class="mb-0"></p>
                    </div>
                    <div class="mb-0">
                        <h6 class="text-muted mb-1">Notas</h6>
                        <p id="cita-notas" class="mb-0 bg-light p-3 rounded"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <!-- Panel izquierdo - Estado de cita -->
                    <div>
                        <div class="dropdown">
                            <button id="btn-cambiar-estado" class="btn btn-outline-primary" data-bs-toggle="dropdown">
                                <i class="fas fa-exchange-alt me-2"></i>Cambiar Estado
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item cambiar-estado" href="#" data-estado="pendiente">
                                    <span class="badge bg-warning me-2">&nbsp;</span>Pendiente
                                </a>
                                <a class="dropdown-item cambiar-estado" href="#" data-estado="completada">
                                    <span class="badge bg-success me-2">&nbsp;</span>Completada
                                </a>
                                <a class="dropdown-item cambiar-estado" href="#" data-estado="cancelada">
                                    <span class="badge bg-danger me-2">&nbsp;</span>Cancelada
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel derecho - Acciones principales -->
                    <div>
                        <a href="#" id="btn-editar-cita" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS directo para FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" rel="stylesheet">

<!-- Scripts directos -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/locales/es.js"></script>

<!-- Script de inicialización directo -->
<script>
// Esperar a que el DOM esté completamente cargado
window.addEventListener('load', function() {
    // Eventos del timeline
    var eventos = <?php echo $eventos_json; ?>;
    var recursos = <?php echo $recursos_json; ?>;
    
    // Elemento de timeline
    var timelineEl = document.getElementById('timeline');
    if (!timelineEl) {
        return;
    }
    
    // Inicializar modal
    var citaModal = new bootstrap.Modal(document.getElementById('citaModal'));
    var currentEventId = null;
    
    try {
        // Crear instancia de FullCalendar con vista Timeline
        var timeline = new FullCalendar.Calendar(timelineEl, {
            locale: 'es',
            initialView: 'resourceTimelineWeek',
            themeSystem: 'bootstrap5',
            height: 'auto',
            events: eventos,
            resources: recursos,
            resourceAreaWidth: '20%',
            resourceAreaHeaderContent: '<?= $vistaRecurso === 'medicos' ? 'Médicos' : 'Salas' ?>',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth'
            },
            buttonText: {
                today: 'Hoy',
                day: 'Día',
                week: 'Semana',
                month: 'Mes'
            },
            slotDuration: '00:30:00',
            slotLabelInterval: '01:00:00',
            snapDuration: '00:15:00',
            scrollTime: '08:00:00',
            slotMinTime: '07:00:00',
            slotMaxTime: '21:00:00',
            nowIndicator: true,
            editable: true, // Permite arrastrar y soltar
            eventStartEditable: true,
            eventDurationEditable: true,
            eventResourceEditable: true, // Permite cambiar de médico
            resourceLabelDidMount: function(info) {
                if (info.resource.extendedProps && info.resource.extendedProps.subtitle) {
                    var subtitle = document.createElement('div');
                    subtitle.className = 'fc-resource-subtitle text-muted small';
                    subtitle.innerText = info.resource.extendedProps.subtitle;
                    info.el.querySelector('.fc-resource-cell').appendChild(subtitle);
                }
            },
            eventClick: function(info) {
                // Guardar ID del evento actual
                currentEventId = info.event.id;
                
                // Mostrar loading
                document.getElementById('cita-loading').style.display = 'block';
                document.getElementById('cita-content').style.display = 'none';
                
                // Obtener datos del evento
                var evento = info.event;
                var props = evento.extendedProps;
                
                // Mostrar el modal
                citaModal.show();
                
                // Pequeño retraso para efecto de carga
                setTimeout(function() {
                    // Ocultar loading
                    document.getElementById('cita-loading').style.display = 'none';
                    document.getElementById('cita-content').style.display = 'block';
                    
                    // Llenar datos
                    document.getElementById('cita-paciente').textContent = props.paciente;
                    document.getElementById('cita-fecha').textContent = props.fecha;
                    document.getElementById('cita-horario').textContent = props.hora_inicio + ' - ' + props.hora_fin;
                    document.getElementById('cita-medico').textContent = props.medico;
                    document.getElementById('cita-sala').textContent = props.sala || 'No asignada';
                    document.getElementById('cita-motivo').textContent = props.motivo;
                    
                    // Estado con color
                    var estadoClass = props.estado === 'pendiente' ? 'warning' : 
                                     (props.estado === 'completada' ? 'success' : 'danger');
                    var estadoTexto = props.estado.charAt(0).toUpperCase() + props.estado.slice(1);
                    document.getElementById('cita-estado').innerHTML = 
                        '<span class="badge bg-' + estadoClass + ' px-3 py-2">' + estadoTexto + '</span>';
                    
                    // Notas (con placeholder si no hay)
                    document.getElementById('cita-notas').textContent = props.notas || 'Sin notas adicionales';
                    
                    // Enlace para editar
                    document.getElementById('btn-editar-cita').href = '../citas/edit.php?id=' + evento.id;
                }, 300);
            },
            eventDrop: function(info) {
                // Capturar información de la cita arrastrada
                const citaId = info.event.id;
                const nuevaFecha = info.event.start.toISOString();
                let nuevaFechaFin = info.event.end ? info.event.end.toISOString() : 
                                    new Date(info.event.start.getTime() + 30*60000).toISOString();
                
                // Confirmar el cambio
                if (confirm('¿Deseas reprogramar esta cita para el ' + 
                          info.event.start.toLocaleDateString() + ' a las ' + 
                          info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + 
                          (info.oldResource !== info.newResource ? ' cambiando al médico ' + info.newResource.title : '') + '?')) {
                    
                    // Mostrar indicador de carga
                    mostrarToast('Actualizando cita...', 'info');
                    
                    // Aquí iría el código para enviar la actualización al servidor
                    // Ejemplo simplificado: simular éxito después de un tiempo
                    setTimeout(function() {
                        // Actualizar propiedades extendidas
                        const fechaFormateada = new Date(info.event.start).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        info.event.setExtendedProp('fecha', fechaFormateada);
                        info.event.setExtendedProp('hora_inicio', nuevaFecha.substr(11, 5));
                        info.event.setExtendedProp('hora_fin', nuevaFechaFin.substr(11, 5));
                        
                        if (info.oldResource !== info.newResource) {
                            info.event.setExtendedProp('medico', info.newResource.title);
                        }
                        
                        // Mostrar notificación
                        mostrarToast('Cita reprogramada correctamente', 'success');
                    }, 1000);
                    
                } else {
                    info.revert(); // Cancelar el cambio
                }
            },
            eventResize: function(info) {
                if (confirm('¿Deseas cambiar la duración de esta cita?')) {
                    // Simular actualización
                    mostrarToast('Duración actualizada', 'success');
                } else {
                    info.revert();
                }
            }
        });
        
        // Renderizar el timeline
        timeline.render();
        
        // Botón de hoy
        document.getElementById('btn-hoy').addEventListener('click', function() {
            timeline.today();
        });
        
        // Botón de actualizar
        document.getElementById('btn-actualizar').addEventListener('click', function() {
            location.reload();
        });
        
        // Botón de exportar
        document.getElementById('btn-exportar').addEventListener('click', function() {
            // Simular exportación
            mostrarToast('Vista exportada correctamente', 'success');
        });
        
        // Aplicar rango de fechas
        document.getElementById('btn-aplicar-rango').addEventListener('click', function() {
            var fechaInicio = document.getElementById('fecha-inicio').value;
            var fechaFin = document.getElementById('fecha-fin').value;
            
            if (fechaInicio && fechaFin) {
                timeline.gotoDate(fechaInicio);
                timeline.setOption('visibleRange', {
                    start: fechaInicio,
                    end: fechaFin
                });
                mostrarToast('Rango de fechas aplicado', 'success');
            }
        });
        
        // Filtros de estado
        document.getElementById('filtro-pendiente').addEventListener('change', function() {
            filtrarEventos();
        });
        
        document.getElementById('filtro-completada').addEventListener('change', function() {
            filtrarEventos();
        });
        
        document.getElementById('filtro-cancelada').addEventListener('change', function() {
            filtrarEventos();
        });
        
        // Función para filtrar eventos
        function filtrarEventos() {
            var mostrarPendientes = document.getElementById('filtro-pendiente').checked;
            var mostrarCompletadas = document.getElementById('filtro-completada').checked;
            var mostrarCanceladas = document.getElementById('filtro-cancelada').checked;
            
            // Filtrar eventos según selección
            var eventosVisibles = eventos.filter(function(evento) {
                if (evento.extendedProps.estado === 'pendiente' && !mostrarPendientes) return false;
                if (evento.extendedProps.estado === 'completada' && !mostrarCompletadas) return false;
                if (evento.extendedProps.estado === 'cancelada' && !mostrarCanceladas) return false;
                return true;
            });
            
            // Actualizar eventos en el timeline
            timeline.getEventSources().forEach(function(source) {
                source.remove();
            });
            
            timeline.addEventSource(eventosVisibles);
        }
        
        // Función para mostrar notificaciones
        function mostrarToast(mensaje, tipo = 'primary') {
            // Si no existe el contenedor de toasts, lo creamos
            var toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            // Crear el toast
            var toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white bg-${tipo} border-0`;
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            // Añadir al contenedor
            toastContainer.appendChild(toastEl);
            
            // Inicializar y mostrar
            var toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            });
            toast.show();
            
            // Eliminar después de ocultarse
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        }
    } catch (error) {
        // Errores de inicialización silenciosos
    }
});
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = <<<EOT
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" rel="stylesheet">
<style>
    /* Estilos para el timeline */
    .fc-resource-cell {
        font-weight: 600;
        white-space: normal;
        height: auto;
        padding: 8px !important;
    }
    
    .fc-resource-subtitle {
        margin-top: 4px;
        font-weight: normal;
        opacity: 0.7;
    }
    
    .fc-timeline-event {
        border-radius: 4px;
        padding: 2px 4px;
        font-size: 0.85rem;
        border: none !important;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .fc-timeline-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 10;
    }
    
    .fc-timeline-event .fc-event-title {
        font-weight: 500;
    }
    
    .fc-timeline-event .fc-event-time {
        font-weight: 600;
        padding-right: 4px;
    }
    
    .fc-timeline-slot-cushion {
        font-weight: 500;
        color: #495057;
    }
    
    .fc-timeline-header-row-chrono .fc-timeline-slot-cushion {
        padding-top: 5px;
        padding-bottom: 5px;
    }
    
    .fc-timeline-event.fc-event-dragging {
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        opacity: 0.8;
    }
    
    .fc-timeline-event.fc-event-resizing {
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        border: 2px dashed #fff !important;
    }
    
    .fc-theme-bootstrap5 a:not([href]) {
        color: inherit;
        text-decoration: inherit;
    }
    
    .fc-col-header-cell-cushion {
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .fc .fc-button-primary {
        background-color: #3a86ff;
        border-color: #3a86ff;
        box-shadow: none !important;
    }
    
    .fc .fc-button-primary:hover {
        background-color: #2a76ff;
        border-color: #2a76ff;
    }
    
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #1a66ff;
        border-color: #1a66ff;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .fc-resource-area {
            width: 30% !important;
        }
        
        .fc-resource-cell {
            font-size: 0.8rem;
            padding: 4px !important;
        }
        
        .fc-timeline-event {
            font-size: 0.75rem;
            padding: 1px 2px;
        }
    }
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal
include ROOT_PATH . '/includes/layouts/main.php';
?> 