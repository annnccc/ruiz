<?php
/**
 * Helper para manejo de fechas y horas
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Formatea una fecha de MySQL (YYYY-MM-DD) a formato local (DD/MM/YYYY)
 * 
 * @param string $fecha Fecha en formato MySQL
 * @return string Fecha formateada
 */
function formatDateToView($fecha) {
    if (empty($fecha)) return '';
    return date('d/m/Y', strtotime($fecha));
}

/**
 * Formatea una fecha de formato local (DD/MM/YYYY) a MySQL (YYYY-MM-DD)
 * 
 * @param string $fecha Fecha en formato local
 * @return string Fecha formateada para MySQL
 */
function formatDateToDB($fecha) {
    if (empty($fecha)) return '';
    
    // Si la fecha ya está en formato YYYY-MM-DD, devolverla tal cual
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }
    
    // Sanitizar la entrada
    $fecha = trim($fecha);
    
    // Intentar convertir desde DD/MM/YYYY
    $partes = explode('/', $fecha);
    if (count($partes) === 3) {
        // Verificar que las partes sean numéricas
        if (is_numeric($partes[0]) && is_numeric($partes[1]) && is_numeric($partes[2])) {
            // Asegurar que el año tenga 4 dígitos
            if (strlen($partes[2]) === 2) {
                $partes[2] = '20' . $partes[2]; // Asumir que es 20XX
            }
            
            // Validar día, mes y año
            $dia = (int)$partes[0];
            $mes = (int)$partes[1];
            $anio = (int)$partes[2];
            
            if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12 && $anio >= 1900 && $anio <= 2100) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }
    }
    
    // Si llegamos aquí, intentar con strtotime como fallback
    $timestamp = strtotime($fecha);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    // Si todo falla, devolver fecha actual
    return date('Y-m-d');
}

/**
 * Formatea una hora de MySQL (HH:MM:SS) a formato local (HH:MM)
 * 
 * @param string $hora Hora en formato MySQL
 * @return string Hora formateada
 */
function formatTime($hora) {
    if (empty($hora)) return '';
    return substr($hora, 0, 5);
}

/**
 * Formatea una fecha y hora de MySQL (YYYY-MM-DD HH:MM:SS) a formato local (DD/MM/YYYY HH:MM)
 * 
 * @param string $fechaHora Fecha y hora en formato MySQL
 * @return string Fecha y hora formateada
 */
function formatDateTimeToView($fechaHora) {
    if (empty($fechaHora)) return '';
    return date('d/m/Y H:i', strtotime($fechaHora));
}

/**
 * Calcula la edad a partir de una fecha de nacimiento
 * 
 * @param string $fechaNacimiento Fecha de nacimiento en formato MySQL
 * @return int Edad en años
 */
function calcularEdad($fechaNacimiento) {
    $fecha_nac = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac);
    return $edad->y;
}

/**
 * Obtiene el nombre del día de la semana para una fecha dada
 * 
 * @param string $fecha Fecha en formato MySQL
 * @return string Nombre del día de la semana
 */
function getDayName($fecha) {
    $dias = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
    $dia = date('w', strtotime($fecha));
    return $dias[$dia];
}

/**
 * Obtiene el nombre del mes a partir de su número
 * 
 * @param int $mes Número del mes (1-12)
 * @return string Nombre del mes
 */
function getMonthName($mes) {
    $meses = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    return $meses[(int)$mes];
}

/**
 * Verifica si un horario está disponible (no hay conflictos con otras citas)
 * 
 * @param string $fecha Fecha de la cita en formato MySQL
 * @param string $hora_inicio Hora de inicio
 * @param string $hora_fin Hora de fin
 * @param int|null $cita_id ID de la cita actual (para excluirla al editar)
 * @return bool True si el horario está disponible, false en caso contrario
 */
function isTimeSlotAvailable($fecha, $hora_inicio, $hora_fin, $cita_id = null) {
    $db = getDB();
    
    $query = "SELECT * FROM citas WHERE fecha = :fecha 
              AND ((hora_inicio <= :hora_inicio AND hora_fin > :hora_inicio) 
              OR (hora_inicio < :hora_fin AND hora_fin >= :hora_fin)
              OR (hora_inicio >= :hora_inicio AND hora_fin <= :hora_fin))
              AND estado != 'cancelada'";
    
    $params = [
        ':fecha' => $fecha,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin' => $hora_fin
    ];
    
    // Si estamos editando una cita, excluimos esa cita de la verificación
    if ($cita_id) {
        $query .= " AND id != :cita_id";
        $params[':cita_id'] = $cita_id;
    }
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    
    $stmt->execute();
    
    return $stmt->rowCount() === 0;
}

/**
 * Obtiene horarios disponibles para una fecha específica
 * 
 * @param string $fecha Fecha en formato MySQL
 * @return array Array de horarios disponibles
 */
function getHorariosDisponibles($fecha) {
    $db = getDB();
    
    // Definir horario de trabajo (8:00 - 20:00)
    $horario_inicio = '08:00:00';
    $horario_fin = '20:00:00';
    
    // Obtener todas las citas para la fecha seleccionada
    $stmt = $db->prepare("SELECT hora_inicio, hora_fin FROM citas WHERE fecha = :fecha AND estado != 'cancelada'");
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    $citas_ocupadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    return $slots_disponibles;
}
?> 