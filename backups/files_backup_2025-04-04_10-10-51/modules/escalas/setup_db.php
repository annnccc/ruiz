<?php
/**
 * Configuración de la base de datos para el módulo de Escalas Psicológicas
 * Este script crea las tablas necesarias para el funcionamiento del sistema
 */

// Incluir configuración global y conexión a la base de datos
require_once '../../includes/config.php';

// Obtener conexión a la base de datos
$db = getDB();

// Función para mostrar mensajes de estado
function showStatus($message, $success = true) {
    echo '<div style="color: ' . ($success ? 'green' : 'red') . '; margin: 5px 0;">';
    echo $message;
    echo '</div>';
}

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Acceso denegado. Debe ser administrador para ejecutar este script.");
}

// Crear tabla de catálogo de escalas
$sql_escalas_catalogo = "
CREATE TABLE IF NOT EXISTS `escalas_catalogo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text,
  `poblacion` enum('adultos', 'adolescentes', 'niños', 'todos') NOT NULL DEFAULT 'todos',
  `instrucciones` text,
  `tiempo_estimado` varchar(50),
  `referencia_bibliografica` text,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Crear tabla de ítems de escalas
$sql_escalas_items = "
CREATE TABLE IF NOT EXISTS `escalas_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `escala_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `texto` text NOT NULL,
  `tipo_respuesta` enum('likert3', 'likert4', 'likert5', 'si_no', 'numerica', 'seleccion_multiple') NOT NULL,
  `opciones_respuesta` text,
  `inversion` tinyint(1) NOT NULL DEFAULT '0',
  `subescala` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escala_id` (`escala_id`),
  CONSTRAINT `fk_items_escala` FOREIGN KEY (`escala_id`) REFERENCES `escalas_catalogo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Crear tabla de administraciones de escalas
$sql_escalas_administraciones = "
CREATE TABLE IF NOT EXISTS `escalas_administraciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `escala_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `completada` tinyint(1) NOT NULL DEFAULT '0',
  `motivo` text,
  `observaciones` text,
  `usuario_id` int(11),
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_modificacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `escala_id` (`escala_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_admin_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_admin_escala` FOREIGN KEY (`escala_id`) REFERENCES `escalas_catalogo` (`id`),
  CONSTRAINT `fk_admin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Crear tabla de respuestas
