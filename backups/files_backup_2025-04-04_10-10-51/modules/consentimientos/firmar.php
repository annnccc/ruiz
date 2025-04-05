<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once 'funciones.php';

// Verificar si se recibió un token y datos de firma
if (!isset($_POST['token']) || !isset($_POST['firma']) || !isset($_POST['nombre_firmante'])) {
    die(json_encode([
        'exito' => false,
        'mensaje' => 'Parámetros incompletos'
    ]));
}

$token = sanitize($_POST['token']);
$firma = $_POST['firma']; // La firma es una imagen en base64, no se sanitiza
$nombre_firmante = sanitize($_POST['nombre_firmante']);

// Validación básica
if (empty($token) || empty($firma) || empty($nombre_firmante)) {
    die(json_encode([
        'exito' => false,
        'mensaje' => 'Todos los campos son obligatorios'
    ]));
}

// Procesar la firma
$resultado = procesarFirmaConsentimiento($token, $firma, $nombre_firmante);

if ($resultado) {
    echo json_encode([
        'exito' => true,
        'mensaje' => 'Consentimiento firmado correctamente'
    ]);
} else {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al procesar la firma. Por favor, inténtalo de nuevo.'
    ]);
}
?> 