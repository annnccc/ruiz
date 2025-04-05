<?php
// Página de visualización de bono - sin usar layout principal
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos básicos
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Requiere login
requiereLogin();

// Obtener ID del bono
$bono_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bono_id <= 0) {
    // Redirigir a la lista si no hay ID válido
    header('Location: list.php?error=' . urlencode('ID de bono no válido'));
    exit;
}

// Buscar información del bono
try {
    $db = getDB();
    
    // Consulta para obtener datos del bono
    $query = "SELECT b.*, 
              p.nombre as paciente_nombre, p.apellidos as paciente_apellidos,
              p.telefono as paciente_telefono, p.email as paciente_email,
              u.nombre as creado_por_nombre
              FROM bonos b
              LEFT JOIN pacientes p ON b.paciente_id = p.id
              LEFT JOIN usuarios u ON b.creado_por = u.id
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
    
    // Obtener citas asociadas al bono
    $queryHistorial = "SELECT c.* 
                      FROM citas c 
                      WHERE c.bono_id = :bono_id
                      ORDER BY c.fecha DESC, c.hora_inicio DESC";
    
    $stmtHistorial = $db->prepare($queryHistorial);
    $stmtHistorial->bindParam(':bono_id', $bono_id, PDO::PARAM_INT);
    $stmtHistorial->execute();
    
    $citas = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Error en la consulta
    header('Location: list.php?error=' . urlencode('Error al obtener la información del bono: ' . $e->getMessage()));
    exit;
}

// Función para calcular el progreso del bono
function calcularPorcentajeBono($total, $disponibles) {
    if ($total <= 0) return 0;
    return round((($total - $disponibles) / $total) * 100);
}

// Función para obtener clase de badge según estado
function getEstadoBadgeClass($estado) {
    switch ($estado) {
        case 'activo':
            return 'bg-success';
        case 'agotado':
        case 'consumido':
            return 'bg-danger';
        case 'caducado':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary';
    }
}

