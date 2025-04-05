<?php
/**
 * Módulo de Escalas Psicológicas - Carga de Ítems GHQ-28
 * Este script carga automáticamente el Cuestionario de Salud General de Goldberg (GHQ-28)
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
$pageTitle = "Carga de Ítems - Cuestionario de Salud General de Goldberg (GHQ-28)";

// Comprobar si debemos usar la conexión remota
$useRemote = isset($_GET['remote']) && $_GET['remote'] == '1';

if ($useRemote) {
    // Usar la conexión remota
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
        
        $db = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error de conexión a la base de datos remota: " . $e->getMessage();
        header('Location: admin_scripts.php');
        exit;
    }
} else {
    // Usar la conexión normal
    $db = getDB();
}

// Iniciar captura del contenido de la página
startPageContent();

// Variable para almacenar mensajes
$mensaje = '';
$error = false;
$itemsInsertados = 0;

// Comprobar si la escala ya existe
$stmt = $db->prepare("SELECT id FROM escalas_catalogo WHERE nombre LIKE ?");
$nombreEscala = "Cuestionario de Salud General de Goldberg (GHQ-28)";
$stmt->execute([$nombreEscala]);
$escalaExistente = $stmt->fetch(PDO::FETCH_ASSOC);

// Comenzar transacción
$db->beginTransaction();

try {
    // Si la escala no existe, crearla
    if (!$escalaExistente) {
        $stmt = $db->prepare("
            INSERT INTO escalas_catalogo 
            (nombre, descripcion, poblacion, instrucciones, tiempo_estimado, referencia_bibliografica)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nombreEscala,
            "El GHQ-28 es un cuestionario de cribado diseñado para detectar trastornos psiquiátricos no psicóticos en entornos comunitarios. Consta de 28 ítems divididos en cuatro subescalas que evalúan síntomas somáticos, ansiedad e insomnio, disfunción social y depresión grave.",
            "adultos",
            "Por favor, lea cuidadosamente estas preguntas. Nos gustaría saber si usted ha tenido algunas molestias o trastornos y cómo ha estado de salud en las últimas semanas. Conteste a todas las preguntas marcando la respuesta que, según su criterio, mejor se adapta a su situación. Recuerde que solo debe responder sobre los problemas recientes y actuales, no sobre los que tuvo en el pasado.",
            "5-10 minutos",
            "Goldberg, D.P. & Hillier, V.F. (1979). A scaled version of the General Health Questionnaire. Psychological Medicine, 9, 139-145."
        ]);
        
        $escalaId = $db->lastInsertId();
        $mensaje = "Se ha creado la escala '$nombreEscala' con ID: $escalaId";
    } else {
        $escalaId = $escalaExistente['id'];
        $mensaje = "La escala '$nombreEscala' ya existe con ID: $escalaId. ";
        
        // Verificar si ya tiene ítems
        $stmt = $db->prepare("SELECT COUNT(*) FROM escalas_items WHERE escala_id = ?");
        $stmt->execute([$escalaId]);
        $itemsCount = $stmt->fetchColumn();
        
        if ($itemsCount > 0) {
            $mensaje .= "La escala ya tiene $itemsCount ítems cargados.";
            $db->commit();
            $_SESSION['success'] = $mensaje;
            header('Location: ' . ($useRemote ? 'config_db_remota.php' : 'admin_scripts.php'));
            exit;
        } else {
            $mensaje .= "Se procederá a cargar los ítems.";
        }
    }
    
    // Definir los ítems de la escala GHQ-28
    $items = [
        // Subescala A: Síntomas somáticos
        ["¿Se ha sentido perfectamente bien de salud y en plena forma?", "likert4", 0, "A", 1],
        ["¿Ha tenido la sensación de que necesitaba un reconstituyente?", "likert4", 0, "A", 2],
        ["¿Se ha sentido agotado y sin fuerzas para nada?", "likert4", 0, "A", 3],
        ["¿Ha tenido la sensación de que estaba enfermo?", "likert4", 0, "A", 4],
        ["¿Ha padecido dolores de cabeza?", "likert4", 0, "A", 5],
        ["¿Ha tenido sensación de opresión en la cabeza, o de que la cabeza le va a estallar?", "likert4", 0, "A", 6],
        ["¿Ha tenido oleadas de calor o escalofríos?", "likert4", 0, "A", 7],
        
        // Subescala B: Ansiedad e insomnio
        ["¿Sus preocupaciones le han hecho perder mucho sueño?", "likert4", 0, "B", 8],
        ["¿Ha tenido dificultades para seguir durmiendo de un tirón toda la noche?", "likert4", 0, "B", 9],
        ["¿Se ha notado constantemente agobiado y en tensión?", "likert4", 0, "B", 10],
        ["¿Se ha sentido con los nervios a flor de piel y malhumorado?", "likert4", 0, "B", 11],
        ["¿Se ha asustado o ha tenido pánico sin motivo?", "likert4", 0, "B", 12],
        ["¿Ha tenido la sensación de que todo se le viene encima?", "likert4", 0, "B", 13],
        ["¿Se ha notado nervioso y 'a punto de explotar' constantemente?", "likert4", 0, "B", 14],
        
        // Subescala C: Disfunción social
        ["¿Se las ha arreglado para mantenerse ocupado y activo?", "likert4", 0, "C", 15],
        ["¿Le cuesta más tiempo hacer las cosas?", "likert4", 0, "C", 16],
        ["¿Ha tenido la impresión de que está haciendo las cosas bien?", "likert4", 0, "C", 17],
        ["¿Se ha sentido satisfecho con su manera de hacer las cosas?", "likert4", 0, "C", 18],
        ["¿Ha sentido que está desempeñando un papel útil en la vida?", "likert4", 0, "C", 19],
        ["¿Se ha sentido capaz de tomar decisiones?", "likert4", 0, "C", 20],
        ["¿Ha sido capaz de disfrutar de sus actividades normales de cada día?", "likert4", 0, "C", 21],
        
        // Subescala D: Depresión grave
        ["¿Ha pensado que usted es una persona que no vale para nada?", "likert4", 0, "D", 22],
        ["¿Ha estado viviendo la vida sin esperanza?", "likert4", 0, "D", 23],
        ["¿Ha tenido el sentimiento de que la vida no merece la pena vivirse?", "likert4", 0, "D", 24],
        ["¿Ha pensado en la posibilidad de 'quitarse de en medio'?", "likert4", 0, "D", 25],
        ["¿Ha notado que a veces no puede hacer nada porque tiene los nervios desquiciados?", "likert4", 0, "D", 26],
        ["¿Ha notado que desea estar muerto y lejos de todo?", "likert4", 0, "D", 27],
        ["¿Ha notado que la idea de quitarse la vida le viene repentinamente a la cabeza?", "likert4", 0, "D", 28]
    ];
    
    // Preparar opciones de respuesta para la escala Likert-4
    $opcionesRespuesta = json_encode([
        ["valor" => "0", "texto" => "No, en absoluto"],
        ["valor" => "1", "texto" => "No más que lo habitual"],
        ["valor" => "2", "texto" => "Bastante más que lo habitual"],
        ["valor" => "3", "texto" => "Mucho más que lo habitual"]
    ]);
    
    // Insertar los ítems
    $stmt = $db->prepare("
        INSERT INTO escalas_items 
        (escala_id, numero, texto, tipo_respuesta, opciones_respuesta, inversion, subescala) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        $stmt->execute([
            $escalaId,
            $item[4],  // número del ítem
            $item[0],  // texto
            $item[1],  // tipo de respuesta
            $opcionesRespuesta, // opciones de respuesta
            $item[2],  // inversión
            $item[3]   // subescala
        ]);
        $itemsInsertados++;
    }
    
    // Confirmar la transacción
    $db->commit();
    
    $mensaje .= " Se han insertado $itemsInsertados ítems correctamente.";
    $_SESSION['success'] = $mensaje;
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $db->rollBack();
    $error = true;
    $mensaje = "Error al cargar los ítems: " . $e->getMessage();
    $_SESSION['error'] = $mensaje;
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">psychology</span>
        Carga de Ítems - Cuestionario de Salud General de Goldberg (GHQ-28)
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Carga de Ítems GHQ-28</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Resultado de la Operación
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-4">
                            <h5 class="alert-heading">Error</h5>
                            <p><?= $mensaje ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-4">
                            <h5 class="alert-heading">Éxito</h5>
                            <p><?= $mensaje ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="m-0">Información sobre el GHQ-28</h5>
                        </div>
                        <div class="card-body">
                            <h5>Características de la Escala</h5>
                            <ul>
                                <li><strong>Nombre completo:</strong> Cuestionario de Salud General de Goldberg (versión de 28 ítems)</li>
                                <li><strong>Autor:</strong> David Goldberg y Hillary Hillier</li>
                                <li><strong>Objetivo:</strong> Detectar trastornos psiquiátricos no psicóticos en entornos comunitarios</li>
                                <li><strong>Estructura:</strong> 28 ítems divididos en cuatro subescalas de 7 ítems cada una:
                                    <ul>
                                        <li><strong>Subescala A:</strong> Síntomas somáticos</li>
                                        <li><strong>Subescala B:</strong> Ansiedad e insomnio</li>
                                        <li><strong>Subescala C:</strong> Disfunción social</li>
                                        <li><strong>Subescala D:</strong> Depresión grave</li>
                                    </ul>
                                </li>
                                <li><strong>Puntuación:</strong> Escala tipo Likert de 4 puntos (0-3)</li>
                            </ul>
                            
                            <h5>Interpretación</h5>
                            <ul>
                                <li>El cuestionario puede puntuarse de dos formas:
                                    <ul>
                                        <li><strong>Método GHQ:</strong> Las respuestas se puntúan 0, 0, 1, 1 (recomendado para detectar casos)</li>
                                        <li><strong>Método Likert:</strong> Las respuestas se puntúan 0, 1, 2, 3 (útil para comparaciones entre grupos)</li>
                                    </ul>
                                </li>
                                <li><strong>Puntos de corte (Método GHQ):</strong>
                                    <ul>
                                        <li>0-4: No hay evidencia de psicopatología</li>
                                        <li>5-6: Sospecha de psicopatología subumbral</li>
                                        <li>7 o más: Indica probable presencia de psicopatología</li>
                                    </ul>
                                </li>
                                <li><strong>Análisis por subescalas:</strong> Permite perfilar el tipo específico de problema de salud mental que pueda estar presente</li>
                            </ul>
                            
                            <div class="alert alert-warning">
                                <span class="material-symbols-rounded me-1">warning</span>
                                <strong>Nota importante:</strong> El GHQ-28 es un instrumento de cribado, no de diagnóstico. Puntuaciones elevadas indican la necesidad de una evaluación más detallada.
                            </div>
                        </div>
                    </div>
                    
                    <a href="<?= $useRemote ? 'config_db_remota.php' : 'admin_scripts.php' ?>" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">arrow_back</span> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 