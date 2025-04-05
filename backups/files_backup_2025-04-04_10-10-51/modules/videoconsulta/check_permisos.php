<?php
/**
 * Módulo de Videoconsulta - Verificación de permisos
 * 
 * Esta página verifica que el navegador tenga permisos de cámara y micrófono
 * antes de entrar a la sala de videoconsulta.
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

// Verificar parámetro de ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php');
    exit;
}

$videoconsulta_id = $_GET['id'];

// Obtener información del usuario actual
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : 'medico';
$es_admin = isset($_SESSION['usuario_rol']) && ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'superadmin');

// Verificar si el usuario tiene acceso a esta videoconsulta
try {
    $db = getDB();
    
    if ($es_admin) {
        // Los administradores tienen acceso a todas las videoconsultas
        $stmt = $db->prepare("SELECT * FROM videoconsultas WHERE id = :id");
        $stmt->bindParam(':id', $videoconsulta_id);
    } else if ($tipo_usuario == 'paciente') {
        // Pacientes solo pueden acceder a sus propias videoconsultas
        $stmt = $db->prepare("SELECT * FROM videoconsultas WHERE id = :id AND paciente_id = :usuario_id");
        $stmt->bindParam(':id', $videoconsulta_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
    } else {
        // Médicos solo pueden acceder a videoconsultas donde son el médico
        $stmt = $db->prepare("SELECT * FROM videoconsultas WHERE id = :id AND medico_id = :usuario_id");
        $stmt->bindParam(':id', $videoconsulta_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
    }
    
    $stmt->execute();
    $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoconsulta) {
        $_SESSION['error'] = "No tienes acceso a esta videoconsulta.";
        header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php');
        exit;
    }
    
    // Obtener el ID de sala para la videoconsulta
    $sala_id = $videoconsulta['id'];
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al verificar acceso: " . $e->getMessage();
    header('Location: ' . BASE_URL . '/modules/videoconsulta/index.php');
    exit;
}

// Título de la página
$pageTitle = "Verificación de Permisos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Permisos | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .permission-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background-color: white;
        }
        
        .permission-icon {
            font-size: 64px;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        
        .status-icon {
            font-size: 36px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .status-pending {
            color: #ffc107;
        }
        
        .status-success {
            color: #198754;
        }
        
        .status-error {
            color: #dc3545;
        }
        
        .permission-status {
            margin: 20px 0;
            text-align: left;
            max-width: 300px;
            margin: 20px auto;
        }
        
        .status-item {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="permission-container">
            <div class="permission-icon">
                <span class="material-symbols-rounded">videocam</span>
            </div>
            
            <h2>Verificación de Permisos</h2>
            <p class="lead">Antes de acceder a la videoconsulta, necesitamos verificar los permisos de su cámara y micrófono.</p>
            
            <div class="permission-status">
                <div class="status-item" id="camara-status">
                    <span class="status-icon status-pending material-symbols-rounded">pending</span>
                    <span>Cámara: Verificando...</span>
                </div>
                <div class="status-item" id="micro-status">
                    <span class="status-icon status-pending material-symbols-rounded">pending</span>
                    <span>Micrófono: Verificando...</span>
                </div>
            </div>
            
            <div id="error-container" class="alert alert-danger d-none">
                <strong>Error:</strong> <span id="error-message"></span>
                <p class="mt-2">Para permitir el acceso:</p>
                <ol class="text-start">
                    <li>Haga clic en el icono de cámara/micrófono en la barra de direcciones</li>
                    <li>Seleccione "Permitir" para ambos permisos</li>
                    <li>Recargue esta página</li>
                </ol>
            </div>
            
            <div class="mt-4">
                <button id="reintentar-btn" class="btn btn-outline-primary me-2 d-none">
                    <span class="material-symbols-rounded me-1">refresh</span> Reintentar
                </button>
                
                <a id="acceder-btn" href="sala.php?id=<?= $videoconsulta_id ?>" 
                   class="btn btn-primary d-none">
                    <span class="material-symbols-rounded me-1">login</span> Acceder a la Videoconsulta
                </a>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const camaraStatus = document.getElementById('camara-status');
        const microStatus = document.getElementById('micro-status');
        const errorContainer = document.getElementById('error-container');
        const errorMessage = document.getElementById('error-message');
        const reintentarBtn = document.getElementById('reintentar-btn');
        const accederBtn = document.getElementById('acceder-btn');
        
        // Comprobar permisos de cámara y micrófono
        checkMediaPermissions();
        
        // Botón de reintentar
        reintentarBtn.addEventListener('click', function() {
            // Reiniciar el estado
            camaraStatus.innerHTML = '<span class="status-icon status-pending material-symbols-rounded">pending</span><span>Cámara: Verificando...</span>';
            microStatus.innerHTML = '<span class="status-icon status-pending material-symbols-rounded">pending</span><span>Micrófono: Verificando...</span>';
            errorContainer.classList.add('d-none');
            reintentarBtn.classList.add('d-none');
            accederBtn.classList.add('d-none');
            
            // Volver a comprobar permisos
            checkMediaPermissions();
        });
        
        function checkMediaPermissions() {
            // Verificar si el navegador soporta getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showError('Su navegador no soporta acceso a cámara y micrófono. Por favor, actualice su navegador o use Chrome/Firefox.');
                return;
            }
            
            // Comprobar permisos de cámara y micrófono
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(function(stream) {
                    // Permisos concedidos
                    updateStatus(camaraStatus, true, 'Cámara');
                    updateStatus(microStatus, true, 'Micrófono');
                    
                    // Detener las pistas de media
                    stream.getTracks().forEach(track => track.stop());
                    
                    // Mostrar botón de acceso
                    accederBtn.classList.remove('d-none');
                    
                    // Redirigir automáticamente después de 3 segundos
                    setTimeout(function() {
                        // Agregar parámetros adicionales para indicar el rol del usuario
                        window.location.href = "sala.php?id=<?= $sala_id ?>&admin=<?= $es_admin ? '1' : '0' ?>&role=<?= $tipo_usuario ?>";
                    }, 3000);
                })
                .catch(function(err) {
                    console.error('Error accediendo a los dispositivos de media: ', err);
                    
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        // Permisos denegados
                        updateStatus(camaraStatus, false, 'Cámara');
                        updateStatus(microStatus, false, 'Micrófono');
                        showError('Debe permitir el acceso a su cámara y micrófono para usar la videoconsulta.');
                    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                        // Dispositivos no encontrados
                        showError('No se encontró cámara y/o micrófono. Por favor, conecte estos dispositivos y vuelva a intentarlo.');
                    } else {
                        // Otro error
                        showError('Error al acceder a los dispositivos: ' + err.message);
                    }
                    
                    // Mostrar botón de reintentar
                    reintentarBtn.classList.remove('d-none');
                    
                    // También mostrar botón para continuar sin permisos si es admin (pueden observar sin cámara)
                    if (<?= $es_admin ? 'true' : 'false' ?>) {
                        accederBtn.textContent = "Continuar sin cámara ni micrófono";
                        accederBtn.href = "sala.php?id=<?= $sala_id ?>&admin=1&role=<?= $tipo_usuario ?>&no_media=1";
                        accederBtn.classList.remove('d-none');
                    }
                });
        }
        
        function updateStatus(element, success, deviceName) {
            if (success) {
                element.innerHTML = `<span class="status-icon status-success material-symbols-rounded">check_circle</span><span>${deviceName}: Permitido</span>`;
            } else {
                element.innerHTML = `<span class="status-icon status-error material-symbols-rounded">error</span><span>${deviceName}: Denegado</span>`;
            }
        }
        
        function showError(message) {
            errorMessage.textContent = message;
            errorContainer.classList.remove('d-none');
        }
    });
    </script>
</body>
</html> 