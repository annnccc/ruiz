<?php
/**
 * Restauración de Copia de Seguridad
 * 
 * Este módulo gestiona el proceso de restauración de una copia de seguridad.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Comprobar permisos de acceso (solo administradores)
if (!isLoggedIn() || !esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta sección.');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar ID de copia de seguridad
if (!isset($_POST['backup_id']) || !is_numeric($_POST['backup_id'])) {
    setAlert('danger', 'ID de copia de seguridad no válido.');
    header('Location: ' . BASE_URL . '/modules/configuracion/backup.php');
    exit;
}

$backupId = (int)$_POST['backup_id'];

// TODO: Implementar la restauración de la copia de seguridad cuando la funcionalidad esté completa

// Por ahora, solo devolvemos un mensaje informativo
setAlert('info', 'La funcionalidad de restauración está siendo implementada. Por favor, inténtalo más tarde.');
header('Location: ' . BASE_URL . '/modules/configuracion/backup.php');
exit; 