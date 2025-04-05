<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si hay sesión activa
checkSession();

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setAlert('danger', 'Método de solicitud no válido');
    header('Location: list.php');
    exit;
}

// Verificar los datos necesarios
if (!isset($_POST['cita_id']) || empty($_POST['cita_id']) ||
    !isset($_POST['titulo']) || empty($_POST['titulo']) ||
    !isset($_POST['contenido']) || empty($_POST['contenido'])) {
    
    setAlert('danger', 'Todos los campos son obligatorios');
    
    if (isset($_POST['cita_id'])) {
        header('Location: view.php?id=' . $_POST['cita_id']);
    } else {
        header('Location: list.php');
    }
    exit;
}

// Obtener y sanear datos
$cita_id = (int)$_POST['cita_id'];
$titulo = trim($_POST['titulo']);
$contenido = $_POST['contenido']; // No filtrar HTML del TinyMCE

// Guardar en la base de datos
try {
    $db = getDB();
    
    // Verificar si la cita existe
    $check_query = "SELECT id FROM citas WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $cita_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        setAlert('danger', 'La cita no existe');
        header('Location: list.php');
        exit;
    }
    
    // Insertar la nueva nota
    $query = "INSERT INTO notas_sesion (cita_id, titulo, contenido, fecha_creacion, usuario_id) 
              VALUES (:cita_id, :titulo, :contenido, NOW(), :usuario_id)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cita_id', $cita_id, PDO::PARAM_INT);
    $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
    $stmt->bindParam(':contenido', $contenido, PDO::PARAM_STR);
    $stmt->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        setAlert('success', 'Nota de sesión guardada correctamente');
    } else {
        setAlert('danger', 'Error al guardar la nota de sesión');
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error en la base de datos: ' . $e->getMessage());
}

// Redirigir de vuelta a la vista de la cita
header('Location: view.php?id=' . $cita_id);
exit;
?>
