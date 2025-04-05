<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requerir autenticación y permisos de administrador
requiereLogin();
requiereAdmin();

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$configuracion = [];

try {
    $db = getDB();
    $configuracion = [];
    
    // Obtener todas las configuraciones
    $stmt = $db->query("SELECT clave, valor FROM configuracion ORDER BY clave");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($row['clave']) && isset($row['valor'])) {
            $configuracion[$row['clave']] = $row['valor'];
        }
    }
    
    // Si no hay configuraciones, redirigir al script de verificación
    if (empty($configuracion)) {
        header("Location: verificar_tabla.php");
        exit;
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Procesar formulario de configuración general
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_general'])) {
    try {
        $db = getDB();
        
        // Actualizar cada configuración recibida
        $campos = ['nombre_sistema', 'email_contacto', 'telefono_contacto', 'direccion', 'site_url'];
        
        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                $valor = trim($_POST[$campo]);
                $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = :clave");
                $stmt->bindParam(':valor', $valor);
                $stmt->bindParam(':clave', $campo);
                $stmt->execute();
                
                // Actualizar el array local
                $configuracion[$campo] = $valor;
            }
        }
        
        setAlert('success', 'Configuración general actualizada correctamente');
    } catch (PDOException $e) {
        setAlert('danger', 'Error al guardar la configuración: ' . $e->getMessage());
    }
}

// Procesar formulario de apariencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_apariencia'])) {
    try {
        $db = getDB();
        
        // Procesar colores
        $campos_color = ['color_primario', 'color_secundario'];
        foreach ($campos_color as $campo) {
            if (isset($_POST[$campo])) {
                $valor = trim($_POST[$campo]);
                $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = :clave");
                $stmt->bindParam(':valor', $valor);
                $stmt->bindParam(':clave', $campo);
                $stmt->execute();
                
                // Actualizar el array local
                $configuracion[$campo] = $valor;
            }
        }
        
        // Procesar logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_path = '../../assets/img/';
                $new_filename = 'logo.' . $ext;
                $upload_file = $upload_path . $new_filename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_file)) {
                    $logo_path = 'assets/img/' . $new_filename;
                    
                    $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = 'logo'");
                    $stmt->bindParam(':valor', $logo_path);
                    $stmt->execute();
                    
                    // Actualizar el array local
                    $configuracion['logo'] = $logo_path;
                } else {
                    setAlert('warning', 'Error al subir el logo');
                }
            } else {
                setAlert('warning', 'Formato de archivo no permitido para el logo');
            }
        }
        
        // Procesar favicon
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
            $allowed = ['ico', 'png'];
            $filename = $_FILES['favicon']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_path = '../../assets/img/';
                $new_filename = 'favicon.' . $ext;
                $upload_file = $upload_path . $new_filename;
                
                if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_file)) {
                    $favicon_path = 'assets/img/' . $new_filename;
                    
                    $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = 'favicon'");
                    $stmt->bindParam(':valor', $favicon_path);
                    $stmt->execute();
                    
                    // Actualizar el array local
                    $configuracion['favicon'] = $favicon_path;
                } else {
                    setAlert('warning', 'Error al subir el favicon');
                }
            } else {
                setAlert('warning', 'Formato de archivo no permitido para el favicon');
            }
        }
        
        setAlert('success', 'Configuración de apariencia actualizada correctamente');
    } catch (PDOException $e) {
        setAlert('danger', 'Error al guardar la configuración: ' . $e->getMessage());
    }
}

// Obtener lista de usuarios para la pestaña de usuarios
$usuarios = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, nombre, email, rol, activo, ultimo_login FROM usuarios ORDER BY id ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setAlert('danger', 'Error al cargar los usuarios: ' . $e->getMessage());
}

