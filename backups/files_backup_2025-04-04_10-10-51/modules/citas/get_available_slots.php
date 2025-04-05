<?php
require_once '../../includes/config.php';

// Establecer cabeceras para JSON
header('Content-Type: application/json');

// Comprobar si se ha enviado una fecha
if (!isset($_GET['fecha']) || empty($_GET['fecha'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se ha proporcionado una fecha'
    ]);
    exit;
}

$fecha = sanitize($_GET['fecha']);

// Definir horario de trabajo (8:00 - 20:00)
$horario_inicio = '08:00:00';
$horario_fin = '20:00:00';

// Obtener todas las citas para la fecha seleccionada
$db->query("SELECT hora_inicio, hora_fin FROM citas WHERE fecha = :fecha AND estado != 'cancelada'");
$db->bind(':fecha', $fecha);
$citas_ocupadas = $db->resultSet();

// Convertir horas de inicio y fin a timestamps para facilitar el cálculo
$inicio_dia = strtotime($fecha . ' ' . $horario_inicio);
$fin_dia = strtotime($fecha . ' ' . $horario_fin);

// Crear array de slots ocupados
$slots_ocupados = [];
foreach ($citas_ocupadas as $cita) {
    $inicio_cita = strtotime($fecha . ' ' . $cita['hora_inicio']);
    $fin_cita = strtotime($fecha . ' ' . $cita['hora_fin']);
    
    // Añadir al array de slots ocupados
    $slots_ocupados[] = [
        'inicio' => $inicio_cita,
        'fin' => $fin_cita
    ];
}

// Generar slots disponibles (cada 15 minutos)
$slots_disponibles = [];
$intervalo = 15 * 60; // 15 minutos en segundos
$duraciones_predefinidas = [15, 30, 45, 60]; // Duraciones en minutos

// Para cada hora de inicio posible
for ($i = $inicio_dia; $i < $fin_dia; $i += $intervalo) {
    $hora_inicio = date('H:i', $i);
    
    // Para cada duración predefinida
    foreach ($duraciones_predefinidas as $duracion) {
        $fin_slot = $i + ($duracion * 60);
        
        // Si el fin del slot es después del fin del día, ignorar
        if ($fin_slot > $fin_dia) {
            continue;
        }
        
        $hora_fin = date('H:i', $fin_slot);
        
        // Comprobar si el slot está disponible
        $disponible = true;
        foreach ($slots_ocupados as $ocupado) {
            // Si hay solapamiento, marcar como no disponible
            if (($i < $ocupado['fin'] && $fin_slot > $ocupado['inicio'])) {
                $disponible = false;
                break;
            }
        }
        
        // Si está disponible, añadir al array de slots disponibles
        if ($disponible) {
            $slots_disponibles[] = [
                'inicio' => $hora_inicio,
                'fin' => $hora_fin,
                'duracion' => $duracion
            ];
        }
    }
}

// Ordenar slots por hora de inicio y duración
usort($slots_disponibles, function($a, $b) {
    $comp = strcmp($a['inicio'], $b['inicio']);
    if ($comp == 0) {
        return $a['duracion'] - $b['duracion'];
    }
    return $comp;
});

// Limitar a un máximo de 20 slots para no sobrecargar la interfaz
$slots_disponibles = array_slice($slots_disponibles, 0, 20);

// Devolver JSON con los slots disponibles
echo json_encode([
    'success' => true,
    'fecha' => $fecha,
    'slots' => $slots_disponibles
]);
exit; 