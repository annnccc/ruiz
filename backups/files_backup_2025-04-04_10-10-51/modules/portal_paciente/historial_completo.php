<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar que el paciente esté autenticado
if (!isset($_SESSION['paciente_id'])) {
    header('Location: ' . BASE_URL . '/modules/portal_paciente/login.php');
    exit;
}

// Obtener información del paciente
$pacienteId = $_SESSION['paciente_id'];
$pacienteNombre = $_SESSION['paciente_nombre'];
$pacienteApellidos = $_SESSION['paciente_apellidos'];

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';

try {
    $db = getDB();
    
    // Verificar si existe la tabla de historial_medico
    $stmt = $db->query("SHOW TABLES LIKE 'historial_medico'");
    if ($stmt->rowCount() === 0) {
        $mensaje = "El historial médico no está disponible en este momento.";
        $tipo_mensaje = "info";
    } else {
        // Obtener historial médico completo
        $query = "SELECT h.id, h.fecha, h.tipo, h.descripcion, h.diagnostico,
                 'Profesional' as medico_nombre, 'Asignado' as medico_apellidos
                 FROM historial_medico h
                 WHERE h.paciente_id = :paciente_id
                 ORDER BY h.fecha DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':paciente_id', $pacienteId, PDO::PARAM_INT);
        $stmt->execute();
        
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $mensaje = "Error al cargar el historial médico. Por favor, inténtelo de nuevo más tarde.";
    $tipo_mensaje = "danger";
    error_log("Error en historial_completo.php: " . $e->getMessage());
}

// Título de la página
$title = "Mi Historial Médico - Portal de Pacientes";

