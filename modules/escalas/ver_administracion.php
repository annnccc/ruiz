<?php
/**
 * Módulo de Escalas Psicológicas - Ver Administración
 * Permite visualizar los resultados de una administración de escala completada
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si el usuario está autenticado antes de incluir el header
if (!isset($_SESSION['usuario_id'])) {
    error_log("No hay sesión de usuario activa en escalas/ver_administracion.php");
    setAlert('warning', 'Debes iniciar sesión para acceder a esta página');
    redirect(BASE_URL . '/login.php');
} else {
    error_log("Usuario autenticado en escalas/ver_administracion.php con ID: " . $_SESSION['usuario_id'] . ", Rol: " . ($_SESSION['usuario_rol'] ?? 'N/A'));
}

// Verificar que se ha especificado una administración
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Debe especificar una administración válida.";
    header('Location: index.php');
    exit;
}

$admin_id = $_GET['id'];

// Obtener información de la administración
$stmt = $db->prepare("
    SELECT a.*, e.nombre AS escala_nombre, e.descripcion, e.instrucciones,
           p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos, p.fecha_nacimiento
    FROM escalas_administraciones a
    JOIN escalas_catalogo e ON a.escala_id = e.id
    JOIN pacientes p ON a.paciente_id = p.id
    WHERE a.id = ?
");
$stmt->execute([$admin_id]);
$administracion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$administracion) {
    $_SESSION['error'] = "La administración especificada no existe.";
    header('Location: index.php');
    exit;
}

// Título de la página
$pageTitle = "Resultados: " . $administracion['escala_nombre'];

// Iniciar captura del contenido de la página
startPageContent();

// Obtener información de los resultados
$stmt = $db->prepare("
    SELECT * 
    FROM escalas_resultados
    WHERE administracion_id = ?
    ORDER BY CASE WHEN subescala = 'total' THEN 1 ELSE 0 END, subescala
");
$stmt->execute([$admin_id]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener respuestas a los ítems
$stmt = $db->prepare("
    SELECT r.*, i.texto, i.numero, i.tipo_respuesta, i.opciones_respuesta, i.inversion
    FROM escalas_respuestas r
    JOIN escalas_items i ON r.item_id = i.id
    WHERE r.administracion_id = ?
    ORDER BY i.numero
");
$stmt->execute([$admin_id]);
$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para obtener la etiqueta de respuesta según el tipo y valor
function obtener_etiqueta_respuesta($tipo, $valor, $opciones = null) {
    switch ($tipo) {
        case 'likert3':
            $opciones_likert3 = [
                0 => 'Nunca/Nada',
                1 => 'A veces/Algo',
                2 => 'Frecuentemente/Mucho'
            ];
            return isset($opciones_likert3[$valor]) ? $opciones_likert3[$valor] : $valor;
            
        case 'likert4':
            $opciones_likert4 = [
                0 => 'Nunca',
                1 => 'A veces',
                2 => 'Frecuentemente',
                3 => 'Siempre'
            ];
            return isset($opciones_likert4[$valor]) ? $opciones_likert4[$valor] : $valor;
            
        case 'likert5':
            $opciones_likert5 = [
                0 => 'Totalmente en desacuerdo',
                1 => 'En desacuerdo',
                2 => 'Neutral',
                3 => 'De acuerdo',
                4 => 'Totalmente de acuerdo'
            ];
            return isset($opciones_likert5[$valor]) ? $opciones_likert5[$valor] : $valor;
            
        case 'si_no':
            return $valor === 'si' ? 'Sí' : 'No';
            
        case 'seleccion_multiple':
            if ($opciones) {
                $lista_opciones = explode(',', $opciones);
                $indice = (int) $valor;
                return isset($lista_opciones[$indice]) ? trim($lista_opciones[$indice]) : $valor;
            }
            return $valor;
            
        default:
            return $valor;
    }
}

// Calcular edad del paciente
$edad = null;
if ($administracion['fecha_nacimiento']) {
    $fecha_nac = new DateTime($administracion['fecha_nacimiento']);
    $fecha_admin = new DateTime($administracion['fecha']);
    $edad = $fecha_nac->diff($fecha_admin)->y;
}
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <?= heroicon_outline('chart-bar', 'sidebar-icon me-2') ?>
            Resultados de la Escala
        </h1>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <?= heroicon_outline('arrow-left', 'me-2') ?> Volver
            </a>
            <a href="#" class="btn btn-outline-primary" onclick="window.print()">
                <?= heroicon_outline('printer', 'me-2') ?> Imprimir
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
            <li class="breadcrumb-item active">Ver Administración</li>
        </ol>
    </nav>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-clipboard-list me-1"></i>
                    <?= htmlspecialchars($administracion['escala_nombre']) ?>
                    
                    <div class="float-end">
                        <a href="index.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="#" class="btn btn-sm btn-light" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Paciente:</strong> <?= htmlspecialchars($administracion['paciente_nombre'] . ' ' . $administracion['paciente_apellidos']) ?>
                            <?php if ($edad): ?>
                                (<?= $edad ?> años)
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Fecha de aplicación:</strong> <?= date('d/m/Y H:i', strtotime($administracion['fecha'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Estado:</strong>
                            <?php if ($administracion['completada']): ?>
                                <span class="badge bg-success">Completada</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($administracion['motivo'])): ?>
                        <div class="mb-3">
                            <strong>Motivo:</strong> <?= htmlspecialchars($administracion['motivo']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($administracion['notas'])): ?>
                        <div class="mb-3">
                            <strong>Notas:</strong> <?= nl2br(htmlspecialchars($administracion['notas'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($resultados) && $administracion['completada']): ?>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-1"></i>
                        Resultados por Subescalas
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Subescala</th>
                                    <th>Puntuación directa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $resultado): ?>
                                    <tr <?= $resultado['subescala'] === 'total' ? 'class="table-primary fw-bold"' : '' ?>>
                                        <td><?= htmlspecialchars($resultado['subescala']) ?></td>
                                        <td><?= $resultado['puntuacion_directa'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-1"></i>
                        Visualización Gráfica
                    </div>
                    <div class="card-body">
                        <canvas id="resultados-grafico" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!$administracion['completada']): ?>
        <div class="alert alert-warning">
            <p>Esta administración aún no ha sido completada. <a href="completar_escala.php?id=<?= $admin_id ?>" class="btn btn-sm btn-primary ms-2">Completar ahora</a></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($respuestas) && $administracion['completada']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-list me-1"></i>
                Respuestas Detalladas
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ítem</th>
                            <th>Respuesta</th>
                            <th>Puntuación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($respuestas as $respuesta): ?>
                            <tr>
                                <td><?= $respuesta['numero'] ?></td>
                                <td><?= htmlspecialchars($respuesta['texto']) ?></td>
                                <td>
                                    <?= htmlspecialchars(obtener_etiqueta_respuesta(
                                        $respuesta['tipo_respuesta'], 
                                        $respuesta['respuesta'], 
                                        $respuesta['opciones_respuesta']
                                    )) ?>
                                </td>
                                <td>
                                    <?= $respuesta['puntuacion'] ?>
                                    <?php if ($respuesta['inversion']): ?>
                                        <span class="badge bg-warning text-dark">Invertida</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al listado
        </a>
        
        <?php if (!$administracion['completada']): ?>
            <a href="completar_escala.php?id=<?= $admin_id ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Completar administración
            </a>
        <?php endif; ?>
        
        <?php if ($administracion['completada']): ?>
            <a href="#" class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Imprimir resultados
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($resultados) && $administracion['completada']): ?>
<!-- Script para generar gráfica -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('resultados-grafico').getContext('2d');
    
    // Preparar datos
    const etiquetas = [];
    const datos = [];
    const colores = [];
    
    <?php 
    // Filtrar para no incluir el total en el gráfico
    $subescalas_grafico = array_filter($resultados, function($r) {
        return $r['subescala'] !== 'total';
    });
    
    if (empty($subescalas_grafico)) {
        $subescalas_grafico = $resultados;
    }
    ?>
    
    <?php foreach ($subescalas_grafico as $i => $resultado): ?>
    etiquetas.push('<?= addslashes($resultado['subescala']) ?>');
    datos.push(<?= $resultado['puntuacion_directa'] ?>);
    colores.push('rgba(54, 162, 235, 0.7)');
    <?php endforeach; ?>
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Puntuación directa',
                data: datos,
                backgroundColor: colores,
                borderColor: colores.map(c => c.replace('0.7', '1')),
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Resultados por subescalas'
                },
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 