// Formatear monto como moneda
function formatMoney($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

// Calcular días restantes hasta caducidad
function diasHastaCaducidad($fecha_caducidad) {
    if (empty($fecha_caducidad)) return null;
    
    $hoy = new DateTime();
    $caducidad = new DateTime($fecha_caducidad);
    $diff = $hoy->diff($caducidad);
    
    if ($diff->invert) {
        return -$diff->days; // Número negativo si ya caducó
    }
    return $diff->days;
}

$dias_caducidad = diasHastaCaducidad($bono['fecha_caducidad']);
$porcentaje_consumido = calcularPorcentajeBono($bono['num_sesiones_total'], $bono['num_sesiones_disponibles']);

// Título y breadcrumbs para la página
$titulo_pagina = "Detalle del Bono #" . $bono_id;
$breadcrumbs = [
    'Bonos' => BASE_URL . '/modules/bono/list.php',
    'Detalle' => '#'
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <span class="material-symbols-rounded me-2">confirmation_number</span><?= $titulo_pagina ?>
            </h1>
            <a href="list.php" class="btn btn-secondary">
                <span class="material-symbols-rounded me-1">arrow_back</span> Volver a la lista
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
        
        <!-- Acciones rápidas -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge <?= getEstadoBadgeClass($bono['estado']) ?> me-2">
                        <?= ucfirst($bono['estado']) ?>
                    </span>
                    <span class="text-muted">
                        <span class="material-symbols-rounded me-1">info</span> 
                        <?= $bono['num_sesiones_disponibles'] ?> de <?= $bono['num_sesiones_total'] ?> sesiones disponibles
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="edit.php?id=<?= $bono_id ?>" class="btn btn-warning btn-sm">
                        <span class="material-symbols-rounded me-1">edit</span> Editar
                    </a>
                    <?php if ($bono['estado'] === 'activo'): ?>
                    <a href="<?= BASE_URL ?>/modules/citas/create.php?paciente_id=<?= $bono['paciente_id'] ?>&bono_id=<?= $bono_id ?>" class="btn btn-success btn-sm">
                        <span class="material-symbols-rounded me-1">event_add</span> Crear Cita
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Información del bono -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">confirmation_number</span> Información del Bono
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted mb-0">Referencia</h5>
                            <span class="badge bg-primary">#<?= $bono_id ?></span>
                        </div>
                        
                        <hr>
                        
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0">Importe</th>
                                <td class="text-end fw-bold"><?= formatMoney($bono['monto']) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Sesiones Totales</th>
                                <td class="text-end"><?= $bono['num_sesiones_total'] ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Sesiones Disponibles</th>
                                <td class="text-end fw-bold"><?= $bono['num_sesiones_disponibles'] ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Fecha de Compra</th>
                                <td class="text-end"><?= formatDateToView($bono['fecha_compra']) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Caducidad</th>
                                <td class="text-end">
                                    <?php if (!empty($bono['fecha_caducidad'])): ?>
                                        <?= formatDateToView($bono['fecha_caducidad']) ?>
                                        
                                        <?php if ($dias_caducidad !== null): ?>
                                            <br>
                                            <small class="text-<?= $dias_caducidad < 0 ? 'danger' : ($dias_caducidad < 30 ? 'warning' : 'success') ?>">
                                                <?php if ($dias_caducidad < 0): ?>
                                                    Caducado hace <?= abs($dias_caducidad) ?> día(s)
                                                <?php else: ?>
                                                    Caduca en <?= $dias_caducidad ?> día(s)
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin caducidad</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-0">Estado</th>
                                <td class="text-end">
                                    <span class="badge <?= getEstadoBadgeClass($bono['estado']) ?>">
                                        <?= ucfirst($bono['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="mt-3">
                            <h6 class="mb-2">Progreso de consumo</h6>
                            <div class="progress mb-2">
                                <div class="progress-bar <?= $porcentaje_consumido >= 100 ? 'bg-danger' : 'bg-success' ?>" 
                                     role="progressbar" 
                                     style="width: <?= $porcentaje_consumido ?>%;" 
                                     aria-valuenow="<?= $porcentaje_consumido ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">
                                <?= $bono['num_sesiones_total'] - $bono['num_sesiones_disponibles'] ?> 
                                sesión(es) usada(s) (<?= $porcentaje_consumido ?>% consumido)
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Información del paciente -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">person</span> Datos del Paciente
                        </h5>
                    </div>
                    <div class="card-body patient-info">
                        <h5 class="mb-3">
                            <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $bono['paciente_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($bono['paciente_apellidos'] . ', ' . $bono['paciente_nombre']) ?>
                            </a>
                        </h5>
                        
                        <div class="mb-2">
                            <span class="material-symbols-rounded text-primary me-2">phone</span>
                            <?= !empty($bono['paciente_telefono']) ? htmlspecialchars($bono['paciente_telefono']) : '<span class="text-muted">No disponible</span>' ?>
                        </div>
                        
                        <div class="mb-2">
                            <span class="material-symbols-rounded text-primary me-2">email</span>
                            <?= !empty($bono['paciente_email']) ? htmlspecialchars($bono['paciente_email']) : '<span class="text-muted">No disponible</span>' ?>
                        </div>
                        
                        <div class="mt-3">
                            <a href="<?= BASE_URL ?>/modules/pacientes/view.php?id=<?= $bono['paciente_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <span class="material-symbols-rounded me-1">visibility</span> Ver ficha completa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historial de citas -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">history</span> Historial de Citas Asociadas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($citas)): ?>
                            <div class="alert alert-info mb-0">
                                <span class="material-symbols-rounded me-2">info</span>
                                No hay citas asociadas a este bono todavía.
                                
                                <?php if ($bono['estado'] === 'activo'): ?>
                                    <div class="mt-3">
                                        <a href="<?= BASE_URL ?>/modules/citas/create.php?paciente_id=<?= $bono['paciente_id'] ?>&bono_id=<?= $bono_id ?>" class="btn btn-sm btn-success">
                                            <span class="material-symbols-rounded me-1">event_add</span> Crear Cita con este Bono
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-light-borders">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Duración</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($citas as $cita): ?>
                                            <tr>
                                                <td><?= formatDateToView($cita['fecha']) ?></td>
                                                <td><?= substr($cita['hora_inicio'], 0, 5) ?></td>
                                                <td><?= $cita['duracion'] ?> min</td>
                                                <td>
                                                    <span class="badge <?= getEstadoCitaBadgeClass($cita['estado']) ?>">
                                                        <?= traducirEstadoCita($cita['estado']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>/modules/citas/view.php?id=<?= $cita['id'] ?>" class="btn btn-sm btn-info me-1" title="Ver detalle">
                                                        <span class="material-symbols-rounded">visibility</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Notas del bono -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">note</span> Notas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($bono['notas'])): ?>
                            <div class="p-3 bg-light rounded">
                                <?= nl2br(htmlspecialchars($bono['notas'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <span class="material-symbols-rounded me-2">info</span>
                                No hay notas asociadas a este bono.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <span class="material-symbols-rounded me-2">info</span> Información Adicional
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th class="ps-0">Creado por</th>
                                        <td class="text-end">
                                            <?= !empty($bono['creado_por_nombre']) ? htmlspecialchars($bono['creado_por_nombre']) : 'Sistema' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0">Fecha de creación</th>
                                        <td class="text-end">
                                            <?= !empty($bono['fecha_creacion']) ? formatDateTimeToView($bono['fecha_creacion']) : 'N/D' ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th class="ps-0">Última actualización</th>
                                        <td class="text-end">
                                            <?= !empty($bono['fecha_actualizacion']) ? formatDateTimeToView($bono['fecha_actualizacion']) : 'N/D' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="ps-0">ID en sistema</th>
                                        <td class="text-end"><?= $bono_id ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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
    
    <!-- Funciones auxiliares para el estado de las citas -->
    <?php
    function traducirEstadoCita($estado) {
        switch ($estado) {
            case 'pendiente':
                return 'Pendiente';
            case 'confirmada':
                return 'Confirmada';
            case 'completada':
                return 'Completada';
            case 'cancelada':
                return 'Cancelada';
            case 'no_asistio':
                return 'No asistió';
            default:
                return ucfirst($estado);
        }
    }
    
    function getEstadoCitaBadgeClass($estado) {
        switch ($estado) {
            case 'pendiente':
                return 'bg-warning text-dark';
            case 'confirmada':
                return 'bg-primary';
            case 'completada':
                return 'bg-success';
            case 'cancelada':
                return 'bg-danger';
            case 'no_asistio':
                return 'bg-secondary';
            default:
                return 'bg-secondary';
        }
    }
    ?>
    
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
    </script>
</body>
</html> 