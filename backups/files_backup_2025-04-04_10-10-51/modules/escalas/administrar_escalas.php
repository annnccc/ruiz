<?php
/**
 * Módulo de Escalas Psicológicas - Administración de Escalas
 * Permite gestionar el catálogo de escalas del sistema
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si el usuario está autenticado y es administrador antes de incluir el header
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta sección.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Administrar Escalas";

// Iniciar captura del contenido de la página
startPageContent();

// Procesar acciones
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Añadir nueva escala
    if ($action == 'add') {
        // Validar campos obligatorios
        if (empty($_POST['nombre'])) {
            $_SESSION['error'] = "El nombre de la escala es obligatorio.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO escalas_catalogo (nombre, descripcion, poblacion, instrucciones, tiempo_estimado, referencia_bibliografica) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['descripcion'] ?? '',
                    $_POST['poblacion'] ?? 'todos',
                    $_POST['instrucciones'] ?? '',
                    $_POST['tiempo_estimado'] ?? '',
                    $_POST['referencia_bibliografica'] ?? ''
                ]);
                
                $_SESSION['success'] = "Escala añadida correctamente.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al añadir la escala: " . $e->getMessage();
            }
        }
    }
    
    // Editar escala
    if ($action == 'edit' && isset($_POST['id'])) {
        // Validar campos obligatorios
        if (empty($_POST['nombre'])) {
            $_SESSION['error'] = "El nombre de la escala es obligatorio.";
        } else {
            try {
                $stmt = $db->prepare("UPDATE escalas_catalogo SET nombre = ?, descripcion = ?, poblacion = ?, instrucciones = ?, tiempo_estimado = ?, referencia_bibliografica = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['descripcion'] ?? '',
                    $_POST['poblacion'] ?? 'todos',
                    $_POST['instrucciones'] ?? '',
                    $_POST['tiempo_estimado'] ?? '',
                    $_POST['referencia_bibliografica'] ?? '',
                    $_POST['id']
                ]);
                
                $_SESSION['success'] = "Escala actualizada correctamente.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al actualizar la escala: " . $e->getMessage();
            }
        }
    }
    
    // Eliminar escala
    if ($action == 'delete' && isset($_POST['id'])) {
        try {
            // Verificar si hay administraciones con esta escala
            $stmt = $db->prepare("SELECT COUNT(*) FROM escalas_administraciones WHERE escala_id = ?");
            $stmt->execute([$_POST['id']]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['error'] = "No se puede eliminar esta escala porque tiene administraciones asociadas.";
            } else {
                $stmt = $db->prepare("DELETE FROM escalas_catalogo WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                
                $_SESSION['success'] = "Escala eliminada correctamente.";
            }
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar la escala: " . $e->getMessage();
        }
    }
}

// Obtener todas las escalas
$stmt = $db->query("SELECT * FROM escalas_catalogo ORDER BY nombre");
$escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar cuántas administraciones tiene cada escala
$administraciones_por_escala = [];
$stmt = $db->query("SELECT escala_id, COUNT(*) as total FROM escalas_administraciones GROUP BY escala_id");
$adminCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($adminCounts as $count) {
    $administraciones_por_escala[$count['escala_id']] = $count['total'];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">settings</span>
        Administrar Escalas
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Administrar Escalas</li>
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
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-clipboard-list me-1"></i>
                    Escalas Disponibles
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEscalaModal">
                    <span class="material-symbols-rounded me-1">add</span> Nueva Escala
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($escalas)): ?>
                <p class="text-muted">No hay escalas disponibles en el sistema.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Población</th>
                                <th>Tiempo Estimado</th>
                                <th>Administraciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escalas as $escala): ?>
                                <tr>
                                    <td><?= $escala['id'] ?></td>
                                    <td><?= htmlspecialchars($escala['nombre']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($escala['poblacion'])) ?></td>
                                    <td><?= htmlspecialchars($escala['tiempo_estimado']) ?></td>
                                    <td><?= isset($administraciones_por_escala[$escala['id']]) ? $administraciones_por_escala[$escala['id']] : 0 ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-escala-btn" data-id="<?= $escala['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewEscalaModal" 
                                            data-nombre="<?= htmlspecialchars($escala['nombre']) ?>"
                                            data-descripcion="<?= htmlspecialchars($escala['descripcion']) ?>"
                                            data-poblacion="<?= htmlspecialchars($escala['poblacion']) ?>"
                                            data-instrucciones="<?= htmlspecialchars($escala['instrucciones']) ?>"
                                            data-tiempo="<?= htmlspecialchars($escala['tiempo_estimado']) ?>"
                                            data-referencia="<?= htmlspecialchars($escala['referencia_bibliografica']) ?>">
                                            <span class="material-symbols-rounded">visibility</span>
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-primary edit-escala-btn" data-id="<?= $escala['id'] ?>" data-bs-toggle="modal" data-bs-target="#editEscalaModal"
                                            data-nombre="<?= htmlspecialchars($escala['nombre']) ?>"
                                            data-descripcion="<?= htmlspecialchars($escala['descripcion']) ?>"
                                            data-poblacion="<?= htmlspecialchars($escala['poblacion']) ?>"
                                            data-instrucciones="<?= htmlspecialchars($escala['instrucciones']) ?>"
                                            data-tiempo="<?= htmlspecialchars($escala['tiempo_estimado']) ?>"
                                            data-referencia="<?= htmlspecialchars($escala['referencia_bibliografica']) ?>">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                        
                                        <a href="administrar_items.php?escala_id=<?= $escala['id'] ?>" class="btn btn-sm btn-secondary">
                                            <span class="material-symbols-rounded">format_list_bulleted</span>
                                        </a>
                                        
                                        <?php if (!isset($administraciones_por_escala[$escala['id']]) || $administraciones_por_escala[$escala['id']] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-escala-btn" data-id="<?= $escala['id'] ?>" data-nombre="<?= htmlspecialchars($escala['nombre']) ?>" data-bs-toggle="modal" data-bs-target="#deleteEscalaModal">
                                                <span class="material-symbols-rounded">delete</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para añadir escala -->
<div class="modal fade" id="addEscalaModal" tabindex="-1" aria-labelledby="addEscalaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEscalaModalLabel">Nueva Escala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="addEscalaForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="poblacion" class="form-label">Población</label>
                        <select class="form-select" id="poblacion" name="poblacion">
                            <option value="todos">Todos</option>
                            <option value="adultos">Adultos</option>
                            <option value="adolescentes">Adolescentes</option>
                            <option value="niños">Niños</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="instrucciones" class="form-label">Instrucciones</label>
                        <textarea class="form-control" id="instrucciones" name="instrucciones" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tiempo_estimado" class="form-label">Tiempo Estimado</label>
                        <input type="text" class="form-control" id="tiempo_estimado" name="tiempo_estimado" placeholder="Ej: 10-15 minutos">
                    </div>
                    
                    <div class="mb-3">
                        <label for="referencia_bibliografica" class="form-label">Referencia Bibliográfica</label>
                        <textarea class="form-control" id="referencia_bibliografica" name="referencia_bibliografica" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="addEscalaForm" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver escala -->
<div class="modal fade" id="viewEscalaModal" tabindex="-1" aria-labelledby="viewEscalaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEscalaModalLabel">Detalles de la Escala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h5>Nombre</h5>
                    <p id="view_nombre"></p>
                </div>
                
                <div class="mb-3">
                    <h5>Descripción</h5>
                    <p id="view_descripcion" class="text-muted"></p>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Población</h5>
                        <p id="view_poblacion"></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Tiempo Estimado</h5>
                        <p id="view_tiempo"></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h5>Instrucciones</h5>
                    <p id="view_instrucciones" class="text-muted"></p>
                </div>
                
                <div class="mb-3">
                    <h5>Referencia Bibliográfica</h5>
                    <p id="view_referencia" class="text-muted"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar escala -->
<div class="modal fade" id="editEscalaModal" tabindex="-1" aria-labelledby="editEscalaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEscalaModalLabel">Editar Escala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="editEscalaForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_poblacion" class="form-label">Población</label>
                        <select class="form-select" id="edit_poblacion" name="poblacion">
                            <option value="todos">Todos</option>
                            <option value="adultos">Adultos</option>
                            <option value="adolescentes">Adolescentes</option>
                            <option value="niños">Niños</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_instrucciones" class="form-label">Instrucciones</label>
                        <textarea class="form-control" id="edit_instrucciones" name="instrucciones" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_tiempo_estimado" class="form-label">Tiempo Estimado</label>
                        <input type="text" class="form-control" id="edit_tiempo_estimado" name="tiempo_estimado">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_referencia_bibliografica" class="form-label">Referencia Bibliográfica</label>
                        <textarea class="form-control" id="edit_referencia_bibliografica" name="referencia_bibliografica" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editEscalaForm" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar escala -->
<div class="modal fade" id="deleteEscalaModal" tabindex="-1" aria-labelledby="deleteEscalaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEscalaModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar la escala <strong id="delete_nombre"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
                
                <form id="deleteEscalaForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="deleteEscalaForm" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Evento para ver detalles de la escala
document.querySelectorAll('.view-escala-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const nombre = this.getAttribute('data-nombre');
        const descripcion = this.getAttribute('data-descripcion');
        const poblacion = this.getAttribute('data-poblacion');
        const instrucciones = this.getAttribute('data-instrucciones');
        const tiempo = this.getAttribute('data-tiempo');
        const referencia = this.getAttribute('data-referencia');
        
        document.getElementById('view_nombre').textContent = nombre;
        document.getElementById('view_descripcion').textContent = descripcion || 'No disponible';
        document.getElementById('view_poblacion').textContent = poblacion.charAt(0).toUpperCase() + poblacion.slice(1);
        document.getElementById('view_tiempo').textContent = tiempo || 'No especificado';
        document.getElementById('view_instrucciones').textContent = instrucciones || 'No disponible';
        document.getElementById('view_referencia').textContent = referencia || 'No disponible';
    });
});

// Evento para editar escala
document.querySelectorAll('.edit-escala-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const nombre = this.getAttribute('data-nombre');
        const descripcion = this.getAttribute('data-descripcion');
        const poblacion = this.getAttribute('data-poblacion');
        const instrucciones = this.getAttribute('data-instrucciones');
        const tiempo = this.getAttribute('data-tiempo');
        const referencia = this.getAttribute('data-referencia');
        
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_descripcion').value = descripcion;
        document.getElementById('edit_poblacion').value = poblacion;
        document.getElementById('edit_instrucciones').value = instrucciones;
        document.getElementById('edit_tiempo_estimado').value = tiempo;
        document.getElementById('edit_referencia_bibliografica').value = referencia;
    });
});

// Evento para eliminar escala
document.querySelectorAll('.delete-escala-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const nombre = this.getAttribute('data-nombre');
        
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_nombre').textContent = nombre;
    });
});
</script>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 