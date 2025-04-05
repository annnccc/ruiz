<?php
require_once '../../includes/config.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'ID de servicio no válido.');
    header("Location: " . BASE_URL . "/modules/servicios/list.php");
    exit;
}

$id = intval($_GET['id']);

try {
    // Comprobar si el servicio está siendo utilizado en alguna cita
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE servicio_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        // El servicio está siendo utilizado en citas
        setAlert('warning', 'No se puede eliminar este servicio porque está siendo utilizado en ' . $result['total'] . ' cita(s).');
        header("Location: " . BASE_URL . "/modules/servicios/list.php");
        exit;
    }
    
    // Si no está siendo utilizado, podemos eliminarlo
    $stmt = $db->prepare("DELETE FROM servicios WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        setAlert('success', 'Servicio eliminado correctamente.');
    } else {
        setAlert('danger', 'Error al eliminar el servicio.');
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error de base de datos: ' . $e->getMessage());
}

// Redirigir a la lista
header("Location: " . BASE_URL . "/modules/servicios/list.php");
exit;
?> 