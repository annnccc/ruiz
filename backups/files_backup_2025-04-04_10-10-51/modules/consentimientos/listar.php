<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once 'funciones.php';

// Requiere autenticación
requiereLogin();

// Verifica si existe la tabla de consentimientos
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'consentimientos_modelos'");
    if ($stmt->rowCount() === 0) {
        redirect(BASE_URL . '/modules/consentimientos/install.php');
    }
} catch (PDOException $e) {
    redirect(BASE_URL . '/modules/consentimientos/install.php');
}

// Filtros de búsqueda
$filter_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filter_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$filter_paciente = isset($_GET['paciente']) ? $_GET['paciente'] : '';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$resultados_por_pagina = 20;

// Obtener total de consentimientos y paginar resultados
try {
    $db = getDB();
    
    // Preparar consulta base
    $sql_count = "
        SELECT COUNT(*) FROM consentimientos_enviados ce
        JOIN pacientes p ON ce.paciente_id = p.id
        JOIN consentimientos_modelos cm ON ce.modelo_id = cm.id
        WHERE 1=1
    ";
    
    $sql = "
        SELECT ce.*, cm.nombre as modelo_nombre, 
               p.nombre as paciente_nombre, p.apellidos as paciente_apellidos,
               u.nombre as usuario_nombre, u.apellidos as usuario_apellidos
        FROM consentimientos_enviados ce
        JOIN pacientes p ON ce.paciente_id = p.id
        JOIN consentimientos_modelos cm ON ce.modelo_id = cm.id
        JOIN usuarios u ON ce.enviado_por = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filter_estado)) {
        $sql .= " AND ce.estado = ?";
        $sql_count .= " AND ce.estado = ?";
        $params[] = $filter_estado;
    }
    
    if (!empty($filter_fecha)) {
        $sql .= " AND DATE(ce.fecha_envio) = ?";
        $sql_count .= " AND DATE(ce.fecha_envio) = ?";
        $params[] = $filter_fecha;
    }
    
    if (!empty($filter_paciente)) {
        $sql .= " AND (p.nombre LIKE ? OR p.apellidos LIKE ?)";
        $sql_count .= " AND (p.nombre LIKE ? OR p.apellidos LIKE ?)";
        $params[] = "%$filter_paciente%";
        $params[] = "%$filter_paciente%";
    }
    
    // Ordenar resultados
    $sql .= " ORDER BY ce.fecha_envio DESC";
    
    // Contar total para paginación
    $stmt = $db->prepare($sql_count);
    $stmt->execute($params);
    $total_resultados = $stmt->fetchColumn();
    $total_paginas = ceil($total_resultados / $resultados_por_pagina);
    
    // Ajustar página actual
    if ($pagina_actual < 1) $pagina_actual = 1;
    if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
    
    // Consulta paginada
    $offset = ($pagina_actual - 1) * $resultados_por_pagina;
    $sql .= " LIMIT $resultados_por_pagina OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $consentimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los consentimientos: ' . $e->getMessage());
    $consentimientos = [];
    $total_resultados = 0;
    $total_paginas = 0;
}

// Obtener estadísticas de consentimientos
try {
    $db = getDB();
    
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'firmado' THEN 1 ELSE 0 END) as firmados,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'caducado' THEN 1 ELSE 0 END) as caducados
        FROM consentimientos_enviados
    ";
    
    $stmt = $db->query($sql);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $estadisticas = [
        'total' => 0,
        'firmados' => 0,
        'pendientes' => 0,
        'caducados' => 0
    ];
}

// Preparar datos para la página
$title = "Gestión de Consentimientos Informados";
$breadcrumbs = [
    'Consentimientos' => '#'
];

