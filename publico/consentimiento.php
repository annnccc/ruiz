<?php
// Usar la constante ROOT_PATH para rutas absolutas si está definida
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__FILE__)));
}

// Incluir archivos de configuración y funciones
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/modules/consentimientos/funciones.php';

// Verificar que se ha proporcionado un token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo '<div class="alert alert-danger">Error: No se ha proporcionado un token válido.</div>';
    exit;
}

$token = $_GET['token'];
$consentimiento = obtenerConsentimientoPorToken($token);
$mensajeError = '';
$exito = false;

// Procesar la firma si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firma']) && isset($_POST['nombre_firmante'])) {
    $firma = $_POST['firma'];
    $nombre_firmante = trim($_POST['nombre_firmante']);
    
    if (empty($firma)) {
        $mensajeError = 'Por favor, firme el documento antes de enviarlo.';
    } elseif (empty($nombre_firmante)) {
        $mensajeError = 'Por favor, ingrese su nombre completo.';
    } else {
        // Registrar la firma
        $resultado = registrarFirmaConsentimiento($consentimiento['id'], $firma, $nombre_firmante);
        
        if ($resultado) {
            $exito = true;
        } else {
            $mensajeError = 'Ha ocurrido un error al registrar la firma. Por favor, intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consentimiento Informado</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .signature-pad-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            margin-bottom: 15px;
            position: relative;
        }
        #signature-pad {
            width: 100%;
            height: 200px;
        }
        .clear-button {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 100;
            background-color: rgba(255, 255, 255, 0.8);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-height: 80px;
        }
        .documento-container {
            background-color: #fff;
            padding: 0;
            border-radius: 5px;
            box-shadow: none;
            margin-bottom: 30px;
        }
        .estado-firma {
            text-align: center;
            margin: 50px 0;
        }
        .icono-grande {
            font-size: 5rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" class="img-fluid">
        </div>
        
        <?php if (!$consentimiento): ?>
        <!-- Token inválido o caducado -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="text-danger mb-4">
                            <span class="material-symbols-rounded icono-grande">error</span>
                        </div>
                        <h2 class="mb-3">Enlace no válido</h2>
                        <p class="mb-4">El enlace que ha utilizado no es válido o ha caducado. Por favor, solicite un nuevo enlace a su médico.</p>
                        <a href="<?= BASE_URL ?>" class="btn btn-primary">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($exito): ?>
        <!-- Firma registrada con éxito -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="text-success mb-4">
                            <span class="material-symbols-rounded icono-grande">check_circle</span>
                        </div>
                        <h2 class="mb-3">¡Gracias por su firma!</h2>
                        <p class="mb-4">El consentimiento informado ha sido firmado correctamente.</p>
                        <p>Fecha y hora de la firma: <strong><?= date('d/m/Y H:i') ?></strong></p>
                        <a href="<?= BASE_URL ?>" class="btn btn-primary mt-3">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Mostrar el documento y formulario de firma -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">description</span>
                            <?= htmlspecialchars($consentimiento['modelo_nombre']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <p><strong>Paciente:</strong> <?= htmlspecialchars($consentimiento['paciente_nombre'] . ' ' . $consentimiento['paciente_apellidos']) ?></p>
                            <p><strong>Fecha:</strong> <?= date('d/m/Y') ?></p>
                        </div>
                        
                        <div class="documento-container">
                            <?php 
                            // Usar la función de procesamiento con variables adicionales
                            echo procesarContenidoConsentimiento($consentimiento['contenido'], $consentimiento);
                            ?>
                        </div>
                        
                        <?php if (!empty($mensajeError)): ?>
                        <div class="alert alert-danger">
                            <?= $mensajeError ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" id="firma-form">
                            <div class="mb-3">
                                <label for="nombre_firmante" class="form-label">Nombre completo del firmante</label>
                                <input type="text" class="form-control" id="nombre_firmante" name="nombre_firmante" required 
                                       value="<?= htmlspecialchars($consentimiento['paciente_nombre'] . ' ' . $consentimiento['paciente_apellidos']) ?>">
                                <div class="form-text">Escriba su nombre completo tal como aparece en su DNI/NIF.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Firma digital</label>
                                <div class="signature-pad-container">
                                    <canvas id="signature-pad"></canvas>
                                    <button type="button" id="clear-button" class="btn btn-sm btn-outline-secondary clear-button">
                                        <span class="material-symbols-rounded">delete</span> Borrar
                                    </button>
                                </div>
                                <input type="hidden" name="firma" id="firma-data">
                                <div class="form-text">Utilice el ratón o el dedo para firmar en el recuadro superior.</div>
                            </div>
                            
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <span class="material-symbols-rounded">info</span>
                                    </div>
                                    <div>
                                        <p class="mb-0">Al firmar este documento, usted confirma que ha leído y entendido su contenido, y acepta las condiciones especificadas.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg fw-bold" id="submit-button" style="padding: 12px 20px; font-size: 1.1rem;">
                                    <span class="material-symbols-rounded me-2">draw</span>Firmar Documento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    
    <?php if ($consentimiento && !$exito): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar el pad de firma
            const canvas = document.getElementById('signature-pad');
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)'
            });
            
            // Ajustar el tamaño del canvas
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear(); // Limpiar firma cuando se redimensiona
            }
            
            window.addEventListener("resize", resizeCanvas);
            resizeCanvas();
            
            // Botón para limpiar firma
            document.getElementById('clear-button').addEventListener('click', function() {
                signaturePad.clear();
            });
            
            // Capturar la firma al enviar
            document.getElementById('firma-form').addEventListener('submit', function(e) {
                if (signaturePad.isEmpty()) {
                    e.preventDefault();
                    alert('Por favor, firme el documento antes de enviarlo.');
                    return false;
                }
                
                document.getElementById('firma-data').value = signaturePad.toDataURL();
            });
        });
    </script>
    <?php endif; ?>
</body>
</html> 