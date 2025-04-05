<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar que el paciente esté autenticado
if (!isset($_SESSION['paciente_id'])) {
    header('Location: ' . BASE_URL . '/modules/portal_paciente/login.php');
    exit;
}

// Obtener el ID de la cita
$citaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($citaId <= 0) {
    setAlert('danger', 'ID de cita no válido.');
    header('Location: ' . BASE_URL . '/modules/portal_paciente/index.php');
    exit;
}

try {
    $db = getDB();
    
    // Verificar que la cita pertenezca al paciente
    $query = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.motivo, c.estado, 
             p.nombre as paciente_nombre, p.apellidos as paciente_apellidos,
             'Profesional' as medico_nombre, 'Asignado' as medico_apellidos
             FROM citas c
             JOIN pacientes p ON c.paciente_id = p.id
             WHERE c.id = :cita_id AND c.paciente_id = :paciente_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cita_id', $citaId, PDO::PARAM_INT);
    $stmt->bindParam(':paciente_id', $_SESSION['paciente_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'La cita solicitada no existe o no tiene acceso a ella.');
        header('Location: ' . BASE_URL . '/modules/portal_paciente/index.php');
        exit;
    }
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos de la cita: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/modules/portal_paciente/index.php');
    exit;
}

// Título de la página
$title = "Sincronizar Cita - Portal de Pacientes";

// Iniciar buffer de salida
ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-plus me-2 text-primary"></i>Añadir Cita a Calendario
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <h6>Detalles de la Cita:</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <p class="mb-1">
                                <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($cita['fecha'])) ?>
                            </p>
                            <p class="mb-1">
                                <strong>Hora:</strong> <?= $cita['hora_inicio'] ?> - <?= $cita['hora_fin'] ?>
                            </p>
                            <p class="mb-1">
                                <strong>Médico:</strong> Dr. <?= htmlspecialchars($cita['medico_apellidos'] . ', ' . $cita['medico_nombre']) ?>
                            </p>
                            <p class="mb-0">
                                <strong>Motivo:</strong> <?= htmlspecialchars($cita['motivo']) ?>
                            </p>
                        </div>
                        
                        <p>Seleccione cómo desea añadir esta cita a su calendario:</p>
                    </div>
                    
                    <div class="d-grid gap-3">
                        <a href="<?= BASE_URL ?>/modules/calendario/export_ical.php?id=<?= $cita['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Descargar como iCalendar (.ics)
                            <div class="small text-white-50">Compatible con Apple Calendar, Outlook, etc.</div>
                        </a>
                        
                        <a href="<?= BASE_URL ?>/modules/calendario/export_ical.php?id=<?= $cita['id'] ?>&format=google" class="btn btn-outline-primary">
                            <i class="fab fa-google me-2"></i>Añadir a Google Calendar
                        </a>
                        
                        <a href="<?= BASE_URL ?>/modules/portal_paciente/index.php" class="btn btn-link text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Portal
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

// Incluir el layout para el portal de pacientes
include ROOT_PATH . '/includes/layouts/portal.php';
?> 