<?php
// Determinar la página actual para resaltar el elemento de menú activo
$current_uri = $_SERVER['REQUEST_URI'];
$current_page = '';

if (strpos($current_uri, '/pacientes/') !== false) {
    $current_page = 'pacientes';
} elseif (strpos($current_uri, '/citas/') !== false) {
    $current_page = 'citas';
} elseif (strpos($current_uri, '/servicios/') !== false) {
    $current_page = 'servicios';
} elseif (strpos($current_uri, '/calendario/') !== false) {
    $current_page = 'calendario';
} elseif (strpos($current_uri, '/recordatorios/') !== false) {
    $current_page = 'recordatorios';
} elseif (strpos($current_uri, '/consentimientos/') !== false) {
    $current_page = 'consentimientos';
} elseif (strpos($current_uri, '/escalas/') !== false) {
    $current_page = 'escalas';
} elseif (strpos($current_uri, '/configuracion/') !== false) {
    $current_page = 'configuracion';
} elseif (strpos($current_uri, 'index.php') !== false || $current_uri == '/' || $current_uri == '') {
    $current_page = 'home';
}

// Generar la URL base para los enlaces
$server_host = $_SERVER['HTTP_HOST'];
$server_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// URL base debería ser siempre /ruiz si accedemos a través de localhost
$site_url = "$server_protocol://$server_host" . BASE_URL;

// Nos aseguramos de que site_url siempre termine sin /
$site_url = rtrim($site_url, '/');

// Obtener el nombre del sistema desde la configuración
$nombre_sistema = APP_NAME;
try {
    $db = getDB();
    $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'nombre_sistema'");
    if ($nombre = $stmt->fetch(PDO::FETCH_COLUMN)) {
        $nombre_sistema = $nombre;
    }
} catch (PDOException $e) {
    // Usar el valor por defecto
}

// Obtener la ruta del logo desde la configuración
$logo_url = $site_url . '/assets/img/logo.png';
try {
    $db = getDB();
    $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'logo'");
    if ($logo = $stmt->fetch(PDO::FETCH_COLUMN)) {
        $logo_url = $site_url . '/' . $logo;
    }
} catch (PDOException $e) {
    // Usar el valor por defecto
}
?>

