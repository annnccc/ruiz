<?php
/**
 * Módulo de Videoconsulta - Página principal
 * 
 * Esta página muestra las videoconsultas programadas y permite crear nuevas.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Obtener usuario actual
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : 'medico'; // Por defecto será médico si no está definido

// Obtener videoconsultas según tipo de usuario
try {
    $db = getDB();
    
    // Verificar si existe la tabla de videoconsultas
    $stmt = $db->query("SHOW TABLES LIKE 'videoconsultas'");
    if ($stmt->rowCount() === 0) {
        // Crear tabla si no existe
        $db->exec("
        CREATE TABLE IF NOT EXISTS `videoconsultas` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `paciente_id` int(11) NOT NULL,
          `medico_id` int(11) NOT NULL,
          `fecha` date NOT NULL,
          `hora_inicio` time NOT NULL,
          `hora_fin` time NOT NULL,
          `duracion` int(11) DEFAULT 30,
          `estado` enum('programada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'programada',
          `sala_id` varchar(50) NOT NULL,
          `codigo_acceso` varchar(64) DEFAULT NULL,
          `enlace_acceso` varchar(32) DEFAULT NULL,
          `fecha_expiracion` datetime DEFAULT NULL,
          `motivo` text DEFAULT NULL,
          `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `fecha_fin` datetime DEFAULT NULL,
          `duracion_real` int(11) DEFAULT NULL,
          `pin_acceso` varchar(4) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `paciente_id` (`paciente_id`),
          KEY `medico_id` (`medico_id`),
          KEY `estado` (`estado`),
          KEY `fecha` (`fecha`),
          UNIQUE KEY `codigo_acceso` (`codigo_acceso`),
          UNIQUE KEY `enlace_acceso` (`enlace_acceso`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        // Verificar si existe la columna hora_fin
        $stmtCol = $db->query("SHOW COLUMNS FROM videoconsultas LIKE 'hora_fin'");
        if ($stmtCol->rowCount() === 0) {
            // Añadir columna si no existe
            $db->exec("ALTER TABLE videoconsultas ADD COLUMN hora_fin TIME NOT NULL AFTER hora_inicio");
        }
        
        // Verificar si existe la columna pin_acceso
        $stmtCol = $db->query("SHOW COLUMNS FROM videoconsultas LIKE 'pin_acceso'");
        if ($stmtCol->rowCount() === 0) {
            // Añadir columna si no existe
            $db->exec("ALTER TABLE videoconsultas ADD COLUMN pin_acceso VARCHAR(4) DEFAULT NULL");
        }
    }
    
    // Obtener videoconsultas
    if ($tipo_usuario == 'paciente') {
        // Para paciente, usamos usuario_id como paciente_id
        $stmt = $db->prepare("
            SELECT v.*, 
                   CONCAT(u.nombre, ' ', u.apellidos) as medico_nombre,
                   DATE_FORMAT(v.fecha, '%d/%m/%Y') as fecha_formateada,
                   TIME_FORMAT(v.hora_inicio, '%H:%i') as hora_formateada
            FROM videoconsultas v
            JOIN usuarios u ON v.medico_id = u.id
            WHERE v.paciente_id = :paciente_id
            ORDER BY v.fecha DESC, v.hora_inicio DESC
        ");
        $stmt->bindParam(':paciente_id', $usuario_id, PDO::PARAM_INT);
    } else {
        // Para médico, usamos usuario_id como medico_id
        $stmt = $db->prepare("
            SELECT v.*, 
                   CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre,
                   DATE_FORMAT(v.fecha, '%d/%m/%Y') as fecha_formateada,
                   TIME_FORMAT(v.hora_inicio, '%H:%i') as hora_formateada,
                   u.nombre as nombre_usuario
            FROM videoconsultas v
            JOIN pacientes p ON v.paciente_id = p.id
            LEFT JOIN usuarios u ON v.medico_id = u.id
            WHERE v.medico_id = :medico_id
            ORDER BY v.fecha DESC, v.hora_inicio DESC
        ");
        $stmt->bindParam(':medico_id', $usuario_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $videoconsultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Manejar error
    $error = 'Error al obtener datos: ' . $e->getMessage();
}

// Incluir cabecera
$pageTitle = "Videoconsultas";
?>
<!-- Asegurarnos de que Bootstrap y los iconos se carguen correctamente -->
<link href="<?= BASE_URL ?>/assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<?php
require_once '../../includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1><span class="material-symbols-rounded me-2">videocam</span>Videoconsultas</h1>
            <p class="text-muted">Gestione sus consultas por video en tiempo real</p>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($tipo_usuario != 'paciente'): ?>
                <a href="<?= BASE_URL ?>/modules/videoconsulta/crear.php" class="btn btn-primary">
                    <span class="material-symbols-rounded me-1">add</span> Nueva videoconsulta
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <select class="form-select" id="filtroEstado">
                            <option value="">Todos los estados</option>
                            <option value="programada">Programadas</option>
                            <option value="en_curso">En curso</option>
                            <option value="finalizada">Finalizadas</option>
                            <option value="cancelada">Canceladas</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="busqueda" placeholder="Buscar...">
                    </div>
                </div>

                <?php if (empty($videoconsultas)): ?>
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <span class="material-symbols-rounded text-muted" style="font-size: 4rem;">videocam_off</span>
                        </div>
                        <h3 class="text-muted">No hay videoconsultas</h3>
                        <?php if ($tipo_usuario == 'paciente'): ?>
                            <p>Sus consultas por video aparecerán aquí cuando su médico las programe.</p>
                        <?php else: ?>
                            <p>Puede crear una nueva videoconsulta con el botón superior.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th><?= $tipo_usuario == 'paciente' ? 'Médico' : 'Paciente' ?></th>
                                    <th>Estado</th>
                                    <th>Duración</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaVideoconsultas">
                                <?php foreach ($videoconsultas as $vc): ?>
                                    <tr data-estado="<?= $vc['estado'] ?>">
                                        <td><?= $vc['fecha_formateada'] ?></td>
                                        <td><?= $vc['hora_formateada'] ?></td>
                                        <td>
                                            <?php if ($tipo_usuario != 'paciente'): ?>
                                                <?= $vc['paciente_nombre'] ?>
                                            <?php else: ?>
                                                <?= isset($vc['medico_nombre']) ? $vc['medico_nombre'] : (isset($vc['nombre_usuario']) ? $vc['nombre_usuario'] : 'Administrador') ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badgeClass = '';
                                            $estadoTexto = '';
                                            
                                            switch ($vc['estado']) {
                                                case 'programada':
                                                    $badgeClass = 'bg-primary';
                                                    $estadoTexto = 'Programada';
                                                    break;
                                                case 'en_curso':
                                                    $badgeClass = 'bg-success';
                                                    $estadoTexto = 'En curso';
                                                    break;
                                                case 'finalizada':
                                                    $badgeClass = 'bg-secondary';
                                                    $estadoTexto = 'Finalizada';
                                                    break;
                                                case 'cancelada':
                                                    $badgeClass = 'bg-danger';
                                                    $estadoTexto = 'Cancelada';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $estadoTexto ?></span>
                                        </td>
                                        <td>
                                            <?php if ($vc['estado'] == 'finalizada' && $vc['duracion_real']): ?>
                                                <?= $vc['duracion_real'] ?> min
                                            <?php else: ?>
                                                <?= $vc['duracion'] ?> min (programados)
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($vc['estado'] == 'programada' || $vc['estado'] == 'en_curso'): ?>
                                                <a href="check_permisos.php?id=<?= $vc['id'] ?>" class="btn btn-sm btn-success">
                                                    <span class="material-symbols-rounded me-1">videocam</span> Unirse
                                                </a>
                                            <?php elseif ($vc['estado'] == 'finalizada'): ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <span class="material-symbols-rounded me-1">check</span> Finalizada
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled>
                                                    <span class="material-symbols-rounded me-1">cancel</span> Cancelada
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($tipo_usuario == 'medico' && $vc['estado'] == 'programada'): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="cancelarVideoconsulta(<?= $vc['id'] ?>)">
                                                    <span class="material-symbols-rounded me-1">close</span> Cancelar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrar por estado
    const filtroEstado = document.getElementById('filtroEstado');
    const busqueda = document.getElementById('busqueda');
    const tabla = document.getElementById('tablaVideoconsultas');
    
    if (filtroEstado && busqueda && tabla) {
        const filtrarTabla = () => {
            const estadoSeleccionado = filtroEstado.value.toLowerCase();
            const textoBusqueda = busqueda.value.toLowerCase();
            const filas = tabla.querySelectorAll('tr');
            
            filas.forEach(fila => {
                const estado = fila.dataset.estado;
                const textoFila = fila.textContent.toLowerCase();
                const coincideEstado = !estadoSeleccionado || estado === estadoSeleccionado;
                const coincideTexto = !textoBusqueda || textoFila.includes(textoBusqueda);
                
                fila.style.display = (coincideEstado && coincideTexto) ? '' : 'none';
            });
        };
        
        filtroEstado.addEventListener('change', filtrarTabla);
        busqueda.addEventListener('input', filtrarTabla);
    }
});

// Función para cancelar videoconsulta
function cancelarVideoconsulta(id) {
    if (confirm('¿Está seguro de que desea cancelar esta videoconsulta?')) {
        fetch('<?= BASE_URL ?>/modules/videoconsulta/cancelar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                videoconsulta_id: id
            })
        })
        .then(response => {
            // Verificar si la respuesta es correcta
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            
            // Verificar el tipo de contenido de la respuesta
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON válido. Tipo de contenido: ' + contentType);
            }
            
            return response.text().then(text => {
                try {
                    // Intentar analizar manualmente el texto como JSON
                    return JSON.parse(text);
                } catch (e) {
                    // Si falla, mostrar el texto recibido para depuración
                    console.error('Error al analizar JSON:', e);
                    console.error('Texto recibido:', text);
                    throw new Error('Error al procesar la respuesta: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Recargar para mostrar los cambios
                window.location.reload();
            } else {
                alert('Error al cancelar la videoconsulta: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        });
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?> 