<?php
/**
 * Módulo de Escalas Psicológicas - Página principal
 * Muestra el listado de escalas disponibles y administraciones recientes
 */

// Incluir configuración y base de datos
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado ANTES de incluir el header
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Obtener conexión a la base de datos
$db = getDB();

// Título de la página
$pageTitle = "Escalas Psicológicas";

// Iniciar captura del contenido de la página
startPageContent();

// Obtener escalas del catálogo
$stmt = $db->query("SELECT id, nombre, descripcion, poblacion, tiempo_estimado FROM escalas_catalogo ORDER BY nombre");
$escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener administraciones recientes según el rol del usuario
if ($_SESSION['usuario_rol'] == 'admin' || $_SESSION['usuario_rol'] == 'psicologo') {
    // Administradores y psicólogos ven todas las administraciones recientes
    $stmt = $db->query("
        SELECT a.id, a.fecha, a.completada, 
               e.nombre AS escala_nombre, 
               p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
               u.nombre AS usuario_nombre
        FROM escalas_administraciones a
        JOIN escalas_catalogo e ON a.escala_id = e.id
        JOIN pacientes p ON a.paciente_id = p.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY a.fecha DESC
        LIMIT 10
    ");
} else {
    // Otros usuarios solo ven sus propias administraciones
    $stmt = $db->prepare("
        SELECT a.id, a.fecha, a.completada, 
               e.nombre AS escala_nombre, 
               p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos,
               u.nombre AS usuario_nombre
        FROM escalas_administraciones a
        JOIN escalas_catalogo e ON a.escala_id = e.id
        JOIN pacientes p ON a.paciente_id = p.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.usuario_id = :usuario_id
        ORDER BY a.fecha DESC
        LIMIT 10
    ");
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->execute();
}

$administraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">psychology</span>
        Escalas Psicológicas
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Escalas Psicológicas</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <?php if (empty($escalas)): ?>
                <div class="alert alert-warning">
                    <p>No hay escalas disponibles en el sistema. Si es administrador, puede configurar la base de datos desde <a href="setup_db.php">aquí</a>.</p>
                </div>
            <?php else: ?>
                <!-- Botones de acción -->
                <div class="mb-4">
                    <a href="nueva_administracion.php" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">add</span> Nueva Administración
                    </a>
                    
                    <?php if ($_SESSION['usuario_rol'] == 'admin'): ?>
                    <a href="administrar_escalas.php" class="btn btn-secondary">
                        <span class="material-symbols-rounded me-1">settings</span> Administrar Escalas
                    </a>
                    <a href="admin_scripts.php" class="btn btn-danger">
                        <span class="material-symbols-rounded me-1">admin_panel_settings</span> Scripts de Admin
                    </a>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-info dropdown-toggle" type="button" id="cargarEscalasDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="material-symbols-rounded me-1">download</span> Cargar Escalas Predefinidas
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="cargarEscalasDropdown">
                            <li><a class="dropdown-item" href="cargar_items_rosenberg.php">Escala de Autoestima de Rosenberg</a></li>
                            <li><a class="dropdown-item" href="cargar_items_beck.php">Inventario de Depresión de Beck (BDI-II)</a></li>
                            <li><a class="dropdown-item" href="cargar_items_stai.php">Inventario de Ansiedad Estado-Rasgo (STAI)</a></li>
                            <li><a class="dropdown-item" href="cargar_items_ghq28.php">Cuestionario de Salud General (GHQ-28)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Escalas para Niños y Adolescentes</h6></li>
                            <li><a class="dropdown-item" href="cargar_items_basc.php">Sistema de Evaluación BASC</a></li>
                            <li><a class="dropdown-item" href="cargar_items_d2.php">Test de Atención D2</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Panel de administraciones recientes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-history me-1"></i>
                        Administraciones Recientes
                    </div>
                    <div class="card-body">
                        <?php if (empty($administraciones)): ?>
                            <p class="text-muted">No hay administraciones recientes.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Paciente</th>
                                            <th>Escala</th>
                                            <th>Estado</th>
                                            <th>Profesional</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($administraciones as $admin): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($admin['fecha'])) ?></td>
                                                <td><?= htmlspecialchars($admin['paciente_nombre'] . ' ' . $admin['paciente_apellidos']) ?></td>
                                                <td><?= htmlspecialchars($admin['escala_nombre']) ?></td>
                                                <td>
                                                    <?php if ($admin['completada']): ?>
                                                        <span class="badge bg-success">Completada</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($admin['usuario_nombre']) ?></td>
                                                <td>
                                                    <a href="ver_administracion.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-primary">
                                                        <span class="material-symbols-rounded">visibility</span>
                                                    </a>
                                                    <?php if (!$admin['completada']): ?>
                                                        <a href="completar_escala.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-success">
                                                            <span class="material-symbols-rounded">edit</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Panel de escalas disponibles -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list me-1"></i>
                        Escalas Disponibles
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($escalas as $escala): ?>
                                <div class="col-lg-6 col-xl-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><?= htmlspecialchars($escala['nombre']) ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <p><?= htmlspecialchars($escala['descripcion']) ?></p>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <span class="material-symbols-rounded me-1 small">group</span>
                                                    Población: <?= ucfirst(htmlspecialchars($escala['poblacion'])) ?>
                                                </small>
                                                <small class="text-muted">
                                                    <span class="material-symbols-rounded me-1 small">schedule</span>
                                                    Tiempo: <?= htmlspecialchars($escala['tiempo_estimado']) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex justify-content-between">
                                            <a href="info_escala.php?id=<?= $escala['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                <span class="material-symbols-rounded me-1">info</span> Detalles
                                            </a>
                                            <a href="nueva_administracion.php?escala_id=<?= $escala['id'] ?>" class="btn btn-sm btn-primary">
                                                <span class="material-symbols-rounded me-1">assignment</span> Aplicar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 