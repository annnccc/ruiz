<?php
/**
 * Módulo de Escalas Psicológicas - Información de Escala
 * Muestra información detallada sobre una escala psicológica
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Verificar que se ha especificado una escala
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Debe especificar una escala válida.";
    header('Location: index.php');
    exit;
}

$escala_id = $_GET['id'];

// Obtener información de la escala
$stmt = $db->prepare("SELECT * FROM escalas_catalogo WHERE id = ?");
$stmt->execute([$escala_id]);
$escala = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$escala) {
    $_SESSION['error'] = "La escala especificada no existe.";
    header('Location: index.php');
    exit;
}

// Obtener los ítems de la escala
$stmt = $db->prepare("
    SELECT * 
    FROM escalas_items 
    WHERE escala_id = ? 
    ORDER BY numero
");
$stmt->execute([$escala_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar administraciones de esta escala
$stmt = $db->prepare("
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN completada = 1 THEN 1 ELSE 0 END) as completadas
    FROM escalas_administraciones 
    WHERE escala_id = ?
");
$stmt->execute([$escala_id]);
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Título de la página
$pageTitle = "Información de Escala: " . $escala['nombre'];

// Iniciar captura del contenido de la página
startPageContent();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">psychology</span>
        Información de Escala
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($escala['nombre']) ?></li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Acciones rápidas -->
    <div class="mb-4">
        <a href="nueva_administracion.php?escala_id=<?= $escala['id'] ?>" class="btn btn-primary">
            <span class="material-symbols-rounded me-1">assignment</span> Aplicar Escala
        </a>
        
        <?php if ($_SESSION['usuario_rol'] == 'admin'): ?>
        <a href="administrar_items.php?escala_id=<?= $escala['id'] ?>" class="btn btn-secondary">
            <span class="material-symbols-rounded me-1">settings</span> Administrar Ítems
        </a>
        <?php endif; ?>
        
        <a href="index.php" class="btn btn-outline-secondary">
            <span class="material-symbols-rounded me-1">arrow_back</span> Volver
        </a>
    </div>
    
    <div class="row">
        <!-- Información general de la escala -->
        <div class="col-lg-8">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">info</span>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Nombre:</div>
                        <div class="col-md-9"><?= htmlspecialchars($escala['nombre']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Descripción:</div>
                        <div class="col-md-9"><?= nl2br(htmlspecialchars($escala['descripcion'] ?? 'No disponible')) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Población:</div>
                        <div class="col-md-9"><?= ucfirst(htmlspecialchars($escala['poblacion'])) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Tiempo estimado:</div>
                        <div class="col-md-9"><?= htmlspecialchars($escala['tiempo_estimado'] ?? 'No especificado') ?></div>
                    </div>
                    
                    <?php if (!empty($escala['referencia_bibliografica'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Referencia bibliográfica:</div>
                        <div class="col-md-9"><?= nl2br(htmlspecialchars($escala['referencia_bibliografica'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($escala['instrucciones'])): ?>
            <!-- Instrucciones de aplicación -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">description</span>
                        Instrucciones de Aplicación
                    </h5>
                </div>
                <div class="card-body">
                    <?= nl2br(htmlspecialchars($escala['instrucciones'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Ítems de la escala -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">checklist</span>
                        Ítems de la Escala
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p class="text-muted">Esta escala no tiene ítems configurados.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="60">Nº</th>
                                        <th>Texto</th>
                                        <th>Tipo de Respuesta</th>
                                        <th>Subescala</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= $item['numero'] ?></td>
                                        <td><?= htmlspecialchars($item['texto']) ?></td>
                                        <td>
                                            <?php
                                            $tipos = [
                                                'likert3' => 'Likert 3 niveles',
                                                'likert4' => 'Likert 4 niveles',
                                                'likert5' => 'Likert 5 niveles',
                                                'si_no' => 'Sí/No',
                                                'numerica' => 'Numérica',
                                                'seleccion_multiple' => 'Selección múltiple'
                                            ];
                                            echo isset($tipos[$item['tipo_respuesta']]) ? $tipos[$item['tipo_respuesta']] : $item['tipo_respuesta'];
                                            
                                            if ($item['tipo_respuesta'] == 'seleccion_multiple' && !empty($item['opciones_respuesta'])) {
                                                echo "<br><small>Opciones: " . htmlspecialchars($item['opciones_respuesta']) . "</small>";
                                            }
                                            
                                            if ($item['inversion']) {
                                                echo "<br><small class='text-danger'>Ítem invertido</small>";
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['subescala'] ?? 'Principal') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas y acciones -->
        <div class="col-lg-4">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">analytics</span>
                        Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="text-center px-3">
                            <h3 class="mb-0"><?= $estadisticas['total'] ?? 0 ?></h3>
                            <div class="text-muted">Total administraciones</div>
                        </div>
                        <div class="text-center px-3">
                            <h3 class="mb-0"><?= $estadisticas['completadas'] ?? 0 ?></h3>
                            <div class="text-muted">Completadas</div>
                        </div>
                        <div class="text-center px-3">
                            <h3 class="mb-0"><?= (isset($estadisticas['total']) && isset($estadisticas['completadas'])) ? ($estadisticas['total'] - $estadisticas['completadas']) : 0 ?></h3>
                            <div class="text-muted">Pendientes</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2 mt-3">
                        <a href="nueva_administracion.php?escala_id=<?= $escala['id'] ?>" class="btn btn-primary">
                            <span class="material-symbols-rounded me-1">assignment</span> Aplicar Escala
                        </a>
                        
                        <?php if ($_SESSION['usuario_rol'] == 'admin'): ?>
                        <a href="administrar_items.php?escala_id=<?= $escala['id'] ?>" class="btn btn-secondary">
                            <span class="material-symbols-rounded me-1">settings</span> Administrar Ítems
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Información de uso -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">help_outline</span>
                        Información de Uso
                    </h5>
                </div>
                <div class="card-body">
                    <p>Para aplicar esta escala:</p>
                    <ol>
                        <li>Haga clic en "Aplicar Escala"</li>
                        <li>Seleccione el paciente</li>
                        <li>Complete la información requerida</li>
                        <li>Las respuestas se almacenarán automáticamente</li>
                        <li>Al finalizar, verá los resultados completos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 