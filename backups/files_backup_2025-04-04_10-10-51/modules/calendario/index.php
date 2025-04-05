<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener citas para el calendario
try {
    $db = getDB();
    
    // Definir rango de fechas para buscar las citas
    $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
    $fecha_fin = date('Y-m-d', strtotime('+90 days'));
    
    // Obtener todas las citas del sistema - sin JOIN a usuarios porque la tabla citas no tiene esa relación
    $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado, 
                p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, c.notas
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              ORDER BY c.fecha ASC, c.hora_inicio ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear las citas como eventos para FullCalendar
    $eventos = [];
    $proximasCitas = [];
    $contadorProximas = 0;
    
    // Definir colores para tipos de citas (basados en el motivo)
    $coloresTipoCita = [
        'consulta' => '#3498db',     // Azul para consultas
        'seguimiento' => '#9b59b6',  // Morado para seguimientos
        'tratamiento' => '#1abc9c',  // Verde azulado para tratamientos
        'otro' => '#34495e'          // Gris oscuro para otros
    ];
    
    foreach ($citas as $cita) {
        // Colorear según estado
        $color = '';
        switch ($cita['estado']) {
            case 'pendiente': $color = '#f39c12'; break; // Naranja
            case 'completada': $color = '#2ecc71'; break; // Verde
            case 'cancelada': $color = '#e74c3c'; break; // Rojo
            default: $color = '#3498db'; // Azul
        }
        
        // Determinar tipo de cita basado en el motivo (palabra clave)
        $tipoCita = 'otro';
        $motivoLower = strtolower($cita['motivo']);
        
        if (strpos($motivoLower, 'consult') !== false) {
            $tipoCita = 'consulta';
        } elseif (strpos($motivoLower, 'seguimiento') !== false) {
            $tipoCita = 'seguimiento';
        } elseif (strpos($motivoLower, 'tratamiento') !== false || strpos($motivoLower, 'terapia') !== false) {
            $tipoCita = 'tratamiento';
        }
        
        // Asignar color según tipo de cita si está configurado para mostrar por tipo
        $mostrarPorTipo = isset($_GET['color_por_tipo']) && $_GET['color_por_tipo'] === '1';
        if ($mostrarPorTipo) {
            $color = $coloresTipoCita[$tipoCita];
        }
        
        // Formatear cada cita como un evento
        $eventos[] = [
            'id' => $cita['id'],
            'title' => $cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre'],
            'start' => $cita['fecha'] . 'T' . $cita['hora_inicio'],
            'end' => $cita['fecha'] . 'T' . $cita['hora_fin'],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#fff',
            'extendedProps' => [
                'estado' => $cita['estado'],
                'paciente' => $cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre'],
                'fecha' => date('d/m/Y', strtotime($cita['fecha'])),
                'hora_inicio' => $cita['hora_inicio'],
                'hora_fin' => $cita['hora_fin'],
                'motivo' => $cita['motivo'],
                'notas' => $cita['notas'] ?? '',
                'medico' => 'No asignado', // No hay médico asignado en esta versión
                'tipo_cita' => $tipoCita
            ]
        ];
        
        // Agregar a la lista de próximas citas
        $proximasCitas[] = $cita;
        $contadorProximas++;
    }
    
    // Convertir a formato JSON para JavaScript
    $eventos_json = json_encode($eventos);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener las citas: ' . $e->getMessage());
    $eventos = [];
    $eventos_json = '[]';
}

