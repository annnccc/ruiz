<?php
/**
 * Módulo de Configuración - Sistema de Backup
 * Permite gestionar copias de seguridad de la base de datos y archivos
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Obtener conexión a la base de datos
$db = getDB();

// Título de la página
$pageTitle = "Sistema de Backup";

// Directorio donde se guardarán los backups
$backupDir = ROOT_PATH . '/backups';

// Crear el directorio si no existe
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Mensajes de estado
$mensaje = '';
$error = false;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear backup
    if (isset($_POST['action']) && $_POST['action'] === 'crear_backup') {
        try {
            // Nombre del archivo de backup (fecha y hora)
            $timestamp = date('Y-m-d_H-i-s');
            $tipo_backup = isset($_POST['tipo_backup']) ? $_POST['tipo_backup'] : 'db';
            
            // Información de conexión para la base de datos
            $host = DB_HOST;
            $puerto = DB_PORT;
            $usuario = DB_USER;
            $password = DB_PASS;
            $baseDatos = DB_NAME;
            
            $descripcion = "";
            $tamanoTotal = 0;
            $backupFileName = "";
            $backupFilePath = "";
            
            // Backup de base de datos
            if ($tipo_backup == 'db' || $tipo_backup == 'completo') {
                // Crear el nombre del archivo SQL para la base de datos
                $backupFileName = "backup_db_{$timestamp}.sql";
                $backupFilePath = $backupDir . '/' . $backupFileName;
                
                // Comando para hacer backup con mysqldump
                $comando = "mysqldump --host={$host} --port={$puerto} --user={$usuario} --password={$password} {$baseDatos} > {$backupFilePath}";
                
                // Ejecutar el comando para crear el backup
                exec($comando, $output, $returnVar);
                
                if ($returnVar === 0) {
                    $tamanoTotal = filesize($backupFilePath);
                    $descripcion .= "Base de datos: {$baseDatos}\n";
                } else {
                    throw new Exception("Error al crear el backup de la base de datos. Código: {$returnVar}");
                }
            }
            
            // Backup de archivos
            if ($tipo_backup == 'archivos' || $tipo_backup == 'completo') {
                // Para backups de archivos, crearemos una carpeta nueva
                $archivosBackupDir = $backupDir . "/files_backup_{$timestamp}";
                
                if (!file_exists($archivosBackupDir)) {
                    mkdir($archivosBackupDir, 0755, true);
                }
                
                // Directorio que contiene los archivos del sistema (excluyendo el directorio de backups)
                $directorios = [
                    'includes' => ROOT_PATH . '/includes',
                    'assets' => ROOT_PATH . '/assets',
                    'modules' => ROOT_PATH . '/modules'
                ];
                
                foreach ($directorios as $nombre => $dir) {
                    if (file_exists($dir)) {
                        // Destino para este directorio en el backup
                        $destinoDir = $archivosBackupDir . '/' . basename($dir);
                        
                        // Copiar directorio
                        $tamanoDir = copiarDirectorio($dir, $destinoDir);
                        $tamanoTotal += $tamanoDir;
                        $descripcion .= "Directorio: {$nombre}\n";
                    }
                }
                
                // Si solo hacemos backup de archivos, actualizamos el nombre del archivo
                if ($tipo_backup == 'archivos') {
                    $backupFileName = "backup_files_{$timestamp}";
                    $backupFilePath = $archivosBackupDir;
                } else {
                    // Si es completo, ya tenemos el SQL, solo actualizamos la descripción
                    $descripcion .= "Archivos guardados en: files_backup_{$timestamp}\n";
                }
            }
            
            // Registrar en la base de datos
            $stmt = $db->prepare("INSERT INTO backups (nombre, ruta, tamano, tipo, descripcion, fecha_creacion, usuario_id) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([
                $backupFileName,
                $backupFilePath,
                $tamanoTotal,
                $tipo_backup,
                $descripcion,
                $_SESSION['usuario_id']
            ]);
            
            $mensaje = "Copia de seguridad creada correctamente: {$backupFileName}";
        } catch (Exception $e) {
            $error = true;
            $mensaje = "Error: " . $e->getMessage();
        }
    }
    
    // Restaurar backup
    elseif (isset($_POST['action']) && $_POST['action'] === 'restaurar_backup' && isset($_POST['backup_id'])) {
        try {
            // Obtener información del backup seleccionado
            $stmt = $db->prepare("SELECT * FROM backups WHERE id = ?");
            $stmt->execute([$_POST['backup_id']]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($backup) {
                $backupFile = $backup['ruta'];
                $tipo_backup = $backup['tipo'];
                
                // Restaurar base de datos (archivo SQL)
                if (($tipo_backup == 'db') && file_exists($backupFile) && pathinfo($backupFile, PATHINFO_EXTENSION) == 'sql') {
                    // Información de conexión
                    $host = DB_HOST;
                    $puerto = DB_PORT;
                    $usuario = DB_USER;
                    $password = DB_PASS;
                    $baseDatos = DB_NAME;
                    
                    // Comando para restaurar el backup
                    $comando = "mysql --host={$host} --port={$puerto} --user={$usuario} --password={$password} {$baseDatos} < {$backupFile}";
                    
                    // Ejecutar el comando para restaurar
                    exec($comando, $output, $returnVar);
                    
                    if ($returnVar !== 0) {
                        throw new Exception("Error al restaurar la base de datos. Código: {$returnVar}");
                    }
                }
                // Restaurar archivos (directorio)
                elseif ($tipo_backup == 'archivos' && is_dir($backupFile)) {
                    $directorios = ['includes', 'assets', 'modules'];
                    
                    foreach ($directorios as $dir) {
                        $sourceDir = $backupFile . '/' . $dir;
                        $targetDir = ROOT_PATH . '/' . $dir;
                        
                        if (file_exists($sourceDir)) {
                            // Copiar archivos del directorio de backup al sistema
                            copiarDirectorio($sourceDir, $targetDir);
                        }
                    }
                }
                // Restaurar completo (SQL y directorio)
                elseif ($tipo_backup == 'completo') {
                    // Primero restaurar la BD si existe el archivo SQL
                    if (file_exists($backupFile) && pathinfo($backupFile, PATHINFO_EXTENSION) == 'sql') {
                        // Información de conexión
                        $host = DB_HOST;
                        $puerto = DB_PORT;
                        $usuario = DB_USER;
                        $password = DB_PASS;
                        $baseDatos = DB_NAME;
                        
                        // Comando para restaurar el backup
                        $comando = "mysql --host={$host} --port={$puerto} --user={$usuario} --password={$password} {$baseDatos} < {$backupFile}";
                        
                        // Ejecutar el comando para restaurar
                        exec($comando, $output, $returnVar);
                        
                        if ($returnVar !== 0) {
                            throw new Exception("Error al restaurar la base de datos. Código: {$returnVar}");
                        }
                    }
                    
                    // Luego buscar la carpeta de archivos correspondiente
                    $filesBackupDir = str_replace('backup_db_', 'files_backup_', $backupFile);
                    $filesBackupDir = str_replace('.sql', '', $filesBackupDir);
                    
                    if (is_dir($filesBackupDir)) {
                        $directorios = ['includes', 'assets', 'modules'];
                        
                        foreach ($directorios as $dir) {
                            $sourceDir = $filesBackupDir . '/' . $dir;
                            $targetDir = ROOT_PATH . '/' . $dir;
                            
                            if (file_exists($sourceDir)) {
                                // Copiar archivos del directorio de backup al sistema
                                copiarDirectorio($sourceDir, $targetDir);
                            }
                        }
                    }
                }
                else {
                    throw new Exception("Tipo de backup no reconocido o archivo no encontrado.");
                }
                
                // Registrar la restauración
                $stmt = $db->prepare("UPDATE backups SET ultima_restauracion = NOW() WHERE id = ?");
                $stmt->execute([$_POST['backup_id']]);
                
                $mensaje = "Backup restaurado correctamente.";
            } else {
                $error = true;
                $mensaje = "Backup no encontrado.";
            }
        } catch (Exception $e) {
            $error = true;
            $mensaje = "Error: " . $e->getMessage();
        }
    }
    
    // Eliminar backup
    elseif (isset($_POST['action']) && $_POST['action'] === 'eliminar_backup' && isset($_POST['backup_id'])) {
        try {
            // Obtener información del backup
            $stmt = $db->prepare("SELECT * FROM backups WHERE id = ?");
            $stmt->execute([$_POST['backup_id']]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($backup) {
                // Eliminar archivo o directorio
                if (file_exists($backup['ruta'])) {
                    if (is_dir($backup['ruta'])) {
                        // Si es un directorio, eliminar recursivamente
                        eliminarDirectorio($backup['ruta']);
                    } else {
                        // Si es un archivo, eliminar directamente
                        unlink($backup['ruta']);
                        
                        // Si es un backup completo, buscar y eliminar también el directorio de archivos
                        if ($backup['tipo'] == 'completo') {
                            $filesBackupDir = str_replace('backup_db_', 'files_backup_', $backup['ruta']);
                            $filesBackupDir = str_replace('.sql', '', $filesBackupDir);
                            
                            if (is_dir($filesBackupDir)) {
                                eliminarDirectorio($filesBackupDir);
                            }
                        }
                    }
                }
                
                // Eliminar el registro de la base de datos
                $stmt = $db->prepare("DELETE FROM backups WHERE id = ?");
                $stmt->execute([$_POST['backup_id']]);
                
                $mensaje = "Backup eliminado correctamente.";
            } else {
                $error = true;
                $mensaje = "No se pudo encontrar el archivo de backup.";
            }
        } catch (Exception $e) {
            $error = true;
            $mensaje = "Error: " . $e->getMessage();
        }
    }
    
    // Descargar backup
    elseif (isset($_POST['action']) && $_POST['action'] === 'descargar_backup' && isset($_POST['backup_id'])) {
        try {
            // Obtener información del backup
            $stmt = $db->prepare("SELECT * FROM backups WHERE id = ?");
            $stmt->execute([$_POST['backup_id']]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($backup && file_exists($backup['ruta']) && !is_dir($backup['ruta'])) {
                // Preparar para la descarga solo si es un archivo (no un directorio)
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($backup['ruta']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($backup['ruta']));
                readfile($backup['ruta']);
                exit;
            } else if ($backup && is_dir($backup['ruta'])) {
                $error = true;
                $mensaje = "No se puede descargar directamente un directorio de backup.";
            } else {
                $error = true;
                $mensaje = "No se pudo encontrar el archivo de backup.";
            }
        } catch (Exception $e) {
            $error = true;
            $mensaje = "Error: " . $e->getMessage();
        }
    }
    
    // Subir backup
    elseif (isset($_POST['action']) && $_POST['action'] === 'subir_backup' && isset($_FILES['backup_file'])) {
        try {
            $uploadedFile = $_FILES['backup_file'];
            
            // Verificar si hay errores en la subida
            if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
                // Verificar extensión del archivo
                $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                
                if (strtolower($fileExtension) === 'sql') {
                    // Crear un nombre único para el archivo
                    $timestamp = date('Y-m-d_H-i-s');
                    $backupFileName = "backup_uploaded_{$timestamp}.sql";
                    $targetFilePath = $backupDir . '/' . $backupFileName;
                    
                    // Mover el archivo subido al directorio de backups
                    if (move_uploaded_file($uploadedFile['tmp_name'], $targetFilePath)) {
                        // Determinar el tipo de backup
                        $tipo = 'db';
                        $descripcion = "Backup SQL subido manualmente";
                        
                        // Registrar en la base de datos
                        $stmt = $db->prepare("INSERT INTO backups (nombre, ruta, tamano, tipo, descripcion, fecha_creacion, usuario_id) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                        $fileSize = filesize($targetFilePath);
                        $stmt->execute([
                            $backupFileName,
                            $targetFilePath,
                            $fileSize,
                            $tipo,
                            $descripcion,
                            $_SESSION['usuario_id']
                        ]);
                        
                        $mensaje = "Backup subido correctamente: {$backupFileName}";
                    } else {
                        $error = true;
                        $mensaje = "Error al mover el archivo subido.";
                    }
                } else {
                    $error = true;
                    $mensaje = "Solo se permiten archivos SQL para backups de base de datos.";
                }
            } else {
                $error = true;
                $mensaje = "Error al subir el archivo: " . getUploadErrorMessage($uploadedFile['error']);
            }
        } catch (Exception $e) {
            $error = true;
            $mensaje = "Error: " . $e->getMessage();
        }
    }
}

// Obtener lista de backups existentes
try {
    // Crear tabla si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS backups (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(255) NOT NULL,
        ruta VARCHAR(255) NOT NULL,
        tamano BIGINT NOT NULL,
        tipo ENUM('db', 'archivos', 'completo', 'desconocido') NOT NULL DEFAULT 'db',
        descripcion TEXT NULL,
        fecha_creacion DATETIME NOT NULL,
        ultima_restauracion DATETIME NULL,
        usuario_id INT(11) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Actualizar tabla si existe versión antigua
    $result = $db->query("SHOW COLUMNS FROM backups LIKE 'tipo'");
    if ($result->rowCount() == 0) {
        $db->exec("ALTER TABLE backups ADD COLUMN tipo ENUM('db', 'archivos', 'completo', 'desconocido') NOT NULL DEFAULT 'db' AFTER tamano");
        $db->exec("ALTER TABLE backups ADD COLUMN descripcion TEXT NULL AFTER tipo");
        // Actualizar registros existentes como tipo 'db'
        $db->exec("UPDATE backups SET tipo = 'db' WHERE tipo IS NULL");
    }
    
    // Obtener todos los backups
    $stmt = $db->query("SELECT b.*, u.nombre as usuario_nombre FROM backups b 
                       LEFT JOIN usuarios u ON b.usuario_id = u.id 
                       ORDER BY b.fecha_creacion DESC");
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = true;
    $mensaje = "Error al obtener la lista de backups: " . $e->getMessage();
    $backups = [];
}

// Iniciar captura del contenido de la página
startPageContent();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">backup</span>
        Sistema de Backup
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/configuracion/index.php">Configuración</a></li>
        <li class="breadcrumb-item active">Sistema de Backup</li>
    </ol>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $error ? 'danger' : 'success' ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Panel de Acción -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">settings</span>
                        Opciones de Backup
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Crear Backup -->
                    <div class="mb-4">
                        <h6 class="mb-3">Crear Copia de Seguridad</h6>
                        <p class="text-muted small">Seleccione el tipo de copia que desea realizar.</p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="crear_backup">
                            
                            <div class="mb-3">
                                <label class="form-label d-block">Tipo de Backup:</label>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="tipo_backup" id="tipo_backup_db" value="db" checked>
                                    <label class="form-check-label" for="tipo_backup_db">
                                        <span class="material-symbols-rounded align-middle me-1">database</span>
                                        Solo Base de Datos
                                    </label>
                                    <small class="text-muted d-block ps-4">Guarda solo los datos de la base de datos.</small>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="tipo_backup" id="tipo_backup_archivos" value="archivos">
                                    <label class="form-check-label" for="tipo_backup_archivos">
                                        <span class="material-symbols-rounded align-middle me-1">folder</span>
                                        Solo Archivos
                                    </label>
                                    <small class="text-muted d-block ps-4">Guarda solo los archivos de la aplicación.</small>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_backup" id="tipo_backup_completo" value="completo">
                                    <label class="form-check-label" for="tipo_backup_completo">
                                        <span class="material-symbols-rounded align-middle me-1">all_inclusive</span>
                                        Completo
                                    </label>
                                    <small class="text-muted d-block ps-4">Guarda tanto la base de datos como los archivos.</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <span class="material-symbols-rounded me-1">add</span>
                                Crear Backup Ahora
                            </button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <!-- Subir Backup -->
                    <div class="mb-4">
                        <h6 class="mb-3">Subir Copia de Seguridad</h6>
                        <p class="text-muted small">Sube un archivo SQL de base de datos.</p>
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="subir_backup">
                            <div class="mb-3">
                                <input type="file" class="form-control" name="backup_file" accept=".sql" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <span class="material-symbols-rounded me-1">upload</span>
                                Subir Backup
                            </button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <!-- Información del Sistema -->
                    <div>
                        <h6 class="mb-3">Información del Sistema</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Base de datos:</dt>
                            <dd class="col-sm-6"><?= DB_NAME ?></dd>
                            
                            <dt class="col-sm-6">Servidor:</dt>
                            <dd class="col-sm-6"><?= DB_HOST ?></dd>
                            
                            <dt class="col-sm-6">Total backups:</dt>
                            <dd class="col-sm-6"><?= count($backups) ?></dd>
                            
                            <dt class="col-sm-6">Directorio:</dt>
                            <dd class="col-sm-6"><code><?= basename($backupDir) ?></code></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Backups -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">list</span>
                        Copias de Seguridad Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <div class="alert alert-info">
                            <span class="material-symbols-rounded me-1">info</span>
                            No hay copias de seguridad disponibles. Cree una nueva copia usando las opciones del panel izquierdo.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Tamaño</th>
                                        <th>Fecha Creación</th>
                                        <th>Última Restauración</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($backup['nombre']) ?></td>
                                            <td>
                                                <?php 
                                                    $tipoBadge = '';
                                                    $tipoTexto = '';
                                                    
                                                    switch($backup['tipo']) {
                                                        case 'db':
                                                            $tipoBadge = 'bg-info';
                                                            $tipoTexto = 'Base de Datos';
                                                            break;
                                                        case 'archivos':
                                                            $tipoBadge = 'bg-success';
                                                            $tipoTexto = 'Archivos';
                                                            break;
                                                        case 'completo':
                                                            $tipoBadge = 'bg-primary';
                                                            $tipoTexto = 'Completo';
                                                            break;
                                                        default:
                                                            $tipoBadge = 'bg-secondary';
                                                            $tipoTexto = 'Desconocido';
                                                    }
                                                ?>
                                                <span class="badge <?= $tipoBadge ?>"><?= $tipoTexto ?></span>
                                            </td>
                                            <td><?= formatBytes($backup['tamano']) ?></td>
                                            <td><?= formatDateTime($backup['fecha_creacion']) ?></td>
                                            <td>
                                                <?= $backup['ultima_restauracion'] ? formatDateTime($backup['ultima_restauracion']) : '<span class="text-muted">Nunca</span>' ?>
                                            </td>
                                            <td><?= htmlspecialchars($backup['usuario_nombre']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <!-- Botón de Restaurar -->
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea RESTAURAR esta copia de seguridad? Esto sobrescribirá los datos actuales según el tipo de backup (<?= $tipoTexto ?>).');">
                                                        <input type="hidden" name="action" value="restaurar_backup">
                                                        <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Restaurar">
                                                            <span class="material-symbols-rounded">restore</span>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Botón de Descargar (solo para archivos SQL, no directorios) -->
                                                    <?php if ($backup['tipo'] == 'db' || (file_exists($backup['ruta']) && !is_dir($backup['ruta']))): ?>
                                                    <form method="post" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="descargar_backup">
                                                        <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-info" title="Descargar">
                                                            <span class="material-symbols-rounded">download</span>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Botón de Eliminar -->
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('¿Está seguro de que desea ELIMINAR esta copia de seguridad? Esta acción no se puede deshacer.');">
                                                        <input type="hidden" name="action" value="eliminar_backup">
                                                        <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <span class="material-symbols-rounded">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instrucciones de Backup -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0">
                        <span class="material-symbols-rounded me-1">help</span>
                        Información sobre Copias de Seguridad
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        <h6 class="alert-heading">
                            <span class="material-symbols-rounded me-1">warning</span>
                            Importante
                        </h6>
                        <p class="mb-0">La restauración de una copia de seguridad sobrescribirá los datos actuales según el tipo de backup. Asegúrese de tener una copia actualizada antes de realizar esta operación.</p>
                    </div>
                    
                    <h6>Tipos de backup disponibles:</h6>
                    <ul class="mb-4">
                        <li><strong>Base de datos:</strong> Guarda solo los datos almacenados en la base de datos MySQL (archivos .sql).</li>
                        <li><strong>Archivos:</strong> Guarda solo los archivos de la aplicación (código, imágenes, etc.) como carpetas.</li>
                        <li><strong>Completo:</strong> Guarda tanto la base de datos como los archivos de la aplicación.</li>
                    </ul>
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Nota:</strong> Esta versión del sistema de backup guarda los archivos sin comprimir. Los archivos SQL se pueden descargar directamente, pero los backups de archivos no.</p>
                    </div>
                    
                    <h6>Recomendaciones:</h6>
                    <ul>
                        <li>Realice copias de seguridad periódicas, especialmente antes de actualizaciones importantes del sistema.</li>
                        <li>Descargue y almacene las copias de la base de datos en un lugar seguro fuera del servidor.</li>
                        <li>Etiquete sus copias con información sobre su contenido y propósito.</li>
                        <li>Pruebe periódicamente la restauración de copias para verificar su integridad.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Copia un directorio completo a otro destino recursivamente
 * 
 * @param string $source Directorio fuente
 * @param string $destination Directorio destino
 * @return int Tamaño total de los archivos copiados en bytes
 */
