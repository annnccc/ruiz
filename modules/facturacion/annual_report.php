<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener el año para el informe
$current_year = date('Y');
$year = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : $current_year;

try {
    $db = getDB();
    
    // Consulta para obtener datos mensuales
    $sql_monthly = "SELECT 
                    MONTH(fecha) as mes,
                    COUNT(*) as total_citas,
                    SUM(CASE WHEN pagada = 1 THEN precio ELSE 0 END) as total_pagado,
                    SUM(CASE WHEN pagada = 0 THEN precio ELSE 0 END) as total_pendiente,
                    SUM(precio) as total_facturado
                FROM citas
                WHERE YEAR(fecha) = :year
                GROUP BY MONTH(fecha)
                ORDER BY MONTH(fecha)";
                
    $stmt_monthly = $db->prepare($sql_monthly);
    $stmt_monthly->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt_monthly->execute();
    $monthly_data = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener el resumen anual
    $sql_yearly = "SELECT 
                   COUNT(*) as total_citas,
                   SUM(CASE WHEN pagada = 1 THEN precio ELSE 0 END) as total_pagado,
                   SUM(CASE WHEN pagada = 0 THEN precio ELSE 0 END) as total_pendiente,
                   SUM(precio) as total_facturado
                FROM citas
                WHERE YEAR(fecha) = :year";
                
    $stmt_yearly = $db->prepare($sql_yearly);
    $stmt_yearly->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt_yearly->execute();
    $yearly_summary = $stmt_yearly->fetch(PDO::FETCH_ASSOC);
    
    // Consulta para obtener los servicios más solicitados
    $sql_services = "SELECT 
                     s.nombre as servicio_nombre,
                     COUNT(*) as total_citas,
                     SUM(c.precio) as total_facturado
                    FROM citas c
                    JOIN servicios s ON c.servicio_id = s.id
                    WHERE YEAR(c.fecha) = :year
                    GROUP BY c.servicio_id
                    ORDER BY total_citas DESC
                    LIMIT 5";
                    
    $stmt_services = $db->prepare($sql_services);
    $stmt_services->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt_services->execute();
    $top_services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener años disponibles para el selector
    $sql_years = "SELECT DISTINCT YEAR(fecha) as year FROM citas ORDER BY year DESC";
    $stmt_years = $db->prepare($sql_years);
    $stmt_years->execute();
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al recuperar los datos: ' . $e->getMessage());
    $monthly_data = [];
    $yearly_summary = [
        'total_citas' => 0,
        'total_pagado' => 0,
        'total_pendiente' => 0,
        'total_facturado' => 0
    ];
    $top_services = [];
    $available_years = [$current_year];
}

// Preparar datos para gráficos en formato JSON
$chart_labels = [];
$chart_facturado = [];
$chart_pagado = [];
$chart_pendiente = [];

foreach ($monthly_data as $data) {
    $mes_nombre = nombreMes($data['mes']);
    $chart_labels[] = $mes_nombre;
    $chart_facturado[] = round($data['total_facturado'], 2);
    $chart_pagado[] = round($data['total_pagado'], 2);
    $chart_pendiente[] = round($data['total_pendiente'], 2);
}

