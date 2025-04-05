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
$motivos = ['Consulta inicial', 'Seguimiento', 'Terapia', 'Evaluación', 'Otro'];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_solicitada = sanitize($_POST['fecha_solicitada'] ?? '');
    $franja_horaria = sanitize($_POST['franja_horaria'] ?? '');
    $motivo = sanitize($_POST['motivo'] ?? '');
    $comentarios = sanitize($_POST['comentarios'] ?? '');
    
    // Validación básica
    $errores = [];
    
    if (empty($fecha_solicitada)) {
        $errores[] = "La fecha es obligatoria";
    }
    
    if (empty($franja_horaria)) {
        $errores[] = "Debe seleccionar una franja horaria preferida";
    }
    
    if (empty($motivo)) {
        $errores[] = "El motivo de la cita es obligatorio";
    }
    
    // Si no hay errores, guardar la solicitud
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Insertar solicitud en la base de datos
            $query = "INSERT INTO solicitudes_citas 
                     (paciente_id, fecha_solicitada, franja_horaria, motivo, comentarios, estado, fecha_solicitud) 
                     VALUES 
                     (:paciente_id, :fecha_solicitada, :franja_horaria, :motivo, :comentarios, 'pendiente', NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':paciente_id', $pacienteId);
            $stmt->bindParam(':fecha_solicitada', $fecha_solicitada);
            $stmt->bindParam(':franja_horaria', $franja_horaria);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':comentarios', $comentarios);
            
            if ($stmt->execute()) {
                $mensaje = "Su solicitud de cita ha sido enviada con éxito. Le contactaremos para confirmar la cita.";
                $tipo_mensaje = "success";
                
                // Enviar notificación por email al personal de la clínica (opcional)
                // enviarEmailNuevaSolicitud($pacienteNombre, $pacienteApellidos, $fecha_solicitada, $franja_horaria, $motivo);
            } else {
                $mensaje = "Ha ocurrido un error al procesar su solicitud. Por favor, inténtelo de nuevo.";
                $tipo_mensaje = "danger";
            }
        } catch (PDOException $e) {
            // Verificar si es error de tabla no existente
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                // Crear la tabla
                try {
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
                    
                    // Reintentar la inserción
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':paciente_id', $pacienteId);
                    $stmt->bindParam(':fecha_solicitada', $fecha_solicitada);
                    $stmt->bindParam(':franja_horaria', $franja_horaria);
                    $stmt->bindParam(':motivo', $motivo);
                    $stmt->bindParam(':comentarios', $comentarios);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Su solicitud de cita ha sido enviada con éxito. Le contactaremos para confirmar la cita.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Ha ocurrido un error al procesar su solicitud. Por favor, inténtelo de nuevo.";
                        $tipo_mensaje = "danger";
                    }
                } catch (PDOException $e2) {
                    $mensaje = "Error en el sistema: No se pudo procesar su solicitud.";
                    $tipo_mensaje = "danger";
                    error_log("Error creando tabla solicitudes_citas: " . $e2->getMessage());
                }
            } else {
                $mensaje = "Error en el sistema: No se pudo procesar su solicitud.";
                $tipo_mensaje = "danger";
                error_log("Error al guardar solicitud de cita: " . $e->getMessage());
            }
        }
    } else {
        $mensaje = "Por favor, corrija los siguientes errores:<br>" . implode("<br>", $errores);
        $tipo_mensaje = "danger";
    }
}

// Título de la página
$title = "Solicitar Cita - Portal de Pacientes";

// Iniciar buffer de salida
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Cabecera con información de usuario -->
    <div class="card border-0 bg-gradient-primary text-white shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Solicitar Nueva Cita</h1>
                    <p class="mb-0">Complete el formulario para solicitar una cita</p>
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
        <div class="col-lg-8 mx-auto">
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-plus me-2 text-primary"></i>Nueva Solicitud
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_solicitada" class="form-label">Fecha deseada</label>
                                <input type="date" class="form-control" id="fecha_solicitada" name="fecha_solicitada" required
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                <div class="form-text">Seleccione una fecha a partir de mañana</div>
                                <div class="invalid-feedback">
                                    Por favor seleccione una fecha válida.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="franja_horaria" class="form-label">Franja horaria preferida</label>
                                <select class="form-select" id="franja_horaria" name="franja_horaria" required>
                                    <option value="" selected disabled>Seleccione una franja</option>
                                    <option value="mañana">Mañana (9:00 - 14:00)</option>
                                    <option value="tarde">Tarde (16:00 - 20:00)</option>
                                    <option value="indiferente">Indiferente</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione una franja horaria.
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="motivo" class="form-label">Motivo de la cita</label>
                                <select class="form-select" id="motivo" name="motivo" required>
                                    <option value="" selected disabled>Seleccione un motivo</option>
                                    <?php foreach ($motivos as $m): ?>
                                    <option value="<?= $m ?>"><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un motivo.
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-4">
                                <label for="comentarios" class="form-label">Comentarios adicionales (opcional)</label>
                                <textarea class="form-control" id="comentarios" name="comentarios" rows="3"
                                          placeholder="Indique cualquier información adicional que considere relevante"></textarea>
                            </div>
                            
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Esta solicitud será procesada por nuestro personal. Le contactaremos para confirmar la cita.
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?= BASE_URL ?>/modules/portal_paciente/index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Validación del formulario
    (function() {
        'use strict';
        
        // Fetch all forms to apply validation
        var forms = document.querySelectorAll('.needs-validation');
        
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
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
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal para portal de pacientes
include ROOT_PATH . '/includes/layouts/portal.php';
?> 