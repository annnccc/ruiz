<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Añadir depuración de sesión
error_log("Estado de la sesión antes de requiereLogin: " . json_encode($_SESSION));

// Verificar manualmente si el usuario está autenticado
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['paciente_id'])) {
    error_log("No hay sesión de usuario activa");
    setAlert('warning', 'Debes iniciar sesión para acceder a esta página');
    redirect(BASE_URL . '/login.php');
} else {
    error_log("Usuario autenticado con ID: " . ($_SESSION['usuario_id'] ?? 'N/A') . ", Rol: " . ($_SESSION['usuario_rol'] ?? 'N/A'));
}

// Requerir autenticación para acceder a esta página
// requiereLogin();

try {
    // Obtener conexión a la base de datos
    $db = getDB();
    
    // Obtener todos los servicios
    $stmt = $db->prepare("SELECT * FROM servicios ORDER BY nombre ASC");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener los datos: ' . $e->getMessage());
    $servicios = [];
}

// Título y breadcrumbs para la página
$titulo_pagina = "Listado de Servicios";
$title = $titulo_pagina;
$breadcrumbs = [
    'Servicios' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">medical_services</span><?= $titulo_pagina ?>
        </h1>
        <a href="<?= BASE_URL ?>/modules/servicios/create.php" class="btn btn-primary">
            <span class="material-symbols-rounded">add_circle</span> Nuevo Servicio
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Tabla de servicios -->
    <div class="card border-0 shadow-sm animate-fade-in">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">format_list_bulleted</span>Listado de Servicios
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($servicios)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <span class="material-symbols-rounded icon-xl mb-2" style="color: #ccc;">folder_off</span>
                                    <p>No se encontraron servicios</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($servicios as $servicio): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($servicio['nombre']) ?></td>
                                    <td>
                                        <?php if (strlen($servicio['descripcion']) > 50): ?>
                                            <span data-bs-toggle="tooltip" title="<?= htmlspecialchars($servicio['descripcion']) ?>">
                                                <?= htmlspecialchars(substr($servicio['descripcion'], 0, 50)) ?>...
                                            </span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($servicio['descripcion']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($servicio['precio'], 2) ?> €</td>
                                    <td><?= $servicio['duracion_minutos'] ?> min</td>
                                    <td>
                                        <span class="badge <?= $servicio['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $servicio['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                            <a href="edit.php?id=<?= $servicio['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar">
                                                <?= heroicon_outline('pencil', 'heroicon-sm') ?>
                                            </a>
                                            <a href="delete.php?id=<?= $servicio['id'] ?>" class="btn btn-delete" data-bs-toggle="tooltip" title="Eliminar">
                                                <?= heroicon_outline('trash', 'heroicon-sm') ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 