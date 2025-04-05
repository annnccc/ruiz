<?php
// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario ha iniciado sesión
requiereLogin();
requiereAdmin();

// Verificar y corregir duplicados antes de cualquier acción
if (isset($_GET['corregir_duplicados']) && $_GET['corregir_duplicados'] == 1) {
    try {
        $db = getDB();
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Listar claves SMTP que podrían estar duplicadas
        $claves_smtp = ['smtp_active', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_name'];
        
        // Para cada clave, buscar duplicados y corregir
        $log = [];
        foreach ($claves_smtp as $clave) {
            // Verificar si hay duplicados
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM configuracion WHERE clave = :clave");
            $stmt->bindParam(':clave', $clave);
            $stmt->execute();
            $total = $stmt->fetchColumn();
            
            if ($total > 1) {
                // Hay duplicados, necesitamos corregir
                $log[] = "La clave '$clave' tiene $total entradas duplicadas.";
                
                // Obtener el ID del primer registro (el que conservaremos)
                $stmt = $db->prepare("SELECT id FROM configuracion WHERE clave = :clave ORDER BY id ASC LIMIT 1");
                $stmt->bindParam(':clave', $clave);
                $stmt->execute();
                $id_conservar = $stmt->fetchColumn();
                
                // Obtener el valor más reciente (que podría estar en cualquier duplicado)
                $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = :clave ORDER BY id DESC LIMIT 1");
                $stmt->bindParam(':clave', $clave);
                $stmt->execute();
                $valor_reciente = $stmt->fetchColumn();
                
                // Actualizar el primer registro con el valor más reciente
                $stmt = $db->prepare("UPDATE configuracion SET valor = :valor WHERE id = :id");
                $stmt->bindParam(':valor', $valor_reciente);
                $stmt->bindParam(':id', $id_conservar);
                $stmt->execute();
                
                // Eliminar duplicados (todos excepto el primero)
                $stmt = $db->prepare("DELETE FROM configuracion WHERE clave = :clave AND id != :id");
                $stmt->bindParam(':clave', $clave);
                $stmt->bindParam(':id', $id_conservar);
                $stmt->execute();
                $eliminados = $stmt->rowCount();
                
                $log[] = "Se actualizó el registro con ID $id_conservar y se eliminaron $eliminados duplicados.";
            } else {
                $log[] = "La clave '$clave' no tiene duplicados.";
            }
        }
        
        // Confirmar cambios
        $db->commit();
        
        // Registrar log y mostrar mensaje de éxito
        $mensaje_log = implode("<br>", $log);
        setAlert('success', "Corrección completada con éxito.<br><small>$mensaje_log</small>");
        
    } catch (PDOException $e) {
        // Revertir cambios en caso de error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        setAlert('danger', 'Error al corregir duplicados: ' . $e->getMessage());
    }
    
    // Redirigir para evitar reenvío del formulario
    redirect(BASE_URL . '/modules/configuracion/smtp.php');
}

// Título y breadcrumbs para la página
$titulo_pagina = "Configuración SMTP";
$breadcrumbs = [
    ['nombre' => 'Inicio', 'enlace' => '../../index.php'],
    ['nombre' => 'Configuración', 'enlace' => '../configuracion/list.php'],
    ['nombre' => 'SMTP', 'enlace' => '#']
];

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Campos a actualizar
        $campos = [
            'smtp_active' => isset($_POST['smtp_active']) ? '1' : '0',
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '587',
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'email_from' => $_POST['email_from'] ?? '',
            'email_name' => $_POST['email_name'] ?? ''
        ];
        
        // Actualizar cada campo
        foreach ($campos as $clave => $valor) {
            // Primero verificar si la clave existe
            $check = $db->prepare("SELECT COUNT(*) FROM configuracion WHERE clave = :clave");
            $check->bindParam(':clave', $clave);
            $check->execute();
            $exists = $check->fetchColumn() > 0;
            
            if ($exists) {
                // Si existe, actualizar
                $update = $db->prepare("UPDATE configuracion SET valor = :valor WHERE clave = :clave");
                $update->bindParam(':clave', $clave);
                $update->bindParam(':valor', $valor);
                $update->execute();
            } else {
                // Si no existe, insertar
                $insert = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor)");
                $insert->bindParam(':clave', $clave);
                $insert->bindParam(':valor', $valor);
                $insert->execute();
            }
        }
        
        // Confirmar cambios
        $db->commit();
        setAlert('success', 'Configuración SMTP actualizada correctamente.');
        
    } catch (PDOException $e) {
        // Revertir cambios en caso de error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        setAlert('danger', 'Error al actualizar la configuración: ' . $e->getMessage());
    }
}

