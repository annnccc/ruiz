<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Solo administradores pueden configurar la sincronización
if (!isAdmin($_SESSION['usuario_rol'])) {
    setAlert('danger', 'Acceso denegado. Solo administradores pueden acceder a esta página.');
    header('Location: ' . BASE_URL . '/modules/calendario/index.php');
    exit;
}

// Procesar formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF (simulado)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setAlert('danger', 'Error de validación del formulario. Por favor, intente de nuevo.');
        header('Location: ' . BASE_URL . '/modules/calendario/sync_settings.php');
        exit;
    }
    
    try {
        $db = getDB();
        
        // Obtener opciones del formulario
        $googleSync = isset($_POST['google_sync']) && $_POST['google_sync'] == '1' ? 1 : 0;
        $clientId = trim($_POST['client_id'] ?? '');
        $clientSecret = trim($_POST['client_secret'] ?? '');
        $syncFrequency = intval($_POST['sync_frequency'] ?? 30);
        $defaultCalendarId = trim($_POST['default_calendar_id'] ?? '');
        
        // Validaciones básicas
        $errors = [];
        
        if ($googleSync) {
            if (empty($clientId)) {
                $errors[] = 'El ID de cliente de Google es obligatorio.';
            }
            
            if (empty($clientSecret)) {
                $errors[] = 'El secreto de cliente de Google es obligatorio.';
            }
            
            if ($syncFrequency < 5 || $syncFrequency > 1440) {
                $errors[] = 'La frecuencia de sincronización debe estar entre 5 y 1440 minutos.';
            }
        }
        
        // Si hay errores, mostrarlos y volver al formulario
        if (!empty($errors)) {
            $errorMessage = implode('<br>', $errors);
            setAlert('danger', $errorMessage);
            header('Location: ' . BASE_URL . '/modules/calendario/sync_settings.php');
            exit;
        }
        
        // Guardar configuración
        $configs = [
            'google_sync_enabled' => $googleSync,
            'google_client_id' => $clientId,
            'google_client_secret' => $clientSecret,
            'google_sync_frequency' => $syncFrequency,
            'google_default_calendar_id' => $defaultCalendarId,
            'last_modified_by' => $_SESSION['usuario_id'],
            'last_modified_at' => date('Y-m-d H:i:s')
        ];
        
        foreach ($configs as $key => $value) {
            // Verificar si la configuración ya existe
            $checkQuery = "SELECT id FROM configuracion WHERE clave = :clave";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':clave', $key);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Actualizar configuración existente
                $updateQuery = "UPDATE configuracion SET valor = :valor WHERE clave = :clave";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':valor', $value);
                $updateStmt->bindParam(':clave', $key);
                $updateStmt->execute();
            } else {
                // Insertar nueva configuración
                $insertQuery = "INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':clave', $key);
                $insertStmt->bindParam(':valor', $value);
                $insertStmt->execute();
            }
        }
        
        // Si la sincronización está activada, programar el primer sincronizado
        if ($googleSync) {
            // Aquí se podría añadir código para programar el primer sincronizado
            // usando una tarea cron o similar
        }
        
        setAlert('success', 'La configuración de sincronización se ha guardado correctamente.');
        header('Location: ' . BASE_URL . '/modules/calendario/sync_settings.php');
        exit;
        
    } catch (PDOException $e) {
        setAlert('danger', 'Error al guardar la configuración: ' . $e->getMessage());
        header('Location: ' . BASE_URL . '/modules/calendario/sync_settings.php');
        exit;
    }
}

// Obtener configuración actual
try {
    $db = getDB();
    
    $query = "SELECT clave, valor FROM configuracion 
             WHERE clave LIKE 'google_%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $config = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
    
    // Valores por defecto
    $googleSync = isset($config['google_sync_enabled']) ? intval($config['google_sync_enabled']) : 0;
    $clientId = $config['google_client_id'] ?? '';
    $clientSecret = $config['google_client_secret'] ?? '';
    $syncFrequency = isset($config['google_sync_frequency']) ? intval($config['google_sync_frequency']) : 30;
    $defaultCalendarId = $config['google_default_calendar_id'] ?? '';
    
    // Generar token CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener la configuración: ' . $e->getMessage());
    // Continuar con valores predeterminados
}

// Título de la página y breadcrumbs
$title = "Configuración de Sincronización";
$breadcrumbs = [
    'Calendario' => BASE_URL . '/modules/calendario/index.php',
    'Configuración de Sincronización' => '#'
];

