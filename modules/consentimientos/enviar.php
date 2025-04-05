<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once 'funciones.php';

// Requiere autenticación
requiereLogin();

// Verifica si existe la tabla de consentimientos
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'consentimientos_modelos'");
    if ($stmt->rowCount() === 0) {
        redirect(BASE_URL . '/modules/consentimientos/install.php');
    }
} catch (PDOException $e) {
    redirect(BASE_URL . '/modules/consentimientos/install.php');
}

// Verifica si se ha proporcionado un ID de paciente
if (!isset($_GET['paciente_id']) || !is_numeric($_GET['paciente_id'])) {
    setAlert('danger', 'Paciente no especificado');
    redirect(BASE_URL . '/modules/pacientes/listar.php');
}

$paciente_id = (int) $_GET['paciente_id'];

// Obtener información del paciente
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$paciente_id]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paciente) {
        setAlert('danger', 'Paciente no encontrado');
        redirect(BASE_URL . '/modules/pacientes/listar.php');
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos del paciente');
    redirect(BASE_URL . '/modules/pacientes/listar.php');
}

// Obtener modelos de consentimiento
$modelos = obtenerModelosConsentimiento();

// Procesar el formulario si se envía
$enviado = false;
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    if (!isset($_POST['modelo_id']) || !is_numeric($_POST['modelo_id'])) {
        setAlert('danger', 'Debe seleccionar un modelo de consentimiento');
    } else {
        $modelo_id = (int) $_POST['modelo_id'];
        $email = !empty($_POST['email']) ? $_POST['email'] : $paciente['email'];
        
        if (empty($email)) {
            setAlert('danger', 'El paciente no tiene una dirección de email. Por favor, proporcione una.');
        } else {
            // Enviar consentimiento
            $resultado = enviarConsentimiento($paciente_id, $modelo_id, $email);
            
            if ($resultado['exito']) {
                setAlert('success', $resultado['mensaje']);
                $enviado = true;
            } else {
                setAlert('danger', $resultado['mensaje']);
            }
        }
    }
}

// Preparar datos para la página
$title = "Enviar Consentimiento Informado";
$breadcrumbs = [
    'Pacientes' => '../pacientes/list.php',
    $paciente['nombre'] . ' ' . $paciente['apellidos'] => '../pacientes/edit.php?id=' . $paciente_id,
    'Enviar Consentimiento' => '#'
];

// Iniciar el buffer de salida
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">approval</span>Enviar Consentimiento Informado
        </h1>
        <a href="<?= BASE_URL ?>/modules/pacientes/edit.php?id=<?= $paciente_id ?>" class="btn btn-outline-secondary">
            <span class="material-symbols-rounded me-2">arrow_back</span>Volver al Paciente
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">person</span>Información del Paciente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Nombre:</p>
                            <p><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Teléfono:</p>
                            <p><?= htmlspecialchars($paciente['telefono']) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Email:</p>
                            <p><?= !empty($paciente['email']) ? htmlspecialchars($paciente['email']) : '<span class="text-danger">Sin correo electrónico</span>' ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">DNI/NIF:</p>
                            <p><?= !empty($paciente['dni']) ? htmlspecialchars($paciente['dni']) : '<span class="text-muted">No especificado</span>' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($enviado): ?>
            <!-- Mensaje de éxito después del envío -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-5">
                    <div class="text-success mb-4">
                        <span class="material-symbols-rounded" style="font-size: 5rem;">mark_email_read</span>
                    </div>
                    <h4 class="mb-3">Consentimiento Enviado</h4>
                    <p>Se ha enviado correctamente el consentimiento informado al paciente.</p>
                    <p>El paciente recibirá un email con un enlace para ver y firmar el documento.</p>
                    
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/modules/pacientes/edit.php?id=<?= $paciente_id ?>" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">arrow_back</span>Volver al Paciente
                        </a>
                        <a href="<?= BASE_URL ?>/modules/consentimientos/enviar.php?paciente_id=<?= $paciente_id ?>" class="btn btn-outline-secondary ms-2">
                            <span class="material-symbols-rounded me-2">refresh</span>Enviar Otro
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Formulario de envío de consentimiento -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">mail</span>Enviar Consentimiento
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($modelos)): ?>
                    <div class="alert alert-warning">
                        No hay modelos de consentimiento disponibles. Contacte con el administrador.
                    </div>
                    <?php else: ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="modelo_id" class="form-label">Modelo de Consentimiento</label>
                            <select class="form-select" id="modelo_id" name="modelo_id" required>
                                <option value="">Seleccione un modelo...</option>
                                <?php foreach ($modelos as $modelo): ?>
                                <option value="<?= $modelo['id'] ?>">
                                    <?= htmlspecialchars($modelo['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Elija el tipo de consentimiento informado que desea enviar.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email del Paciente</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">mail</span>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($paciente['email']) ?>" 
                                       <?= empty($paciente['email']) ? 'required' : '' ?>>
                            </div>
                            <div class="form-text">
                                <?php if (empty($paciente['email'])): ?>
                                El paciente no tiene email registrado. Por favor, proporcione uno.
                                <?php else: ?>
                                Se utilizará este email para enviar el consentimiento. Puede cambiarlo si lo desea.
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex">
                                <div class="me-3">
                                    <span class="material-symbols-rounded">info</span>
                                </div>
                                <div>
                                    <p class="mb-0">Al enviar este formulario, se generará un enlace único que será enviado al email del paciente. 
                                    El paciente podrá ver el documento y firmarlo electrónicamente.</p>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">send</span>Enviar Consentimiento
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Panel informativo y de ayuda -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">help</span>Información
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">¿Qué es un consentimiento informado?</h6>
                    <p>El consentimiento informado es un documento que garantiza que el paciente ha recibido información suficiente sobre un procedimiento médico y acepta voluntariamente someterse a él.</p>
                    
                    <h6 class="fw-bold">¿Cómo funciona el proceso?</h6>
                    <ol class="ps-3">
                        <li>Seleccione el modelo de consentimiento adecuado</li>
                        <li>Verifique o añada el email del paciente</li>
                        <li>El paciente recibirá un email con un enlace</li>
                        <li>El paciente revisa el documento y firma digitalmente</li>
                        <li>El sistema registra la firma y actualiza el estado</li>
                    </ol>
                    
                    <div class="mt-3 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-outline-secondary btn-sm w-100">
                            <span class="material-symbols-rounded me-2">list</span>Ver Todos los Consentimientos
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Historial de consentimientos del paciente -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">history</span>Historial
                    </h5>
                </div>
                <div class="card-body">
                    <?php 
                    $historial = listarConsentimientosPaciente($paciente_id);
                    if (empty($historial)): 
                    ?>
                    <p class="text-muted">Este paciente no tiene consentimientos enviados anteriormente.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Modelo</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['modelo_nombre']) ?></td>
                                    <td>
                                        <?php if ($item['estado'] === 'firmado'): ?>
                                        <span class="badge bg-success">Firmado</span>
                                        <?php elseif ($item['estado'] === 'pendiente'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Caducado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDateToView($item['fecha_envio']) ?></td>
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

<?php
// Finalizar el buffer de salida
endPageContent();
?> 