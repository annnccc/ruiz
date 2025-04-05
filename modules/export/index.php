<?php
/**
 * Página principal del módulo de exportación
 * Permite seleccionar y generar reportes en diferentes formatos
 */

// Comprobar que estamos en el contexto correcto
$relativePath = '../../';

// Incluir archivos necesarios
require_once $relativePath . 'includes/config.php';
require_once $relativePath . 'includes/db.php';
require_once $relativePath . 'includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/export_functions.php';

// Verificar que el usuario está autenticado
requiereLogin();

// Obtener la lista de reportes disponibles
$availableReports = getAvailableReports();

// Procesar la solicitud de exportación si existe
$exportResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    // Obtener parámetros
    $reportCode = isset($_POST['report']) ? $_POST['report'] : '';
    $format = isset($_POST['format']) ? $_POST['format'] : EXPORT_FORMAT_PDF;
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    
    // Generar exportación
    $exportResult = generateExport($reportCode, $format, $filters);
}

// Título de la página
$titulo_pagina = "Exportación de Datos";

// Iniciar captura del contenido
startPageContent();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0"><i class="material-symbols-rounded me-2 align-middle">download</i> Exportación de Datos</h4>
                    </div>
                    
                    <p class="text-muted mb-4">Genera reportes en diferentes formatos (PDF, Excel) con los datos del sistema.</p>
                    
                    <?php if ($exportResult): ?>
                        <?php if ($exportResult['success']): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>¡Reporte generado correctamente!</strong>
                                <p class="mb-2">Tu archivo está listo para descargar.</p>
                                <a href="<?= $exportResult['url'] ?>" class="btn btn-sm btn-success" download>
                                    <i class="material-symbols-rounded me-1 align-middle">download</i> Descargar Archivo
                                </a>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error al generar el reporte:</strong> <?= $exportResult['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Reportes disponibles</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush report-list">
                        <?php foreach ($availableReports as $code => $report): ?>
                            <a href="#" class="list-group-item list-group-item-action report-item" data-report="<?= $code ?>" data-type="<?= $report['type'] ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1"><?= htmlspecialchars($report['name']) ?></h6>
                                    <?php
                                    $icon = '';
                                    switch ($report['type']) {
                                        case REPORT_PATIENTS:
                                            $icon = 'person';
                                            break;
                                        case REPORT_APPOINTMENTS:
                                            $icon = 'calendar_month';
                                            break;
                                        case REPORT_CUSTOM:
                                            $icon = 'description';
                                            break;
                                    }
                                    ?>
                                    <i class="material-symbols-rounded text-primary"><?= $icon ?></i>
                                </div>
                                <p class="mb-1 small text-muted"><?= htmlspecialchars($report['description']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0" id="reportConfigTitle">Configurar exportación</h5>
                </div>
                <div class="card-body">
                    <div id="reportPlaceholder" class="py-5 text-center">
                        <i class="material-symbols-rounded d-block mx-auto mb-3" style="font-size: 3rem; color: var(--bs-secondary);">description</i>
                        <h5 class="text-muted">Selecciona un reporte</h5>
                        <p class="text-muted mb-0">Elige un reporte de la lista para configurar y generar una exportación.</p>
                    </div>
                    
                    <div id="reportConfig" class="d-none">
                        <form id="exportForm" method="post">
                            <input type="hidden" name="action" value="export">
                            <input type="hidden" name="report" id="selectedReport">
                            
                            <div class="mb-4">
                                <h6>Formato de exportación</h6>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="formatPDF" value="pdf" checked>
                                        <label class="form-check-label" for="formatPDF">
                                            <i class="material-symbols-rounded me-1 align-middle">picture_as_pdf</i> PDF
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="formatExcel" value="excel">
                                        <label class="form-check-label" for="formatExcel">
                                            <i class="material-symbols-rounded me-1 align-middle">table</i> Excel
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filtros para pacientes -->
                            <div id="patientFilters" class="filters-section d-none">
                                <h6>Filtros</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="filterGenero" class="form-label">Género</label>
                                        <select class="form-select" id="filterGenero" name="filters[genero]">
                                            <option value="">Todos</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                            <option value="O">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="filterCiudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="filterCiudad" name="filters[ciudad]" placeholder="Filtrar por ciudad">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filtros para citas -->
                            <div id="appointmentFilters" class="filters-section d-none">
                                <h6>Filtros</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="filterFecha" class="form-label">Fecha específica</label>
                                        <input type="date" class="form-control" id="filterFecha" name="filters[fecha]">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="filterEstado" class="form-label">Estado</label>
                                        <select class="form-select" id="filterEstado" name="filters[estado]">
                                            <option value="">Todos</option>
                                            <option value="pendiente">Pendiente</option>
                                            <option value="completada">Completada</option>
                                            <option value="cancelada">Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="filterFechaDesde" class="form-label">Desde</label>
                                        <input type="date" class="form-control" id="filterFechaDesde" name="filters[fecha_desde]">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="filterFechaHasta" class="form-label">Hasta</label>
                                        <input type="date" class="form-control" id="filterFechaHasta" name="filters[fecha_hasta]">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filtros para reportes personalizados -->
                            <div id="customFilters" class="filters-section d-none">
                                <h6>Filtros</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="filterPacienteId" class="form-label">Paciente</label>
                                        <select class="form-select" id="filterPacienteId" name="filters[paciente_id]">
                                            <option value="">Seleccionar paciente</option>
                                            <?php
                                            try {
                                                $db = getDB();
                                                $stmt = $db->query("SELECT id, nombre, apellidos FROM pacientes ORDER BY apellidos, nombre");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['apellidos'] . ', ' . $row['nombre']) . '</option>';
                                                }
                                            } catch (PDOException $e) {
                                                error_log('Error al obtener pacientes: ' . $e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-symbols-rounded me-1 align-middle">download</i> Generar reporte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportItems = document.querySelectorAll('.report-item');
    const reportPlaceholder = document.getElementById('reportPlaceholder');
    const reportConfig = document.getElementById('reportConfig');
    const reportConfigTitle = document.getElementById('reportConfigTitle');
    const selectedReport = document.getElementById('selectedReport');
    const filterSections = document.querySelectorAll('.filters-section');
    const patientFilters = document.getElementById('patientFilters');
    const appointmentFilters = document.getElementById('appointmentFilters');
    const customFilters = document.getElementById('customFilters');
    
    // Selección de reporte
    reportItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Actualizar clases activas
            reportItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Obtener código y tipo del reporte
            const reportCode = this.dataset.report;
            const reportType = this.dataset.type;
            
            // Actualizar título
            reportConfigTitle.textContent = 'Configurar: ' + this.querySelector('h6').textContent;
            
            // Actualizar valor del reporte seleccionado
            selectedReport.value = reportCode;
            
            // Mostrar configuración y ocultar placeholder
            reportPlaceholder.classList.add('d-none');
            reportConfig.classList.remove('d-none');
            
            // Ocultar todos los filtros
            filterSections.forEach(section => section.classList.add('d-none'));
            
            // Mostrar filtros según el tipo de reporte
            switch (reportType) {
                case '<?= REPORT_PATIENTS ?>':
                    patientFilters.classList.remove('d-none');
                    break;
                case '<?= REPORT_APPOINTMENTS ?>':
                    appointmentFilters.classList.remove('d-none');
                    break;
                case '<?= REPORT_CUSTOM ?>':
                    if (reportCode === 'patients_appointments') {
                        customFilters.classList.remove('d-none');
                    }
                    break;
            }
            
            // Verificar si es reporte de citas para un día específico
            if (reportCode === 'appointments_day') {
                // Establecer fecha actual
                document.getElementById('filterFecha').valueAsDate = new Date();
                // Ocultar campos de rango
                document.getElementById('filterFechaDesde').parentElement.classList.add('d-none');
                document.getElementById('filterFechaHasta').parentElement.classList.add('d-none');
            } else if (reportCode === 'appointments_list') {
                // Mostrar campos de rango
                document.getElementById('filterFechaDesde').parentElement.classList.remove('d-none');
                document.getElementById('filterFechaHasta').parentElement.classList.remove('d-none');
            }
        });
    });
    
    // Validar formulario antes de enviar
    document.getElementById('exportForm').addEventListener('submit', function(e) {
        const reportCode = selectedReport.value;
        
        if (!reportCode) {
            e.preventDefault();
            alert('Por favor, selecciona un reporte.');
            return;
        }
        
        // Validaciones específicas por tipo de reporte
        if (reportCode === 'patients_appointments') {
            const pacienteId = document.getElementById('filterPacienteId').value;
            if (!pacienteId) {
                e.preventDefault();
                alert('Por favor, selecciona un paciente para generar el historial de citas.');
                return;
            }
        }
    });
});
</script>

<?php
// Finalizar captura del contenido y mostrar
endPageContent();
?> 