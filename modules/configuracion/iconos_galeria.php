<?php
/**
 * Módulo de Configuración - Galería de Iconos
 * Muestra todos los iconos disponibles en el sistema con opciones de búsqueda.
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Galería de Iconos";

// Cargar la lista de iconos disponibles
function getIconsList($directory) {
    $icons = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'svg') {
                $icons[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        sort($icons); // Ordenar alfabéticamente
    }
    return $icons;
}

$outlineIcons = getIconsList(HEROICONS_PATH . '/outline');
$solidIcons = getIconsList(HEROICONS_PATH . '/solid');

// Iniciar captura del contenido de la página
startPageContent();

// CSS adicional para esta página
$extra_css = '
<style>
.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
}

.icon-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.icon-item:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,.05);
}

.icon-display {
    height: 36px;
    width: 36px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-name {
    font-size: 12px;
    color: var(--bs-gray-700);
    text-align: center;
    word-break: break-all;
    font-family: monospace;
}

.icon-section-header {
    border-bottom: 1px solid rgba(0,0,0,.1);
    margin-bottom: 20px;
    padding-bottom: 10px;
}

.style-switch {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.style-switch button {
    padding: 0.5rem 1rem;
    border-radius: 30px;
}

.filter-box {
    max-width: 500px;
    margin: 0 auto 2rem auto;
}

.code-copy {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: rgba(255,255,255,0.9);
    border: none;
    border-radius: 4px;
    padding: 5px 8px;
    font-size: 12px;
    cursor: pointer;
    display: none;
}

.icon-modal-body {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 1rem;
}

.icon-modal-body .heroicon {
    height: 4rem;
    width: 4rem;
}

.icon-modal pre {
    position: relative;
    margin-top: 1rem;
}

.icon-modal pre:hover .code-copy {
    display: block;
}
</style>';

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= heroicon_outline('swatch', 'heroicon-md me-2') ?>
        Galería de Iconos
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/configuracion/index.php">Configuración</a></li>
        <li class="breadcrumb-item active">Galería de Iconos</li>
    </ol>

    <p class="lead mb-4">Explora todos los iconos disponibles en el sistema. Haz clic en cualquier icono para obtener el código para usarlo.</p>

    <!-- Filtrado y búsqueda -->
    <div class="filter-box">
        <div class="input-group mb-3">
            <span class="input-group-text">
                <?= heroicon_outline('magnifying-glass', 'heroicon-sm') ?>
            </span>
            <input type="text" id="iconSearch" class="form-control" placeholder="Buscar iconos..." aria-label="Buscar iconos">
        </div>
    </div>

    <!-- Selector de estilo -->
    <div class="style-switch mb-3">
        <button class="btn btn-primary active" data-style="outline" id="btnOutline">Outline</button>
        <button class="btn btn-outline-primary" data-style="solid" id="btnSolid">Solid</button>
    </div>

    <!-- Iconos Outline -->
    <div id="outlineSection" class="icon-section">
        <h2 class="icon-section-header">
            Outline
            <small class="text-muted ms-2"><?= count($outlineIcons) ?> iconos</small>
        </h2>
        <div class="icon-grid">
            <?php foreach ($outlineIcons as $icon): ?>
            <div class="icon-item" data-name="<?= $icon ?>" data-style="outline">
                <div class="icon-display">
                    <?= heroicon_outline($icon, 'heroicon-xl') ?>
                </div>
                <div class="icon-name"><?= $icon ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Iconos Solid -->
    <div id="solidSection" class="icon-section d-none">
        <h2 class="icon-section-header">
            Solid
            <small class="text-muted ms-2"><?= count($solidIcons) ?> iconos</small>
        </h2>
        <div class="icon-grid">
            <?php foreach ($solidIcons as $icon): ?>
            <div class="icon-item" data-name="<?= $icon ?>" data-style="solid">
                <div class="icon-display">
                    <?= heroicon_solid($icon, 'heroicon-xl') ?>
                </div>
                <div class="icon-name"><?= $icon ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para mostrar el icono -->
    <div class="modal fade" id="iconModal" tabindex="-1" aria-labelledby="iconModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content icon-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="iconModalLabel">Icono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="icon-modal-body" id="iconDisplay"></div>
                    <pre class="bg-light p-3 rounded-3"><code id="iconCode"></code><button class="code-copy" onclick="copyCode()">Copiar</button></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Script personalizado para la página
$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Filtrado de iconos
    const searchInput = document.getElementById("iconSearch");
    searchInput.addEventListener("input", filterIcons);

    function filterIcons() {
        const searchTerm = searchInput.value.toLowerCase();
        const items = document.querySelectorAll(".icon-item");
        
        items.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            if (name.includes(searchTerm)) {
                item.style.display = "flex";
            } else {
                item.style.display = "none";
            }
        });
    }

    // Cambio entre Outline y Solid
    const btnOutline = document.getElementById("btnOutline");
    const btnSolid = document.getElementById("btnSolid");
    const outlineSection = document.getElementById("outlineSection");
    const solidSection = document.getElementById("solidSection");

    btnOutline.addEventListener("click", function() {
        btnOutline.classList.add("btn-primary");
        btnOutline.classList.remove("btn-outline-primary");
        btnSolid.classList.remove("btn-primary");
        btnSolid.classList.add("btn-outline-primary");
        
        outlineSection.classList.remove("d-none");
        solidSection.classList.add("d-none");
    });

    btnSolid.addEventListener("click", function() {
        btnSolid.classList.add("btn-primary");
        btnSolid.classList.remove("btn-outline-primary");
        btnOutline.classList.remove("btn-primary");
        btnOutline.classList.add("btn-outline-primary");
        
        solidSection.classList.remove("d-none");
        outlineSection.classList.add("d-none");
    });

    // Modal para mostrar icono y código
    const iconModal = new bootstrap.Modal(document.getElementById("iconModal"));
    const iconItems = document.querySelectorAll(".icon-item");
    
    iconItems.forEach(item => {
        item.addEventListener("click", function() {
            const name = this.dataset.name;
            const style = this.dataset.style;
            const modalTitle = document.getElementById("iconModalLabel");
            const iconDisplay = document.getElementById("iconDisplay");
            const iconCode = document.getElementById("iconCode");
            
            modalTitle.textContent = name + " (" + style + ")";
            
            if (style === "outline") {
                iconDisplay.innerHTML = `<?= heroicon_outline("${name}", "heroicon-xl") ?>`;
                iconCode.textContent = `<?= heroicon_outline("${name}", "heroicon-sm") ?>`;
            } else {
                iconDisplay.innerHTML = `<?= heroicon_solid("${name}", "heroicon-xl") ?>`;
                iconCode.textContent = `<?= heroicon_solid("${name}", "heroicon-sm") ?>`;
            }
            
            iconModal.show();
        });
    });
});

// Función para copiar código
function copyCode() {
    const code = document.getElementById("iconCode").textContent;
    navigator.clipboard.writeText(code).then(() => {
        const copyBtn = document.querySelector(".code-copy");
        copyBtn.textContent = "¡Copiado!";
        setTimeout(() => {
            copyBtn.textContent = "Copiar";
        }, 2000);
    });
}
</script>';

// Finalizar la captura y renderizar la página
endPageContent($pageTitle, $extra_css, $extra_js);
?> 