<?php
/**
 * Módulo de Videoconsulta - Sala de videollamada
 * 
 * Esta página implementa la sala de videoconsulta con WebRTC.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

// Obtener el id de la sala desde la URL
$sala_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$sala_id) {
    header('Location: index.php');
    exit;
}

// Obtener información adicional de la URL
$es_admin = isset($_GET['admin']) && $_GET['admin'] == '1';
$rol_usuario = isset($_GET['role']) ? $_GET['role'] : null;
$sin_media = isset($_GET['no_media']) && $_GET['no_media'] == '1';

// Verificar si el usuario tiene acceso a esta sala
$db = getDB();
$stmt = $db->prepare("
    SELECT c.*, p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos
    FROM citas c 
    JOIN pacientes p ON c.paciente_id = p.id 
    WHERE c.id = :sala_id
");
$stmt->bindParam(':sala_id', $sala_id);
$stmt->execute();
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no encontramos la cita, intentar buscar en videoconsultas
if (!$cita) {
    $stmt = $db->prepare("
        SELECT v.*, p.nombre AS paciente_nombre, p.apellidos AS paciente_apellidos
        FROM videoconsultas v
        JOIN pacientes p ON v.paciente_id = p.id
        WHERE v.id = :sala_id
    ");
    $stmt->bindParam(':sala_id', $sala_id);
    $stmt->execute();
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$cita && !$es_admin) {
    $_SESSION['error'] = "La sala de videoconsulta no existe o no tienes acceso.";
    header('Location: index.php');
    exit;
}

// Determinar si el usuario es un paciente o un profesional
$usuario_id = $_SESSION['usuario_id'];
$is_paciente = ($cita && $cita['paciente_id'] == $usuario_id);

// Para la videoconsulta, asumimos que cualquier usuario que no sea el paciente es un profesional autorizado
// O si es un administrador, también tiene acceso
$is_profesional = (!$is_paciente && isLoggedIn()) || $es_admin;

// Si no es un paciente ni profesional autorizado, redirigir
if (!$is_profesional && !$is_paciente) {
    $_SESSION['error'] = "No tienes acceso a esta sala de videoconsulta.";
    header('Location: index.php');
    exit;
}

// Variable para determinar si es el iniciador (profesional o admin)
$is_initiator = $is_profesional;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Videoconsulta - <?= APP_NAME ?></title>
    
    <!-- Cargar Bootstrap y CSS del sistema -->
    <link href="<?= BASE_URL ?>/assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Google Fonts y Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos específicos para la videoconsulta -->
    <style>
        .video-container {
            border-radius: 8px;
            overflow: hidden;
            background-color: #000;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        #remoteVideo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #1a1a1a;
        }
        
        #selfVideo {
            border: 2px solid white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .btn-group .btn {
            min-width: 50px;
        }
        
        #hangupBtn {
            min-width: 120px;
        }
        
        #errorMessage, #callEnded {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<?php
// Incluir la plantilla header
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="../index.php">Videoconsulta</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sala de consulta</li>
                </ol>
            </nav>
            <h1>Videoconsulta</h1>
            <p class="lead">
                <?php if ($is_profesional): ?>
                    Paciente: <?php echo htmlspecialchars($cita ? ($cita['paciente_nombre'] . ' ' . $cita['paciente_apellidos']) : 'Información no disponible'); ?>
                <?php else: ?>
                    Consulta médica
                <?php endif; ?>
            </p>
            
            <?php if ($es_admin): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Estás accediendo a esta sala como administrador.
            </div>
            <?php endif; ?>
            
            <?php if ($sin_media): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i> No se detectaron permisos de cámara/micrófono. Podrás ver y escuchar, pero no transmitir.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
            <div id="callEnded" class="alert alert-info" style="display: none;">
                La llamada ha finalizado. Redirigiendo...
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="position-relative video-container" style="height: 70vh;">
                <!-- Video remoto (grande) -->
                <video id="remoteVideo" class="w-100 h-100 bg-dark" autoplay playsinline style="object-fit: contain;"></video>
                
                <!-- Video local (pequeño, superpuesto) -->
                <div class="position-absolute" style="bottom: 20px; right: 20px; width: 150px; height: 100px;">
                    <video id="selfVideo" class="w-100 h-100 bg-dark" autoplay playsinline muted style="object-fit: cover; border: 2px solid white; border-radius: 8px;"></video>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 text-center">
            <div class="btn-group" role="group">
                <button id="muteBtn" class="btn btn-secondary">
                    <i class="fas fa-microphone"></i>
                </button>
                <button id="videoBtn" class="btn btn-secondary">
                    <i class="fas fa-video"></i>
                </button>
                <button id="hangupBtn" class="btn btn-danger">
                    <i class="fas fa-phone-slash"></i> Finalizar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Añadir bibliotecas JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/videocall.js"></script>
<script>
    // Definir baseUrl para uso en videocall.js
    const baseUrl = '<?= BASE_URL ?>';
    
    // Asignar valores a las variables ya declaradas (sin usar const/let)
    window.addEventListener('DOMContentLoaded', function() {
        // Estas asignaciones se hacen después de que videocall.js haya declarado las variables
        if (typeof salaId !== 'undefined') salaId = "<?php echo $sala_id; ?>";
        if (typeof isInitiator !== 'undefined') isInitiator = <?php echo $is_initiator ? 'true' : 'false'; ?>;
        if (typeof isAdmin !== 'undefined') isAdmin = <?php echo $es_admin ? 'true' : 'false'; ?>;
        if (typeof noMedia !== 'undefined') noMedia = <?php echo $sin_media ? 'true' : 'false'; ?>;
    });
</script>

<?php
// Incluir la plantilla footer
include_once '../../includes/footer.php';
?> 