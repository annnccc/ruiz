<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Requiere autenticación y derechos de administrador
requiereLogin();
if (!esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta página.');
    redirect(BASE_URL);
}

$resultado_instalacion = false;
$mensaje_error = '';

try {
    $db = getDB();
    
    // Cargar el esquema SQL desde el archivo
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Ejecutar las consultas SQL
    $db->exec($sql);
    
    // Insertar un modelo de consentimiento predeterminado si no existe ninguno
    $stmt = $db->query("SELECT COUNT(*) FROM consentimientos_modelos");
    if ($stmt->fetchColumn() == 0) {
        $contenido_predeterminado = '
        <h2>CONSENTIMIENTO INFORMADO</h2>
        <p><strong>Nombre del procedimiento:</strong> [NOMBRE DEL PROCEDIMIENTO]</p>
        
        <p>En, a {DIA} de {MES} de {AÑO}</p>
        
        <p>Yo, {NOMBRE_PACIENTE}, con DNI {DNI_PACIENTE}, con domicilio en {DIRECCION}, declaro que he sido informado/a de manera comprensible por el Dr./Dra. {NOMBRE_MEDICO} sobre:</p>
        
        <ul>
            <li>La naturaleza y propósito del procedimiento médico propuesto, que consiste en [DESCRIPCIÓN DEL PROCEDIMIENTO].</li>
            <li>Los beneficios razonablemente esperados del procedimiento y las consecuencias de no realizarlo.</li>
            <li>Los riesgos y complicaciones más frecuentes del procedimiento, que incluyen [RIESGOS].</li>
            <li>Las alternativas razonables al procedimiento propuesto.</li>
            <li>Mi derecho a rechazar el tratamiento en cualquier momento.</li>
        </ul>
        
        <p>He comprendido la información recibida, he podido formular todas las preguntas que he considerado necesarias, y me han sido resueltas todas mis dudas.</p>
        
        <p>En consecuencia, doy mi consentimiento para que se realice el procedimiento propuesto.</p>
        
        <p>Fecha: {FECHA}</p>
        
        <div class="firma-container">
            <p>Firma del paciente:</p>
            <div class="firma-placeholder"></div>
        </div>
        ';
        
        $stmt = $db->prepare("
            INSERT INTO consentimientos_modelos 
            (nombre, descripcion, contenido, activo) 
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([
            'Consentimiento General', 
            'Modelo de consentimiento informado general para procedimientos médicos', 
            $contenido_predeterminado
        ]);
    }
    
    setAlert('success', 'Módulo de Consentimientos Informados instalado correctamente.');
    $resultado_instalacion = true;
} catch (PDOException $e) {
    setAlert('danger', 'Error al instalar el módulo: ' . $e->getMessage());
    $mensaje_error = $e->getMessage();
}

// Preparar datos para la página
$title = "Instalación de Consentimientos Informados";
$breadcrumbs = [
    'Consentimientos' => '../consentimientos/listar.php',
    'Instalación' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-5">
                    <h2 class="mb-4">Instalación de Consentimientos Informados</h2>
                    
                    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
                    
                    <?php if ($resultado_instalacion): ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="text-success">
                                    <span class="material-symbols-rounded" style="font-size: 5rem;">check_circle</span>
                                </div>
                            </div>
                            <h4 class="text-success mb-3">Instalación completada</h4>
                            <p>Las tablas necesarias han sido creadas correctamente.</p>
                            <p>Serás redirigido a la página de gestión en 3 segundos...</p>
                            <meta http-equiv="refresh" content="3;url=<?= BASE_URL ?>/modules/consentimientos/listar.php">
                        </div>
                        <div class="spinner-border text-primary mb-4" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="text-danger">
                                    <span class="material-symbols-rounded" style="font-size: 5rem;">error</span>
                                </div>
                            </div>
                            <h4 class="text-danger mb-3">Error en la instalación</h4>
                            <p>No se pudieron crear las tablas necesarias.</p>
                            <p class="text-danger"><?= htmlspecialchars($mensaje_error) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/modules/consentimientos/listar.php" class="btn btn-primary">
                            <span class="material-symbols-rounded me-2">list</span>Ir a Consentimientos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar el buffer de salida y mostrar el contenido
endPageContent();
?> 