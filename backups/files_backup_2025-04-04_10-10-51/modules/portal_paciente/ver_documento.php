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

// Verificar si se proporcionó un ID de documento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'Documento no especificado');
    header('Location: ' . BASE_URL . '/modules/portal_paciente/documentos.php');
    exit;
}

$documentoId = (int) $_GET['id'];

// Obtener información del documento
try {
    $db = getDB();
    
    $query = "SELECT d.*, u.nombre as usuario_nombre, u.apellidos as usuario_apellidos
             FROM documentos_pacientes d
             JOIN usuarios u ON d.subido_por = u.id
             WHERE d.id = :documento_id AND d.paciente_id = :paciente_id AND d.visible_paciente = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':documento_id', $documentoId, PDO::PARAM_INT);
    $stmt->bindParam(':paciente_id', $pacienteId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'Documento no encontrado o no tiene permisos para acceder a él');
        header('Location: ' . BASE_URL . '/modules/portal_paciente/documentos.php');
        exit;
    }
    
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar fecha de visualización si no está definida
    if ($documento['fecha_visualizacion'] === null) {
        $updateQuery = "UPDATE documentos_pacientes 
                      SET fecha_visualizacion = NOW() 
                      WHERE id = :documento_id";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':documento_id', $documentoId, PDO::PARAM_INT);
        $updateStmt->execute();
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener el documento');
    header('Location: ' . BASE_URL . '/modules/portal_paciente/documentos.php');
    exit;
}

// Determinar el tipo de documento para decidir cómo mostrarlo
$extension = pathinfo($documento['archivo'], PATHINFO_EXTENSION);
$urlDocumento = BASE_URL . '/uploads/documentos/' . $documento['archivo'];

// Título de la página
$title = $documento['nombre'] . " - Portal de Pacientes";

// Iniciar buffer de salida
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Cabecera con información de usuario -->
    <div class="card border-0 bg-gradient-primary text-white shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2"><?= htmlspecialchars($documento['nombre']) ?></h1>
                    <p class="mb-0">
                        <?php if (!empty($documento['descripcion'])): ?>
                        <?= htmlspecialchars($documento['descripcion']) ?>
                        <?php else: ?>
                        Documento compartido por la clínica
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/modules/portal_paciente/documentos.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Documentos
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php 
                            $iconClass = '';
                            switch($documento['tipo']) {
                                case 'informe': $iconClass = 'text-info'; $icon = 'description'; break;
                                case 'consentimiento': $iconClass = 'text-success'; $icon = 'assignment'; break;
                                case 'resultado': $iconClass = 'text-warning'; $icon = 'analytics'; break;
                                default: $iconClass = 'text-secondary'; $icon = 'insert_drive_file';
                            }
                            ?>
                            <span class="material-symbols-rounded <?= $iconClass ?> me-2"><?= $icon ?></span>
                            Detalles del Documento
                        </h5>
                        <a href="<?= $urlDocumento ?>" class="btn btn-primary btn-sm" download="<?= htmlspecialchars($documento['nombre']) ?>">
                            <i class="fas fa-download me-2"></i>Descargar
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Tipo:</strong> 
                                <?php 
                                $tipoDocumento = '';
                                switch($documento['tipo']) {
                                    case 'informe': $tipoDocumento = 'Informe'; break;
                                    case 'consentimiento': $tipoDocumento = 'Consentimiento'; break;
                                    case 'resultado': $tipoDocumento = 'Resultado'; break;
                                    default: $tipoDocumento = 'Otro';
                                }
                                echo $tipoDocumento;
                                ?>
                            </p>
                            <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($documento['fecha_subida'])) ?></p>
                            <p><strong>Compartido por:</strong> Dr. <?= htmlspecialchars($documento['usuario_apellidos'] . ', ' . $documento['usuario_nombre']) ?></p>
                        </div>
                    </div>
                    
                    <?php if (in_array(strtolower($extension), ['pdf'])): ?>
                    <!-- Visor de PDF -->
                    <div class="ratio ratio-16x9 mb-3" style="height: 700px;">
                        <iframe src="<?= $urlDocumento ?>" allowfullscreen></iframe>
                    </div>
                    <?php elseif (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <!-- Visor de imágenes -->
                    <div class="text-center mb-3">
                        <img src="<?= $urlDocumento ?>" alt="<?= htmlspecialchars($documento['nombre']) ?>" class="img-fluid rounded">
                    </div>
                    <?php else: ?>
                    <!-- Otros tipos de archivo -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Este tipo de archivo no puede visualizarse directamente en el navegador. 
                        Por favor, utilice el botón "Descargar" para ver su contenido.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = <<<EOT
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal para portal de pacientes
include ROOT_PATH . '/includes/layouts/portal.php';
?> 