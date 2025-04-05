<?php
/**
 * Módulo de Escalas Psicológicas - Carga Ítems de la Escala Yale-Brown para TOC
 * Permite cargar automáticamente los ítems de la escala Y-BOCS para evaluación del TOC
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

// Obtener conexión a la base de datos
$db = getDB();

// Título de la página
$pageTitle = "Carga de Ítems - Escala Yale-Brown para TOC (Y-BOCS)";

// Variables para el resultado
$error = false;
$mensaje = '';

// Procesar la petición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Comprobar si la escala ya existe
        $stmt = $db->prepare("SELECT id FROM escalas_catalogo WHERE nombre = ?");
        $nombre_escala = 'Escala Yale-Brown para Trastorno Obsesivo-Compulsivo (Y-BOCS)';
        $stmt->execute([$nombre_escala]);
        $escala = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($escala) {
            $escala_id = $escala['id'];
            
            // Comprobar si ya tiene ítems
            $stmt = $db->prepare("SELECT COUNT(*) FROM escalas_items WHERE escala_id = ?");
            $stmt->execute([$escala_id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("La escala ya tiene ítems cargados. No se pueden volver a cargar para evitar duplicados.");
            }
        } else {
            // Crear la escala
            $descripcion = "La Escala Yale-Brown para Trastorno Obsesivo-Compulsivo (Y-BOCS) es un instrumento diseñado para evaluar la gravedad de los síntomas del TOC independientemente del contenido de obsesiones o compulsiones. Evalúa el tiempo dedicado, interferencia, malestar, resistencia y control sobre obsesiones y compulsiones.";
            $instrucciones = "Este cuestionario debe ser aplicado por un clínico entrenado. Se evalúan por separado las obsesiones y compulsiones. Cada ítem se puntúa de 0 a 4, donde 0 significa 'ninguno' y 4 'extremo'. Asegúrese de explicar claramente los conceptos de 'obsesión' y 'compulsión' al paciente antes de comenzar.";
            $tiempo_estimado = "15-20 minutos";
            $referencia = "Goodman, W. K., Price, L. H., Rasmussen, S. A., Mazure, C., Fleischmann, R. L., Hill, C. L., ... & Charney, D. S. (1989). The Yale-Brown obsessive compulsive scale: I. Development, use, and reliability. Archives of general psychiatry, 46(11), 1006-1011.";
            
            $stmt = $db->prepare("INSERT INTO escalas_catalogo (nombre, descripcion, poblacion, instrucciones, tiempo_estimado, referencia_bibliografica) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre_escala, $descripcion, 'adultos', $instrucciones, $tiempo_estimado, $referencia]);
            
            $escala_id = $db->lastInsertId();
        }
        
        // Definir los ítems de la escala Y-BOCS
        $items = [
            // Obsesiones
            [
                'numero' => 1,
                'texto' => 'TIEMPO DEDICADO A LAS OBSESIONES: ¿Cuánto tiempo ocupa en sus obsesiones? ¿Con qué frecuencia aparecen?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ninguno',
                    '1' => 'Leve (menos de 1 h/día) o intrusión infrecuente',
                    '2' => 'Moderado (1-3 h/día) o intrusión frecuente',
                    '3' => 'Grave (3-8 h/día) o intrusión muy frecuente',
                    '4' => 'Extremo (más de 8 h/día) o intrusión casi constante'
                ]),
                'inversion' => 0,
                'subescala' => 'obsesiones'
            ],
            [
                'numero' => 2,
                'texto' => 'INTERFERENCIA DEBIDA A LAS OBSESIONES: ¿En qué medida interfieren sus obsesiones en su vida social o laboral?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ninguna',
                    '1' => 'Leve, ligera interferencia con actividades sociales o laborales',
                    '2' => 'Moderada, clara interferencia pero manejable',
                    '3' => 'Grave, causa interferencia sustancial',
                    '4' => 'Extrema, incapacitante'
                ]),
                'inversion' => 0,
                'subescala' => 'obsesiones'
            ],
            [
                'numero' => 3,
                'texto' => 'MALESTAR ASOCIADO A LAS OBSESIONES: ¿Cuánto malestar le causan sus obsesiones?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ninguno',
                    '1' => 'Leve, no muy molesto',
                    '2' => 'Moderado, molesto pero manejable',
                    '3' => 'Grave, malestar muy molesto',
                    '4' => 'Extremo, malestar incapacitante'
                ]),
                'inversion' => 0,
                'subescala' => 'obsesiones'
            ],
            [
                'numero' => 4,
                'texto' => 'RESISTENCIA A LAS OBSESIONES: ¿Cuánto esfuerzo hace para resistirse a las obsesiones?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Siempre hace esfuerzo para resistir, o síntomas mínimos que no necesitan resistencia activa',
                    '1' => 'Intenta resistir la mayor parte del tiempo',
                    '2' => 'Hace algún esfuerzo para resistir',
                    '3' => 'Cede a todas las obsesiones sin intentar controlarlas',
                    '4' => 'Cede completamente y voluntariamente a todas las obsesiones'
                ]),
                'inversion' => 0,
                'subescala' => 'obsesiones'
            ],
            [
                'numero' => 5,
                'texto' => 'GRADO DE CONTROL SOBRE LAS OBSESIONES: ¿Cuánto control tiene sobre sus obsesiones?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Control completo',
                    '1' => 'Mucho control, generalmente puede detener o desviar las obsesiones con esfuerzo y concentración',
                    '2' => 'Control moderado, a veces puede detener o desviar las obsesiones',
                    '3' => 'Poco control, raramente tiene éxito en detener las obsesiones, solo puede desviar la atención con dificultad',
                    '4' => 'Ningún control, experimentadas como completamente involuntarias'
                ]),
                'inversion' => 0,
                'subescala' => 'obsesiones'
            ],
            
            // Compulsiones
            [
                'numero' => 6,
                'texto' => 'TIEMPO DEDICADO A LAS COMPULSIONES: ¿Cuánto tiempo dedica a realizar conductas compulsivas?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ninguno',
                    '1' => 'Leve (menos de 1 h/día) o realización ocasional de conductas compulsivas',
                    '2' => 'Moderado (1-3 h/día) o realización frecuente de conductas compulsivas',
                    '3' => 'Grave (3-8 h/día) o realización muy frecuente de conductas compulsivas',
                    '4' => 'Extremo (más de 8 h/día) o realización casi constante de conductas compulsivas'
                ]),
                'inversion' => 0,
                'subescala' => 'compulsiones'
            ],
            [
                'numero' => 7,
                'texto' => 'INTERFERENCIA DEBIDA A LAS COMPULSIONES: ¿En qué medida interfieren las compulsiones en su vida social o laboral?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ninguna',
                    '1' => 'Leve, ligera interferencia con actividades sociales o laborales',
                    '2' => 'Moderada, clara interferencia pero manejable',
                    '3' => 'Grave, causa interferencia sustancial',
                    '4' => 'Extrema, incapacitante'
                ]),
                'inversion' => 0,
                'subescala' => 'compulsiones'
            ],
            [
                'numero' => 8,
                'texto' => 'MALESTAR ASOCIADO A LAS COMPULSIONES: ¿Cómo se sentiría si le impidieran realizar sus compulsiones?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Sin ansiedad',
                    '1' => 'Leve, solo ligeramente ansioso si las compulsiones son prevenidas',
                    '2' => 'Moderado, aumento moderado de ansiedad si las compulsiones son prevenidas',
                    '3' => 'Grave, aumento prominente y muy perturbador de ansiedad si las compulsiones son interrumpidas',
                    '4' => 'Extremo, ansiedad incapacitante ante cualquier intervención dirigida a modificar la actividad'
                ]),
                'inversion' => 0,
                'subescala' => 'compulsiones'
            ],
            [
                'numero' => 9,
                'texto' => 'RESISTENCIA A LAS COMPULSIONES: ¿Cuánto se esfuerza para resistirse a las compulsiones?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Siempre hace esfuerzo para resistir, o síntomas mínimos que no necesitan resistencia activa',
                    '1' => 'Intenta resistir la mayor parte del tiempo',
                    '2' => 'Hace algún esfuerzo para resistir',
                    '3' => 'Cede a casi todas las compulsiones sin intentar controlarlas',
                    '4' => 'Cede completamente y voluntariamente a todas las compulsiones'
                ]),
                'inversion' => 0,
                'subescala' => 'compulsiones'
            ],
            [
                'numero' => 10,
                'texto' => 'GRADO DE CONTROL SOBRE LAS COMPULSIONES: ¿Cuánto control tiene sobre las compulsiones?',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Control completo',
                    '1' => 'Mucho control, siente presión para realizar la conducta pero normalmente puede ejercer control voluntario sobre ella',
                    '2' => 'Control moderado, fuerte presión para realizar la conducta, solo puede controlarla con dificultad',
                    '3' => 'Poco control, fuerte impulso para realizar la conducta, debe completarla hasta el final, solo puede retrasar con dificultad',
                    '4' => 'Ningún control, el impulso para realizar la conducta se experimenta como completamente involuntario y abrumador'
                ]),
                'inversion' => 0,
                'subescala' => 'compulsiones'
            ]
        ];
        
        // Insertar ítems
        $stmt = $db->prepare("INSERT INTO escalas_items (escala_id, numero, texto, tipo_respuesta, opciones_respuesta, inversion, subescala) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $stmt->execute([
                $escala_id,
                $item['numero'],
                $item['texto'],
                $item['tipo_respuesta'],
                $item['opciones_respuesta'],
                $item['inversion'],
                $item['subescala']
            ]);
        }
        
        // Confirmar transacción
        $db->commit();
        
        $mensaje = "La escala Yale-Brown para TOC (Y-BOCS) ha sido cargada correctamente con " . count($items) . " ítems.";
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $db->rollBack();
        $error = true;
        $mensaje = "Error: " . $e->getMessage();
    }
}

// Iniciar captura del contenido de la página
startPageContent();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">playlist_add</span>
        Carga de Ítems - Escala Yale-Brown para TOC (Y-BOCS)
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Cargar Ítems Y-BOCS</li>
    </ol>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $error ? 'danger' : 'success' ?>">
            <?= $mensaje ?>
        </div>
        
        <?php if (!$error): ?>
            <div class="mb-4">
                <a href="index.php" class="btn btn-primary">
                    <span class="material-symbols-rounded me-1">arrow_back</span> Volver a Escalas
                </a>
                <a href="administrar_escalas.php" class="btn btn-secondary">
                    <span class="material-symbols-rounded me-1">settings</span> Administrar Escalas
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (!$mensaje): ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <span class="material-symbols-rounded me-1">info</span>
                    Información de la Escala
                </div>
                <div class="card-body">
                    <h5>Escala Yale-Brown para Trastorno Obsesivo-Compulsivo (Y-BOCS)</h5>
                    <p>La Y-BOCS es considerada el "estándar de oro" para medir la gravedad de los síntomas del TOC. Evalúa tanto obsesiones como compulsiones a través de 10 ítems, 5 para cada dimensión.</p>
                    
                    <h6 class="mt-3">Características:</h6>
                    <ul>
                        <li><strong>Población:</strong> Adultos</li>
                        <li><strong>Tiempo estimado:</strong> 15-20 minutos</li>
                        <li><strong>Subescalas:</strong> Obsesiones (ítems 1-5) y Compulsiones (ítems 6-10)</li>
                        <li><strong>Puntuación:</strong> 0-40 puntos en total, con rangos de gravedad del TOC</li>
                    </ul>
                    
                    <h6 class="mt-3">Interpretación:</h6>
                    <ul>
                        <li><strong>0-7 puntos:</strong> Síntomas subclínicos</li>
                        <li><strong>8-15 puntos:</strong> TOC leve</li>
                        <li><strong>16-23 puntos:</strong> TOC moderado</li>
                        <li><strong>24-31 puntos:</strong> TOC grave</li>
                        <li><strong>32-40 puntos:</strong> TOC extremo</li>
                    </ul>
                    
                    <div class="alert alert-info mt-3">
                        <div class="d-flex">
                            <div class="me-2">
                                <span class="material-symbols-rounded">lightbulb</span>
                            </div>
                            <div>
                                <p class="mb-0">Al cargar esta escala, se creará automáticamente en el sistema con todos sus ítems y opciones de respuesta preconfiguradas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span class="material-symbols-rounded me-1">help</span>
                    Acciones Disponibles
                </div>
                <div class="card-body">
                    <p>¿Desea cargar la Escala Yale-Brown para TOC (Y-BOCS) en el sistema?</p>
                    <p>Esta acción añadirá automáticamente:</p>
                    <ul>
                        <li>La escala al catálogo</li>
                        <li>Los 10 ítems con sus opciones de respuesta</li>
                        <li>Las subescalas correspondientes</li>
                    </ul>
                    
                    <form method="post" action="">
                        <input type="hidden" name="confirmar" value="1">
                        <button type="submit" class="btn btn-success w-100">
                            <span class="material-symbols-rounded me-1">add_circle</span> Cargar Escala Y-BOCS
                        </button>
                    </form>
                    
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <span class="material-symbols-rounded me-1">arrow_back</span> Volver sin Cargar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 