// Obtener la configuración actual
$config = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT clave, valor FROM configuracion WHERE clave IN ('smtp_active', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_name')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error al cargar la configuración: ' . $e->getMessage());
}

// Valores por defecto
$default_config = [
    'smtp_active' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_pass' => '',
    'email_from' => 'no-reply@' . $_SERVER['HTTP_HOST'],
    'email_name' => ''
];

// Combinar con valores por defecto
$config = array_merge($default_config, $config);

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<div class="container-fluid py-4 px-4">
    <div class="row">
        <div class="col">
            <!-- Breadcrumbs -->
            <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
            
            <!-- Título y descripción -->
            <h1 class="mb-4">
                <span class="material-symbols-rounded me-2">mail</span><?php echo $titulo_pagina; ?>
            </h1>
            <p class="lead">Configure los parámetros para el envío de correos electrónicos mediante SMTP.</p>
            
            <!-- Alertas -->
            <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
            
            <!-- Formulario de configuración -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <span class="material-symbols-rounded me-2">settings</span>Configuración de servidor SMTP
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <!-- Activar/Desactivar SMTP -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="smtp_active" name="smtp_active" <?php echo $config['smtp_active'] == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="smtp_active">Activar envío por SMTP</label>
                            <div class="form-text">Si está desactivado, se utilizará la función mail() nativa de PHP.</div>
                        </div>
                        
                        <div class="row">
                            <!-- Host SMTP -->
                            <div class="col-md-6 mb-3">
                                <label for="smtp_host" class="form-label">Servidor SMTP</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($config['smtp_host']); ?>" placeholder="smtp.example.com">
                            </div>
                            
                            <!-- Puerto SMTP -->
                            <div class="col-md-6 mb-3">
                                <label for="smtp_port" class="form-label">Puerto SMTP</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($config['smtp_port']); ?>" placeholder="587">
                                <div class="form-text">Puertos comunes: 25, 465 (SSL), 587 (TLS)</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Usuario SMTP -->
                            <div class="col-md-6 mb-3">
                                <label for="smtp_user" class="form-label">Usuario SMTP</label>
                                <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($config['smtp_user']); ?>" placeholder="usuario@example.com">
                            </div>
                            
                            <!-- Contraseña SMTP -->
                            <div class="col-md-6 mb-3">
                                <label for="smtp_pass" class="form-label">Contraseña SMTP</label>
                                <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" value="<?php echo htmlspecialchars($config['smtp_pass']); ?>" placeholder="Contraseña">
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Email remitente -->
                            <div class="col-md-6 mb-3">
                                <label for="email_from" class="form-label">Email remitente</label>
                                <input type="email" class="form-control" id="email_from" name="email_from" value="<?php echo htmlspecialchars($config['email_from']); ?>" placeholder="no-reply@example.com">
                            </div>
                            
                            <!-- Nombre remitente -->
                            <div class="col-md-6 mb-3">
                                <label for="email_name" class="form-label">Nombre del remitente</label>
                                <input type="text" class="form-control" id="email_name" name="email_name" value="<?php echo htmlspecialchars($config['email_name']); ?>" placeholder="Clínica">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="../configuracion/list.php" class="btn btn-secondary">
                                <span class="material-symbols-rounded me-2">arrow_back</span>Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded me-2">save</span>Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <span class="material-symbols-rounded me-2">info</span>Información
                    </h5>
                </div>
                <div class="card-body">
                    <p>Esta configuración se utiliza para el envío de correos electrónicos desde el sistema, incluyendo:</p>
                    <ul>
                        <li>Envío de consentimientos informados a pacientes</li>
                        <li>Recordatorios de citas</li>
                        <li>Notificaciones del sistema</li>
                    </ul>
                    <p><strong>Nota:</strong> Para utilizar un servidor SMTP externo (recomendado), es necesario tener instalada la librería PHPMailer.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar
endPageContent($titulo_pagina);
?> 