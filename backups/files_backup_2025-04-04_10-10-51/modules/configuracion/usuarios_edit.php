<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación y permisos de administrador
requiereLogin();
requiereAdmin();

// Comprobar si se ha recibido un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'ID de usuario no válido');
    redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
}

$id_usuario = (int)$_GET['id'];

// Inicializar variables
$errores = [];
$usuario = null;

// Obtener los datos del usuario
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id_usuario);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        setAlert('danger', 'Usuario no encontrado');
        redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener datos del usuario: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $rol = $_POST['rol'] ?? 'usuario';
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar datos
    if (empty($nombre)) {
        $errores['nombre'] = "El nombre es obligatorio";
    }
    
    if (empty($email)) {
        $errores['email'] = "El email es obligatorio";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = "El formato del email no es válido";
    } else {
        // Verificar que el email no esté ya registrado para otro usuario
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id_usuario);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errores['email'] = "Este email ya está registrado para otro usuario";
            }
        } catch (PDOException $e) {
            setAlert('danger', 'Error al verificar email: ' . $e->getMessage());
        }
    }
    
    // Validar contraseña solo si se ha introducido una nueva
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errores['password'] = "La contraseña debe tener al menos 6 caracteres";
        }
        
        if ($password !== $password_confirm) {
            $errores['password_confirm'] = "Las contraseñas no coinciden";
        }
    }
    
    if (!in_array($rol, ['admin', 'usuario'])) {
        $errores['rol'] = "El rol seleccionado no es válido";
    }
    
    // Si no hay errores, proceder con la actualización
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Preparar la consulta para actualizar los datos
            if (!empty($password)) {
                // Si se ha proporcionado una nueva contraseña, actualizarla también
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, password = :password, 
                        rol = :rol, activo = :activo WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':password', $password_hashed);
            } else {
                // Si no se ha proporcionado contraseña, mantener la existente
                $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, 
                        rol = :rol, activo = :activo WHERE id = :id";
                $stmt = $db->prepare($sql);
            }
            
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':activo', $activo);
            $stmt->bindParam(':id', $id_usuario);
            
            if ($stmt->execute()) {
                setAlert('success', 'Usuario actualizado correctamente');
                redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
            } else {
                setAlert('danger', 'Error al actualizar el usuario');
            }
        } catch (PDOException $e) {
            setAlert('danger', 'Error de base de datos: ' . $e->getMessage());
        }
    }
} else {
    // Si es GET, prellenar el formulario con los datos del usuario
    $_POST['nombre'] = $usuario['nombre'];
    $_POST['email'] = $usuario['email'];
    $_POST['rol'] = $usuario['rol'];
    if ($usuario['activo']) {
        $_POST['activo'] = 1;
    }
}

// Título y breadcrumbs
$titulo_pagina = "Editar Usuario";
$title = $titulo_pagina;
$breadcrumbs = [
    'Configuración' => BASE_URL . '/modules/configuracion/list.php',
    'Editar Usuario' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">edit</span><?= $titulo_pagina ?>
        </h1>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                            <?php if (isset($errores['nombre'])): ?>
                                <div class="invalid-feedback"><?= $errores['nombre'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <?php if (isset($errores['email'])): ?>
                                <div class="invalid-feedback"><?= $errores['email'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>" id="password" name="password">
                            <?php if (isset($errores['password'])): ?>
                                <div class="invalid-feedback"><?= $errores['password'] ?></div>
                            <?php endif; ?>
                            <div class="form-text">Dejar en blanco para mantener la actual. Mínimo 6 caracteres.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control <?= isset($errores['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm">
                            <?php if (isset($errores['password_confirm'])): ?>
                                <div class="invalid-feedback"><?= $errores['password_confirm'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select <?= isset($errores['rol']) ? 'is-invalid' : '' ?>" id="rol" name="rol">
                                <option value="usuario" <?= isset($_POST['rol']) && $_POST['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                <option value="admin" <?= isset($_POST['rol']) && $_POST['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                            <?php if (isset($errores['rol'])): ?>
                                <div class="invalid-feedback"><?= $errores['rol'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= isset($_POST['activo']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="activo">Usuario activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="<?= BASE_URL ?>/modules/configuracion/list.php#usuarios" class="btn btn-outline-secondary me-2">
                        <span class="material-symbols-rounded me-1">arrow_back</span>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-1">save</span>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 