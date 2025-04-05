<?php
/**
 * Funciones para la exportación de datos
 * Permite exportar información a diferentes formatos (PDF, Excel)
 */

// Prevenir acceso directo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Obtiene la lista de reportes disponibles para mostrar en la interfaz
 * 
 * @return array Lista de reportes disponibles
 */
function getAvailableReports() {
    global $export_config;
    
    $reports = [];
    foreach ($export_config['reports'] as $code => $report) {
        $reports[$code] = [
            'name' => $report['name'],
            'description' => $report['description'],
            'type' => $report['type']
        ];
    }
    
    return $reports;
}

/**
 * Genera una exportación de datos en el formato especificado
 * 
 * @param string $reportCode Código del reporte a generar
 * @param string $format Formato de exportación (pdf, excel)
 * @param array $filters Filtros aplicados a los datos
 * @return array Resultado de la operación (éxito, mensaje, archivo generado)
 */
function generateExport($reportCode, $format, $filters = []) {
    global $export_config;
    
    // Verificar que el reporte existe
    if (!isset($export_config['reports'][$reportCode])) {
        return ['success' => false, 'message' => 'El reporte solicitado no existe'];
    }
    
    // Obtener configuración del reporte
    $reportConfig = $export_config['reports'][$reportCode];
    
    // Obtener datos según el tipo de reporte
    $data = [];
    switch ($reportConfig['type']) {
        case REPORT_PATIENTS:
            $data = getPatientData($reportConfig, $filters);
            break;
        case REPORT_APPOINTMENTS:
            $data = getAppointmentData($reportConfig, $filters);
            break;
        case REPORT_CUSTOM:
            // Reportes personalizados pueden tener lógica específica
            if ($reportCode === 'patients_appointments') {
                $data = getPatientAppointmentsData($reportConfig, $filters);
            }
            break;
    }
    
    // Si no hay datos, retornar error
    if (empty($data)) {
        return ['success' => false, 'message' => 'No hay datos para generar el reporte'];
    }
    
    // Generar el documento según el formato
    $result = ['success' => false, 'message' => 'Formato no soportado'];
    
    switch ($format) {
        case EXPORT_FORMAT_PDF:
            $result = generatePDF($reportConfig, $data);
            break;
        case EXPORT_FORMAT_EXCEL:
            $result = generateExcel($reportConfig, $data);
            break;
    }
    
    return $result;
}

/**
 * Obtiene datos de pacientes según configuración y filtros
 * 
 * @param array $reportConfig Configuración del reporte
 * @param array $filters Filtros aplicados
 * @return array Datos obtenidos
 */
function getPatientData($reportConfig, $filters) {
    try {
        $db = getDB();
        $sql = "SELECT " . implode(', ', $reportConfig['fields']) . " FROM pacientes WHERE 1 = 1";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['genero']) && !empty($filters['genero'])) {
                $sql .= " AND genero = :genero";
                $params[':genero'] = $filters['genero'];
            }
            
            if (isset($filters['ciudad']) && !empty($filters['ciudad'])) {
                $sql .= " AND ciudad LIKE :ciudad";
                $params[':ciudad'] = '%' . $filters['ciudad'] . '%';
            }
        }
        
        $sql .= " ORDER BY apellidos, nombre";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error al obtener datos de pacientes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene datos de citas según configuración y filtros
 * 
 * @param array $reportConfig Configuración del reporte
 * @param array $filters Filtros aplicados
 * @return array Datos obtenidos
 */
function getAppointmentData($reportConfig, $filters) {
    try {
        $db = getDB();
        
        // Determinar si necesitamos unir con tabla de pacientes
        $needsPatientData = false;
        foreach ($reportConfig['fields'] as $field) {
            if ($field == 'paciente_id') {
                $needsPatientData = true;
                break;
            }
        }
        
        $fields = array_map(function($field) {
            return 'c.' . $field;
        }, $reportConfig['fields']);
        
        $sql = "SELECT " . implode(', ', $fields);
        
        if ($needsPatientData) {
            $sql .= ", p.nombre, p.apellidos";
        }
        
        $sql .= " FROM citas c";
        
        if ($needsPatientData) {
            $sql .= " LEFT JOIN pacientes p ON c.paciente_id = p.id";
        }
        
        $sql .= " WHERE 1 = 1";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['fecha']) && !empty($filters['fecha'])) {
                $sql .= " AND DATE(c.fecha) = :fecha";
                $params[':fecha'] = $filters['fecha'];
            }
            
            if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
                $sql .= " AND c.fecha >= :fecha_desde";
                $params[':fecha_desde'] = $filters['fecha_desde'];
            }
            
            if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
                $sql .= " AND c.fecha <= :fecha_hasta";
                $params[':fecha_hasta'] = $filters['fecha_hasta'];
            }
            
            if (isset($filters['estado']) && !empty($filters['estado'])) {
                $sql .= " AND c.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }
        }
        
        $sql .= " ORDER BY c.fecha DESC, c.hora_inicio ASC";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error al obtener datos de citas: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene historial de citas de un paciente específico
 * 
 * @param array $reportConfig Configuración del reporte
 * @param array $filters Filtros aplicados
 * @return array Datos obtenidos
 */
