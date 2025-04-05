<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación
requiereLogin();

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones 
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $accion = $_GET['accion'];
    $solicitud_id = (int)$_GET['id'];
    
    try {
        $db = getDB();
        
        // Obtener datos de la solicitud
        $stmt = $db->prepare("
            SELECT s.*, p.nombre as paciente_nombre, p.apellidos as paciente_apellidos
            FROM solicitudes_citas s
            JOIN pacientes p ON s.paciente_id = p.id
            WHERE s.id = ?
        ");
        $stmt->execute([$solicitud_id]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$solicitud) {
            setAlert('danger', 'Solicitud no encontrada');
            redirect(BASE_URL . '/modules/admin/solicitudes_citas.php');
        }
        
        // Procesar según la acción solicitada
        switch ($accion) {
            case 'confirmar':
                // Redirigir a la página de crear cita con los datos prellenados
                redirect(BASE_URL . '/modules/citas/create.php?paciente_id=' . $solicitud['paciente_id'] . 
                         '&fecha=' . $solicitud['fecha_solicitada'] . 
                         '&solicitud_id=' . $solicitud_id);
                break;
                
            case 'rechazar':
                // Actualizar estado de la solicitud
                $stmt = $db->prepare("
                    UPDATE solicitudes_citas 
                    SET estado = 'rechazada', fecha_procesado = NOW(), notas_admin = ?
                    WHERE id = ?
                ");
                $stmt->execute(["Rechazada por " . $_SESSION['nombre'] . " " . $_SESSION['apellidos'], $solicitud_id]);
                
                setAlert('success', 'Solicitud rechazada correctamente');
                redirect(BASE_URL . '/modules/admin/solicitudes_citas.php');
                break;
                
            default:
                setAlert('danger', 'Acción no válida');
                redirect(BASE_URL . '/modules/admin/solicitudes_citas.php');
        }
    } catch (PDOException $e) {
        setAlert('danger', 'Error al procesar la solicitud: ' . $e->getMessage());
        redirect(BASE_URL . '/modules/admin/solicitudes_citas.php');
    }
}

// Obtener todas las solicitudes pendientes
try {
    $db = getDB();
    
    // Verificar si existe la tabla de solicitudes
    $stmt = $db->query("SHOW TABLES LIKE 'solicitudes_citas'");
    if ($stmt->rowCount() === 0) {
        // Crear la tabla si no existe
        $db->exec("
        CREATE TABLE IF NOT EXISTS `solicitudes_citas` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `paciente_id` int(11) NOT NULL,
          `fecha_solicitada` date NOT NULL,
          `franja_horaria` varchar(50) NOT NULL,
          `motivo` varchar(100) NOT NULL,
          `comentarios` text,
          `estado` enum('pendiente','confirmada','rechazada') NOT NULL DEFAULT 'pendiente',
          `fecha_solicitud` datetime NOT NULL,
          `fecha_procesado` datetime DEFAULT NULL,
          `cita_id` int(11) DEFAULT NULL,
          `notas_admin` text,
          PRIMARY KEY (`id`),
          KEY `paciente_id` (`paciente_id`),
          KEY `cita_id` (`cita_id`),
          CONSTRAINT `fk_solicitud_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_solicitud_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    // Obtener solicitudes
    $query = "
        SELECT s.*, p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, p.telefono
        FROM solicitudes_citas s
        JOIN pacientes p ON s.paciente_id = p.id
        ORDER BY 
            CASE WHEN s.estado = 'pendiente' THEN 0 ELSE 1 END, 
            s.fecha_solicitud DESC
    ";
    
    $stmt = $db->query($query);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al cargar las solicitudes: " . $e->getMessage();
    $tipo_mensaje = "danger";
    error_log("Error en solicitudes_citas.php: " . $e->getMessage());
}

// Preparar título y breadcrumbs
$pageTitle = 'Solicitudes de Citas';
$breadcrumbs = [
    'Administración' => BASE_URL . '/modules/admin/index.php',
    'Solicitudes de Citas' => '#'
];

// Iniciar buffer de salida
ob_start();
?>

<div class="row mb-4">
    <div class="col">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>
                        Gestión de Solicitudes de Citas
                    </h5>
                    <div>
                        <a href="<?= BASE_URL ?>/modules/citas/list.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Ver Calendario de Citas
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($solicitudes)): ?>
                <div class="text-center p-5">
                    <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
                    <h5 class="mb-3">No hay solicitudes de citas</h5>
                    <p class="text-muted">Actualmente no hay solicitudes de citas realizadas por los pacientes.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Estado</th>
                                <th scope="col">Paciente</th>
                                <th scope="col">Fecha Solicitada</th>
                                <th scope="col">Franja Horaria</th>
                                <th scope="col">Motivo</th>
                                <th scope="col">Fecha Solicitud</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td>
                                    <?php
                                    $badgeClass = '';
                                    switch ($solicitud['estado']) {
                                        case 'pendiente':
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'confirmada':
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'rechazada':
                                            $badgeClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($solicitud['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $solicitud['paciente_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($solicitud['paciente_apellidos'] . ', ' . $solicitud['paciente_nombre']) ?>
                                    </a>
                                </td>
                                <td><?= date('d/m/Y', strtotime($solicitud['fecha_solicitada'])) ?></td>
                                <td><?= ucfirst(htmlspecialchars($solicitud['franja_horaria'])) ?></td>
                                <td><?= htmlspecialchars($solicitud['motivo']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></td>
                                <td class="text-center">
                                    <?php if ($solicitud['estado'] === 'pendiente'): ?>
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>/modules/admin/solicitudes_citas.php?accion=confirmar&id=<?= $solicitud['id'] ?>" 
                                           class="btn btn-success btn-sm" title="Confirmar" data-bs-toggle="tooltip">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" title="Rechazar" data-bs-toggle="tooltip"
                                                onclick="confirmarRechazo(<?= $solicitud['id'] ?>, '<?= htmlspecialchars($solicitud['paciente_nombre'] . ' ' . $solicitud['paciente_apellidos']) ?>')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php if (!empty($solicitud['telefono'])): ?>
                                        <a href="<?= generarUrlWhatsApp($solicitud['telefono'], [
                                            'motivo' => $solicitud['motivo'],
                                            'fecha' => date('d/m/Y', strtotime($solicitud['fecha_solicitada']))
                                        ]) ?>" class="btn btn-whatsapp btn-sm" title="Enviar WhatsApp" target="_blank" data-bs-toggle="tooltip">
                                            <span class="material-symbols-rounded">smartphone</span>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <?php if ($solicitud['cita_id']): ?>
                                    <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $solicitud['cita_id'] ?>" 
                                       class="btn btn-primary btn-sm" title="Ver Cita" data-bs-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="details-row">
                                <td colspan="7" class="p-0">
                                    <div class="collapse" id="detallesSolicitud<?= $solicitud['id'] ?>">
                                        <div class="p-3 bg-light">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <?php if (!empty($solicitud['comentarios'])): ?>
                                                    <h6 class="mb-2">Comentarios del Paciente:</h6>
                                                    <p class="mb-3"><?= nl2br(htmlspecialchars($solicitud['comentarios'])) ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($solicitud['notas_admin'])): ?>
                                                    <h6 class="mb-2">Notas Administrativas:</h6>
                                                    <p class="mb-0"><?= nl2br(htmlspecialchars($solicitud['notas_admin'])) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <?php if ($solicitud['fecha_procesado']): ?>
                                                    <p class="mb-0 text-muted small">Procesada: <?= date('d/m/Y H:i', strtotime($solicitud['fecha_procesado'])) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
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
    </div>
</div>

<!-- Modal para confirmar rechazo -->
<div class="modal fade" id="rechazarModal" tabindex="-1" aria-labelledby="rechazarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rechazarModalLabel">Confirmar Rechazo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea rechazar la solicitud de cita de <strong id="nombrePaciente"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnRechazar" class="btn btn-danger">Rechazar</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Hacer que las filas sean expandibles para mostrar detalles
        document.querySelectorAll('tr:not(.details-row)').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // No expandir si se hizo clic en un botón
                if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.tagName === 'I' || e.target.tagName === 'SPAN') {
                    return;
                }
                
                const id = this.nextElementSibling.querySelector('.collapse').id;
                const collapseElement = document.getElementById(id);
                const bsCollapse = new bootstrap.Collapse(collapseElement, {
                    toggle: true
                });
            });
        });
    });
    
    function confirmarRechazo(id, nombre) {
        document.getElementById('nombrePaciente').textContent = nombre;
        document.getElementById('btnRechazar').href = '<?= BASE_URL ?>/modules/admin/solicitudes_citas.php?accion=rechazar&id=' + id;
        
        const modal = new bootstrap.Modal(document.getElementById('rechazarModal'));
        modal.show();
    }
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// Incluir el layout
include_once ROOT_PATH . '/includes/layouts/app.php';
?> 