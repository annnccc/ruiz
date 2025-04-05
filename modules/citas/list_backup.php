<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación para acceder a esta página
requiereLogin();

// Filtros
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-1 year'));
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d', strtotime('+1 year'));

// Convertir las fechas si vienen en formato DD/MM/YYYY
if (isset($_GET['fecha_desde']) && strpos($_GET['fecha_desde'], '/') !== false) {
    $fecha_desde = formatDateToDB(sanitize($_GET['fecha_desde']));
} else {
    $fecha_desde = sanitize($fecha_desde);
}

if (isset($_GET['fecha_hasta']) && strpos($_GET['fecha_hasta'], '/') !== false) {
    $fecha_hasta = formatDateToDB(sanitize($_GET['fecha_hasta']));
} else {
    $fecha_hasta = sanitize($fecha_hasta);
}

$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;
$estado = isset($_GET['estado']) ? sanitize($_GET['estado']) : '';
$pagada = isset($_GET['pagada']) ? sanitize($_GET['pagada']) : '';

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ordenación
$sort_field = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'fecha';
$sort_direction = isset($_GET['direction']) && strtolower($_GET['direction']) === 'desc' ? 'DESC' : 'ASC';

// Validar campos de ordenación permitidos
$allowed_sort_fields = ['fecha', 'hora_inicio', 'paciente', 'servicio'];
if (!in_array($sort_field, $allowed_sort_fields)) {
    $sort_field = 'fecha';
}

// Para DataTables server-side processing, no necesitamos la paginación PHP
// ya que DataTables la maneja en el cliente
$whereConditions = ['c.fecha BETWEEN :fecha_desde AND :fecha_hasta'];
$params = [
    ':fecha_desde' => $fecha_desde,
    ':fecha_hasta' => $fecha_hasta
];

if ($paciente_id > 0) {
    $whereConditions[] = 'c.paciente_id = :paciente_id';
    $params[':paciente_id'] = $paciente_id;
}

if (!empty($estado)) {
    $whereConditions[] = 'c.estado = :estado';
    $params[':estado'] = $estado;
}

