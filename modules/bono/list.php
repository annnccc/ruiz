<?php
// Página de listado de bonos con encabezado y sidebar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos básicos
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere login
requiereLogin();

// Procesar parámetros de filtro
$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;
$estado = isset($_GET['estado']) ? sanitizeInput($_GET['estado']) : '';
$fecha_compra_desde = isset($_GET['fecha_compra_desde']) ? sanitizeInput($_GET['fecha_compra_desde']) : '';
$fecha_compra_hasta = isset($_GET['fecha_compra_hasta']) ? sanitizeInput($_GET['fecha_compra_hasta']) : '';
$fecha_caducidad_desde = isset($_GET['fecha_caducidad_desde']) ? sanitizeInput($_GET['fecha_caducidad_desde']) : '';
$fecha_caducidad_hasta = isset($_GET['fecha_caducidad_hasta']) ? sanitizeInput($_GET['fecha_caducidad_hasta']) : '';

// Configurar paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Configurar ordenamiento
$sortable_fields = ['id', 'fecha_compra', 'fecha_caducidad', 'monto', 'estado', 'paciente_nombre'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $sortable_fields) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';

try {
    $db = getDB();
    
    // Construir condiciones SQL para la consulta
    $conditions = [];
    $params = [];
    
    if ($paciente_id > 0) {
        $conditions[] = "b.paciente_id = :paciente_id";
        $params[':paciente_id'] = $paciente_id;
    }
    
    if (!empty($estado)) {
        $conditions[] = "b.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if (!empty($fecha_compra_desde)) {
        $conditions[] = "b.fecha_compra >= :fecha_compra_desde";
        $params[':fecha_compra_desde'] = $fecha_compra_desde;
    }
    
    if (!empty($fecha_compra_hasta)) {
        $conditions[] = "b.fecha_compra <= :fecha_compra_hasta";
        $params[':fecha_compra_hasta'] = $fecha_compra_hasta . ' 23:59:59';
    }
    
    if (!empty($fecha_caducidad_desde)) {
        $conditions[] = "b.fecha_caducidad >= :fecha_caducidad_desde";
        $params[':fecha_caducidad_desde'] = $fecha_caducidad_desde;
    }
    
    if (!empty($fecha_caducidad_hasta)) {
        $conditions[] = "b.fecha_caducidad <= :fecha_caducidad_hasta";
        $params[':fecha_caducidad_hasta'] = $fecha_caducidad_hasta . ' 23:59:59';
    }
    
    $where_clause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
    
    // Inicializar contadores para cada estado
    $total_activos = 0;
    $total_consumidos = 0;
    $total_caducados = 0;
    
    // Consulta para estadísticas
    $queryStats = "SELECT 
                       SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                       SUM(CASE WHEN estado = 'consumido' THEN 1 ELSE 0 END) as consumidos,
                       SUM(CASE WHEN estado = 'caducado' THEN 1 ELSE 0 END) as caducados
                   FROM bonos b
                   $where_clause";
    
    $stmtStats = $db->prepare($queryStats);
    foreach ($params as $key => $value) {
        $stmtStats->bindValue($key, $value);
    }
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    $total_activos = (int)$stats['activos'];
    $total_consumidos = (int)$stats['consumidos'];
    $total_caducados = (int)$stats['caducados'];
    
    // Consulta para contar el total de registros con los filtros aplicados
    $queryCount = "SELECT COUNT(*) FROM bonos b $where_clause";
    $stmtCount = $db->prepare($queryCount);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRecords = $stmtCount->fetchColumn();
    
    // Consulta para obtener los bonos con paginación
    $query = "SELECT b.*, 
                     p.nombre as paciente_nombre, p.apellidos as paciente_apellidos
              FROM bonos b
              LEFT JOIN pacientes p ON b.paciente_id = p.id
              $where_clause
              ORDER BY $sort $order
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $bonos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener la lista de pacientes para el filtro
    $queryPacientes = "SELECT id, nombre, apellidos FROM pacientes ORDER BY apellidos, nombre";
    $stmtPacientes = $db->prepare($queryPacientes);
    $stmtPacientes->execute();
    $pacientes = $stmtPacientes->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Manejar error de base de datos
    $error = "Error en la consulta: " . $e->getMessage();
    $bonos = [];
    $totalRecords = 0;
    $pacientes = [];
}

// Calcular total de páginas para la paginación
$totalPages = ceil($totalRecords / $limit);

