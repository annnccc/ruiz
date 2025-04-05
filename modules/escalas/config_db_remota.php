<?php
/**
 * Módulo de Escalas Psicológicas - Configuración de Base de Datos Remota
 * Este script permite configurar temporalmente la conexión a la base de datos remota
 */

// Incluir configuración básica
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Configuración de Base de Datos Remota";

// Iniciar captura del contenido de la página
startPageContent();

// Función para crear una conexión remota
function getRemoteDB() {
    // Usar los mismos datos excepto el host
    $host = '178.211.133.60';
    $port = DB_PORT;
    $name = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Error de conexión a la base de datos remota: " . $e->getMessage());
    }
}

// Comprobar si se ha pulsado el botón de ejecutar acción
$mensaje = '';
$error = false;
$results = [];
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    try {
        $db = getRemoteDB();
        
        switch ($action) {
            case 'test_connection':
                // Probar la conexión
                $stmt = $db->query("SELECT 'Conexión exitosa' AS resultado");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $mensaje = "Conexión a la base de datos remota establecida con éxito.";
                break;
                
            case 'cargar_rosenberg':
                // Cargar Escala de Rosenberg en BD remota
                // Redirigir al script específico con parámetro para BD remota
                header('Location: cargar_items_rosenberg.php?remote=1');
                exit;
                break;
                
            case 'cargar_beck':
                // Cargar Inventario Beck en BD remota
                // Redirigir al script específico con parámetro para BD remota
                header('Location: cargar_items_beck.php?remote=1');
                exit;
                break;
                
            case 'setup_tables':
                // Configurar tablas en BD remota
                header('Location: setup_db.php?remote=1');
                exit;
                break;
                
            case 'check_tables':
                // Verificar tablas en BD remota
                $tables = [
                    'escalas_catalogo',
                    'escalas_items',
                    'escalas_administraciones',
                    'escalas_respuestas'
                ];
                
                $results = [];
                foreach ($tables as $table) {
                    try {
                        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
                        $exists = $stmt->rowCount() > 0;
                        
                        if ($exists) {
                            // Contar registros
                            $count = $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                            $results[] = [
                                'tabla' => $table,
                                'estado' => 'Existe',
                                'registros' => $count
                            ];
                        } else {
                            $results[] = [
                                'tabla' => $table,
                                'estado' => 'No existe',
                                'registros' => 0
                            ];
                        }
                    } catch (PDOException $e) {
                        $results[] = [
                            'tabla' => $table,
                            'estado' => 'Error: ' . $e->getMessage(),
                            'registros' => 0
                        ];
                    }
                }
                
                $mensaje = "Verificación de tablas completada.";
                break;
                
            default:
                $error = true;
                $mensaje = "Acción no reconocida.";
        }
    } catch (Exception $e) {
        $error = true;
        $mensaje = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">settings_applications</span>
        Configuración de Base de Datos Remota
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Configuración BD Remota</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <!-- Información de conexión -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">info</span>
                        Información de Conexión Remota
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Host:</strong> 178.211.133.60</p>
                        <p class="mb-0"><strong>Base de datos:</strong> <?= DB_NAME ?></p>
                        <p class="mb-0"><strong>Usuario:</strong> <?= DB_USER ?></p>
                        <p class="mb-0"><strong>Puerto:</strong> <?= DB_PORT ?></p>
                    </div>
                    
                    <form method="post" action="" class="mt-3">
                        <input type="hidden" name="action" value="test_connection">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-rounded me-1">lan</span> Probar Conexión
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $error ? 'danger' : 'success' ?> mb-4">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($results)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="m-0">
                            <span class="material-symbols-rounded me-1">data_table</span>
                            Resultados
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($results[0]) as $columna): ?>
                                            <th><?= ucfirst(htmlspecialchars($columna)) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $fila): ?>
                                        <tr>
                                            <?php foreach ($fila as $valor): ?>
                                                <td><?= htmlspecialchars($valor) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Acciones disponibles -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">playlist_add_check</span>
                        Acciones Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Verificar tablas -->
                        <div class="col-md-6 col-xl-3 mb-3">
                            <div class="card h-100 border-0 shadow-hover">
                                <div class="card-header bg-info text-white">
                                    <h5 class="m-0">
                                        <span class="material-symbols-rounded me-1">search</span>
                                        Verificar Tablas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p>Comprueba si las tablas necesarias existen en la base de datos remota.</p>
                                </div>
                                <div class="card-footer bg-light border-0">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="check_tables">
                                        <button type="submit" class="btn btn-info w-100">
                                            <span class="material-symbols-rounded me-1">check_circle</span> Verificar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configurar tablas -->
                        <div class="col-md-6 col-xl-3 mb-3">
                            <div class="card h-100 border-0 shadow-hover">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="m-0">
                                        <span class="material-symbols-rounded me-1">build</span>
                                        Configurar Tablas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p>Crea o actualiza las tablas necesarias para el módulo de escalas en la BD remota.</p>
                                </div>
                                <div class="card-footer bg-light border-0">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="setup_tables">
                                        <button type="submit" class="btn btn-warning w-100">
                                            <span class="material-symbols-rounded me-1">table_chart</span> Configurar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cargar Rosenberg -->
                        <div class="col-md-6 col-xl-3 mb-3">
                            <div class="card h-100 border-0 shadow-hover">
                                <div class="card-header bg-success text-white">
                                    <h5 class="m-0">
                                        <span class="material-symbols-rounded me-1">psychology</span>
                                        Escala Rosenberg
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p>Carga la Escala de Autoestima de Rosenberg en la base de datos remota.</p>
                                </div>
                                <div class="card-footer bg-light border-0">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="cargar_rosenberg">
                                        <button type="submit" class="btn btn-success w-100">
                                            <span class="material-symbols-rounded me-1">upload</span> Cargar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cargar Beck -->
                        <div class="col-md-6 col-xl-3 mb-3">
                            <div class="card h-100 border-0 shadow-hover">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="m-0">
                                        <span class="material-symbols-rounded me-1">psychology</span>
                                        Inventario Beck
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p>Carga el Inventario de Depresión de Beck (BDI-II) en la base de datos remota.</p>
                                </div>
                                <div class="card-footer bg-light border-0">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="cargar_beck">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <span class="material-symbols-rounded me-1">upload</span> Cargar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Advertencia -->
            <div class="alert alert-danger mb-4">
                <span class="material-symbols-rounded align-middle me-2">warning</span>
                <span class="align-middle"><strong>¡Importante!</strong> Estas operaciones modifican directamente la base de datos remota. Asegúrese de tener una copia de seguridad antes de realizar cambios significativos.</span>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 