if ($pagada !== '') {
    $isPagada = ($pagada === '1') ? 1 : 0;
    $whereConditions[] = 'c.pagada = :pagada';
    $params[':pagada'] = $isPagada;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Obtener clase para badge de estado
function getEstadoBadgeClass($estado) {
    switch ($estado) {
        case 'pendiente':
            return 'bg-warning text-dark';
        case 'completada':
            return 'bg-success';
        case 'cancelada':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

/**
 * Genera una URL para enviar un mensaje por WhatsApp
 * @param string $telefono Número de teléfono del destinatario
 * @param array $cita Datos de la cita
 * @return string URL formateada para WhatsApp
 */
function generarUrlWhatsApp($telefono, $cita) {
    // Limpiar el teléfono (quitar espacios, guiones, etc.)
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Asegurarnos que tenga el prefijo de España (34)
    if (strlen($telefono) == 9) {
        $telefono = '34' . $telefono;
    }
    
    // Preparar el mensaje
    $mensaje = "Hola " . $cita['paciente_nombre'] . ", ";
    $mensaje .= "le recordamos su cita programada para el " . formatDateToView($cita['fecha']);
    $mensaje .= " a las " . $cita['hora_inicio'] . " para " . ($cita['servicio_nombre'] ?? 'consulta') . ". ";
    $mensaje .= "Por favor, avísenos si necesita cancelar o reprogramar. Gracias.";
    
    // Codificar el mensaje para URL
    $mensaje_codificado = urlencode($mensaje);
    
    // Generar la URL de WhatsApp
    return "https://api.whatsapp.com/send?phone={$telefono}&text={$mensaje_codificado}";
}

try {
    // Obtener conexión a la base de datos
    $db = getDB();
    
    // Construir la consulta SQL
    $query = "SELECT c.*, 
              p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, p.telefono as paciente_telefono, p.email,
              s.nombre as servicio_nombre
              FROM citas c
              LEFT JOIN pacientes p ON c.paciente_id = p.id
              LEFT JOIN servicios s ON c.servicio_id = s.id";

    // Si hay filtro por rango de fechas
    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $query .= " WHERE c.fecha BETWEEN :fecha_desde AND :fecha_hasta";
    }

    // Ordenación
    switch ($sort_field) {
        case 'paciente':
            $query .= " ORDER BY p.apellidos $sort_direction, p.nombre $sort_direction";
            break;
        case 'servicio':
            $query .= " ORDER BY s.nombre $sort_direction";
            break;
        case 'hora_inicio':
            $query .= " ORDER BY c.hora_inicio $sort_direction";
            break;
        case 'fecha':
        default:
            $query .= " ORDER BY c.fecha $sort_direction, c.hora_inicio ASC";
            break;
    }

    $query .= " LIMIT :offset, :records_per_page";
    
    $stmt = $db->prepare($query);
    
    // Vincular parámetros
    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $stmt->bindParam(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        $stmt->bindParam(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
    }

    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);

    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de registros para paginación
    $countQuery = "SELECT COUNT(*) as total FROM citas c";
    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $countQuery .= " WHERE c.fecha BETWEEN :fecha_desde AND :fecha_hasta";
    }

    $countStmt = $db->prepare($countQuery);
    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $countStmt->bindParam(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        $countStmt->bindParam(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $records_per_page);
    
    // Obtener recuentos de citas por estado para toda la base de datos, no solo la página actual
    $countByStateQuery = "SELECT estado, COUNT(*) as total FROM citas c";
    $whereClause = "";
    
    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $whereClause = " WHERE c.fecha BETWEEN :fecha_desde AND :fecha_hasta";
    }
    
    if ($paciente_id > 0) {
        $whereClause = empty($whereClause) ? " WHERE" : $whereClause . " AND";
        $whereClause .= " c.paciente_id = :paciente_id";
    }
    
    if (!empty($estado)) {
        $whereClause = empty($whereClause) ? " WHERE" : $whereClause . " AND";
        $whereClause .= " c.estado = :estado";
    }
    
    if ($pagada !== '') {
        $whereClause = empty($whereClause) ? " WHERE" : $whereClause . " AND";
        $whereClause .= " c.pagada = :pagada";
    }
    
    $countByStateQuery .= $whereClause . " GROUP BY estado";
    
    $countByStateStmt = $db->prepare($countByStateQuery);
    
    if (!empty($fecha_desde) && !empty($fecha_hasta)) {
        $countByStateStmt->bindParam(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        $countByStateStmt->bindParam(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
    }
    
    if ($paciente_id > 0) {
        $countByStateStmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
    }
    
    if (!empty($estado)) {
        $countByStateStmt->bindParam(':estado', $estado, PDO::PARAM_STR);
    }
    
    if ($pagada !== '') {
        $isPagada = ($pagada === '1') ? 1 : 0;
        $countByStateStmt->bindParam(':pagada', $isPagada, PDO::PARAM_INT);
    }
    
    $countByStateStmt->execute();
    $estadosCounts = $countByStateStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Inicializar contadores
    $pendienteCount = $estadosCounts['pendiente'] ?? 0;
    $completadaCount = $estadosCounts['completada'] ?? 0;
    $canceladaCount = $estadosCounts['cancelada'] ?? 0;
    
    // Obtener pacientes para el filtro
    $stmt = $db->prepare("SELECT id, nombre, apellidos FROM pacientes ORDER BY apellidos ASC, nombre ASC");
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos: ' . $e->getMessage());
    $citas = [];
    $pacientes = [];
}

// Título y breadcrumbs para la página
$titulo_pagina = "Listado de Citas";
$title = $titulo_pagina;
$breadcrumbs = [
    'Citas' => '#'
];

// Definir el JavaScript adicional antes de empezar la captura del contenido
$extra_js = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Flatpickr para los selectores de fecha
    flatpickr(".date-picker", {
        locale: "es",
        dateFormat: "d/m/Y",
        allowInput: true,
        clickOpens: true,
        position: "auto",
        static: true,
        onChange: function(selectedDates, dateStr, instance) {
            // Sin depuración
        }
    });
    
    // Permitir también abrir el calendario al hacer clic en el icono
    document.querySelectorAll('.input-group .input-group-text').forEach(function(icon) {
        icon.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('.date-picker');
            if (input._flatpickr) {
                input._flatpickr.open();
            }
        });
    });
    
    // Asegurar que Bootstrap se ha cargado
    if (typeof bootstrap !== 'undefined') {
        // Inicializar los dropdowns
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
        });
        
        // Agregar manejo adicional para abrir dropdowns al hacer clic
        document.querySelectorAll('.estado-selector').forEach(function(toggler) {
            toggler.addEventListener('click', function() {
                // Encuentra el dropdown en Bootstrap
                var dropdownInstance = bootstrap.Dropdown.getInstance(toggler);
                if (!dropdownInstance) {
                    // Si no existe, créalo
                    dropdownInstance = new bootstrap.Dropdown(toggler);
                }
                // Abre el dropdown
                dropdownInstance.toggle();
            });
        });
    }
    
    // Inicializar tooltips de Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirmación para eliminar
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar esta cita?')) {
                e.preventDefault();
            }
        });
    });
    
    // Manejo de cambio de estado con el nuevo dropdown
    document.querySelectorAll('.estado-option').forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const nuevoEstado = this.getAttribute('data-estado');
            const citaId = this.closest('.estado-menu').getAttribute('data-cita-id');
            
            if (nuevoEstado) {
                // Mostrar confirmación
                if (confirm('¿Está seguro de que desea cambiar el estado de esta cita a ' + nuevoEstado + '?')) {
                    // Redirigir a change_status.php
                    window.location.href = 'change_status.php?id=' + citaId + '&status=' + nuevoEstado;
                }
            }
        });
    });
});
</script>
HTML;

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<style>
.sort-icon {
    font-size: 1rem;
    vertical-align: middle;
    color: #0d6efd;
}
th {
    cursor: pointer;
    user-select: none;
    position: relative;
    padding-right: 25px !important;
}
th:hover {
    background-color: rgba(0,0,0,0.05);
}
th .sort-icon {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
}
th:nth-last-child(-n+2) {
    cursor: default;
}
th:nth-last-child(-n+2):hover {
    background-color: transparent;
}
</style>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">event_note</span><?= $titulo_pagina ?>
        </h1>
        <a href="<?= BASE_URL ?>/modules/citas/create.php" class="btn btn-primary">
            <span class="material-symbols-rounded">add_circle</span> Nueva Cita
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">filter_alt</span>Filtros
            </h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row gy-3">
                <div class="col-lg-4 col-md-6">
                    <label for="fecha_desde" class="form-label">Desde</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <span class="material-symbols-rounded icon-sm">calendar_today</span>
                        </span>
                        <input type="text" class="form-control date-picker" id="fecha_desde" name="fecha_desde" placeholder="DD/MM/YYYY" value="<?= formatDateToView($fecha_desde) ?>">
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label for="fecha_hasta" class="form-label">Hasta</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <span class="material-symbols-rounded icon-sm">calendar_today</span>
                        </span>
                        <input type="text" class="form-control date-picker" id="fecha_hasta" name="fecha_hasta" placeholder="DD/MM/YYYY" value="<?= formatDateToView($fecha_hasta) ?>">
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label for="paciente_id" class="form-label">Paciente</label>
                    <select class="form-select" id="paciente_id" name="paciente_id">
                        <option value="0">Todos los pacientes</option>
                        <?php foreach ($pacientes as $paciente): ?>
                        <option value="<?= $paciente['id'] ?>" <?= ($paciente_id == $paciente['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= ($estado == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                        <option value="completada" <?= ($estado == 'completada') ? 'selected' : '' ?>>Completada</option>
                        <option value="cancelada" <?= ($estado == 'cancelada') ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6">
                    <label for="pagada" class="form-label">Pagada</label>
                    <select class="form-select" id="pagada" name="pagada">
                        <option value="">Todos</option>
                        <option value="1" <?= ($pagada === '1') ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= ($pagada === '0') ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="material-symbols-rounded">search</span> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resumen de citas -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 animate-fade-in">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-light-warning text-warning p-3 me-3">
                        <span class="material-symbols-rounded material-symbols-filled icon-lg">pending_actions</span>
                    </div>
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">Citas Pendientes</h6>
                        <h3 class="card-title mb-0"><?= $pendienteCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 animate-fade-in" style="animation-delay: 100ms;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-light-green text-success p-3 me-3">
                        <span class="material-symbols-rounded material-symbols-filled icon-lg">task_alt</span>
                    </div>
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">Citas Completadas</h6>
                        <h3 class="card-title mb-0"><?= $completadaCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 animate-fade-in" style="animation-delay: 200ms;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-light-danger text-danger p-3 me-3">
                        <span class="material-symbols-rounded material-symbols-filled icon-lg">cancel</span>
                    </div>
                    <div>
                        <h6 class="card-subtitle text-muted mb-1">Citas Canceladas</h6>
                        <h3 class="card-title mb-0"><?= $canceladaCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de citas -->
    <div class="card border-0 shadow-sm animate-fade-in" style="animation-delay: 300ms;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">format_list_bulleted</span>Listado de Citas
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0" id="tablaCitas">
                    <thead>
                        <tr>
                            <th onclick="window.location='?sort=fecha&direction=<?= ($sort_field === 'fecha' && $sort_direction === 'ASC') ? 'DESC' : 'ASC' ?>'">
                                Fecha
                                <?php if ($sort_field === 'fecha'): ?>
                                    <span class="material-symbols-rounded sort-icon">
                                        <?= $sort_direction === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                    </span>
                                <?php endif; ?>
                            </th>
                            <th onclick="window.location='?sort=hora_inicio&direction=<?= ($sort_field === 'hora_inicio' && $sort_direction === 'ASC') ? 'DESC' : 'ASC' ?>'">
                                Hora
                                <?php if ($sort_field === 'hora_inicio'): ?>
                                    <span class="material-symbols-rounded sort-icon">
                                        <?= $sort_direction === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                    </span>
                                <?php endif; ?>
                            </th>
                            <th onclick="window.location='?sort=paciente&direction=<?= ($sort_field === 'paciente' && $sort_direction === 'ASC') ? 'DESC' : 'ASC' ?>'">
                                Paciente
                                <?php if ($sort_field === 'paciente'): ?>
                                    <span class="material-symbols-rounded sort-icon">
                                        <?= $sort_direction === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                    </span>
                                <?php endif; ?>
                            </th>
                            <th onclick="window.location='?sort=servicio&direction=<?= ($sort_field === 'servicio' && $sort_direction === 'ASC') ? 'DESC' : 'ASC' ?>'">
                                Tipo de Sesión
                                <?php if ($sort_field === 'servicio'): ?>
                                    <span class="material-symbols-rounded sort-icon">
                                        <?= $sort_direction === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                    </span>
                                <?php endif; ?>
                            </th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($citas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <span class="material-symbols-rounded icon-xl mb-2" style="color: #ccc;">event_busy</span>
                                    <p>No se encontraron citas en el período seleccionado</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($citas as $cita): ?>
                                <tr>
                                    <td><?= formatDateToView($cita['fecha']) ?></td>
                                    <td><?= $cita['hora_inicio'] ?> - <?= $cita['hora_fin'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle avatar-sm me-2">
                                                <?= strtoupper(substr($cita['paciente_nombre'], 0, 1) . substr($cita['paciente_apellidos'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($cita['servicio_nombre'] ?? $cita['motivo']) ?></td>
                                    <td>
                                        <span class="badge <?= getEstadoBadgeClass($cita['estado']) ?>">
                                            <?= ucfirst($cita['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cita['pagada']): ?>
                                        <span class="badge bg-success">Pagada</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="table-actions">
                                            <a href="view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </a>
                                            <a href="edit.php?id=<?= $cita['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                                <span class="material-symbols-rounded">edit</span>
                                            </a>
                                            <?php if (!empty($cita['email'])): ?>
                                            <a href="../recordatorios/enviar_manual.php?cita_id=<?= $cita['id'] ?>" class="btn btn-notify" data-bs-toggle="tooltip" title="Enviar recordatorio">
                                                <span class="material-symbols-rounded">notifications</span>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (!empty($cita['paciente_telefono'])): ?>
                                            <a href="<?= generarUrlWhatsApp($cita['paciente_telefono'], $cita) ?>" class="btn btn-whatsapp" data-bs-toggle="tooltip" title="Enviar WhatsApp" target="_blank">
                                                <span class="material-symbols-rounded">chat</span>
                                            </a>
                                            <?php endif; ?>
                                            <a href="delete.php?id=<?= $cita['id'] ?>" class="btn btn-delete" data-bs-toggle="tooltip" title="Eliminar">
                                                <span class="material-symbols-rounded">delete</span>
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
    
    <!-- Paginación -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= $sort_field ?>&direction=<?= $sort_direction ?>" tabindex="-1">Anterior</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort_field ?>&direction=<?= $sort_direction ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= $sort_field ?>&direction=<?= $sort_direction ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
// Finalizar captura y renderizar con el JavaScript adicional
endPageContent($titulo_pagina, ['extra_js' => $extra_js]);
?> 