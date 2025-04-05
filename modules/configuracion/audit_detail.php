<?php
/**
 * Detalles de Registro de Auditoría
 * 
 * Este módulo muestra los detalles completos de un registro de auditoría específico,
 * incluyendo datos antiguos y nuevos para los cambios.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Comprobar permisos de acceso (solo administradores)
if (!isLoggedIn() || !esAdmin()) {
    setAlert('danger', 'No tienes permisos para acceder a esta sección.');
    header('Location: ' . BASE_URL);
    exit;
}

// Verificar ID de registro de auditoría
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('danger', 'ID de registro de auditoría no válido.');
    header('Location: ' . BASE_URL . '/modules/configuracion/audit.php');
    exit;
}

$id = (int)$_GET['id'];

// Obtener detalles del registro de auditoría
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM audit_logs WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        setAlert('danger', 'El registro de auditoría solicitado no existe.');
        header('Location: ' . BASE_URL . '/modules/configuracion/audit.php');
        exit;
    }
    
    // Obtener información del usuario si existe
    if ($registro['usuario_id'] > 0) {
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $registro['usuario_id']);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $usuario = null;
    }
    
} catch (PDOException $e) {
    setAlert('danger', 'Error al obtener detalles del registro: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/modules/configuracion/audit.php');
    exit;
}

// Título de la página
$titulo = 'Detalles de Registro de Auditoría #' . $id;
$breadcrumb = [
    ['nombre' => 'Inicio', 'enlace' => BASE_URL],
    ['nombre' => 'Configuración', 'enlace' => BASE_URL . '/modules/configuracion'],
    ['nombre' => 'Auditoría de Accesos', 'enlace' => BASE_URL . '/modules/configuracion/audit.php'],
    ['nombre' => 'Registro #' . $id, 'enlace' => '']
];

// Incluir header
include '../../includes/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">security</span><?= $titulo ?>
        </h1>
        <a href="<?= BASE_URL ?>/modules/configuracion/audit.php" class="btn btn-outline-primary">
            <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Auditoría
        </a>
    </div>
    
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Detalles del registro -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">Información General</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ID:</strong> <?= $registro['id'] ?></p>
                    <p>
                        <strong>Usuario:</strong> 
                        <?php if ($usuario): ?>
                            <a href="<?= BASE_URL ?>/modules/configuracion/usuarios_edit.php?id=<?= $usuario['id'] ?>">
                                <?= htmlspecialchars($registro['nombre_usuario']) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($registro['nombre_usuario']) ?>
                        <?php endif; ?>
                    </p>
                    <p>
                        <strong>Acción:</strong> 
                        <span class="badge bg-<?= getAccionBadgeClass($registro['accion']) ?>">
                            <?= ucfirst(htmlspecialchars($registro['accion'])) ?>
                        </span>
                    </p>
                    <p>
                        <strong>Entidad:</strong> 
                        <?= ucfirst(htmlspecialchars($registro['entidad'])) ?>
                    </p>
                    <p>
                        <strong>ID de Entidad:</strong> 
                        <?php if (canLinkEntity($registro['entidad'])): ?>
                            <a href="<?= getEntityUrl($registro['entidad'], $registro['entidad_id']) ?>">
                                <?= $registro['entidad_id'] ?>
                            </a>
                        <?php else: ?>
                            <?= $registro['entidad_id'] ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha y Hora:</strong> <?= date('d/m/Y H:i:s', strtotime($registro['fecha_hora'])) ?></p>
                    <p><strong>Dirección IP:</strong> <?= htmlspecialchars($registro['ip_address']) ?></p>
                    <p><strong>User Agent:</strong> <?= htmlspecialchars($registro['user_agent']) ?></p>
                    <p><strong>URL:</strong> <?= htmlspecialchars($registro['url']) ?></p>
                    <p><strong>Método:</strong> <?= htmlspecialchars($registro['metodo']) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($registro['datos_adicionales'])): ?>
    <!-- Datos adicionales -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">Datos Adicionales</h5>
        </div>
        <div class="card-body">
            <pre class="border rounded p-3 bg-light"><?= formatJsonData($registro['datos_adicionales']) ?></pre>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($registro['datos_antiguos']) || !empty($registro['datos_nuevos'])): ?>
    <!-- Cambios en los datos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">Cambios Realizados</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (!empty($registro['datos_antiguos'])): ?>
                <div class="col-md-6">
                    <h6 class="mb-3">Datos Anteriores</h6>
                    <pre class="border rounded p-3 bg-light"><?= formatJsonData($registro['datos_antiguos']) ?></pre>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($registro['datos_nuevos'])): ?>
                <div class="col-md-6">
                    <h6 class="mb-3">Datos Nuevos</h6>
                    <pre class="border rounded p-3 bg-light"><?= formatJsonData($registro['datos_nuevos']) ?></pre>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($registro['datos_antiguos']) && !empty($registro['datos_nuevos'])): ?>
            <div class="mt-4">
                <h6 class="mb-3">Diferencias Detectadas</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Valor Anterior</th>
                            <th>Valor Nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $datosAntiguos = json_decode($registro['datos_antiguos'], true);
                        $datosNuevos = json_decode($registro['datos_nuevos'], true);
                        $diferencias = getDiferencias($datosAntiguos, $datosNuevos);
                        
                        foreach ($diferencias as $campo => $valores): 
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($campo) ?></strong></td>
                            <td>
                                <?php if (is_array($valores['anterior'])): ?>
                                <pre class="m-0"><?= json_encode($valores['anterior'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                                <?php else: ?>
                                <?= formatValue($valores['anterior']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (is_array($valores['nuevo'])): ?>
                                <pre class="m-0"><?= json_encode($valores['nuevo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                                <?php else: ?>
                                <?= formatValue($valores['nuevo']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($diferencias)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No se detectaron diferencias en los datos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
/**
 * Obtiene la clase de badge según el tipo de acción
 */
