<?php
/**
 * Módulo de Escalas Psicológicas - Conexión a base de datos remota
 * Este script proporciona una conexión a la base de datos remota para consultas
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
$pageTitle = "Conexión a Base de Datos Remota";

// Iniciar captura del contenido de la página
startPageContent();

// Función para obtener conexión a la BD remota
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

// Procesar consulta SQL
$resultado = [];
$mensaje = '';
$error = false;
$query = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {
    $query = trim($_POST['sql_query']);
    
    if (!empty($query)) {
        try {
            $db = getRemoteDB();
            
            // Determinar si es una consulta SELECT o una consulta de modificación
            $isPrimarySelect = (stripos($query, 'SELECT') === 0);
            
            if ($isPrimarySelect) {
                $stmt = $db->query($query);
                $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $mensaje = "Consulta ejecutada con éxito. Se encontraron " . count($resultado) . " registros.";
            } else {
                $count = $db->exec($query);
                $mensaje = "Consulta ejecutada con éxito. Filas afectadas: " . $count;
            }
        } catch (Exception $e) {
            $error = true;
            $mensaje = "Error al ejecutar la consulta: " . $e->getMessage();
        }
    } else {
        $error = true;
        $mensaje = "La consulta SQL no puede estar vacía.";
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">database</span>
        Conexión a Base de Datos Remota
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Conexión Remota</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">database</span>
                        Ejecutar Consulta SQL en Base de Datos Remota
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <span class="material-symbols-rounded me-1">info</span>
                        <strong>Información de conexión:</strong> 
                        <ul class="mb-0">
                            <li><strong>Host:</strong> 178.211.133.60</li>
                            <li><strong>Base de datos:</strong> <?= DB_NAME ?></li>
                            <li><strong>Usuario:</strong> <?= DB_USER ?></li>
                        </ul>
                    </div>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="sql_query" class="form-label">Consulta SQL:</label>
                            <textarea class="form-control" id="sql_query" name="sql_query" rows="5" placeholder="Escriba su consulta SQL aquí..."><?= htmlspecialchars($query) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded me-1">play_arrow</span> Ejecutar Consulta
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?= $error ? 'danger' : 'success' ?> mt-4">
                            <?= $mensaje ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($resultado)): ?>
                        <div class="mt-4">
                            <h5>Resultados de la consulta:</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($resultado[0]) as $columna): ?>
                                                <th><?= htmlspecialchars($columna) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultado as $fila): ?>
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
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">help</span>
                        Ayuda y Sugerencias de Consulta
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Consultas útiles:</h6>
                    <pre><code>-- Ver todas las escalas disponibles
SELECT * FROM escalas_catalogo;

-- Ver ítems de una escala específica
SELECT * FROM escalas_items WHERE escala_id = 1;

-- Ver administraciones y resultados
SELECT a.id, a.fecha, e.nombre, p.nombre, p.apellidos 
FROM escalas_administraciones a
JOIN escalas_catalogo e ON a.escala_id = e.id
JOIN pacientes p ON a.paciente_id = p.id;</code></pre>
                    
                    <div class="alert alert-warning mt-3">
                        <span class="material-symbols-rounded me-1">warning</span>
                        <strong>¡Precaución!</strong> Tenga cuidado al ejecutar consultas de modificación (INSERT, UPDATE, DELETE) ya que pueden alterar los datos de forma permanente.
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