// Título y breadcrumbs
$titulo_pagina = "Configuración del Sistema";
$title = $titulo_pagina;
$breadcrumbs = [
    'Configuración' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">settings</span><?= $titulo_pagina ?>
        </h1>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Pestañas de configuración -->
    <ul class="nav nav-tabs" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                <span class="material-symbols-rounded align-middle me-1">tune</span>General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="apariencia-tab" data-bs-toggle="tab" data-bs-target="#apariencia" type="button" role="tab" aria-controls="apariencia" aria-selected="false">
                <span class="material-symbols-rounded align-middle me-1">palette</span>Apariencia
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab" aria-controls="usuarios" aria-selected="false">
                <span class="material-symbols-rounded align-middle me-1">manage_accounts</span>Usuarios
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button" role="tab" aria-controls="seguridad" aria-selected="false">
                <span class="material-symbols-rounded align-middle me-1">security</span>Seguridad
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <a href="guia_estilo.php" class="nav-link" id="estilo-tab">
                <span class="material-symbols-rounded align-middle me-1">palette</span>Estilo
            </a>
        </li>
        <li class="nav-item" role="presentation">
             <a href="backup.php" class="nav-link" id="backup-tab">
                 <span class="material-symbols-rounded align-middle me-1">backup</span>Backup
             </a>
        </li>
    </ul>
    
    <div class="tab-content" id="configTabsContent">
        <!-- Pestaña: Configuración General -->
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-4">Configuración General</h5>
                    
                    <form id="configForm" method="POST" action="update.php" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nombre_sistema">Nombre del Sistema:</label>
                                    <input type="text" class="form-control" id="nombre_sistema" name="configuracion[nombre_sistema]" 
                                        value="<?php echo htmlspecialchars($configuracion['nombre_sistema'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="email_contacto">Email de Contacto:</label>
                                    <input type="email" class="form-control" id="email_contacto" name="configuracion[email_contacto]" 
                                        value="<?php echo htmlspecialchars($configuracion['email_contacto'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="telefono_contacto">Teléfono de Contacto:</label>
                                    <input type="tel" class="form-control" id="telefono_contacto" name="configuracion[telefono_contacto]" 
                                        value="<?php echo htmlspecialchars($configuracion['telefono_contacto'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="direccion">Dirección:</label>
                                    <textarea class="form-control" id="direccion" name="configuracion[direccion]" rows="3"><?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="site_url">URL del Sitio:</label>
                                    <input type="text" class="form-control" id="site_url" name="configuracion[site_url]" 
                                        value="<?php echo htmlspecialchars($configuracion['site_url'] ?? 'http://app.ruizarrietapsicologia.com/ruiz/'); ?>" 
                                        placeholder="http://app.ruizarrietapsicologia.com/ruiz/">
                                    <div class="form-text">Esta URL se utilizará en los enlaces de los correos electrónicos enviados a los pacientes.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="color_primario">Color Primario:</label>
                                    <input type="color" class="form-control" id="color_primario" name="configuracion[color_primario]" 
                                        value="<?php echo htmlspecialchars($configuracion['color_primario'] ?? '#6366f1'); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="color_secundario">Color Secundario:</label>
                                    <input type="color" class="form-control" id="color_secundario" name="configuracion[color_secundario]" 
                                        value="<?php echo htmlspecialchars($configuracion['color_secundario'] ?? '#0ea5e9'); ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="logo">Logo:</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                    <?php if (!empty($configuracion['logo'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($configuracion['logo']); ?>" alt="Logo actual" class="mt-2" style="max-height: 50px;">
                                    <?php endif; ?>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="favicon">Favicon:</label>
                                    <input type="file" class="form-control" id="favicon" name="favicon" accept="image/x-icon,image/png">
                                    <?php if (!empty($configuracion['favicon'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($configuracion['favicon']); ?>" alt="Favicon actual" class="mt-2" style="max-height: 32px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Apariencia -->
        <div class="tab-pane fade" id="apariencia" role="tabpanel" aria-labelledby="apariencia-tab">
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-4">Configuración de Apariencia</h5>
                    
                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="logo" class="form-label">Logo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/png, image/jpeg, image/gif, image/svg+xml">
                                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF, SVG</div>
                                </div>
                                
                                <?php if (!empty($configuracion['logo'])): ?>
                                <div class="mt-2">
                                    <label class="form-label">Logo Actual</label>
                                    <div class="bg-light p-3 text-center rounded">
                                        <img src="<?= BASE_URL . '/' . $configuracion['logo'] ?>" alt="Logo Actual" class="img-fluid" style="max-height: 80px;">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="favicon" class="form-label">Favicon</label>
                                    <input type="file" class="form-control" id="favicon" name="favicon" accept="image/x-icon, image/png">
                                    <div class="form-text">Formatos permitidos: ICO, PNG</div>
                                </div>
                                
                                <?php if (!empty($configuracion['favicon'])): ?>
                                <div class="mt-2">
                                    <label class="form-label">Favicon Actual</label>
                                    <div class="bg-light p-3 text-center rounded">
                                        <img src="<?= BASE_URL . '/' . $configuracion['favicon'] ?>" alt="Favicon Actual" class="img-fluid" style="max-height: 32px;">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="color_primario" class="form-label">Color Primario</label>
                                    <input type="color" class="form-control form-control-color w-100" id="color_primario" name="color_primario" value="<?= htmlspecialchars($configuracion['color_primario'] ?? '#0d6efd') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="color_secundario" class="form-label">Color Secundario</label>
                                    <input type="color" class="form-control form-control-color w-100" id="color_secundario" name="color_secundario" value="<?= htmlspecialchars($configuracion['color_secundario'] ?? '#6c757d') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="guardar_apariencia" class="btn btn-primary">
                                <span class="material-symbols-rounded me-1">save</span>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Usuarios -->
        <div class="tab-pane fade" id="usuarios" role="tabpanel" aria-labelledby="usuarios-tab">
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Gestión de Usuarios</h5>
                        <a href="<?= BASE_URL ?>/modules/configuracion/usuarios_create.php" class="btn btn-primary btn-sm">
                            <span class="material-symbols-rounded me-1">person_add</span>Nuevo Usuario
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= $usuario['id'] ?></td>
                                    <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td>
                                        <?php if ($usuario['rol'] === 'admin'): ?>
                                            <span class="badge bg-primary">Administrador</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Usuario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca' ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= BASE_URL ?>/modules/configuracion/usuarios_edit.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <span class="material-symbols-rounded">edit</span>
                                            </a>
                                            <a href="<?= BASE_URL ?>/modules/configuracion/usuarios_delete.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <span class="material-symbols-rounded">delete</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-3">No hay usuarios registrados</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pestaña: Seguridad y Cumplimiento -->
        <div class="tab-pane fade" id="seguridad" role="tabpanel" aria-labelledby="seguridad-tab">
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Configuración de Seguridad y Cumplimiento</h5>
                    </div>
                    
                    <!-- Auditoría de Accesos -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="me-3">
                                    <span class="material-symbols-rounded fs-1 text-primary">security</span>
                                </div>
                                <div>
                                    <h5 class="card-title">Auditoría de Accesos</h5>
                                    <p class="card-text">Registro detallado de quién accede a qué datos. Permite monitorizar la actividad y cumplir con requisitos normativos.</p>
                                    <a href="<?= BASE_URL ?>/modules/configuracion/audit.php" class="btn btn-primary">
                                        <span class="material-symbols-rounded me-2">visibility</span>Ver Registros de Auditoría
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Copias de Seguridad -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="me-3">
                                    <span class="material-symbols-rounded fs-1 text-primary">backup</span>
                                </div>
                                <div>
                                    <h5 class="card-title">Copias de Seguridad</h5>
                                    <p class="card-text">Configuración de copias de seguridad automatizadas para garantizar la integridad y disponibilidad de los datos.</p>
                                    <a href="<?= BASE_URL ?>/modules/configuracion/backup.php" class="btn btn-primary">
                                        <span class="material-symbols-rounded me-2">settings_backup_restore</span>Gestionar Copias de Seguridad
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 