<?php
require_once '../../includes/config.php';
require_once ROOT_PATH . '/vendor/autoload.php'; // Asumiendo que tienes FPDF o TCPDF instalado

// Obtener el período de facturación (mes y año)
$year = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) && is_numeric($_GET['month']) ? intval($_GET['month']) : date('m');
$view_mode = isset($_GET['view']) && in_array($_GET['view'], ['all', 'paid', 'pending']) ? $_GET['view'] : 'all';

// Fechas para filtrar
$date_start = sprintf('%04d-%02d-01', $year, $month);
$date_end = date('Y-m-t', strtotime($date_start));

// Condiciones según el modo de vista
$payment_condition = "";
if ($view_mode === 'paid') {
    $payment_condition = "AND c.pagada = 1";
} elseif ($view_mode === 'pending') {
    $payment_condition = "AND c.pagada = 0";
}

try {
    $db = getDB();
    
    // Consulta de resumen
    $sql_summary = "SELECT 
                    COUNT(*) as total_citas,
                    SUM(CASE WHEN c.pagada = 1 THEN c.precio ELSE 0 END) as total_pagado,
                    SUM(CASE WHEN c.pagada = 0 THEN c.precio ELSE 0 END) as total_pendiente,
                    SUM(c.precio) as total_facturado
                FROM citas c
                WHERE c.fecha BETWEEN :date_start AND :date_end";
                
    $stmt_summary = $db->prepare($sql_summary);
    $stmt_summary->bindParam(':date_start', $date_start, PDO::PARAM_STR);
    $stmt_summary->bindParam(':date_end', $date_end, PDO::PARAM_STR);
    $stmt_summary->execute();
    $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);
    
    // Consulta de citas
    $sql_citas = "SELECT 
                    c.id, c.fecha, c.hora_inicio, c.hora_fin, c.precio, c.pagada, 
                    c.fecha_pago, c.forma_pago, c.servicio_id,
                    p.id as paciente_id, p.nombre, p.apellidos,
                    s.nombre as servicio_nombre
                FROM citas c
                LEFT JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN servicios s ON c.servicio_id = s.id
                WHERE c.fecha BETWEEN :date_start AND :date_end
                $payment_condition
                ORDER BY c.fecha ASC, c.hora_inicio ASC";
                
    $stmt_citas = $db->prepare($sql_citas);
    $stmt_citas->bindParam(':date_start', $date_start, PDO::PARAM_STR);
    $stmt_citas->bindParam(':date_end', $date_end, PDO::PARAM_STR);
    $stmt_citas->execute();
    $citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al recuperar los datos: " . $e->getMessage());
}

// Nombres de meses en español
function nombreMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$mes] ?? '';
}

// Título del informe
$titulo = "Informe de Facturación - " . nombreMes($month) . " " . $year;

// Aquí generarías el PDF con FPDF o TCPDF
// Como no tenemos certeza de que tengas instalado FPDF/TCPDF, 
// simplemente mostraremos el contenido como HTML con cabeceras para descarga

// Configurar cabeceras para descarga
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="informe_facturacion_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.html"');

// Inicio del documento HTML
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>' . $titulo . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { margin-bottom: 30px; }
        .summary h2 { color: #555; margin-bottom: 15px; }
        .summary table { width: 50%; }
        .text-success { color: green; }
        .text-danger { color: red; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <h1>' . $titulo . '</h1>
    
    <div class="summary">
        <h2>Resumen del Período</h2>
        <table>
            <tr>
                <th>Total Citas</th>
                <td>' . $summary['total_citas'] . '</td>
            </tr>
            <tr>
                <th>Total Facturado</th>
                <td>' . number_format($summary['total_facturado'], 2, ',', '.') . ' €</td>
            </tr>
            <tr>
                <th>Total Cobrado</th>
                <td class="text-success">' . number_format($summary['total_pagado'], 2, ',', '.') . ' € (' . 
                    number_format(($summary['total_facturado'] > 0 ? ($summary['total_pagado'] / $summary['total_facturado']) * 100 : 0), 1) . '%)</td>
            </tr>
            <tr>
                <th>Total Pendiente</th>
                <td class="text-danger">' . number_format($summary['total_pendiente'], 2, ',', '.') . ' € (' . 
                    number_format(($summary['total_facturado'] > 0 ? ($summary['total_pendiente'] / $summary['total_facturado']) * 100 : 0), 1) . '%)</td>
            </tr>
        </table>
    </div>
    
    <h2>Detalle de Citas</h2>';

if (count($citas) > 0) {
    echo '<table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Paciente</th>
                <th>Servicio</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Forma de Pago</th>
            </tr>
        </thead>
        <tbody>';
        
    foreach ($citas as $cita) {
        $estado = $cita['pagada'] ? '<span class="text-success">Pagada</span>' : '<span class="text-danger">Pendiente</span>';
        $forma_pago = $cita['pagada'] ? ucfirst($cita['forma_pago']) : '-';
        
        echo '<tr>
            <td>' . date('d/m/Y', strtotime($cita['fecha'])) . '</td>
            <td>' . date('H:i', strtotime($cita['hora_inicio'])) . ' - ' . date('H:i', strtotime($cita['hora_fin'])) . '</td>
            <td>' . htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']) . '</td>
            <td>' . htmlspecialchars($cita['servicio_nombre'] ?? 'No especificado') . '</td>
            <td class="text-right">' . number_format($cita['precio'], 2, ',', '.') . ' €</td>
            <td>' . $estado . '</td>
            <td>' . $forma_pago . '</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>';
} else {
    echo '<p class="text-center">No hay citas en este período con los filtros seleccionados</p>';
}

echo '<div class="footer">
        <p>Informe generado el ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';

exit;
?> 