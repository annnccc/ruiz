<?php
// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once 'header_fix.php';

// Verificar si el usuario ha iniciado sesión y es administrador
if (!isset($_SESSION['usuario_id']) || !esAdmin()) {
    header('Location: ../../login.php');
    exit();
}

// Título y breadcrumbs para la página
$titulo_pagina = "Corrección de Tabla Configuración";
$breadcrumbs = [
    ['nombre' => 'Inicio', 'enlace' => '../../index.php'],
    ['nombre' => 'Configuración', 'enlace' => '../configuracion/list.php'],
    ['nombre' => 'Corregir Tabla', 'enlace' => '#']
];

$mensaje = "";
$tipo_alerta = "";
$header_cargado = false;

try {
    // Conectar a la base de datos
    $db = getDB();
    
    // Verificar si estamos en modo de ejecución
    $modo_ejecucion = isset($_GET['ejecutar']) && $_GET['ejecutar'] == '1';
    
    if ($modo_ejecucion) {
        // Iniciar transacción para seguridad
        $db->beginTransaction();
        
        // 1. Comprobar la estructura actual de la tabla
        $stmt = $db->query("DESCRIBE configuracion");
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Si la tabla tiene la estructura antigua
        if (in_array('nombre_clinica', $columnas) && !in_array('clave', $columnas)) {
            // 2. Crear una tabla temporal con la nueva estructura
            $db->exec("CREATE TABLE configuracion_temp (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(50) NOT NULL UNIQUE,
                valor TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // 3. Migrar los datos existentes a la nueva estructura
            // Obtener los datos de la tabla actual
            $stmt = $db->query("SELECT * FROM configuracion LIMIT 1");
            $config_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config_actual) {
                // Insertar cada campo como una fila en la nueva estructura
                $stmt = $db->prepare("INSERT INTO configuracion_temp (clave, valor) VALUES (?, ?)");
                
                // Migrar cada campo como un par clave-valor
                foreach ($config_actual as $campo => $valor) {
                    if ($campo != 'id' && $campo != 'fecha_creacion' && $campo != 'fecha_actualizacion') {
                        $stmt->execute([$campo, $valor]);
                    }
                }
                
                // 4. Añadir configuraciones adicionales necesarias
                $configuraciones_adicionales = [
                    'nombre_sistema' => $config_actual['nombre_clinica'] ?? 'Clínica Ruiz',
                    'email_contacto' => $config_actual['email'] ?? '',
                    'telefono_contacto' => $config_actual['telefono'] ?? '',
                    'color_primario' => '#6366f1',
                    'color_secundario' => '#0ea5e9',
                    'favicon' => 'assets/img/favicon.ico'
                ];
                
                foreach ($configuraciones_adicionales as $clave => $valor) {
                    $check = $db->prepare("SELECT COUNT(*) FROM configuracion_temp WHERE clave = ?");
                    $check->execute([$clave]);
                    
                    if ($check->fetchColumn() == 0) {
                        $stmt->execute([$clave, $valor]);
                    }
                }
                
                // 5. Eliminar la tabla original y renombrar la temporal
                $db->exec("DROP TABLE configuracion");
                $db->exec("RENAME TABLE configuracion_temp TO configuracion");
                
                // Confirmar transacción
                $db->commit();
                
                $mensaje = "¡La tabla de configuración ha sido corregida exitosamente!";
                $tipo_alerta = "success";
            } else {
                // No hay datos en la tabla actual
                $db->exec("DROP TABLE configuracion");
                $db->exec("RENAME TABLE configuracion_temp TO configuracion");
                
                // Insertar configuraciones por defecto
                $configuraciones_default = [
                    'nombre_sistema' => 'Clínica Ruiz',
                    'email_contacto' => '',
                    'telefono_contacto' => '',
                    'direccion' => '',
                    'color_primario' => '#6366f1',
                    'color_secundario' => '#0ea5e9',
                    'logo' => 'assets/img/logo.png',
                    'favicon' => 'assets/img/favicon.ico'
                ];
                
                $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?)");
                foreach ($configuraciones_default as $clave => $valor) {
                    $stmt->execute([$clave, $valor]);
                }
                
                $db->commit();
                
                $mensaje = "¡La tabla de configuración ha sido recreada con valores por defecto!";
                $tipo_alerta = "success";
            }
        } elseif (in_array('clave', $columnas)) {
            // La tabla ya tiene la estructura correcta
            $db->rollBack();  // No es necesario hacer cambios
            $mensaje = "La tabla de configuración ya tiene la estructura correcta.";
            $tipo_alerta = "info";
        } else {
            // Estructura desconocida
            $db->rollBack();
            $mensaje = "La estructura de la tabla configuración no coincide con ninguna versión conocida.";
            $tipo_alerta = "danger";
        }
    } else {
        // Modo de análisis (sin ejecutar cambios)
        $stmt = $db->query("DESCRIBE configuracion");
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('nombre_clinica', $columnas) && !in_array('clave', $columnas)) {
            $mensaje = "La tabla configuración tiene la estructura antigua y necesita ser corregida.";
            $tipo_alerta = "warning";
        } elseif (in_array('clave', $columnas)) {
            $mensaje = "La tabla configuración ya tiene la estructura correcta y no necesita cambios.";
            $tipo_alerta = "info";
        } else {
            $mensaje = "No se puede determinar la estructura de la tabla configuración.";
            $tipo_alerta = "danger";
        }
    }
    
    // Intentar cargar el header normal
    try {
        include_once '../../includes/header.php';
        $header_cargado = true;
    } catch (Exception $e) {
        // Si falla, usamos nuestro header alternativo
        mostrarHeadRecursos();
    }
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $mensaje = "Error en la base de datos: " . $e->getMessage();
    $tipo_alerta = "danger";
    
    // Usar header alternativo
    mostrarHeadRecursos();
}

