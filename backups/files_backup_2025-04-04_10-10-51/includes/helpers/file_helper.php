<?php
/**
 * Helper para funciones de manejo de archivos
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Guarda una imagen subida y devuelve la ruta
 * 
 * @param array $archivo Array del archivo subido ($_FILES)
 * @param string $directorio Directorio donde guardar la imagen
 * @param string $nombreArchivo Nombre base del archivo
 * @return string|null Ruta relativa de la imagen o null si hay error
 */
function guardarImagen($archivo, $directorio, $nombreArchivo) {
    // Verificar si se subió correctamente un archivo
    if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
        return null;
    }
    
    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Generar nombre único para evitar sobreescrituras
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreFinal = $nombreArchivo . '_' . time() . '.' . $extension;
    $rutaCompleta = $directorio . '/' . $nombreFinal;
    
    // Mover el archivo subido a su ubicación final
    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        return $rutaCompleta;
    }
    
    return null;
}

/**
 * Elimina un archivo si existe
 * 
 * @param string $ruta Ruta del archivo a eliminar
 * @return bool True si se eliminó correctamente, false en caso contrario
 */
function eliminarArchivo($ruta) {
    if (file_exists($ruta)) {
        return unlink($ruta);
    }
    return false;
}

/**
 * Genera un nombre de archivo único
 * 
 * @param string $prefijo Prefijo para el nombre del archivo
 * @param string $extension Extensión del archivo
 * @return string Nombre de archivo único
 */
function generarNombreUnico($prefijo = '', $extension = '') {
    $nombre = $prefijo . '_' . uniqid() . '_' . time();
    if (!empty($extension)) {
        $nombre .= '.' . ltrim($extension, '.');
    }
    return $nombre;
}

/**
 * Verifica si un archivo es una imagen válida
 * 
 * @param array $archivo Array del archivo subido ($_FILES)
 * @return bool True si es una imagen válida, false en caso contrario
 */
function esImagenValida($archivo) {
    if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
        return false;
    }
    
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Verificar tipo MIME
    if (!in_array($archivo['type'], $tiposPermitidos)) {
        return false;
    }
    
    // Verificar si realmente es una imagen
    $infoImagen = getimagesize($archivo['tmp_name']);
    if ($infoImagen === false) {
        return false;
    }
    
    return true;
}

/**
 * Genera una miniatura de una imagen
 * 
 * @param string $rutaOriginal Ruta de la imagen original
 * @param string $rutaDestino Ruta donde guardar la miniatura
 * @param int $ancho Ancho máximo de la miniatura
 * @param int $alto Alto máximo de la miniatura
 * @return bool True si se generó correctamente, false en caso contrario
 */
function generarMiniatura($rutaOriginal, $rutaDestino, $ancho = 150, $alto = 150) {
    // Obtener información de la imagen
    $infoImagen = getimagesize($rutaOriginal);
    if ($infoImagen === false) {
        return false;
    }
    
    // Crear imagen según el tipo
    switch ($infoImagen[2]) {
        case IMAGETYPE_JPEG:
            $imagenOriginal = imagecreatefromjpeg($rutaOriginal);
            break;
        case IMAGETYPE_PNG:
            $imagenOriginal = imagecreatefrompng($rutaOriginal);
            break;
        case IMAGETYPE_GIF:
            $imagenOriginal = imagecreatefromgif($rutaOriginal);
            break;
        default:
            return false;
    }
    
    // Calcular nuevas dimensiones manteniendo la proporción
    $anchoOriginal = $infoImagen[0];
    $altoOriginal = $infoImagen[1];
    
    if ($anchoOriginal <= $ancho && $altoOriginal <= $alto) {
        // La imagen ya es más pequeña que la miniatura deseada
        return copy($rutaOriginal, $rutaDestino);
    }
    
    $ratio = min($ancho / $anchoOriginal, $alto / $altoOriginal);
    $nuevoAncho = $anchoOriginal * $ratio;
    $nuevoAlto = $altoOriginal * $ratio;
    
    // Crear nueva imagen
    $miniatura = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    
    // Preservar transparencia para PNG
    if ($infoImagen[2] === IMAGETYPE_PNG) {
        imagealphablending($miniatura, false);
        imagesavealpha($miniatura, true);
    }
    
    // Redimensionar
    imagecopyresampled(
        $miniatura, $imagenOriginal,
        0, 0, 0, 0,
        $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal
    );
    
    // Guardar miniatura según el tipo
    $resultado = false;
    switch ($infoImagen[2]) {
        case IMAGETYPE_JPEG:
            $resultado = imagejpeg($miniatura, $rutaDestino, 90);
            break;
        case IMAGETYPE_PNG:
            $resultado = imagepng($miniatura, $rutaDestino, 9);
            break;
        case IMAGETYPE_GIF:
            $resultado = imagegif($miniatura, $rutaDestino);
            break;
    }
    
    // Liberar memoria
    imagedestroy($imagenOriginal);
    imagedestroy($miniatura);
    
    return $resultado;
}
?> 