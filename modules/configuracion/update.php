<?php
require_once '../../includes/config.php';

try {
    $db = getDB();
    
    // Procesar los datos del formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Manejar la subida de archivos
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logo_tmp = $_FILES['logo']['tmp_name'];
            $logo_name = 'logo_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logo_path = '../../assets/img/' . $logo_name;
            
            if (move_uploaded_file($logo_tmp, $logo_path)) {
                $_POST['configuracion']['logo'] = 'assets/img/' . $logo_name;
            }
        }
        
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $favicon_tmp = $_FILES['favicon']['tmp_name'];
            $favicon_name = 'favicon_' . time() . '.' . pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
            $favicon_path = '../../assets/img/' . $favicon_name;
            
            if (move_uploaded_file($favicon_tmp, $favicon_path)) {
                $_POST['configuracion']['favicon'] = 'assets/img/' . $favicon_name;
            }
        }
        
        // Actualizar cada configuración
        $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = :clave");
        foreach ($_POST['configuracion'] as $clave => $valor) {
            $stmt->execute([
                'clave' => $clave,
                'valor' => $valor
            ]);
        }
        
        // Redirigir con mensaje de éxito
        header("Location: list.php?success=1");
        exit;
    }
} catch (PDOException $e) {
    // Redirigir con mensaje de error
    header("Location: list.php?error=" . urlencode($e->getMessage()));
    exit;
}

// Si no es POST, redirigir a la lista
header("Location: list.php");
exit;
?> 