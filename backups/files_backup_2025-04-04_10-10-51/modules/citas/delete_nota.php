<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si hay sesión activa
checkSession();

// Verificar si se proporcionaron los parámetros necesarios
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['cita_id']) || empty($_GET['cita_id'])) {
    setAlert('danger', 'Parámetros incorrectos para eliminar la nota');
    header('Location: list.php');
    exit;
}

$nota_id = (int)$_GET['id'];
$cita_id = (int)$_GET['cita_id'];

// Eliminar la nota de la base de datos
try {
    $db = getDB();
    
    // Verificar si la nota existe y pertenece a la cita indicada
    $check_query = "SELECT id FROM notas_sesion WHERE id = :id AND cita_id = :cita_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $nota_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':cita_id', $cita_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        setAlert('danger', 'La nota no existe o no pertenece a esta cita');
        header('Location: view.php?id=' . $cita_id);
        exit;
    }
    
    // Eliminar la nota
    $query = "DELETE FROM notas_sesion WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $nota_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        setAlert('success', 'Nota de sesión eliminada correctamente');
    } else {
        setAlert('danger', 'Error al eliminar la nota de sesión');
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error en la base de datos: ' . $e->getMessage());
}

// Redirigir de vuelta a la vista de la cita
header('Location: view.php?id=' . $cita_id);
exit;
?>