// Título de la página y breadcrumbs
$title = "Calendario de Citas";
$breadcrumbs = [
    'Calendario' => '#'
];

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Calendario de Citas</h1>
            <p class="text-muted small mb-0">Gestión y visualización de todas las citas médicas</p>
        </div>
        <div>
            <div class="btn-group">
                <a href="<?= BASE_URL ?>/modules/calendario/index.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-calendar-alt me-1"></i> Vista Principal
                </a>
                <a href="<?= BASE_URL ?>/modules/calendario/timeline.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-stream me-1"></i> Timeline
                </a>
                <a href="<?= BASE_URL ?>/modules/calendario/sync_settings.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-sync-alt me-1"></i> Sincronización
                </a>
                <a href="<?= BASE_URL ?>/modules/portal_paciente/login.php" class="btn btn-outline-info btn-sm" target="_blank">
                    <i class="fas fa-user me-1"></i> Portal Pacientes
                </a>
            </div>
            <a href="<?= BASE_URL ?>/modules/citas/create.php" class="btn btn-success btn-sm ms-2">
                <i class="fas fa-plus me-2"></i>Nueva Cita
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Panel del calendario -->
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
                                <span class="badge bg-warning me-1 filtro-estado-badge">&nbsp;</span> Pendientes
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-completada" checked>
                            <label class="form-check-label" for="filtro-completada">
                                <span class="badge bg-success me-1 filtro-estado-badge">&nbsp;</span> Completadas
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="filtro-cancelada" checked>
                            <label class="form-check-label" for="filtro-cancelada">
                                <span class="badge bg-danger me-1 filtro-estado-badge">&nbsp;</span> Canceladas
                            </label>
                        </div>
                    </div>
                    
                    <!-- Categorías de citas -->
                    <h6 class="text-uppercase text-muted small mb-3">Tipo de cita</h6>
                    <div class="mb-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-consulta" checked>
                            <label class="form-check-label" for="filtro-consulta">
                                <span class="badge me-1 filtro-tipo-badge" style="background-color: #3498db;">&nbsp;</span> Consulta
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-seguimiento" checked>
                            <label class="form-check-label" for="filtro-seguimiento">
                                <span class="badge me-1 filtro-tipo-badge" style="background-color: #9b59b6;">&nbsp;</span> Seguimiento
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-tratamiento" checked>
                            <label class="form-check-label" for="filtro-tratamiento">
                                <span class="badge me-1 filtro-tipo-badge" style="background-color: #1abc9c;">&nbsp;</span> Tratamiento
                            </label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="filtro-otro" checked>
                            <label class="form-check-label" for="filtro-otro">
                                <span class="badge me-1 filtro-tipo-badge" style="background-color: #34495e;">&nbsp;</span> Otro
                            </label>
                        </div>
                    </div>
                    
                    <!-- Mostrar colores por -->
                    <h6 class="text-uppercase text-muted small mb-3">Colorear citas por</h6>
                    <div class="mb-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="color-por" id="color-por-estado" value="estado" checked>
                            <label class="form-check-label" for="color-por-estado">
                                Estado (pendiente, completada, cancelada)
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="color-por" id="color-por-tipo" value="tipo">
                            <label class="form-check-label" for="color-por-tipo">
                                Tipo de cita (consulta, seguimiento, tratamiento)
                            </label>
                        </div>
                    </div>
                    
                    <!-- Acciones rápidas -->
                    <h6 class="text-uppercase text-muted small mb-3">Acciones Rápidas</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/citas/create.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Nueva Cita
                        </a>
                        <a href="<?= BASE_URL ?>/modules/calendario/timeline.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-stream me-2"></i>Vista Timeline
                        </a>
                        <a href="<?= BASE_URL ?>/modules/recordatorios/send_reminders.php" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-bell me-2"></i>Enviar Recordatorios
                        </a>
                        <button id="btn-exportar-calendario" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-download me-2"></i>Exportar Calendario
                        </button>
                    </div>
                    
                    <!-- Mini calendario (placeholder) -->
                    <h6 class="text-uppercase text-muted small mt-4 mb-3">Próximas Citas</h6>
                    <div id="proximas-citas" class="list-group list-group-flush small">
                        <!-- Se llenará con JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Calendario principal -->
        <div class="col-lg-9 col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div id="calendar" style="min-height: 700px;"></div>
                    
                    <?php if (empty($eventos)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3 text-muted">
                            <i class="fas fa-calendar-alt fa-4x opacity-25"></i>
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
                    
                    <!-- Panel derecho - Acciones -->
                    <div class="d-flex">
                        <!-- Botones principales -->
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

<!-- Modal de Ayuda del Calendario -->
<div class="modal fade" id="ayudaModal" tabindex="-1" aria-labelledby="ayudaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="ayudaModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Ayuda del Calendario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="border-start border-info border-3 ps-3">
                            <h5><i class="fas fa-mouse-pointer me-2 text-info"></i>Arrastrar y Soltar</h5>
                            <p class="text-muted">Puedes reprogramar citas arrastrándolas a una nueva fecha u hora en el calendario. Simplemente haz clic y mantén presionado para mover la cita a otro momento.</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="border-start border-success border-3 ps-3">
                            <h5><i class="fas fa-arrows-alt-v me-2 text-success"></i>Cambiar Duración</h5>
                            <p class="text-muted">Para cambiar la duración de una cita, haz clic en el borde inferior del evento y arrástralo hacia arriba o abajo para acortar o alargar la duración.</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="border-start border-warning border-3 ps-3">
                            <h5><i class="fas fa-calendar-plus me-2 text-warning"></i>Crear Nueva Cita</h5>
                            <p class="text-muted">Haz clic en cualquier espacio vacío del calendario para crear una nueva cita en esa fecha y hora específica.</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="border-start border-primary border-3 ps-3">
                            <h5><i class="fas fa-info-circle me-2 text-primary"></i>Ver Detalles</h5>
                            <p class="text-muted">Haz clic en cualquier cita para ver sus detalles completos, editar información o cambiar su estado actual.</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border">
                            <h6 class="mb-2"><i class="fas fa-lightbulb me-2 text-warning"></i>Consejos útiles:</h6>
                            <ul class="mb-0">
                                <li>Utiliza las vistas de Mes, Semana o Día según necesites visualizar las citas.</li>
                                <li>Filtra las citas por su estado usando los interruptores en el panel lateral.</li>
                                <li>Si una cita no puede ser movida a un horario específico, aparecerá un mensaje indicando el conflicto.</li>
                                <li>Los cambios se guardan automáticamente después de confirmar la acción.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
    
    // Elemento de calendario
    var calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        return;
    }
    
    // Inicializar modal
    var citaModal = new bootstrap.Modal(document.getElementById('citaModal'));
    var currentEventId = null;
    
    // Eventos del calendario
    var eventos = <?php echo $eventos_json; ?>;
    
    try {
        // Crear instancia de FullCalendar
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es',
            initialView: 'dayGridMonth',
            themeSystem: 'bootstrap5',
            height: 'auto',
            events: eventos,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Lista'
            },
            dayMaxEvents: true,
            nowIndicator: true,
            businessHours: {
                daysOfWeek: [ 1, 2, 3, 4, 5 ],
                startTime: '09:00',
                endTime: '18:00',
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            eventDisplay: 'block',
            slotDuration: '00:15:00',
            slotLabelInterval: '01:00:00',
            editable: true, // Permite arrastrar y soltar
            eventStartEditable: true, // Permite mover eventos
            eventDurationEditable: true, // Permite redimensionar eventos
            eventResizableFromStart: false, // Solo permite redimensionar el final
            snapDuration: '00:05:00', // Incrementos de 5 minutos al redimensionar
            eventOverlap: false, // No permitir eventos superpuestos
            allDaySlot: false, // Ocultar la ranura 'todo el día'
            scrollTime: '08:00:00', // Scroll inicial a las 8:00 AM
            slotMinTime: '07:00:00', // Hora mínima en la vista
            slotMaxTime: '21:00:00', // Hora máxima en la vista
            longPressDelay: 300, // Para dispositivos táctiles
            unselectAuto: true,
            selectLongPressDelay: 300,
            eventLongPressDelay: 300,
            selectable: true, // Permite seleccionar intervalos de tiempo
            selectMirror: true, // Muestra un evento "espejo" durante selección
            selectMinDistance: 5, // Distancia mínima para iniciar una selección
            eventDrop: function(info) {
                // Capturar información de la cita arrastrada
                const citaId = info.event.id;
                const nuevaFecha = info.event.start.toISOString();
                let nuevaFechaFin = info.event.end ? info.event.end.toISOString() : 
                                    new Date(info.event.start.getTime() + 30*60000).toISOString();
                
                // Confirmar el cambio
                if (confirm('¿Deseas reprogramar esta cita para el ' + 
                          info.event.start.toLocaleDateString() + ' a las ' + 
                          info.event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '?')) {
                    
                    // Mostrar indicador de carga
                    mostrarToast('Actualizando cita...', 'info');
                    
                    // Enviar petición a la API
                    fetch('update_date.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: citaId,
                            start: nuevaFecha,
                            end: nuevaFechaFin
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar el evento en el calendario
                            info.event.setProp('backgroundColor', info.event.backgroundColor);
                            info.event.setProp('borderColor', info.event.borderColor);
                            
                            // Actualizar propiedades extendidas
                            const fechaFormateada = new Date(info.event.start).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                            info.event.setExtendedProp('fecha', fechaFormateada);
                            info.event.setExtendedProp('hora_inicio', nuevaFecha.substr(11, 5));
                            info.event.setExtendedProp('hora_fin', nuevaFechaFin.substr(11, 5));
                            
                            // Actualizar próximas citas
                            actualizarProximasCitas();
                            
                            // Mostrar notificación de éxito
                            mostrarToast('Cita reprogramada correctamente para el ' + fechaFormateada, 'success');
                        } else {
                            // Si hay conflicto con otra cita
                            if (data.conflict) {
                                mostrarToast('No se pudo reprogramar: Conflicto con otra cita existente', 'danger');
                            } else {
                                mostrarToast('Error al reprogramar: ' + data.message, 'danger');
                            }
                            info.revert(); // Revertir el cambio en el calendario
                        }
                    })
                    .catch(error => {
                        mostrarToast('Error de conexión al actualizar la cita', 'danger');
                        info.revert(); // Revertir el cambio
                    });
                } else {
                    info.revert(); // Cancelar cambio si el usuario no confirma
                }
            },
            eventResize: function(info) {
                // Capturar información de la cita redimensionada
                const citaId = info.event.id;
                const fechaInicio = info.event.start.toISOString();
                const fechaFin = info.event.end.toISOString();
                
                // Confirmar el cambio
                if (confirm('¿Deseas cambiar la duración de esta cita?')) {
                    // Mostrar indicador de carga
                    mostrarToast('Actualizando duración...', 'info');
                    
                    // Enviar petición a la API
                    fetch('update_time.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: citaId,
                            start: fechaInicio,
                            end: fechaFin
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar propiedades extendidas
                            info.event.setExtendedProp('hora_inicio', data.data.hora_inicio);
                            info.event.setExtendedProp('hora_fin', data.data.hora_fin);
                            
                            // Mostrar notificación de éxito
                            mostrarToast('Duración de la cita actualizada correctamente', 'success');
                        } else {
                            // Si hay conflicto con otra cita
                            if (data.conflict) {
                                mostrarToast('No se pudo cambiar: Conflicto con otra cita existente', 'danger');
                            } else {
                                mostrarToast('Error al cambiar duración: ' + data.message, 'danger');
                            }
                            info.revert(); // Revertir el cambio en el calendario
                        }
                    })
                    .catch(error => {
                        mostrarToast('Error de conexión al actualizar la duración', 'danger');
                        info.revert(); // Revertir el cambio
                    });
                } else {
                    info.revert(); // Cancelar cambio si el usuario no confirma
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
                    document.getElementById('cita-motivo').textContent = props.motivo;
                    
                    // Mostrar el tipo de cita
                    var tipoClass = 'primary';
                    var tipoTexto = 'Consulta';
                    
                    switch (props.tipo_cita) {
                        case 'tratamiento':
                            tipoClass = 'info';
                            tipoTexto = 'Tratamiento';
                            break;
                        case 'seguimiento':
                            tipoClass = 'secondary';
                            tipoTexto = 'Seguimiento';
                            break;
                        case 'otro':
                            tipoClass = 'dark';
                            tipoTexto = 'Otro';
                            break;
                    }
                    
                    // Si no existe aún, añadir el elemento para el tipo de cita
                    var tipoContainer = document.getElementById('cita-tipo');
                    if (!tipoContainer) {
                        // Crear después del motivo
                        var motivoContainer = document.getElementById('cita-motivo').parentNode;
                        var tipoDiv = document.createElement('div');
                        tipoDiv.className = 'mb-3';
                        tipoDiv.innerHTML = `
                            <h6 class="text-muted mb-1">Tipo</h6>
                            <p id="cita-tipo" class="mb-0"></p>
                        `;
                        motivoContainer.parentNode.insertBefore(tipoDiv, motivoContainer.nextSibling);
                        tipoContainer = document.getElementById('cita-tipo');
                    }
                    
                    tipoContainer.innerHTML = `<span class="badge bg-${tipoClass} px-3 py-2">${tipoTexto}</span>`;
                    
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
            dateClick: function(info) {
                // Redirigir a crear cita con la fecha seleccionada
                window.location.href = '../citas/create.php?fecha=' + info.dateStr;
            },
            eventDidMount: function(info) {
                // Tooltips en eventos
                var tooltip = new bootstrap.Tooltip(info.el, {
                    title: info.event.title,
                    placement: 'top',
                    boundary: 'window',
                    template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner text-start small"></div></div>'
                });
            }
        });
        
        // Renderizar el calendario
        calendar.render();
        
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
        
        // Filtros por tipo de cita
        document.getElementById('filtro-consulta').addEventListener('change', function() {
            filtrarEventos();
        });
        
        document.getElementById('filtro-tratamiento').addEventListener('change', function() {
            filtrarEventos();
        });
        
        document.getElementById('filtro-seguimiento').addEventListener('change', function() {
            filtrarEventos();
        });
        
        document.getElementById('filtro-otro').addEventListener('change', function() {
            filtrarEventos();
        });
        
        // Enlaces de cambiar estado
        document.querySelectorAll('.cambiar-estado').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var nuevoEstado = this.getAttribute('data-estado');
                cambiarEstadoCita(currentEventId, nuevoEstado);
            });
        });
        
        // Función para filtrar eventos
        function filtrarEventos() {
            var mostrarPendientes = document.getElementById('filtro-pendiente').checked;
            var mostrarCompletadas = document.getElementById('filtro-completada').checked;
            var mostrarCanceladas = document.getElementById('filtro-cancelada').checked;
            
            var mostrarConsultas = document.getElementById('filtro-consulta').checked;
            var mostrarSeguimientos = document.getElementById('filtro-seguimiento').checked;
            var mostrarTratamientos = document.getElementById('filtro-tratamiento').checked;
            var mostrarOtros = document.getElementById('filtro-otro').checked;
            
            calendar.getEvents().forEach(function(evento) {
                var estado = evento.extendedProps.estado;
                var tipoCita = evento.extendedProps.tipo_cita;
                var mostrar = true;
                
                // Comprobar estado
                if (estado === 'pendiente' && !mostrarPendientes) mostrar = false;
                if (estado === 'completada' && !mostrarCompletadas) mostrar = false;
                if (estado === 'cancelada' && !mostrarCanceladas) mostrar = false;
                
                // Comprobar tipo
                if (tipoCita === 'consulta' && !mostrarConsultas) mostrar = false;
                if (tipoCita === 'seguimiento' && !mostrarSeguimientos) mostrar = false;
                if (tipoCita === 'tratamiento' && !mostrarTratamientos) mostrar = false;
                if (tipoCita === 'otro' && !mostrarOtros) mostrar = false;
                
                // Mostrar u ocultar evento
                if (mostrar) {
                    evento.setProp('display', 'auto');
                } else {
                    evento.setProp('display', 'none');
                }
            });
        }
        
        // Función para actualizar lista de próximas citas
        function actualizarProximasCitas() {
            var container = document.getElementById('proximas-citas');
            if (!container) return;
            
            // Vaciar contenedor
            container.innerHTML = '';
            
            // Obtener fecha actual
            var hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            // Filtrar citas futuras y ordenar por fecha
            var citasFuturas = eventos
                .filter(function(evento) {
                    var fechaEvento = new Date(evento.start);
                    return fechaEvento >= hoy && evento.extendedProps.estado !== 'cancelada';
                })
                .sort(function(a, b) {
                    return new Date(a.start) - new Date(b.start);
                })
                .slice(0, 5); // Tomar solo las 5 primeras
            
            if (citasFuturas.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3">No hay citas próximas</div>';
                return;
            }
            
            // Añadir cada cita a la lista
            citasFuturas.forEach(function(cita) {
                var fechaCita = new Date(cita.start);
                var esMismoDia = fechaCita.toDateString() === hoy.toDateString();
                var esManana = new Date(hoy.getTime() + 24*60*60*1000).toDateString() === fechaCita.toDateString();
                
                var fechaTexto = esMismoDia ? 'Hoy' : (esManana ? 'Mañana' : cita.extendedProps.fecha);
                var estadoClass = cita.extendedProps.estado === 'pendiente' ? 'warning' : 'success';
                
                var item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action px-0 py-2 border-0';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${fechaTexto}, ${cita.extendedProps.hora_inicio}</div>
                            <div class="text-truncate">${cita.extendedProps.paciente}</div>
                        </div>
                        <span class="badge bg-${estadoClass} rounded-pill"></span>
                    </div>
                `;
                
                // Al hacer clic, mostrar el detalle
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Buscar el evento en el calendario y disparar el click
                    var evento = calendar.getEventById(cita.id);
                    if (evento) {
                        // Hacer scroll al evento
                        calendar.gotoDate(evento.start);
                        // Simular click
                        calendar.trigger('eventClick', {event: evento});
                    }
                });
                
                container.appendChild(item);
            });
        }
        
        // Función para cambiar estado de una cita
        function cambiarEstadoCita(citaId, nuevoEstado) {
            // Aquí iría el código para actualizar en el servidor
            // Por ahora sólo actualizamos la interfaz
            
            // Buscar el evento
            var evento = calendar.getEventById(citaId);
            if (!evento) return;
            
            // Asignar nuevo color
            var nuevoColor = nuevoEstado === 'pendiente' ? '#f39c12' : 
                            (nuevoEstado === 'completada' ? '#2ecc71' : '#e74c3c');
            
            // Actualizar evento
            evento.setProp('backgroundColor', nuevoColor);
            evento.setProp('borderColor', nuevoColor);
            evento.setExtendedProp('estado', nuevoEstado);
            
            // Actualizar estado en el modal
            var estadoClass = nuevoEstado === 'pendiente' ? 'warning' : 
                             (nuevoEstado === 'completada' ? 'success' : 'danger');
            var estadoTexto = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);
            document.getElementById('cita-estado').innerHTML = 
                '<span class="badge bg-' + estadoClass + ' px-3 py-2">' + estadoTexto + '</span>';
            
            // Mostrar notificación
            mostrarToast('Estado de la cita actualizado a: ' + estadoTexto);
            
            // Actualizar lista de próximas citas
            actualizarProximasCitas();
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
        
        // Inicializar vista actual
        highlightActiveButton('btn-vista-mes');
        
        // Ahora que todas las funciones están definidas, actualizar próximas citas
        actualizarProximasCitas();
        
        // Establecer fecha para impresión
        document.getElementById('calendar').setAttribute('data-print-date', new Date().toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }));
        
    } catch (error) {
        // Error silencioso en la inicialización del calendario
    }

    // Botón para exportar todo el calendario
    document.getElementById('btn-exportar-calendario').addEventListener('click', function() {
        // Obtener rango de fechas del calendario actual
        var view = calendar.view;
        var startDate = view.activeStart.toISOString().substring(0, 10);
        var endDate = view.activeEnd.toISOString().substring(0, 10);
        
        // Construir URL de exportación con el rango actual
        var exportUrl = '<?= BASE_URL ?>/modules/calendario/export_ical.php?all=1&fecha_inicio=' + startDate + '&fecha_fin=' + endDate;
        
        // Redirigir a la exportación
        window.location.href = exportUrl;
    });

    // Funcionalidad de exportación a iCalendar
    document.getElementById('btn-exportar-ical').addEventListener('click', function(e) {
        e.preventDefault();
        exportarICalendar();
    });

    /**
     * Función para exportar eventos a formato iCalendar
     * @param {boolean} soloVisible - Exportar solo eventos en el período visible
     */
    function exportarICalendar(soloVisible = false) {
        // Crear contenido iCalendar
        var icalContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Clínica//Calendario de Citas//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH'
        ];
        
        // Función para formatear fecha en formato iCalendar
        function formatoICal(fecha) {
            // Formato: YYYYMMDDTHHMMSSZ
            return fecha.replace(/[-:]/g, '').replace('T', 'T');
        }
        
        // Filtrar eventos según parámetro
        var eventosExportar = eventos;
        if (soloVisible) {
            var rangoVisible = calendar.view.getCurrentData().dateProfile.activeRange;
            var fechaInicio = rangoVisible.start;
            var fechaFin = rangoVisible.end;
            
            eventosExportar = eventosExportar.filter(function(evento) {
                var eventStart = new Date(evento.start);
                return eventStart >= fechaInicio && eventStart <= fechaFin;
            });
        }
        
        // Generar eventos
        eventosExportar.forEach(function(evento) {
            var fechaCreacion = new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
            var uid = 'cita-' + evento.id + '@clinica.com';
            
            icalContent.push('BEGIN:VEVENT');
            icalContent.push('UID:' + uid);
            icalContent.push('DTSTAMP:' + fechaCreacion);
            icalContent.push('DTSTART:' + formatoICal(evento.start));
            icalContent.push('DTEND:' + formatoICal(evento.end));
            icalContent.push('SUMMARY:' + evento.title);
            
            // Añadir descripción si hay notas
            if (evento.extendedProps.notas) {
                icalContent.push('DESCRIPTION:' + evento.extendedProps.notas.replace(/\n/g, '\\n'));
            }
            
            // Añadir estado
            var status = evento.extendedProps.estado === 'cancelada' ? 'CANCELLED' : 
                        (evento.extendedProps.estado === 'completada' ? 'CONFIRMED' : 'TENTATIVE');
            icalContent.push('STATUS:' + status);
            
            icalContent.push('END:VEVENT');
        });
        
        icalContent.push('END:VCALENDAR');
        
        // Crear archivo para descargar
        var blob = new Blob([icalContent.join('\r\n')], {type: 'text/calendar;charset=utf-8'});
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'citas_clinica.ics';
        link.click();
        
        // Notificar al usuario
        mostrarToast('Calendario exportado correctamente', 'success');
    }

    // Función para resaltar botón activo (que faltaba definir)
    function highlightActiveButton(buttonId) {
        // Esta función es usada en otras partes del código
    }
});
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS para el calendario
$extra_css = <<<EOT
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" rel="stylesheet">
<style>
    /* Estilos para el calendario */
    #calendar {
        background: white;
    }
    
    /* Mejorar botones */
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
    
    /* Mejorar eventos */
    .fc-event {
        cursor: pointer;
        border-radius: 4px;
        border: none !important;
        padding: 2px 4px;
        font-size: 0.85rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 10;
    }
    
    .fc-event-title {
        font-weight: 500;
    }
    
    /* Mejorar eventos con tiempo */
    .fc-timegrid-event {
        padding: 2px 6px;
    }
    
    .fc-timegrid-event .fc-event-time {
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .fc-timegrid-event .fc-event-title {
        white-space: normal;
        font-size: 0.8rem;
    }
    
    /* Estilo para vista de lista */
    .fc-list-table {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .fc-list-day-cushion {
        background-color: #f8f9fa !important;
    }
    
    .fc-list-event:hover td {
        background-color: #f0f7ff !important;
    }
    
    .fc-list-event-dot {
        border-width: 6px !important;
    }
    
    .fc-list-event-title {
        font-weight: 500;
    }
    
    .fc-list-event-time {
        font-weight: 600;
        color: #495057;
    }
    
    /* Resaltado al arrastrar */
    .fc-event.fc-event-dragging {
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        opacity: 0.8;
    }
    
    /* Resaltado al redimensionar */
    .fc-event.fc-event-resizing {
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        border: 2px dashed #fff !important;
    }
    
    /* Guía de tiempo actual */
    .fc .fc-timegrid-now-indicator-line {
        border-color: #ff3860;
        border-width: 2px;
    }
    
    .fc .fc-timegrid-now-indicator-arrow {
        border-color: #ff3860;
        border-width: 5px;
    }
    
    /* Mejorar cabeceras */
    .fc-col-header-cell {
        background-color: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        padding: 10px 0;
    }
    
    .fc-daygrid-day-number,
    .fc-col-header-cell-cushion {
        color: #495057;
        text-decoration: none !important;
    }
    
    /* Destacar días */
    .fc-day-today {
        background-color: rgba(58, 134, 255, 0.05) !important;
    }
    
    .fc-day-today .fc-daygrid-day-number {
        font-weight: bold;
        color: #3a86ff;
    }
    
    /* Bordes y divisiones */
    .fc th, .fc td {
        border-color: #eaecf0 !important;
    }
    
    .fc-timegrid-slot, .fc-timegrid-axis {
        height: 48px !important;
    }
    
    /* Mejorar tooltips */
    .tooltip .tooltip-inner {
        max-width: 300px;
        padding: 8px 12px;
        background-color: rgba(33, 37, 41, 0.95);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    /* Animaciones */
    .fc-event, .fc-button {
        transition: all 0.2s ease;
    }
    
    /* Mejorar modal */
    .modal-content {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .modal-backdrop {
        backdrop-filter: blur(2px);
    }
    
    /* Proximas citas */
    #proximas-citas .list-group-item:hover {
        background-color: #f8f9fa;
    }
    
    #proximas-citas .badge {
        width: 10px;
        height: 10px;
        padding: 0;
    }
    
    /* CSS para los indicadores de disponibilidad */
    .disponibilidad-indicador {
        display: inline-block;
        width: 16px;
        height: 16px;
        border-radius: 4px;
    }
    
    .disponibilidad-baja {
        background-color: rgba(231, 76, 60, 0.2);
    }
    
    .disponibilidad-media {
        background-color: rgba(243, 156, 18, 0.2);
    }
    
    .disponibilidad-alta {
        background-color: rgba(46, 204, 113, 0.2);
    }
    
    .disponibilidad-cell {
        transition: background-color 0.3s ease;
    }
    
    /* Estilos para impresión */
    @media print {
        /* Ocultar elementos que no son necesarios para imprimir */
        .container-fluid {
            padding: 0 !important;
        }
        
        nav, .sidebar, .card-header,
        .breadcrumb, .col-lg-3, .btn, .form-check,
        .fc-toolbar-chunk:last-child, .fc-toolbar-chunk:first-child,
        footer, header, .toast-container {
            display: none !important;
        }
        
        /* Ajustar el calendario para que ocupe toda la página */
        .col-lg-9 {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }
        
        /* Eliminar sombras y bordes para ahorrar tinta */
        .card, .shadow-sm {
            box-shadow: none !important;
            border: none !important;
        }
        
        /* Reducir el padding y margin para aprovechar el espacio */
        .card-body {
            padding: 0 !important;
        }
        
        /* Asegurar que los textos sean negros y legibles */
        body {
            color: #000 !important;
            background: #fff !important;
        }
        
        /* Asegurar que el título del calendario esté visible */
        .fc-toolbar-title {
            font-size: 18pt !important;
            color: #000 !important;
            text-align: center !important;
            margin-bottom: 10px !important;
            display: block !important;
            width: 100% !important;
        }
        
        /* Estilos para las celdas y eventos */
        .fc-daygrid-day-number {
            color: #000 !important;
        }
        
        .fc-col-header-cell {
            background-color: #fff !important;
        }
        
        .fc-day-today {
            background-color: #fff !important;
            border: 1px solid #000 !important;
        }
        
        /* Estilos específicos para eventos en la impresión */
        .fc-event {
            border: 1px solid #000 !important;
            color: #000 !important;
            page-break-inside: avoid !important;
        }
        
        /* Estilos para eventos en vistas de tiempo */
        .fc-timegrid-event {
            border: 1px solid #000 !important;
            page-break-inside: avoid !important;
        }
        
        /* Añadir estilos para vista de lista */
        .fc-list-event {
            page-break-inside: avoid !important;
        }
        
        /* Encabezado de página para impresión */
        @page {
            size: portrait;
            margin: 1cm;
        }
        
        /* Añadir encabezado y pie de impresión */
        .fc:after {
            content: "Impreso el " attr(data-print-date);
            display: block;
            text-align: center;
            font-size: 10pt;
            margin-top: 20px;
        }
        
        /* Mejorar visualización en tablas */
        table {
            border-collapse: collapse !important;
        }
        
        th, td {
            border: 1px solid #ddd !important;
        }
    }
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal
include ROOT_PATH . '/includes/layouts/main.php';
?> 