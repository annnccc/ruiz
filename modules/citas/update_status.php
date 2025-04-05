<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si el método de solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setAlert('danger', 'Método de solicitud no válido');
    redirect(BASE_URL . '/modules/citas/list.php');
    exit;
}

// Obtener y validar los datos del formulario
$cita_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

// Validar que los campos obligatorios estén presentes
if ($cita_id <= 0 || empty($estado)) {
    setAlert('danger', 'ID de cita o estado no válido');
    redirect(BASE_URL . '/modules/citas/list.php');
    exit;
}

// Validar que el estado sea uno de los permitidos
if (!in_array($estado, ['pendiente', 'completada', 'cancelada'])) {
    setAlert('danger', 'Estado de cita no válido');
    redirect(BASE_URL . '/modules/citas/list.php');
    exit;
}

try {
    $db = getDB();
    
    // Verificar si la cita existe
    $stmt = $db->prepare("SELECT * FROM citas WHERE id = :id");
    $stmt->bindParam(':id', $cita_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'Cita no encontrada');
        redirect(BASE_URL . '/modules/citas/list.php');
        exit;
    }
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar el estado de la cita
    $stmt = $db->prepare("UPDATE citas SET estado = :estado WHERE id = :id");
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':id', $cita_id);
    $stmt->execute();
    
    // Mensaje según el estado
    $mensaje = '';
    switch ($estado) {
        case 'completada':
            $mensaje = 'Cita marcada como completada correctamente';
            break;
        case 'cancelada':
            $mensaje = 'Cita cancelada correctamente';
            break;
        case 'pendiente':
            $mensaje = 'Cita marcada como pendiente correctamente';
            break;
    }
    
    setAlert('success', $mensaje);
    
} catch (Exception $e) {
    setAlert('danger', 'Error al actualizar el estado de la cita: ' . $e->getMessage());
}

// Redireccionar a la página de detalles de la cita
redirect(BASE_URL . "/modules/citas/view.php?id=$cita_id");
?> 