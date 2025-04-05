<?php
require_once '../../includes/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setAlert('danger', 'Método no permitido.');
    header("Location: " . BASE_URL . "/modules/facturacion/list.php");
    exit;
}

// Verificar que se envió un ID de cita
if (!isset($_POST['cita_id']) || !is_numeric($_POST['cita_id'])) {
    setAlert('danger', 'ID de cita no válido.');
    header("Location: " . BASE_URL . "/modules/facturacion/list.php");
    exit;
}

// Obtener los datos del formulario
$cita_id = intval($_POST['cita_id']);
$fecha_pago = trim($_POST['fecha_pago'] ?? '');
$forma_pago = trim($_POST['forma_pago'] ?? '');

// Validar datos
$errors = [];

if (empty($fecha_pago)) {
    $errors[] = "La fecha de pago es obligatoria.";
}

if (empty($forma_pago)) {
    $errors[] = "La forma de pago es obligatoria.";
}

// Si hay errores, redirigir con mensaje de error
if (!empty($errors)) {
    setAlert('danger', 'Error al procesar el pago: ' . implode(' ', $errors));
    header("Location: " . BASE_URL . "/modules/facturacion/list.php");
    exit;
}

try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Primero verificamos que la cita exista y no esté ya pagada
    $stmt = $db->prepare("SELECT id, fecha, pagada FROM citas WHERE id = :id");
    $stmt->bindParam(':id', $cita_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita) {
        setAlert('danger', 'La cita seleccionada no existe.');
        header("Location: " . BASE_URL . "/modules/facturacion/list.php");
        exit;
    }
    
    if ($cita['pagada'] == 1) {
        setAlert('warning', 'Esta cita ya está marcada como pagada.');
        header("Location: " . BASE_URL . "/modules/facturacion/list.php");
        exit;
    }
    
    // Actualizar el estado de pago de la cita
    $stmt = $db->prepare("UPDATE citas SET 
                           pagada = 1,
                           fecha_pago = :fecha_pago,
                           forma_pago = :forma_pago
                          WHERE id = :id");
                          
    $stmt->bindParam(':fecha_pago', $fecha_pago, PDO::PARAM_STR);
    $stmt->bindParam(':forma_pago', $forma_pago, PDO::PARAM_STR);
    $stmt->bindParam(':id', $cita_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Obtener mes y año de la cita para redireccionar a la página correcta
        $cita_date = new DateTime($cita['fecha']);
        $cita_year = $cita_date->format('Y');
        $cita_month = $cita_date->format('n');
        
        setAlert('success', 'Pago registrado correctamente.');
        header("Location: " . BASE_URL . "/modules/facturacion/list.php?year={$cita_year}&month={$cita_month}");
        exit;
    } else {
        setAlert('danger', 'Error al registrar el pago.');
        header("Location: " . BASE_URL . "/modules/facturacion/list.php");
        exit;
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error de base de datos: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/modules/facturacion/list.php");
    exit;
}
?> 