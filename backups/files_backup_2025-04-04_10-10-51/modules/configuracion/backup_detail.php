<?php
/**
 * Detalles de Copia de Seguridad
 * 
 * Este módulo muestra los detalles de una copia de seguridad específica.
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
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'ID de copia de seguridad no válido.');
    header('Location: ' . BASE_URL . '/modules/configuracion/backup.php');
    exit;
}

$backupId = (int)$_GET['id'];

// TODO: Implementar la obtención de detalles del backup

// Título de la página
$titulo = 'Detalles de Copia de Seguridad';
$breadcrumb = [
    ['nombre' => 'Inicio', 'enlace' => BASE_URL],
    ['nombre' => 'Configuración', 'enlace' => BASE_URL . '/modules/configuracion'],
    ['nombre' => 'Copias de Seguridad', 'enlace' => BASE_URL . '/modules/configuracion/backup.php'],
    ['nombre' => $titulo, 'enlace' => '']
];

// Incluir header
include '../../includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">info</span><?= $titulo ?> #<?= $backupId ?>
        </h1>
        <a href="<?= BASE_URL ?>/modules/configuracion/backup.php" class="btn btn-outline-primary">
            <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Copias de Seguridad
        </a>
    </div>
    
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-center py-5">
                <span class="material-symbols-rounded display-1 text-primary">backup</span>
                <h2 class="mt-4">Detalles de la Copia de Seguridad #<?= $backupId ?></h2>
                <p class="lead mb-4">La funcionalidad de detalles de copias de seguridad está siendo implementada.</p>
                <div class="alert alert-info">
                    <p><strong>Importante:</strong> Esta página mostrará información detallada sobre la copia de seguridad seleccionada, incluyendo:</p>
                    <ul class="text-start mb-0">
                        <li>Información general (fecha, tamaño, estado)</li>
                        <li>Configuración utilizada para la copia</li>
                        <li>Log de eventos durante la creación</li>
                        <li>Opciones para verificar, restaurar o descargar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?> 