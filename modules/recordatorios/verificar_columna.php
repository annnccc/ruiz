<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere autenticación y derechos de administrador
requiereLogin();
if (!esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta página.');
    redirect(BASE_URL);
}

try {
    $db = getDB();
    
    // Verificar si existe la columna recordatorio_enviado en la tabla citas
    $columnaExiste = false;
    $columnas = $db->query("SHOW COLUMNS FROM citas")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($columnas as $columna) {
        if ($columna == 'recordatorio_enviado') {
            $columnaExiste = true;
            break;
        }
    }
    
    // Si la columna no existe, agregarla
    if (!$columnaExiste) {
        $db->exec("ALTER TABLE citas ADD COLUMN recordatorio_enviado TINYINT(1) DEFAULT 0");
        setAlert('success', 'Se ha agregado la columna recordatorio_enviado a la tabla citas.');
    } else {
        setAlert('info', 'La columna recordatorio_enviado ya existe en la tabla citas.');
    }
    
    // Verificar estado de las tablas de recordatorios
    $tablaExiste = false;
    $tablas = $db->query("SHOW TABLES LIKE 'recordatorios_%'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('recordatorios_config', $tablas) && in_array('recordatorios_enviados', $tablas)) {
        setAlert('info', 'Las tablas de recordatorios están correctamente instaladas.');
    } else {
        setAlert('warning', 'Algunas tablas de recordatorios no existen. Ejecute el script de instalación.');
    }
    
    // Verificar configuración SMTP
    $stmt = $db->prepare("SELECT COUNT(*) FROM configuracion WHERE clave IN ('smtp_active', 'smtp_host', 'smtp_user')");
    $stmt->execute();
    $configuracionSMTP = $stmt->fetchColumn() == 3;
    
    if ($configuracionSMTP) {
        $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = 'smtp_active'");
        $stmt->execute();
        $smtpActivo = $stmt->fetchColumn() == '1';
        
        if ($smtpActivo) {
            setAlert('info', 'El servicio SMTP está configurado y activado.');
        } else {
            setAlert('warning', 'El servicio SMTP está configurado pero no activado. Active el SMTP en la configuración.');
        }
    } else {
        setAlert('warning', 'El servicio SMTP no está completamente configurado. Configure el SMTP en la sección de configuración.');
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error: ' . $e->getMessage());
}

redirect(BASE_URL . '/modules/recordatorios/enviar.php');
?> 