// Iniciar buffer de salida
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Cabecera con información de usuario -->
    <div class="card border-0 bg-gradient-primary text-white shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Mi Historial Médico</h1>
                    <p class="mb-0">Consulte su historial médico completo</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/modules/portal_paciente/index.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2 text-primary"></i>Historial Médico
                        </h5>
                        
                        <!-- Opciones de filtrado -->
                        <div class="d-flex gap-2">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filtroTipoDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-filter me-1"></i>Filtrar por tipo
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filtroTipoDropdown">
                                    <li><a class="dropdown-item filter-tipo active" href="#" data-tipo="todos">Todos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item filter-tipo" href="#" data-tipo="Consulta">Consultas</a></li>
                                    <li><a class="dropdown-item filter-tipo" href="#" data-tipo="Evaluación">Evaluaciones</a></li>
                                    <li><a class="dropdown-item filter-tipo" href="#" data-tipo="Terapia">Terapias</a></li>
                                    <li><a class="dropdown-item filter-tipo" href="#" data-tipo="Seguimiento">Seguimientos</a></li>
                                </ul>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filtroFechaDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-calendar-alt me-1"></i>Filtrar por fecha
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filtroFechaDropdown">
                                    <li><a class="dropdown-item filter-fecha active" href="#" data-periodo="todos">Todos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item filter-fecha" href="#" data-periodo="3">Últimos 3 meses</a></li>
                                    <li><a class="dropdown-item filter-fecha" href="#" data-periodo="6">Últimos 6 meses</a></li>
                                    <li><a class="dropdown-item filter-fecha" href="#" data-periodo="12">Último año</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historial)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard fa-4x text-muted mb-3"></i>
                        <h6 class="mb-2">No hay registros en su historial médico</h6>
                        <p class="text-muted">Actualmente no se han registrado entradas en su historial.</p>
                    </div>
                    <?php else: ?>
                    
                    <!-- Timeline de historial médico -->
                    <div class="historial-timeline p-4">
                        <?php 
                        $currentYear = null;
                        foreach ($historial as $index => $registro): 
                            $fechaRegistro = strtotime($registro['fecha']);
                            $year = date('Y', $fechaRegistro);
                            
                            // Mostrar separador de año si cambia
                            if ($year !== $currentYear) {
                                $currentYear = $year;
                                echo '<div class="timeline-year mb-4"><h5>' . $year . '</h5></div>';
                            }
                        ?>
                        <div class="timeline-item mb-4" 
                             data-tipo="<?= htmlspecialchars($registro['tipo']) ?>" 
                             data-fecha="<?= date('Y-m-d', $fechaRegistro) ?>">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary">
                                            <?= htmlspecialchars($registro['tipo']) ?>
                                        </h6>
                                        <span class="text-muted small">
                                            <?= date('d/m/Y', $fechaRegistro) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= nl2br(htmlspecialchars($registro['descripcion'])) ?></p>
                                    
                                    <?php if (!empty($registro['diagnostico'])): ?>
                                    <div class="bg-light p-3 rounded mb-3">
                                        <h6 class="mb-2">Diagnóstico:</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($registro['diagnostico'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-end text-muted small">
                                        <?php if (!empty($registro['medico_nombre'])): ?>
                                        <span>Dr. <?= htmlspecialchars($registro['medico_apellidos'] . ', ' . $registro['medico_nombre']) ?></span>
                                        <?php else: ?>
                                        <span>Registro médico</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filtro por tipo
        document.querySelectorAll('.filter-tipo').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                
                // Actualizar estado activo en el menú
                document.querySelectorAll('.filter-tipo').forEach(el => {
                    el.classList.remove('active');
                });
                event.target.classList.add('active');
                
                const tipo = event.target.getAttribute('data-tipo');
                filtrarHistorial();
            });
        });
        
        // Filtro por fecha
        document.querySelectorAll('.filter-fecha').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                
                // Actualizar estado activo en el menú
                document.querySelectorAll('.filter-fecha').forEach(el => {
                    el.classList.remove('active');
                });
                event.target.classList.add('active');
                
                const periodo = event.target.getAttribute('data-periodo');
                filtrarHistorial();
            });
        });
        
        // Función para aplicar filtros
        function filtrarHistorial() {
            const tipoSeleccionado = document.querySelector('.filter-tipo.active').getAttribute('data-tipo');
            const periodoSeleccionado = document.querySelector('.filter-fecha.active').getAttribute('data-periodo');
            
            // Calcular fecha límite para el filtro
            let fechaLimite = null;
            if (periodoSeleccionado !== 'todos') {
                const meses = parseInt(periodoSeleccionado);
                fechaLimite = new Date();
                fechaLimite.setMonth(fechaLimite.getMonth() - meses);
                fechaLimite = fechaLimite.toISOString().split('T')[0]; // Formato YYYY-MM-DD
            }
            
            // Aplicar filtros a los elementos
            document.querySelectorAll('.timeline-item').forEach(item => {
                const itemTipo = item.getAttribute('data-tipo');
                const itemFecha = item.getAttribute('data-fecha');
                
                const cumpleTipo = tipoSeleccionado === 'todos' || itemTipo.includes(tipoSeleccionado);
                const cumpleFecha = periodoSeleccionado === 'todos' || (fechaLimite && itemFecha >= fechaLimite);
                
                if (cumpleTipo && cumpleFecha) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Ocultar años vacíos
            document.querySelectorAll('.timeline-year').forEach(year => {
                const nextElement = year.nextElementSibling;
                
                // Verificar si hay algún elemento visible después del año hasta el siguiente año
                let hayElementosVisibles = false;
                let current = nextElement;
                
                while (current && !current.classList.contains('timeline-year')) {
                    if (current.style.display !== 'none' && current.classList.contains('timeline-item')) {
                        hayElementosVisibles = true;
                        break;
                    }
                    current = current.nextElementSibling;
                }
                
                year.style.display = hayElementosVisibles ? '' : 'none';
            });
        }
    });
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = <<<EOT
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }
    
    .timeline-year {
        position: relative;
        padding-left: 20px;
        margin-bottom: 20px;
        border-left: 3px solid #e9ecef;
    }
    
    .timeline-year h5 {
        background: #f8f9fa;
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
        margin-left: -10px;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-left: 20px;
    }
    
    .timeline-item:before {
        content: '';
        position: absolute;
        left: 0;
        top: 15px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: #007bff;
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #007bff;
    }
    
    .timeline-item:after {
        content: '';
        position: absolute;
        left: 7px;
        top: 30px;
        bottom: -15px;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item:last-child:after {
        display: none;
    }
    
    .dropdown-item.active {
        background-color: #007bff;
        color: white;
    }
</style>
EOT;

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal para portal de pacientes
include ROOT_PATH . '/includes/layouts/portal.php';
?> 