// Si no se ha cargado el header aún, cargarlo
if (!$header_cargado) {
    mostrarHeadRecursos();
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col">
            <!-- Título -->
            <h1 class="mb-4"><i class="material-symbols-rounded me-2">tools</i><?php echo $titulo_pagina; ?></h1>
            
            <!-- Navegación -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumbs as $index => $item): ?>
                        <?php if ($index === count($breadcrumbs) - 1): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $item['nombre']; ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="<?php echo $item['enlace']; ?>"><?php echo $item['nombre']; ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
            
            <!-- Alerta de resultado -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_alerta; ?>" role="alert">
                    <i class="material-symbols-rounded me-2"><?php echo $tipo_alerta == 'success' ? 'check_circle' : ($tipo_alerta == 'info' ? 'info' : 'warning'); ?></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <!-- Tarjeta de información -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="material-symbols-rounded me-2">info</i>Información</h5>
                </div>
                <div class="card-body">
                    <p>Esta herramienta corrige la estructura de la tabla <code>configuracion</code> para hacer compatible el sistema con las nuevas actualizaciones.</p>
                    
                    <h5 class="mt-4">¿Qué hace esta herramienta?</h5>
                    <ol>
                        <li>Analiza la estructura actual de la tabla de configuración</li>
                        <li>Crea una nueva tabla con la estructura correcta de clave-valor</li>
                        <li>Migra los datos existentes a la nueva estructura</li>
                        <li>Reemplaza la tabla antigua por la nueva</li>
                    </ol>
                    
                    <div class="alert alert-warning">
                        <i class="material-symbols-rounded me-2">warning</i>
                        <strong>Importante:</strong> Es recomendable hacer una copia de seguridad de la base de datos antes de ejecutar esta corrección.
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="../../index.php" class="btn btn-secondary">
                            <i class="material-symbols-rounded me-2">home</i>Volver al inicio
                        </a>
                        
                        <?php if ($tipo_alerta == "warning"): ?>
                            <a href="?ejecutar=1" class="btn btn-danger">
                                <i class="material-symbols-rounded me-2">build</i>Ejecutar corrección
                            </a>
                        <?php elseif ($tipo_alerta == "success"): ?>
                            <a href="../configuracion/list.php" class="btn btn-primary">
                                <i class="material-symbols-rounded me-2">settings</i>Ir a Configuración
                            </a>
                        <?php else: ?>
                            <a href="../configuracion/list.php" class="btn btn-primary">
                                <i class="material-symbols-rounded me-2">settings</i>Ir a Configuración
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer
if ($header_cargado) {
    include_once '../../includes/footer.php';
} else {
    mostrarFooterRecursos();
}
?> 