function copiarDirectorio($source, $destination) {
    $tamanoTotal = 0;
    
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $source . '/' . $file;
            $destFile = $destination . '/' . $file;
            
            if (is_dir($srcFile)) {
                // Es un directorio, copiar recursivamente
                $tamanoTotal += copiarDirectorio($srcFile, $destFile);
            } else {
                // Es un archivo, copiar directamente
                copy($srcFile, $destFile);
                $tamanoTotal += filesize($srcFile);
            }
        }
    }
    
    closedir($dir);
    return $tamanoTotal;
}

/**
 * Elimina un directorio completo y todos sus contenidos recursivamente
 * 
 * @param string $directorio Directorio a eliminar
 * @return bool Éxito de la operación
 */
function eliminarDirectorio($directorio) {
    if (!file_exists($directorio)) {
        return true;
    }
    
    $archivos = array_diff(scandir($directorio), ['.', '..']);
    
    foreach ($archivos as $archivo) {
        $ruta = $directorio . '/' . $archivo;
        
        if (is_dir($ruta)) {
            eliminarDirectorio($ruta);
        } else {
            unlink($ruta);
        }
    }
    
    return rmdir($directorio);
}

/**
 * Formatea un tamaño en bytes a una representación más legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Formatea una fecha y hora de MySQL a un formato más legible
 */
function formatDateTime($dateTime) {
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y H:i:s');
}

/**
 * Devuelve un mensaje descriptivo para un código de error de subida de archivos
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "El archivo es demasiado grande (excede el tamaño permitido por el servidor).";
        case UPLOAD_ERR_FORM_SIZE:
            return "El archivo es demasiado grande (excede el tamaño permitido por el formulario).";
        case UPLOAD_ERR_PARTIAL:
            return "El archivo se subió solo parcialmente.";
        case UPLOAD_ERR_NO_FILE:
            return "No se seleccionó ningún archivo para subir.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Falta la carpeta temporal del servidor.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Error al escribir el archivo en el disco.";
        case UPLOAD_ERR_EXTENSION:
            return "La subida del archivo fue detenida por una extensión de PHP.";
        default:
            return "Error desconocido en la subida del archivo.";
    }
}

// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 