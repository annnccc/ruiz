<?php
// Declaración explícita de codificación
header('Content-Type: text/html; charset=UTF-8');
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID de cita no proporcionado');
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos de la cita
try {
    $db = getDB();
    
    $query = "SELECT c.*, p.nombre, p.apellidos, p.telefono, p.email
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              WHERE c.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'Cita no encontrada');
        header('Location: list.php');
        exit;
    }
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener notas de sesión relacionadas con esta cita
    $query_notas = "SELECT * FROM notas_sesion WHERE cita_id = :cita_id ORDER BY fecha_creacion DESC";
    $stmt_notas = $db->prepare($query_notas);
    $stmt_notas->bindParam(':cita_id', $id, PDO::PARAM_INT);
    $stmt_notas->execute();
    $notas_sesion = $stmt_notas->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fecha para mostrar
    $fecha_formateada = date('d/m/Y', strtotime($cita['fecha']));
    
    // Determinar color según estado
    $estado_class = [
        'pendiente' => 'warning',
        'completada' => 'success',
        'cancelada' => 'danger'
    ];
    
    $color_estado = isset($estado_class[$cita['estado']]) ? $estado_class[$cita['estado']] : 'primary';
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener la cita: ' . $e->getMessage());
    header('Location: list.php');
    exit;
}

// Título de la página y breadcrumbs
$title = "Detalle de Cita";
$breadcrumbs = [
    'Citas' => 'list.php',
    'Detalle' => '#'
];

// CSS adicional
$extra_css = <<<EOT
<style>
    .nota-sesion {
        border-left: 4px solid #3a86ff;
        margin-bottom: 20px;
    }
    
    .nota-header {
        background-color: #f8f9fa;
        padding: 10px 15px;
        font-size: 0.9rem;
        color: #495057;
        border-bottom: 1px solid #eaecf0;
    }
    
    .nota-content {
        padding: 15px;
    }
</style>
EOT;

