<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar si el método de solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setAlert('danger', 'Método de solicitud no válido');
    redirect(BASE_URL . '/modules/pacientes/list.php');
    exit;
}

// Obtener y validar los datos del formulario
$cita_id = isset($_POST['cita_id']) ? (int)$_POST['cita_id'] : 0;
$paciente_id = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
$nota_id = isset($_POST['nota_id']) ? (int)$_POST['nota_id'] : 0;
$contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';

// Validar que los campos obligatorios estén presentes
if ($cita_id <= 0 || $paciente_id <= 0 || empty($contenido)) {
    setAlert('danger', 'Todos los campos son obligatorios');
    redirect(BASE_URL . "/modules/pacientes/view.php?id=$paciente_id");
    exit;
}

try {
    $db = getDB();
    
    // Verificar si la cita existe y pertenece al paciente
    $stmt = $db->prepare("SELECT * FROM citas WHERE id = :cita_id AND paciente_id = :paciente_id");
    $stmt->bindParam(':cita_id', $cita_id);
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'La cita no existe o no pertenece a este paciente');
        redirect(BASE_URL . "/modules/pacientes/view.php?id=$paciente_id");
        exit;
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    if ($nota_id > 0) {
        // Actualizar nota existente
        $stmt = $db->prepare("
            UPDATE notas_sesion 
            SET contenido = :contenido, 
                fecha_actualizacion = NOW() 
            WHERE id = :nota_id AND cita_id = :cita_id
        ");
        $stmt->bindParam(':contenido', $contenido);
        $stmt->bindParam(':nota_id', $nota_id);
        $stmt->bindParam(':cita_id', $cita_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('No se pudo actualizar la nota');
        }
        
        $mensaje = 'Nota actualizada correctamente';
    } else {
        // Verificar si ya existe una nota para esta cita
        $stmt = $db->prepare("SELECT id FROM notas_sesion WHERE cita_id = :cita_id");
        $stmt->bindParam(':cita_id', $cita_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Actualizar la nota existente aunque no se haya pasado el nota_id
            $nota_existente = $stmt->fetch(PDO::FETCH_ASSOC);
            $nota_id = $nota_existente['id'];
            
            $stmt = $db->prepare("
                UPDATE notas_sesion 
                SET contenido = :contenido, 
                    fecha_actualizacion = NOW() 
                WHERE id = :nota_id
            ");
            $stmt->bindParam(':contenido', $contenido);
            $stmt->bindParam(':nota_id', $nota_id);
            $stmt->execute();
            
            $mensaje = 'Nota actualizada correctamente';
        } else {
            // Crear nueva nota
            $stmt = $db->prepare("
                INSERT INTO notas_sesion (cita_id, contenido, fecha_creacion, fecha_actualizacion) 
                VALUES (:cita_id, :contenido, NOW(), NOW())
            ");
            $stmt->bindParam(':cita_id', $cita_id);
            $stmt->bindParam(':contenido', $contenido);
            $stmt->execute();
            
            $mensaje = 'Nota guardada correctamente';
        }
    }
    
    // Confirmar transacción
    $db->commit();
    
    setAlert('success', $mensaje);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    setAlert('danger', 'Error al guardar la nota: ' . $e->getMessage());
}

// Redireccionar a la página de detalles del paciente
redirect(BASE_URL . "/modules/pacientes/view.php?id=$paciente_id");
?> 