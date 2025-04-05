<?php
// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario ha iniciado sesión
requiereLogin();
requiereAdmin();

// Título y breadcrumbs para la página
$titulo_pagina = "Instalación de Configuración SMTP";
$breadcrumbs = [
    ['nombre' => 'Inicio', 'enlace' => '../../index.php'],
    ['nombre' => 'Configuración', 'enlace' => '../configuracion/list.php'],
    ['nombre' => 'Instalar SMTP', 'enlace' => '#']
];

$mensaje = "";
$tipo_alerta = "";

try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Verificar si ya existen configuraciones SMTP
    $stmt = $db->query("SELECT COUNT(*) FROM configuracion WHERE clave LIKE 'smtp_%'");
    $smtp_config_count = $stmt->fetchColumn();
    
    // Si no hay configuraciones SMTP, crearlas
    if ($smtp_config_count == 0) {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Configuraciones a insertar
        $configuraciones = [
            ['smtp_active', '0'],
            ['smtp_host', ''],
            ['smtp_port', '587'],
            ['smtp_user', ''],
            ['smtp_pass', ''],
            ['email_from', 'no-reply@' . $_SERVER['HTTP_HOST']],
            ['email_name', '']
        ];
        
        // Preparar la consulta
        $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)");
        
        // Insertar cada configuración
        foreach ($configuraciones as $config) {
            // Verificar si la configuración ya existe
            $check = $db->prepare("SELECT COUNT(*) FROM configuracion WHERE clave = ?");
            $check->execute([$config[0]]);
            
            if ($check->fetchColumn() == 0) {
                $stmt->execute($config);
            }
        }
        
        // Confirmar cambios
        $db->commit();
        
        $mensaje = "¡La configuración SMTP ha sido instalada correctamente!";
        $tipo_alerta = "success";
    } else {
        $mensaje = "La configuración SMTP ya está instalada.";
        $tipo_alerta = "info";
    }
} catch (PDOException $e) {
    // Revertir cambios en caso de error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $mensaje = "Error al instalar la configuración SMTP: " . $e->getMessage();
    $tipo_alerta = "danger";
}

// Incluir el header
include_once '../../includes/header.php';

// Combinar con valores por defecto
$default_config = [
    'smtp_active' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_pass' => '',
    'email_from' => 'no-reply@' . $_SERVER['HTTP_HOST'],
    'email_name' => ''
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="col">
        <!-- Breadcrumbs -->
        <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
        
        <!-- Alertas -->
        <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
        
        <!-- Título -->
        <h1 class="mb-4">
            <span class="material-symbols-rounded me-2">mail</span><?php echo $titulo_pagina; ?>
        </h1>
        
        <!-- Información -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <span class="material-symbols-rounded me-2">info</span>Información
                </h5>
            </div>
            <div class="card-body">
                <p>La configuración SMTP permite el envío de correos electrónicos desde la aplicación, lo cual es necesario para:</p>
                <ul>
                    <li>Enviar recordatorios de citas a pacientes</li>
                    <li>Enviar consentimientos informados</li>
                    <li>Notificaciones y alertas del sistema</li>
                </ul>
                <div class="mt-4">
                    <a href="../../index.php" class="btn btn-secondary">
                        <span class="material-symbols-rounded me-2">home</span>Volver al inicio
                    </a>
                    <a href="../configuracion/smtp.php" class="btn btn-primary">
                        <span class="material-symbols-rounded me-2">settings</span>Configurar SMTP
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 