<?php
require_once 'includes/config.php';

// Añadir depuración del estado de la sesión al inicio
error_log("Estado inicial de la sesión en login.php: " . json_encode($_SESSION));

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    error_log("Usuario ya autenticado en login.php, redirigiendo: ID=" . $_SESSION['usuario_id']);
    redirect(BASE_URL . '/index.php');
}

$errores = [];
$email = '';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validar los campos
    if (empty($email)) {
        $errores['email'] = 'El email es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El formato del email no es válido';
    }
    
    if (empty($password)) {
        $errores['password'] = 'La contraseña es obligatoria';
    }
    
    // Si no hay errores de validación, intentar autenticar
    if (empty($errores)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, nombre, email, password, rol, activo FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar si la contraseña es correcta
                if (password_verify($password, $usuario['password'])) {
                    // Verificar si el usuario está activo
                    if ($usuario['activo']) {
                        // Guardar datos del usuario en la sesión
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'];
                        $_SESSION['usuario_email'] = $usuario['email'];
                        $_SESSION['usuario_rol'] = $usuario['rol'];
                        
                        // Log de depuración
                        error_log("Sesión iniciada correctamente: ID {$usuario['id']}, Rol: {$usuario['rol']}");
                        error_log("Estado de la sesión después de login: " . json_encode($_SESSION));
                        
                        // Actualizar la fecha de último acceso
                        $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
                        $updateStmt->bindParam(':id', $usuario['id']);
                        $updateStmt->execute();
                        
                        // Asegurar que la sesión se guarde antes de la redirección
                        session_write_close();
                        
                        // Redirigir al usuario al dashboard
                        redirect(BASE_URL . '/index.php');
                    } else {
                        error_log("Intento de login con cuenta desactivada: {$email}");
                        $errores['login'] = 'Tu cuenta está desactivada. Contacta al administrador.';
                    }
                } else {
                    error_log("Contraseña incorrecta para usuario: {$email}");
                    $errores['login'] = 'Credenciales incorrectas. Inténtalo de nuevo.';
                }
            } else {
                error_log("Usuario no encontrado: {$email}");
                $errores['login'] = 'Credenciales incorrectas. Inténtalo de nuevo.';
            }
        } catch (PDOException $e) {
            error_log("Error en la base de datos durante login: " . $e->getMessage());
            $errores['login'] = 'Error al conectar con la base de datos. Inténtalo más tarde.';
        }
    }
}

// Obtener la configuración del sistema
$nombre_sistema = 'Sistema de Gestión';
$logo_url = 'assets/img/logo.png';  // Logo por defecto

try {
    $db = getDB();
    $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'nombre_sistema'");
    if ($nombre = $stmt->fetch(PDO::FETCH_COLUMN)) {
        $nombre_sistema = $nombre;
    }
    
    $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'logo'");
    if ($logo = $stmt->fetch(PDO::FETCH_COLUMN)) {
        $logo_url = $logo;
    }
} catch (PDOException $e) {
    // Usar los valores por defecto
}

// Título de la página
$title = 'Iniciar Sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | <?= htmlspecialchars($nombre_sistema) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <?php 
    $favicon_url = 'assets/img/favicon.ico';
    try {
        $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'favicon'");
        if ($favicon = $stmt->fetch(PDO::FETCH_COLUMN)) {
            $favicon_url = $favicon;
        }
    } catch (PDOException $e) {
        // Usar el valor por defecto
    }
    ?>
    <link rel="shortcut icon" href="<?= $favicon_url ?>" type="image/x-icon">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($nombre_sistema) ?>" class="img-fluid mb-3" style="max-height: 80px;">
                            <h2 class="h4 mb-0"><?= htmlspecialchars($nombre_sistema) ?></h2>
                            <p class="text-muted">Accede a tu cuenta para continuar</p>
                        </div>
                        
                        <?php if (isset($errores['login'])): ?>
                            <div class="alert alert-danger">
                                <span class="material-symbols-rounded align-middle me-1">error</span>
                                <?= $errores['login'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <?= heroicon_outline('envelope', 'heroicon-sm') ?>
                                    </span>
                                    <input type="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                    <?php if (isset($errores['email'])): ?>
                                        <div class="invalid-feedback"><?= $errores['email'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <?= heroicon_outline('lock-closed', 'heroicon-sm') ?>
                                    </span>
                                    <input type="password" class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                                    <?php if (isset($errores['password'])): ?>
                                        <div class="invalid-feedback"><?= $errores['password'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <?= heroicon_outline('login', 'heroicon-sm me-1') ?>Iniciar Sesión
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-muted">
                    <small>&copy; <?= date('Y') ?> <?= htmlspecialchars($nombre_sistema) ?>. Todos los derechos reservados.</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html> 