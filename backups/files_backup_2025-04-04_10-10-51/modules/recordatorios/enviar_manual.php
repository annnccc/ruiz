<?php
/**
 * Script para enviar recordatorios manualmente a un paciente específico
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once 'funciones.php';

// Verificar si el usuario ha iniciado sesión
requiereLogin();

// Verificar si se proporcionó un ID de cita
if (!isset($_GET['cita_id']) || empty($_GET['cita_id'])) {
    setAlert('danger', 'ID de cita no proporcionado');
    redirect(BASE_URL . '/modules/citas/list.php');
}

$cita_id = (int)$_GET['cita_id'];

// Verificar que la cita existe y obtener datos básicos
try {
    $db = getDB();
    
    $query = "SELECT c.*, p.nombre, p.apellidos, p.email
              FROM citas c
              JOIN pacientes p ON c.paciente_id = p.id
              WHERE c.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $cita_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'Cita no encontrada');
        redirect(BASE_URL . '/modules/citas/list.php');
    }
    
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar que el paciente tiene email
    if (empty($cita['email'])) {
        setAlert('danger', 'El paciente no tiene dirección de email registrada');
        redirect(BASE_URL . '/modules/citas/view.php?id=' . $cita_id);
    }
    
    // Enviar el recordatorio
    $resultado = enviarRecordatorioManual($cita_id);
    
    if ($resultado) {
        setAlert('success', 'Recordatorio enviado correctamente a ' . $cita['nombre'] . ' ' . $cita['apellidos'] . ' (' . $cita['email'] . ')');
    } else {
        setAlert('danger', 'Error al enviar el recordatorio. Verifique la configuración SMTP.');
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al procesar la solicitud: ' . $e->getMessage());
}

// Redirigir de vuelta a la página de detalles de la cita
redirect(BASE_URL . '/modules/citas/view.php?id=' . $cita_id); 