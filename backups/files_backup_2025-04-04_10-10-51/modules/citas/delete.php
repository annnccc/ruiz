<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID de cita no proporcionado');
    redirect(BASE_URL . '/modules/citas/list.php');
}

$id = (int)$_GET['id'];

// Comprobar si la cita existe
$db = getDB();
$stmt = $db->prepare("SELECT c.*, p.nombre, p.apellidos 
            FROM citas c 
            JOIN pacientes p ON c.paciente_id = p.id 
            WHERE c.id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    setAlert('danger', 'Cita no encontrada');
    redirect(BASE_URL . '/modules/citas/list.php');
}

// Procesar la eliminación si se ha confirmado
if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
    try {
        $db->beginTransaction();
        
        // Eliminar la cita
        $stmt = $db->prepare("DELETE FROM citas WHERE id = :id");
        $stmt->bindValue(':id', $id);
        
        if ($stmt->execute()) {
            $db->commit();
            setAlert('success', 'Cita eliminada correctamente');
            redirect(BASE_URL . '/modules/citas/list.php');
        } else {
            $db->rollBack();
            setAlert('danger', 'Error al eliminar la cita');
            redirect(BASE_URL . '/modules/citas/view.php?id=' . $id);
        }
    } catch (Exception $e) {
        $db->rollBack();
        setAlert('danger', 'Error: ' . $e->getMessage());
        redirect(BASE_URL . '/modules/citas/view.php?id=' . $id);
    }
}

// Título y breadcrumbs para la página
$titulo_pagina = "Eliminar Cita";
$title = $titulo_pagina;
$breadcrumbs = [
    'Citas' => BASE_URL . '/modules/citas/list.php',
    'Detalles' => BASE_URL . '/modules/citas/view.php?id=' . $id,
    'Eliminar' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">delete_forever</span>Eliminar Cita
        </h1>
        <div>
            <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">
                <span class="material-symbols-rounded">arrow_back</span> Volver a Detalles
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Tarjeta de confirmación -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">warning</span>Confirmar Eliminación
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning">
                        <span class="material-symbols-rounded me-2">warning</span>
                        <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                    </div>
                    
                    <div class="text-center mb-4">
                        <span class="material-symbols-rounded fs-1 text-danger mb-3" style="font-size: 5rem !important;">event_busy</span>
                        <h4>¿Estás seguro de que deseas eliminar esta cita?</h4>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Paciente</h6>
                                    <p class="h5 mb-3"><?= htmlspecialchars($cita['apellidos'] . ', ' . $cita['nombre']) ?></p>
                                    
                                    <h6 class="text-muted mb-1">Fecha y Hora</h6>
                                    <p class="h5 mb-3">
                                        <?= formatDateToView($cita['fecha']) ?> 
                                        (<?= formatTime($cita['hora_inicio']) ?> - <?= formatTime($cita['hora_fin']) ?>)
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Motivo</h6>
                                    <p class="h5 mb-3"><?= htmlspecialchars($cita['motivo']) ?></p>
                                    
                                    <h6 class="text-muted mb-1">Estado</h6>
                                    <p class="h5">
                                        <span class="badge <?= getStatusClass($cita['estado']) ?>">
                                            <?= ucfirst($cita['estado']) ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <span class="material-symbols-rounded me-2">info</span>
                        <strong>Recomendación:</strong> Si la cita simplemente no se realizará, considera marcarla como <span class="badge bg-danger">cancelada</span> en lugar de eliminarla para mantener un registro completo.
                    </div>
                    
                    <form action="" method="POST" class="mt-4">
                        <input type="hidden" name="confirmar" value="si">
                        <div class="d-flex justify-content-center gap-3">
                            <a href="view.php?id=<?= $id ?>" class="btn btn-secondary btn-lg">
                                <span class="material-symbols-rounded">close</span> Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger btn-lg">
                                <span class="material-symbols-rounded">delete</span> Sí, Eliminar Cita
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 