<?php
/**
 * Módulo de Escalas Psicológicas - Completar Escala
 * Permite completar una administración de escala registrando las respuestas a los ítems
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si el usuario está autenticado antes de incluir el header
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Verificar que se ha especificado una administración
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Debe especificar una administración válida.";
    header('Location: index.php');
    exit;
}

$admin_id = $_GET['id'];

// Obtener información de la administración
$stmt = $db->prepare("
    SELECT a.*, e.nombre AS escala_nombre, e.instrucciones, e.descripcion,
           p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos
    FROM escalas_administraciones a
    JOIN escalas_catalogo e ON a.escala_id = e.id
    JOIN pacientes p ON a.paciente_id = p.id
    WHERE a.id = ?
");
$stmt->execute([$admin_id]);
$administracion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$administracion) {
    $_SESSION['error'] = "La administración especificada no existe.";
    header('Location: index.php');
    exit;
}

// Verificar si la administración ya está completada
if ($administracion['completada']) {
    $_SESSION['error'] = "Esta administración ya ha sido completada.";
    header('Location: ver_administracion.php?id=' . $admin_id);
    exit;
}

// Título de la página
$pageTitle = "Completar Escala: " . $administracion['escala_nombre'];

// Iniciar captura del contenido de la página
startPageContent();

// Obtener los ítems de la escala
$stmt = $db->prepare("
    SELECT i.* 
    FROM escalas_items i
    WHERE i.escala_id = ?
    ORDER BY i.numero
");
$stmt->execute([$administracion['escala_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    $_SESSION['error'] = "Esta escala no tiene ítems configurados.";
    header('Location: index.php');
    exit;
}

// Procesar formulario de respuestas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Inicio de transacción
        $db->beginTransaction();
        
        // Almacenar respuestas
        foreach ($_POST['respuestas'] as $item_id => $respuesta) {
            if (!is_numeric($item_id)) continue;
            
            // Obtener información del ítem para calcular puntuación
            $stmt = $db->prepare("SELECT tipo_respuesta, inversion FROM escalas_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular puntuación según el tipo de respuesta y si hay inversión
            $puntuacion = calcular_puntuacion($respuesta, $item_info['tipo_respuesta'], $item_info['inversion']);
            
            // Insertar respuesta
            $stmt = $db->prepare("
                INSERT INTO escalas_respuestas (administracion_id, item_id, respuesta, puntuacion)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$admin_id, $item_id, $respuesta, $puntuacion]);
        }
        
        // Calcular resultados por subescalas y total
        $resultados = calcular_resultados($db, $admin_id, $administracion['escala_id']);
        
        // Marcar administración como completada
        $stmt = $db->prepare("
            UPDATE escalas_administraciones
            SET completada = 1, ultima_modificacion = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$admin_id]);
        
        // Confirmar transacción
        $db->commit();
        
        $_SESSION['success'] = "La escala ha sido completada con éxito.";
        header('Location: ver_administracion.php?id=' . $admin_id);
        exit;
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        $_SESSION['error'] = "Error al guardar las respuestas: " . $e->getMessage();
    }
}

/**
 * Calcula la puntuación de una respuesta según el tipo y si hay inversión
 * @param string $respuesta Respuesta dada
 * @param string $tipo Tipo de respuesta (likert3, likert4, likert5, si_no, numerica, seleccion_multiple)
 * @param bool $inversion Si la puntuación debe invertirse
 * @return float Puntuación calculada
 */
function calcular_puntuacion($respuesta, $tipo, $inversion) {
    $puntuacion = 0;
    
    switch ($tipo) {
        case 'likert3':
            $puntuacion = (int) $respuesta;
            $max = 2;
            break;
        case 'likert4':
            $puntuacion = (int) $respuesta;
            $max = 3;
            break;
        case 'likert5':
            $puntuacion = (int) $respuesta;
            $max = 4;
            break;
        case 'si_no':
            $puntuacion = $respuesta === 'si' ? 1 : 0;
            $max = 1;
            break;
        case 'numerica':
            $puntuacion = (float) $respuesta;
            $max = $puntuacion; // No hay inversión para valores numéricos directos
            return $puntuacion;
        case 'seleccion_multiple':
            $puntuacion = (int) $respuesta;
            $max = $puntuacion; // Se asume que es un índice con valor ya asignado
            return $puntuacion;
        default:
            return $puntuacion;
    }
    
    // Aplicar inversión si corresponde
    if ($inversion && $max > 0) {
        $puntuacion = $max - $puntuacion;
    }
    
    return $puntuacion;
}

/**
 * Calcula los resultados de una administración de escala
 * @param PDO $db Conexión a la base de datos
 * @param int $admin_id ID de la administración
 * @param int $escala_id ID de la escala
 * @return array Resultados calculados
 */
