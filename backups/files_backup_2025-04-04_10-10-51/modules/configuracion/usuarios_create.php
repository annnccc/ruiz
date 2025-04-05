<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación y permisos de administrador
requiereLogin();
requiereAdmin();

// Inicializar variables
$errores = [];

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
        // Verificar que el email no exista ya en la base de datos
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errores['email'] = "Este email ya está registrado";
            }
        } catch (PDOException $e) {
            setAlert('danger', 'Error al verificar email: ' . $e->getMessage());
        }
    }
    
    if (empty($password)) {
        $errores['password'] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errores['password'] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $password_confirm) {
        $errores['password_confirm'] = "Las contraseñas no coinciden";
    }
    
    if (!in_array($rol, ['admin', 'usuario'])) {
        $errores['rol'] = "El rol seleccionado no es válido";
    }
    
    // Si no hay errores, proceder con la creación
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Hashear la contraseña
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) 
                                VALUES (:nombre, :email, :password, :rol, :activo)");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password_hashed);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':activo', $activo);
            
            if ($stmt->execute()) {
                setAlert('success', 'Usuario creado correctamente');
                redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
            } else {
                setAlert('danger', 'Error al crear el usuario');
            }
        } catch (PDOException $e) {
            setAlert('danger', 'Error de base de datos: ' . $e->getMessage());
        }
    }
}

// Título y breadcrumbs
$titulo_pagina = "Crear Nuevo Usuario";
$title = $titulo_pagina;
$breadcrumbs = [
    'Configuración' => BASE_URL . '/modules/configuracion/list.php',
    'Nuevo Usuario' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">person_add</span><?= $titulo_pagina ?>
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
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                            <?php if (isset($errores['password'])): ?>
                                <div class="invalid-feedback"><?= $errores['password'] ?></div>
                            <?php endif; ?>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control <?= isset($errores['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required>
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
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= isset($_POST['activo']) ? 'checked' : 'checked' ?>>
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
                        <span class="material-symbols-rounded me-1">person_add</span>Crear Usuario
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