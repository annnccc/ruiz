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
    $stmt = $db->query("SHOW TABLES LIKE 'consentimientos_enviados'");
    if ($stmt->rowCount() === 0) {
        redirect(BASE_URL . '/modules/consentimientos/install.php');
    }
} catch (PDOException $e) {
    redirect(BASE_URL . '/modules/consentimientos/install.php');
}

// Verifica si se ha proporcionado un ID de consentimiento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'Consentimiento no especificado');
    redirect(BASE_URL . '/modules/consentimientos/listar.php');
}

$consentimiento_id = (int) $_GET['id'];

// Obtener información del consentimiento
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, p.nombre, p.apellidos, p.email, p.telefono, p.dni, m.nombre as modelo_nombre 
        FROM consentimientos_enviados c 
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN consentimientos_modelos m ON c.modelo_id = m.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$consentimiento_id]);
    $consentimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consentimiento) {
        setAlert('danger', 'Consentimiento no encontrado');
        redirect(BASE_URL . '/modules/consentimientos/listar.php');
    }
    
    // Obtener paciente_id para reenviar a la página correcta
    $paciente_id = $consentimiento['paciente_id'];
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos del consentimiento: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/consentimientos/listar.php');
}

// Procesar el formulario si se envía
$enviado = false;
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    $email = !empty($_POST['email']) ? $_POST['email'] : $consentimiento['email'];
    
    if (empty($email)) {
        setAlert('danger', 'El paciente no tiene una dirección de email. Por favor, proporcione una.');
    } else {
        // Generar un nuevo token
        $token = generarTokenConsentimiento();
        
        // Actualizar información del consentimiento
        $stmtUpdate = $db->prepare("
            UPDATE consentimientos_enviados 
            SET token = ?, email = ?, fecha_envio = NOW(), fecha_caducidad = DATE_ADD(NOW(), INTERVAL 7 DAY), estado = 'pendiente'
            WHERE id = ?
        ");
        $stmtUpdate->execute([$token, $email, $consentimiento_id]);
        
        // Forzar URL específica para consentimientos
        $base_url = 'https://app.ruizarrietapsicologia.com/ruiz';
        
        // Construir URL para firma
        $url_firma = $base_url . '/publico/consentimiento.php?token=' . $token;
        
        // Enviar email al paciente
        $asunto = "Solicitud de firma de Consentimiento Informado";
        $mensaje = "
        <p>Estimado/a {$consentimiento['nombre']},</p>
        <p>Le reenviamos este correo para solicitarle su consentimiento informado para <strong>{$consentimiento['modelo_nombre']}</strong>.</p>
        <p>Por favor haga clic en el siguiente enlace para ver y firmar el documento:</p>
        <p><a href='{$url_firma}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ver y firmar documento</a></p>
        <p>O copie y pegue esta dirección en su navegador: {$url_firma}</p>
        <p><strong>IMPORTANTE:</strong> Este enlace caducará en 7 días. Por favor, complete el proceso antes de esa fecha.</p>
        <p>Si tiene alguna duda, no dude en contactarnos.</p>
        <p>Atentamente,<br>El equipo médico</p>
        ";
        
        $enviado = enviarEmail($email, $asunto, $mensaje);
        
        if ($enviado) {
            setAlert('success', 'Consentimiento reenviado correctamente');
            $enviado = true;
        } else {
            // Si falla el envío, mantener el estado anterior
            setAlert('danger', 'Error al enviar el email. Verifique la configuración SMTP.');
        }
    }
}

// Preparar datos para la página
$title = "Reenviar Consentimiento Informado";
$breadcrumbs = [
    'Consentimientos' => 'listar.php',
    'Reenviar' => '#'
];

// Iniciar el buffer de salida
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">forward_to_inbox</span>Reenviar Consentimiento Informado
        </h1>
        <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-outline-secondary">
            <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Consentimientos
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
                            <p><?= htmlspecialchars($consentimiento['nombre'] . ' ' . $consentimiento['apellidos']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Teléfono:</p>
                            <p><?= htmlspecialchars($consentimiento['telefono']) ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Email:</p>
                            <p><?= !empty($consentimiento['email']) ? htmlspecialchars($consentimiento['email']) : '<span class="text-danger">Sin correo electrónico</span>' ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">DNI/NIF:</p>
                            <p><?= !empty($consentimiento['dni']) ? htmlspecialchars($consentimiento['dni']) : '<span class="text-muted">No especificado</span>' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">description</span>Información del Consentimiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Tipo de Consentimiento:</p>
                            <p><?= htmlspecialchars($consentimiento['modelo_nombre']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Estado:</p>
                            <p>
                                <?php if ($consentimiento['estado'] == 'firmado'): ?>
                                <span class="badge bg-success">Firmado</span>
                                <?php elseif ($consentimiento['estado'] == 'pendiente'): ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Caducado</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="fw-bold mb-1">Fecha de envío:</p>
                            <p><?= formatDateToView($consentimiento['fecha_envio']) ?></p>
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
                    <h4 class="mb-3">Consentimiento Reenviado</h4>
                    <p>Se ha reenviado correctamente el consentimiento informado al paciente.</p>
                    <p>El paciente recibirá un email con un nuevo enlace para ver y firmar el documento.</p>
                    
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">list</span>Ver Todos los Consentimientos
                        </a>
                        <a href="<?= BASE_URL ?>/modules/pacientes/editar.php?id=<?= $paciente_id ?>" class="btn btn-outline-secondary ms-2">
                            <span class="material-symbols-rounded me-2">person</span>Ver Paciente
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Formulario de reenvío de consentimiento -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">mail</span>Reenviar Consentimiento
                    </h5>
                </div>
                <div class="card-body">
                    <p class="alert alert-info">
                        <span class="material-symbols-rounded me-2">info</span>
                        Al reenviar el consentimiento, se generará un nuevo enlace único y se invalidará el anterior.
                    </p>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email del Paciente</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">mail</span>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($consentimiento['email']) ?>" 
                                       <?= empty($consentimiento['email']) ? 'required' : '' ?>>
                            </div>
                            <div class="form-text">
                                <?php if (empty($consentimiento['email'])): ?>
                                El paciente no tiene email registrado. Por favor, proporcione uno.
                                <?php else: ?>
                                Se utilizará este email para enviar el consentimiento. Puede cambiarlo si lo desea.
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded me-2">send</span>Reenviar Consentimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">help</span>Información
                    </h5>
                </div>
                <div class="card-body">
                    <h6>¿Por qué reenviar un consentimiento?</h6>
                    <p>Puede ser necesario reenviar un consentimiento si:</p>
                    <ul>
                        <li>El paciente no recibió el email original</li>
                        <li>El enlace ha caducado o se ha perdido</li>
                        <li>El paciente ha cambiado su dirección de email</li>
                    </ul>
                    
                    <h6 class="mt-4">¿Qué ocurre al reenviar?</h6>
                    <p>Al reenviar un consentimiento:</p>
                    <ul>
                        <li>Se genera un nuevo enlace único</li>
                        <li>El enlace anterior queda invalidado</li>
                        <li>El estado se actualiza a "Pendiente"</li>
                        <li>Se registra una nueva fecha de envío</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($title);
?> 