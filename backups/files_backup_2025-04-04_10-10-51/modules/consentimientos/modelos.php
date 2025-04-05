<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once 'funciones.php';

// Requiere autenticación
requiereLogin();

// Verifica si existe la tabla de consentimientos
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'consentimientos_modelos'");
    if ($stmt->rowCount() === 0) {
        redirect(BASE_URL . '/modules/consentimientos/install.php');
    }
} catch (PDOException $e) {
    redirect(BASE_URL . '/modules/consentimientos/install.php');
}

// Procesar formulario de nuevo modelo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    // Crear nuevo modelo
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validación
        $errores = [];
        
        if (empty($nombre)) {
            $errores[] = 'El nombre del modelo es obligatorio';
        }
        
        if (empty($contenido)) {
            $errores[] = 'El contenido del modelo es obligatorio';
        }
        
        if (empty($errores)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    INSERT INTO consentimientos_modelos 
                    (nombre, descripcion, contenido, activo)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nombre, $descripcion, $contenido, $activo]);
                
                setAlert('success', 'Modelo de consentimiento creado correctamente');
                redirect(BASE_URL . '/modules/consentimientos/modelos.php');
            } catch (PDOException $e) {
                setAlert('danger', 'Error al crear el modelo: ' . $e->getMessage());
            }
        } else {
            setAlert('danger', implode('<br>', $errores));
        }
    }
    
    // Editar modelo existente
    elseif ($accion === 'editar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validación
        $errores = [];
        
        if (empty($nombre)) {
            $errores[] = 'El nombre del modelo es obligatorio';
        }
        
        if (empty($contenido)) {
            $errores[] = 'El contenido del modelo es obligatorio';
        }
        
        if (empty($errores)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("
                    UPDATE consentimientos_modelos 
                    SET nombre = ?, descripcion = ?, contenido = ?, activo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $descripcion, $contenido, $activo, $id]);
                
                setAlert('success', 'Modelo de consentimiento actualizado correctamente');
                redirect(BASE_URL . '/modules/consentimientos/modelos.php');
            } catch (PDOException $e) {
                setAlert('danger', 'Error al actualizar el modelo: ' . $e->getMessage());
            }
        } else {
            setAlert('danger', implode('<br>', $errores));
        }
    }
    
    // Cambiar estado (activar/desactivar)
    elseif ($accion === 'cambiar_estado' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $nuevo_estado = (int)$_POST['nuevo_estado'];
        
        try {
            $db = getDB();
            $stmt = $db->prepare("
                UPDATE consentimientos_modelos 
                SET activo = ?
                WHERE id = ?
            ");
            $stmt->execute([$nuevo_estado, $id]);
            
            $mensaje = $nuevo_estado ? 'activado' : 'desactivado';
            setAlert('success', 'Modelo de consentimiento ' . $mensaje . ' correctamente');
        } catch (PDOException $e) {
            setAlert('danger', 'Error al cambiar el estado del modelo: ' . $e->getMessage());
        }
    }
}

// Obtener modelo a editar si se solicita
$modelo_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM consentimientos_modelos WHERE id = ?");
        $stmt->execute([(int)$_GET['editar']]);
        $modelo_editar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$modelo_editar) {
            setAlert('warning', 'El modelo solicitado no existe');
        }
    } catch (PDOException $e) {
        setAlert('danger', 'Error al obtener el modelo: ' . $e->getMessage());
    }
}

// Obtener todos los modelos para la lista
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT * FROM consentimientos_modelos 
        ORDER BY nombre ASC
    ");
    $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los modelos: ' . $e->getMessage());
    $modelos = [];
}

// Preparar datos para la página
$title = "Gestión de Modelos de Consentimiento";
$breadcrumbs = [
    'Consentimientos' => '../consentimientos/listar.php',
    'Modelos' => '#'
];

