<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar que el paciente esté autenticado
if (!isset($_SESSION['paciente_id'])) {
    header('Location: ' . BASE_URL . '/modules/portal_paciente/login.php');
    exit;
}

// Obtener información del paciente
$pacienteId = $_SESSION['paciente_id'];
$pacienteNombre = $_SESSION['paciente_nombre'];
$pacienteApellidos = $_SESSION['paciente_apellidos'];

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';

try {
    $db = getDB();
    
    // Verificar si existe la tabla de documentos
    $stmt = $db->query("SHOW TABLES LIKE 'documentos_pacientes'");
    if ($stmt->rowCount() === 0) {
        // Crear la tabla si no existe
        $db->exec("
        CREATE TABLE IF NOT EXISTS `documentos_pacientes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `paciente_id` int(11) NOT NULL,
          `nombre` varchar(255) NOT NULL,
          `descripcion` text,
          `tipo` varchar(50) NOT NULL COMMENT 'informe, consentimiento, resultado, otro',
          `archivo` varchar(255) NOT NULL,
          `fecha_subida` datetime NOT NULL,
          `subido_por` int(11) NOT NULL,
          `visible_paciente` tinyint(1) NOT NULL DEFAULT 1,
          `fecha_visualizacion` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `paciente_id` (`paciente_id`),
          KEY `subido_por` (`subido_por`),
          CONSTRAINT `fk_doc_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_doc_usuario` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    // Obtener los documentos del paciente
    $query = "SELECT d.*, u.nombre as usuario_nombre, u.apellidos as usuario_apellidos
             FROM documentos_pacientes d
             JOIN usuarios u ON d.subido_por = u.id
             WHERE d.paciente_id = :paciente_id AND d.visible_paciente = 1
             ORDER BY d.fecha_subida DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':paciente_id', $pacienteId, PDO::PARAM_INT);
    $stmt->execute();
    
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Actualizar la fecha de visualización si hay documentos y se está accediendo a la página
    if (!empty($documentos)) {
        $updateQuery = "UPDATE documentos_pacientes 
                       SET fecha_visualizacion = NOW() 
                       WHERE paciente_id = :paciente_id 
                       AND visible_paciente = 1 
                       AND fecha_visualizacion IS NULL";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':paciente_id', $pacienteId, PDO::PARAM_INT);
        $updateStmt->execute();
    }
    
} catch (PDOException $e) {
    $mensaje = "Error al cargar los documentos. Por favor, inténtelo de nuevo más tarde.";
    $tipo_mensaje = "danger";
    error_log("Error en documentos.php: " . $e->getMessage());
}

// Título de la página
$title = "Mis Documentos - Portal de Pacientes";

// Iniciar buffer de salida
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Cabecera con información de usuario -->
    <div class="card border-0 bg-gradient-primary text-white shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Mis Documentos</h1>
                    <p class="mb-0">Acceda a los documentos compartidos por la clínica</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/modules/portal_paciente/index.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2 text-primary"></i>Documentos Disponibles
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($documentos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <h6 class="mb-2">No hay documentos disponibles</h6>
                        <p class="text-muted">Actualmente no tiene documentos compartidos en su portal.</p>
                    </div>
                    <?php else: ?>
                    
                    <!-- Filtro por tipo de documento -->
                    <div class="mb-4">
                        <div class="btn-group" role="group" aria-label="Filtro de documentos">
                            <button type="button" class="btn btn-outline-primary active" data-filter="todos">Todos</button>
                            <button type="button" class="btn btn-outline-primary" data-filter="informe">Informes</button>
                            <button type="button" class="btn btn-outline-primary" data-filter="consentimiento">Consentimientos</button>
                            <button type="button" class="btn btn-outline-primary" data-filter="resultado">Resultados</button>
                            <button type="button" class="btn btn-outline-primary" data-filter="otro">Otros</button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Documento</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Compartido por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentos as $doc): ?>
                                <tr class="documento-item" data-tipo="<?= $doc['tipo'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $iconClass = '';
                                            switch($doc['tipo']) {
                                                case 'informe': $iconClass = 'text-info'; $icon = 'description'; break;
                                                case 'consentimiento': $iconClass = 'text-success'; $icon = 'assignment'; break;
                                                case 'resultado': $iconClass = 'text-warning'; $icon = 'analytics'; break;
                                                default: $iconClass = 'text-secondary'; $icon = 'insert_drive_file';
                                            }
                                            ?>
                                            <span class="material-symbols-rounded <?= $iconClass ?> me-2"><?= $icon ?></span>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($doc['nombre']) ?></h6>
                                                <?php if (!empty($doc['descripcion'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($doc['descripcion']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $badgeClass = '';
                                        switch($doc['tipo']) {
                                            case 'informe': $badgeClass = 'bg-info'; $tipoText = 'Informe'; break;
                                            case 'consentimiento': $badgeClass = 'bg-success'; $tipoText = 'Consentimiento'; break;
                                            case 'resultado': $badgeClass = 'bg-warning'; $tipoText = 'Resultado'; break;
                                            default: $badgeClass = 'bg-secondary'; $tipoText = 'Otro';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $tipoText ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($doc['fecha_subida'])) ?></td>
                                    <td>Dr. <?= htmlspecialchars($doc['usuario_apellidos'] . ', ' . $doc['usuario_nombre']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/uploads/documentos/<?= $doc['archivo'] ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank" 
                                           download="<?= htmlspecialchars($doc['nombre']) ?>">
                                            <i class="fas fa-download me-1"></i> Descargar
                                        </a>
                                        <a href="<?= BASE_URL ?>/modules/portal_paciente/ver_documento.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </a>
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
</div>

<script>
    // Filtro de documentos
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('[data-filter]');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Quitar clase activa de todos los botones
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Añadir clase activa al botón clickeado
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const items = document.querySelectorAll('.documento-item');
                
                items.forEach(item => {
                    if (filter === 'todos' || item.getAttribute('data-tipo') === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = <<<EOT
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
    
    .btn-group .btn.active {
        background-color: #007bff;
        color: white;
    }
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal para portal de pacientes
include ROOT_PATH . '/includes/layouts/portal.php';
?> 