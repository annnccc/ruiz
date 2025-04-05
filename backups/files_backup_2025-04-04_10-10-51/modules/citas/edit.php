<?php
// Declaración explícita de codificación
header('Content-Type: text/html; charset=UTF-8');
require_once '../../includes/config.php';

// Preparar el título y breadcrumbs
$isEditing = isset($_GET['id']) && is_numeric($_GET['id']);
$pageTitle = $isEditing ? 'Editar Cita' : 'Nueva Cita';

$breadcrumbs = [
    'Citas' => BASE_URL . '/modules/citas/list.php',
    $pageTitle => '#'
];

$db = new Database();

// Arrays para las opciones de los selectores
$estados = ['pendiente', 'completada', 'cancelada'];
$horas = [];
for ($h = 8; $h <= 20; $h++) {
    for ($m = 0; $m < 60; $m += 15) {
        $horas[] = sprintf('%02d:%02d', $h, $m);
    }
}

// Hora de inicio por defecto si es una nueva cita
$default_hora_inicio = '08:00';

// Manejar las solicitudes POST para crear o actualizar citas
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cita = isset($_POST['id_cita']) ? intval($_POST['id_cita']) : 0;
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $hora_inicio = filter_input(INPUT_POST, 'hora_inicio', FILTER_SANITIZE_STRING);
    $hora_fin = filter_input(INPUT_POST, 'hora_fin', FILTER_SANITIZE_STRING);
    $paciente_id = filter_input(INPUT_POST, 'paciente_id', FILTER_SANITIZE_NUMBER_INT);
    $motivo = filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_SPECIAL_CHARS);
    $pagada = isset($_POST['pagada']) ? 1 : 0;
    $servicio_id = filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_SANITIZE_STRING);
    $fecha_pago = filter_input(INPUT_POST, 'fecha_pago', FILTER_SANITIZE_STRING);
    $forma_pago = filter_input(INPUT_POST, 'forma_pago', FILTER_SANITIZE_STRING);
    $es_bono = isset($_POST['es_bono']) ? 1 : 0;
    $sesiones_bono = filter_input(INPUT_POST, 'sesiones_bono', FILTER_SANITIZE_STRING);
    $bono_id = filter_input(INPUT_POST, 'bono_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Si la cita no está pagada, los campos de pago deben ser NULL
    if ($pagada == 0) {
        $fecha_pago = null;
        $forma_pago = null;
    }
    
    // Convertir precio de formato español a formato de base de datos
    if (!empty($precio)) {
        $precio = str_replace(',', '.', $precio);
    }
    
    $errors = [];
    
    // Validación básica
    if (empty($fecha)) {
        $errors[] = "La fecha es obligatoria";
    }
    if (empty($hora_inicio)) {
        $errors[] = "La hora de inicio es obligatoria";
    }
    if (empty($hora_fin)) {
        $errors[] = "La hora de fin es obligatoria";
    }
    if (empty($paciente_id)) {
        $errors[] = "Debe seleccionar un paciente";
    }
    if (empty($motivo)) {
        $errors[] = "El motivo de la cita es obligatorio";
    }
    
    // Validar servicio si está seleccionado
    if (!empty($servicio_id) && !is_numeric($servicio_id)) {
        $errors[] = "Por favor seleccione un tipo de sesión válido";
    }
    
    // Validar precio si está definido
    if (!empty($precio) && (!is_numeric($precio) || $precio <= 0)) {
        $errors[] = "El precio debe ser un número mayor que cero";
    }
    
    // Si está pagada, validar fecha de pago y forma de pago
    if ($pagada == 1) {
        if (empty($fecha_pago)) {
            $errors[] = "Si la cita está pagada, debe especificar la fecha de pago";
        }
        
        if (empty($forma_pago)) {
            $errors[] = "Si la cita está pagada, debe especificar la forma de pago";
        }
    }
    
    if (empty($errors)) {
        try {
            if ($id_cita > 0) {
                // Actualizar una cita existente
                $query = "UPDATE citas SET 
                            fecha = :fecha, 
                            hora_inicio = :hora_inicio,
                            hora_fin = :hora_fin,
                            paciente_id = :paciente_id, 
                            motivo = :motivo,
                            estado = :estado, 
                            notas = :notas,
                            servicio_id = :servicio_id,
                            precio = :precio,
                            pagada = :pagada,
                            fecha_pago = :fecha_pago,
                            forma_pago = :forma_pago,
                            es_bono = :es_bono,
                            sesiones_bono = :sesiones_bono,
                            bono_id = :bono_id
                          WHERE id = :id";
                $db->query($query);
                $db->bind(':id', $id_cita, PDO::PARAM_INT);
                $db->bind(':fecha', $fecha, PDO::PARAM_STR);
                $db->bind(':hora_inicio', $hora_inicio, PDO::PARAM_STR);
                $db->bind(':hora_fin', $hora_fin, PDO::PARAM_STR);
                $db->bind(':paciente_id', $paciente_id, PDO::PARAM_INT);
                $db->bind(':motivo', $motivo, PDO::PARAM_STR);
                $db->bind(':estado', $estado, PDO::PARAM_STR);
                $db->bind(':notas', $notas, PDO::PARAM_STR);
                $db->bind(':servicio_id', $servicio_id ? $servicio_id : null, PDO::PARAM_INT);
                $db->bind(':precio', $precio ? $precio : null, PDO::PARAM_STR);
                $db->bind(':pagada', $pagada, PDO::PARAM_INT);
                $db->bind(':fecha_pago', $fecha_pago, $fecha_pago ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $db->bind(':forma_pago', $forma_pago, $forma_pago ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $db->bind(':es_bono', $es_bono, PDO::PARAM_INT);
                $db->bind(':sesiones_bono', $sesiones_bono, PDO::PARAM_STR);
                $db->bind(':bono_id', $bono_id, PDO::PARAM_INT);
                
                if ($db->execute()) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'La cita ha sido actualizada con éxito'
                    ];
                    header("Location: view.php?id=" . $id_cita);
                    exit;
                } else {
                    $errors[] = "Error al guardar la cita";
                }
            } else {
                // Crear una nueva cita
                $query = "INSERT INTO citas (fecha, hora_inicio, hora_fin, paciente_id, motivo, estado, notas, servicio_id, precio, pagada, fecha_pago, forma_pago, es_bono, sesiones_bono, bono_id) 
                          VALUES (:fecha, :hora_inicio, :hora_fin, :paciente_id, :motivo, :estado, :notas, :servicio_id, :precio, :pagada, :fecha_pago, :forma_pago, :es_bono, :sesiones_bono, :bono_id)";
                $db->query($query);
                $db->bind(':fecha', $fecha, PDO::PARAM_STR);
                $db->bind(':hora_inicio', $hora_inicio, PDO::PARAM_STR);
                $db->bind(':hora_fin', $hora_fin, PDO::PARAM_STR);
                $db->bind(':paciente_id', $paciente_id, PDO::PARAM_INT);
                $db->bind(':motivo', $motivo, PDO::PARAM_STR);
                $db->bind(':estado', $estado, PDO::PARAM_STR);
                $db->bind(':notas', $notas, PDO::PARAM_STR);
                $db->bind(':servicio_id', $servicio_id ? $servicio_id : null, PDO::PARAM_INT);
                $db->bind(':precio', $precio ? $precio : null, PDO::PARAM_STR);
                $db->bind(':pagada', $pagada, PDO::PARAM_INT);
                $db->bind(':fecha_pago', $fecha_pago, $fecha_pago ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $db->bind(':forma_pago', $forma_pago, $forma_pago ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $db->bind(':es_bono', $es_bono, PDO::PARAM_INT);
                $db->bind(':sesiones_bono', $sesiones_bono, PDO::PARAM_STR);
                $db->bind(':bono_id', $bono_id, PDO::PARAM_INT);
                
                if ($db->execute()) {
                    $id_cita = $db->lastInsertId();
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'La cita ha sido creada con éxito'
                    ];
                    header("Location: view.php?id=" . $id_cita);
                    exit;
                } else {
                    $errors[] = "Error al guardar la cita";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error de base de datos: " . $e->getMessage();
        }
    }
}

// Si estamos editando, obtener los datos de la cita
$cita = null;
$id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Obtener información de la cita
        $query = "SELECT * FROM citas WHERE id = :id LIMIT 1";
        $db->query($query);
        $db->bind(':id', $id, PDO::PARAM_INT);
        $db->execute();
        $cita = $db->single();
        
        if (!$cita) {
            setAlert('danger', 'La cita no existe');
            redirect($relativePath . '/list.php');
        }
        
        // Obtener información del paciente
        $query = "SELECT id, nombre, apellidos FROM pacientes WHERE id = :id LIMIT 1";
        $db->query($query);
        $db->bind(':id', $cita['paciente_id'], PDO::PARAM_INT);
        $db->execute();
        $paciente = $db->single();
        
        if (!$paciente) {
            setAlert('danger', 'El paciente no existe');
            redirect($relativePath . '/list.php');
        }
        
    } catch (PDOException $e) {
        echo "<!-- Error de base de datos: " . $e->getMessage() . " -->";
        $error = "Error al obtener los datos de la cita: " . $e->getMessage();
    }
}

// Obtener la lista de pacientes para el selector
$pacientes = [];
try {
    $query = "SELECT id, nombre, apellidos FROM pacientes ORDER BY apellidos, nombre";
    $db->query($query);
    $db->execute();
    $pacientes = $db->resultSet();
} catch (PDOException $e) {
    echo "<!-- Error al obtener pacientes: " . $e->getMessage() . " -->";
    $error = "Error al obtener la lista de pacientes: " . $e->getMessage();
}

// Configurar el título y las migas de pan
$isEditing = !empty($cita);
$pageTitle = $isEditing ? 'Editar Cita' : 'Nueva Cita';

$breadcrumbs = [
    'Inicio' => BASE_URL . '/index.php',
    'Citas' => BASE_URL . '/modules/citas/list.php',
    $pageTitle => '#'
];

// Incluir el layout header
include '../../includes/layout_header.php';

// Cargar directamente las fuentes de iconos para asegurar que estén disponibles
?>
<!-- Carga directa de Material Symbols para garantizar los iconos -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<div class="container-fluid py-4">
    <?php 
    if(isset($breadcrumbs) && is_array($breadcrumbs)) {
        echo generarBreadcrumb($breadcrumbs); 
    }
    ?>
    
    <!-- Alertas -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><?php echo $pageTitle; ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . ($isEditing ? '?id=' . $cita['id'] : '')); ?>" method="post" class="needs-validation" novalidate>
                        <?php if ($isEditing): ?>
                            <input type="hidden" name="id_cita" value="<?php echo $cita['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="paciente_id" class="form-label">Paciente</label>
                                <select class="form-select" id="paciente_id" name="paciente_id" required>
                                    <option value="">Seleccione un paciente</option>
                                    <?php foreach ($pacientes as $paciente): ?>
                                        <?php 
                                        $selected = '';
                                        if ($isEditing && isset($cita['paciente_id'])) {
                                            if ((int)$cita['paciente_id'] === (int)$paciente['id']) {
                                                $selected = 'selected';
                                            }
                                        }
                                        ?>
                                        <option value="<?php echo $paciente['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un paciente
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="motivo" class="form-label">Motivo de la cita</label>
                                <input type="text" class="form-control" id="motivo" name="motivo" required 
                                       value="<?php echo $isEditing ? htmlspecialchars($cita['motivo'] ?? '') : ''; ?>" 
                                       placeholder="Ej: Consulta general, Revisión, Tratamiento...">
                                <div class="invalid-feedback">
                                    Por favor ingrese el motivo de la cita
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="servicio_id" class="form-label">Tipo de Sesión</label>
                                <select class="form-select" id="servicio_id" name="servicio_id">
                                    <option value="" <?= (!$isEditing && empty($servicio_preseleccionado)) ? 'selected' : '' ?>>Seleccionar tipo de sesión...</option>
                                    <?php
                                    // Obtener lista de servicios
                                    $dbServicios = new Database();
                                    $dbServicios->query("SELECT id, nombre, precio, duracion_minutos FROM servicios ORDER BY nombre");
                                    $servicios = $dbServicios->resultSet();
                                    
                                    // Buscar servicio Individual para preseleccionarlo en caso de nueva cita
                                    $servicio_preseleccionado = null;
                                    $precio_preseleccionado = null;
                                    if (!$isEditing) {
                                        foreach ($servicios as $s) {
                                            if (stripos($s['nombre'], 'Individual') !== false) {
                                                $servicio_preseleccionado = $s['id'];
                                                $precio_preseleccionado = $s['precio'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    foreach ($servicios as $servicio) {
                                        // Si estamos editando, mantenemos el servicio seleccionado
                                        // Si es nueva cita, usamos el preseleccionado
                                        if ($isEditing) {
                                            $selected = (isset($cita['servicio_id']) && $cita['servicio_id'] == $servicio['id']) ? 'selected' : '';
                                        } else {
                                            $selected = ($servicio_preseleccionado == $servicio['id']) ? 'selected' : '';
                                        }
                                        
                                        echo "<option 
                                            value='" . $servicio['id'] . "' 
                                            data-precio='" . $servicio['precio'] . "' 
                                            data-duracion='" . ($servicio['duracion_minutos'] ?? '0') . "' 
                                            $selected>" . htmlspecialchars($servicio['nombre']) . " (" . ($servicio['duracion_minutos'] ?? '0') . " min - " . number_format($servicio['precio'], 2) . " €)</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="precio" class="form-label required-field">Precio (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="precio" name="precio" value="<?= $isEditing && isset($cita['precio']) ? number_format($cita['precio'], 2, '.', '') : (isset($precio_preseleccionado) ? number_format($precio_preseleccionado, 2, '.', '') : '') ?>" step="0.01" min="0" required>
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="invalid-feedback">El precio es obligatorio</div>
                                <div class="form-text">Se actualiza automáticamente al seleccionar el tipo de sesión</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="fecha" class="form-label required-field">Fecha</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="material-symbols-rounded icon-sm">calendar_today</span>
                                    </span>
                                    <input type="date" class="form-control" id="fecha" name="fecha" required 
                                           value="<?php echo $isEditing && isset($cita['fecha']) ? htmlspecialchars($cita['fecha']) : date('Y-m-d'); ?>">
                                </div>
                                <div class="invalid-feedback">
                                    Por favor seleccione una fecha
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="hora_inicio" class="form-label required-field">Hora inicio</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="material-symbols-rounded icon-sm">schedule</span>
                                    </span>
                                    <select class="form-select" id="hora_inicio" name="hora_inicio" required>
                                        <option value="">Seleccione una hora</option>
                                        <?php foreach ($horas as $h): ?>
                                            <?php 
                                            $selected = '';
                                            if ($isEditing && isset($cita['hora_inicio'])) {
                                                if (substr(trim($cita['hora_inicio']), 0, 5) === trim($h)) {
                                                    $selected = 'selected';
                                                }
                                            } else {
                                                // Si es una nueva cita, seleccionar la hora por defecto
                                                if ($h === $default_hora_inicio) {
                                                    $selected = 'selected';
                                                }
                                            }
                                            ?>
                                            <option value="<?php echo $h; ?>" <?php echo $selected; ?>>
                                                <?php echo $h; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor seleccione una hora de inicio
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="hora_fin" class="form-label required-field">Hora fin</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <span class="material-symbols-rounded icon-sm">schedule</span>
                                    </span>
                                    <select class="form-select" id="hora_fin" name="hora_fin" required>
                                        <option value="" disabled <?= (empty($cita['hora_fin'])) ? 'selected' : '' ?>>Seleccionar</option>
                                        <?php
                                        // Generar opciones de hora cada 5 minutos desde 8:05 hasta 21:00
                                        $hora_actual = '08:05';
                                        $hora_final = '21:00';
                                        $incremento_minutos = 5; // Incremento de 5 minutos
                                        
                                        while ($hora_actual <= $hora_final) {
                                            $selected = ($hora_actual == $cita['hora_fin']) ? 'selected' : '';
                                            echo "<option value=\"$hora_actual\" $selected>$hora_actual</option>";
                                            
                                            // Incrementar minutos
                                            $time = strtotime($hora_actual) + $incremento_minutos * 60;
                                            $hora_actual = date('H:i', $time);
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="invalid-feedback">La hora de fin es obligatoria</div>
                                <div class="form-text">Se calcula automáticamente según la duración del servicio</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="pendiente" <?php echo $isEditing && isset($cita['estado']) && $cita['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="completada" <?php echo $isEditing && isset($cita['estado']) && $cita['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                    <option value="cancelada" <?php echo $isEditing && isset($cita['estado']) && $cita['estado'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un estado
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="pagada" name="pagada" value="1" 
                                        <?php echo $isEditing && isset($cita['pagada']) && $cita['pagada'] == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pagada">
                                        Cita pagada
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="detalles-pago" class="row mb-3" style="<?php echo $isEditing && isset($cita['pagada']) && $cita['pagada'] == 1 ? '' : 'display: none;'; ?>">
                            <div class="col-md-6">
                                <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                                <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" 
                                    value="<?php echo $isEditing && isset($cita['fecha_pago']) ? htmlspecialchars($cita['fecha_pago']) : date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="forma_pago" class="form-label">Forma de Pago</label>
                                <select class="form-select" id="forma_pago" name="forma_pago">
                                    <option value="">Seleccionar</option>
                                    <option value="efectivo" <?php echo $isEditing && isset($cita['forma_pago']) && $cita['forma_pago'] == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                                    <option value="tarjeta" <?php echo $isEditing && isset($cita['forma_pago']) && $cita['forma_pago'] == 'tarjeta' ? 'selected' : ''; ?>>Tarjeta de crédito</option>
                                    <option value="transferencia" <?php echo $isEditing && isset($cita['forma_pago']) && $cita['forma_pago'] == 'transferencia' ? 'selected' : ''; ?>>Transferencia bancaria</option>
                                    <option value="bizum" <?php echo $isEditing && isset($cita['forma_pago']) && $cita['forma_pago'] == 'bizum' ? 'selected' : ''; ?>>Bizum</option>
                                    <option value="otro" <?php echo $isEditing && isset($cita['forma_pago']) && $cita['forma_pago'] == 'otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="notas" class="form-label">Notas adicionales</label>
                                <textarea class="form-control" id="notas" name="notas" rows="10"><?php echo $isEditing ? htmlspecialchars($cita['notas'] ?? '') : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="<?php echo $isEditing ? 'view.php?id=' . $cita['id'] : 'list.php'; ?>" class="btn btn-secondary me-2">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Añadir CSS para el editor TinyMCE -->
<style>
    .tox-tinymce {
        border: 1px solid #ced4da;
        border-radius: .25rem;
    }
    
    .tox .tox-toolbar, .tox .tox-toolbar__overflow, .tox .tox-toolbar__primary {
        background-color: #f8f9fa;
    }
    
    .tox .tox-tbtn--enabled, .tox .tox-tbtn:hover {
        background-color: #e9ecef;
    }
</style>

<!-- Cargar TinyMCE -->
<script src="<?= BASE_URL ?>/assets/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
// Inicializar TinyMCE
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#notas',
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
    
    // Validación del formulario
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });

    // Manejar la selección de hora de fin basada en la hora de inicio
    const horaInicio = document.getElementById('hora_inicio');
    const horaFin = document.getElementById('hora_fin');
    
    if (horaInicio && horaFin) {
        horaInicio.addEventListener('change', function() {
            const horaInicioSeleccionada = this.value;
            if (horaInicioSeleccionada) {
                const [hora, minutos] = horaInicioSeleccionada.split(':');
                const horaInicioNum = parseInt(hora) * 60 + parseInt(minutos);
                
                // Habilitar solo las horas posteriores a la hora de inicio
                Array.from(horaFin.options).forEach(option => {
                    if (option.value) {
                        const [horaFin, minutosFin] = option.value.split(':').map(Number);
                        const horaFinNum = parseInt(horaFin) * 60 + parseInt(minutosFin);
                        
                        if (horaFinNum > horaInicioNum) {
                            option.disabled = false;
                        } else {
                            option.disabled = true;
                            if (option.value === horaFin.value) {
                                horaFin.value = '';
                            }
                        }
                    }
                });
            }
        });
    }

    // Manejo del campo de pagada y mostrar/ocultar detalles de pago
    const pagadaCheck = document.getElementById('pagada');
    const detallesPago = document.getElementById('detalles-pago');
    
    pagadaCheck.addEventListener('change', function() {
        if (this.checked) {
            detallesPago.style.display = 'flex';
        } else {
            detallesPago.style.display = 'none';
            document.getElementById('fecha_pago').value = '';
            document.getElementById('forma_pago').value = '';
        }
    });
    
    // Actualizar precio cuando se selecciona un servicio
    const servicioSelect = document.getElementById('servicio_id');
    const horaInicioInput = document.getElementById('hora_inicio');
    const horaFinInput = document.getElementById('hora_fin');
    const precioInput = document.getElementById('precio');
    
    // Si hay un servicio preseleccionado, aseguramos que se calcule el precio y hora fin automáticamente
    if (servicioSelect && servicioSelect.selectedIndex > 0) {
        // Pequeño retraso para asegurar que todos los componentes estén listos
        setTimeout(function() {
            actualizarPrecioYDuracion();
        }, 100);
    }

    // Función mejorada para actualizar el precio y calcular la hora de fin
    function actualizarPrecioYDuracion() {
        if (!servicioSelect || servicioSelect.selectedIndex <= 0) {
            return;
        }
        
        const selectedOption = servicioSelect.options[servicioSelect.selectedIndex];
        
        // 1. Actualizar el precio
        if (precioInput) {
            const precio = selectedOption.getAttribute('data-precio');
            
            if (precio) {
                try {
                    // Asegurar que sea un número con dos decimales
                    const precioNumerico = parseFloat(precio).toFixed(2);
                    // Siempre usamos punto decimal para inputs type="number"
                    precioInput.value = precioNumerico;
                    
                    // Añadir un efecto visual para indicar que el campo se actualizó automáticamente
                    precioInput.classList.add('bg-light');
                    precioInput.classList.add('border-primary');
                    setTimeout(() => {
                        precioInput.classList.remove('bg-light');
                        precioInput.classList.remove('border-primary');
                    }, 1000);
                } catch (error) {
                    // Error silencioso
                }
            }
        }
        
        // 2. Calcular la hora fin si tenemos hora inicio
        if (!horaInicioInput || !horaInicioInput.value || !horaFinInput) {
            return;
        }
        
        // Obtener duración del servicio de manera más robusta
        let duracion = 55; // Usamos valor por defecto en caso de no encontrar duración específica
        
        // Primero intentamos obtener el valor del atributo data-duracion
        const duracionAttr = selectedOption.getAttribute('data-duracion');
        
        if (duracionAttr && duracionAttr !== '0' && !isNaN(parseInt(duracionAttr)) && parseInt(duracionAttr) > 0) {
            duracion = parseInt(duracionAttr);
        } else {
            // Si no está en el atributo, intentamos extraerlo del texto visible
            const duracionMatch = selectedOption.text.match(/\((\d+)\s*min/);
            
            if (duracionMatch && duracionMatch[1] && duracionMatch[1] !== '0' && parseInt(duracionMatch[1]) > 0) {
                duracion = parseInt(duracionMatch[1]);
            }
        }
        
        // Calcular la hora de fin
        const horaInicio = horaInicioInput.value;
        
        const [horas, minutos] = horaInicio.split(':').map(Number);
        const totalMinutosInicio = (horas * 60) + minutos;
        const totalMinutosFin = totalMinutosInicio + duracion;
        
        // Convertir a formato HH:MM
        const horasFin = Math.floor(totalMinutosFin / 60);
        const minutosFin = totalMinutosFin % 60;
        const horaFinCalculada = horasFin.toString().padStart(2, '0') + ':' + minutosFin.toString().padStart(2, '0');
        
        // Buscar la opción más cercana en el select
        let mejorOpcion = null;
        let menorDiferencia = Infinity;
        
        // Buscar primero una coincidencia exacta, luego la opción más cercana posterior
        Array.from(horaFinInput.options).forEach(opcion => {
            if (!opcion.value) return;
            
            // Si encontramos coincidencia exacta, la seleccionamos inmediatamente
            if (opcion.value === horaFinCalculada) {
                mejorOpcion = opcion;
                menorDiferencia = 0;
                return;
            }
            
            // Calcular la diferencia en minutos
            const [h, m] = opcion.value.split(':').map(Number);
            const minutosOpcion = (h * 60) + m;
            const diferencia = minutosOpcion - totalMinutosFin;
            
            // Si es una opción posterior a la calculada y más cercana que la mejor actual
            if (diferencia >= 0 && diferencia < menorDiferencia) {
                menorDiferencia = diferencia;
                mejorOpcion = opcion;
            }
        });
        
        // Si no encontramos ninguna opción posterior, buscar la más cercana que sea mayor a la hora inicio
        if (!mejorOpcion) {
            menorDiferencia = Infinity;
            
            Array.from(horaFinInput.options).forEach(opcion => {
                if (!opcion.value) return;
                
                const [h, m] = opcion.value.split(':').map(Number);
                const minutosOpcion = (h * 60) + m;
                
                // Solo considerar opciones posteriores a la hora de inicio
                if (minutosOpcion > totalMinutosInicio) {
                    const diferencia = Math.abs(minutosOpcion - totalMinutosFin);
                    
                    if (diferencia < menorDiferencia) {
                        menorDiferencia = diferencia;
                        mejorOpcion = opcion;
                    }
                }
            });
        }
        
        // Seleccionar la mejor opción
        if (mejorOpcion) {
            horaFinInput.value = mejorOpcion.value;
            
            // Añadir un efecto visual para indicar que el campo se actualizó automáticamente
            horaFinInput.classList.add('bg-light');
            horaFinInput.classList.add('border-primary');
            setTimeout(() => {
                horaFinInput.classList.remove('bg-light');
                horaFinInput.classList.remove('border-primary');
            }, 1000);
            
            // Dispara el evento change para que otros listeners lo detecten
            const event = new Event('change');
            horaFinInput.dispatchEvent(event);
        }
    }
    
    // Vincular eventos con la función combinada
    if (servicioSelect) {
        servicioSelect.addEventListener('change', actualizarPrecioYDuracion);
    }
    
    if (horaInicioInput) {
        horaInicioInput.addEventListener('change', actualizarPrecioYDuracion);
    }
    
    // Ejecutar después de un pequeño retraso para asegurar que todos los componentes estén listos
    setTimeout(function() {
        if (servicioSelect && servicioSelect.selectedIndex > 0) {
            actualizarPrecioYDuracion();
        }
    }, 500);
});
</script>

<?php
// Incluir el footer
include '../../includes/layout_footer.php';
?>