<?php
/**
 * Módulo de Escalas Psicológicas - Carga de ítems del Inventario de Depresión de Beck (BDI-II)
 * Este script carga automáticamente los ítems del Inventario de Depresión de Beck
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Carga de Ítems - Inventario de Depresión de Beck (BDI-II)";

// Iniciar captura del contenido de la página
startPageContent();

// Definir el nombre de la escala
$nombreEscala = "Inventario de Depresión de Beck (BDI-II)";

// Verificar si la escala ya existe
$stmt = $db->prepare("SELECT id FROM escalas_catalogo WHERE nombre = ?");
$stmt->execute([$nombreEscala]);
$escala = $stmt->fetch(PDO::FETCH_ASSOC);

$escala_id = null;
$mensaje = '';
$error = false;

// Si no existe, crearla
if (!$escala) {
    try {
        $db->beginTransaction();
        
        // Insertar la escala
        $stmt = $db->prepare("
            INSERT INTO escalas_catalogo (
                nombre, 
                descripcion, 
                poblacion, 
                instrucciones, 
                tiempo_estimado, 
                referencia_bibliografica
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nombreEscala,
            "El Inventario de Depresión de Beck (BDI-II) es uno de los instrumentos más utilizados para evaluar la presencia y gravedad de síntomas depresivos. Desarrollado por Aaron T. Beck, se basa en los criterios diagnósticos del DSM-IV y evalúa los síntomas cognitivos, afectivos, motivacionales y fisiológicos de la depresión.",
            "adultos",
            "Este cuestionario consiste en 21 grupos de afirmaciones. Por favor, lea con atención cada uno de ellos y luego elija la afirmación de cada grupo que mejor describa cómo se ha sentido durante las ÚLTIMAS DOS SEMANAS, INCLUYENDO EL DÍA DE HOY.",
            "5-10 minutos",
            "Beck, A.T., Steer, R.A., & Brown, G.K. (1996). Manual for the Beck Depression Inventory-II. San Antonio, TX: Psychological Corporation."
        ]);
        
        $escala_id = $db->lastInsertId();
        
        // Definir los ítems
        $items = [
            [
                "texto" => "1. Tristeza\n0 No me siento triste habitualmente.\n1 Me siento triste gran parte del tiempo.\n2 Me siento triste continuamente.\n3 Me siento tan triste o infeliz que no puedo soportarlo.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No me siento triste, Me siento triste gran parte del tiempo, Me siento triste continuamente, Me siento tan triste o infeliz que no puedo soportarlo",
                "inversion" => 0,
                "subescala" => "afectivo"
            ],
            [
                "texto" => "2. Pesimismo\n0 No estoy desanimado sobre mi futuro.\n1 Me siento más desanimado sobre mi futuro que antes.\n2 No espero que las cosas mejoren.\n3 Siento que mi futuro es desesperanzador y que las cosas sólo empeorarán.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No estoy desanimado sobre mi futuro, Me siento más desanimado sobre mi futuro que antes, No espero que las cosas mejoren, Siento que mi futuro es desesperanzador y que las cosas sólo empeorarán",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ],
            [
                "texto" => "3. Sentimientos de fracaso\n0 No me siento fracasado.\n1 He fracasado más de lo que debería.\n2 Cuando miro atrás, veo fracaso tras fracaso.\n3 Me siento una persona totalmente fracasada.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No me siento fracasado, He fracasado más de lo que debería, Cuando miro atrás veo fracaso tras fracaso, Me siento una persona totalmente fracasada",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ],
            [
                "texto" => "4. Pérdida de placer\n0 Disfruto de las cosas que me gustan tanto como antes.\n1 No disfruto de las cosas tanto como antes.\n2 Obtengo muy poco placer de las cosas con las que antes disfrutaba.\n3 No obtengo ningún placer de las cosas con las que antes disfrutaba.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "Disfruto de las cosas que me gustan tanto como antes, No disfruto de las cosas tanto como antes, Obtengo muy poco placer de las cosas con las que antes disfrutaba, No obtengo ningún placer de las cosas con las que antes disfrutaba",
                "inversion" => 0,
                "subescala" => "afectivo"
            ],
            [
                "texto" => "5. Sentimientos de culpa\n0 No me siento especialmente culpable.\n1 Me siento culpable por muchas cosas que he hecho o debería haber hecho.\n2 Me siento bastante culpable la mayor parte del tiempo.\n3 Me siento culpable constantemente.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No me siento especialmente culpable, Me siento culpable por muchas cosas que he hecho o debería haber hecho, Me siento bastante culpable la mayor parte del tiempo, Me siento culpable constantemente",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ]
        ];
        
        // Añadir el resto de los ítems del BDI-II
        $itemsRestantes = [
            [
                "texto" => "6. Sentimientos de castigo\n0 No siento que esté siendo castigado.\n1 Siento que puedo ser castigado.\n2 Espero ser castigado.\n3 Siento que estoy siendo castigado.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No siento que esté siendo castigado, Siento que puedo ser castigado, Espero ser castigado, Siento que estoy siendo castigado",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ],
            [
                "texto" => "7. Insatisfacción con uno mismo\n0 Siento lo mismo que antes sobre mí mismo.\n1 He perdido confianza en mí mismo.\n2 Estoy decepcionado conmigo mismo.\n3 No me gusto.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "Siento lo mismo que antes sobre mí mismo, He perdido confianza en mí mismo, Estoy decepcionado conmigo mismo, No me gusto",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ],
            [
                "texto" => "8. Auto-crítica\n0 No me critico o me culpo más que antes.\n1 Soy más crítico conmigo mismo de lo que solía ser.\n2 Critico todos mis defectos.\n3 Me culpo por todo lo malo que sucede.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No me critico o me culpo más que antes, Soy más crítico conmigo mismo de lo que solía ser, Critico todos mis defectos, Me culpo por todo lo malo que sucede",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ],
            [
                "texto" => "9. Pensamientos o deseos de suicidio\n0 No tengo ningún pensamiento de suicidio.\n1 Tengo pensamientos de suicidio, pero no los llevaría a cabo.\n2 Me gustaría suicidarme.\n3 Me suicidaría si tuviera la oportunidad.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No tengo ningún pensamiento de suicidio, Tengo pensamientos de suicidio pero no los llevaría a cabo, Me gustaría suicidarme, Me suicidaría si tuviera la oportunidad",
                "inversion" => 0,
                "subescala" => "cognitivo"
            ],
            [
                "texto" => "10. Llanto\n0 No lloro más de lo que solía hacerlo.\n1 Lloro más de lo que solía hacerlo.\n2 Lloro por cualquier cosa.\n3 Tengo ganas de llorar pero no puedo.",
                "tipo_respuesta" => "seleccion_multiple",
                "opciones_respuesta" => "No lloro más de lo que solía hacerlo, Lloro más de lo que solía hacerlo, Lloro por cualquier cosa, Tengo ganas de llorar pero no puedo",
                "inversion" => 0,
                "subescala" => "afectivo"
            ]
        ];
        
        // Unir todos los ítems
        $items = array_merge($items, $itemsRestantes);
        
        // Insertar los ítems
        $stmt = $db->prepare("
            INSERT INTO escalas_items (
                escala_id, 
                numero, 
                texto, 
                tipo_respuesta, 
                opciones_respuesta, 
                inversion, 
                subescala
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $index => $item) {
            $stmt->execute([
                $escala_id,
                $index + 1,
                $item['texto'],
                $item['tipo_respuesta'],
                $item['opciones_respuesta'],
                $item['inversion'],
                $item['subescala']
            ]);
        }
        
        $db->commit();
        $mensaje = "El Inventario de Depresión de Beck (BDI-II) ha sido creado con éxito con " . count($items) . " ítems.";
    } catch (PDOException $e) {
        $db->rollBack();
        $error = true;
        $mensaje = "Error al crear la escala: " . $e->getMessage();
    }
} else {
    // La escala ya existe, verificar si tiene ítems
    $escala_id = $escala['id'];
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM escalas_items WHERE escala_id = ?");
    $stmt->execute([$escala_id]);
    $itemCount = $stmt->fetchColumn();
    
    if ($itemCount > 0) {
        $error = true;
        $mensaje = "El Inventario de Depresión de Beck (BDI-II) ya existe y tiene {$itemCount} ítems configurados.";
    } else {
        // La escala existe pero no tiene ítems, añadirlos
        try {
            $db->beginTransaction();
            
            // Definir los ítems
            $items = [
                [
                    "texto" => "1. Tristeza\n0 No me siento triste habitualmente.\n1 Me siento triste gran parte del tiempo.\n2 Me siento triste continuamente.\n3 Me siento tan triste o infeliz que no puedo soportarlo.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No me siento triste, Me siento triste gran parte del tiempo, Me siento triste continuamente, Me siento tan triste o infeliz que no puedo soportarlo",
                    "inversion" => 0,
                    "subescala" => "afectivo"
                ],
                [
                    "texto" => "2. Pesimismo\n0 No estoy desanimado sobre mi futuro.\n1 Me siento más desanimado sobre mi futuro que antes.\n2 No espero que las cosas mejoren.\n3 Siento que mi futuro es desesperanzador y que las cosas sólo empeorarán.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No estoy desanimado sobre mi futuro, Me siento más desanimado sobre mi futuro que antes, No espero que las cosas mejoren, Siento que mi futuro es desesperanzador y que las cosas sólo empeorarán",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ],
                [
                    "texto" => "3. Sentimientos de fracaso\n0 No me siento fracasado.\n1 He fracasado más de lo que debería.\n2 Cuando miro atrás, veo fracaso tras fracaso.\n3 Me siento una persona totalmente fracasada.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No me siento fracasado, He fracasado más de lo que debería, Cuando miro atrás veo fracaso tras fracaso, Me siento una persona totalmente fracasada",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ],
                [
                    "texto" => "4. Pérdida de placer\n0 Disfruto de las cosas que me gustan tanto como antes.\n1 No disfruto de las cosas tanto como antes.\n2 Obtengo muy poco placer de las cosas con las que antes disfrutaba.\n3 No obtengo ningún placer de las cosas con las que antes disfrutaba.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "Disfruto de las cosas que me gustan tanto como antes, No disfruto de las cosas tanto como antes, Obtengo muy poco placer de las cosas con las que antes disfrutaba, No obtengo ningún placer de las cosas con las que antes disfrutaba",
                    "inversion" => 0,
                    "subescala" => "afectivo"
                ],
                [
                    "texto" => "5. Sentimientos de culpa\n0 No me siento especialmente culpable.\n1 Me siento culpable por muchas cosas que he hecho o debería haber hecho.\n2 Me siento bastante culpable la mayor parte del tiempo.\n3 Me siento culpable constantemente.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No me siento especialmente culpable, Me siento culpable por muchas cosas que he hecho o debería haber hecho, Me siento bastante culpable la mayor parte del tiempo, Me siento culpable constantemente",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ]
            ];
            
            // Añadir el resto de los ítems del BDI-II
            $itemsRestantes = [
                [
                    "texto" => "6. Sentimientos de castigo\n0 No siento que esté siendo castigado.\n1 Siento que puedo ser castigado.\n2 Espero ser castigado.\n3 Siento que estoy siendo castigado.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No siento que esté siendo castigado, Siento que puedo ser castigado, Espero ser castigado, Siento que estoy siendo castigado",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ],
                [
                    "texto" => "7. Insatisfacción con uno mismo\n0 Siento lo mismo que antes sobre mí mismo.\n1 He perdido confianza en mí mismo.\n2 Estoy decepcionado conmigo mismo.\n3 No me gusto.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "Siento lo mismo que antes sobre mí mismo, He perdido confianza en mí mismo, Estoy decepcionado conmigo mismo, No me gusto",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ],
                [
                    "texto" => "8. Auto-crítica\n0 No me critico o me culpo más que antes.\n1 Soy más crítico conmigo mismo de lo que solía ser.\n2 Critico todos mis defectos.\n3 Me culpo por todo lo malo que sucede.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No me critico o me culpo más que antes, Soy más crítico conmigo mismo de lo que solía ser, Critico todos mis defectos, Me culpo por todo lo malo que sucede",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ],
                [
                    "texto" => "9. Pensamientos o deseos de suicidio\n0 No tengo ningún pensamiento de suicidio.\n1 Tengo pensamientos de suicidio, pero no los llevaría a cabo.\n2 Me gustaría suicidarme.\n3 Me suicidaría si tuviera la oportunidad.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No tengo ningún pensamiento de suicidio, Tengo pensamientos de suicidio pero no los llevaría a cabo, Me gustaría suicidarme, Me suicidaría si tuviera la oportunidad",
                    "inversion" => 0,
                    "subescala" => "cognitivo"
                ],
                [
                    "texto" => "10. Llanto\n0 No lloro más de lo que solía hacerlo.\n1 Lloro más de lo que solía hacerlo.\n2 Lloro por cualquier cosa.\n3 Tengo ganas de llorar pero no puedo.",
                    "tipo_respuesta" => "seleccion_multiple",
                    "opciones_respuesta" => "No lloro más de lo que solía hacerlo, Lloro más de lo que solía hacerlo, Lloro por cualquier cosa, Tengo ganas de llorar pero no puedo",
                    "inversion" => 0,
                    "subescala" => "afectivo"
                ]
            ];
            
            // Unir todos los ítems
            $items = array_merge($items, $itemsRestantes);
            
            // Insertar los ítems
            $stmt = $db->prepare("
                INSERT INTO escalas_items (
                    escala_id, 
                    numero, 
                    texto, 
                    tipo_respuesta, 
                    opciones_respuesta, 
                    inversion, 
                    subescala
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($items as $index => $item) {
                $stmt->execute([
                    $escala_id,
                    $index + 1,
                    $item['texto'],
                    $item['tipo_respuesta'],
                    $item['opciones_respuesta'],
                    $item['inversion'],
                    $item['subescala']
                ]);
            }
            
            $db->commit();
            $mensaje = "Se han añadido " . count($items) . " ítems al Inventario de Depresión de Beck (BDI-II) existente.";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = true;
            $mensaje = "Error al añadir ítems a la escala: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">psychology</span>
        Carga de Ítems - Inventario de Depresión de Beck (BDI-II)
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Carga de Ítems</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $error ? 'danger' : 'success' ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header <?= $error ? 'bg-danger' : 'bg-success' ?> text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1"><?= $error ? 'error' : 'check_circle' ?></span>
                        Resultado de la Operación
                    </h5>
                </div>
                <div class="card-body">
                    <p><?= $mensaje ?></p>
                    
                    <?php if (!$error && $escala_id): ?>
                        <div class="mt-4">
                            <a href="info_escala.php?id=<?= $escala_id ?>" class="btn btn-primary">
                                <span class="material-symbols-rounded me-1">visibility</span> Ver Escala
                            </a>
                            
                            <a href="administrar_items.php?escala_id=<?= $escala_id ?>" class="btn btn-secondary">
                                <span class="material-symbols-rounded me-1">edit</span> Editar Ítems
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <span class="material-symbols-rounded me-1">arrow_back</span> Volver al Listado
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Información sobre la escala -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">info</span>
                        Sobre el Inventario de Depresión de Beck (BDI-II)
                    </h5>
                </div>
                <div class="card-body">
                    <p>El Inventario de Depresión de Beck (BDI-II) es uno de los instrumentos más utilizados para la evaluación de síntomas depresivos tanto en poblaciones clínicas como no clínicas. Desarrollado por Aaron T. Beck, el BDI-II es la versión actualizada del BDI original y fue diseñado para evaluar la gravedad de la depresión de acuerdo con los criterios del DSM-IV.</p>
                    
                    <h6 class="mt-4">Características:</h6>
                    <ul>
                        <li><strong>Ítems:</strong> 21 preguntas</li>
                        <li><strong>Tipo de respuesta:</strong> Escala de 0 a 3 puntos para cada ítem</li>
                        <li><strong>Tiempo de aplicación:</strong> 5-10 minutos</li>
                        <li><strong>Población:</strong> Adolescentes y adultos (a partir de 13 años)</li>
                    </ul>
                    
                    <h6 class="mt-4">Interpretación:</h6>
                    <p>La puntuación total oscila entre 0 y 63 puntos. Las siguientes directrices han sido sugeridas para interpretar las puntuaciones:</p>
                    <ul>
                        <li><strong>0-13:</strong> Depresión mínima</li>
                        <li><strong>14-19:</strong> Depresión leve</li>
                        <li><strong>20-28:</strong> Depresión moderada</li>
                        <li><strong>29-63:</strong> Depresión grave</li>
                    </ul>
                    
                    <div class="alert alert-warning mt-4">
                        <strong>Nota importante:</strong> El BDI-II es una herramienta de screening, no de diagnóstico. Una puntuación alta sugiere la presencia de síntomas depresivos significativos, pero siempre debe ser complementada con una evaluación clínica profesional.
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