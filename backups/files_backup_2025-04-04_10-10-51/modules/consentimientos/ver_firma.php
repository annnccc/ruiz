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

// Obtener información del consentimiento y la firma
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT ce.*, cm.nombre as modelo_nombre, cm.contenido, 
               p.nombre as paciente_nombre, p.apellidos as paciente_apellidos,
               cf.firma_imagen, cf.nombre_firmante, cf.fecha_firma
        FROM consentimientos_enviados ce
        JOIN consentimientos_modelos cm ON ce.modelo_id = cm.id
        JOIN pacientes p ON ce.paciente_id = p.id
        LEFT JOIN consentimientos_firmas cf ON ce.id = cf.consentimiento_id
        WHERE ce.id = ? AND ce.estado = 'firmado'
    ");
    $stmt->execute([$consentimiento_id]);
    $consentimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consentimiento) {
        setAlert('danger', 'Consentimiento no encontrado o no está firmado');
        redirect(BASE_URL . '/modules/consentimientos/listar.php');
    }
    
    // Obtener paciente_id para redirigir correctamente
    $paciente_id = $consentimiento['paciente_id'];
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos del consentimiento: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/consentimientos/listar.php');
}

// Preparar datos para la página
$title = "Ver Firma de Consentimiento";
$breadcrumbs = [
    'Consentimientos' => 'listar.php',
    'Ver Firma' => '#'
];

// Reemplazar placeholders en el contenido
$contenido = procesarContenidoConsentimiento(
    $consentimiento['contenido'], 
    $consentimiento, 
    $consentimiento['fecha_firma']
);

// Iniciar el buffer de salida
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">description</span>Consentimiento Firmado
        </h1>
        <div>
            <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-outline-secondary">
                <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Consentimientos
            </a>
            <a href="<?= BASE_URL ?>/modules/pacientes/editar.php?id=<?= $paciente_id ?>" class="btn btn-primary ms-2">
                <span class="material-symbols-rounded me-2">person</span>Ver Paciente
            </a>
        </div>
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
                        <span class="material-symbols-rounded me-2">description</span>
                        <?= htmlspecialchars($consentimiento['modelo_nombre']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p><strong>Paciente:</strong> <?= htmlspecialchars($consentimiento['paciente_nombre'] . ' ' . $consentimiento['paciente_apellidos']) ?></p>
                        <p><strong>Fecha de firma:</strong> <?= formatDateToView($consentimiento['fecha_firma']) ?></p>
                        <p><strong>IP de firma:</strong> <?= htmlspecialchars($consentimiento['ip_firma']) ?></p>
                    </div>
                    
                    <div class="documento-container border rounded bg-light mb-4">
                        <?= $contenido ?>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">draw</span>Firma Digital
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="border p-3 d-inline-block bg-white">
                            <img src="<?= $consentimiento['firma_imagen'] ?>" alt="Firma digital" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                    <p class="text-center"><strong>Firmado por:</strong> <?= htmlspecialchars($consentimiento['nombre_firmante']) ?></p>
                    <p class="text-center"><strong>Fecha y hora:</strong> <?= formatDateToView($consentimiento['fecha_firma'], true) ?></p>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <a href="#" class="btn btn-success btn-lg fw-bold" onclick="window.print();" style="padding: 12px 20px; font-size: 1.1rem;">
                    <span class="material-symbols-rounded me-2">print</span>Imprimir Consentimiento
                </a>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">info</span>Información
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-3">
                        <div class="d-flex">
                            <div class="me-3">
                                <span class="material-symbols-rounded">check_circle</span>
                            </div>
                            <div>
                                <p class="mb-0">Este consentimiento ha sido firmado correctamente y es legalmente válido.</p>
                            </div>
                        </div>
                    </div>
                    
                    <h6>¿Qué información se guarda?</h6>
                    <ul>
                        <li>Contenido del consentimiento</li>
                        <li>Fecha y hora exacta de la firma</li>
                        <li>Dirección IP del firmante</li>
                        <li>Nombre completo del firmante</li>
                        <li>Imagen de la firma digital</li>
                    </ul>
                    
                    <h6 class="mt-3">¿Es segura esta firma?</h6>
                    <p>Sí. La firma digital se almacena de forma segura y cumple con los requisitos legales para consentimientos informados en el ámbito médico.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    header, .sidebar, .breadcrumb, .no-print, 
    .btn, .card-header, .col-lg-4, h1, 
    .d-flex.justify-content-between.align-items-center {
        display: none !important;
    }
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card, .card-body {
        border: none !important;
        box-shadow: none !important;
    }
    .col-lg-8 {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    body {
        background-color: white !important;
    }
}
</style>

<?php
// Finalizar captura y renderizar
endPageContent($title);
?> 