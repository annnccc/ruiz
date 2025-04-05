<?php
/**
 * Módulo de Escalas Psicológicas - Página principal
 * Muestra el listado de escalas disponibles y administraciones recientes
 */

// Incluir configuración y base de datos
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Añadir depuración de sesión
error_log("Estado de la sesión en escalas/index.php: " . json_encode($_SESSION));

// Verificar manualmente si el usuario está autenticado
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['paciente_id'])) {
    error_log("No hay sesión de usuario activa en escalas/index.php");
    setAlert('warning', 'Debes iniciar sesión para acceder a esta página');
    redirect(BASE_URL . '/login.php');
} else {
    error_log("Usuario autenticado en escalas/index.php con ID: " . ($_SESSION['usuario_id'] ?? 'N/A') . ", Rol: " . ($_SESSION['usuario_rol'] ?? 'N/A'));
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

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <?= heroicon_outline('brain', 'sidebar-icon me-2') ?>
            <?= $pageTitle ?>
        </h1>
        <div>
            <a href="nueva_administracion.php" class="btn btn-primary">
                <?= heroicon_outline('plus-circle', 'me-2') ?> Nueva Administración
            </a>
            
            <a href="sugerir_escalas.php" class="btn btn-success">
                <?= heroicon_outline('light-bulb', 'me-2') ?> Sugerir Escalas
            </a>
            
            <?php if ($_SESSION['usuario_rol'] == 'admin'): ?>
            <a href="administrar_escalas.php" class="btn btn-secondary">
                <?= heroicon_outline('cog', 'me-2') ?> Administrar Escalas
            </a>
            <a href="admin_scripts.php" class="btn btn-danger">
                <?= heroicon_outline('command-line', 'me-2') ?> Scripts de Admin
            </a>
            <div class="dropdown d-inline-block">
                <button class="btn btn-info dropdown-toggle" type="button" id="cargarEscalasDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= heroicon_outline('arrow-down-tray', 'me-2') ?> Cargar Escalas Predefinidas
                </button>
                <ul class="dropdown-menu" aria-labelledby="cargarEscalasDropdown">
                    <li><a class="dropdown-item" href="cargar_items_rosenberg.php">Escala de Autoestima de Rosenberg</a></li>
                    <li><a class="dropdown-item" href="cargar_items_beck.php">Inventario de Depresión de Beck (BDI-II)</a></li>
                    <li><a class="dropdown-item" href="cargar_items_stai.php">Inventario de Ansiedad Estado-Rasgo (STAI)</a></li>
                    <li><a class="dropdown-item" href="cargar_items_ansiedad.php">Escala de Ansiedad de Hamilton (HARS)</a></li>
                    <li><a class="dropdown-item" href="cargar_items_ghq28.php">Cuestionario de Salud General (GHQ-28)</a></li>
                    <li><a class="dropdown-item" href="cargar_items_toc.php">Escala Yale-Brown para TOC (Y-BOCS)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Escalas para Niños y Adolescentes</h6></li>
                    <li><a class="dropdown-item" href="cargar_items_basc.php">Sistema de Evaluación BASC</a></li>
                    <li><a class="dropdown-item" href="cargar_items_d2.php">Test de Atención D2</a></li>
                    <li><a class="dropdown-item" href="cargar_items_tdah.php">ADHD Rating Scale-IV (TDAH)</a></li>
                </ul>
            </div>
            <div class="mb-3">
                <a href="setup_baremos.php" class="btn btn-info">
                    <?= heroicon_outline('chart-bar', 'me-2') ?> Configurar Baremos
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Dashboard</a></li>
            <li class="breadcrumb-item active">Escalas Psicológicas</li>
        </ol>
    </nav>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="row">
        <div class="col-xl-12">
            <?php if (empty($escalas)): ?>
                <div class="alert alert-warning">
                    <p>No hay escalas disponibles en el sistema. Si es administrador, puede configurar la base de datos desde <a href="setup_db.php">aquí</a>.</p>
                </div>
            <?php else: ?>
                <!-- Panel de administraciones recientes -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <?= heroicon_outline('clock', 'me-2') ?>
                            Administraciones Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($administraciones)): ?>
                            <p class="text-muted">No hay administraciones recientes.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Paciente</th>
                                            <th>Escala</th>
                                            <th>Estado</th>
                                            <th>Profesional</th>
                                            <th class="text-end">Acciones</th>
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
                                                <td class="text-end">
                                                    <div class="table-actions">
                                                        <a href="ver_administracion.php?id=<?= $admin['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalles">
                                                            <?= heroicon_outline('eye', 'heroicon-sm') ?>
                                                        </a>
                                                        <?php if (!$admin['completada']): ?>
                                                            <a href="completar_escala.php?id=<?= $admin['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Completar">
                                                                <?= heroicon_outline('pencil', 'heroicon-sm') ?>
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
                </div>
                
                <!-- Panel de escalas disponibles -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <?= heroicon_outline('clipboard-document-list', 'me-2') ?>
                            Escalas Disponibles
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($escalas as $escala): ?>
                                <div class="col-lg-6 col-xl-4 mb-3">
                                    <div class="card h-100 border">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><?= htmlspecialchars($escala['nombre']) ?></h5>
                                        </div>
                                        <div class="card-body">
                                            <p><?= htmlspecialchars($escala['descripcion']) ?></p>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <?= heroicon_outline('user-group', 'heroicon-sm me-1') ?>
                                                    Población: <?= ucfirst(htmlspecialchars($escala['poblacion'])) ?>
                                                </small>
                                                <small class="text-muted">
                                                    <?= heroicon_outline('clock', 'heroicon-sm me-1') ?>
                                                    Tiempo: <?= htmlspecialchars($escala['tiempo_estimado']) ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex justify-content-between">
                                            <a href="info_escala.php?id=<?= $escala['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                <?= heroicon_outline('information-circle', 'heroicon-sm me-1') ?> Detalles
                                            </a>
                                            <a href="nueva_administracion.php?escala_id=<?= $escala['id'] ?>" class="btn btn-sm btn-primary">
                                                <?= heroicon_outline('clipboard-document-check', 'heroicon-sm me-1') ?> Aplicar
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