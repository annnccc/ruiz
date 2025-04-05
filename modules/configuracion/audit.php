<?php
/**
 * Sistema de Auditoría de Accesos
 * 
 * Este módulo permite la visualización y gestión de los registros de auditoría,
 * mostrando quién accede a qué datos en el sistema.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Comprobar permisos de acceso (solo administradores)
if (!isLoggedIn() || !esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta sección.');
    header('Location: ' . BASE_URL);
    exit;
}

// Título de la página
$titulo = 'Auditoría de Accesos';
$breadcrumb = [
    ['nombre' => 'Inicio', 'enlace' => BASE_URL],
    ['nombre' => 'Configuración', 'enlace' => BASE_URL . '/modules/configuracion'],
    ['nombre' => $titulo, 'enlace' => '']
];

// Inicializar filtros
$filtros = [];
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 50;

// Procesar filtros de búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar'])) {
    if (!empty($_GET['usuario_id'])) {
        $filtros['usuario_id'] = (int)$_GET['usuario_id'];
    }
    
    if (!empty($_GET['accion'])) {
        $filtros['accion'] = $_GET['accion'];
    }
    
    if (!empty($_GET['entidad'])) {
        $filtros['entidad'] = $_GET['entidad'];
    }
    
    if (!empty($_GET['entidad_id'])) {
        $filtros['entidad_id'] = (int)$_GET['entidad_id'];
    }
    
    if (!empty($_GET['fecha_desde'])) {
        $filtros['fecha_desde'] = $_GET['fecha_desde'];
    }
    
    if (!empty($_GET['fecha_hasta'])) {
        $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
    }
    
    if (!empty($_GET['ip_address'])) {
        $filtros['ip_address'] = $_GET['ip_address'];
    }
}

// Obtener registros de auditoría
$auditManager = getAuditManager();
$resultados = $auditManager->getAuditLogs($filtros, $pagina, $por_pagina);

// Obtener listas para filtros
try {
    $db = getDB();
    
    // Obtener usuarios
    $stmt = $db->query("SELECT id, nombre FROM usuarios ORDER BY nombre");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener entidades (tablas) únicas
    $stmt = $db->query("SELECT DISTINCT entidad FROM audit_logs ORDER BY entidad");
    $entidades = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener acciones únicas
    $stmt = $db->query("SELECT DISTINCT accion FROM audit_logs ORDER BY accion");
    $acciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener datos para filtros: ' . $e->getMessage());
    $usuarios = [];
    $entidades = [];
    $acciones = [];
}

// Incluir header
include '../../includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">security</span><?= $titulo ?>
        </h1>
    </div>
    
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Filtros de búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">Filtros de búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="usuario_id" class="form-label">Usuario</label>
                    <select class="form-select" id="usuario_id" name="usuario_id">
                        <option value="">Todos los usuarios</option>
                        <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario['id'] ?>" <?= (isset($filtros['usuario_id']) && $filtros['usuario_id'] == $usuario['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usuario['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="accion" class="form-label">Acción</label>
                    <select class="form-select" id="accion" name="accion">
                        <option value="">Todas las acciones</option>
                        <?php foreach ($acciones as $accion): ?>
                        <option value="<?= $accion ?>" <?= (isset($filtros['accion']) && $filtros['accion'] == $accion) ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($accion)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="entidad" class="form-label">Entidad</label>
                    <select class="form-select" id="entidad" name="entidad">
                        <option value="">Todas las entidades</option>
                        <?php foreach ($entidades as $entidad): ?>
                        <option value="<?= $entidad ?>" <?= (isset($filtros['entidad']) && $filtros['entidad'] == $entidad) ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($entidad)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="entidad_id" class="form-label">ID de Entidad</label>
                    <input type="number" class="form-control" id="entidad_id" name="entidad_id" value="<?= isset($filtros['entidad_id']) ? $filtros['entidad_id'] : '' ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="fecha_desde" class="form-label">Fecha desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= isset($filtros['fecha_desde']) ? $filtros['fecha_desde'] : '' ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= isset($filtros['fecha_hasta']) ? $filtros['fecha_hasta'] : '' ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="ip_address" class="form-label">Dirección IP</label>
                    <input type="text" class="form-control" id="ip_address" name="ip_address" value="<?= isset($filtros['ip_address']) ? $filtros['ip_address'] : '' ?>">
                </div>
                
                <div class="col-12 text-end">
                    <a href="<?= BASE_URL ?>/modules/configuracion/audit.php" class="btn btn-outline-secondary me-2">
                        <span class="material-symbols-rounded me-2">refresh</span>Limpiar
                    </a>
                    <button type="submit" name="buscar" value="1" class="btn btn-primary">
                        <span class="material-symbols-rounded me-2">search</span>Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resultados de auditoría -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Registros de Auditoría</h5>
                <?php if (!empty($resultados['registros'])): ?>
                <span class="badge bg-primary"><?= $resultados['total_registros'] ?> registros encontrados</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($resultados['registros'])): ?>
            <div class="text-center py-5">
                <span class="material-symbols-rounded display-4 text-muted">security</span>
                <p class="mt-3">No se encontraron registros de auditoría con los filtros seleccionados.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>ID Entidad</th>
                            <th>Dirección IP</th>
                            <th>Fecha y Hora</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados['registros'] as $registro): ?>
                        <tr>
                            <td><?= $registro['id'] ?></td>
                            <td>
                                <?php if ($registro['usuario_id'] > 0): ?>
                                <a href="<?= BASE_URL ?>/modules/configuracion/usuarios_edit.php?id=<?= $registro['usuario_id'] ?>">
                                    <?= htmlspecialchars($registro['nombre_usuario']) ?>
                                </a>
                                <?php else: ?>
                                <?= htmlspecialchars($registro['nombre_usuario']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= getAccionBadgeClass($registro['accion']) ?>">
                                    <?= ucfirst(htmlspecialchars($registro['accion'])) ?>
                                </span>
                            </td>
                            <td><?= ucfirst(htmlspecialchars($registro['entidad'])) ?></td>
                            <td>
                                <?php if (canLinkEntity($registro['entidad'])): ?>
                                <a href="<?= getEntityUrl($registro['entidad'], $registro['entidad_id']) ?>">
                                    <?= $registro['entidad_id'] ?>
                                </a>
                                <?php else: ?>
                                <?= $registro['entidad_id'] ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($registro['ip_address']) ?></td>
                            <td><?= date('d/m/Y H:i:s', strtotime($registro['fecha_hora'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/configuracion/audit_detail.php?id=<?= $registro['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <span class="material-symbols-rounded">visibility</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($resultados['total_paginas'] > 1): ?>
            <div class="d-flex justify-content-center py-3">
                <nav aria-label="Paginación de registros">
                    <ul class="pagination">
                        <!-- Botón Anterior -->
                        <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($pagina - 1) ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- Páginas -->
                        <?php for ($i = max(1, $pagina - 2); $i <= min($resultados['total_paginas'], $pagina + 2); $i++): ?>
                        <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <!-- Botón Siguiente -->
                        <li class="page-item <?= ($pagina >= $resultados['total_paginas']) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($pagina + 1) ?>" aria-label="Siguiente">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
/**
 * Construye la URL para la paginación manteniendo los filtros
 */
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['pagina'] = $page;
    return '?' . http_build_query($params);
}

/**
 * Obtiene la clase de badge según el tipo de acción
 */
function getAccionBadgeClass($accion) {
    $clases = [
        'ver' => 'info',
        'crear' => 'success',
        'editar' => 'warning',
        'eliminar' => 'danger',
        'descargar' => 'primary',
        'login' => 'secondary',
        'logout' => 'secondary'
    ];
    
    return $clases[$accion] ?? 'secondary';
}

/**
 * Verifica si se puede enlazar a la entidad
 */
function canLinkEntity($entidad) {
    $entidades_enlazables = ['pacientes', 'citas', 'usuarios', 'facturas'];
    return in_array($entidad, $entidades_enlazables);
}

/**
 * Obtiene la URL de la entidad
 */
function getEntityUrl($entidad, $id) {
    $urls = [
        'pacientes' => BASE_URL . '/modules/pacientes/view.php?id=',
        'citas' => BASE_URL . '/modules/citas/view.php?id=',
        'usuarios' => BASE_URL . '/modules/configuracion/usuarios_edit.php?id=',
        'facturas' => BASE_URL . '/modules/facturacion/view.php?id='
    ];
    
    return ($urls[$entidad] ?? '#') . $id;
}

include '../../includes/layout_footer.php'; 
?> 