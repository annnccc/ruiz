<?php
/**
 * Página para configurar los widgets del dashboard
 * Permite al usuario elegir qué widgets mostrar y en qué orden
 */

// Comprobar que estamos en el contexto correcto
$relativePath = '../../';

// Incluir archivos necesarios
require_once $relativePath . 'includes/config.php';
require_once $relativePath . 'includes/db.php';
require_once $relativePath . 'includes/functions.php';
require_once __DIR__ . '/dashboard_functions.php';

// Verificar que el usuario está autenticado
requiereLogin();

// Verificar que las tablas existen
if (!verificarTablasDashboard()) {
    // Redirigir a la página de instalación
    header('Location: ' . $relativePath . 'modules/dashboard/install.php');
    exit;
}

// Obtener todos los widgets disponibles
$widgetsDisponibles = obtenerWidgetsDisponibles();

// Obtener la configuración actual del usuario
$usuario_id = $_SESSION['usuario_id'];
$configuracionActual = obtenerConfigDashboard($usuario_id);

// Título de la página
$titulo_pagina = "Configurar Dashboard";

// Iniciar captura del contenido
startPageContent();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0"><span class="material-symbols-rounded me-2 align-middle">dashboard_customize</span> Personalizar Dashboard</h4>
                        <div>
                            <a href="<?= $relativePath ?>" class="btn btn-outline-secondary me-2">
                                <span class="material-symbols-rounded me-1">cancel</span> Cancelar
                            </a>
                            <button id="btnSaveConfig" class="btn btn-primary">
                                <span class="material-symbols-rounded me-1">save</span> Guardar cambios
                            </button>
                        </div>
                    </div>
                    
                    <p class="text-muted mb-4">Personaliza tu dashboard arrastrando los widgets o activando/desactivando los que necesites. Los cambios se guardarán automáticamente.</p>
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <span class="material-symbols-rounded me-2 fs-4">lightbulb</span>
                            <div>
                                <strong>Consejo:</strong> Arrastra los widgets para reordenarlos. Puedes ocultar widgets usando el botón en la esquina superior derecha de cada uno.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Widgets disponibles</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="widgetsDisponibles">
                        <?php foreach ($widgetsDisponibles as $widget): ?>
                            <?php
                            // Comprobar si el widget está activo en la configuración actual
                            $isActive = false;
                            $size = $widget['tamano_predeterminado'];
                            
                            foreach ($configuracionActual as $configWidget) {
                                if ($configWidget['codigo'] === $widget['codigo']) {
                                    $isActive = $configWidget['activo'];
                                    $size = $configWidget['tamano'];
                                    break;
                                }
                            }
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 widget-item <?= $isActive ? 'border-primary' : 'border-light' ?>" 
                                    data-widget-code="<?= htmlspecialchars($widget['codigo']) ?>"
                                    data-widget-size="<?= htmlspecialchars($size) ?>"
                                    data-widget-active="<?= $isActive ? 'true' : 'false' ?>">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="widget-icon me-3 p-3 rounded 
                                            <?= $isActive ? 'bg-primary bg-opacity-10' : 'bg-light' ?>">
                                            <span class="material-symbols-rounded 
                                                <?= $isActive ? 'text-primary' : 'text-muted' ?>">
                                                <?= htmlspecialchars($widget['icono'] ?: 'widgets') ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($widget['nombre']) ?></h6>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($widget['descripcion'] ?: 'Widget del dashboard') ?></p>
                                        </div>
                                        <div class="ms-auto">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input widget-active-toggle" type="checkbox" 
                                                    id="widget_<?= $widget['codigo'] ?>" 
                                                    <?= $isActive ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="widget_<?= $widget['codigo'] ?>">
                                                    <span class="visually-hidden"><?= $isActive ? 'Activado' : 'Desactivado' ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Vista previa del dashboard</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex">
                            <span class="material-symbols-rounded me-2 fs-4">info</span>
                            <div>
                                Esta es una vista previa de cómo quedará tu dashboard. Los widgets activos aparecerán en el orden establecido.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="dashboardPreview">
                        <!-- La vista previa se generará dinámicamente con JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const widgetsDisponibles = document.getElementById('widgetsDisponibles');
    const dashboardPreview = document.getElementById('dashboardPreview');
    const btnSaveConfig = document.getElementById('btnSaveConfig');
    
    // Inicializar vista previa
    updateDashboardPreview();
    
    // Event listeners para cambios en los toggles
    document.querySelectorAll('.widget-active-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const widgetCard = this.closest('.widget-item');
            const isActive = this.checked;
            
            widgetCard.dataset.widgetActive = isActive ? 'true' : 'false';
            widgetCard.classList.toggle('border-primary', isActive);
            widgetCard.classList.toggle('border-light', !isActive);
            
            const iconContainer = widgetCard.querySelector('.widget-icon');
            iconContainer.classList.toggle('bg-primary', isActive);
            iconContainer.classList.toggle('bg-opacity-10', isActive);
            iconContainer.classList.toggle('bg-light', !isActive);
            
            const icon = iconContainer.querySelector('.material-symbols-rounded');
            icon.classList.toggle('text-primary', isActive);
            icon.classList.toggle('text-muted', !isActive);
            
            // Actualizar vista previa
            updateDashboardPreview();
        });
    });
    
    // Guardar configuración
    btnSaveConfig.addEventListener('click', function() {
        saveConfiguration();
    });
    
    // Función para actualizar la vista previa
    function updateDashboardPreview() {
        // Limpiar vista previa
        dashboardPreview.innerHTML = '';
        
        // Obtener widgets activos
        const activeWidgets = [];
        document.querySelectorAll('.widget-item').forEach(widget => {
            const isActive = widget.dataset.widgetActive === 'true';
            if (isActive) {
                activeWidgets.push({
                    codigo: widget.dataset.widgetCode,
                    tamano: widget.dataset.widgetSize,
                    nombre: widget.querySelector('h6').textContent,
                    icono: widget.querySelector('.material-symbols-rounded').textContent.trim()
                });
            }
        });
        
        // Generar elementos para la vista previa
        if (activeWidgets.length === 0) {
            dashboardPreview.innerHTML = `
                <div class="col-12 text-center py-5">
                    <span class="material-symbols-rounded d-block mb-3" style="font-size: 3rem; color: var(--text-muted);">
                        dashboard_customize
                    </span>
                    <h5 class="text-muted">No hay widgets activos</h5>
                    <p class="text-muted">Activa algunos widgets para ver cómo quedará tu dashboard.</p>
                </div>
            `;
            return;
        }
        
        // Renderizar widgets
        activeWidgets.forEach(widget => {
            dashboardPreview.innerHTML += `
                <div class="${widget.tamano} mb-4">
                    <div class="card border-0 shadow-sm preview-widget">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <span class="material-symbols-rounded me-2 text-primary">${widget.icono}</span>
                                    ${widget.nombre}
                                </h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4 text-muted">
                                <span class="material-symbols-rounded d-block mb-2" style="font-size: 2rem;">preview</span>
                                <p>Vista previa del contenido para ${widget.nombre}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    // Función para guardar la configuración
    function saveConfiguration() {
        // Mostrar indicador de carga
        btnSaveConfig.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Guardando...';
        btnSaveConfig.disabled = true;
        
        // Recopilar datos de los widgets
        const widgets = [];
        document.querySelectorAll('.widget-item').forEach(widget => {
            // Asegurarse de que activo sea un booleano verdadero, no un string "true"
            const isActive = widget.dataset.widgetActive === 'true';
            
            widgets.push({
                codigo: widget.dataset.widgetCode,
                tamano: widget.dataset.widgetSize,
                activo: isActive
            });
        });
        
        const requestData = { widgets: widgets };
        
        // Enviar configuración al servidor
        fetch('<?= $relativePath ?>modules/dashboard/save_config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Error al guardar configuración: ' + response.status);
            }
            return response.text();
        })
        .then(function(text) {
            Swal.fire({
                title: '¡Configuración guardada!',
                text: 'Tu dashboard se ha personalizado correctamente.',
                icon: 'success',
                confirmButtonText: 'Ver dashboard',
                showCancelButton: true,
                cancelButtonText: 'Seguir configurando'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= $relativePath ?>';
                }
            });
        })
        .catch(function(error) {
            Swal.fire({
                title: 'Error',
                text: 'Ha ocurrido un error al guardar la configuración: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        })
        .finally(() => {
            // Restaurar botón
            btnSaveConfig.innerHTML = '<span class="material-symbols-rounded me-1">save</span> Guardar cambios';
            btnSaveConfig.disabled = false;
        });
    }
});
</script>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 