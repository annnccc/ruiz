<?php
/**
 * Página de resultados de búsqueda global
 * Muestra resultados de búsqueda en todas las entidades del sistema
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación para acceder a esta página
requiereLogin();

// Verificar si hay una consulta de búsqueda
$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Inicializar arrays de resultados
$pacientes = [];
$citas = [];
$servicios = [];
$total_results = 0;

// Realizar búsqueda si hay una consulta
if (!empty($query) && strlen($query) >= 2) {
    try {
        $db = getDB();
        
        // Buscar pacientes
        $stmt = $db->prepare("
            SELECT id, nombre, apellidos, dni, telefono, email, fecha_nacimiento
            FROM pacientes
            WHERE 
                nombre LIKE :query1 OR 
                apellidos LIKE :query2 OR 
                dni LIKE :query3 OR 
                telefono LIKE :query4 OR 
                email LIKE :query5
            ORDER BY apellidos, nombre ASC
            LIMIT 20
        ");
        $stmt->bindValue(':query1', "%$query%");
        $stmt->bindValue(':query2', "%$query%");
        $stmt->bindValue(':query3', "%$query%");
        $stmt->bindValue(':query4', "%$query%");
        $stmt->bindValue(':query5', "%$query%");
        $stmt->execute();
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar citas
        $stmt = $db->prepare("
            SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.estado, c.notas, 
                  p.id as paciente_id, p.nombre, p.apellidos
            FROM citas c
            JOIN pacientes p ON c.paciente_id = p.id
            WHERE 
                p.nombre LIKE :query1 OR 
                p.apellidos LIKE :query2 OR
                c.notas LIKE :query3 OR
                c.estado LIKE :query4
            ORDER BY c.fecha DESC, c.hora_inicio ASC
            LIMIT 20
        ");
        $stmt->bindValue(':query1', "%$query%");
        $stmt->bindValue(':query2', "%$query%");
        $stmt->bindValue(':query3', "%$query%");
        $stmt->bindValue(':query4', "%$query%");
        $stmt->execute();
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar servicios
        $stmt = $db->prepare("
            SELECT id, nombre, descripcion, precio
            FROM servicios
            WHERE 
                nombre LIKE :query1 OR 
                descripcion LIKE :query2
            ORDER BY nombre ASC
            LIMIT 20
        ");
        $stmt->bindValue(':query1', "%$query%");
        $stmt->bindValue(':query2', "%$query%");
        $stmt->execute();
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular total de resultados
        $total_results = count($pacientes) + count($citas) + count($servicios);
        
    } catch (PDOException $e) {
        setAlert('danger', 'Error en la búsqueda: ' . $e->getMessage());
    }
}

// Título de la página y breadcrumbs
$titulo_pagina = "Resultados de búsqueda: " . htmlspecialchars($query);
$breadcrumbs = [
    'Búsqueda' => '#',
    $query => '#'
];

// Función para resaltar términos de búsqueda en un texto
function highlightSearchTerms($text, $search) {
    if (empty($search)) return $text;
    
    // Escapar caracteres especiales de expresiones regulares
    $search = preg_quote($search, '/');
    
    // Resaltar términos con clase CSS
    return preg_replace('/(' . $search . ')/i', '<span class="search-result-highlight">$1</span>', $text);
}

// Iniciar captura del contenido
startPageContent();
?>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">search</span>Resultados de búsqueda
        </h1>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Formulario de búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/modules/busqueda/results.php" method="GET" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><span class="material-symbols-rounded">search</span></span>
                        <input type="text" class="form-control" id="searchInput" name="q" 
                               placeholder="Buscar en pacientes, citas y servicios..." 
                               value="<?= htmlspecialchars($query) ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (!empty($query)): ?>
        <!-- Resumen de resultados -->
        <div class="mb-4">
            <?php if ($total_results > 0): ?>
                <p class="text-muted">Se encontraron <?= $total_results ?> resultados para "<strong><?= htmlspecialchars($query) ?></strong>"</p>
            <?php else: ?>
                <div class="alert alert-info">
                    No se encontraron resultados para "<strong><?= htmlspecialchars($query) ?></strong>".
                    <ul class="mt-2 mb-0">
                        <li>Verifica que todas las palabras estén escritas correctamente.</li>
                        <li>Prueba con palabras clave diferentes.</li>
                        <li>Utiliza términos más generales.</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Resultados -->
        <div class="row g-4">
            <?php if (!empty($pacientes)): ?>
                <!-- Resultados de pacientes -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <span class="material-symbols-rounded me-2 text-primary">person</span>
                                    Pacientes (<?= count($pacientes) ?>)
                                </h5>
                                <a href="<?= BASE_URL ?>/modules/pacientes/list.php?search=<?= urlencode($query) ?>" class="btn btn-sm btn-outline-primary">
                                    Ver todos
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>DNI</th>
                                            <th>Contacto</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pacientes as $paciente): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle avatar-sm me-2">
                                                            <?= strtoupper(substr($paciente['nombre'], 0, 1) . substr($paciente['apellidos'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium">
                                                                <?= highlightSearchTerms(htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']), $query) ?>
                                                            </div>
                                                            <?php if (!empty($paciente['fecha_nacimiento'])): ?>
                                                                <small class="text-muted"><?= formatDateToView($paciente['fecha_nacimiento']) ?> (<?= calcularEdad($paciente['fecha_nacimiento']) ?> años)</small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= highlightSearchTerms(htmlspecialchars($paciente['dni']), $query) ?></td>
                                                <td>
                                                    <div>
                                                        <span class="d-block"><?= highlightSearchTerms(htmlspecialchars($paciente['telefono']), $query) ?></span>
                                                        <small class="text-muted"><?= highlightSearchTerms(htmlspecialchars($paciente['email']), $query) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $paciente['id'] ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Ver detalle">
                                                            <span class="material-symbols-rounded small">visibility</span>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/modules/pacientes/edit.php?id=<?= $paciente['id'] ?>" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Editar">
                                                            <span class="material-symbols-rounded small">edit</span>
                                                        </a>
                                                    </div>
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
            
            <?php if (!empty($citas)): ?>
                <!-- Resultados de citas -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <span class="material-symbols-rounded me-2 text-primary">calendar_month</span>
                                    Citas (<?= count($citas) ?>)
                                </h5>
                                <a href="<?= BASE_URL ?>/modules/citas/list.php?search=<?= urlencode($query) ?>" class="btn btn-sm btn-outline-primary">
                                    Ver todas
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha y hora</th>
                                            <th>Paciente</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($citas as $cita): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium"><?= formatDateToView($cita['fecha']) ?></div>
                                                    <small class="text-muted"><?= formatTime($cita['hora_inicio']) ?> - <?= formatTime($cita['hora_fin']) ?></small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle avatar-sm me-2">
                                                            <?= strtoupper(substr($cita['nombre'], 0, 1) . substr($cita['apellidos'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $cita['paciente_id'] ?>" class="text-decoration-none">
                                                                <?= highlightSearchTerms(htmlspecialchars($cita['apellidos'] . ', ' . $cita['nombre']), $query) ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = '';
                                                    switch ($cita['estado']) {
                                                        case 'pendiente':
                                                            $badgeClass = 'badge-pendiente';
                                                            break;
                                                        case 'completada':
                                                            $badgeClass = 'badge-completada';
                                                            break;
                                                        case 'cancelada':
                                                            $badgeClass = 'badge-cancelada';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst(highlightSearchTerms(htmlspecialchars($cita['estado']), $query)) ?></span>
                                                    
                                                    <?php if (!empty($cita['notas'])): ?>
                                                        <div class="mt-1 small text-muted">
                                                            <?= highlightSearchTerms(htmlspecialchars(substr($cita['notas'], 0, 50) . (strlen($cita['notas']) > 50 ? '...' : '')), $query) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Ver detalle">
                                                            <span class="material-symbols-rounded small">visibility</span>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/modules/citas/edit.php?id=<?= $cita['id'] ?>" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Editar">
                                                            <span class="material-symbols-rounded small">edit</span>
                                                        </a>
                                                    </div>
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
            
            <?php if (!empty($servicios)): ?>
                <!-- Resultados de servicios -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <span class="material-symbols-rounded me-2 text-primary">medical_services</span>
                                    Servicios (<?= count($servicios) ?>)
                                </h5>
                                <a href="<?= BASE_URL ?>/modules/servicios/list.php?search=<?= urlencode($query) ?>" class="btn btn-sm btn-outline-primary">
                                    Ver todos
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Precio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($servicios as $servicio): ?>
                                            <tr>
                                                <td class="fw-medium"><?= highlightSearchTerms(htmlspecialchars($servicio['nombre']), $query) ?></td>
                                                <td><?= highlightSearchTerms(htmlspecialchars(substr($servicio['descripcion'], 0, 100) . (strlen($servicio['descripcion']) > 100 ? '...' : '')), $query) ?></td>
                                                <td>
                                                    <div class="fw-medium"><?= formatCurrency($servicio['precio']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <a href="<?= BASE_URL ?>/modules/servicios/view.php?id=<?= $servicio['id'] ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Ver detalle">
                                                            <span class="material-symbols-rounded small">visibility</span>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/modules/servicios/edit.php?id=<?= $servicio['id'] ?>" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Editar">
                                                            <span class="material-symbols-rounded small">edit</span>
                                                        </a>
                                                    </div>
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
            
            <?php if ($total_results === 0 && !empty($query)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <span class="material-symbols-rounded display-1 text-muted">search_off</span>
                        </div>
                        <h4>No se encontraron resultados</h4>
                        <p class="text-muted">Intenta con otros términos o verifica la ortografía.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Finalizar la captura del contenido y renderizar la página
endPageContent();
?> 