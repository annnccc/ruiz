<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación para acceder a esta página
requiereLogin();

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ordenación
$sort_field = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'apellidos';
$sort_direction = isset($_GET['direction']) && strtolower($_GET['direction']) === 'desc' ? 'DESC' : 'ASC';

// Validar campos de ordenación permitidos
$allowed_sort_fields = ['nombre', 'apellidos', 'dni', 'telefono', 'email', 'fecha_nacimiento', 'num_citas'];
if (!in_array($sort_field, $allowed_sort_fields)) {
    $sort_field = 'apellidos';
}

// Búsqueda
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = "WHERE nombre LIKE :search_nombre OR apellidos LIKE :search_apellidos OR dni LIKE :search_dni OR telefono LIKE :search_telefono OR email LIKE :search_email";
    $params[':search_nombre'] = "%$search%";
    $params[':search_apellidos'] = "%$search%";
    $params[':search_dni'] = "%$search%";
    $params[':search_telefono'] = "%$search%";
    $params[':search_email'] = "%$search%";
}

try {
    // Configurar opciones de búsqueda
    $options = [
        'sort_field' => $sort_field,
        'sort_direction' => $sort_direction,
        'offset' => $offset,
        'limit' => $records_per_page,
        'with_citas_count' => true
    ];
    
    // Obtener total de registros
    $total_results = searchPacientes($search, [], true);
    $total_pages = ceil($total_results / $records_per_page);
    
    // Obtener pacientes
    $pacientes = searchPacientes($search, $options);
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los pacientes: ' . $e->getMessage());
    $pacientes = [];
    $total_pages = 0;
}

// Título y breadcrumbs para la página
$titulo_pagina = "Listado de Pacientes";
$title = $titulo_pagina;
$breadcrumbs = [
    'Pacientes' => '#'
];

