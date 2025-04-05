<?php
/**
 * Módulo de Escalas Psicológicas - Carga de Ítems Test de Atención D2
 * Este script carga automáticamente el Test de Atención D2
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
$pageTitle = "Carga de Ítems - Test de Atención D2";

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
$nombreEscala = "Test de Atención D2";
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
            "El Test de Atención D2 es una prueba que evalúa la atención selectiva y la concentración mental. Mide la capacidad para atender selectivamente a ciertos aspectos relevantes de una tarea mientras se ignoran los irrelevantes, de forma rápida y precisa.",
            "todos",
            "En esta prueba, aparecen filas con las letras 'd' y 'p', que pueden estar acompañadas de una o dos pequeñas rayitas situadas encima o debajo de cada letra. Su tarea consiste en revisar atentamente, de izquierda a derecha, cada una de las filas y marcar toda letra 'd' que tenga DOS RAYITAS (las dos arriba, las dos abajo o una arriba y otra abajo). Tendrá un tiempo limitado para cada fila.",
            "8-10 minutos",
            "Brickenkamp, R. (2002). D2, Test de atención. Manual. Madrid: TEA Ediciones."
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
    
    // En el D2, en lugar de ítems convencionales, vamos a insertar algunos ítems de ejemplo
    // y luego los parámetros a medir en la prueba
    $items = [
        // Filas de ejemplo (en una implementación real, habría 14 filas con 47 caracteres cada una)
        ["Fila 1 - Ejemplo", "seleccion_multiple", 0, "ejemplo", 1],
        ["Fila 2 - Ejemplo", "seleccion_multiple", 0, "ejemplo", 2],
        
        // Elementos a valorar/medir en el test
        ["TR - Total de Respuestas", "numerica", 0, "puntuacion", 3],
        ["TA - Total de Aciertos", "numerica", 0, "puntuacion", 4],
        ["O - Omisiones", "numerica", 0, "puntuacion", 5],
        ["C - Comisiones", "numerica", 0, "puntuacion", 6],
        ["TOT - Efectividad Total (TR-(O+C))", "numerica", 0, "puntuacion", 7],
        ["CON - Índice de Concentración (TA-C)", "numerica", 0, "puntuacion", 8],
        ["VAR - Variación o diferencia (TR+)-(TR-)", "numerica", 0, "puntuacion", 9]
    ];
    
    // Preparar opciones de respuesta específicas para el D2
    $opcionesRespuestaFila = json_encode([
        ["valor" => "d'", "texto" => "d con una rayita arriba"],
        ["valor" => "d''", "texto" => "d con dos rayitas arriba"],
        ["valor" => "d,", "texto" => "d con una rayita abajo"],
        ["valor" => "d,,", "texto" => "d con dos rayitas abajo"],
        ["valor" => "d',", "texto" => "d con una rayita arriba y una abajo"],
        ["valor" => "p'", "texto" => "p con una rayita arriba"],
        ["valor" => "p''", "texto" => "p con dos rayitas arriba"],
        ["valor" => "p,", "texto" => "p con una rayita abajo"],
        ["valor" => "p,,", "texto" => "p con dos rayitas abajo"],
        ["valor" => "p',", "texto" => "p con una rayita arriba y una abajo"]
    ]);
    
    $opcionesRespuestaNumerica = json_encode([
        ["valor" => "", "texto" => "Valor numérico"]
    ]);
    
    // Insertar los ítems
    $stmt = $db->prepare("
        INSERT INTO escalas_items 
        (escala_id, numero, texto, tipo_respuesta, opciones_respuesta, inversion, subescala) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        $opciones = $item[1] === "seleccion_multiple" ? $opcionesRespuestaFila : $opcionesRespuestaNumerica;
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
        Carga de Ítems - Test de Atención D2
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Carga de Ítems D2</li>
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
                            <h5 class="m-0">Información sobre el Test D2</h5>
                        </div>
                        <div class="card-body">
                            <h5>Características de la Prueba</h5>
                            <ul>
                                <li><strong>Nombre completo:</strong> Test de Atención D2</li>
                                <li><strong>Autor:</strong> Rolf Brickenkamp</li>
                                <li><strong>Objetivo:</strong> Evaluar la atención selectiva y la concentración mental</li>
                                <li><strong>Aplicación:</strong> Individual o colectiva</li>
                                <li><strong>Edades:</strong> Niños (a partir de 8 años), adolescentes y adultos</li>
                                <li><strong>Estructura:</strong> 14 filas con 47 caracteres cada una (total 658 elementos)</li>
                                <li><strong>Tiempo:</strong> 20 segundos por línea (4 minutos y 40 segundos en total)</li>
                            </ul>
                            
                            <h5>Medidas que proporciona</h5>
                            <ul>
                                <li><strong>TR (Total de Respuestas):</strong> Número total de elementos procesados</li>
                                <li><strong>TA (Total de Aciertos):</strong> Número de elementos relevantes marcados correctamente</li>
                                <li><strong>O (Omisiones):</strong> Número de elementos relevantes no marcados</li>
                                <li><strong>C (Comisiones):</strong> Número de elementos irrelevantes marcados</li>
                                <li><strong>TOT (Efectividad Total):</strong> TR-(O+C), indica el control atencional e inhibitorio</li>
                                <li><strong>CON (Índice de Concentración):</strong> TA-C, indica la precisión y calidad de procesamiento</li>
                                <li><strong>VAR (Variación):</strong> (TR+)-(TR-), indica la estabilidad y consistencia del rendimiento</li>
                            </ul>
                            
                            <div class="alert alert-info mt-3">
                                <span class="material-symbols-rounded me-1">info</span>
                                <strong>Nota sobre implementación:</strong> El Test D2 requiere una interfaz específica para su administración, con temporizador y visualización adecuada de los estímulos. Esta implementación básica solo incluye la estructura para registrar las puntuaciones.
                            </div>
                            
                            <div class="alert alert-warning">
                                <span class="material-symbols-rounded me-1">warning</span>
                                <strong>Aviso importante:</strong> Para una aplicación clínica del D2, se recomienda utilizar el material oficial y seguir estrictamente las instrucciones de administración y corrección.
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