// Iniciar el buffer de salida
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">file_copy</span>Modelos de Consentimiento
        </h1>
        <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-outline-secondary">
            <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Consentimientos
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="row">
        <div class="col-lg-5">
            <!-- Formulario para crear/editar modelos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">
                            <?= $modelo_editar ? 'edit_document' : 'add_circle' ?>
                        </span>
                        <?= $modelo_editar ? 'Editar Modelo' : 'Nuevo Modelo' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="accion" value="<?= $modelo_editar ? 'editar' : 'crear' ?>">
                        <?php if ($modelo_editar): ?>
                            <input type="hidden" name="id" value="<?= $modelo_editar['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del modelo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($modelo_editar['nombre'] ?? '') ?>" required>
                            <div class="form-text">Nombre descriptivo para identificar este modelo</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($modelo_editar['descripcion'] ?? '') ?></textarea>
                            <div class="form-text">Breve descripción del propósito o uso de este modelo</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contenido" class="form-label">Contenido del consentimiento *</label>
                            <textarea class="form-control" id="contenido" name="contenido" rows="15" required><?= htmlspecialchars($modelo_editar['contenido'] ?? '') ?></textarea>
                            <div class="form-text">
                                <p class="mb-1">Puedes utilizar las siguientes variables en el contenido del consentimiento:</p>
                                <ul class="mb-1">
                                    <li><strong>{NOMBRE_PACIENTE}</strong> - Nombre completo del paciente</li>
                                    <li><strong>{DNI_PACIENTE}</strong> - DNI/NIF del paciente</li>
                                    <li><strong>{DIRECCION}</strong> - Dirección completa del paciente</li>
                                    <li><strong>{DIA}</strong> - Día del mes (número)</li>
                                    <li><strong>{MES}</strong> - Nombre del mes (ejemplo: enero)</li>
                                    <li><strong>{AÑO}</strong> o <strong>{ANO}</strong> - Año (número)</li>
                                    <li><strong>{FECHA}</strong> - Fecha completa en formato día/mes/año</li>
                                </ul>
                                <p class="mb-0">Estas variables serán reemplazadas automáticamente con los datos correspondientes.</p>
                            </div>
                        </div>
                        
                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                   <?= (!isset($modelo_editar) || $modelo_editar['activo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">Modelo activo</label>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <?php if ($modelo_editar): ?>
                                <a href="<?= BASE_URL ?>/modules/consentimientos/modelos.php" class="btn btn-secondary me-2">
                                    <span class="material-symbols-rounded me-2">cancel</span>Cancelar
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded me-2">save</span>
                                <?= $modelo_editar ? 'Actualizar Modelo' : 'Guardar Modelo' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <!-- Lista de modelos existentes -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">list</span>Modelos Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($modelos)): ?>
                        <div class="alert alert-info">
                            <div class="d-flex">
                                <div class="me-3">
                                    <span class="material-symbols-rounded">info</span>
                                </div>
                                <div>
                                    <p class="mb-0">No hay modelos de consentimiento disponibles. Cree uno nuevo utilizando el formulario.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modelos as $modelo): ?>
                                        <tr>
                                            <td>
                                                <a href="?editar=<?= $modelo['id'] ?>" class="fw-bold text-decoration-none">
                                                    <?= htmlspecialchars($modelo['nombre']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= !empty($modelo['descripcion']) ? htmlspecialchars($modelo['descripcion']) : 'Sin descripción' ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($modelo['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="?editar=<?= $modelo['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <span class="material-symbols-rounded icon-sm">edit</span>
                                                    </a>
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('¿Estás seguro de cambiar el estado de este modelo?')">
                                                        <input type="hidden" name="accion" value="cambiar_estado">
                                                        <input type="hidden" name="id" value="<?= $modelo['id'] ?>">
                                                        <input type="hidden" name="nuevo_estado" value="<?= $modelo['activo'] ? 0 : 1 ?>">
                                                        <button type="submit" class="btn btn-sm <?= $modelo['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                            <span class="material-symbols-rounded icon-sm">
                                                                <?= $modelo['activo'] ? 'toggle_off' : 'toggle_on' ?>
                                                            </span>
                                                        </button>
                                                    </form>
                                                </div>
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
    </div>
</div>

<?php
// Finalizar el buffer de salida
endPageContent();
?> 