<?php
/**
 * Módulo de Videoconsulta - Crear nueva videoconsulta
 * 
 * Esta página permite a los médicos crear nuevas videoconsultas.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar autenticación y permisos
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Solo médicos y administradores pueden crear videoconsultas
if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'paciente') {
    header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php');
    exit;
}

// Obtener usuario actual
$medico_id = $_SESSION['usuario_id'];

// Conexión a la base de datos
$db = getDB();

// Verificar si existe la tabla de videoconsultas
$stmt = $db->query("SHOW TABLES LIKE 'videoconsultas'");
if ($stmt->rowCount() === 0) {
    // Crear tabla si no existe
    $db->exec("
    CREATE TABLE IF NOT EXISTS `videoconsultas` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `paciente_id` int(11) NOT NULL,
      `medico_id` int(11) NOT NULL,
      `fecha` date NOT NULL,
      `hora_inicio` time NOT NULL,
      `hora_fin` time NOT NULL,
      `duracion` int(11) DEFAULT 30,
      `estado` enum('programada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'programada',
      `sala_id` varchar(50) NOT NULL,
      `codigo_acceso` varchar(64) DEFAULT NULL,
      `enlace_acceso` varchar(32) DEFAULT NULL,
      `fecha_expiracion` datetime DEFAULT NULL,
      `motivo` text DEFAULT NULL,
      `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_fin` datetime DEFAULT NULL,
      `duracion_real` int(11) DEFAULT NULL,
      `pin_acceso` varchar(4) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `paciente_id` (`paciente_id`),
      KEY `medico_id` (`medico_id`),
      KEY `estado` (`estado`),
      KEY `fecha` (`fecha`),
      UNIQUE KEY `codigo_acceso` (`codigo_acceso`),
      UNIQUE KEY `enlace_acceso` (`enlace_acceso`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} else {
    // Verificar si existen las columnas necesarias
    $columns = [
        ['nombre' => 'codigo_acceso', 'tipo' => 'varchar(64) DEFAULT NULL', 'unique' => true],
        ['nombre' => 'enlace_acceso', 'tipo' => 'varchar(32) DEFAULT NULL', 'unique' => true],
        ['nombre' => 'fecha_expiracion', 'tipo' => 'datetime DEFAULT NULL', 'unique' => false],
        ['nombre' => 'hora_fin', 'tipo' => 'time NOT NULL', 'unique' => false],
        ['nombre' => 'pin_acceso', 'tipo' => 'varchar(4) DEFAULT NULL', 'unique' => false]
    ];
    
    foreach ($columns as $column) {
        $stmt = $db->query("SHOW COLUMNS FROM videoconsultas LIKE '{$column['nombre']}'");
        if ($stmt->rowCount() === 0) {
            // Añadir columna si no existe
            $unique = $column['unique'] ? ", ADD UNIQUE KEY {$column['nombre']} ({$column['nombre']})" : "";
            $db->exec("ALTER TABLE videoconsultas ADD COLUMN {$column['nombre']} {$column['tipo']}{$unique}");
        }
    }
}

// Obtener pacientes del médico
try {
    $db = getDB();
    
    // Mostrar todos los pacientes disponibles en el sistema
    $stmt = $db->prepare("
        SELECT id, nombre, apellidos
        FROM pacientes
        ORDER BY apellidos, nombre
    ");
    
    $stmt->execute();
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Error al obtener los pacientes: ' . $e->getMessage();
    $pacientes = [];
}

// Iniciar el procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validación de datos
        $paciente_id = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
        $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
        $hora_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '';
        $duracion = isset($_POST['duracion']) ? (int)$_POST['duracion'] : 30;
        $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : '';
        
        // Validaciones básicas
        if (empty($paciente_id)) {
            throw new Exception("Debe seleccionar un paciente");
        }
        
        if (empty($fecha)) {
            throw new Exception("La fecha es obligatoria");
        }
        
        if (empty($hora_inicio)) {
            throw new Exception("La hora de inicio es obligatoria");
        }
        
        // Calcular hora de fin basada en la duración
        $hora_fin = date('H:i:s', strtotime($hora_inicio . ' + ' . $duracion . ' minutes'));
        
        // Generar ID único para la sala
        $sala_id = uniqid('vc_') . '_' . date('Ymd');
        
        // Generar código de acceso (para futuras implementaciones de seguridad)
        $codigo_acceso = bin2hex(random_bytes(6));
        
        // Generar enlace de acceso único
        $enlace_acceso = bin2hex(random_bytes(10));
        
        // Generar PIN de 4 dígitos para acceso directo
        $pin_acceso = sprintf("%04d", rand(0, 9999));
        
        // Establecer fecha de expiración (24 horas después de la fecha de la consulta)
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime($fecha . ' ' . $hora_fin . ' + 24 hours'));
        
        // Insertar en la base de datos
        $stmt = $db->prepare("
            INSERT INTO videoconsultas (paciente_id, medico_id, fecha, hora_inicio, hora_fin, duracion, 
                                      sala_id, codigo_acceso, enlace_acceso, fecha_expiracion, motivo, pin_acceso) 
            VALUES (:paciente_id, :medico_id, :fecha, :hora_inicio, :hora_fin, :duracion, 
                   :sala_id, :codigo_acceso, :enlace_acceso, :fecha_expiracion, :motivo, :pin_acceso)
        ");
        
        $stmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
        $stmt->bindParam(':medico_id', $medico_id, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':hora_inicio', $hora_inicio);
        $stmt->bindParam(':hora_fin', $hora_fin);
        $stmt->bindParam(':duracion', $duracion, PDO::PARAM_INT);
        $stmt->bindParam(':sala_id', $sala_id);
        $stmt->bindParam(':codigo_acceso', $codigo_acceso);
        $stmt->bindParam(':enlace_acceso', $enlace_acceso);
        $stmt->bindParam(':fecha_expiracion', $fecha_expiracion);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':pin_acceso', $pin_acceso);
        
        $stmt->execute();
        $videoconsulta_id = $db->lastInsertId();
        
        // Obtener nombre completo del paciente
        $stmt = $db->prepare("SELECT nombre, apellidos, email FROM pacientes WHERE id = :paciente_id");
        $stmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($paciente['email'])) {
            // Verificar si existe la función enviarEmail, si no importarla
            if (!function_exists('enviarEmail')) {
                require_once ROOT_PATH . '/modules/consentimientos/funciones.php';
            }
            
            // URL absoluta (asegurarnos de usar el dominio completo)
            $base_url_absoluta = 'https://app.ruizarrietapsicologia.com/ruiz';
            
            // Configuración de correo
            $asunto = 'Invitación a Videoconsulta - ' . date('d/m/Y', strtotime($fecha)) . ' a las ' . date('H:i', strtotime($hora_inicio));
            
            // Construir el contenido del correo
            $contenido = '<p>Estimado/a ' . $paciente['nombre'] . ' ' . $paciente['apellidos'] . ',</p>';
            $contenido .= '<p>Le informamos que tiene programada una videoconsulta para el día <strong>' . date('d/m/Y', strtotime($fecha)) . '</strong> a las <strong>' . date('H:i', strtotime($hora_inicio)) . '</strong>.</p>';
            $contenido .= '<p>Para acceder a la consulta, haga clic en el siguiente enlace el día y hora indicados:</p>';
            $contenido .= '<p><a href="' . $base_url_absoluta . '/modules/videoconsulta/acceso.php?codigo=' . $enlace_acceso . '" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Acceder a la videoconsulta</a></p>';
            $contenido .= '<p>O copie esta dirección en su navegador:</p>';
            $contenido .= '<p>' . $base_url_absoluta . '/modules/videoconsulta/acceso.php?codigo=' . $enlace_acceso . '</p>';
            
            $contenido .= '<p><strong>Su código PIN para acceder a la consulta es: <span style="font-size: 20px; background-color: #f1f1f1; padding: 5px 10px; border-radius: 5px;">' . $pin_acceso . '</span></strong></p>';
            $contenido .= '<p>Necesitará este código para confirmar su identidad al ingresar a la videoconsulta.</p>';
            
            if (!empty($motivo)) {
                $contenido .= '<p><strong>Motivo de la consulta:</strong> ' . $motivo . '</p>';
            }
            
            $contenido .= '<p>Si tiene problemas para conectarse o necesita cambiar la cita, contáctenos lo antes posible.</p>';
            $contenido .= '<p>Atentamente,<br>El equipo médico</p>';
            
            // Enviar el correo
            try {
                $email_enviado = enviarEmail($paciente['email'], $asunto, $contenido);
                
                if ($email_enviado) {
                    setAlert('success', 'Videoconsulta creada y email enviado a ' . $paciente['email']);
                } else {
                    setAlert('warning', 'Videoconsulta creada, pero hubo un problema al enviar el email de notificación');
                }
            } catch (Exception $e) {
                setAlert('warning', 'Videoconsulta creada, pero hubo un error al enviar el email: ' . $e->getMessage());
            }
        } else {
            setAlert('success', 'Videoconsulta creada correctamente');
        }
        
        // Redireccionar a la página de listado
        header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Incluir cabecera
$pageTitle = "Nueva Videoconsulta";
?>
<!-- Asegurarnos de que Bootstrap y los iconos se carguen correctamente -->
<link href="<?= BASE_URL ?>/assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<?php
require_once '../../includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/videoconsulta">Videoconsultas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nueva Videoconsulta</li>
                </ol>
            </nav>
            <h1><span class="material-symbols-rounded me-2">add</span>Nueva Videoconsulta</h1>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" id="formCrearVideoconsulta">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="paciente_id" class="form-label">Paciente *</label>
                        <select class="form-select" id="paciente_id" name="paciente_id" required>
                            <option value="">Seleccione un paciente</option>
                            <?php foreach ($pacientes as $paciente): ?>
                                <option value="<?= $paciente['id'] ?>"><?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" required 
                               min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="hora_inicio" class="form-label">Hora de inicio *</label>
                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                    </div>
                    <div class="col-md-6">
                        <label for="duracion" class="form-label">Duración (minutos)</label>
                        <input type="number" class="form-control" id="duracion" name="duracion" 
                               min="10" max="120" value="30" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="motivo" class="form-label">Motivo de la consulta</label>
                    <textarea class="form-control" id="motivo" name="motivo" rows="3"></textarea>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <span class="material-symbols-rounded me-1 align-middle">info</span>
                        <strong>Nota:</strong> El paciente recibirá una notificación por correo electrónico con los detalles para unirse a la videoconsulta.
                    </small>
                </div>
                
                <div class="d-flex justify-content-end">
                    <a href="<?= BASE_URL ?>/modules/videoconsulta/index.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">save</span> Guardar Videoconsulta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Establecer la fecha mínima al día de hoy
    document.getElementById('fecha').min = new Date().toISOString().split('T')[0];
    
    // Validación adicional del formulario
    document.getElementById('formCrearVideoconsulta').addEventListener('submit', function(e) {
        const paciente = document.getElementById('paciente_id').value;
        const fecha = document.getElementById('fecha').value;
        const hora = document.getElementById('hora_inicio').value;
        
        if (!paciente || !fecha || !hora) {
            e.preventDefault();
            alert('Por favor, complete todos los campos obligatorios.');
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?> 