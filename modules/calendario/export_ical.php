<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Comprobar parámetros
$cita_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$export_all = isset($_GET['all']) && $_GET['all'] == 1;
$format = isset($_GET['format']) ? $_GET['format'] : 'ical';

try {
    $db = getDB();
    
    if ($cita_id > 0) {
        // Exportar una cita específica
        $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado, 
                 p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, c.notas,
                 u.nombre as medico_nombre, u.apellidos as medico_apellidos,
                 c.sala, c.recordatorio_enviado
                 FROM citas c
                 JOIN pacientes p ON c.paciente_id = p.id
                 JOIN usuarios u ON c.usuario_id = u.id
                 WHERE c.id = :cita_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cita_id', $cita_id, PDO::PARAM_INT);
        $stmt->execute();
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Exportar todas las citas o filtradas por fecha
        $whereClause = '';
        $params = [];
        
        // Filtrar por fecha inicio/fin si se especifican
        if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
            $whereClause .= "c.fecha >= :fecha_inicio";
            $params[':fecha_inicio'] = $_GET['fecha_inicio'];
        }
        
        if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
            $whereClause .= $whereClause ? " AND " : "";
            $whereClause .= "c.fecha <= :fecha_fin";
            $params[':fecha_fin'] = $_GET['fecha_fin'];
        }
        
        // Filtrar por médico si se especifica
        if (isset($_GET['medico_id']) && intval($_GET['medico_id']) > 0) {
            $whereClause .= $whereClause ? " AND " : "";
            $whereClause .= "c.usuario_id = :medico_id";
            $params[':medico_id'] = intval($_GET['medico_id']);
        }
        
        // Filtrar por estado si se especifica
        if (isset($_GET['estado']) && in_array($_GET['estado'], ['pendiente', 'completada', 'cancelada'])) {
            $whereClause .= $whereClause ? " AND " : "";
            $whereClause .= "c.estado = :estado";
            $params[':estado'] = $_GET['estado'];
        }
        
        // Filtrar por sala si se especifica
        if (isset($_GET['sala_id']) && intval($_GET['sala_id']) > 0) {
            $whereClause .= $whereClause ? " AND " : "";
            $whereClause .= "c.sala = :sala_id";
            $params[':sala_id'] = intval($_GET['sala_id']);
        }
        
        // Construir consulta final
        $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado, 
                 p.nombre as paciente_nombre, p.apellidos as paciente_apellidos, c.notas,
                 u.nombre as medico_nombre, u.apellidos as medico_apellidos,
                 c.sala, c.recordatorio_enviado
                 FROM citas c
                 JOIN pacientes p ON c.paciente_id = p.id
                 JOIN usuarios u ON c.usuario_id = u.id";
        
        if ($whereClause) {
            $query .= " WHERE " . $whereClause;
        }
        
        $query .= " ORDER BY c.fecha ASC, c.hora_inicio ASC";
        
        $stmt = $db->prepare($query);
        
        // Bindear parámetros
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Generar archivo según el formato solicitado
    if ($format === 'google') {
        // Redirigir a Google Calendar con los parámetros adecuados
        $cita = $citas[0]; // Para una sola cita
        
        $start = str_replace('-', '', $cita['fecha']) . 'T' . str_replace(':', '', $cita['hora_inicio']) . '00';
        $end = str_replace('-', '', $cita['fecha']) . 'T' . str_replace(':', '', $cita['hora_fin']) . '00';
        
        $text = 'Cita: ' . $cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre'];
        $details = "Paciente: " . $cita['paciente_apellidos'] . ', ' . $cita['paciente_nombre'] . "\n";
        $details .= "Médico: " . $cita['medico_apellidos'] . ', ' . $cita['medico_nombre'] . "\n";
        $details .= "Motivo: " . $cita['motivo'] . "\n";
        if (!empty($cita['notas'])) {
            $details .= "Notas: " . $cita['notas'];
        }
        
        $googleUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE";
        $googleUrl .= "&text=" . urlencode($text);
        $googleUrl .= "&dates=" . $start . "/" . $end;
        $googleUrl .= "&details=" . urlencode($details);
        $googleUrl .= "&sf=true&output=xml";
        
        header('Location: ' . $googleUrl);
        exit;
    } else {
        // Generar iCalendar (.ics)
        $nombreArchivo = $cita_id > 0 ? "cita_" . $cita_id . ".ics" : "calendario_citas.ics";
        
        // Encabezados para la descarga
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        
        // Generar contenido iCalendar
        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Clínica//Sistema de Gestión//ES\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        
        foreach ($citas as $cita) {
            if ($cita['estado'] === 'cancelada' && !$cita_id) {
                continue; // No exportar citas canceladas en exportación masiva
            }
            
            $fechaInicio = $cita['fecha'] . 'T' . $cita['hora_inicio'] . ':00';
            $fechaFin = $cita['fecha'] . 'T' . $cita['hora_fin'] . ':00';
            
            // Convertir a formato UTC para iCalendar
            $dtstart = new DateTime($fechaInicio, new DateTimeZone(date_default_timezone_get()));
            $dtend = new DateTime($fechaFin, new DateTimeZone(date_default_timezone_get()));
            $dtstart->setTimezone(new DateTimeZone('UTC'));
            $dtend->setTimezone(new DateTimeZone('UTC'));
            
            // Crear identificador único
            $uid = $cita['id'] . '@clinica.' . strtolower(str_replace(' ', '', $_SERVER['SERVER_NAME']));
            
            // Título del evento
            $summary = "Cita: " . $cita['paciente_apellidos'] . ", " . $cita['paciente_nombre'];
            
            // Descripción
            $description = "Paciente: " . $cita['paciente_apellidos'] . ", " . $cita['paciente_nombre'] . "\\n";
            $description .= "Médico: " . $cita['medico_apellidos'] . ", " . $cita['medico_nombre'] . "\\n";
            $description .= "Motivo: " . $cita['motivo'] . "\\n";
            if (!empty($cita['notas'])) {
                $description .= "Notas: " . $cita['notas'] . "\\n";
            }
            
            // Estado
            $status = $cita['estado'] === 'pendiente' ? 'CONFIRMED' : 
                     ($cita['estado'] === 'completada' ? 'COMPLETED' : 'CANCELLED');
            
            // Generar evento
            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . $uid . "\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:" . $dtstart->format('Ymd\THis\Z') . "\r\n";
            echo "DTEND:" . $dtend->format('Ymd\THis\Z') . "\r\n";
            echo "SUMMARY:" . escapeIcalText($summary) . "\r\n";
            echo "DESCRIPTION:" . escapeIcalText($description) . "\r\n";
            echo "STATUS:" . $status . "\r\n";
            echo "END:VEVENT\r\n";
        }
        
        echo "END:VCALENDAR\r\n";
    }
    
} catch (PDOException $e) {
    // En caso de error, redireccionar con mensaje de error
    setAlert('danger', 'Error al exportar las citas: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/modules/calendario/index.php');
    exit;
}

// Función para escapar texto en formato iCal
function escapeIcalText($text) {
    $text = str_replace(["\r\n", "\n", "\r"], "\\n", $text);
    $text = str_replace(["\\", ";", ","], ["\\\\", "\\;", "\\,"], $text);
    return $text;
} 