$sql_escalas_respuestas = "
CREATE TABLE IF NOT EXISTS `escalas_respuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `administracion_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `respuesta` varchar(255) NOT NULL,
  `puntuacion` float,
  PRIMARY KEY (`id`),
  KEY `administracion_id` (`administracion_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `fk_resp_admin` FOREIGN KEY (`administracion_id`) REFERENCES `escalas_administraciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resp_item` FOREIGN KEY (`item_id`) REFERENCES `escalas_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Crear tabla de resultados
$sql_escalas_resultados = "
CREATE TABLE IF NOT EXISTS `escalas_resultados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `administracion_id` int(11) NOT NULL,
  `subescala` varchar(100) DEFAULT 'total',
  `puntuacion_directa` float NOT NULL,
  `puntuacion_tipica` float,
  `percentil` int(11),
  `interpretacion` text,
  `alerta` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `administracion_id` (`administracion_id`),
  CONSTRAINT `fk_result_admin` FOREIGN KEY (`administracion_id`) REFERENCES `escalas_administraciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Ejecutar las consultas SQL para crear las tablas
try {
    $db->exec($sql_escalas_catalogo);
    showStatus("Tabla 'escalas_catalogo' creada o ya existente.");
    
    $db->exec($sql_escalas_items);
    showStatus("Tabla 'escalas_items' creada o ya existente.");
    
    $db->exec($sql_escalas_administraciones);
    showStatus("Tabla 'escalas_administraciones' creada o ya existente.");
    
    $db->exec($sql_escalas_respuestas);
    showStatus("Tabla 'escalas_respuestas' creada o ya existente.");
    
    $db->exec($sql_escalas_resultados);
    showStatus("Tabla 'escalas_resultados' creada o ya existente.");
    
    showStatus("¡Todas las tablas han sido creadas exitosamente!", true);
} catch (PDOException $e) {
    showStatus("Error al crear las tablas: " . $e->getMessage(), false);
    die();
}

// Añadir algunas escalas de ejemplo
$escalas_iniciales = [
    [
        'nombre' => 'Inventario de Depresión de Beck (BDI-II)',
        'descripcion' => 'Evaluación de síntomas depresivos. 21 ítems con escala de 0-3.',
        'poblacion' => 'adultos',
        'instrucciones' => 'A continuación se expresan varias respuestas posibles a cada uno de los 21 apartados. Marque la opción que mejor refleje su situación actual.',
        'tiempo_estimado' => '5-10 minutos',
        'referencia_bibliografica' => 'Beck, A. T., Steer, R. A., & Brown, G. K. (1996). Manual for the Beck Depression Inventory-II. San Antonio, TX: Psychological Corporation.'
    ],
    [
        'nombre' => 'Inventario de Ansiedad Estado-Rasgo (STAI)',
        'descripcion' => 'Evaluación diferenciada de ansiedad como estado y como rasgo. Dos subescalas de 20 ítems cada una.',
        'poblacion' => 'adultos',
        'instrucciones' => 'Lea cada frase y señale la puntuación de 0 a 3 que indique mejor cómo se siente ahora mismo.',
        'tiempo_estimado' => '10-15 minutos',
        'referencia_bibliografica' => 'Spielberger, C. D., Gorsuch, R. L., Lushene, R., Vagg, P. R., & Jacobs, G. A. (1983). Manual for the State-Trait Anxiety Inventory. Palo Alto, CA: Consulting Psychologists Press.'
    ],
    [
        'nombre' => 'Escala de Autoestima de Rosenberg',
        'descripcion' => 'Evaluación de la autoestima global. 10 ítems con puntuación de 1-4.',
        'poblacion' => 'todos',
        'instrucciones' => 'Por favor, lea las siguientes afirmaciones y marque la opción que mejor se ajuste a su grado de acuerdo o desacuerdo.',
        'tiempo_estimado' => '5 minutos',
        'referencia_bibliografica' => 'Rosenberg, M. (1965). Society and the adolescent self-image. Princeton, NJ: Princeton University Press.'
    ],
    [
        'nombre' => 'Cuestionario de Salud General de Goldberg (GHQ-28)',
        'descripcion' => 'Cribado de malestar psicológico general. 28 ítems divididos en 4 subescalas.',
        'poblacion' => 'adultos',
        'instrucciones' => 'Nos gustaría saber si ha tenido algunas molestias y cómo ha estado su salud en las últimas semanas.',
        'tiempo_estimado' => '10-15 minutos',
        'referencia_bibliografica' => 'Goldberg, D. P., & Hillier, V. F. (1979). A scaled version of the General Health Questionnaire. Psychological Medicine, 9(1), 139-145.'
    ]
];

// Verificar si ya existen escalas en la tabla
$stmt = $db->query("SELECT COUNT(*) FROM escalas_catalogo");
$count = $stmt->fetchColumn();

if ($count == 0) {
    // Insertar escalas iniciales
    $stmt = $db->prepare("INSERT INTO escalas_catalogo (nombre, descripcion, poblacion, instrucciones, tiempo_estimado, referencia_bibliografica) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($escalas_iniciales as $escala) {
        $stmt->execute([
            $escala['nombre'],
            $escala['descripcion'],
            $escala['poblacion'],
            $escala['instrucciones'],
            $escala['tiempo_estimado'],
            $escala['referencia_bibliografica']
        ]);
    }
    
    showStatus("Se han añadido " . count($escalas_iniciales) . " escalas iniciales al catálogo.", true);
} else {
    showStatus("Ya existen escalas en el catálogo. No se han añadido escalas iniciales.", true);
}

echo "<p>Puede <a href='../index.php'>volver al panel principal</a> o <a href='index.php'>ir al módulo de escalas</a>.</p>";
?>