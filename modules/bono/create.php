<?php
// Página de creación de bonos con encabezado y sidebar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos básicos
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere login
requiereLogin();

// Procesar el paciente_id si viene como parámetro GET
$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;

// Procesar formulario de creación
$success = false;
$error = null;
$bono_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos recibidos
    $paciente_id = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
    $num_sesiones_total = isset($_POST['num_sesiones_total']) ? (int)$_POST['num_sesiones_total'] : 0;
    $fecha_compra = isset($_POST['fecha_compra']) ? sanitizeInput($_POST['fecha_compra']) : '';
    $fecha_caducidad = isset($_POST['fecha_caducidad']) ? sanitizeInput($_POST['fecha_caducidad']) : null;
    $monto = isset($_POST['monto']) ? floatval(str_replace(',', '.', $_POST['monto'])) : 0;
    $notas = isset($_POST['notas']) ? sanitizeInput($_POST['notas']) : '';
    
    // Validar datos
    if ($paciente_id <= 0) {
        $error = "Debe seleccionar un paciente válido.";
    } elseif ($num_sesiones_total <= 0) {
        $error = "El número de sesiones debe ser mayor que cero.";
    } elseif ($monto <= 0) {
        $error = "El monto debe ser mayor que cero.";
    } elseif (empty($fecha_compra)) {
        $error = "La fecha de compra es obligatoria.";
    } else {
        try {
            $db = getDB();
            
            // Insertar nuevo bono
            $query = "INSERT INTO bonos (
                        paciente_id, 
                        num_sesiones_total, 
                        num_sesiones_disponibles,
                        fecha_compra, 
                        fecha_caducidad, 
                        monto, 
                        estado, 
                        notas, 
                        creado_por,
                        fecha_creacion
                      ) VALUES (
                        :paciente_id, 
                        :num_sesiones_total, 
                        :num_sesiones_total, 
                        :fecha_compra, 
                        :fecha_caducidad, 
                        :monto, 
                        'activo', 
                        :notas, 
                        :creado_por,
                        NOW()
                      )";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
            $stmt->bindParam(':num_sesiones_total', $num_sesiones_total, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_compra', $fecha_compra);
            
            if (empty($fecha_caducidad)) {
                $stmt->bindValue(':fecha_caducidad', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':fecha_caducidad', $fecha_caducidad);
            }
            
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':notas', $notas);
            $stmt->bindValue(':creado_por', $_SESSION['usuario_id'] ?? 0, PDO::PARAM_INT);
            
            $stmt->execute();
            $bono_id = $db->lastInsertId();
            
            $success = true;
            
            // Redirigir después de crear
            header('Location: view.php?id=' . $bono_id . '&success=' . urlencode('Bono creado correctamente'));
            exit;
            
        } catch (PDOException $e) {
            $error = "Error al crear el bono: " . $e->getMessage();
        }
    }
}

