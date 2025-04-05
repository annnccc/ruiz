<?php
/**
 * Módulo de Escalas Psicológicas - Nueva Administración
 * Permite asignar una escala a un paciente
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Obtener conexión a la base de datos
$db = getDB();

// Verificar si el usuario está autenticado antes de incluir el header
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Título de la página
$pageTitle = "Nueva Administración de Escala";

// Iniciar captura del contenido de la página
startPageContent();

// Verificar si se ha proporcionado un paciente o una escala predeterminada
$escala_id = isset($_GET['escala_id']) ? $_GET['escala_id'] : null;
$paciente_id = isset($_GET['paciente_id']) ? $_GET['paciente_id'] : null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos obligatorios
    if (empty($_POST['escala_id']) || empty($_POST['paciente_id']) || empty($_POST['fecha'])) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
    } else {
        try {
            // Preparar fecha y hora
            $fecha_hora = $_POST['fecha'] . ' ' . ($_POST['hora'] ?? '00:00:00');
            
            $stmt = $db->prepare("INSERT INTO escalas_administraciones 
                (escala_id, paciente_id, fecha, motivo, observaciones, usuario_id, fecha_creacion) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $_POST['escala_id'],
                $_POST['paciente_id'],
                $fecha_hora,
                $_POST['motivo'] ?? null,
                $_POST['observaciones'] ?? null,
                $_SESSION['usuario_id']
            ]);
            
            $admin_id = $db->lastInsertId();
            
            $_SESSION['success'] = "Administración de escala programada correctamente.";
            
            // Redireccionar según la acción elegida
            if (isset($_POST['action']) && $_POST['action'] === 'aplicar_ahora') {
                header('Location: completar_escala.php?id=' . $admin_id);
            } else {
                header('Location: index.php');
            }
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al programar la administración: " . $e->getMessage();
        }
    }
}

// Obtener lista de escalas disponibles
$stmt = $db->query("SELECT id, nombre, poblacion, tiempo_estimado FROM escalas_catalogo 
                   WHERE poblacion IN ('adultos', 'adolescentes', 'niños', 'todos') 
                   ORDER BY nombre");
$escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agregar mensaje de depuración
if ($_SESSION['usuario_rol'] === 'admin') {
    echo "<div class='alert alert-info'><h5>Valores de población:</h5><ul>";
    foreach ($escalas as $escala) {
        echo "<li>Escala ID {$escala['id']}: {$escala['nombre']} - Población: '{$escala['poblacion']}'</li>";
    }
    echo "</ul></div>";
}

// Obtener lista de pacientes
$stmt = $db->query("SELECT id, nombre, apellidos, fecha_nacimiento FROM pacientes ORDER BY apellidos, nombre");
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si se proporcionó un ID de escala, obtener sus detalles
$escala_seleccionada = null;
if ($escala_id) {
    $stmt = $db->prepare("SELECT * FROM escalas_catalogo WHERE id = ?");
    $stmt->execute([$escala_id]);
    $escala_seleccionada = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si se proporcionó un ID de paciente, obtener sus detalles
$paciente_seleccionado = null;
if ($paciente_id) {
    $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$paciente_id]);
    $paciente_seleccionado = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">assignment</span>
        Nueva Administración de Escala
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Nueva Administración</li>
    </ol>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($escalas)): ?>
        <div class="alert alert-warning">
            <p>No hay escalas disponibles en el sistema. Los administradores deben configurar las escalas primero.</p>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
    <?php elseif (empty($pacientes)): ?>
        <div class="alert alert-warning">
            <p>No hay pacientes registrados en el sistema. Debe registrar pacientes primero.</p>
            <a href="<?= BASE_URL ?>/modules/pacientes/crear.php" class="btn btn-primary">Registrar Paciente</a>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-clipboard-list me-1"></i>
                Datos de la Administración
            </div>
            <div class="card-body">
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="escala_id" class="form-label">Escala <span class="text-danger">*</span></label>
                            <select class="form-select" id="escala_id" name="escala_id" required>
                                <option value="">Seleccione una escala</option>
                                <?php foreach ($escalas as $escala): ?>
                                    <option value="<?= $escala['id'] ?>" <?= ($escala_id == $escala['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($escala['nombre']) ?> 
                                        (<?= ucfirst(htmlspecialchars($escala['poblacion'])) ?>, <?= htmlspecialchars($escala['tiempo_estimado']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="paciente_id" class="form-label">Paciente <span class="text-danger">*</span></label>
                            <select class="form-select" id="paciente_id" name="paciente_id" required>
                                <option value="">Seleccione un paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?= $paciente['id'] ?>" <?= ($paciente_id == $paciente['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']) ?>
                                        <?php if ($paciente['fecha_nacimiento']): ?>
                                            (<?= calcular_edad($paciente['fecha_nacimiento']) ?> años)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="hora" class="form-label">Hora</label>
                            <input type="time" class="form-control" id="hora" name="hora" value="<?= date('H:i') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo de la Evaluación</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" name="action" value="programar" class="btn btn-primary">Programar Evaluación</button>
                            <button type="submit" name="action" value="aplicar_ahora" class="btn btn-success">Aplicar Ahora</button>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($escala_seleccionada): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i>
                    Información de la Escala Seleccionada
                </div>
                <div class="card-body">
                    <h5><?= htmlspecialchars($escala_seleccionada['nombre']) ?></h5>
                    <p><?= htmlspecialchars($escala_seleccionada['descripcion']) ?></p>
                    
                    <?php if (!empty($escala_seleccionada['instrucciones'])): ?>
                        <h6 class="mt-3">Instrucciones:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($escala_seleccionada['instrucciones'])) ?></p>
                    <?php endif; ?>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Población:</strong> <?= ucfirst(htmlspecialchars($escala_seleccionada['poblacion'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Tiempo Estimado:</strong> <?= htmlspecialchars($escala_seleccionada['tiempo_estimado']) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
/**
 * Función para calcular la edad a partir de la fecha de nacimiento
 */
function calcular_edad($fecha_nacimiento) {
    $nacimiento = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento);
    return $edad->y;
}

// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 