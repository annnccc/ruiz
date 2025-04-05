<?php
// Declaración explícita de codificación
header('Content-Type: text/html; charset=UTF-8');
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si hay sesión activa
checkSession();

// Verificar si se proporcionó el ID de la nota y de la cita
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['cita_id']) || empty($_GET['cita_id'])) {
    setAlert('danger', 'Parámetros incorrectos para editar la nota');
    header('Location: list.php');
    exit;
}

$nota_id = (int)$_GET['id'];
$cita_id = (int)$_GET['cita_id'];
$nota = null;

// Obtener los datos de la nota
try {
    $db = getDB();
    
    // Verificar si la nota existe y pertenece a la cita indicada
    $query = "SELECT * FROM notas_sesion WHERE id = :id AND cita_id = :cita_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $nota_id, PDO::PARAM_INT);
    $stmt->bindParam(':cita_id', $cita_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'La nota no existe o no pertenece a esta cita');
        header('Location: view.php?id=' . $cita_id);
        exit;
    }
    
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener detalles de la cita
    $cita_query = "SELECT c.*, p.nombre, p.apellidos 
                  FROM citas c 
                  JOIN pacientes p ON c.paciente_id = p.id 
                  WHERE c.id = :id";
    $cita_stmt = $db->prepare($cita_query);
    $cita_stmt->bindParam(':id', $cita_id, PDO::PARAM_INT);
    $cita_stmt->execute();
    
    if ($cita_stmt->rowCount() === 0) {
        setAlert('danger', 'La cita no existe');
        header('Location: list.php');
        exit;
    }
    
    $cita = $cita_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setAlert('danger', 'Error en la base de datos: ' . $e->getMessage());
    header('Location: view.php?id=' . $cita_id);
    exit;
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['titulo']) || empty($_POST['titulo']) || !isset($_POST['contenido'])) {
        setAlert('danger', 'Por favor, complete todos los campos obligatorios');
    } else {
        $titulo = trim($_POST['titulo']);
        $contenido = trim($_POST['contenido']);
        
        try {
            $update_query = "UPDATE notas_sesion 
                            SET titulo = :titulo, 
                                contenido = :contenido,
                                fecha_actualizacion = NOW() 
                            WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':titulo', $titulo);
            $update_stmt->bindParam(':contenido', $contenido);
            $update_stmt->bindParam(':id', $nota_id, PDO::PARAM_INT);
            
            if ($update_stmt->execute()) {
                setAlert('success', 'Nota actualizada correctamente');
                header('Location: view.php?id=' . $cita_id);
                exit;
            } else {
                setAlert('danger', 'Error al actualizar la nota');
            }
        } catch (PDOException $e) {
            setAlert('danger', 'Error en la base de datos: ' . $e->getMessage());
        }
    }
}

// Título y migas de pan
$pageTitle = "Editar Nota de Sesión";
$breadcrumbs = [
    ['link' => '../dashboard', 'title' => 'Inicio'],
    ['link' => 'list.php', 'title' => 'Citas'],
    ['link' => 'view.php?id=' . $cita_id, 'title' => 'Detalle de Cita'],
    ['link' => '#', 'title' => 'Editar Nota']
];

include_once '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <?php include_once '../../includes/breadcrumb.php'; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Editar Nota de Sesión para la cita de <?php echo htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']); ?>
        </div>
        <div class="card-body">
            <form method="POST" action="edit_nota.php?id=<?php echo $nota_id; ?>&cita_id=<?php echo $cita_id; ?>">
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($nota['titulo']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="contenido" name="contenido" rows="10"><?php echo htmlspecialchars($nota['contenido']); ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="view.php?id=<?php echo $cita_id; ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TinyMCE -->
<script src="<?= BASE_URL ?>/assets/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
    tinymce.init({
        selector: '#contenido',
        height: 300,
        menubar: false,
        language: 'es',
        plugins: [
            'code', 'link', 'lists', 'table'
        ],
        toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
</script>

<?php include_once '../../includes/footer.php'; ?>
