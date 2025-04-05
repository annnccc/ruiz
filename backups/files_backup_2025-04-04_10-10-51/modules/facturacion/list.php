<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener el período de facturación (mes y año)
$current_year = date('Y');
$current_month = date('m');

// Filtros
$year = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : $current_year;
$month = isset($_GET['month']) && is_numeric($_GET['month']) ? intval($_GET['month']) : $current_month;
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
    
    // Obtener años para el selector
    $sql_years = "SELECT DISTINCT YEAR(fecha) as year FROM citas ORDER BY year DESC";
    $stmt_years = $db->prepare($sql_years);
    $stmt_years->execute();
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al recuperar los datos: ' . $e->getMessage());
    $summary = [
        'total_citas' => 0,
        'total_pagado' => 0,
        'total_pendiente' => 0,
        'total_facturado' => 0
    ];
    $citas = [];
    $available_years = [$current_year];
}

// Título y breadcrumbs
$titulo_pagina = "Facturación - " . nombreMes($month) . " " . $year;
$title = $titulo_pagina;
$breadcrumbs = [
    'Facturación' => '#'
];

// Nombres de meses en español
function nombreMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$mes] ?? '';
}

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">payments</span><?= $titulo_pagina ?>
        </h1>
        
        <a href="<?= BASE_URL ?>/modules/facturacion/pdf_report.php?year=<?= $year ?>&month=<?= $month ?>&view=<?= $view_mode ?>" class="btn btn-primary">
            <span class="material-symbols-rounded">picture_as_pdf</span> Generar PDF
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <!-- Año -->
                <div class="col-md-3">
                    <label for="year" class="form-label">Año</label>
                    <select class="form-select" id="year" name="year">
                        <?php foreach ($available_years as $yr): ?>
                        <option value="<?= $yr ?>" <?= $yr == $year ? 'selected' : '' ?>><?= $yr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Mes -->
                <div class="col-md-3">
                    <label for="month" class="form-label">Mes</label>
                    <select class="form-select" id="month" name="month">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $month ? 'selected' : '' ?>><?= nombreMes($i) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- Filtro estado de pago -->
                <div class="col-md-3">
                    <label for="view" class="form-label">Mostrar</label>
                    <select class="form-select" id="view" name="view">
                        <option value="all" <?= $view_mode == 'all' ? 'selected' : '' ?>>Todas las citas</option>
                        <option value="paid" <?= $view_mode == 'paid' ? 'selected' : '' ?>>Solo pagadas</option>
                        <option value="pending" <?= $view_mode == 'pending' ? 'selected' : '' ?>>Solo pendientes</option>
                    </select>
                </div>
                
                <!-- Botón de filtro -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="material-symbols-rounded me-1">filter_alt</span>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tarjetas de resumen -->
    <div class="row mb-4">
        <!-- Total facturado -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total facturado</h6>
                    <h3 class="mb-0"><?= number_format($summary['total_facturado'] ?? 0, 2, ',', '.') ?> €</h3>
                    <p class="text-muted mt-2 mb-0"><?= $summary['total_citas'] ?? 0 ?> citas</p>
                </div>
            </div>
        </div>
        
        <!-- Total cobrado -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-success border-top border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Cobrado</h6>
                    <h3 class="mb-0 text-success"><?= number_format($summary['total_pagado'] ?? 0, 2, ',', '.') ?> €</h3>
                    <p class="text-muted mt-2 mb-0">
                        <?php 
                        $porcentaje_pagado = ($summary['total_facturado'] > 0) 
                            ? ($summary['total_pagado'] / $summary['total_facturado']) * 100 
                            : 0;
                        echo number_format($porcentaje_pagado, 1) . '%';
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Total pendiente -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-danger border-top border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Pendiente de cobro</h6>
                    <h3 class="mb-0 text-danger"><?= number_format($summary['total_pendiente'] ?? 0, 2, ',', '.') ?> €</h3>
                    <p class="text-muted mt-2 mb-0">
                        <?php 
                        $porcentaje_pendiente = ($summary['total_facturado'] > 0) 
                            ? ($summary['total_pendiente'] / $summary['total_facturado']) * 100 
                            : 0;
                        echo number_format($porcentaje_pendiente, 1) . '%';
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Navegación rápida -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Navegación rápida</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <a href="?year=<?= $year ?>&month=<?= $month-1 < 1 ? 12 : $month-1 ?>&view=<?= $view_mode ?>" class="btn btn-sm btn-outline-secondary">
                            <span class="material-symbols-rounded me-1">arrow_back</span>Mes anterior
                        </a>
                        <a href="?year=<?= $year ?>&month=<?= $month+1 > 12 ? 1 : $month+1 ?>&view=<?= $view_mode ?>" class="btn btn-sm btn-outline-secondary">
                            Mes siguiente<span class="material-symbols-rounded ms-1">arrow_forward</span>
                        </a>
                    </div>
                    <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>&view=<?= $view_mode ?>" class="btn btn-sm btn-outline-primary w-100">
                        <span class="material-symbols-rounded me-1">today</span>Mes actual
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Listado de citas -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">receipt_long</span>Detalle de citas
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>Servicio</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Método de pago</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($citas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <span class="material-symbols-rounded icon-xl mb-2" style="color: #ccc;">payments_off</span>
                                <p>No hay citas en este período con los filtros seleccionados</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($citas as $cita): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($cita['fecha'])) ?></td>
                                    <td><?= date('H:i', strtotime($cita['hora_inicio'])) ?> - <?= date('H:i', strtotime($cita['hora_fin'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $cita['paciente_id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($cita['servicio_nombre'] ?? 'No especificado') ?></td>
                                    <td class="fw-medium"><?= number_format($cita['precio'], 2, ',', '.') ?> €</td>
                                    <td>
                                        <?php if ($cita['pagada']): ?>
                                            <span class="badge bg-success">Pagada</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cita['pagada']): ?>
                                            <span class="d-flex align-items-center">
                                                <?php 
                                                $icon = '';
                                                switch ($cita['forma_pago']) {
                                                    case 'efectivo': $icon = 'payments'; break;
                                                    case 'tarjeta': $icon = 'credit_card'; break;
                                                    case 'transferencia': $icon = 'account_balance'; break;
                                                    case 'bizum': $icon = 'smartphone'; break;
                                                    default: $icon = 'question_mark'; break;
                                                }
                                                ?>
                                                <span class="material-symbols-rounded me-1"><?= $icon ?></span>
                                                <?= ucfirst($cita['forma_pago'] ?? '') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                            <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </a>
                                            <a href="<?= BASE_URL ?>/modules/citas/edit.php?id=<?= $cita['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                                <span class="material-symbols-rounded">edit</span>
                                            </a>
                                            <?php if (!$cita['pagada']): ?>
                                            <button type="button" class="btn btn-success btn-mark-paid" data-bs-toggle="tooltip" title="Marcar como pagada" 
                                                    data-id="<?= $cita['id'] ?>" data-paciente="<?= htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']) ?>">
                                                <span class="material-symbols-rounded">done</span>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para marcar como pagada -->
<div class="modal fade" id="markAsPaidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="markAsPaidForm" method="POST" action="<?= BASE_URL ?>/modules/facturacion/mark_as_paid.php">
                <div class="modal-body">
                    <input type="hidden" id="cita_id" name="cita_id">
                    <p id="modal-patient-name" class="mb-4"></p>
                    
                    <div class="mb-3">
                        <label for="fecha_pago" class="form-label">Fecha de pago</label>
                        <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="forma_pago" class="form-label">Forma de pago</label>
                        <select class="form-select" id="forma_pago" name="forma_pago" required>
                            <option value="">Seleccionar forma de pago...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta de crédito</option>
                            <option value="transferencia">Transferencia bancaria</option>
                            <option value="bizum">Bizum</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <span class="material-symbols-rounded me-1">payments</span>Registrar pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Manejo del modal de pago
    const markAsPaidModal = new bootstrap.Modal(document.getElementById('markAsPaidModal'));
    const markAsPaidButtons = document.querySelectorAll('.btn-mark-paid');
    
    markAsPaidButtons.forEach(button => {
        button.addEventListener('click', function() {
            const citaId = this.dataset.id;
            const paciente = this.dataset.paciente;
            
            document.getElementById('cita_id').value = citaId;
            document.getElementById('modal-patient-name').textContent = 'Registrar pago para la cita de ' + paciente;
            
            markAsPaidModal.show();
        });
    });
    
    // Actualizar automáticamente al cambiar los filtros
    const yearSelect = document.getElementById('year');
    const monthSelect = document.getElementById('month');
    const viewSelect = document.getElementById('view');
    
    yearSelect.addEventListener('change', applyFilters);
    monthSelect.addEventListener('change', applyFilters);
    viewSelect.addEventListener('change', applyFilters);
    
    function applyFilters() {
        document.querySelector('form').submit();
    }
});
</script>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 