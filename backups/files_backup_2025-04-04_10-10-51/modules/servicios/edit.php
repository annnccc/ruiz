<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Inicializamos variables
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
$isEditing = $id > 0;
$servicio = null;
$errors = [];

// Título y breadcrumbs
$titulo_pagina = $isEditing ? "Editar Servicio" : "Nuevo Servicio";
$title = $titulo_pagina;
$breadcrumbs = [
    'Servicios' => BASE_URL . '/modules/servicios/list.php',
    $titulo_pagina => '#'
];

// Procesamos el formulario si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $duracion_minutos = trim($_POST['duracion_minutos'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validamos los datos
    if (empty($nombre)) {
        $errors[] = "El nombre del servicio es obligatorio";
    }
    
    // Convertimos el formato de precio (coma a punto)
    if (!empty($precio)) {
        $precio = str_replace(',', '.', $precio);
        if (!is_numeric($precio) || $precio < 0) {
            $errors[] = "El precio debe ser un número válido";
        }
    } else {
        $errors[] = "El precio es obligatorio";
    }
    
    // Validamos la duración
    if (empty($duracion_minutos) || !is_numeric($duracion_minutos) || $duracion_minutos <= 0) {
        $errors[] = "La duración debe ser un número positivo";
    }
    
    // Si no hay errores, guardamos en la base de datos
    if (empty($errors)) {
        try {
            $db = getDB();
            
            if ($isEditing) {
                // Actualizamos el servicio existente
                $stmt = $db->prepare("UPDATE servicios SET 
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio = :precio,
                    duracion_minutos = :duracion_minutos,
                    activo = :activo
                    WHERE id = :id");
                    
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
                // Creamos un nuevo servicio
                $stmt = $db->prepare("INSERT INTO servicios (
                    nombre, descripcion, precio, duracion_minutos, activo
                ) VALUES (
                    :nombre, :descripcion, :precio, :duracion_minutos, :activo
                )");
            }
            
            // Vinculamos los parámetros
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':precio', $precio, PDO::PARAM_STR);
            $stmt->bindParam(':duracion_minutos', $duracion_minutos, PDO::PARAM_INT);
            $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
            
            // Ejecutamos la consulta
            if ($stmt->execute()) {
                // Si es nuevo, obtenemos el ID insertado
                if (!$isEditing) {
                    $id = $db->lastInsertId();
                }
                
                // Mensaje de éxito
                setAlert('success', ($isEditing ? 'Servicio actualizado' : 'Servicio creado') . ' correctamente.');
                
                // Redirigimos a la lista
                header("Location: " . BASE_URL . "/modules/servicios/list.php");
                exit;
            } else {
                $errors[] = "Error al guardar los datos";
            }
        } catch (PDOException $e) {
            $errors[] = "Error de base de datos: " . $e->getMessage();
        }
    }
} else if ($isEditing) {
    // Si estamos editando, cargamos los datos del servicio
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM servicios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$servicio) {
            setAlert('danger', 'El servicio solicitado no existe.');
            header("Location: " . BASE_URL . "/modules/servicios/list.php");
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = "Error al cargar el servicio: " . $e->getMessage();
    }
}

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2"><?= $isEditing ? 'edit' : 'add_circle' ?></span><?= $titulo_pagina ?>
        </h1>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas de error -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    
    <!-- Alertas del sistema -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Formulario -->
    <div class="card border-0 shadow-sm animate-fade-in">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">format_list_bulleted</span>Información del Servicio
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'] . ($isEditing ? '?id=' . $id : '')) ?>" class="needs-validation" novalidate>
                <!-- Nombre del servicio -->
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del servicio *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?= htmlspecialchars($_POST['nombre'] ?? $servicio['nombre'] ?? '') ?>" 
                           required>
                    <div class="invalid-feedback">El nombre del servicio es obligatorio</div>
                </div>
                
                <!-- Descripción -->
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($_POST['descripcion'] ?? $servicio['descripcion'] ?? '') ?></textarea>
                </div>
                
                <div class="row">
                    <!-- Precio -->
                    <div class="col-md-4 mb-3">
                        <label for="precio" class="form-label">Precio (€) *</label>
                        <input type="text" class="form-control" id="precio" name="precio" 
                               value="<?= htmlspecialchars($_POST['precio'] ?? (isset($servicio['precio']) ? number_format($servicio['precio'], 2, ',', '.') : '')) ?>" 
                               required>
                        <div class="invalid-feedback">Introduzca un precio válido</div>
                    </div>
                    
                    <!-- Duración -->
                    <div class="col-md-4 mb-3">
                        <label for="duracion_minutos" class="form-label">Duración (minutos) *</label>
                        <input type="number" class="form-control" id="duracion_minutos" name="duracion_minutos" 
                               value="<?= htmlspecialchars($_POST['duracion_minutos'] ?? $servicio['duracion_minutos'] ?? '60') ?>" 
                               min="5" step="5" required>
                        <div class="invalid-feedback">La duración debe ser un número positivo</div>
                    </div>
                    
                    <!-- Estado (Activo/Inactivo) -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label d-block">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                                <?= (isset($_POST['activo']) || (isset($servicio['activo']) && $servicio['activo'] == 1)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">Servicio activo</label>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="d-flex justify-content-end mt-4">
                    <a href="<?= BASE_URL ?>/modules/servicios/list.php" class="btn btn-secondary me-2">
                        <span class="material-symbols-rounded me-1">arrow_back</span>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">save</span>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Formatear precio al perder el foco
    const precioInput = document.getElementById('precio');
    precioInput.addEventListener('blur', function() {
        if (this.value && !isNaN(parseFloat(this.value.replace(',', '.')))) {
            const precio = parseFloat(this.value.replace(',', '.'));
            this.value = precio.toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    });
});
</script>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 