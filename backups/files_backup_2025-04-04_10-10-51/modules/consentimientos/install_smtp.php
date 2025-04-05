<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Requiere autenticación y derechos de administrador
requiereLogin();
if (!esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta página.');
    redirect(BASE_URL);
}

// Comprobar si ya existen las configuraciones
$db = getDB();
$stmt = $db->query("SELECT clave FROM configuracion WHERE clave IN ('smtp_active', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_name')");
$existe = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Configuraciones a insertar
$configuraciones = [
    'smtp_active' => '0',
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => '587',
    'smtp_user' => 'user@example.com',
    'smtp_pass' => '',
    'email_from' => 'noreply@example.com',
    'email_name' => 'Sistema de Consentimientos'
];

try {
    $db->beginTransaction();
    
    // Insertar solo las configuraciones que no existen
    $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)");
    foreach ($configuraciones as $clave => $valor) {
        if (!in_array($clave, $existe)) {
            $stmt->execute(['clave' => $clave, 'valor' => $valor]);
        }
    }
    
    $db->commit();
    setAlert('success', 'Configuración SMTP instalada correctamente.');
} catch (PDOException $e) {
    $db->rollBack();
    setAlert('danger', 'Error al instalar la configuración SMTP: ' . $e->getMessage());
}

// Preparar datos para la página
$title = "Instalación de Configuración SMTP";
$breadcrumbs = [
    'Consentimientos' => '../consentimientos/listar.php',
    'Configuración SMTP' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <h2 class="mb-4">Instalación de Configuración SMTP</h2>
                    
                    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
                    
                    <div class="mb-4">
                        <p>La configuración SMTP ha sido instalada. Ahora puede configurar los parámetros SMTP en la sección de configuración.</p>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/modules/configuracion/list.php" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">settings</span>Ir a Configuración
                        </a>
                        <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-outline-secondary ms-2">
                            <span class="material-symbols-rounded me-2">list</span>Volver a Consentimientos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar el buffer de salida y mostrar el contenido
endPageContent();
?> 