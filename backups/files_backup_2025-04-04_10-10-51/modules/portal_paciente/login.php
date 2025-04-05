<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Si ya hay una sesión de paciente activa, redirigir al portal
if (isset($_SESSION['paciente_id'])) {
    header('Location: ' . BASE_URL . '/modules/portal_paciente/index.php');
    exit;
}

// Procesar formulario de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $documento = $_POST['documento'] ?? '';
    
    if (empty($email) || empty($documento)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            $db = getDB();
            
            // Verificar datos de paciente
            $query = "SELECT id, nombre, apellidos, email, telefono 
                     FROM pacientes 
                     WHERE email = :email AND documento_identidad = :documento
                     LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':documento', $documento);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Iniciar sesión del paciente
                $_SESSION['paciente_id'] = $paciente['id'];
                $_SESSION['paciente_nombre'] = $paciente['nombre'];
                $_SESSION['paciente_apellidos'] = $paciente['apellidos'];
                $_SESSION['paciente_email'] = $paciente['email'];
                
                // Registrar acceso
                $logQuery = "INSERT INTO accesos_pacientes (paciente_id, fecha_acceso, ip) 
                            VALUES (:paciente_id, NOW(), :ip)";
                $logStmt = $db->prepare($logQuery);
                $logStmt->bindParam(':paciente_id', $paciente['id']);
                $logStmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
                $logStmt->execute();
                
                // Redireccionar al portal
                header('Location: ' . BASE_URL . '/modules/portal_paciente/index.php');
                exit;
            } else {
                $error = 'Los datos de acceso no son correctos. Por favor, verifique la información.';
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema. Por favor, intente más tarde.';
            // Registrar el error real para análisis
            error_log('Error en login de paciente: ' . $e->getMessage());
        }
    }
}

// Título de la página
$title = "Portal de Pacientes - Acceso";

// Iniciar buffer de salida
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo Clínica" class="mb-4" style="max-height: 60px;">
                        <h2 class="h4 mb-1">Portal de Pacientes</h2>
                        <p class="text-muted small">Acceda a su información y citas médicas</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Su correo electrónico" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="documento" class="form-label">Documento de Identidad</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="documento" name="documento" placeholder="Su documento de identidad" required>
                            </div>
                            <div class="form-text small text-muted">Ingrese su DNI/NIE sin espacios ni guiones.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Acceder
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0 small text-muted">¿Problemas para acceder? Contacte con la clínica.</p>
                        <a href="<?= BASE_URL ?>" class="btn btn-link btn-sm text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Volver al sitio principal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Obtener el contenido del buffer y limpiarlo
$content = ob_get_clean();

// CSS adicional
$extra_css = '';

// JavaScript adicional
$extra_js = '';

// Incluir el layout principal (público, sin sidebar)
include ROOT_PATH . '/includes/layouts/public.php';
?> 