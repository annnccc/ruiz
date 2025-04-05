<?php
/**
 * Historial de Copias de Seguridad
 * 
 * Este módulo muestra el historial completo de copias de seguridad realizadas
 * y permite filtrar por diferentes criterios.
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

// Título de la página
$titulo = 'Historial de Copias de Seguridad';
$breadcrumb = [
    ['nombre' => 'Inicio', 'enlace' => BASE_URL],
    ['nombre' => 'Configuración', 'enlace' => BASE_URL . '/modules/configuracion'],
    ['nombre' => 'Copias de Seguridad', 'enlace' => BASE_URL . '/modules/configuracion/backup.php'],
    ['nombre' => $titulo, 'enlace' => '']
];

// TODO: Implementar la obtención del historial desde la base de datos

// Incluir header
include '../../includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">history</span><?= $titulo ?>
        </h1>
        <a href="<?= BASE_URL ?>/modules/configuracion/backup.php" class="btn btn-outline-primary">
            <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Copias de Seguridad
        </a>
    </div>
    
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-center py-5">
                <span class="material-symbols-rounded display-1 text-primary">history</span>
                <h2 class="mt-4">Historial de Copias de Seguridad</h2>
                <p class="lead mb-4">La funcionalidad de historial de copias de seguridad está siendo implementada.</p>
                <div class="alert alert-info">
                    <p><strong>Importante:</strong> Esta página mostrará un historial completo de todas las copias de seguridad realizadas, con opciones para:</p>
                    <ul class="text-start mb-0">
                        <li>Filtrar por fecha, configuración, estado, etc.</li>
                        <li>Ver detalles de cada copia de seguridad</li>
                        <li>Descargar, verificar o restaurar copias de seguridad</li>
                        <li>Eliminar copias de seguridad antiguas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?> 