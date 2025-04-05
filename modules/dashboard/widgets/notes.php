<?php
/**
 * Widget de notas personales para el dashboard
 * Permite al usuario crear, editar y eliminar notas personales
 */

// Evitar acceso directo
if (!defined('BASE_URL')) {
    exit('Acceso directo no permitido');
}

// Obtener ID de usuario (ya está disponible en el contexto del dashboard)
$usuario_id = $_SESSION['usuario_id'];

// Verificar si es una petición AJAX para obtener notas
if (isset($_GET['action']) && $_GET['action'] === 'get_notes') {
    outputNotesList($usuario_id);
    return;
}

// Verificar si la tabla existe o crearla
$db = getDB();
$stmt = $db->prepare("SHOW TABLES LIKE 'dashboard_notas'");
$stmt->execute();

if ($stmt->rowCount() == 0) {
    $db->exec("CREATE TABLE IF NOT EXISTS `dashboard_notas` (
        `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `usuario_id` int(11) NOT NULL,
        `contenido` text NOT NULL,
        `color` varchar(20) DEFAULT 'primary',
        `creado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `actualizado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_usuario_id` (`usuario_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

?>
<div class="card h-100" id="dashboardNotes">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="material-symbols-rounded me-2 align-middle">note</i>
            Notas personales
        </h5>
        <button type="button" class="btn btn-sm btn-primary" id="addNoteBtn">
            <i class="material-symbols-rounded me-1 align-middle">add</i>
            Nueva nota
        </button>
    </div>
    <div class="card-body">
        <div class="notes-list">
            <?php outputNotesList($usuario_id); ?>
        </div>
    </div>
</div>

<!-- Modal para crear/editar notas -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="noteModalLabel">Nueva nota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="noteForm">
                <div class="modal-body">
                    <input type="hidden" id="noteId" name="id">
                    
                    <div class="mb-3">
                        <label for="noteContent" class="form-label">Contenido</label>
                        <textarea class="form-control" id="noteContent" name="contenido" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="hidden" id="noteColor" name="color" value="primary">
                        <div class="color-selector d-flex gap-2">
                            <button type="button" class="color-btn btn btn-primary active" data-color="primary"></button>
                            <button type="button" class="color-btn btn btn-success" data-color="success"></button>
                            <button type="button" class="color-btn btn btn-danger" data-color="danger"></button>
                            <button type="button" class="color-btn btn btn-warning" data-color="warning"></button>
                            <button type="button" class="color-btn btn btn-info" data-color="info"></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Muestra la lista de notas del usuario
 */
function outputNotesList($usuario_id) {
    $db = getDB();
    
    // Obtener las notas del usuario
    $stmt = $db->prepare("SELECT * FROM dashboard_notas WHERE usuario_id = :usuario_id ORDER BY actualizado DESC");
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notas)) {
        echo '<div class="text-center p-3 text-muted">No hay notas. Añade una nueva para empezar.</div>';
        return;
    }
    
    // Mostrar cada nota
    foreach ($notas as $nota) {
        $color = htmlspecialchars($nota['color']);
        $id = (int)$nota['id'];
        $contenido = htmlspecialchars($nota['contenido']);
        $fecha = date('d/m/Y H:i', strtotime($nota['actualizado']));
        
        echo <<<HTML
        <div class="note-item mb-3 p-3 border-start border-4 border-{$color} rounded shadow-sm" data-color="{$color}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="note-content">{$contenido}</div>
                <div class="dropdown ms-2">
                    <button class="btn btn-sm text-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="material-symbols-rounded">more_vert</i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button class="dropdown-item edit-note" type="button" data-id="{$id}">
                                <i class="material-symbols-rounded me-2 align-middle">edit</i>
                                Editar
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item text-danger delete-note" type="button" data-id="{$id}">
                                <i class="material-symbols-rounded me-2 align-middle">delete</i>
                                Eliminar
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="text-muted small">
                <i class="material-symbols-rounded me-1 align-middle" style="font-size: 1rem;">schedule</i>
                {$fecha}
            </div>
        </div>
        HTML;
    }
} 