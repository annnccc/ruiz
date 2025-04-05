<?php
/**
 * Página de configuración de recordatorios
 * Permite configurar parámetros como:
 * - Días de anticipación para enviar el recordatorio
 * - Hora de envío
 * - Asunto y mensaje del email
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere autenticación
requiereLogin();

// Verificar si las tablas existen, si no, redirigir a install.php
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'recordatorios_config'");
    if ($stmt->rowCount() == 0) {
        // La tabla no existe, redirigir a install.php
        redirect('install.php');
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error al verificar la estructura de la base de datos: ' . $e->getMessage());
}

// Si es una solicitud POST, procesamos el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        // Validar datos
        $activo = isset($_POST['activo']) ? 1 : 0;
        $dias_anticipacion = isset($_POST['dias_anticipacion']) ? (int)$_POST['dias_anticipacion'] : 1;
        $hora_envio = isset($_POST['hora_envio']) ? $_POST['hora_envio'] : '08:00:00';
        $asunto_email = isset($_POST['asunto_email']) ? $_POST['asunto_email'] : 'Recordatorio de su cita';
        $mensaje_email = isset($_POST['mensaje_email']) ? $_POST['mensaje_email'] : '';
        
        // Validaciones básicas
        if ($dias_anticipacion < 1) {
            setAlert('danger', 'Los días de anticipación deben ser al menos 1.');
            redirect('configurar.php');
        }
        
        // Comprobar si ya existe una configuración
        $stmt = $db->query("SELECT COUNT(*) FROM recordatorios_config");
        $existe = $stmt->fetchColumn() > 0;
        
        if ($existe) {
            // Actualizar configuración existente
            $sql = "UPDATE recordatorios_config SET 
                   activo = :activo,
                   dias_anticipacion = :dias_anticipacion,
                   hora_envio = :hora_envio,
                   asunto_email = :asunto_email,
                   mensaje_email = :mensaje_email,
                   fecha_actualizacion = NOW()";
            
            $stmt = $db->prepare($sql);
        } else {
            // Insertar nueva configuración
            $sql = "INSERT INTO recordatorios_config 
                   (activo, dias_anticipacion, hora_envio, asunto_email, mensaje_email) 
                   VALUES 
                   (:activo, :dias_anticipacion, :hora_envio, :asunto_email, :mensaje_email)";
            
            $stmt = $db->prepare($sql);
        }
        
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindParam(':dias_anticipacion', $dias_anticipacion, PDO::PARAM_INT);
        $stmt->bindParam(':hora_envio', $hora_envio);
        $stmt->bindParam(':asunto_email', $asunto_email);
        $stmt->bindParam(':mensaje_email', $mensaje_email);
        
        $stmt->execute();
        
        setAlert('success', 'Configuración guardada correctamente.');
        redirect('configurar.php');
        
    } catch (PDOException $e) {
        setAlert('danger', 'Error al guardar la configuración: ' . $e->getMessage());
        redirect('configurar.php');
    }
}

// Obtener la configuración actual
$db = getDB();
$stmt = $db->query("SELECT * FROM recordatorios_config ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe configuración, usar valores predeterminados
if (!$config) {
    $config = [
        'activo' => 1,
        'dias_anticipacion' => 1,
        'hora_envio' => '08:00:00',
        'asunto_email' => 'Recordatorio de su cita médica',
        'mensaje_email' => '<p>Estimado/a {paciente},</p>
<p>Le recordamos que tiene una cita programada para el día <strong>{fecha}</strong> a las <strong>{hora}</strong> para <strong>{servicio}</strong>.</p>
<p>Por favor, llegue con 10 minutos de anticipación. Si necesita cancelar o reprogramar, contáctenos lo antes posible.</p>
<p>Atentamente,<br>El equipo de {clinica}</p>'
    ];
}

// Obtener estadísticas de envío
$query = "SELECT COUNT(*) as total FROM recordatorios_enviados";
$stmt = $db->query($query);
$total_enviados = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM recordatorios_enviados WHERE DATE(fecha_envio) = CURRENT_DATE()";
$stmt = $db->query($query);
$enviados_hoy = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM recordatorios_enviados WHERE estado = 'error'";
$stmt = $db->query($query);
$total_errores = $stmt->fetchColumn();

// Título y cabecera de la página
$title = "Configuración de Recordatorios";
$breadcrumbs = [
    'Recordatorios' => '#',
    'Configuración' => '#'
];

// CSS adicional para TinyMCE
$extra_css = '';

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-3 mb-md-0">
            <span class="material-symbols-rounded me-2">notifications</span>Configuración de Recordatorios
        </h1>
        <a href="enviar.php" class="btn btn-primary">
            <span class="material-symbols-rounded me-2">send</span>Enviar Recordatorios
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Formulario de configuración -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">settings</span>Parámetros de Envío
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="configurar.php">
                        <div class="mb-3 form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" 
                                   <?php echo $config['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">Activar envío de recordatorios</label>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <label for="dias_anticipacion" class="form-label">Días de anticipación</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="material-symbols-rounded icon-sm">calendar_month</span>
                                    </span>
                                    <input type="number" class="form-control" id="dias_anticipacion" name="dias_anticipacion" 
                                           value="<?php echo htmlspecialchars($config['dias_anticipacion']); ?>" min="1" required>
                                </div>
                                <div class="form-text">Días antes de la cita para enviar el recordatorio.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="hora_envio" class="form-label">Hora de envío</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="material-symbols-rounded icon-sm">schedule</span>
                                    </span>
                                    <input type="time" class="form-control" id="hora_envio" name="hora_envio" 
                                           value="<?php echo htmlspecialchars(substr($config['hora_envio'], 0, 5)); ?>" required>
                                </div>
                                <div class="form-text">Hora del día para programar los envíos.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="asunto_email" class="form-label">Asunto del Email</label>
                            <input type="text" class="form-control" id="asunto_email" name="asunto_email" 
                                   value="<?php echo htmlspecialchars($config['asunto_email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mensaje_email" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensaje_email" name="mensaje_email" 
                                      rows="10" required><?php echo htmlspecialchars($config['mensaje_email']); ?></textarea>
                            <div class="form-text">
                                Puedes usar las siguientes variables: {paciente}, {fecha}, {hora}, {servicio}, {clinica}
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">save</span>Guardar Configuración
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Estadísticas y Ayuda -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">analytics</span>Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total recordatorios enviados
                            <span class="badge bg-primary rounded-pill"><?php echo $total_enviados; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Enviados hoy
                            <span class="badge bg-success rounded-pill"><?php echo $enviados_hoy; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Errores
                            <span class="badge bg-danger rounded-pill"><?php echo $total_errores; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">schedule</span>Configuración Automática
                    </h5>
                </div>
                <div class="card-body">
                    <p>Para automatizar el envío de recordatorios, configura una tarea programada (cron job) que ejecute:</p>
                    <div class="bg-light p-3 rounded overflow-auto">
                        <pre class="mb-0" style="white-space: pre-wrap; word-break: break-all;">php <?php echo __DIR__; ?>/enviar.php</pre>
                    </div>
                    <p class="mb-0 small text-muted mt-3">Se recomienda programar esta tarea diariamente a la hora configurada.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE -->
<script src="<?= BASE_URL ?>/assets/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: '#mensaje_email',
        height: 300,
        menubar: false,
        language: 'es',
        plugins: [
            'code', 'link', 'lists', 'table'
        ],
        toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
</script>

<?php
// Finalizar el buffer de salida y mostrar el contenido
endPageContent($title);
?> 