// JS adicional con TinyMCE
$extra_js = <<<EOT
<script src="<?= BASE_URL ?>/assets/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#notas_ocultas',
        height: 300,
        menubar: false,
        language: 'es',
        plugins: [
            'code', 'link', 'lists', 'table'
        ],
        toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
});
</script>
EOT;

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>Detalle de Cita</h1>
            <p class="text-muted small mb-0">Visualizando información completa de la cita</p>
        </div>
        <div>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-warning me-2">
                <span class="material-symbols-rounded me-2">edit</span>Editar
            </a>
            <?php if (!empty($cita['email'])): ?>
            <a href="../recordatorios/enviar_manual.php?cita_id=<?= $id ?>" class="btn btn-info me-2">
                <span class="material-symbols-rounded me-2">notifications</span>Enviar recordatorio
            </a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-outline-secondary">
                <span class="material-symbols-rounded me-2">arrow_back</span>Volver
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Detalles de la cita -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2 text-primary"></i>Información de la Cita
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Paciente</h6>
                            <h5><?= htmlspecialchars($cita['apellidos'] . ', ' . $cita['nombre']) ?></h5>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted mb-1">Estado</h6>
                            <span class="badge bg-<?= $color_estado ?> px-3 py-2">
                                <?= ucfirst(htmlspecialchars($cita['estado'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Fecha</h6>
                                <p class="mb-0 fs-5"><?= $fecha_formateada ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted mb-1">Horario</h6>
                                <p class="mb-0 fs-5"><?= htmlspecialchars($cita['hora_inicio']) ?> - <?= htmlspecialchars($cita['hora_fin']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Motivo de la consulta</h6>
                        <p><?= nl2br(htmlspecialchars($cita['motivo'])) ?></p>
                    </div>
                    
                    <?php if (!empty($cita['notas'])): ?>
                    <div class="mb-0">
                        <h6 class="text-muted mb-2">Notas adicionales</h6>
                        <div class="bg-light p-3 rounded">
                            <?= html_entity_decode($cita['notas']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2 text-primary"></i>Datos del Paciente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Nombre completo</h6>
                        <p><?= htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']) ?></p>
                    </div>
                    <?php if (!empty($cita['telefono'])): ?>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Teléfono</h6>
                        <p>
                            <a href="tel:<?= htmlspecialchars($cita['telefono']) ?>" class="text-decoration-none">
                                <i class="fas fa-phone me-2 text-primary"></i><?= htmlspecialchars($cita['telefono']) ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($cita['email'])): ?>
                    <div class="mb-0">
                        <h6 class="text-muted mb-1">Correo electrónico</h6>
                        <p class="mb-0">
                            <a href="mailto:<?= htmlspecialchars($cita['email']) ?>" class="text-decoration-none">
                                <i class="fas fa-envelope me-2 text-primary"></i><?= htmlspecialchars($cita['email']) ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="../pacientes/view.php?id=<?= $cita['paciente_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-user-circle me-2"></i>Ver perfil completo
                    </a>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2 text-primary"></i>Acciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($cita['estado'] === 'pendiente'): ?>
                        <button id="btn-completar" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Marcar como Completada
                        </button>
                        <button id="btn-cancelar" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cancelar Cita
                        </button>
                        <?php elseif ($cita['estado'] === 'cancelada'): ?>
                        <button id="btn-reactivar" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Reactivar Cita
                        </button>
                        <?php elseif ($cita['estado'] === 'completada'): ?>
                        <button id="btn-pendiente" class="btn btn-warning">
                            <i class="fas fa-undo me-2"></i>Marcar como Pendiente
                        </button>
                        <?php endif; ?>
                        <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-warning">
                            <i class="fas fa-edit me-2"></i>Editar Cita
                        </a>
                        <button id="btn-eliminar" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#eliminarModal">
                            <i class="fas fa-trash-alt me-2"></i>Eliminar Cita
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notas de sesión -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-list me-2 text-primary"></i>Notas de Sesión
                </h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#nuevaNotaForm">
                    <i class="fas fa-plus me-2"></i>Nueva Nota
                </button>
            </div>
            <div class="card-body">
                <!-- Formulario para nueva nota (colapsable) -->
                <div class="collapse mb-4" id="nuevaNotaForm">
                    <div class="card card-body border-0 bg-light">
                        <h6 class="mb-3">Añadir una nueva nota de sesión</h6>
                        <form action="save_nota.php" method="post">
                            <input type="hidden" name="cita_id" value="<?= $id ?>">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                            <div class="mb-3">
                                <label for="contenido_nota" class="form-label">Contenido <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="contenido_nota" name="contenido" rows="5" required></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="collapse" data-bs-target="#nuevaNotaForm">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Nota
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de notas existentes -->
                <?php if (empty($notas_sesion)): ?>
                <div class="text-center py-5">
                    <div class="mb-3 text-muted">
                        <i class="fas fa-clipboard fa-4x opacity-25"></i>
                    </div>
                    <h5>No hay notas de sesión</h5>
                    <p class="text-muted">Añade notas para registrar detalles importantes de esta cita</p>
                </div>
                <?php else: ?>
                <div class="notas-container">
                    <?php foreach ($notas_sesion as $nota): ?>
                    <div class="nota-sesion">
                        <div class="nota-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($nota['titulo']) ?></strong>
                                <small class="text-muted ms-2">
                                    <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($nota['fecha_creacion'])) ?>
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="edit_nota.php?id=<?= $nota['id'] ?>">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger btn-delete-nota" href="#" data-id="<?= $nota['id'] ?>">
                                            <i class="fas fa-trash-alt me-2"></i>Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="nota-content">
                            <?= $nota['contenido'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar esta cita? Esta acción no se puede deshacer.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta acción eliminará permanentemente la cita del sistema.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="delete.php?id=<?= $id ?>" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para cambiar el estado de la cita -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botón para marcar como completada
    const btnCompletar = document.getElementById('btn-completar');
    if (btnCompletar) {
        btnCompletar.addEventListener('click', function() {
            if (confirm('¿Marcar esta cita como completada?')) {
                window.location.href = 'change_status.php?id=<?= $id ?>&estado=completada';
            }
        });
    }
    
    // Botón para cancelar cita
    const btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que quieres cancelar esta cita?')) {
                window.location.href = 'change_status.php?id=<?= $id ?>&estado=cancelada';
            }
        });
    }
    
    // Botón para reactivar cita
    const btnReactivar = document.getElementById('btn-reactivar');
    if (btnReactivar) {
        btnReactivar.addEventListener('click', function() {
            if (confirm('¿Quieres reactivar esta cita como pendiente?')) {
                window.location.href = 'change_status.php?id=<?= $id ?>&estado=pendiente';
            }
        });
    }
    
    // Botón para marcar como pendiente
    const btnPendiente = document.getElementById('btn-pendiente');
    if (btnPendiente) {
        btnPendiente.addEventListener('click', function() {
            if (confirm('¿Marcar esta cita como pendiente nuevamente?')) {
                window.location.href = 'change_status.php?id=<?= $id ?>&estado=pendiente';
            }
        });
    }
    
    // Botones para eliminar notas
    const botonesEliminarNota = document.querySelectorAll('.btn-delete-nota');
    botonesEliminarNota.forEach(function(boton) {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            const notaId = this.getAttribute('data-id');
            if (confirm('¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.')) {
                window.location.href = 'delete_nota.php?id=' + notaId + '&cita_id=<?= $id ?>';
            }
        });
    });
});
</script>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// Incluir el layout principal
include ROOT_PATH . '/includes/layouts/main.php';
?>
