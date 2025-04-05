<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Incluir funciones del dashboard personalizable
define('MODULES_PATH', __DIR__ . '/modules');
require_once MODULES_PATH . '/dashboard/dashboard_functions.php';

// Si no hay sesión iniciada, redirigir al login
// Utilizar la función requiereLogin para verificar la autenticación
requiereLogin();

// Verificar si las tablas del dashboard existen
$tablasDashboardExisten = verificarTablasDashboard();

// Si no existen y el usuario es admin, sugerir instalación
$mostrarAlertaInstalacion = false;
if (!$tablasDashboardExisten && esAdmin()) {
    $mostrarAlertaInstalacion = true;
}

// Consultar estadísticas básicas (se utilizan en varios widgets)
try {
    $db = getDB();
    
    // Total de pacientes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM pacientes");
    $stmt->execute();
    $total_pacientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de citas
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas");
    $stmt->execute();
    $total_citas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Citas pendientes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE estado = 'pendiente'");
    $stmt->execute();
    $citas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Citas para hoy
    $hoy = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE DATE(fecha) = :fecha");
    $stmt->bindParam(':fecha', $hoy);
    $stmt->execute();
    $citas_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Últimas 5 citas
    $stmt = $db->prepare("
        SELECT c.*, p.nombre, p.apellidos 
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        ORDER BY c.fecha DESC, c.hora_inicio DESC
        LIMIT 5
    ");
    $stmt->execute();
    $ultimas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimos 5 pacientes añadidos
    $stmt = $db->prepare("
        SELECT * FROM pacientes
        ORDER BY fecha_registro DESC
        LIMIT 5
    ");
    $stmt->execute();
    $ultimos_pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Citas programadas para hoy
    $stmt = $db->prepare("
        SELECT c.*, p.nombre, p.apellidos 
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE DATE(c.fecha) = :fecha
        ORDER BY c.hora_inicio ASC
    ");
    $stmt->bindParam(':fecha', $hoy);
    $stmt->execute();
    $citas_para_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Próximas citas (citas futuras excluyendo hoy)
    $manana = date('Y-m-d', strtotime('+1 day'));
    $limite = date('Y-m-d', strtotime('+7 days'));
    $stmt = $db->prepare("
        SELECT c.*, p.nombre, p.apellidos 
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE c.fecha BETWEEN :manana AND :limite
        ORDER BY c.fecha ASC, c.hora_inicio ASC
        LIMIT 5
    ");
    $stmt->bindParam(':manana', $manana);
    $stmt->bindParam(':limite', $limite);
    $stmt->execute();
    $proximas_citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Citas por estado para gráfico
    $stmt = $db->prepare("
        SELECT estado, COUNT(*) as total 
        FROM citas 
        GROUP BY estado
    ");
    $stmt->execute();
    $citas_por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pacientes por género
    $stmt = $db->prepare("
        SELECT sexo, COUNT(*) as total 
        FROM pacientes 
        GROUP BY sexo
    ");
    $stmt->execute();
    $pacientes_por_genero = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Si hay error en la DB, inicializar con valores por defecto
    $total_pacientes = 0;
    $total_citas = 0;
    $citas_pendientes = 0;
    $citas_hoy = 0;
    $ultimas_citas = [];
    $ultimos_pacientes = [];
    $citas_para_hoy = [];
    $proximas_citas = [];
    $citas_por_estado = [];
    $pacientes_por_genero = [];
}

// Obtener configuración del dashboard si las tablas existen
$widgets = [];
if ($tablasDashboardExisten) {
    $usuario_id = $_SESSION['usuario_id'];
    $widgets = obtenerConfigDashboard($usuario_id);
}

// Título de la página
$titulo_pagina = "Dashboard";

// Iniciar captura del contenido
startPageContent();
?>

<!-- Contenido del dashboard -->
<div class="container-fluid py-4">
    <?php if ($mostrarAlertaInstalacion): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex">
            <div class="me-3">
                <span class="material-symbols-rounded fs-1">dashboard_customize</span>
            </div>
            <div>
                <h4 class="alert-heading">¡Dashboard personalizable disponible!</h4>
                <p>Hemos detectado que las tablas para el dashboard personalizable no están instaladas. Esta funcionalidad te permite elegir qué widgets mostrar y en qué orden.</p>
                <hr>
                <p class="mb-0">
                    <a href="<?= BASE_URL ?>/modules/dashboard/install.php" class="btn btn-warning">
                        <span class="material-symbols-rounded me-1">install_desktop</span> Instalar ahora
                    </a>
                </p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php endif; ?>
    
    <?php 
    // Variables para determinar el saludo (se mantienen para posible uso futuro)
    $nombre_usuario = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'usuario';
    $hora_actual = date('H');
    $saludo = 'Buenos días';
    
    if ($hora_actual >= 12 && $hora_actual < 20) {
        $saludo = 'Buenas tardes';
    } elseif ($hora_actual >= 20) {
        $saludo = 'Buenas noches';
    }
    ?>
    
    <?php if ($tablasDashboardExisten): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Panel de control</h4>
        <a href="<?= BASE_URL ?>/modules/dashboard/configure.php" class="btn btn-sm btn-outline-primary">
            <span class="material-symbols-rounded me-1">dashboard_customize</span> Personalizar dashboard
        </a>
    </div>
    
    <div class="row">
        <?php
        // Si no hay widgets, mostrar mensaje
        if (empty($widgets)) {
            echo '<div class="col-12 text-center py-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-5">
                            <span class="material-symbols-rounded d-block mb-3" style="font-size: 3rem; color: var(--text-muted);">
                                dashboard_customize
                            </span>
                            <h4 class="text-muted">Sin widgets configurados</h4>
                            <p class="text-muted mb-4">No hay widgets configurados para mostrar en el dashboard.</p>
                            <a href="' . BASE_URL . '/modules/dashboard/configure.php" class="btn btn-primary">
                                <span class="material-symbols-rounded me-1">settings</span> Configurar dashboard
                            </a>
                        </div>
                    </div>
                </div>';
        } else {
            // Renderizar widgets según configuración
            foreach ($widgets as $widget) {
                if ($widget['activo']) {
                    // Incluir archivo del widget
                    $widgetFile = MODULES_PATH . '/dashboard/widgets/' . $widget['codigo'] . '.php';
                    if (file_exists($widgetFile)) {
                        include $widgetFile;
                    } else {
                        // Widget no encontrado, mostrar placeholder
                        echo '<div class="' . $widget['tamano'] . ' mb-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center py-5">
                                        <span class="material-symbols-rounded d-block mb-3" style="font-size: 2.5rem;">error</span>
                                        <h5>Widget no disponible</h5>
                                        <p>El widget solicitado no está disponible o ha sido eliminado.</p>
                                    </div>
                                </div>
                            </div>';
                    }
                }
            }
        }
        ?>
    </div>
    <?php else: ?>
    <!-- Mostrar dashboard clásico si no existen las tablas -->
    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-primary">person</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Pacientes</h6>
                            <h2 class="mb-0 fw-bold"><?= $total_pacientes ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-success">calendar_month</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Citas totales</h6>
                            <h2 class="mb-0 fw-bold"><?= $total_citas ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-warning">pending</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Pendientes</h6>
                            <h2 class="mb-0 fw-bold"><?= $citas_pendientes ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <span class="material-symbols-rounded text-info">today</span>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Citas hoy</h6>
                            <h2 class="mb-0 fw-bold"><?= $citas_hoy ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen de actividad reciente -->
    <div class="row g-4">
        <!-- Últimas citas -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Últimas citas</h5>
                        <a href="<?= BASE_URL ?>/modules/citas/list.php" class="btn btn-sm btn-primary">
                            Ver todas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimas_citas)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">No hay citas recientes</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ultimas_citas as $cita): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle avatar-sm me-2">
                                                        <?= strtoupper(substr($cita['nombre'], 0, 1) . substr($cita['apellidos'], 0, 1)) ?>
                                                    </div>
                                                    <div class="fw-medium"><?= htmlspecialchars($cita['apellidos'] . ', ' . $cita['nombre']) ?></div>
                                                </div>
                                            </td>
                                            <td><?= formatDateToView($cita['fecha']) ?> <small class="text-muted"><?= formatTime($cita['hora_inicio']) ?></small></td>
                                            <td>
                                                <span class="badge <?= getEstadoCitaClass($cita['estado']) ?>"><?= ucfirst(htmlspecialchars($cita['estado'])) ?></span>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                                        <span class="material-symbols-rounded">visibility</span>
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
        </div>
        
        <!-- Últimos pacientes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pacientes recientes</h5>
                        <a href="<?= BASE_URL ?>/modules/pacientes/list.php" class="btn btn-sm btn-primary">
                            Ver todos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Contacto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimos_pacientes)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4">No hay pacientes recientes</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ultimos_pacientes as $paciente): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle avatar-sm me-2">
                                                        <?= strtoupper(substr($paciente['nombre'], 0, 1) . substr($paciente['apellidos'], 0, 1)) ?>
                                                    </div>
                                                    <div class="fw-medium"><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><span class="material-symbols-rounded me-1 small text-muted">call</span> <?= htmlspecialchars($paciente['telefono']) ?></div>
                                                <div class="small text-muted"><span class="material-symbols-rounded me-1 small">mail</span> <?= htmlspecialchars($paciente['email']) ?></div>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $paciente['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                                        <span class="material-symbols-rounded">visibility</span>
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
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?>

<script>
// Mostrar notificación de bienvenida con el nuevo diseño
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<div class="d-flex align-items-center justify-content-center"><span class="material-symbols-rounded material-symbols-filled me-2" style="color: var(--primary-color); font-size: 32px;">auto_awesome</span> ¡Nuevo diseño!</div>',
                html: '<div class="text-center mb-3">' +
                      'Hemos actualizado la interfaz con Material Symbols para una experiencia más moderna.' +
                      '</div>' +
                      '<div class="d-flex justify-content-center gap-3 mb-4">' +
                      '<span class="material-symbols-rounded material-symbols-filled" style="font-size: 30px; color: var(--primary-color);">dashboard</span>' +
                      '<span class="material-symbols-rounded material-symbols-filled" style="font-size: 30px; color: var(--success-color);">event_available</span>' +
                      '<span class="material-symbols-rounded material-symbols-filled" style="font-size: 30px; color: var(--warning-color);">calendar_month</span>' +
                      '<span class="material-symbols-rounded material-symbols-filled" style="font-size: 30px; color: var(--danger-color);">group</span>' +
                      '</div>',
                showCloseButton: true,
                showConfirmButton: false,
                position: 'bottom-end',
                timer: 6000,
                timerProgressBar: true,
                backdrop: false,
                customClass: {
                    popup: 'animate__animated animate__fadeInUp swal-modern',
                    title: 'fs-5'
                }
            });
        }
    }, 1000);
});
</script> 