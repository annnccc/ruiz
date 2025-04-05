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

// No permitir eliminar al usuario propio (descomentar cuando esté implementado el login)
// if ($_SESSION['usuario_id'] == $id_usuario) {
//     setAlert('danger', 'No puedes eliminar tu propia cuenta de usuario');
//     redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
// }

// Verificar que el usuario existe
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT nombre FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id_usuario);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        setAlert('danger', 'Usuario no encontrado');
        redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
    }
    
    $nombre_usuario = $stmt->fetch(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener datos del usuario: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
}

// Procesar la eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_eliminar'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id_usuario);
        
        if ($stmt->execute()) {
            setAlert('success', 'Usuario eliminado correctamente');
        } else {
            setAlert('danger', 'Error al eliminar el usuario');
        }
        
        redirect(BASE_URL . '/modules/configuracion/list.php#usuarios');
    } catch (PDOException $e) {
        setAlert('danger', 'Error de base de datos: ' . $e->getMessage());
    }
}

// Título y breadcrumbs
$titulo_pagina = "Eliminar Usuario";
$title = $titulo_pagina;
$breadcrumbs = [
    'Configuración' => BASE_URL . '/modules/configuracion/list.php',
    'Eliminar Usuario' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">delete</span><?= $titulo_pagina ?>
        </h1>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="alert alert-danger mb-4">
                <h4 class="alert-heading">
                    <span class="material-symbols-rounded me-2">warning</span>¿Estás seguro?
                </h4>
                <p class="mb-0">Estás a punto de eliminar al usuario <strong><?= htmlspecialchars($nombre_usuario) ?></strong>. Esta acción no se puede deshacer.</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="confirmar_eliminar" value="1">
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="<?= BASE_URL ?>/modules/configuracion/list.php#usuarios" class="btn btn-outline-secondary me-2">
                        <span class="material-symbols-rounded me-1">arrow_back</span>Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <span class="material-symbols-rounded me-1">delete</span>Eliminar Usuario
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