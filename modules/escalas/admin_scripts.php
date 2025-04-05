<?php
/**
 * Módulo de Escalas Psicológicas - Panel de Scripts de Administración
 * Este script proporciona acceso centralizado a todos los scripts de configuración del módulo
 */

// Incluir configuración básica
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador antes de incluir el header
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Panel de Scripts de Administración";

// Iniciar captura del contenido de la página
startPageContent();

// Estructura para las tarjetas de los scripts
$scripts = [
    // Configuración de escalas
    [
        'categoria' => 'Configuración de Escalas',
        'items' => [
            [
                'titulo' => 'Cargar Escala de Autoestima de Rosenberg',
                'descripcion' => 'Configura automáticamente la Escala de Autoestima de Rosenberg con sus 10 ítems.',
                'icono' => 'psychology',
                'url' => 'cargar_items_rosenberg.php',
                'color' => 'primary'
            ],
            [
                'titulo' => 'Cargar Inventario de Depresión de Beck (BDI-II)',
                'descripcion' => 'Configura automáticamente el Inventario de Depresión de Beck con sus ítems.',
                'icono' => 'psychology',
                'url' => 'cargar_items_beck.php',
                'color' => 'success'
            ],
            [
                'titulo' => 'Cargar Inventario de Ansiedad STAI',
                'descripcion' => 'Configura el Inventario de Ansiedad Estado-Rasgo con sus subescalas.',
                'icono' => 'psychology',
                'url' => 'cargar_items_stai.php',
                'color' => 'warning'
            ],
            [
                'titulo' => 'Cargar Cuestionario GHQ-28',
                'descripcion' => 'Configura el Cuestionario de Salud General de Goldberg con sus 4 subescalas.',
                'icono' => 'psychology',
                'url' => 'cargar_items_ghq28.php',
                'color' => 'danger'
            ],
            [
                'titulo' => 'Cargar Sistema BASC',
                'descripcion' => 'Configura el Sistema de Evaluación de Conducta para niños y adolescentes.',
                'icono' => 'child_care',
                'url' => 'cargar_items_basc.php',
                'color' => 'info'
            ],
            [
                'titulo' => 'Cargar Test de Atención D2',
                'descripcion' => 'Configura el Test de Atención D2 para evaluar concentración y atención selectiva.',
                'icono' => 'visibility',
                'url' => 'cargar_items_d2.php',
                'color' => 'secondary'
            ],
            [
                'titulo' => 'Administrar Escalas',
                'descripcion' => 'Panel para crear, editar y eliminar escalas psicológicas.',
                'icono' => 'settings',
                'url' => 'administrar_escalas.php',
                'color' => 'dark'
            ]
        ]
    ],
    
    // Base de datos
    [
        'categoria' => 'Herramientas de Base de Datos',
        'items' => [
            [
                'titulo' => 'Conexión a Base de Datos Remota',
                'descripcion' => 'Ejecuta consultas SQL en la base de datos remota (178.211.133.60).',
                'icono' => 'database',
                'url' => 'conexion_remota.php',
                'color' => 'danger'
            ],
            [
                'titulo' => 'Configurar Base de Datos del Módulo',
                'descripcion' => 'Crea o actualiza las tablas necesarias para el módulo de escalas.',
                'icono' => 'build',
                'url' => 'setup_db.php',
                'color' => 'warning'
            ]
        ]
    ],
    
    // Administración
    [
        'categoria' => 'Administración',
        'items' => [
            [
                'titulo' => 'Ver Administraciones',
                'descripcion' => 'Visualiza todas las administraciones de escalas realizadas.',
                'icono' => 'list',
                'url' => 'index.php',
                'color' => 'secondary'
            ],
            [
                'titulo' => 'Administrar Ítems',
                'descripcion' => 'Panel para gestionar los ítems de las escalas existentes.',
                'icono' => 'format_list_bulleted',
                'url' => 'administrar_items.php',
                'color' => 'dark'
            ]
        ]
    ]
];
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">admin_panel_settings</span>
        Panel de Scripts de Administración
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Scripts de Administración</li>
    </ol>
    
    <!-- Mensaje informativo -->
    <div class="alert alert-info mb-4">
        <span class="material-symbols-rounded align-middle me-2">info</span>
        <span class="align-middle">Este panel centraliza todos los scripts de administración y configuración del módulo de Escalas Psicológicas. Seleccione la opción que desee ejecutar.</span>
    </div>
    
    <!-- Scripts organizados por categorías -->
    <?php foreach ($scripts as $categoria): ?>
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="m-0"><?= $categoria['categoria'] ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($categoria['items'] as $script): ?>
                        <div class="col-md-6 col-xl-4 mb-3">
                            <div class="card h-100 border-0 shadow-hover">
                                <div class="card-header bg-<?= $script['color'] ?> text-white">
                                    <h5 class="m-0">
                                        <span class="material-symbols-rounded me-1"><?= $script['icono'] ?></span>
                                        <?= $script['titulo'] ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= $script['descripcion'] ?></p>
                                </div>
                                <div class="card-footer bg-light border-0">
                                    <a href="<?= $script['url'] ?>" class="btn btn-<?= $script['color'] ?> w-100">
                                        <span class="material-symbols-rounded me-1">open_in_new</span> Ejecutar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Scripts relacionados con la estructura de datos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0">Configuración de Base de Datos</h5>
        </div>
        <div class="card-body">
            <p>Scripts para configurar la estructura de la base de datos de escalas.</p>
            
            <div class="list-group">
                <a href="setup_db.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">database</span>
                    Crear Tablas Principales
                    <span class="badge bg-primary ms-auto">Estructura Básica</span>
                </a>
                <a href="setup_baremos.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">bar_chart</span>
                    Crear Tablas de Baremos
                    <span class="badge bg-success ms-auto">Nuevo</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts para cargar datos predefinidos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="m-0">Carga de Datos</h5>
        </div>
        <div class="card-body">
            <p>Scripts para cargar datos predefinidos en el sistema de escalas.</p>
            
            <div class="list-group">
                <div class="list-group-item bg-light">
                    <strong>Escalas Predefinidas</strong>
                </div>
                <a href="cargar_items_beck.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar Inventario de Depresión de Beck (BDI-II)
                </a>
                <a href="cargar_items_stai.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar Inventario de Ansiedad Estado-Rasgo (STAI)
                </a>
                <a href="cargar_items_ansiedad.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar Escala de Ansiedad de Hamilton (HARS)
                    <span class="badge bg-success ms-auto">Nuevo</span>
                </a>
                <a href="cargar_items_ghq28.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar Cuestionario de Salud General (GHQ-28)
                </a>
                <a href="cargar_items_rosenberg.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar Escala de Autoestima de Rosenberg
                </a>
                <a href="cargar_items_toc.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar Escala Yale-Brown para TOC (Y-BOCS)
                    <span class="badge bg-success ms-auto">Nuevo</span>
                </a>
                <a href="cargar_items_tdah.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">psychology</span>
                    Cargar ADHD Rating Scale-IV (TDAH)
                    <span class="badge bg-success ms-auto">Nuevo</span>
                </a>
                
                <div class="list-group-item bg-light mt-3">
                    <strong>Baremos y Datos Normativos</strong>
                </div>
                <a href="cargar_baremos.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">monitoring</span>
                    Cargar Baremos y Datos Normativos
                    <span class="badge bg-success ms-auto">Nuevo</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Otras herramientas -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h5 class="m-0">Herramientas de Administración</h5>
        </div>
        <div class="card-body">
            <p>Herramientas adicionales para la administración del sistema de escalas.</p>
            
            <div class="list-group">
                <a href="administrar_escalas.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">edit_note</span>
                    Administrar Escalas
                </a>
                <a href="administrar_items.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <span class="material-symbols-rounded me-2">list</span>
                    Administrar Ítems
                </a>
            </div>
        </div>
    </div>
    
    <!-- Notas importantes -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="m-0">
                <span class="material-symbols-rounded me-1">warning</span>
                Notas Importantes
            </h5>
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li class="mb-2"><strong>Backup:</strong> Antes de realizar modificaciones importantes en la base de datos, asegúrese de tener una copia de seguridad.</li>
                <li class="mb-2"><strong>Carga de Ítems:</strong> Los scripts de carga de ítems verifican si la escala ya existe antes de crearla, evitando duplicaciones.</li>
                <li class="mb-2"><strong>Base de Datos Remota:</strong> Al trabajar con la base de datos remota, tenga cuidado con las consultas que ejecuta para evitar pérdida de datos.</li>
            </ul>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 