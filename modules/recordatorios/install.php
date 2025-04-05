<?php
/**
 * Script de instalación de las tablas para el sistema de recordatorios
 */
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere autenticación y derechos de administrador
requiereLogin();
if (!esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta página.');
    redirect(BASE_URL);
}

$resultado_instalacion = false;
$mensaje_error = '';

try {
    $db = getDB();
    
    // Crear tabla de configuración de recordatorios
    $sql = "
    CREATE TABLE IF NOT EXISTS `recordatorios_config` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `activo` tinyint(1) NOT NULL DEFAULT 1,
      `dias_anticipacion` int(11) NOT NULL DEFAULT 1,
      `hora_envio` time NOT NULL DEFAULT '08:00:00',
      `asunto_email` varchar(255) NOT NULL DEFAULT 'Recordatorio de su cita',
      `mensaje_email` text NOT NULL,
      `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $db->exec($sql);
    
    // Insertar configuración predeterminada si no existe
    $stmt = $db->query("SELECT COUNT(*) FROM recordatorios_config");
    if ($stmt->fetchColumn() == 0) {
        $sql = "
        INSERT INTO `recordatorios_config` 
        (`activo`, `dias_anticipacion`, `hora_envio`, `asunto_email`, `mensaje_email`) VALUES
        (1, 1, '08:00:00', 'Recordatorio de su cita médica', 
        '<p>Estimado/a {paciente},</p>
        <p>Le recordamos que tiene una cita programada para el día <strong>{fecha}</strong> a las <strong>{hora}</strong> para <strong>{servicio}</strong>.</p>
        <p>Por favor, llegue con 10 minutos de anticipación. Si necesita cancelar o reprogramar, contáctenos lo antes posible.</p>
        <p>Atentamente,<br>El equipo de {clinica}</p>');
        ";
        
        $db->exec($sql);
    }
    
    // Crear tabla de recordatorios enviados
    $sql = "
    CREATE TABLE IF NOT EXISTS `recordatorios_enviados` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cita_id` int(11) NOT NULL,
      `tipo` enum('email','sms') NOT NULL DEFAULT 'email',
      `fecha_envio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `estado` enum('enviado','error') NOT NULL,
      `mensaje_error` text DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `cita_id` (`cita_id`),
      CONSTRAINT `fk_recordatorios_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $db->exec($sql);
    
    setAlert('success', 'Tablas de recordatorios creadas correctamente.');
    $resultado_instalacion = true;
} catch (PDOException $e) {
    setAlert('danger', 'Error al crear las tablas: ' . $e->getMessage());
    $mensaje_error = $e->getMessage();
}

// Preparar datos para la página
$title = "Instalación de Recordatorios";
$breadcrumbs = [
    'Recordatorios' => '../recordatorios/configurar.php',
    'Instalación' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <h2 class="mb-4">Instalación de Recordatorios</h2>
                    
                    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
                    
                    <?php if ($resultado_instalacion): ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="text-success">
                                    <span class="material-symbols-rounded" style="font-size: 5rem;">check_circle</span>
                                </div>
                            </div>
                            <h4 class="text-success mb-3">Instalación completada</h4>
                            <p>Las tablas necesarias han sido creadas correctamente.</p>
                            <p>Serás redirigido a la página de configuración en 3 segundos...</p>
                            <meta http-equiv="refresh" content="3;url=<?= BASE_URL ?>/modules/recordatorios/configurar.php">
                        </div>
                        <div class="spinner-border text-primary mb-4" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="text-danger">
                                    <span class="material-symbols-rounded" style="font-size: 5rem;">error</span>
                                </div>
                            </div>
                            <h4 class="text-danger mb-3">Error en la instalación</h4>
                            <p>No se pudieron crear las tablas necesarias.</p>
                            <p class="text-danger"><?= htmlspecialchars($mensaje_error) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/modules/recordatorios/configurar.php" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">settings</span>Ir a configuración
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