// Obtener lista de pacientes
try {
    $db = getDB();
    
    // Consulta para obtener la lista de pacientes
    $queryPacientes = "SELECT id, nombre, apellidos FROM pacientes ORDER BY apellidos, nombre";
    $stmtPacientes = $db->prepare($queryPacientes);
    $stmtPacientes->execute();
    $pacientes = $stmtPacientes->fetchAll(PDO::FETCH_ASSOC);
    
    // Si hay un paciente_id predeterminado, obtener su información
    $paciente_info = null;
    if ($paciente_id > 0) {
        $queryPaciente = "SELECT id, nombre, apellidos FROM pacientes WHERE id = :paciente_id";
        $stmtPaciente = $db->prepare($queryPaciente);
        $stmtPaciente->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
        $stmtPaciente->execute();
        $paciente_info = $stmtPaciente->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = "Error al obtener la lista de pacientes: " . $e->getMessage();
    $pacientes = [];
}

// Valores predeterminados para el formulario
$today = date('Y-m-d');
$yearLater = date('Y-m-d', strtotime('+1 year'));

// Título y breadcrumbs para la página
$titulo_pagina = "Crear Nuevo Bono";
$breadcrumbs = [
    'Bonos' => BASE_URL . '/modules/bono/list.php',
    'Crear' => '#'
];

// Iniciar buffer de salida
startPageContent();
?>

<div class="container-fluid py-4">
    <!-- Encabezado de la página -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">add_circle</span> <?= $titulo_pagina ?>
        </h1>
        <a href="list.php" class="btn btn-secondary">
            <span class="material-symbols-rounded me-1">arrow_back</span> Volver a la lista
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Bono creado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    
    <!-- Formulario de creación -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Información del Bono</h5>
        </div>
        <div class="card-body">
            <form action="create.php" method="post" id="bonoForm" class="row g-3">
                <!-- Primera columna -->
                <div class="col-md-6">
                    <!-- Paciente -->
                    <div class="mb-3">
                        <label for="paciente_id" class="form-label">Paciente <span class="text-danger">*</span></label>
                        <select class="form-select" id="paciente_id" name="paciente_id" required>
                            <option value="">Seleccionar paciente</option>
                            <?php foreach ($pacientes as $paciente): ?>
                                <option value="<?= $paciente['id'] ?>" <?= ($paciente_id == $paciente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Paciente al que pertenece el bono.</div>
                    </div>
                    
                    <!-- Sesiones total -->
                    <div class="mb-3">
                        <label for="num_sesiones_total" class="form-label">Número total de sesiones <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="num_sesiones_total" name="num_sesiones_total" value="10" min="1" required>
                        <div class="form-text">Cantidad total de sesiones que incluye el bono.</div>
                    </div>
                    
                    <!-- Monto -->
                    <div class="mb-3">
                        <label for="monto" class="form-label">Importe (€) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="monto" name="monto" value="450,00" required>
                            <span class="input-group-text">€</span>
                        </div>
                        <div class="form-text">Precio total del bono.</div>
                    </div>
                </div>
                
                <!-- Segunda columna -->
                <div class="col-md-6">
                    <!-- Fecha de compra -->
                    <div class="mb-3">
                        <label for="fecha_compra" class="form-label">Fecha de compra <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" value="<?= $today ?>" required>
                        <div class="form-text">Fecha en que se adquirió el bono.</div>
                    </div>
                    
                    <!-- Fecha de caducidad -->
                    <div class="mb-3">
                        <label for="fecha_caducidad" class="form-label">Fecha de caducidad</label>
                        <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad" value="<?= $yearLater ?>">
                        <div class="form-text">Fecha de vencimiento del bono (opcional). Dejar en blanco si no caduca.</div>
                    </div>
                    
                    <!-- Notas -->
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="4"></textarea>
                        <div class="form-text">Observaciones adicionales sobre el bono.</div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="col-12 d-flex justify-content-end mt-4">
                    <a href="list.php" class="btn btn-secondary me-2">
                        <span class="material-symbols-rounded me-1">cancel</span> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">save</span> Crear Bono
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar select2 para el selector de pacientes si está disponible
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('#paciente_id').select2({
                placeholder: 'Seleccione un paciente',
                width: '100%'
            });
        }
        
        // Validación de formulario
        const bonoForm = document.getElementById('bonoForm');
        
        bonoForm.addEventListener('submit', function(event) {
            const numSesiones = parseInt(document.getElementById('num_sesiones_total').value);
            
            if (numSesiones <= 0) {
                alert('El número de sesiones debe ser mayor que cero.');
                event.preventDefault();
                return false;
            }
            
            // Validar el monto como un número decimal válido
            const montoInput = document.getElementById('monto');
            const montoValue = montoInput.value.replace(',', '.');
            
            if (isNaN(montoValue) || parseFloat(montoValue) <= 0) {
                alert('Por favor, introduzca un importe válido mayor que cero.');
                event.preventDefault();
                return false;
            }
            
            return true;
        });
    });
</script>

<?php
// Finalizar la captura y renderizar la página
endPageContent($titulo_pagina, [
    'breadcrumb' => [
        'Inicio' => BASE_URL,
        'Bonos' => BASE_URL . '/modules/bono/list.php',
        'Crear Bono' => '#'
    ]
]);
?> 