function getAccionBadgeClass($accion) {
    $clases = [
        'ver' => 'info',
        'crear' => 'success',
        'editar' => 'warning',
        'eliminar' => 'danger',
        'descargar' => 'primary',
        'login' => 'secondary',
        'logout' => 'secondary'
    ];
    
    return $clases[$accion] ?? 'secondary';
}

/**
 * Verifica si se puede enlazar a la entidad
 */
function canLinkEntity($entidad) {
    $entidades_enlazables = ['pacientes', 'citas', 'usuarios', 'facturas'];
    return in_array($entidad, $entidades_enlazables);
}

/**
 * Obtiene la URL de la entidad
 */
function getEntityUrl($entidad, $id) {
    $urls = [
        'pacientes' => BASE_URL . '/modules/pacientes/view.php?id=',
        'citas' => BASE_URL . '/modules/citas/view.php?id=',
        'usuarios' => BASE_URL . '/modules/configuracion/usuarios_edit.php?id=',
        'facturas' => BASE_URL . '/modules/facturacion/view.php?id='
    ];
    
    return ($urls[$entidad] ?? '#') . $id;
}

/**
 * Formatea datos JSON para visualización
 */
function formatJsonData($json) {
    if (empty($json)) {
        return 'No hay datos disponibles';
    }
    
    $data = json_decode($json, true);
    if ($data === null) {
        return htmlspecialchars($json);
    }
    
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Obtiene las diferencias entre dos arrays asociativos
 */
function getDiferencias($datosAntiguos, $datosNuevos) {
    $diferencias = [];
    
    // Verificar tipos de datos
    if (!is_array($datosAntiguos) || !is_array($datosNuevos)) {
        return $diferencias;
    }
    
    // Buscar campos que han cambiado
    foreach ($datosNuevos as $campo => $valorNuevo) {
        // Si el campo existe en los datos antiguos y el valor es diferente
        if (array_key_exists($campo, $datosAntiguos) && $datosAntiguos[$campo] !== $valorNuevo) {
            $diferencias[$campo] = [
                'anterior' => $datosAntiguos[$campo],
                'nuevo' => $valorNuevo
            ];
        } 
        // Si el campo no existe en los datos antiguos (campo añadido)
        elseif (!array_key_exists($campo, $datosAntiguos)) {
            $diferencias[$campo] = [
                'anterior' => null,
                'nuevo' => $valorNuevo
            ];
        }
    }
    
    // Buscar campos que han sido eliminados
    foreach ($datosAntiguos as $campo => $valorAntiguo) {
        if (!array_key_exists($campo, $datosNuevos)) {
            $diferencias[$campo] = [
                'anterior' => $valorAntiguo,
                'nuevo' => null
            ];
        }
    }
    
    return $diferencias;
}

/**
 * Formatea un valor para visualización
 */
function formatValue($valor) {
    if ($valor === null) {
        return '<span class="text-muted">Vacío</span>';
    } elseif ($valor === '') {
        return '<span class="text-muted">Cadena vacía</span>';
    } elseif (is_bool($valor)) {
        return $valor ? '<span class="text-success">Verdadero</span>' : '<span class="text-danger">Falso</span>';
    } else {
        return htmlspecialchars((string)$valor);
    }
}

include '../../includes/layout_footer.php'; 
?> 