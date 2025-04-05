<?php
/**
 * Script para cargar los ítems de la Escala de Ansiedad de Hamilton (HARS)
 * Escala específica para la evaluación de la ansiedad
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo "<p>Error: Acceso denegado. Debe ser administrador para ejecutar este script.</p>";
    exit;
}

// Título de la página
$pageTitle = "Cargar ítems de la Escala de Ansiedad de Hamilton (HARS)";

// Iniciar captura del contenido
startPageContent();

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si la escala ya existe
$stmt = $db->prepare("SELECT id FROM escalas_catalogo WHERE nombre = ?");
$nombre_escala = "Escala de Ansiedad de Hamilton (HARS)";
$stmt->execute([$nombre_escala]);
$escala = $stmt->fetch(PDO::FETCH_ASSOC);

if ($escala) {
    $escala_id = $escala['id'];
    echo "<div class='alert alert-info'>La escala '$nombre_escala' ya existe en el catálogo con ID: $escala_id</div>";
} else {
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Insertar la escala en el catálogo
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
        
        $descripcion = "La Escala de Ansiedad de Hamilton (HARS) es un instrumento diseñado para evaluar la severidad de los síntomas de ansiedad. Consta de 14 ítems que valoran tanto los aspectos psíquicos (tensión, miedos) como somáticos (síntomas cardiovasculares, respiratorios, gastrointestinales) de la ansiedad.";
        $poblacion = "adultos";
        $instrucciones = "Para cada ítem, evalúe la intensidad de los síntomas del paciente en una escala de 0 a 4, donde 0 es ausente, 1 es leve, 2 es moderado, 3 es severo y 4 es muy severo o incapacitante.";
        $tiempo_estimado = "10-15 minutos";
        $referencia = "Hamilton, M. (1959). The assessment of anxiety states by rating. British Journal of Medical Psychology, 32, 50-55.";
        
        $stmt->execute([
            $nombre_escala,
            $descripcion,
            $poblacion,
            $instrucciones,
            $tiempo_estimado,
            $referencia
        ]);
        
        $escala_id = $db->lastInsertId();
        
        // Definir los ítems de la escala
        $items = [
            [
                'numero' => 1,
                'texto' => 'Estado ansioso: Preocupaciones, anticipación de lo peor, aprensión (anticipación temerosa), irritabilidad.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad psíquica'
            ],
            [
                'numero' => 2,
                'texto' => 'Tensión: Sensaciones de tensión, fatigabilidad, sobresalto, llanto fácil, temblores, sentimientos de inquietud, incapacidad para relajarse.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad psíquica'
            ],
            [
                'numero' => 3,
                'texto' => 'Miedos: A la oscuridad, a los desconocidos, a quedarse solo, a los animales, al tráfico, a las multitudes.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad psíquica'
            ],
            [
                'numero' => 4,
                'texto' => 'Insomnio: Dificultad para conciliar el sueño, sueño interrumpido, sueño no satisfactorio con cansancio al despertar, pesadillas, terrores nocturnos.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad psíquica'
            ],
            [
                'numero' => 5,
                'texto' => 'Funciones intelectuales (cognitivas): Dificultad de concentración, mala memoria.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad psíquica'
            ],
            [
                'numero' => 6,
                'texto' => 'Estado de ánimo depresivo: Pérdida de interés, falta de placer en los pasatiempos, depresión, despertar anticipado, variaciones del humor.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad psíquica'
            ],
            [
                'numero' => 7,
                'texto' => 'Síntomas somáticos musculares: Dolores musculares, rigidez muscular, sacudidas musculares, sacudidas clónicas, rechinar de dientes, voz quebrada.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 8,
                'texto' => 'Síntomas somáticos sensoriales: Zumbidos de oídos, visión borrosa, sofocos o escalofríos, sensación de debilidad, sensación de hormigueo.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 9,
                'texto' => 'Síntomas cardiovasculares: Taquicardia, palpitaciones, dolor torácico, sensación de desmayo, sensación de extrasístoles.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 10,
                'texto' => 'Síntomas respiratorios: Opresión o constricción torácica, sensación de ahogo, suspiros, disnea.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 11,
                'texto' => 'Síntomas gastrointestinales: Dificultad para tragar, gases, dispepsia, dolor antes o después de comer, sensación de ardor, distensión abdominal, náuseas, vómitos, constipación, pérdida de peso.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 12,
                'texto' => 'Síntomas genitourinarios: Micción frecuente, urgencia de micción, amenorrea, menorragia, frigidez, eyaculación precoz, pérdida de la libido, impotencia sexual.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 13,
                'texto' => 'Síntomas del sistema nervioso autónomo: Boca seca, enrojecimiento, palidez, tendencia a sudar, vértigos, cefalea tensional.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Ansiedad somática'
            ],
            [
                'numero' => 14,
                'texto' => 'Comportamiento durante la entrevista: Inquietud, impaciencia o intranquilidad, temblor de manos, fruncimiento del entrecejo, tensión facial, suspiros o respiración rápida, palidez, eructos, dilatación pupilar, exoftalmos, sudor, tics.',
                'tipo_respuesta' => 'likert5',
                'opciones_respuesta' => json_encode([
                    '0' => 'Ausente',
                    '1' => 'Leve',
                    '2' => 'Moderado',
                    '3' => 'Severo',
                    '4' => 'Muy severo'
                ]),
                'inversion' => 0,
                'subescala' => 'Observación'
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
        
        echo "<div class='alert alert-success'>
            Se ha añadido la escala '$nombre_escala' al catálogo con ID: $escala_id<br>
            Se han añadido " . count($items) . " ítems a la escala.
        </div>";
        
    } catch (PDOException $e) {
        // Revertir en caso de error
        $db->rollBack();
        echo "<div class='alert alert-danger'>Error al cargar la escala: " . $e->getMessage() . "</div>";
    }
}

// Enlaces de navegación
echo "<div class='mt-4'>
    <a href='index.php' class='btn btn-primary'>
        <span class='material-symbols-rounded me-1'>arrow_back</span> Volver al módulo de Escalas
    </a>
</div>";

// Finalizar captura del contenido
endPageContent();

// Incluir la plantilla
include '../../includes/template.php';
?> 