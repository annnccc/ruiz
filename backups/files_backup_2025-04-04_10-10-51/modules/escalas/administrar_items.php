<?php
/**
 * Módulo de Escalas Psicológicas - Administración de Ítems
 * Permite gestionar los ítems de una escala específica
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

// Verificar que se ha especificado una escala
if (!isset($_GET['escala_id']) || !is_numeric($_GET['escala_id'])) {
    $_SESSION['error'] = "Debe especificar una escala válida.";
    header('Location: administrar_escalas.php');
    exit;
}

// Título de la página
$pageTitle = "Administrar Ítems";

// Iniciar captura del contenido de la página
startPageContent();

$escala_id = $_GET['escala_id'];

// Obtener información de la escala
$stmt = $db->prepare("SELECT * FROM escalas_catalogo WHERE id = ?");
$stmt->execute([$escala_id]);
$escala = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$escala) {
    $_SESSION['error'] = "La escala especificada no existe.";
    header('Location: administrar_escalas.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Añadir nuevo ítem
    if ($action === 'add') {
        if (empty($_POST['texto'])) {
            $_SESSION['error'] = "El texto del ítem es obligatorio.";
        } else {
            try {
                // Obtener el próximo número de ítem
                $stmt = $db->prepare("SELECT MAX(numero) FROM escalas_items WHERE escala_id = ?");
                $stmt->execute([$escala_id]);
                $nextNum = ($stmt->fetchColumn() ?: 0) + 1;
                
                $stmt = $db->prepare("INSERT INTO escalas_items 
                    (escala_id, numero, texto, tipo_respuesta, opciones_respuesta, inversion, subescala) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $escala_id,
                    $nextNum,
                    $_POST['texto'],
                    $_POST['tipo_respuesta'],
                    $_POST['opciones_respuesta'] ?? null,
                    isset($_POST['inversion']) ? 1 : 0,
                    $_POST['subescala'] ?? null
                ]);
                
                $_SESSION['success'] = "Ítem añadido correctamente.";
                header('Location: ' . $_SERVER['PHP_SELF'] . '?escala_id=' . $escala_id);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al añadir el ítem: " . $e->getMessage();
            }
        }
    }
    
    // Editar ítem
    if ($action === 'edit') {
        if (empty($_POST['texto']) || empty($_POST['id'])) {
            $_SESSION['error'] = "El texto del ítem es obligatorio.";
        } else {
            try {
                $stmt = $db->prepare("UPDATE escalas_items SET 
                    texto = ?, 
                    tipo_respuesta = ?, 
                    opciones_respuesta = ?, 
                    inversion = ?, 
                    subescala = ?
                    WHERE id = ? AND escala_id = ?");
                
                $stmt->execute([
                    $_POST['texto'],
                    $_POST['tipo_respuesta'],
                    $_POST['opciones_respuesta'] ?? null,
                    isset($_POST['inversion']) ? 1 : 0,
                    $_POST['subescala'] ?? null,
                    $_POST['id'],
                    $escala_id
                ]);
                
                $_SESSION['success'] = "Ítem actualizado correctamente.";
                header('Location: ' . $_SERVER['PHP_SELF'] . '?escala_id=' . $escala_id);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al actualizar el ítem: " . $e->getMessage();
            }
        }
    }
    
    // Eliminar ítem
    if ($action === 'delete') {
        if (empty($_POST['id'])) {
            $_SESSION['error'] = "ID de ítem no especificado.";
        } else {
            try {
                // Verificar si hay respuestas asociadas a este ítem
                $stmt = $db->prepare("SELECT COUNT(*) FROM escalas_respuestas WHERE item_id = ?");
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['error'] = "No se puede eliminar este ítem porque tiene respuestas asociadas.";
                } else {
                    $stmt = $db->prepare("DELETE FROM escalas_items WHERE id = ? AND escala_id = ?");
                    $stmt->execute([$_POST['id'], $escala_id]);
                    
                    // Reordenar los números de ítems
                    $stmt = $db->prepare("SELECT id FROM escalas_items WHERE escala_id = ? ORDER BY numero");
                    $stmt->execute([$escala_id]);
                    $items = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($items as $index => $id) {
                        $db->prepare("UPDATE escalas_items SET numero = ? WHERE id = ?")
                           ->execute([$index + 1, $id]);
                    }
                    
                    $_SESSION['success'] = "Ítem eliminado correctamente.";
                }
                
                header('Location: ' . $_SERVER['PHP_SELF'] . '?escala_id=' . $escala_id);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al eliminar el ítem: " . $e->getMessage();
            }
        }
    }
}

// Obtener ítems de la escala
$stmt = $db->prepare("SELECT * FROM escalas_items WHERE escala_id = ? ORDER BY numero");
$stmt->execute([$escala_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">list</span>
        Administrar Ítems
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item"><a href="administrar_escalas.php">Administrar Escalas</a></li>
        <li class="breadcrumb-item active">Ítems: <?= htmlspecialchars($escala['nombre']) ?></li>
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
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-clipboard-list me-1"></i>
                    <?= htmlspecialchars($escala['nombre']) ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <p><?= htmlspecialchars($escala['descripcion']) ?></p>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Población:</strong> <?= ucfirst(htmlspecialchars($escala['poblacion'])) ?>
                </div>
                <div class="col-md-6">
                    <strong>Tiempo Estimado:</strong> <?= htmlspecialchars($escala['tiempo_estimado']) ?>
                </div>
            </div>
            
            <?php if (!empty($escala['instrucciones'])): ?>
                <div class="mb-3">
                    <strong>Instrucciones:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($escala['instrucciones'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-list me-1"></i>
                    Ítems de la Escala
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <span class="material-symbols-rounded me-1">add</span> Nuevo Ítem
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <p class="text-muted">No hay ítems definidos para esta escala.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 60px">Nº</th>
                                <th>Texto</th>
                                <th style="width: 150px">Tipo de Respuesta</th>
                                <th style="width: 100px">Subescala</th>
                                <th style="width: 90px">Invertido</th>
                                <th style="width: 150px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="text-center"><?= $item['numero'] ?></td>
                                    <td><?= htmlspecialchars($item['texto']) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($item['tipo_respuesta']))) ?></td>
                                    <td><?= htmlspecialchars($item['subescala'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if ($item['inversion']): ?>
                                            <span class="badge bg-info">Sí</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-item-btn" 
                                            data-id="<?= $item['id'] ?>" 
                                            data-texto="<?= htmlspecialchars($item['texto']) ?>"
                                            data-tipo="<?= htmlspecialchars($item['tipo_respuesta']) ?>"
                                            data-opciones="<?= htmlspecialchars($item['opciones_respuesta'] ?? '') ?>"
                                            data-inversion="<?= $item['inversion'] ?>"
                                            data-subescala="<?= htmlspecialchars($item['subescala'] ?? '') ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editItemModal">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-danger delete-item-btn" 
                                            data-id="<?= $item['id'] ?>" 
                                            data-numero="<?= $item['numero'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteItemModal">
                                            <span class="material-symbols-rounded">delete</span>
                                        </button>
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

<!-- Modal para añadir ítem -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Nuevo Ítem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?escala_id=<?= $escala_id ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="texto" class="form-label">Texto del Ítem <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="texto" name="texto" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo_respuesta" class="form-label">Tipo de Respuesta <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo_respuesta" name="tipo_respuesta" required>
                            <option value="likert3">Likert 3 niveles (0-1-2)</option>
                            <option value="likert4">Likert 4 niveles (0-1-2-3)</option>
                            <option value="likert5">Likert 5 niveles (0-1-2-3-4)</option>
                            <option value="si_no">Sí/No</option>
                            <option value="numerica">Numérica</option>
                            <option value="seleccion_multiple">Selección Múltiple</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="opcionesRespuestaDiv">
                        <label for="opciones_respuesta" class="form-label">Opciones de Respuesta</label>
                        <textarea class="form-control" id="opciones_respuesta" name="opciones_respuesta" rows="3" placeholder="Para respuestas de selección múltiple, ingrese las opciones separadas por comas"></textarea>
                        <small class="form-text text-muted">Para Selección Múltiple, ingrese las opciones separadas por comas (ej: "Nunca, A veces, Frecuentemente, Siempre").</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subescala" class="form-label">Subescala (opcional)</label>
                        <input type="text" class="form-control" id="subescala" name="subescala" placeholder="Ej: Depresión, Ansiedad, etc.">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="inversion" name="inversion">
                        <label class="form-check-label" for="inversion">
                            Puntuación invertida
                        </label>
                        <small class="form-text text-muted d-block">Marque esta opción si la puntuación de este ítem debe invertirse para el cálculo total.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="addItemForm" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar ítem -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Editar Ítem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?escala_id=<?= $escala_id ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_texto" class="form-label">Texto del Ítem <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_texto" name="texto" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_tipo_respuesta" class="form-label">Tipo de Respuesta <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_tipo_respuesta" name="tipo_respuesta" required>
                            <option value="likert3">Likert 3 niveles (0-1-2)</option>
                            <option value="likert4">Likert 4 niveles (0-1-2-3)</option>
                            <option value="likert5">Likert 5 niveles (0-1-2-3-4)</option>
                            <option value="si_no">Sí/No</option>
                            <option value="numerica">Numérica</option>
                            <option value="seleccion_multiple">Selección Múltiple</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="editOpcionesRespuestaDiv">
                        <label for="edit_opciones_respuesta" class="form-label">Opciones de Respuesta</label>
                        <textarea class="form-control" id="edit_opciones_respuesta" name="opciones_respuesta" rows="3" placeholder="Para respuestas de selección múltiple, ingrese las opciones separadas por comas"></textarea>
                        <small class="form-text text-muted">Para Selección Múltiple, ingrese las opciones separadas por comas.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_subescala" class="form-label">Subescala (opcional)</label>
                        <input type="text" class="form-control" id="edit_subescala" name="subescala">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_inversion" name="inversion">
                        <label class="form-check-label" for="edit_inversion">
                            Puntuación invertida
                        </label>
                        <small class="form-text text-muted d-block">Marque esta opción si la puntuación de este ítem debe invertirse para el cálculo total.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editItemForm" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar ítem -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteItemModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el ítem número <strong id="delete_numero"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
                
                <form id="deleteItemForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?escala_id=<?= $escala_id ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="deleteItemForm" class="btn btn-danger">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Funcionalidad para mostrar/ocultar opciones de respuesta según el tipo seleccionado
function toggleOpcionesRespuesta(selectId, divId) {
    const tipoRespuesta = document.getElementById(selectId).value;
    const opcionesDiv = document.getElementById(divId);
    
    if (tipoRespuesta === 'seleccion_multiple') {
        opcionesDiv.style.display = 'block';
    } else {
        opcionesDiv.style.display = 'none';
    }
}

// Configurar eventos para el formulario de añadir ítem
document.getElementById('tipo_respuesta').addEventListener('change', function() {
    toggleOpcionesRespuesta('tipo_respuesta', 'opcionesRespuestaDiv');
});

// Configurar eventos para el formulario de editar ítem
document.getElementById('edit_tipo_respuesta').addEventListener('change', function() {
    toggleOpcionesRespuesta('edit_tipo_respuesta', 'editOpcionesRespuestaDiv');
});

// Inicializar estado de los divs de opciones
document.addEventListener('DOMContentLoaded', function() {
    toggleOpcionesRespuesta('tipo_respuesta', 'opcionesRespuestaDiv');
    
    // Para el formulario de edición se hará cuando se abra el modal
});

// Configurar evento para el botón de editar
document.querySelectorAll('.edit-item-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const texto = this.getAttribute('data-texto');
        const tipo = this.getAttribute('data-tipo');
        const opciones = this.getAttribute('data-opciones');
        const inversion = this.getAttribute('data-inversion') === '1';
        const subescala = this.getAttribute('data-subescala');
        
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_texto').value = texto;
        document.getElementById('edit_tipo_respuesta').value = tipo;
        document.getElementById('edit_opciones_respuesta').value = opciones;
        document.getElementById('edit_inversion').checked = inversion;
        document.getElementById('edit_subescala').value = subescala;
        
        // Actualizar visibilidad de las opciones de respuesta
        toggleOpcionesRespuesta('edit_tipo_respuesta', 'editOpcionesRespuestaDiv');
    });
});

// Configurar evento para el botón de eliminar
document.querySelectorAll('.delete-item-btn').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const numero = this.getAttribute('data-numero');
        
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_numero').textContent = numero;
    });
});
</script>

<?php
// Finalizar la captura y renderizar la página
endPageContent("Administrar Ítems - " . $escala['nombre']);
?> 