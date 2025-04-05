<?php
// Página de edición de bonos con encabezado y sidebar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos básicos
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere login
requiereLogin();

// Obtener ID del bono a editar
$bono_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bono_id <= 0) {
    // Redirigir a la lista si no hay ID válido
    header('Location: list.php?error=' . urlencode('ID de bono no válido'));
    exit;
}

// Procesar formulario de actualización
$updated = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos recibidos
    $paciente_id = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
    $num_sesiones_total = isset($_POST['num_sesiones_total']) ? (int)$_POST['num_sesiones_total'] : 0;
    $num_sesiones_disponibles = isset($_POST['num_sesiones_disponibles']) ? (int)$_POST['num_sesiones_disponibles'] : 0;
    $fecha_compra = isset($_POST['fecha_compra']) ? sanitizeInput($_POST['fecha_compra']) : '';
    $fecha_caducidad = isset($_POST['fecha_caducidad']) ? sanitizeInput($_POST['fecha_caducidad']) : null;
    $monto = isset($_POST['monto']) ? floatval(str_replace(',', '.', $_POST['monto'])) : 0;
    $estado = isset($_POST['estado']) ? sanitizeInput($_POST['estado']) : 'activo';
    $notas = isset($_POST['notas']) ? sanitizeInput($_POST['notas']) : '';
    
    // Validar datos
    if ($paciente_id <= 0) {
        $error = "Debe seleccionar un paciente válido.";
    } elseif ($num_sesiones_total <= 0) {
        $error = "El número de sesiones debe ser mayor que cero.";
    } elseif ($num_sesiones_disponibles < 0 || $num_sesiones_disponibles > $num_sesiones_total) {
        $error = "El número de sesiones disponibles debe ser mayor o igual a cero y no superar el total.";
    } elseif ($monto <= 0) {
        $error = "El monto debe ser mayor que cero.";
    } elseif (empty($fecha_compra)) {
        $error = "La fecha de compra es obligatoria.";
    } else {
        try {
            $db = getDB();
            
            // Actualizar datos del bono
            $query = "UPDATE bonos SET 
                        paciente_id = :paciente_id,
                        num_sesiones_total = :num_sesiones_total,
                        num_sesiones_disponibles = :num_sesiones_disponibles,
                        fecha_compra = :fecha_compra,
                        fecha_caducidad = :fecha_caducidad,
                        monto = :monto,
                        estado = :estado,
                        notas = :notas,
                        fecha_actualizacion = NOW()
                      WHERE id = :bono_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
            $stmt->bindParam(':num_sesiones_total', $num_sesiones_total, PDO::PARAM_INT);
            $stmt->bindParam(':num_sesiones_disponibles', $num_sesiones_disponibles, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_compra', $fecha_compra);
            
            if (empty($fecha_caducidad)) {
                $stmt->bindValue(':fecha_caducidad', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':fecha_caducidad', $fecha_caducidad);
            }
            
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':notas', $notas);
            $stmt->bindParam(':bono_id', $bono_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Actualizar estado basado en sesiones disponibles
            if ($num_sesiones_disponibles <= 0) {
                $queryUpdateEstado = "UPDATE bonos SET estado = 'consumido' WHERE id = :bono_id";
                $stmtUpdateEstado = $db->prepare($queryUpdateEstado);
                $stmtUpdateEstado->bindParam(':bono_id', $bono_id, PDO::PARAM_INT);
                $stmtUpdateEstado->execute();
            }
            
            $updated = true;
            
            // Redirigir después de actualizar
            header('Location: view.php?id=' . $bono_id . '&success=' . urlencode('Bono actualizado correctamente'));
            exit;
            
        } catch (PDOException $e) {
            $error = "Error al actualizar el bono: " . $e->getMessage();
        }
    }
}

// Obtener datos actuales del bono
try {
    $db = getDB();
    
    // Consulta para obtener datos del bono
    $query = "SELECT b.*, 
              p.nombre as paciente_nombre, p.apellidos as paciente_apellidos
              FROM bonos b
              LEFT JOIN pacientes p ON b.paciente_id = p.id
              WHERE b.id = :bono_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bono_id', $bono_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $bono = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bono) {
        // Bono no encontrado
        header('Location: list.php?error=' . urlencode('Bono no encontrado'));
        exit;
    }
    
    // Obtener lista de pacientes para el select
    $queryPacientes = "SELECT id, nombre, apellidos FROM pacientes ORDER BY apellidos, nombre";
    $stmtPacientes = $db->prepare($queryPacientes);
    $stmtPacientes->execute();
    $pacientes = $stmtPacientes->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
    header('Location: list.php?error=' . urlencode($error));
    exit;
}

