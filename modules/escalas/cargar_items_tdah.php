<?php
/**
 * Script para cargar los ítems de la escala ADHD Rating Scale-IV
 * Escala para la evaluación del Trastorno por Déficit de Atención e Hiperactividad
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
$pageTitle = "Cargar ítems de la escala ADHD Rating Scale-IV";

// Iniciar captura del contenido
startPageContent();

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si la escala ya existe
$stmt = $db->prepare("SELECT id FROM escalas_catalogo WHERE nombre = ?");
$nombre_escala = "ADHD Rating Scale-IV";
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
        
        $descripcion = "Escala para evaluar la presencia y severidad de síntomas del Trastorno por Déficit de Atención e Hiperactividad (TDAH) según los criterios del DSM-IV. Evalúa las dimensiones de inatención e hiperactividad-impulsividad.";
        $poblacion = "niños"; // Principalmente para niños, aunque hay versiones adaptadas
        $instrucciones = "Por favor, indique la frecuencia con la que el niño/adolescente presenta cada uno de los siguientes comportamientos. Marque la opción que mejor describa el comportamiento durante los últimos 6 meses.";
        $tiempo_estimado = "5-10 minutos";
        $referencia = "DuPaul, G. J., Power, T. J., Anastopoulos, A. D., & Reid, R. (1998). ADHD Rating Scale-IV: Checklists, norms, and clinical interpretation. New York: Guilford Press.";
        
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
            // Subescala Inatención (ítems impares)
            [
                'numero' => 1,
                'texto' => 'No presta atención a los detalles o comete errores por descuido en sus tareas',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 3,
                'texto' => 'Tiene dificultades para mantener la atención en tareas o actividades de juego',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 5,
                'texto' => 'Parece no escuchar cuando se le habla directamente',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 7,
                'texto' => 'No sigue instrucciones y no finaliza tareas escolares o responsabilidades',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 9,
                'texto' => 'Tiene dificultades para organizar tareas y actividades',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 11,
                'texto' => 'Evita, le disgusta o se muestra poco entusiasta ante tareas que requieren esfuerzo mental sostenido',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 13,
                'texto' => 'Pierde objetos necesarios para tareas o actividades',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 15,
                'texto' => 'Se distrae fácilmente con estímulos externos',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            [
                'numero' => 17,
                'texto' => 'Es olvidadizo en las actividades diarias',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Inatención'
            ],
            
            // Subescala Hiperactividad-Impulsividad (ítems pares)
            [
                'numero' => 2,
                'texto' => 'Mueve en exceso manos o pies, o se remueve en su asiento',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 4,
                'texto' => 'Abandona su asiento en situaciones en las que se espera que permanezca sentado',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 6,
                'texto' => 'Corre o trepa en situaciones en las que no es apropiado',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 8,
                'texto' => 'Tiene dificultades para jugar o dedicarse a actividades de ocio tranquilamente',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 10,
                'texto' => 'Está "en marcha" o actúa como si tuviera un motor',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 12,
                'texto' => 'Habla en exceso',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 14,
                'texto' => 'Responde inesperadamente o antes de que se haya concluido una pregunta',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 16,
                'texto' => 'Le cuesta esperar su turno',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
            ],
            [
                'numero' => 18,
                'texto' => 'Interrumpe o se inmiscuye con otros',
                'tipo_respuesta' => 'likert4',
                'opciones_respuesta' => json_encode([
                    '0' => 'Nunca o raramente',
                    '1' => 'Algunas veces',
                    '2' => 'Con frecuencia',
                    '3' => 'Con mucha frecuencia'
                ]),
                'inversion' => 0,
                'subescala' => 'Hiperactividad-Impulsividad'
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