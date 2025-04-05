<!-- Header que se adapta correctamente cuando el sidebar se colapsa -->
<header class="border-bottom bg-white">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center flex-grow-1">
                <!-- Botón para colapsar/expandir sidebar en desktop -->
                <button class="btn btn-icon sidebar-toggle d-none d-lg-inline-flex me-3" id="sidebarToggle" aria-label="Toggle sidebar">
                    <span class="material-symbols-rounded" style="line-height: 1; display: block;">chevron_left</span>
                </button>
                
                <?php
                // Obtener el título según la URL actual
                $page_title = "Inicio";
                $current_uri = $_SERVER['REQUEST_URI'];
                
                if (strpos($current_uri, '/pacientes/list.php') !== false) {
                    $page_title = "Listado de Pacientes";
                } elseif (strpos($current_uri, '/pacientes/create.php') !== false) {
                    $page_title = "Nuevo Paciente";
                } elseif (strpos($current_uri, '/pacientes/view.php') !== false) {
                    $page_title = "Detalle de Paciente";
                } elseif (strpos($current_uri, '/pacientes/edit.php') !== false) {
                    $page_title = "Editar Paciente";
                } elseif (strpos($current_uri, '/citas/list.php') !== false) {
                    $page_title = "Listado de Citas";
                } elseif (strpos($current_uri, '/citas/create.php') !== false) {
                    $page_title = "Nueva Cita";
                } elseif (strpos($current_uri, '/citas/view.php') !== false) {
                    $page_title = "Detalle de Cita";
                } elseif (strpos($current_uri, '/citas/edit.php') !== false) {
                    $page_title = "Editar Cita";
                } elseif (strpos($current_uri, '/calendario/') !== false) {
                    $page_title = "Calendario";
                } elseif (strpos($current_uri, '/configuracion/') !== false) {
                    $page_title = "Configuración";
                }
                ?>
                
                <!-- Barra de búsqueda global (ahora más grande y a la izquierda) -->
                <div class="global-search-container d-none d-md-block flex-grow-1 me-4">
                    <form action="<?= BASE_URL ?>/modules/busqueda/results.php" method="GET" class="global-search-form">
                        <div class="input-group">
                            <input type="text" class="form-control global-search-input" 
                                   placeholder="Buscar pacientes, citas..." 
                                   name="q" 
                                   id="globalSearchInput"
                                   autocomplete="off">
                            <button class="btn btn-primary" type="submit">
                                <span class="material-symbols-rounded">search</span>
                            </button>
                        </div>
                    </form>
                    <div id="globalSearchResults" class="global-search-results shadow d-none"></div>
                </div>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span class="text-muted d-none d-md-inline">
                        <span class="material-symbols-rounded me-1 small">calendar_today</span> <?= date('d/m/Y') ?>
                    </span>
                </div>
                
                <!-- Botón de búsqueda para móviles -->
                <div class="d-md-none me-3">
                    <button class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#searchModal" aria-label="Buscar">
                        <span class="material-symbols-rounded">search</span>
                    </button>
                </div>
                
                <!-- Notificaciones (ahora junto a los otros controles de usuario) -->
                <div class="notifications-container me-3 dropdown">
                    <button class="notification-toggle" id="notificationsDropdown" 
                           data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones">
                        <span class="material-symbols-rounded">notifications</span>
                        <span class="notifications-count d-none">0</span>
                    </button>
                    
                    <div class="dropdown-menu dropdown-menu-end shadow-sm p-0 notifications-dropdown" aria-labelledby="notificationsDropdown">
                        <div class="notifications-header d-flex justify-content-between align-items-center p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Notificaciones</h6>
                            <button class="btn btn-sm btn-link mark-all-read text-decoration-none p-0">
                                Marcar todas como leídas
                            </button>
                        </div>
                        <div class="notifications-body">
                            <!-- Las notificaciones se cargarán aquí vía AJAX -->
                            <div class="notifications-loading text-center p-4">
                                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <div class="mt-2">Cargando notificaciones...</div>
                            </div>
                        </div>
                        <div class="notifications-footer p-3 py-3 text-center border-top">
                            <a href="<?= BASE_URL ?>/modules/citas/list.php" class="btn btn-sm btn-primary w-100">
                                <span class="material-symbols-rounded me-2 small">calendar_month</span>Ver todas las citas
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Toggle modo oscuro (ahora junto a los otros controles de usuario) -->
                <div class="theme-switch-wrapper me-3">
                    <button id="themeToggle" class="btn btn-icon" aria-label="Cambiar tema" title="Cambiar tema">
                        <span class="material-symbols-rounded theme-icon-light">light_mode</span>
                        <span class="material-symbols-rounded theme-icon-dark d-none">dark_mode</span>
                    </button>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="text-decoration-none dropdown-toggle dropdown-user" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (isset($_SESSION['usuario_nombre'])): ?>
                            <span class="d-none d-md-inline me-2"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                            <?php 
                            // Obtener la primera letra del nombre
                            $initial = strtoupper(substr($_SESSION['usuario_nombre'], 0, 1));
                            ?>
                            <div class="avatar-circle avatar-sm me-2 d-inline-flex align-items-center justify-content-center" data-initial="<?= $initial ?>">
                                <span><?= $initial ?></span>
                            </div>
                        <?php else: ?>
                            <div class="avatar-circle avatar-sm me-2 d-inline-flex align-items-center justify-content-center">
                                <span class="material-symbols-rounded">account_circle</span>
                            </div>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <li>
                                <div class="dropdown-item-text text-center border-bottom pb-2">
                                    <span class="d-block fw-bold"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($_SESSION['usuario_email']) ?></small>
                                </div>
                            </li>
                            <?php if (esAdmin()): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/modules/configuracion/list.php">
                                        <span class="material-symbols-rounded me-2">settings</span>Configuración
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">
                                    <span class="material-symbols-rounded me-2">logout</span>Cerrar Sesión
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/login.php">
                                    <span class="material-symbols-rounded me-2">login</span>Iniciar Sesión
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Modal de búsqueda para móviles -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchModalLabel">Búsqueda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form action="<?= BASE_URL ?>/modules/busqueda/results.php" method="GET">
                    <div class="mb-3">
                        <label for="mobileSearchInput" class="form-label">Buscar en todo el sistema</label>
                        <div class="input-group shadow-sm rounded overflow-hidden">
                            <span class="input-group-text border-0 bg-light"><span class="material-symbols-rounded">search</span></span>
                            <input type="text" class="form-control border-0" 
                                   id="mobileSearchInput" 
                                   name="q" 
                                   placeholder="Pacientes, citas, servicios..." 
                                   aria-describedby="searchHelp" 
                                   minlength="2" 
                                   required>
                        </div>
                        <div id="searchHelp" class="form-text">
                            Ingresa al menos 2 caracteres para buscar.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="material-symbols-rounded me-2">search</span>Buscar
                    </button>
                </form>
                
                <div class="mt-4">
                    <h6>Búsquedas rápidas</h6>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="<?= BASE_URL ?>/modules/busqueda/results.php?q=pendiente" class="btn btn-sm btn-outline-primary">
                            Citas pendientes
                        </a>
                        <a href="<?= BASE_URL ?>/modules/busqueda/results.php?q=hoy" class="btn btn-sm btn-outline-primary">
                            Citas de hoy
                        </a>
                        <a href="<?= BASE_URL ?>/modules/busqueda/results.php?q=cancelada" class="btn btn-sm btn-outline-primary">
                            Cancelaciones
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 