// Título y breadcrumbs para la página
$titulo_pagina = "Editar Bono #" . $bono_id;
$breadcrumbs = [
    'Bonos' => BASE_URL . '/modules/bono/list.php',
    'Editar' => '#'
];

// Iniciar la página directamente (sin layout)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> | <?= NOMBRE_SISTEMA ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= BASE_URL ?>/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Tema oscuro si está configurado -->
    <?php if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark'): ?>
    <script>document.documentElement.setAttribute('data-theme', 'dark');</script>
    <?php endif; ?>
</head>

<body class="bg-light">
    <!-- Navbar principal -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= NOMBRE_SISTEMA ?>" height="30" class="me-2">
                <?= NOMBRE_SISTEMA ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/dashboard.php">
                            <span class="material-symbols-rounded">dashboard</span> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/modules/pacientes/list.php">
                            <span class="material-symbols-rounded">people</span> Pacientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/modules/citas/list.php">
                            <span class="material-symbols-rounded">event</span> Citas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= BASE_URL ?>/modules/bono/list.php">
                            <span class="material-symbols-rounded">confirmation_number</span> Bonos
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <!-- Tema oscuro/claro -->
                    <button id="theme-toggle" class="btn btn-sm btn-outline-secondary me-2">
                        <span class="material-symbols-rounded">dark_mode</span>
                    </button>
                    
                    <!-- Menú de usuario -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <span class="material-symbols-rounded me-1">account_circle</span>
                            <?= $_SESSION['nombre'] ?? 'Usuario' ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/perfil.php">Mi perfil</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container-fluid py-4 mt-5 px-4 bono-container">
        <!-- Encabezado de la página -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <span class="material-symbols-rounded me-2">edit</span> <?= $titulo_pagina ?>
            </h1>
            <a href="view.php?id=<?= $bono_id ?>" class="btn btn-secondary">
                <span class="material-symbols-rounded me-1">arrow_back</span> Volver al detalle
            </a>
        </div>
        
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Inicio</a></li>
                <?php foreach ($breadcrumbs as $text => $url): ?>
                    <?php if ($url === '#'): ?>
                        <li class="breadcrumb-item active"><?= $text ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item"><a href="<?= $url ?>"><?= $text ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        
        <!-- Alertas -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($updated): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                El bono ha sido actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        
        <!-- Formulario de edición -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Información del Bono</h5>
            </div>
            <div class="card-body">
                <form action="edit.php?id=<?= $bono_id ?>" method="post" id="bonoForm" class="row g-3">
                    <!-- Primera columna -->
                    <div class="col-md-6">
                        <!-- Paciente -->
                        <div class="mb-3">
                            <label for="paciente_id" class="form-label">Paciente <span class="text-danger">*</span></label>
                            <select class="form-select" id="paciente_id" name="paciente_id" required>
                                <option value="">Seleccionar paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?= $paciente['id'] ?>" <?= ($bono['paciente_id'] == $paciente['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Paciente al que pertenece el bono.</div>
                        </div>
                        
                        <!-- Sesiones total -->
                        <div class="mb-3">
                            <label for="num_sesiones_total" class="form-label">Número total de sesiones <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="num_sesiones_total" name="num_sesiones_total" value="<?= $bono['num_sesiones_total'] ?>" min="1" required>
                            <div class="form-text">Cantidad total de sesiones que incluye el bono.</div>
                        </div>
                        
                        <!-- Sesiones disponibles -->
                        <div class="mb-3">
                            <label for="num_sesiones_disponibles" class="form-label">Sesiones disponibles <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="num_sesiones_disponibles" name="num_sesiones_disponibles" value="<?= $bono['num_sesiones_disponibles'] ?>" min="0" required>
                            <div class="form-text">Número de sesiones que aún puede utilizar el paciente.</div>
                        </div>
                        
                        <!-- Monto -->
                        <div class="mb-3">
                            <label for="monto" class="form-label">Importe (€) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="monto" name="monto" value="<?= number_format($bono['monto'], 2, ',', '.') ?>" required>
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="form-text">Precio total del bono.</div>
                        </div>
                    </div>
                    
                    <!-- Segunda columna -->
                    <div class="col-md-6">
                        <!-- Fecha de compra -->
                        <div class="mb-3">
                            <label for="fecha_compra" class="form-label">Fecha de compra <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" value="<?= $bono['fecha_compra'] ?>" required>
                            <div class="form-text">Fecha en que se adquirió el bono.</div>
                        </div>
                        
                        <!-- Fecha de caducidad -->
                        <div class="mb-3">
                            <label for="fecha_caducidad" class="form-label">Fecha de caducidad</label>
                            <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad" value="<?= $bono['fecha_caducidad'] ?>">
                            <div class="form-text">Fecha de vencimiento del bono (opcional). Dejar en blanco si no caduca.</div>
                        </div>
                        
                        <!-- Estado -->
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="activo" <?= ($bono['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                                <option value="caducado" <?= ($bono['estado'] == 'caducado') ? 'selected' : '' ?>>Caducado</option>
                                <option value="consumido" <?= ($bono['estado'] == 'consumido') ? 'selected' : '' ?>>Consumido</option>
                            </select>
                            <div class="form-text">Estado actual del bono.</div>
                        </div>
                        
                        <!-- Notas -->
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="4"><?= htmlspecialchars($bono['notas']) ?></textarea>
                            <div class="form-text">Observaciones adicionales sobre el bono.</div>
                        </div>
                    </div>
                    
                    <!-- Progreso de consumo -->
                    <div class="col-12 mb-3">
                        <h6 class="form-label">Progreso de consumo</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar <?= ($bono['num_sesiones_total'] > $bono['num_sesiones_disponibles'] ? 'bg-success' : 'bg-secondary') ?>" 
                                 role="progressbar" 
                                 style="width: <?= ($bono['num_sesiones_total'] > 0 ? (($bono['num_sesiones_total'] - $bono['num_sesiones_disponibles']) / $bono['num_sesiones_total'] * 100) : 0) ?>%" 
                                 aria-valuenow="<?= ($bono['num_sesiones_total'] > 0 ? (($bono['num_sesiones_total'] - $bono['num_sesiones_disponibles']) / $bono['num_sesiones_total'] * 100) : 0) ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?= $bono['num_sesiones_total'] - $bono['num_sesiones_disponibles'] ?> 
                            sesión(es) usada(s) (<?= round(($bono['num_sesiones_total'] > 0 ? (($bono['num_sesiones_total'] - $bono['num_sesiones_disponibles']) / $bono['num_sesiones_total'] * 100) : 0), 0) ?>% consumido)
                        </small>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="col-12 d-flex justify-content-end mt-4">
                        <a href="view.php?id=<?= $bono_id ?>" class="btn btn-secondary me-2">
                            <span class="material-symbols-rounded me-1">cancel</span> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-rounded me-1">save</span> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white py-4 mt-auto border-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">
                    &copy; <?= date('Y') ?> <?= NOMBRE_SISTEMA ?>. Todos los derechos reservados.
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/privacidad.php">Política de Privacidad</a>
                    &middot;
                    <a href="<?= BASE_URL ?>/terminos.php">Términos y Condiciones</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript de Bootstrap y personalizados -->
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/common.js"></script>
    
    <script>
        // Toggle para el tema oscuro/claro
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            
            // Guardar preferencia en cookie
            document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
            
            // Actualizar icono
            this.querySelector('.material-symbols-rounded').textContent = 
                newTheme === 'light' ? 'dark_mode' : 'light_mode';
        });
        
        // Configurar el icono inicial correcto
        (function() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            document.querySelector('#theme-toggle .material-symbols-rounded').textContent = 
                currentTheme === 'light' ? 'dark_mode' : 'light_mode';
        })();
        
        // Actualizar automáticamente las sesiones disponibles cuando cambien las totales
        document.getElementById('num_sesiones_total').addEventListener('change', function() {
            const totalSesiones = parseInt(this.value);
            const disponibles = parseInt(document.getElementById('num_sesiones_disponibles').value);
            
            // Si tenemos más sesiones disponibles que el nuevo total, ajustar
            if (disponibles > totalSesiones) {
                document.getElementById('num_sesiones_disponibles').value = totalSesiones;
            }
            
            // Actualizar valor máximo permitido
            document.getElementById('num_sesiones_disponibles').max = totalSesiones;
        });
    </script>
</body>
</html> 