// Iniciar buffer de salida
ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-sync-alt me-2 text-primary"></i>Configuración de Sincronización</h1>
            <p class="text-muted small mb-0">Configure la sincronización con calendarios externos</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/modules/calendario/index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Calendario
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Formulario de configuración -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fab fa-google me-2 text-primary"></i>Sincronización con Google Calendar
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <!-- Token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="google_sync" name="google_sync" value="1" <?= $googleSync ? 'checked' : '' ?>>
                            <label class="form-check-label" for="google_sync">
                                Activar sincronización con Google Calendar
                            </label>
                            <div class="form-text text-muted">
                                Al activar esta opción, las citas se sincronizarán automáticamente con Google Calendar.
                            </div>
                        </div>
                        
                        <div class="row mb-3" id="google-credentials">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label">ID de Cliente</label>
                                <input type="text" class="form-control" id="client_id" name="client_id" value="<?= htmlspecialchars($clientId) ?>">
                                <div class="form-text text-muted">
                                    Obtenido en la Consola de Desarrolladores de Google.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="client_secret" class="form-label">Secreto de Cliente</label>
                                <input type="password" class="form-control" id="client_secret" name="client_secret" value="<?= htmlspecialchars($clientSecret) ?>">
                                <div class="form-text text-muted">
                                    No se mostrará después de guardarlo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="sync_frequency" class="form-label">Frecuencia de Sincronización (minutos)</label>
                                <input type="number" class="form-control" id="sync_frequency" name="sync_frequency" min="5" max="1440" value="<?= $syncFrequency ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="default_calendar_id" class="form-label">ID del Calendario por Defecto</label>
                                <input type="text" class="form-control" id="default_calendar_id" name="default_calendar_id" value="<?= htmlspecialchars($defaultCalendarId) ?>">
                                <div class="form-text text-muted">
                                    Dejar en blanco para usar el calendario principal.
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Estado de la sincronización -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Estado de la Sincronización
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($googleSync): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        La sincronización con Google Calendar está activa.
                    </div>
                    
                    <p>Última sincronización: <strong><?= isset($config['last_sync_time']) ? date('d/m/Y H:i:s', strtotime($config['last_sync_time'])) : 'Nunca' ?></strong></p>
                    <p>Próxima sincronización: <strong><?= isset($config['last_sync_time']) ? date('d/m/Y H:i:s', strtotime($config['last_sync_time']) + ($syncFrequency * 60)) : 'Pendiente' ?></strong></p>
                    
                    <div class="d-grid gap-2 mt-3">
                        <a href="<?= BASE_URL ?>/modules/calendario/sync_now.php" class="btn btn-outline-primary">
                            <i class="fas fa-sync me-2"></i>Sincronizar Ahora
                        </a>
                        <a href="<?= BASE_URL ?>/modules/calendario/sync_logs.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>Ver Registros de Sincronización
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        La sincronización con Google Calendar está desactivada.
                    </div>
                    
                    <p>Active la sincronización y configure las credenciales de Google para habilitar esta función.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Instrucciones -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2 text-primary"></i>Instrucciones
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">¿Cómo configurar la sincronización?</h6>
                    
                    <ol class="small">
                        <li class="mb-2">Vaya a la <a href="https://console.developers.google.com/" target="_blank">Consola de Desarrolladores de Google</a> y cree un nuevo proyecto.</li>
                        <li class="mb-2">Active la API de Google Calendar para su proyecto.</li>
                        <li class="mb-2">En "Credenciales", cree credenciales de tipo "ID de cliente OAuth".</li>
                        <li class="mb-2">Configure los orígenes autorizados y las URIs de redirección.</li>
                        <li class="mb-2">Copie el ID de cliente y el secreto de cliente en este formulario.</li>
                        <li>Guarde la configuración y active la sincronización.</li>
                    </ol>
                    
                    <hr>
                    
                    <h6 class="mb-3">Notas importantes</h6>
                    
                    <ul class="small text-muted">
                        <li class="mb-2">La sincronización es bidireccional: las citas se actualizarán en ambos sistemas.</li>
                        <li class="mb-2">Los cambios en Google Calendar se reflejarán en el sistema de citas.</li>
                        <li>Se recomienda una frecuencia de sincronización de al menos 15 minutos.</li>
                    </ul>
                </div>
            </div>
            
            <!-- Requisitos -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2 text-primary"></i>Requisitos
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>PHP 7.3+</span>
                            <span class="badge bg-success">OK</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Google API PHP Client</span>
                            <?php if (class_exists('Google_Client')): ?>
                            <span class="badge bg-success">Instalado</span>
                            <?php else: ?>
                            <span class="badge bg-danger">No instalado</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Directorio de almacenamiento</span>
                            <?php if (is_writable(ROOT_PATH . '/temp')): ?>
                            <span class="badge bg-success">Escribible</span>
                            <?php else: ?>
                            <span class="badge bg-danger">No escribible</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <span>Cron Jobs</span>
                            <span class="badge bg-secondary">Requerido</span>
                        </li>
                    </ul>
                    
                    <?php if (!class_exists('Google_Client')): ?>
                    <div class="alert alert-info mt-3 small">
                        <i class="fas fa-info-circle me-2"></i>
                        Para instalar la biblioteca de Google API, ejecute:<br>
                        <code>composer require google/apiclient:^2.0</code>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campos de Google Calendar según el estado del switch
    const googleSyncSwitch = document.getElementById('google_sync');
    const googleCredentials = document.getElementById('google-credentials');
    
    function toggleGoogleFields() {
        if (googleSyncSwitch.checked) {
            googleCredentials.classList.remove('d-none');
        } else {
            googleCredentials.classList.add('d-none');
        }
    }
    
    // Aplicar estado inicial
    toggleGoogleFields();
    
    // Añadir evento de cambio
    googleSyncSwitch.addEventListener('change', toggleGoogleFields);
});
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = '';

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal
include ROOT_PATH . '/includes/layouts/main.php';
?> 