function calcular_resultados($db, $admin_id, $escala_id) {
    // Obtener todas las respuestas de esta administración
    $stmt = $db->prepare("
        SELECT r.puntuacion, i.subescala
        FROM escalas_respuestas r
        JOIN escalas_items i ON r.item_id = i.id
        WHERE r.administracion_id = ?
    ");
    $stmt->execute([$admin_id]);
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular puntuaciones por subescala
    $subescalas = [];
    $total = 0;
    
    foreach ($respuestas as $resp) {
        $subescala = $resp['subescala'] ?: 'total';
        $puntuacion = (float) $resp['puntuacion'];
        
        if (!isset($subescalas[$subescala])) {
            $subescalas[$subescala] = [
                'suma' => 0,
                'count' => 0
            ];
        }
        
        $subescalas[$subescala]['suma'] += $puntuacion;
        $subescalas[$subescala]['count']++;
        
        // Sumar al total general solo si no es de una subescala específica
        if ($subescala !== 'total') {
            $total += $puntuacion;
        }
    }
    
    // Añadir el total general si hay subescalas
    if (count($subescalas) > 1 || !isset($subescalas['total'])) {
        $subescalas['total'] = [
            'suma' => $total,
            'count' => array_sum(array_column($subescalas, 'count'))
        ];
    }
    
    // Guardar resultados en la base de datos
    $resultados = [];
    
    foreach ($subescalas as $nombre => $datos) {
        $puntuacion_directa = $datos['suma'];
        
        // Aquí se podrían calcular puntuaciones tipificadas, percentiles, etc.
        // según baremos específicos de cada escala
        
        // Insertar resultado
        $stmt = $db->prepare("
            INSERT INTO escalas_resultados 
            (administracion_id, subescala, puntuacion_directa)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$admin_id, $nombre, $puntuacion_directa]);
        
        $resultados[$nombre] = [
            'puntuacion_directa' => $puntuacion_directa,
            'num_items' => $datos['count']
        ];
    }
    
    return $resultados;
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">edit_note</span>
        Completar Escala
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Completar Escala</li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-clipboard-list me-1"></i>
            <?= htmlspecialchars($administracion['escala_nombre']) ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Paciente:</strong> <?= htmlspecialchars($administracion['paciente_nombre'] . ' ' . $administracion['paciente_apellidos']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($administracion['fecha'])) ?>
                </div>
            </div>
            
            <?php if (!empty($administracion['motivo'])): ?>
                <div class="mb-3">
                    <strong>Motivo:</strong> <?= htmlspecialchars($administracion['motivo']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($administracion['instrucciones'])): ?>
                <div class="alert alert-info">
                    <h5>Instrucciones:</h5>
                    <p><?= nl2br(htmlspecialchars($administracion['instrucciones'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $admin_id ?>">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-list me-1"></i>
                Ítems de la Escala
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <p class="text-muted">No hay ítems definidos para esta escala.</p>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= $item['numero'] ?>. <?= htmlspecialchars($item['texto']) ?></h5>
                                
                                <div class="mt-3">
                                    <?php switch ($item['tipo_respuesta']): 
                                        case 'likert3': ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_0" value="0" required>
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_0">0 - Nunca/Nada</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_1" value="1">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_1">1 - A veces/Algo</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_2" value="2">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_2">2 - Frecuentemente/Mucho</label>
                                            </div>
                                            <?php break; ?>
                                            
                                        <?php case 'likert4': ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_0" value="0" required>
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_0">0 - Nunca</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_1" value="1">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_1">1 - A veces</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_2" value="2">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_2">2 - Frecuentemente</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_3" value="3">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_3">3 - Siempre</label>
                                            </div>
                                            <?php break; ?>
                                            
                                        <?php case 'likert5': ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_0" value="0" required>
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_0">0 - Totalmente en desacuerdo</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_1" value="1">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_1">1 - En desacuerdo</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_2" value="2">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_2">2 - Neutral</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_3" value="3">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_3">3 - De acuerdo</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_4" value="4">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_4">4 - Totalmente de acuerdo</label>
                                            </div>
                                            <?php break; ?>
                                            
                                        <?php case 'si_no': ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_si" value="si" required>
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_si">Sí</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_no" value="no">
                                                <label class="form-check-label" for="resp_<?= $item['id'] ?>_no">No</label>
                                            </div>
                                            <?php break; ?>
                                            
                                        <?php case 'numerica': ?>
                                            <input type="number" class="form-control" name="respuestas[<?= $item['id'] ?>]" required>
                                            <?php break; ?>
                                            
                                        <?php case 'seleccion_multiple': ?>
                                            <?php if (!empty($item['opciones_respuesta'])): 
                                                $opciones = explode(',', $item['opciones_respuesta']);
                                                foreach ($opciones as $i => $opcion): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="respuestas[<?= $item['id'] ?>]" id="resp_<?= $item['id'] ?>_<?= $i ?>" value="<?= $i ?>" required>
                                                        <label class="form-check-label" for="resp_<?= $item['id'] ?>_<?= $i ?>"><?= htmlspecialchars(trim($opcion)) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="alert alert-warning">
                                                    <p>No hay opciones definidas para este ítem.</p>
                                                </div>
                                            <?php endif; ?>
                                            <?php break; ?>
                                            
                                        <?php default: ?>
                                            <input type="text" class="form-control" name="respuestas[<?= $item['id'] ?>]" required>
                                    <?php endswitch; ?>
                                </div>
                                
                                <?php if (!empty($item['subescala'])): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-info">Subescala: <?= htmlspecialchars($item['subescala']) ?></span>
                                        <?php if ($item['inversion']): ?>
                                            <span class="badge bg-warning text-dark">Puntuación invertida</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Guardar Respuestas</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 