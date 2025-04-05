<?php
/**
 * Módulo de Escalas Psicológicas - Carga de Ítems BASC
 * Este script carga automáticamente el Sistema de Evaluación de Conducta de Niños y Adolescentes (BASC)
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
$pageTitle = "Carga de Ítems - Sistema de Evaluación de Conducta de Niños y Adolescentes (BASC)";

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
$nombreEscala = "Sistema de Evaluación de Conducta de Niños y Adolescentes (BASC)";
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
            "El BASC es un sistema de evaluación multidimensional diseñado para evaluar aspectos del comportamiento y la personalidad, incluyendo dimensiones tanto positivas (adaptativas) como negativas (clínicas). Incluye diferentes cuestionarios según el informante (padres, profesores, autoinforme) y la edad del evaluado.",
            "niños",
            "Este cuestionario contiene frases que describen cómo pueden actuar los niños y jóvenes, cómo pueden pensar o cómo pueden sentirse. Lea cada frase y elija la respuesta que mejor describa la conducta del niño/a o adolescente durante los últimos seis meses. No hay respuestas correctas o incorrectas.",
            "30-40 minutos",
            "Reynolds, C. R., & Kamphaus, R. W. (2004). Sistema de Evaluación de la Conducta de Niños y Adolescentes (BASC). Madrid: TEA Ediciones."
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
    
    // Definir los ítems del BASC (versión reducida de ejemplo)
    // Normalmente, el BASC tiene más de 100 ítems en cada versión
    $items = [
        // Cuestionario para Padres (P) - Ítems de ejemplo
        ["Acepta ir al colegio", "likert4", 0, "P-Adaptativo", 1],
        ["Es incapaz de esperar para conseguir lo que quiere", "likert4", 0, "P-Clínico", 2],
        ["Respeta las normas", "likert4", 0, "P-Adaptativo", 3],
        ["Actúa sin pensar", "likert4", 0, "P-Clínico", 4],
        ["Muestra interés por las ideas de los demás", "likert4", 0, "P-Adaptativo", 5],
        ["Se queja de mareos", "likert4", 0, "P-Clínico", 6],
        ["Tiene amigos que se meten en problemas", "likert4", 0, "P-Clínico", 7],
        ["Participa en actividades familiares", "likert4", 0, "P-Adaptativo", 8],
        ["Se preocupa por cosas que no puede cambiar", "likert4", 0, "P-Clínico", 9],
        ["Comparte sus cosas con otros niños", "likert4", 0, "P-Adaptativo", 10],
        
        // Cuestionario para Profesores (T) - Ítems de ejemplo
        ["Presta atención", "likert4", 0, "T-Adaptativo", 11],
        ["Interrumpe a los demás cuando están hablando", "likert4", 0, "T-Clínico", 12],
        ["Es considerado con los demás", "likert4", 0, "T-Adaptativo", 13],
        ["Discute con los adultos", "likert4", 0, "T-Clínico", 14],
        ["Termina sus tareas", "likert4", 0, "T-Adaptativo", 15],
        ["Se aísla de los demás", "likert4", 0, "T-Clínico", 16],
        ["Anima a los demás", "likert4", 0, "T-Adaptativo", 17],
        ["Parece no estar en contacto con la realidad", "likert4", 0, "T-Clínico", 18],
        ["Trabaja bien en grupo", "likert4", 0, "T-Adaptativo", 19],
        ["Se distrae fácilmente de sus tareas", "likert4", 0, "T-Clínico", 20],
        
        // Autoinforme (S) - Ítems de ejemplo
        ["Siento que puedo tomar buenas decisiones", "likert4", 0, "S-Adaptativo", 21],
        ["Me pongo nervioso cuando las cosas no salen como quiero", "likert4", 0, "S-Clínico", 22],
        ["Mis amigos suelen ser amables conmigo", "likert4", 0, "S-Adaptativo", 23],
        ["Me preocupa lo que la gente piensa de mí", "likert4", 0, "S-Clínico", 24],
        ["Me gusta cómo soy", "likert4", 0, "S-Adaptativo", 25],
        ["Nada me hace realmente feliz", "likert4", 0, "S-Clínico", 26],
        ["Me gusta probar cosas nuevas", "likert4", 0, "S-Adaptativo", 27],
        ["Me siento diferente a los demás", "likert4", 0, "S-Clínico", 28],
        ["Me gusta ayudar a los demás", "likert4", 0, "S-Adaptativo", 29],
        ["La gente siempre encuentra fallos en mí", "likert4", 0, "S-Clínico", 30]
    ];
    
    // Preparar opciones de respuesta para la escala Likert-4
    $opcionesRespuesta = json_encode([
        ["valor" => "0", "texto" => "Nunca"],
        ["valor" => "1", "texto" => "A veces"],
        ["valor" => "2", "texto" => "Con frecuencia"],
        ["valor" => "3", "texto" => "Casi siempre"]
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
        Carga de Ítems - Sistema BASC
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Carga de Ítems BASC</li>
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
                            <h5 class="m-0">Información sobre el Sistema BASC</h5>
                        </div>
                        <div class="card-body">
                            <h5>Características del Sistema</h5>
                            <ul>
                                <li><strong>Nombre completo:</strong> Sistema de Evaluación de la Conducta de Niños y Adolescentes</li>
                                <li><strong>Autor:</strong> C.R. Reynolds y R.W. Kamphaus</li>
                                <li><strong>Objetivo:</strong> Evaluar una amplia gama de dimensiones patológicas y adaptativas en diferentes contextos</li>
                                <li><strong>Aplicación:</strong> Individual</li>
                                <li><strong>Edades:</strong> De 3 a 18 años (diferentes niveles según edad)</li>
                                <li><strong>Fuentes de información:</strong> 
                                    <ul>
                                        <li><strong>Padres (P):</strong> Evalúan la conducta observable del niño en contexto familiar/social</li>
                                        <li><strong>Profesores (T):</strong> Evalúan la conducta observable del niño en contexto escolar</li>
                                        <li><strong>Autoinforme (S):</strong> El propio niño/adolescente evalúa sus sentimientos y percepciones</li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <h5>Dimensiones evaluadas</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Escalas Clínicas</h6>
                                    <ul>
                                        <li>Agresividad</li>
                                        <li>Hiperactividad</li>
                                        <li>Problemas de conducta</li>
                                        <li>Problemas de atención</li>
                                        <li>Problemas de aprendizaje</li>
                                        <li>Depresión</li>
                                        <li>Ansiedad</li>
                                        <li>Somatización</li>
                                        <li>Atipicidad</li>
                                        <li>Retraimiento</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Escalas Adaptativas</h6>
                                    <ul>
                                        <li>Adaptabilidad</li>
                                        <li>Habilidades sociales</li>
                                        <li>Liderazgo</li>
                                        <li>Habilidades para el estudio</li>
                                        <li>Relaciones interpersonales</li>
                                        <li>Relaciones con los padres</li>
                                        <li>Autoestima</li>
                                        <li>Confianza en sí mismo</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <h5>Interpretación</h5>
                            <ul>
                                <li>Las puntuaciones se transforman en puntuaciones T (media=50, DT=10)</li>
                                <li><strong>Escalas Clínicas:</strong>
                                    <ul>
                                        <li>T > 70: Clínicamente significativo</li>
                                        <li>T = 60-69: En riesgo</li>
                                        <li>T < 60: Normal</li>
                                    </ul>
                                </li>
                                <li><strong>Escalas Adaptativas:</strong> (interpretación inversa)
                                    <ul>
                                        <li>T < 30: Clínicamente significativo</li>
                                        <li>T = 31-40: En riesgo</li>
                                        <li>T > 40: Normal</li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <div class="alert alert-warning">
                                <span class="material-symbols-rounded me-1">warning</span>
                                <strong>Nota importante:</strong> Esta es una versión simplificada del BASC. La versión completa contiene más de 100 ítems por cuestionario, organizada en diferentes módulos por edades. Para una aplicación clínica completa, se recomienda utilizar los materiales originales.
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