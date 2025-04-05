<?php
/**
 * API de búsqueda global
 * Punto de entrada para búsquedas AJAX desde cualquier parte del sistema
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación para acceder a esta API
requiereLogin();

// Establecer cabeceras para JSON
header('Content-Type: application/json');

// Inicializar respuesta
$response = [
    'success' => false,
    'results' => [],
    'message' => 'No se ha proporcionado un término de búsqueda'
];

// Verificar si hay un término de búsqueda
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode($response);
    exit;
}

// Obtener y sanitizar término de búsqueda
$query = sanitize($_GET['q']);

// Solo procesar búsquedas de al menos 2 caracteres
if (strlen($query) < 2) {
    $response['message'] = 'El término de búsqueda debe tener al menos 2 caracteres';
    echo json_encode($response);
    exit;
}

try {
    $db = getDB();
    $results = [];

    // Buscar pacientes
    $stmt = $db->prepare("
        SELECT id, nombre, apellidos, dni, telefono, email
        FROM pacientes
        WHERE 
            nombre LIKE :query1 OR 
            apellidos LIKE :query2 OR 
            dni LIKE :query3 OR 
            telefono LIKE :query4 OR 
            email LIKE :query5
        ORDER BY apellidos, nombre ASC
        LIMIT 5
    ");
    $stmt->bindValue(':query1', "%$query%");
    $stmt->bindValue(':query2', "%$query%");
    $stmt->bindValue(':query3', "%$query%");
    $stmt->bindValue(':query4', "%$query%");
    $stmt->bindValue(':query5', "%$query%");
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($pacientes)) {
        $pacientesResults = [];
        foreach ($pacientes as $paciente) {
            $pacientesResults[] = [
                'id' => $paciente['id'],
                'title' => $paciente['apellidos'] . ', ' . $paciente['nombre'],
                'detail' => 'DNI: ' . $paciente['dni'] . ' | Tel: ' . $paciente['telefono'],
                'url' => BASE_URL . '/modules/pacientes/view.php?id=' . $paciente['id'],
                'category' => 'paciente'
            ];
        }
        $results['pacientes'] = [
            'title' => 'Pacientes',
            'icon' => 'person',
            'items' => $pacientesResults,
            'more_url' => BASE_URL . '/modules/pacientes/list.php?search=' . urlencode($query)
        ];
    }

    // Buscar citas
    $stmt = $db->prepare("
        SELECT c.id, c.fecha, c.hora_inicio, c.estado, p.nombre, p.apellidos
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        WHERE 
            p.nombre LIKE :query1 OR 
            p.apellidos LIKE :query2 OR
            c.notas LIKE :query3 OR
            c.estado LIKE :query4
        ORDER BY c.fecha DESC, c.hora_inicio ASC
        LIMIT 5
    ");
    $stmt->bindValue(':query1', "%$query%");
    $stmt->bindValue(':query2', "%$query%");
    $stmt->bindValue(':query3', "%$query%");
    $stmt->bindValue(':query4', "%$query%");
    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($citas)) {
        $citasResults = [];
        foreach ($citas as $cita) {
            $citasResults[] = [
                'id' => $cita['id'],
                'title' => formatDateToView($cita['fecha']) . ' - ' . formatTime($cita['hora_inicio']),
                'detail' => 'Paciente: ' . $cita['apellidos'] . ', ' . $cita['nombre'] . ' | Estado: ' . ucfirst($cita['estado']),
                'url' => BASE_URL . '/modules/citas/view.php?id=' . $cita['id'],
                'category' => 'cita',
                'estado' => $cita['estado']
            ];
        }
        $results['citas'] = [
            'title' => 'Citas',
            'icon' => 'calendar_month',
            'items' => $citasResults,
            'more_url' => BASE_URL . '/modules/citas/list.php?search=' . urlencode($query)
        ];
    }

    // Buscar servicios (si existen en el sistema)
    $stmt = $db->prepare("
        SELECT id, nombre, descripcion, precio
        FROM servicios
        WHERE 
            nombre LIKE :query1 OR 
            descripcion LIKE :query2
        ORDER BY nombre ASC
        LIMIT 5
    ");
    $stmt->bindValue(':query1', "%$query%");
    $stmt->bindValue(':query2', "%$query%");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($servicios)) {
        $serviciosResults = [];
        foreach ($servicios as $servicio) {
            $serviciosResults[] = [
                'id' => $servicio['id'],
                'title' => $servicio['nombre'],
                'detail' => formatCurrency($servicio['precio']),
                'url' => BASE_URL . '/modules/servicios/view.php?id=' . $servicio['id'],
                'category' => 'servicio'
            ];
        }
        $results['servicios'] = [
            'title' => 'Servicios',
            'icon' => 'medical_services',
            'items' => $serviciosResults,
            'more_url' => BASE_URL . '/modules/servicios/list.php?search=' . urlencode($query)
        ];
    }

    // Preparar la respuesta
    $response = [
        'success' => true,
        'results' => $results,
        'message' => empty($results) ? 'No se encontraron resultados' : '',
        'query' => $query
    ];

} catch (PDOException $e) {
    $response = [
        'success' => false,
        'results' => [],
        'message' => 'Error en la búsqueda: ' . $e->getMessage()
    ];
}

// Devolver resultados como JSON
echo json_encode($response);
exit; 