// Iniciar el buffer de salida
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">description</span>Consentimientos Informados
        </h1>
        <a href="<?= BASE_URL ?>/modules/consentimientos/modelos.php" class="btn btn-primary">
            <span class="material-symbols-rounded me-2">file_copy</span>Gestionar Modelos
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Resumen estadístico -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded p-3 me-3">
                            <span class="material-symbols-rounded text-primary">description</span>
                        </div>
                        <div>
                            <h6 class="card-title mb-0">Total</h6>
                            <h3 class="mt-2 mb-0"><?= $estadisticas['total'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 rounded p-3 me-3">
                            <span class="material-symbols-rounded text-success">check_circle</span>
                        </div>
                        <div>
                            <h6 class="card-title mb-0">Firmados</h6>
                            <h3 class="mt-2 mb-0"><?= $estadisticas['firmados'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded p-3 me-3">
                            <span class="material-symbols-rounded text-warning">pending</span>
                        </div>
                        <div>
                            <h6 class="card-title mb-0">Pendientes</h6>
                            <h3 class="mt-2 mb-0"><?= $estadisticas['pendientes'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-secondary bg-opacity-10 rounded p-3 me-3">
                            <span class="material-symbols-rounded text-secondary">event_busy</span>
                        </div>
                        <div>
                            <h6 class="card-title mb-0">Caducados</h6>
                            <h3 class="mt-2 mb-0"><?= $estadisticas['caducados'] ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros y búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">filter_alt</span>Filtros
            </h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="paciente" class="form-label">Nombre del paciente</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <span class="material-symbols-rounded icon-sm">person</span>
                        </span>
                        <input type="text" class="form-control" id="paciente" name="paciente" 
                               value="<?= htmlspecialchars($filter_paciente) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="firmado" <?= $filter_estado === 'firmado' ? 'selected' : '' ?>>Firmados</option>
                        <option value="pendiente" <?= $filter_estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="caducado" <?= $filter_estado === 'caducado' ? 'selected' : '' ?>>Caducados</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha de envío</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" 
                           value="<?= htmlspecialchars($filter_fecha) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">search</span>Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de consentimientos -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">list</span>Consentimientos Enviados
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($consentimientos)): ?>
            <div class="alert alert-info">
                <div class="d-flex">
                    <div class="me-3">
                        <span class="material-symbols-rounded">info</span>
                    </div>
                    <div>
                        <p class="mb-0">No se encontraron consentimientos que coincidan con los criterios de búsqueda.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Modelo</th>
                            <th>Enviado por</th>
                            <th>Fecha de envío</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consentimientos as $consentimiento): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/pacientes/editar.php?id=<?= $consentimiento['paciente_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($consentimiento['paciente_nombre'] . ' ' . $consentimiento['paciente_apellidos']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($consentimiento['modelo_nombre']) ?></td>
                            <td><?= htmlspecialchars($consentimiento['usuario_nombre'] . ' ' . $consentimiento['usuario_apellidos']) ?></td>
                            <td><?= formatDateToView($consentimiento['fecha_envio']) ?></td>
                            <td>
                                <?php if ($consentimiento['estado'] === 'firmado'): ?>
                                <span class="badge bg-success">Firmado</span>
                                <?php elseif ($consentimiento['estado'] === 'pendiente'): ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Caducado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($consentimiento['estado'] === 'pendiente'): ?>
                                <a href="<?= BASE_URL ?>/publico/consentimiento.php?token=<?= $consentimiento['token'] ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <span class="material-symbols-rounded icon-sm">visibility</span>
                                </a>
                                <a href="reenviar.php?id=<?= $consentimiento['id'] ?>" 
                                   class="btn btn-sm btn-outline-info">
                                    <span class="material-symbols-rounded icon-sm">send</span>
                                </a>
                                <?php elseif ($consentimiento['estado'] === 'firmado'): ?>
                                <a href="ver_firma.php?id=<?= $consentimiento['id'] ?>" 
                                   class="btn btn-sm btn-outline-success">
                                    <span class="material-symbols-rounded icon-sm">check</span>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $pagina_actual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&estado=<?= urlencode($filter_estado) ?>&fecha=<?= urlencode($filter_fecha) ?>&paciente=<?= urlencode($filter_paciente) ?>">
                            <span class="material-symbols-rounded icon-sm">navigate_before</span>
                        </a>
                    </li>
                    
                    <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                    <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>&estado=<?= urlencode($filter_estado) ?>&fecha=<?= urlencode($filter_fecha) ?>&paciente=<?= urlencode($filter_paciente) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&estado=<?= urlencode($filter_estado) ?>&fecha=<?= urlencode($filter_fecha) ?>&paciente=<?= urlencode($filter_paciente) ?>">
                            <span class="material-symbols-rounded icon-sm">navigate_next</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Finalizar el buffer de salida
endPageContent();
?> 