<?php
/**
 * Módulo de Escalas Psicológicas - Carga de ítems de la Escala de Autoestima de Rosenberg
 * Este script carga automáticamente los ítems de la Escala de Autoestima de Rosenberg
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
$pageTitle = "Carga de Ítems - Escala de Autoestima de Rosenberg";

// Iniciar captura del contenido de la página
startPageContent();

// Definir el nombre de la escala
$nombreEscala = "Escala de Autoestima de Rosenberg";

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
            "La Escala de Autoestima de Rosenberg es un instrumento ampliamente utilizado para evaluar la autoestima global. Desarrollada por Morris Rosenberg en 1965, esta escala mide los sentimientos de valía personal y respeto hacia uno mismo.",
            "adultos",
            "A continuación encontrará una lista de afirmaciones sobre sentimientos generales acerca de usted mismo. Por favor, indique en qué grado está de acuerdo o en desacuerdo con cada afirmación.",
            "5-10 minutos",
            "Rosenberg, M. (1965). Society and the adolescent self-image. Princeton, NJ: Princeton University Press."
        ]);
        
        $escala_id = $db->lastInsertId();
        
        // Definir los ítems
        $items = [
            [
                "texto" => "En general, estoy satisfecho conmigo mismo.",
                "tipo_respuesta" => "likert4",
                "inversion" => 0,
                "subescala" => null
            ],
            [
                "texto" => "A veces pienso que no soy bueno en nada.",
                "tipo_respuesta" => "likert4",
                "inversion" => 1,
                "subescala" => null
            ],
            [
                "texto" => "Tengo la sensación de que poseo algunas buenas cualidades.",
                "tipo_respuesta" => "likert4",
                "inversion" => 0,
                "subescala" => null
            ],
            [
                "texto" => "Soy capaz de hacer las cosas tan bien como la mayoría de las personas.",
                "tipo_respuesta" => "likert4",
                "inversion" => 0,
                "subescala" => null
            ],
            [
                "texto" => "Siento que no tengo demasiadas cosas de las que sentirme orgulloso.",
                "tipo_respuesta" => "likert4",
                "inversion" => 1,
                "subescala" => null
            ],
            [
                "texto" => "A veces me siento realmente inútil.",
                "tipo_respuesta" => "likert4",
                "inversion" => 1,
                "subescala" => null
            ],
            [
                "texto" => "Tengo la sensación de que soy una persona de valía, al menos igual que la mayoría de la gente.",
                "tipo_respuesta" => "likert4",
                "inversion" => 0,
                "subescala" => null
            ],
            [
                "texto" => "Ojalá me respetara más a mí mismo.",
                "tipo_respuesta" => "likert4",
                "inversion" => 1,
                "subescala" => null
            ],
            [
                "texto" => "En definitiva, tiendo a pensar que soy un fracasado.",
                "tipo_respuesta" => "likert4",
                "inversion" => 1,
                "subescala" => null
            ],
            [
                "texto" => "Tengo una actitud positiva hacia mí mismo.",
                "tipo_respuesta" => "likert4",
                "inversion" => 0,
                "subescala" => null
            ]
        ];
        
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
                null, // No hay opciones personalizadas
                $item['inversion'],
                $item['subescala']
            ]);
        }
        
        $db->commit();
        $mensaje = "La Escala de Autoestima de Rosenberg ha sido creada con éxito con sus 10 ítems.";
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
        $mensaje = "La Escala de Autoestima de Rosenberg ya existe y tiene {$itemCount} ítems configurados.";
    } else {
        // La escala existe pero no tiene ítems, añadirlos
        try {
            $db->beginTransaction();
            
            // Definir los ítems
            $items = [
                [
                    "texto" => "En general, estoy satisfecho conmigo mismo.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 0,
                    "subescala" => null
                ],
                [
                    "texto" => "A veces pienso que no soy bueno en nada.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 1,
                    "subescala" => null
                ],
                [
                    "texto" => "Tengo la sensación de que poseo algunas buenas cualidades.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 0,
                    "subescala" => null
                ],
                [
                    "texto" => "Soy capaz de hacer las cosas tan bien como la mayoría de las personas.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 0,
                    "subescala" => null
                ],
                [
                    "texto" => "Siento que no tengo demasiadas cosas de las que sentirme orgulloso.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 1,
                    "subescala" => null
                ],
                [
                    "texto" => "A veces me siento realmente inútil.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 1,
                    "subescala" => null
                ],
                [
                    "texto" => "Tengo la sensación de que soy una persona de valía, al menos igual que la mayoría de la gente.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 0,
                    "subescala" => null
                ],
                [
                    "texto" => "Ojalá me respetara más a mí mismo.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 1,
                    "subescala" => null
                ],
                [
                    "texto" => "En definitiva, tiendo a pensar que soy un fracasado.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 1,
                    "subescala" => null
                ],
                [
                    "texto" => "Tengo una actitud positiva hacia mí mismo.",
                    "tipo_respuesta" => "likert4",
                    "inversion" => 0,
                    "subescala" => null
                ]
            ];
            
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
                    null, // No hay opciones personalizadas
                    $item['inversion'],
                    $item['subescala']
                ]);
            }
            
            $db->commit();
            $mensaje = "Se han añadido 10 ítems a la Escala de Autoestima de Rosenberg existente.";
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
        Carga de Ítems - Escala de Autoestima de Rosenberg
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
                        Sobre la Escala de Autoestima de Rosenberg
                    </h5>
                </div>
                <div class="card-body">
                    <p>La Escala de Autoestima de Rosenberg es uno de los instrumentos más utilizados para la evaluación de la autoestima global. Desarrollada por Morris Rosenberg en 1965, consta de 10 ítems relacionados con los sentimientos de respeto y aceptación de uno mismo.</p>
                    
                    <h6 class="mt-4">Características:</h6>
                    <ul>
                        <li><strong>Ítems:</strong> 10 preguntas</li>
                        <li><strong>Tipo de respuesta:</strong> Escala Likert de 4 puntos (Muy en desacuerdo a Muy de acuerdo)</li>
                        <li><strong>Tiempo de aplicación:</strong> 5-10 minutos</li>
                        <li><strong>Población:</strong> Adolescentes, jóvenes y adultos</li>
                    </ul>
                    
                    <h6 class="mt-4">Interpretación:</h6>
                    <p>La puntuación total oscila entre 10 y 40 puntos. Las puntuaciones más altas indican mayor autoestima.</p>
                    <ul>
                        <li><strong>De 30 a 40 puntos:</strong> Autoestima elevada. Considerada como autoestima normal.</li>
                        <li><strong>De 26 a 29 puntos:</strong> Autoestima media. No presenta problemas graves pero es conveniente mejorarla.</li>
                        <li><strong>Menos de 25 puntos:</strong> Autoestima baja. Existen problemas significativos de autoestima.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 