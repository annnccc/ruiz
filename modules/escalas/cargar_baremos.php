<?php
/**
 * Script para cargar baremos y datos normativos de las escalas
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo "<div class='alert alert-danger'>Acceso denegado. Debe ser administrador para ejecutar este script.</div>";
    exit;
}

// Título de la página
$pageTitle = "Cargar Baremos y Datos Normativos";

// Iniciar captura del contenido
startPageContent();

// Obtener conexión a la base de datos
$db = getDB();

// Obtener la lista de escalas disponibles
$stmt = $db->query("SELECT id, nombre FROM escalas_catalogo ORDER BY nombre");
$escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar si se ha enviado el formulario
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cargar_baremos'])) {
    try {
        $db->beginTransaction();
        
        $escala_id = isset($_POST['escala_id']) ? (int)$_POST['escala_id'] : 0;
        $baremo_tipo = isset($_POST['baremo_tipo']) ? $_POST['baremo_tipo'] : '';
        
        if ($escala_id > 0 && $baremo_tipo === 'beck') {
            // Cargar baremos del Inventario de Depresión de Beck (BDI-II)
            
            // 1. Primero, verificar si ya existe el baremo para adultos
            $stmt = $db->prepare("SELECT id FROM escalas_baremos WHERE escala_id = ? AND poblacion = 'adultos' AND subescala = 'total'");
            $stmt->execute([$escala_id]);
            $baremo_existente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$baremo_existente) {
                // Insertar baremo para adultos en general
                $stmt = $db->prepare("
                    INSERT INTO escalas_baremos (
                        escala_id, subescala, poblacion, genero, edad_min, edad_max, 
                        media, desviacion_estandar, descripcion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $escala_id,
                    'total',
                    'adultos',
                    'todos',
                    18,
                    65,
                    10.6,  // Media según manuales del BDI-II
                    8.0,   // Desviación estándar
                    'Baremo normativo para población adulta general (18-65 años). Basado en Beck, A. T., Steer, R. A., & Brown, G. K. (1996). Manual for the Beck Depression Inventory-II. San Antonio, TX: Psychological Corporation.'
                ]);
                
                $baremo_id = $db->lastInsertId();
                
                // 2. Cargar puntos de corte para el BDI-II
                $puntos_corte = [
                    [0, 13, 'Mínima', 'Sintomatología depresiva mínima o ausente.', 0],
                    [14, 19, 'Leve', 'Sintomatología depresiva leve.', 0],
                    [20, 28, 'Moderada', 'Sintomatología depresiva moderada. Se recomienda evaluación clínica.', 1],
                    [29, 63, 'Grave', 'Sintomatología depresiva grave. Se recomienda evaluación clínica urgente.', 1]
                ];
                
                $stmt = $db->prepare("
                    INSERT INTO escalas_puntos_corte (
                        escala_id, subescala, puntuacion_min, puntuacion_max, 
                        interpretacion, descripcion, nivel_alerta
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($puntos_corte as $punto) {
                    $stmt->execute([
                        $escala_id,
                        'total',
                        $punto[0],
                        $punto[1],
                        $punto[2],
                        $punto[3],
                        $punto[4]
                    ]);
                }
                
                // 3. Cargar equivalencias de percentiles (simplificado)
                $equivalencias = [
                    [0, 5, 25, -0.69, 4, 2],   // Puntuación 0, percentil 5
                    [1, 10, 30, -0.48, 4, 3],  // Puntuación 1, percentil 10
                    [2, 15, 35, -0.39, 4, 3],
                    [4, 25, 40, -0.25, 5, 4],
                    [6, 35, 43, -0.15, 5, 4],
                    [8, 45, 45, -0.12, 5, 5],
                    [10, 50, 50, 0.00, 5, 5],   // Puntuación 10, percentil 50 (media)
                    [12, 55, 55, 0.12, 6, 6],
                    [14, 65, 60, 0.37, 6, 6],
                    [17, 75, 64, 0.69, 7, 7],
                    [21, 85, 69, 1.03, 7, 7],
                    [26, 90, 72, 1.28, 8, 8],
                    [31, 95, 75, 1.65, 8, 8],
                    [36, 98, 80, 2.05, 9, 9]    // Puntuación 36, percentil 98
                ];
                
                $stmt = $db->prepare("
                    INSERT INTO escalas_equivalencias (
                        baremo_id, puntuacion_directa, percentil, 
                        puntuacion_T, puntuacion_z, eneatipos, decatipos
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($equivalencias as $equiv) {
                    $stmt->execute([
                        $baremo_id,
                        $equiv[0],  // Puntuación directa
                        $equiv[1],  // Percentil
                        $equiv[2],  // Puntuación T
                        $equiv[3],  // Puntuación z
                        $equiv[4],  // Eneatipos
                        $equiv[5]   // Decatipos
                    ]);
                }
                
                $mensaje = "<div class='alert alert-success'>
                    <strong>¡Baremos cargados correctamente!</strong><br>
                    Se han añadido los baremos normativos, puntos de corte y equivalencias para el Inventario de Depresión de Beck (BDI-II).
                </div>";
            } else {
                $mensaje = "<div class='alert alert-info'>
                    Los baremos para el Inventario de Depresión de Beck (BDI-II) ya existen en la base de datos.
                </div>";
            }
        } elseif ($escala_id > 0 && $baremo_tipo === 'stai') {
            // Aquí se implementaría el código para cargar baremos del STAI
            $mensaje = "<div class='alert alert-warning'>
                La carga de baremos para STAI será implementada próximamente.
            </div>";
        } elseif ($escala_id > 0 && $baremo_tipo === 'hamilton') {
            // Aquí se implementaría el código para cargar baremos de Hamilton
            $mensaje = "<div class='alert alert-warning'>
                La carga de baremos para la Escala de Ansiedad de Hamilton será implementada próximamente.
            </div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>
                Error: Debe seleccionar una escala y un tipo de baremo válidos.
            </div>";
        }
        
        // Confirmar transacción
        $db->commit();
        
    } catch (PDOException $e) {
        // Revertir en caso de error
        $db->rollBack();
        $mensaje = "<div class='alert alert-danger'>Error al cargar los baremos: " . $e->getMessage() . "</div>";
    }
}

// Mostrar formulario para cargar baremos
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="m-0">Carga de Baremos y Datos Normativos</h5>
    </div>
    <div class="card-body">
        <?php echo $mensaje; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="escala_id" class="form-label">Seleccione la Escala:</label>
                <select name="escala_id" id="escala_id" class="form-select" required>
                    <option value="">-- Seleccione una escala --</option>
                    <?php foreach ($escalas as $escala): ?>
                        <option value="<?php echo $escala['id']; ?>"><?php echo htmlspecialchars($escala['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="baremo_tipo" class="form-label">Tipo de Baremo a Cargar:</label>
                <select name="baremo_tipo" id="baremo_tipo" class="form-select" required>
                    <option value="">-- Seleccione tipo de baremo --</option>
                    <option value="beck">Inventario de Depresión de Beck (BDI-II)</option>
                    <option value="stai">Inventario de Ansiedad Estado-Rasgo (STAI)</option>
                    <option value="hamilton">Escala de Ansiedad de Hamilton (HARS)</option>
                </select>
            </div>
            
            <div class="alert alert-info">
                <span class="material-symbols-rounded me-1">info</span>
                Al cargar los baremos, se añadirán datos normativos, puntos de corte y equivalencias de percentiles para la escala seleccionada.
            </div>
            
            <button type="submit" name="cargar_baremos" class="btn btn-primary">
                <span class="material-symbols-rounded me-1">upload</span>
                Cargar Baremos
            </button>
            
            <a href="index.php" class="btn btn-secondary ms-2">
                <span class="material-symbols-rounded me-1">arrow_back</span>
                Volver
            </a>
        </form>
    </div>
</div>

<?php
// Información de ayuda sobre los baremos
?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="m-0">Información sobre Baremos</h5>
    </div>
    <div class="card-body">
        <p>Los baremos permiten interpretar las puntuaciones directas convirtiéndolas en percentiles, puntuaciones típicas u otras escalas derivadas.</p>
        
        <h6>Componentes de los Baremos:</h6>
        <ul>
            <li><strong>Puntos de corte:</strong> Rangos de puntuación con su interpretación clínica correspondiente.</li>
            <li><strong>Equivalencias:</strong> Conversión de puntuaciones directas a percentiles, puntuaciones T, etc.</li>
            <li><strong>Datos normativos:</strong> Estadísticos descriptivos (media, desviación estándar) de la población de referencia.</li>
        </ul>
        
        <p>Las tablas disponibles actualmente incluyen:</p>
        <ul>
            <li><strong>BDI-II:</strong> Baremos para población adulta general con puntos de corte validados para detección de depresión.</li>
            <li><strong>STAI:</strong> Baremos normativos diferenciados para ansiedad estado y ansiedad rasgo (próximamente).</li>
            <li><strong>Hamilton:</strong> Baremos clínicos para interpretación de niveles de severidad de ansiedad (próximamente).</li>
        </ul>
    </div>
</div>

<?php
// Finalizar captura del contenido
endPageContent();

// Incluir la plantilla
include '../../includes/template.php';
?> 