// Función para generar enlace de ordenación
function getSortLink($field, $current_sort_field, $current_sort_direction) {
    $direction = ($field === $current_sort_field && $current_sort_direction === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    
    if ($field === $current_sort_field) {
        $icon = $current_sort_direction === 'ASC' 
            ? '<span class="material-symbols-rounded sort-icon">arrow_upward</span>' 
            : '<span class="material-symbols-rounded sort-icon">arrow_downward</span>';
    }
    
    $params = $_GET;
    $params['sort'] = $field;
    $params['direction'] = $direction;
    
    $url = '?' . http_build_query($params);
    return "<a href=\"$url\" class=\"sort-link\">$icon</a>";
}

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">people</span><?= $titulo_pagina ?>
        </h1>
        <a href="<?= BASE_URL ?>/modules/pacientes/create.php" class="btn btn-primary">
            <span class="material-symbols-rounded me-2">person_add</span>Nuevo Paciente
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="searchForm" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><span class="material-symbols-rounded">search</span></span>
                        <input type="text" class="form-control" id="searchInput" name="search" placeholder="Buscar por nombre, apellidos, DNI, teléfono o email" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de pacientes -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col">Email</th>
                            <th scope="col">Historial</th>
                            <th scope="col" class="text-center">Consentimiento</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="pacientesTableBody">
                        <?php if (empty($pacientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No se encontraron pacientes</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($pacientes as $paciente): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="view.php?id=<?= $paciente['id'] ?>" class="text-primary">
                                                <?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']) ?>
                                            </a>
                                        </h6>
                                        <?php if (!empty($paciente['dni'])): ?>
                                        <span class="text-muted small"><?= htmlspecialchars($paciente['dni']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($paciente['telefono'])): ?>
                                <span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">phone</span>
                                <?= htmlspecialchars($paciente['telefono']) ?>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($paciente['email'])): ?>
                                <span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">email</span>
                                <?= htmlspecialchars($paciente['email']) ?>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($paciente['num_citas']) && $paciente['num_citas'] > 0): ?>
                                <div class="mb-1 small">
                                    <span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">event</span>
                                    <?= $paciente['num_citas'] ?> cita<?= $paciente['num_citas'] > 1 ? 's' : '' ?>
                                </div>
                                <?php else: ?>
                                <div class="mb-1 small text-muted">Sin citas</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (isset($paciente['consentimiento_firmado']) && $paciente['consentimiento_firmado'] == 1): ?>
                                <span class="material-symbols-rounded text-success" style="font-size: 24px;">check_circle</span>
                                <?php else: ?>
                                <span class="material-symbols-rounded text-danger" style="font-size: 24px;">cancel</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="table-actions">
                                    <a href="view.php?id=<?= $paciente['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                        <?= heroicon_outline('eye', 'heroicon-sm') ?>
                                    </a>
                                    <a href="edit.php?id=<?= $paciente['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                        <?= heroicon_outline('pencil', 'heroicon-sm') ?>
                                    </a>
                                    <a href="delete.php?id=<?= $paciente['id'] ?>" class="btn btn-delete" data-bs-toggle="tooltip" title="Eliminar">
                                        <?= heroicon_outline('trash', 'heroicon-sm') ?>
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
    <div id="paginationContainer">
        <?php if ($total_pages > 1): ?>
        <div class="mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $page - 1 ?>" tabindex="-1" aria-disabled="<?= ($page <= 1) ? 'true' : 'false' ?>">Anterior</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $page + 1 ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    const pacientesTableBody = document.getElementById('pacientesTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    let currentPage = <?= $page ?>;
    
    // Verificar si hay soporte para fetch en el navegador
    if (!window.fetch) {
        return; // Si no hay soporte para fetch, usamos el método tradicional
    }

    // Función para realizar la búsqueda
    function performSearch(page = 1) {
        const searchTerm = searchInput.value;
        const sortField = '<?= $sort_field ?>';
        const sortDirection = '<?= $sort_direction ?>';
        
        // Mostrar indicador de carga
        pacientesTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';
        
        fetch(`search.php?search=${encodeURIComponent(searchTerm)}&page=${page}&sort=${sortField}&direction=${sortDirection}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Error ${response.status}: ${text || response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    pacientesTableBody.innerHTML = data.html;
                    paginationContainer.innerHTML = data.pagination;
                    
                    // Reinicializar tooltips
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                    
                    // Reinicializar botones de eliminación
                    initializeDeleteButtons();
                } else {
                    pacientesTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Error al cargar pacientes: ' + data.error + '</td></tr>';
                }
            })
            .catch(error => {
                pacientesTableBody.innerHTML = `                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="alert alert-danger">
                                <strong>Error en la búsqueda:</strong> ${error.message}
                            </div>
                        </td>
                    </tr>`;
            });
    }

    // Evento de búsqueda en vivo
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            performSearch(currentPage);
        }, 500);
    });

    // Delegación de eventos para paginación
    document.addEventListener('click', function(e) {
        const target = e.target;
        
        // Verificar si es un enlace de paginación
        if (target.tagName === 'A' && target.hasAttribute('data-page')) {
            e.preventDefault();
            const page = parseInt(target.getAttribute('data-page'));
            
            // Si estamos usando fetch para búsqueda
            if (window.fetch) {
                performSearch(page);
            } else {
                // Enfoque tradicional
                const form = document.getElementById('searchForm');
                const pageInput = document.createElement('input');
                pageInput.type = 'hidden';
                pageInput.name = 'page';
                pageInput.value = page;
                
                // Incluir parámetros de ordenación
                const sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                sortInput.value = '<?= $sort_field ?>';
                
                const directionInput = document.createElement('input');
                directionInput.type = 'hidden';
                directionInput.name = 'direction';
                directionInput.value = '<?= $sort_direction ?>';
                
                form.appendChild(pageInput);
                form.appendChild(sortInput);
                form.appendChild(directionInput);
                form.submit();
            }
        }
    });

    // Evento del formulario
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        performSearch(currentPage);
    });

    // Función para inicializar botones de eliminación
    function initializeDeleteButtons() {
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('¿Está seguro de que desea eliminar este paciente?')) {
                    window.location.href = this.href;
                }
            });
        });
    }

    // Inicializar botones de eliminación al cargar la página
    initializeDeleteButtons();
    
    // Realizar búsqueda inicial si hay un término de búsqueda
    if (searchInput.value.trim()) {
        performSearch(currentPage);
    }
});
</script>

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
th:last-child {
    cursor: default;
}
th:last-child:hover {
    background-color: transparent;
}
</style>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 
