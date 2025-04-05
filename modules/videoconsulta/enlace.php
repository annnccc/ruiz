<?php
/**
 * Módulo de Videoconsulta - Acceso mediante enlace temporal
 * 
 * Esta página permite acceder a la videoconsulta usando un enlace aleatorio
 * que expira después de 1 hora desde su creación.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar parámetro de enlace
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Enlace de acceso no proporcionado. Por favor, use el enlace completo enviado por correo electrónico.');
}

$enlace_acceso = $_GET['id'];

// Verificar que el enlace existe y no ha expirado
try {
    $db = getDB();
    $ahora = date('Y-m-d H:i:s');
    
    $stmt = $db->prepare("
        SELECT id, codigo_acceso, fecha_expiracion 
        FROM videoconsultas 
        WHERE enlace_acceso = :enlace_acceso 
        AND estado != 'cancelada'
    ");
    $stmt->bindParam(':enlace_acceso', $enlace_acceso, PDO::PARAM_STR);
    $stmt->execute();
    
    // Verificar si el enlace existe
    if ($stmt->rowCount() === 0) {
        die('El enlace proporcionado no es válido o la videoconsulta ha sido cancelada.');
    }
    
    $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si el enlace ha expirado
    if ($videoconsulta['fecha_expiracion'] < $ahora) {
        die('<div class="alert alert-danger text-center" style="margin: 100px auto; max-width: 500px; padding: 30px;">
            <h3><i class="material-symbols-rounded" style="font-size: 48px; margin-bottom: 20px;">timer_off</i><br>Enlace expirado</h3>
            <p>El enlace de acceso a esta videoconsulta ha expirado.</p>
            <p>Este enlace es válido por 1 hora después de su creación.</p>
            <p>Por favor, contacte con su médico para obtener un nuevo enlace.</p>
        </div>');
    }
    
    // Redirigir a la página de verificación de permisos
    header('Location: ' . BASE_URL . '/modules/videoconsulta/check_permisos_invitado.php?codigo=' . $videoconsulta['codigo_acceso']);
    exit;
    
} catch (PDOException $e) {
    die('Error al verificar el enlace: ' . $e->getMessage());
} 