<div class="sidebar" id="sidebar">
    <!-- Logo y Nombre del Sistema -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center justify-content-center w-100">
            <img src="<?= $logo_url ?>" alt="Logo" onerror="this.src='<?= $site_url ?>/assets/img/logo-placeholder.png'; this.onerror=null;">
        </div>
    </div>
    
    <?php if (isset($_SESSION['usuario_id'])): // Solo mostrar menú si el usuario está autenticado ?>
    <!-- Menú Principal -->
    <ul class="sidebar-menu">
        <!-- Inicio -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/" class="sidebar-link <?= ($current_page == 'home') ? 'active' : '' ?>" aria-label="Dashboard">
                <?= heroicon_outline('dashboard', 'sidebar-icon') ?>
                <span class="link-text">Dashboard</span>
            </a>
        </li>
        
        <!-- Pacientes -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/pacientes/list.php" class="sidebar-link <?= ($current_page == 'pacientes') ? 'active' : '' ?>" aria-label="Pacientes">
                <?= heroicon_outline('users', 'sidebar-icon') ?>
                <span class="link-text">Pacientes</span>
            </a>
        </li>
        
        <!-- Citas -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/citas/list.php" class="sidebar-link <?= ($current_page == 'citas') ? 'active' : '' ?>" aria-label="Citas">
                <?= heroicon_outline('calendar', 'sidebar-icon') ?>
                <span class="link-text">Citas</span>
            </a>
        </li>
        
        <!-- Agenda con menú desplegable -->
        <li class="sidebar-item">
            <div class="dropdown-toggle" id="dropdownMenuAgenda" data-bs-toggle="collapse" href="#collapseAgenda" role="button" aria-expanded="false" aria-controls="collapseAgenda">
                <div class="d-flex align-items-center">
                    <?= heroicon_outline('calendar-days', 'sidebar-icon') ?>
                    <span>Agenda</span>
                </div>
                <?= heroicon_outline('chevron-down', 'ms-auto') ?>
            </div>
            <ul id="collapseAgenda" class="collapse sidebar-submenu">
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/citas/list.php" class="sidebar-sublink">
                        <?= heroicon_outline('clipboard-document', 'sidebar-icon') ?>
                        <span class="link-text">Listado de Citas</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/bono/list.php" class="sidebar-sublink">
                        <?= heroicon_outline('ticket', 'sidebar-icon') ?>
                        <span class="link-text">Bonos</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/calendario/index.php" class="sidebar-sublink">
                        <?= heroicon_outline('calendar-days', 'sidebar-icon') ?>
                        <span class="link-text">Calendario</span>
                    </a>
                </li>
            </ul>
        </li>
        
        <!-- Servicios -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/servicios/list.php" class="sidebar-link <?= ($current_page == 'servicios') ? 'active' : '' ?>" aria-label="Servicios">
                <?= heroicon_outline('medical-services', 'sidebar-icon') ?>
                <span class="link-text">Servicios</span>
            </a>
        </li>
        
        <!-- Recordatorios -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/recordatorios/configurar.php" class="sidebar-link <?= ($current_page == 'recordatorios') ? 'active' : '' ?>" aria-label="Recordatorios">
                <?= heroicon_outline('bell', 'sidebar-icon') ?>
                <span class="link-text">Recordatorios</span>
            </a>
        </li>
        
        <!-- Consentimientos -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/consentimientos/listar.php" class="sidebar-link <?= ($current_page == 'consentimientos') ? 'active' : '' ?>" aria-label="Consentimientos">
                <?= heroicon_outline('document-check', 'sidebar-icon') ?>
                <span class="link-text">Consentimientos</span>
            </a>
        </li>
        
        <!-- Escalas Psicológicas -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/escalas/index.php" class="sidebar-link <?= ($current_page == 'escalas') ? 'active' : '' ?>" aria-label="Escalas Psicológicas">
                <?= heroicon_outline('brain', 'sidebar-icon') ?>
                <span class="link-text">Escalas</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/facturacion/list.php" class="sidebar-link <?= ($current_page == 'facturacion') ? 'active' : '' ?>" aria-label="Facturación">
                <?= heroicon_outline('banknotes', 'sidebar-icon') ?>
                <span class="link-text">Facturación</span>
            </a>
        </li>
        
        <!-- Documentos -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/documentos/list.php" class="sidebar-link <?= ($current_page == 'documentos') ? 'active' : '' ?>" aria-label="Documentos">
                <?= heroicon_outline('document-text', 'sidebar-icon') ?>
                <span class="link-text">Documentos</span>
            </a>
        </li>
        
        <?php if (esAdmin()): // Opción de configuración solo para administradores ?>
        <!-- Configuración -->
        <li class="sidebar-item">
            <div class="dropdown-toggle" id="dropdownMenuConfiguracion" data-bs-toggle="collapse" href="#collapseConfiguracion" role="button" aria-expanded="false" aria-controls="collapseConfiguracion">
                <div class="d-flex align-items-center">
                    <?= heroicon_outline('cog', 'sidebar-icon') ?>
                    <span>Configuración</span>
                </div>
                <?= heroicon_outline('chevron-down', 'ms-auto') ?>
            </div>
            <ul id="collapseConfiguracion" class="collapse sidebar-submenu">
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/configuracion/list.php" class="sidebar-sublink">
                        <?= heroicon_outline('adjustments-horizontal', 'sidebar-icon') ?>
                        <span class="link-text">General</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/configuracion/smtp.php" class="sidebar-sublink">
                        <?= heroicon_outline('envelope', 'sidebar-icon') ?>
                        <span class="link-text">SMTP</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/configuracion/list.php#usuarios" class="sidebar-sublink">
                        <?= heroicon_outline('user-group', 'sidebar-icon') ?>
                        <span class="link-text">Usuarios</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/videoconsulta" class="sidebar-sublink">
                        <?= heroicon_outline('video-camera', 'sidebar-icon') ?>
                        <span class="link-text">Videoconsulta</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/configuracion/iconos_galeria.php" class="sidebar-sublink">
                        <?= heroicon_outline('squares-2x2', 'sidebar-icon') ?>
                        <span class="link-text">Iconos</span>
                    </a>
                </li>
                <li class="sidebar-subitem">
                    <a href="<?= $site_url ?>/modules/configuracion/guia_estilo.php" class="sidebar-sublink">
                        <?= heroicon_outline('palette', 'sidebar-icon') ?>
                        <span class="link-text">Guía de Estilo</span>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
        
        <div class="sidebar-divider"></div>
        
        <!-- Acceso Rápido -->
        <div class="sidebar-heading">Acceso Rápido</div>
        
        <!-- Nuevo Paciente -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/pacientes/create.php" class="sidebar-link" aria-label="Nuevo Paciente">
                <?= heroicon_outline('user-plus', 'sidebar-icon') ?>
                <span class="link-text">Nuevo Paciente</span>
            </a>
        </li>
        
        <!-- Nueva Cita -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/citas/create.php" class="sidebar-link" aria-label="Nueva Cita">
                <?= heroicon_outline('calendar-plus', 'sidebar-icon') ?>
                <span class="link-text">Nueva Cita</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/facturacion/annual_report.php" class="sidebar-link" aria-label="Informe Anual">
                <?= heroicon_outline('chart-bar', 'sidebar-icon') ?>
                <span class="link-text">Informe Anual</span>
            </a>
        </li>
        
        <!-- Exportación de Datos -->
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/modules/export/index.php" class="sidebar-link <?= (strpos($current_uri, '/export/') !== false) ? 'active' : '' ?>" aria-label="Exportación de Datos">
                <?= heroicon_outline('arrow-down-tray', 'sidebar-icon') ?>
                <span class="link-text">Exportar Datos</span>
            </a>
        </li>
        
        <div class="sidebar-divider"></div>
        
        <!-- Opción de salir -->
        <li class="sidebar-item mt-auto">
            <a href="<?= $site_url ?>/logout.php" class="sidebar-link text-danger" aria-label="Cerrar Sesión">
                <?= heroicon_outline('arrow-right-on-rectangle', 'sidebar-icon') ?>
                <span class="link-text">Cerrar Sesión</span>
            </a>
        </li>
    </ul>
    <?php else: // Si el usuario no está autenticado, mostrar solo el botón de login ?>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="<?= $site_url ?>/login.php" class="sidebar-link" aria-label="Iniciar Sesión">
                <?= heroicon_outline('login', 'sidebar-icon') ?>
                <span class="link-text">Iniciar Sesión</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>
</div>

<style>
.sidebar-brand {
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    height: var(--header-height);
    background-color: var(--white-color);
    border-bottom: 1px solid var(--border-color);
}
</style> 