// Función para generar URL con parámetros de filtro y ordenamiento
function generateUrl($newParams = []) {
    $params = $_GET;
    
    foreach ($newParams as $key => $value) {
        if ($value === null && isset($params[$key])) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    
    return '?' . http_build_query($params);
}

// Función para obtener clase de badge según estado
function getEstadoBadgeClass($estado) {
    switch ($estado) {
        case 'activo':
            return 'bg-success';
        case 'agotado':
        case 'consumido':
            return 'bg-danger';
        case 'caducado':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary';
    }
}

// Iniciar buffer de salida
startPageContent();
?>

<div class="container-fluid py-4 bono-container module-page">
    <!-- Encabezado de la página -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">confirmation_number</span> Gestión de Bonos
        </h1>
        <a href="create.php" class="btn btn-primary">
            <span class="material-symbols-rounded me-1">add_circle</span> Nuevo Bono
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex">
                    <div class="icon-box bg-success-light me-3">
                        <span class="material-symbols-rounded text-success">check_circle</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small fw-normal mb-0">Bonos Activos</h6>
                        <h2 class="mb-0"><?= $total_activos ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex">
                    <div class="icon-box bg-danger-light me-3">
                        <span class="material-symbols-rounded text-danger">block</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small fw-normal mb-0">Bonos Consumidos</h6>
                        <h2 class="mb-0"><?= $total_consumidos ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex">
                    <div class="icon-box bg-warning-light me-3">
                        <span class="material-symbols-rounded text-warning">timer_off</span>
                    </div>
                    <div>
                        <h6 class="text-uppercase text-muted small fw-normal mb-0">Bonos Caducados</h6>
                        <h2 class="mb-0"><?= $total_caducados ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" id="filter-form" class="row g-3">
                <!-- Paciente -->
                <div class="col-md-4">
                    <label for="paciente_id" class="form-label">Paciente</label>
                    <select class="form-select" id="paciente_id" name="paciente_id">
                        <option value="">Todos los pacientes</option>
                        <?php foreach ($pacientes as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $paciente_id == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['apellidos'] . ', ' . $p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Estado -->
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="consumido" <?= $estado === 'consumido' ? 'selected' : '' ?>>Consumido</option>
                        <option value="caducado" <?= $estado === 'caducado' ? 'selected' : '' ?>>Caducado</option>
                    </select>
                </div>
                
                <!-- Fecha compra desde -->
                <div class="col-md-4">
                    <label for="fecha_compra_desde" class="form-label">Fecha compra desde</label>
                    <input type="date" class="form-control" id="fecha_compra_desde" name="fecha_compra_desde" value="<?= $fecha_compra_desde ?>">
                </div>
                
                <!-- Fecha compra hasta -->
                <div class="col-md-4">
                    <label for="fecha_compra_hasta" class="form-label">Fecha compra hasta</label>
                    <input type="date" class="form-control" id="fecha_compra_hasta" name="fecha_compra_hasta" value="<?= $fecha_compra_hasta ?>">
                </div>
                
                <!-- Fecha caducidad desde -->
                <div class="col-md-4">
                    <label for="fecha_caducidad_desde" class="form-label">Fecha caducidad desde</label>
                    <input type="date" class="form-control" id="fecha_caducidad_desde" name="fecha_caducidad_desde" value="<?= $fecha_caducidad_desde ?>">
                </div>
                
                <!-- Fecha caducidad hasta -->
                <div class="col-md-4">
                    <label for="fecha_caducidad_hasta" class="form-label">Fecha caducidad hasta</label>
                    <input type="date" class="form-control" id="fecha_caducidad_hasta" name="fecha_caducidad_hasta" value="<?= $fecha_caducidad_hasta ?>">
                </div>
                
                <!-- Botones de acción del filtro -->
                <div class="col-12 d-flex justify-content-end mt-4">
                    <a href="list.php" class="btn btn-secondary me-2">
                        <span class="material-symbols-rounded me-1">refresh</span> Limpiar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">filter_alt</span> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Listado de bonos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bonos</h5>
                <span class="badge bg-primary"><?= $totalRecords ?> registros</span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($bonos)): ?>
                <div class="alert alert-info m-4">
                    No se encontraron bonos con los criterios seleccionados.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="<?= generateUrl(['sort' => 'id', 'order' => ($sort === 'id' && $order === 'ASC') ? 'DESC' : 'ASC']) ?>" class="text-decoration-none text-dark">
                                        ID
                                        <?php if ($sort === 'id'): ?>
                                            <span class="material-symbols-rounded align-middle small">
                                                <?= $order === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= generateUrl(['sort' => 'paciente_nombre', 'order' => ($sort === 'paciente_nombre' && $order === 'ASC') ? 'DESC' : 'ASC']) ?>" class="text-decoration-none text-dark">
                                        Paciente
                                        <?php if ($sort === 'paciente_nombre'): ?>
                                            <span class="material-symbols-rounded align-middle small">
                                                <?= $order === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Sesiones</th>
                                <th>
                                    <a href="<?= generateUrl(['sort' => 'fecha_compra', 'order' => ($sort === 'fecha_compra' && $order === 'ASC') ? 'DESC' : 'ASC']) ?>" class="text-decoration-none text-dark">
                                        Fecha Compra
                                        <?php if ($sort === 'fecha_compra'): ?>
                                            <span class="material-symbols-rounded align-middle small">
                                                <?= $order === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= generateUrl(['sort' => 'fecha_caducidad', 'order' => ($sort === 'fecha_caducidad' && $order === 'ASC') ? 'DESC' : 'ASC']) ?>" class="text-decoration-none text-dark">
                                        Caducidad
                                        <?php if ($sort === 'fecha_caducidad'): ?>
                                            <span class="material-symbols-rounded align-middle small">
                                                <?= $order === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= generateUrl(['sort' => 'monto', 'order' => ($sort === 'monto' && $order === 'ASC') ? 'DESC' : 'ASC']) ?>" class="text-decoration-none text-dark">
                                        Monto
                                        <?php if ($sort === 'monto'): ?>
                                            <span class="material-symbols-rounded align-middle small">
                                                <?= $order === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= generateUrl(['sort' => 'estado', 'order' => ($sort === 'estado' && $order === 'ASC') ? 'DESC' : 'ASC']) ?>" class="text-decoration-none text-dark">
                                        Estado
                                        <?php if ($sort === 'estado'): ?>
                                            <span class="material-symbols-rounded align-middle small">
                                                <?= $order === 'ASC' ? 'arrow_upward' : 'arrow_downward' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bonos as $bono): ?>
                                <tr>
                                    <td><strong>#<?= $bono['id'] ?></strong></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $bono['paciente_id'] ?>" class="text-primary">
                                            <?= htmlspecialchars($bono['paciente_apellidos'] . ', ' . $bono['paciente_nombre']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= $bono['num_sesiones_disponibles'] ?> / <?= $bono['num_sesiones_total'] ?>
                                    </td>
                                    <td><?= formatDateToView($bono['fecha_compra']) ?></td>
                                    <td>
                                        <?php if (!empty($bono['fecha_caducidad'])): ?>
                                            <?= formatDateToView($bono['fecha_caducidad']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($bono['monto'], 2, ',', '.') ?> €</td>
                                    <td>
                                        <span class="badge <?= getEstadoBadgeClass($bono['estado']) ?>">
                                            <?= ucfirst($bono['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                            <a href="view.php?id=<?= $bono['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </a>
                                            <a href="edit.php?id=<?= $bono['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                                <span class="material-symbols-rounded">edit</span>
                                            </a>
                                            <?php if ($bono['estado'] === 'activo'): ?>
                                                <a href="<?= BASE_URL ?>/modules/citas/create.php?paciente_id=<?= $bono['paciente_id'] ?>&bono_id=<?= $bono['id'] ?>" class="btn btn-complete" data-bs-toggle="tooltip" title="Crear cita con este bono">
                                                    <span class="material-symbols-rounded">event_add</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-3">
                <nav aria-label="Paginación de bonos">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= generateUrl(['page' => 1]) ?>" aria-label="Primera">
                                <span class="material-symbols-rounded small">first_page</span>
                            </a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= generateUrl(['page' => $page - 1]) ?>" aria-label="Anterior">
                                <span class="material-symbols-rounded small">chevron_left</span>
                            </a>
                        </li>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        
                        for ($i = $start; $i <= $end; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                                  <a class="page-link" href="' . generateUrl(['page' => $i]) . '">' . $i . '</a>
                                  </li>';
                        }
                        
                        if ($end < $totalPages) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= generateUrl(['page' => $page + 1]) ?>" aria-label="Siguiente">
                                <span class="material-symbols-rounded small">chevron_right</span>
                            </a>
                        </li>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= generateUrl(['page' => $totalPages]) ?>" aria-label="Última">
                                <span class="material-symbols-rounded small">last_page</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Inicializar select2 para el selector de pacientes
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('#paciente_id').select2({
                placeholder: 'Seleccione un paciente',
                allowClear: true,
                width: '100%'
            });
        }
    });
</script>

<?php
// Finalizar la captura y renderizar la página
endPageContent('Gestión de Bonos', [
    'breadcrumb' => [
        'Inicio' => BASE_URL,
        'Bonos' => '#'
    ]
]);
?> 