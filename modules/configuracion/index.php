<?php
// Auto-detector de problemas y redireccionador
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Configuración del Sistema";

// Iniciar captura del contenido de la página
startPageContent();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= heroicon_outline('cog', 'heroicon-sm me-2') ?> Configuración del Sistema</h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Configuración</li>
    </ol>

    <div class="row">
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= heroicon_outline('building-office', 'heroicon-sm me-2') ?> Configuración General</h5>
                    <p class="card-text">Ajustes generales de la clínica y del sistema.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/configuracion/list.php" class="btn btn-primary">
                            <?= heroicon_outline('adjustments-horizontal', 'heroicon-sm') ?> Gestionar Configuración
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= heroicon_outline('user-group', 'heroicon-sm me-2') ?> Usuarios</h5>
                    <p class="card-text">Administración de usuarios del sistema y sus permisos.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/usuarios/index.php" class="btn btn-primary">
                            <?= heroicon_outline('users', 'heroicon-sm') ?> Gestionar Usuarios
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= heroicon_outline('envelope', 'heroicon-sm me-2') ?> Configuración de Email</h5>
                    <p class="card-text">Configuración del servidor SMTP para envío de emails.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/configuracion/smtp.php" class="btn btn-primary">
                            <?= heroicon_outline('paper-airplane', 'heroicon-sm') ?> Configurar SMTP
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= heroicon_outline('swatch', 'heroicon-sm me-2') ?> Recursos Visuales</h5>
                    <p class="card-text">Gestión de los elementos visuales de la aplicación.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/configuracion/guia_estilo.php" class="btn btn-outline-primary">
                            <?= heroicon_outline('palette', 'heroicon-sm') ?> Guía de Estilo
                        </a>
                        <a href="<?= BASE_URL ?>/modules/configuracion/iconos_galeria.php" class="btn btn-outline-primary">
                            <?= heroicon_outline('squares-2x2', 'heroicon-sm') ?> Galería de Iconos
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= heroicon_outline('arrow-path', 'heroicon-sm me-2') ?> Copias de Seguridad</h5>
                    <p class="card-text">Gestión de backups y restauración de datos.</p>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/modules/configuracion/backup.php" class="btn btn-primary">
                            <?= heroicon_outline('archive-box', 'heroicon-sm') ?> Gestionar Backups
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 