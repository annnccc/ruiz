<?php
/**
 * Módulo de Escalas Psicológicas - Carga de Ítems STAI
 * Este script carga automáticamente el Inventario de Ansiedad Estado-Rasgo (STAI)
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
$pageTitle = "Carga de Ítems - Inventario de Ansiedad Estado-Rasgo (STAI)";

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
$nombreEscala = "Inventario de Ansiedad Estado-Rasgo (STAI)";
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
            "El STAI es un instrumento que mide dos dimensiones independientes de la ansiedad: la ansiedad como estado (condición emocional transitoria) y la ansiedad como rasgo (propensión ansiosa relativamente estable). Consta de dos subescalas con 20 ítems cada una.",
            "adultos",
            "A continuación encontrará unas frases que se utilizan para describirse uno a sí mismo. Lea cada frase y señale la respuesta que mejor indique cómo se SIENTE EN GENERAL en la escala de Ansiedad-Rasgo, y cómo se SIENTE AHORA MISMO, en este momento, en la escala de Ansiedad-Estado.",
            "15-20 minutos",
            "Spielberger, C. D., Gorsuch, R. L., Lushene, R., Vagg, P. R., & Jacobs, G. A. (1983). Manual for the State-Trait Anxiety Inventory. Consulting Psychologists Press."
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
    
    // Definir los ítems de la escala STAI
    $items = [
        // Items de Ansiedad-Estado (STAI-E)
        ["Me siento calmado", "likert4", 0, "estado", 1],
        ["Me siento seguro", "likert4", 0, "estado", 2],
        ["Estoy tenso", "likert4", 0, "estado", 3],
        ["Estoy contrariado", "likert4", 0, "estado", 4],
        ["Me siento cómodo (estoy a gusto)", "likert4", 0, "estado", 5],
        ["Me siento alterado", "likert4", 0, "estado", 6],
        ["Estoy preocupado ahora por posibles desgracias futuras", "likert4", 0, "estado", 7],
        ["Me siento descansado", "likert4", 0, "estado", 8],
        ["Me siento angustiado", "likert4", 0, "estado", 9],
        ["Me siento confortable", "likert4", 0, "estado", 10],
        ["Tengo confianza en mí mismo", "likert4", 0, "estado", 11],
        ["Me siento nervioso", "likert4", 0, "estado", 12],
        ["Estoy desasosegado", "likert4", 0, "estado", 13],
        ["Me siento muy 'atado' (como oprimido)", "likert4", 0, "estado", 14],
        ["Estoy relajado", "likert4", 0, "estado", 15],
        ["Me siento satisfecho", "likert4", 0, "estado", 16],
        ["Estoy preocupado", "likert4", 0, "estado", 17],
        ["Me siento aturdido y sobreexcitado", "likert4", 0, "estado", 18],
        ["Me siento alegre", "likert4", 0, "estado", 19],
        ["En este momento me siento bien", "likert4", 0, "estado", 20],
        
        // Items de Ansiedad-Rasgo (STAI-R)
        ["Me siento bien", "likert4", 0, "rasgo", 21],
        ["Me canso rápidamente", "likert4", 0, "rasgo", 22],
        ["Siento ganas de llorar", "likert4", 0, "rasgo", 23],
        ["Me gustaría ser tan feliz como otros", "likert4", 0, "rasgo", 24],
        ["Pierdo oportunidades por no decidirme pronto", "likert4", 0, "rasgo", 25],
        ["Me siento descansado", "likert4", 0, "rasgo", 26],
        ["Soy una persona tranquila, serena y sosegada", "likert4", 0, "rasgo", 27],
        ["Veo que las dificultades se amontonan y no puedo con ellas", "likert4", 0, "rasgo", 28],
        ["Me preocupo demasiado por cosas sin importancia", "likert4", 0, "rasgo", 29],
        ["Soy feliz", "likert4", 0, "rasgo", 30],
        ["Suelo tomar las cosas demasiado seriamente", "likert4", 0, "rasgo", 31],
        ["Me falta confianza en mí mismo", "likert4", 0, "rasgo", 32],
        ["Me siento seguro", "likert4", 0, "rasgo", 33],
        ["No suelo afrontar las crisis o dificultades", "likert4", 0, "rasgo", 34],
        ["Me siento triste (melancólico)", "likert4", 0, "rasgo", 35],
        ["Estoy satisfecho", "likert4", 0, "rasgo", 36],
        ["Me rondan y molestan pensamientos sin importancia", "likert4", 0, "rasgo", 37],
        ["Me afectan tanto los desengaños que no puedo olvidarlos", "likert4", 0, "rasgo", 38],
        ["Soy una persona estable", "likert4", 0, "rasgo", 39],
        ["Cuando pienso sobre asuntos y preocupaciones actuales, me pongo tenso y agitado", "likert4", 0, "rasgo", 40]
    ];
    
    // Preparar opciones de respuesta para la escala Likert-4
    $opcionesRespuestaEstado = json_encode([
        ["valor" => "1", "texto" => "Nada"],
        ["valor" => "2", "texto" => "Algo"],
        ["valor" => "3", "texto" => "Bastante"],
        ["valor" => "4", "texto" => "Mucho"]
    ]);
    
    $opcionesRespuestaRasgo = json_encode([
        ["valor" => "1", "texto" => "Casi nunca"],
        ["valor" => "2", "texto" => "A veces"],
        ["valor" => "3", "texto" => "A menudo"],
        ["valor" => "4", "texto" => "Casi siempre"]
    ]);
    
    // Insertar los ítems
    $stmt = $db->prepare("
        INSERT INTO escalas_items 
        (escala_id, numero, texto, tipo_respuesta, opciones_respuesta, inversion, subescala) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        $opciones = $item[3] === "estado" ? $opcionesRespuestaEstado : $opcionesRespuestaRasgo;
        $stmt->execute([
            $escalaId,
            $item[4],  // número del ítem
            $item[0],  // texto
            $item[1],  // tipo de respuesta
            $opciones, // opciones de respuesta
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
        Carga de Ítems - Inventario de Ansiedad Estado-Rasgo (STAI)
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Carga de Ítems STAI</li>
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
                            <h5 class="m-0">Información sobre el STAI</h5>
                        </div>
                        <div class="card-body">
                            <h5>Características de la Escala</h5>
                            <ul>
                                <li><strong>Nombre completo:</strong> Inventario de Ansiedad Estado-Rasgo</li>
                                <li><strong>Autor:</strong> Spielberger, Gorsuch y Lushene</li>
                                <li><strong>Objetivo:</strong> Evaluar el nivel actual de ansiedad y la predisposición a responder al estrés de manera ansiosa</li>
                                <li><strong>Estructura:</strong> Dos subescalas, cada una con 20 ítems:
                                    <ul>
                                        <li><strong>Ansiedad-Estado (A/E):</strong> Evalúa cómo se siente la persona en este momento</li>
                                        <li><strong>Ansiedad-Rasgo (A/R):</strong> Evalúa cómo se siente la persona generalmente</li>
                                    </ul>
                                </li>
                                <li><strong>Puntuación:</strong> Escala tipo Likert de 4 puntos (diferente para cada subescala)</li>
                            </ul>
                            
                            <h5>Interpretación</h5>
                            <ul>
                                <li><strong>Puntuación A/E:</strong> Refleja sentimientos subjetivos de tensión, aprensión y nerviosismo</li>
                                <li><strong>Puntuación A/R:</strong> Indica la propensión ansiosa relativamente estable de la persona</li>
                                <li><strong>Puntuaciones más altas:</strong> Indican mayores niveles de ansiedad</li>
                                <li><strong>Rangos:</strong> 
                                    <ul>
                                        <li>20-31: Ansiedad muy baja</li>
                                        <li>32-43: Ansiedad baja</li>
                                        <li>44-55: Ansiedad media</li>
                                        <li>56-67: Ansiedad alta</li>
                                        <li>68-80: Ansiedad muy alta</li>
                                    </ul>
                                </li>
                            </ul>
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