// Título y breadcrumbs
$titulo_pagina = "Informe Anual " . $year;
$title = $titulo_pagina;
$breadcrumbs = [
    'Facturación' => BASE_URL . '/modules/facturacion/list.php',
    'Informe Anual' => '#'
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
            <span class="material-symbols-rounded me-2">analytics</span><?= $titulo_pagina ?>
        </h1>
        
        <div class="d-flex">
            <form id="yearForm" class="me-2">
                <select class="form-select" id="yearSelect" name="year">
                    <?php foreach ($available_years as $yr): ?>
                    <option value="<?= $yr ?>" <?= $yr == $year ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <a href="<?= BASE_URL ?>/modules/facturacion/pdf_annual_report.php?year=<?= $year ?>" class="btn btn-outline-primary">
                <span class="material-symbols-rounded me-1">picture_as_pdf</span>Generar PDF
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Tarjetas de resumen anual -->
    <div class="row mb-4">
        <!-- Total citas -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Citas totales</h6>
                    <h3 class="mb-0"><?= $yearly_summary['total_citas'] ?? 0 ?></h3>
                    <p class="text-muted mt-2 mb-0">
                        <?= number_format(($yearly_summary['total_citas'] ?? 0) / 12, 1) ?> citas/mes
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Total facturado -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total facturado</h6>
                    <h3 class="mb-0"><?= number_format($yearly_summary['total_facturado'] ?? 0, 2, ',', '.') ?> €</h3>
                    <p class="text-muted mt-2 mb-0">
                        <?= number_format(($yearly_summary['total_facturado'] ?? 0) / 12, 2, ',', '.') ?> €/mes
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Total cobrado -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-success border-top border-4">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total cobrado</h6>
                    <h3 class="mb-0 text-success"><?= number_format($yearly_summary['total_pagado'] ?? 0, 2, ',', '.') ?> €</h3>
                    <p class="text-muted mt-2 mb-0">
                        <?php 
                        $porcentaje_pagado = ($yearly_summary['total_facturado'] > 0) 
                            ? ($yearly_summary['total_pagado'] / $yearly_summary['total_facturado']) * 100 
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
                    <h3 class="mb-0 text-danger"><?= number_format($yearly_summary['total_pendiente'] ?? 0, 2, ',', '.') ?> €</h3>
                    <p class="text-muted mt-2 mb-0">
                        <?php 
                        $porcentaje_pendiente = ($yearly_summary['total_facturado'] > 0) 
                            ? ($yearly_summary['total_pendiente'] / $yearly_summary['total_facturado']) * 100 
                            : 0;
                        echo number_format($porcentaje_pendiente, 1) . '%';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico mensual -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">bar_chart</span>Facturación Mensual
            </h5>
        </div>
        <div class="card-body">
            <canvas id="monthlyChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- Tabla de datos mensuales y top servicios -->
    <div class="row">
        <!-- Tabla de datos mensuales -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">calendar_month</span>Datos Mensuales
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Citas</th>
                                    <th>Facturado</th>
                                    <th>Cobrado</th>
                                    <th>Pendiente</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthly_data)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No hay datos disponibles para este año</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($monthly_data as $data): ?>
                                    <tr>
                                        <td class="fw-medium"><?= nombreMes($data['mes']) ?></td>
                                        <td><?= $data['total_citas'] ?></td>
                                        <td><?= number_format($data['total_facturado'], 2, ',', '.') ?> €</td>
                                        <td class="text-success"><?= number_format($data['total_pagado'], 2, ',', '.') ?> €</td>
                                        <td class="text-danger"><?= number_format($data['total_pendiente'], 2, ',', '.') ?> €</td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>/modules/facturacion/list.php?year=<?= $year ?>&month=<?= $data['mes'] ?>" class="btn btn-sm btn-outline-primary">
                                                <span class="material-symbols-rounded">visibility</span>
                                            </a>
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
        
        <!-- Top servicios -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">star</span>Servicios más solicitados
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_services)): ?>
                        <p class="text-center text-muted my-5">No hay datos disponibles</p>
                    <?php else: ?>
                        <?php foreach ($top_services as $service): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <p class="mb-0 fw-medium"><?= htmlspecialchars($service['servicio_nombre']) ?></p>
                                    <p class="mb-0 text-muted small"><?= $service['total_citas'] ?> citas</p>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0 fw-bold"><?= number_format($service['total_facturado'], 2, ',', '.') ?> €</p>
                                </div>
                            </div>
                            <?php if ($service !== end($top_services)): ?>
                                <hr class="my-3">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cambiar de año automáticamente
    document.getElementById('yearSelect').addEventListener('change', function() {
        document.getElementById('yearForm').submit();
    });
    
    // Gráfico mensual
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const labels = <?= json_encode($chart_labels) ?>;
    const facturadoData = <?= json_encode($chart_facturado) ?>;
    const pagadoData = <?= json_encode($chart_pagado) ?>;
    const pendienteData = <?= json_encode($chart_pendiente) ?>;
    
    const monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Facturado',
                    data: facturadoData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Cobrado',
                    data: pagadoData,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pendiente',
                    data: pendienteData,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' €';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('es-ES', { 
                                    style: 'currency', 
                                    currency: 'EUR' 
                                }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 