function getPatientAppointmentsData($reportConfig, $filters) {
    try {
        if (!isset($filters['paciente_id']) || empty($filters['paciente_id'])) {
            return [];
        }
        
        $pacienteId = $filters['paciente_id'];
        
        // Obtener información del paciente
        $db = getDB();
        $stmtPatient = $db->prepare("SELECT id, nombre, apellidos FROM pacientes WHERE id = :id");
        $stmtPatient->bindParam(':id', $pacienteId, PDO::PARAM_INT);
        $stmtPatient->execute();
        $patientInfo = $stmtPatient->fetch(PDO::FETCH_ASSOC);
        
        if (!$patientInfo) {
            return [];
        }
        
        // Obtener citas del paciente
        $fields = array_map(function($field) {
            return 'c.' . $field;
        }, $reportConfig['fields']);
        
        $sql = "SELECT " . implode(', ', $fields);
        $sql .= " FROM citas c WHERE c.paciente_id = :paciente_id";
        
        // Aplicar filtros adicionales
        $params = [':paciente_id' => $pacienteId];
        
        $sql .= " ORDER BY c.fecha DESC, c.hora_inicio ASC";
        
        $stmtAppts = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmtAppts->bindValue($key, $value);
        }
        
        $stmtAppts->execute();
        $appointments = $stmtAppts->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar datos
        return [
            'patient' => $patientInfo,
            'appointments' => $appointments
        ];
    } catch (PDOException $e) {
        error_log('Error al obtener historial de citas del paciente: ' . $e->getMessage());
        return [];
    }
}

/**
 * Genera un documento PDF con los datos proporcionados
 * 
 * @param array $reportConfig Configuración del reporte
 * @param array $data Datos a incluir en el documento
 * @return array Resultado de la operación
 */
function generatePDF($reportConfig, $data) {
    // Verificar si está la librería TCPDF
    if (!file_exists(__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php')) {
        return [
            'success' => false, 
            'message' => 'La librería TCPDF no está instalada. Por favor ejecute "composer install"'
        ];
    }
    
    try {
        // Aquí iría la lógica para generar el PDF con TCPDF
        // Por ahora simularemos la creación
        $filename = 'reporte_' . $reportConfig['type'] . '_' . date('Ymd_His') . '.pdf';
        $filepath = __DIR__ . '/../../temp/' . $filename;
        
        // Asegurarse de que existe el directorio temp
        if (!is_dir(__DIR__ . '/../../temp/')) {
            mkdir(__DIR__ . '/../../temp/', 0755, true);
        }
        
        // Simulación de creación del archivo
        file_put_contents($filepath, 'Simulación PDF');
        
        return [
            'success' => true,
            'message' => 'PDF generado correctamente',
            'file' => $filename,
            'path' => $filepath,
            'url' => BASE_URL . 'temp/' . $filename
        ];
    } catch (Exception $e) {
        error_log('Error al generar PDF: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al generar el PDF: ' . $e->getMessage()];
    }
}

/**
 * Genera un documento Excel con los datos proporcionados
 * 
 * @param array $reportConfig Configuración del reporte
 * @param array $data Datos a incluir en el documento
 * @return array Resultado de la operación
 */
function generateExcel($reportConfig, $data) {
    // Verificar si está la librería PhpSpreadsheet
    if (!file_exists(__DIR__ . '/../../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet.php')) {
        return [
            'success' => false, 
            'message' => 'La librería PhpSpreadsheet no está instalada. Por favor ejecute "composer install"'
        ];
    }
    
    try {
        // Aquí iría la lógica para generar el Excel con PhpSpreadsheet
        // Por ahora simularemos la creación
        $filename = 'reporte_' . $reportConfig['type'] . '_' . date('Ymd_His') . '.xlsx';
        $filepath = __DIR__ . '/../../temp/' . $filename;
        
        // Asegurarse de que existe el directorio temp
        if (!is_dir(__DIR__ . '/../../temp/')) {
            mkdir(__DIR__ . '/../../temp/', 0755, true);
        }
        
        // Simulación de creación del archivo
        file_put_contents($filepath, 'Simulación Excel');
        
        return [
            'success' => true,
            'message' => 'Excel generado correctamente',
            'file' => $filename,
            'path' => $filepath,
            'url' => BASE_URL . 'temp/' . $filename
        ];
    } catch (Exception $e) {
        error_log('Error al generar Excel: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al generar el Excel: ' . $e->getMessage()];
    }
} 