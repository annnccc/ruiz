<?php
// Habilitar visualización de errores para depuración
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar que la solicitud tenga el encabezado AJAX 
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Esta página solo puede ser accedida mediante AJAX'
    ]);
    exit;
}

try {
    // Obtener el término de búsqueda
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 10;
    $offset = ($page - 1) * $records_per_page;
    
    // Ordenación
    $sort_field = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'apellidos';
    $sort_direction = isset($_GET['direction']) && strtolower($_GET['direction']) === 'desc' ? 'DESC' : 'ASC';
    
    // Validar campos de ordenación permitidos
    $allowed_sort_fields = ['nombre', 'apellidos', 'dni', 'telefono', 'email', 'fecha_nacimiento', 'num_citas'];
    if (!in_array($sort_field, $allowed_sort_fields)) {
        $sort_field = 'apellidos';
    }
    
    // Construir la condición de búsqueda
    $searchCondition = '';
    if (!empty($search)) {
        $searchCondition = "WHERE nombre LIKE :search_nombre OR apellidos LIKE :search_apellidos OR dni LIKE :search_dni OR telefono LIKE :search_telefono OR email LIKE :search_email";
    }
    
    try {
        $db = getDB();
        
        // Obtener total de registros
        $query = "SELECT COUNT(*) AS total FROM pacientes $searchCondition";
        $stmt = $db->prepare($query);
        
        if (!empty($search)) {
            $stmt->bindValue(':search_nombre', "%$search%");
            $stmt->bindValue(':search_apellidos', "%$search%");
            $stmt->bindValue(':search_dni', "%$search%");
            $stmt->bindValue(':search_telefono', "%$search%");
            $stmt->bindValue(':search_email', "%$search%");
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_results = $result['total'];
        $total_pages = ceil($total_results / $records_per_page);
        
        // Obtener pacientes
        $query = "SELECT p.*, 
                 (SELECT COUNT(*) FROM citas WHERE paciente_id = p.id) AS num_citas 
                 FROM pacientes p 
                 $searchCondition ";
                 
        // Manejo especial para ordenar por num_citas
        if ($sort_field === 'num_citas') {
            $query .= " ORDER BY num_citas $sort_direction, apellidos ASC";
        } else {
            $query .= " ORDER BY $sort_field $sort_direction";
            // Añadir ordenación secundaria si no es por apellidos
            if ($sort_field !== 'apellidos') {
                $query .= ", apellidos ASC";
            }
        }
        
        $query .= " LIMIT :offset, :records_per_page";
        $stmt = $db->prepare($query);
        
        if (!empty($search)) {
            $stmt->bindValue(':search_nombre', "%$search%");
            $stmt->bindValue(':search_apellidos', "%$search%");
            $stmt->bindValue(':search_dni', "%$search%");
            $stmt->bindValue(':search_telefono', "%$search%");
            $stmt->bindValue(':search_email', "%$search%");
        }
        
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparar la respuesta HTML
        $html = '';
        
        if (empty($pacientes)) {
            $html .= '<tr><td colspan="7" class="text-center py-4">No se encontraron pacientes</td></tr>';
        } else {
            foreach ($pacientes as $paciente) {
                $html .= '<tr>';
                $html .= '<td>';
                $html .= '<div class="d-flex align-items-center">';
                $html .= '<div class="flex-grow-1">';
                $html .= '<h6 class="mb-1">';
                $html .= '<a href="view.php?id=' . $paciente['id'] . '" class="text-primary">';
                $html .= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']);
                $html .= '</a>';
                $html .= '</h6>';
                if (!empty($paciente['dni'])):
                $html .= '<span class="text-muted small">' . htmlspecialchars($paciente['dni']) . '</span>';
                endif;
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</td>';
                $html .= '<td>';
                if (!empty($paciente['telefono'])):
                $html .= '<span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">phone</span>';
                $html .= htmlspecialchars($paciente['telefono']);
                else:
                $html .= '<span class="text-muted">—</span>';
                endif;
                $html .= '</td>';
                $html .= '<td>';
                if (!empty($paciente['email'])):
                $html .= '<span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">email</span>';
                $html .= htmlspecialchars($paciente['email']);
                else:
                $html .= '<span class="text-muted">—</span>';
                endif;
                $html .= '</td>';
                $html .= '<td>';
                if (isset($paciente['num_citas']) && $paciente['num_citas'] > 0):
                $html .= '<div class="mb-1 small">';
                $html .= '<span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">event</span>';
                $html .= $paciente['num_citas'] . ' cita' . ($paciente['num_citas'] > 1 ? 's' : '');
                $html .= '</div>';
                else:
                $html .= '<div class="mb-1 small text-muted">Sin citas</div>';
                endif;
                $html .= '</td>';
                $html .= '<td class="text-center">';
                if (isset($paciente['consentimiento_firmado']) && $paciente['consentimiento_firmado'] == 1):
                $html .= '<span class="material-symbols-rounded text-success" style="font-size: 24px;">check_circle</span>';
                else:
                $html .= '<span class="material-symbols-rounded text-danger" style="font-size: 24px;">cancel</span>';
                endif;
                $html .= '</td>';
                $html .= '<td class="text-center">';
                $html .= '<div class="table-actions">';
                $html .= '<a href="view.php?id=' . $paciente['id'] . '" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">';
                $html .= '<span class="material-symbols-rounded">visibility</span>';
                $html .= '</a>';
                $html .= '<a href="edit.php?id=' . $paciente['id'] . '" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">';
                $html .= '<span class="material-symbols-rounded">edit</span>';
                $html .= '</a>';
                $html .= '<a href="delete.php?id=' . $paciente['id'] . '" class="btn btn-delete btn-delete-paciente" data-bs-toggle="tooltip" title="Eliminar">';
                $html .= '<span class="material-symbols-rounded">delete</span>';
                $html .= '</a>';
                $html .= '</div>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        
        // Preparar la paginación
        $pagination = '';
        if ($total_pages > 1) {
            $pagination .= '<div class="mt-4">';
            $pagination .= '<nav aria-label="Page navigation">';
            $pagination .= '<ul class="pagination justify-content-center">';
            
            // Botón anterior
            $pagination .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
            $pagination .= '<a class="page-link" href="#" data-page="' . ($page - 1) . '" tabindex="-1">Anterior</a>';
            $pagination .= '</li>';
            
            // Números de página
            for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                $pagination .= '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
                $pagination .= '<a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>';
                $pagination .= '</li>';
            }
            
            // Botón siguiente
            $pagination .= '<li class="page-item ' . ($page >= $total_pages ? 'disabled' : '') . '">';
            $pagination .= '<a class="page-link" href="#" data-page="' . ($page + 1) . '">Siguiente</a>';
            $pagination .= '</li>';
            
            $pagination .= '</ul>';
            $pagination .= '</nav>';
            $pagination .= '</div>';
        }
        
        // Enviar respuesta JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'pagination' => $pagination,
            'total_results' => $total_results,
            'page' => $page,
            'total_pages' => $total_pages
        ]);
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error en la base de datos: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error en el servidor: ' . $e->getMessage()
    ]);
} 