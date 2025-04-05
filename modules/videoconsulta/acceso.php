<?php
/**
 * Módulo de Videoconsulta - Página de acceso con PIN
 * 
 * Esta página permite a los pacientes acceder a una videoconsulta
 * utilizando un PIN de 4 dígitos en lugar de iniciar sesión.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar parámetros
if (!isset($_GET['codigo']) || empty($_GET['codigo'])) {
    die('Enlace no válido. Por favor, utilice el enlace completo enviado a su correo electrónico.');
}

$codigo = $_GET['codigo'];
$error = null;
$videoconsulta = null;

// Verificar si se envió el formulario con el PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin_acceso'])) {
    $pin_ingresado = trim($_POST['pin_acceso']);
    
    // Validar que sea un PIN de 4 dígitos
    if (!preg_match('/^\d{4}$/', $pin_ingresado)) {
        $error = "El PIN debe ser un número de 4 dígitos.";
    } else {
        try {
            // Verificar si el PIN coincide con el de la videoconsulta
            $db = getDB();
            $stmt = $db->prepare("
                SELECT id, pin_acceso, fecha_expiracion
                FROM videoconsultas
                WHERE enlace_acceso = :codigo
                AND pin_acceso = :pin
            ");
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':pin', $pin_ingresado);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si el enlace ha expirado
                $ahora = new DateTime();
                $expiracion = new DateTime($videoconsulta['fecha_expiracion']);
                
                if ($ahora > $expiracion) {
                    $error = "El enlace de acceso ha expirado. Por favor, contacte con su médico para obtener un nuevo enlace.";
                } else {
                    // PIN correcto y enlace vigente, redirigir a la sala
                    header('Location: ' . BASE_URL . '/modules/videoconsulta/sala_invitado.php?id=' . $videoconsulta['id'] . '&pin=' . $pin_ingresado);
                    exit;
                }
            } else {
                $error = "El PIN ingresado no es correcto. Por favor, revise el correo electrónico con su invitación.";
            }
        } catch (PDOException $e) {
            $error = "Error al verificar el acceso: " . $e->getMessage();
        }
    }
}

// Verificar que el enlace sea válido antes de mostrar el formulario
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT v.id, v.fecha, v.hora_inicio, v.fecha_expiracion,
               CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre,
               CONCAT(u.nombre, ' ', u.apellidos) as medico_nombre
        FROM videoconsultas v
        JOIN pacientes p ON v.paciente_id = p.id
        JOIN usuarios u ON v.medico_id = u.id
        WHERE v.enlace_acceso = :codigo
    ");
    $stmt->bindParam(':codigo', $codigo);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        die('El enlace de acceso no es válido o la videoconsulta no existe.');
    }
    
    $info_consulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si el enlace ha expirado
    $ahora = new DateTime();
    $expiracion = new DateTime($info_consulta['fecha_expiracion']);
    
    if ($ahora > $expiracion) {
        die('El enlace de acceso ha expirado. Por favor, contacte con su médico para obtener un nuevo enlace.');
    }
    
} catch (PDOException $e) {
    die('Error al verificar el enlace: ' . $e->getMessage());
}

// Título de la página
$pageTitle = "Acceso a Videoconsulta";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Videoconsulta | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pin-container {
            max-width: 450px;
            width: 100%;
            padding: 40px 15px;
        }
        .pin-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
            font-weight: 500;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 180px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="pin-container">
        <div class="logo-container">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" class="logo">
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="text-center mb-4">Acceso a Videoconsulta</h2>
                
                <div class="text-center mb-4">
                    <p class="text-secondary mb-0">Videoconsulta programada para</p>
                    <p class="fw-bold fs-5 mb-2"><?= htmlspecialchars($info_consulta['paciente_nombre']) ?></p>
                    <p class="mb-0">Fecha: <span class="fw-bold"><?= date('d/m/Y', strtotime($info_consulta['fecha'])) ?></span></p>
                    <p>Hora: <span class="fw-bold"><?= date('H:i', strtotime($info_consulta['hora_inicio'])) ?></span></p>
                    <p>Con: <span class="fw-bold"><?= htmlspecialchars($info_consulta['medico_nombre']) ?></span></p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?= BASE_URL ?>/modules/videoconsulta/acceso.php?codigo=<?= $codigo ?>">
                    <div class="mb-4">
                        <label for="pin_acceso" class="form-label">Ingrese el código PIN de 4 dígitos que recibió en su correo</label>
                        <input type="text" class="form-control pin-input" id="pin_acceso" name="pin_acceso" 
                               maxlength="4" placeholder="• • • •" required autocomplete="off"
                               pattern="[0-9]{4}" inputmode="numeric">
                        <div class="form-text text-center">El PIN fue enviado a su correo electrónico</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-rounded me-1">videocam</span> Acceder a la Videoconsulta
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <p class="text-center mt-4 text-secondary">
            <small>Si tiene problemas para acceder, contacte directamente con su médico.</small>
        </p>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mejorar la experiencia del campo PIN
        const pinInput = document.getElementById('pin_acceso');
        
        // Solo permitir números en el campo del PIN
        pinInput.addEventListener('keypress', function(e) {
            const key = String.fromCharCode(e.which);
            if (!/[0-9]/.test(key)) {
                e.preventDefault();
            }
        });
        
        // Enfocar el campo de PIN automáticamente
        pinInput.focus();
    });
    </script>
</body>
</html> 