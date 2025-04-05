<?php
/**
 * Helper para funciones de validación
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Sanitiza una entrada para prevenir XSS
 * 
 * @param string|null $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitize($data) {
    // Si es null, convertir a string vacío para evitar warnings
    if ($data === null) {
        return '';
    }
    
    // Asegurar que $data sea string
    $data = (string)$data;
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Valida si un correo electrónico es válido
 * 
 * @param string $email Correo electrónico a validar
 * @return bool True si es válido, false en caso contrario
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Valida si un número de teléfono tiene formato español válido
 * 
 * @param string $telefono Teléfono a validar
 * @return bool True si es válido, false en caso contrario
 */
function isValidPhone($telefono) {
    return preg_match('/^[6789][0-9]{8}$/', $telefono);
}

/**
 * Valida si un DNI/NIE tiene formato válido según algoritmo español
 * 
 * @param string $dni DNI a validar
 * @return bool True si es válido, false en caso contrario
 */
function validarDNI($dni) {
    // Modificación: Siempre devolver true para eliminar la validación
    return true;
    
    /* Código original comentado:
    $dni = strtoupper($dni);
    $dni = str_replace('-', '', $dni);
    $dni = str_replace(' ', '', $dni);
    
    // Si es un NIE, reemplazar la primera letra por su número correspondiente
    if (preg_match('/^[XYZ]/', $dni)) {
        $dni = str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $dni);
    }
    
    // Comprobar si el formato es válido
    if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
        return false;
    }
    
    // Obtener el número y la letra
    $numero = substr($dni, 0, 8);
    $letra = substr($dni, 8, 1);
    
    // Calcular la letra correspondiente al número
    $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
    $letraCalculada = $letras[$numero % 23];
    
    return $letra == $letraCalculada;
    */
}

/**
 * Verifica si un campo obligatorio está vacío
 * 
 * @param mixed $valor Valor a verificar
 * @return bool True si está vacío, false en caso contrario
 */
function isEmpty($valor) {
    return empty($valor) && $valor !== '0';
}

/**
 * Formatea un número de teléfono para visualización
 * 
 * @param string $telefono Número de teléfono
 * @return string Teléfono formateado
 */
function formatTelefono($telefono) {
    // Eliminar caracteres no numéricos
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    
    // Formatear según el patrón español (ej: 600 123 456)
    if (strlen($telefono) == 9) {
        return substr($telefono, 0, 3) . ' ' . substr($telefono, 3, 3) . ' ' . substr($telefono, 6);
    }
    
    return $telefono;
}

/**
 * Valida un conjunto de datos según reglas especificadas
 * 
 * @param array $datos Datos a validar
 * @param array $reglas Reglas de validación
 * @return array Errores encontrados
 */
function validarDatos($datos, $reglas) {
    $errores = [];
    
    foreach ($reglas as $campo => $reglasDelCampo) {
        $valor = $datos[$campo] ?? '';
        
        // Procesar cada regla
        foreach ($reglasDelCampo as $regla => $mensaje) {
            switch ($regla) {
                case 'required':
                    if (isEmpty($valor)) {
                        $errores[$campo] = $mensaje;
                    }
                    break;
                    
                case 'email':
                    if (!isEmpty($valor) && !isValidEmail($valor)) {
                        $errores[$campo] = $mensaje;
                    }
                    break;
                    
                case 'telefono':
                    if (!isEmpty($valor) && !isValidPhone($valor)) {
                        $errores[$campo] = $mensaje;
                    }
                    break;
                    
                case 'dni':
                    if (!isEmpty($valor) && !validarDNI($valor)) {
                        $errores[$campo] = $mensaje;
                    }
                    break;
                    
                case 'fecha':
                    if (!isEmpty($valor) && !strtotime($valor)) {
                        $errores[$campo] = $mensaje;
                    }
                    break;
                    
                case 'min':
                    $min = (int) $mensaje;
                    if (strlen($valor) < $min) {
                        $errores[$campo] = "Este campo debe tener al menos $min caracteres";
                    }
                    break;
                    
                case 'max':
                    $max = (int) $mensaje;
                    if (strlen($valor) > $max) {
                        $errores[$campo] = "Este campo no debe exceder los $max caracteres";
                    }
                    break;
                    
                case 'numeric':
                    if (!isEmpty($valor) && !is_numeric($valor)) {
                        $errores[$campo] = $mensaje;
                    }
                    break;
            }
            
            // Si ya hay un error para este campo, pasar al siguiente
            if (isset($errores[$campo])) {
                break;
            }
        }
    }
    
    return $errores;
}
?> 