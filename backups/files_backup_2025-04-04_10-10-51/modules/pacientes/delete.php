<?php
require_once '../../includes/config.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID de paciente no proporcionado');
    redirect('list.php');
}

$id = (int)$_GET['id'];

try {
    // Obtener conexión a la base de datos
    $db = getDB();
    
    // Comprobar si el paciente existe
    $query = "SELECT id, nombre, apellidos FROM pacientes WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paciente) {
        setAlert('danger', 'Paciente no encontrado');
        redirect('list.php');
    }

    // Verificar si hay citas asociadas
    $query = "SELECT COUNT(*) as total FROM citas WHERE paciente_id = :paciente_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':paciente_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $citas_count = $result['total'];

    // Procesar la eliminación si se ha confirmado
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
        try {
            $db->beginTransaction();
            
            // Si hay citas, eliminarlas primero
            if ($citas_count > 0) {
                $query = "DELETE FROM citas WHERE paciente_id = :paciente_id";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':paciente_id', $id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            // Eliminar el paciente
            $query = "DELETE FROM pacientes WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $db->commit();
                setAlert('success', 'Paciente eliminado correctamente');
                redirect('list.php');
            } else {
                $db->rollBack();
                setAlert('danger', 'Error al eliminar el paciente');
                redirect('view.php?id=' . $id);
            }
        } catch (Exception $e) {
            $db->rollBack();
            setAlert('danger', 'Error: ' . $e->getMessage());
            redirect('view.php?id=' . $id);
        }
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error: ' . $e->getMessage());
    redirect('list.php');
}

// Título y breadcrumbs para la página
$titulo_pagina = "Eliminar Paciente";
$breadcrumbs = [
    'Pacientes' => BASE_URL . '/modules/pacientes/list.php',
    $paciente['nombre'] . ' ' . $paciente['apellidos'] => BASE_URL . '/modules/pacientes/view.php?id=' . $paciente['id'],
    'Eliminar' => '#'
];

include '../../includes/layout_header.php';
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">person_remove</span><?= $titulo_pagina ?>
        </h1>
        <div>
            <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">
                <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Detalles
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Tarjeta de confirmación -->
    <div class="card border-danger shadow-sm">
        <div class="card-header bg-danger text-white py-3">
            <h5 class="mb-0"><span class="material-symbols-rounded me-2">warning</span>Confirmar Eliminación</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <span class="material-symbols-rounded me-2">error</span>
                <strong>Advertencia:</strong> Esta acción no se puede deshacer.
            </div>
            
            <p>¿Estás seguro de que deseas eliminar al paciente <strong><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></strong>?</p>
            
            <?php if ($citas_count > 0): ?>
            <div class="alert alert-danger">
                <span class="material-symbols-rounded me-2">event_busy</span>
                <strong>Importante:</strong> Este paciente tiene <?= $citas_count ?> cita(s) asociada(s) que también se eliminarán.
            </div>
            <?php endif; ?>
            
            <form action="" method="POST" class="mt-4">
                <input type="hidden" name="confirmar" value="si">
                <div class="d-flex justify-content-center gap-3">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">
                        <span class="material-symbols-rounded me-2">cancel</span>Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <span class="material-symbols-rounded me-2">delete</span>Sí, Eliminar Paciente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/layout_footer.php'; ?> 