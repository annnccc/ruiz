        <!-- Videoconsulta -->
        <li class="nav-item <?= isActiveMenu('videoconsulta') ? 'active' : '' ?>">
            <a class="nav-link" href="<?= BASE_URL ?>/modules/videoconsulta">
                <span class="material-symbols-rounded me-2">videocam</span>
                Videoconsulta
            </a>
        </li>
        
        <!-- Calendario -->
        <li class="nav-item <?= isActiveMenu('calendario') ? 'active' : '' ?>">
            <a class="nav-link" href="<?= BASE_URL ?>/modules/calendario">
                <span class="material-symbols-rounded me-2">calendar_month</span>
                Calendario
            </a>
        </li>
        
        <!-- Pacientes -->
        <li class="nav-item <?= isActiveMenu('pacientes') ? 'active' : '' ?>">
            <a class="nav-link" href="<?= BASE_URL ?>/modules/pacientes">
                <span class="material-symbols-rounded me-2">person</span>
                Pacientes
            </a>
        </li>
        
        <!-- Facturación -->
        <li class="nav-item <?= isActiveMenu('facturacion') ? 'active' : '' ?>">
            <a class="nav-link" href="<?= BASE_URL ?>/modules/facturacion">
                <span class="material-symbols-rounded me-2">receipt</span>
                Facturación
            </a>
        </li>
        
        <!-- Servicios --> 

        <!-- Configuración -->
        <li class="nav-item">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="material-symbols-rounded me-2">settings</span>
                Configuración
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/configuracion">
                    <span class="material-symbols-rounded me-2">tune</span>General
                </a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/videoconsulta">
                    <span class="material-symbols-rounded me-2">videocam</span>Videoconsulta
                </a></li>
            </ul>
        </li> 