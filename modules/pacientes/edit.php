<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

/**
 * Función para validar el formato de fecha
 * @param string $fecha La fecha a validar
 * @return bool True si la fecha es válida, false en caso contrario
 */
function validarFecha($fecha) {
    // Si está en formato Y-m-d (YYYY-MM-DD)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $partes = explode('-', $fecha);
        return checkdate($partes[1], $partes[2], $partes[0]);
    }
    
    // Si está en formato d/m/Y (DD/MM/YYYY)
    if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $fecha)) {
        $partes = explode('/', $fecha);
        return checkdate($partes[1], $partes[0], $partes[2]);
    }
    
    // Intentar convertir con strtotime como último recurso
    $timestamp = strtotime($fecha);
    return $timestamp !== false;
}

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('danger', 'ID de paciente no proporcionado');
    redirect('list.php');
}

$id = (int)$_GET['id'];
$errores = [];

try {
    // Obtener la conexión PDO
    $db = getDB();
    
    // Consultar los datos del paciente
    $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paciente) {
        setAlert('danger', 'Paciente no encontrado');
        redirect('list.php');
    }
    
    // Procesar el formulario cuando se envía
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recoger y validar datos del formulario
        $nombre = sanitize($_POST['nombre'] ?? '');
        $apellidos = sanitize($_POST['apellidos'] ?? '');
        $dni = sanitize($_POST['dni'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $fecha_nacimiento = sanitize($_POST['fecha_nacimiento'] ?? '');
        $notas = sanitize($_POST['notas'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');
        $codigo_postal = sanitize($_POST['codigo_postal'] ?? '');
        $ciudad = sanitize($_POST['ciudad'] ?? '');
        $provincia = sanitize($_POST['provincia'] ?? '');
        $sexo = sanitize($_POST['sexo'] ?? '');
        
        // Nuevo campo de consentimiento
        $consentimiento_firmado = isset($_POST['consentimiento_firmado']) ? 1 : 0;
        
        // Convertir fecha al formato de la base de datos (si es necesario)
        if (strpos($fecha_nacimiento, '/') !== false) {
            $fecha_nacimiento = formatDateToDB($fecha_nacimiento);
        }
        
        // Validación
        $errores = [];
        
        if (empty($nombre)) {
            $errores['nombre'] = 'El nombre es obligatorio';
        }
        
        if (empty($apellidos)) {
            $errores['apellidos'] = 'Los apellidos son obligatorios';
        }
        
        // Validar DNI solo si no está vacío
        if (!empty($dni) && !validarDNI($dni)) {
            $errores['dni'] = 'El formato del DNI/NIE no es válido';
        } elseif (!empty($dni)) {
            // Verificar si el DNI ya existe para otro paciente
            $stmt = $db->prepare("SELECT id FROM pacientes WHERE dni = :dni AND id != :id");
            $stmt->bindValue(':dni', $dni);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $errores['dni'] = 'Este DNI/NIE ya está registrado para otro paciente';
            }
        }
        
        if (empty($fecha_nacimiento)) {
            $errores['fecha_nacimiento'] = 'La fecha de nacimiento es obligatoria';
        }
        
        if (empty($telefono)) {
            $errores['telefono'] = 'El teléfono es obligatorio';
        } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
            $errores['telefono'] = 'El teléfono debe tener 9 dígitos';
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El formato del email no es válido';
        }
        
        // Si no hay errores, actualizar el paciente
        if (empty($errores)) {
            try {
                $db->beginTransaction();
                
                $sql = "UPDATE pacientes SET 
                        nombre = :nombre,
                        apellidos = :apellidos,
                        dni = :dni,
                        telefono = :telefono,
                        email = :email,
                        fecha_nacimiento = :fecha_nacimiento,
                        notas = :notas,
                        direccion = :direccion,
                        codigo_postal = :codigo_postal,
                        ciudad = :ciudad,
                        provincia = :provincia,
                        consentimiento_firmado = :consentimiento_firmado,
                        sexo = :sexo
                        WHERE id = :id";
                
                $stmt = $db->prepare($sql);
                
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':nombre', $nombre);
                $stmt->bindValue(':apellidos', $apellidos);
                
                // Asignar NULL si el DNI está vacío para evitar conflictos de unicidad
                if (empty($dni)) {
                    $stmt->bindValue(':dni', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':dni', $dni);
                }
                
                $stmt->bindValue(':telefono', $telefono);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':fecha_nacimiento', $fecha_nacimiento);
                $stmt->bindValue(':notas', $notas);
                $stmt->bindValue(':direccion', $direccion);
                $stmt->bindValue(':codigo_postal', $codigo_postal);
                $stmt->bindValue(':ciudad', $ciudad);
                $stmt->bindValue(':provincia', $provincia);
                $stmt->bindValue(':consentimiento_firmado', $consentimiento_firmado, PDO::PARAM_INT);
                $stmt->bindValue(':sexo', $sexo);
                
                if ($stmt->execute()) {
                    $db->commit();
                    setAlert('success', 'Paciente actualizado correctamente');
                    redirect('view.php?id=' . $id);
                } else {
                    $db->rollBack();
                    setAlert('danger', 'Error al actualizar el paciente');
                }
            } catch (Exception $e) {
                $db->rollBack();
                setAlert('danger', 'Error: ' . $e->getMessage());
            }
        }
    }
} catch (PDOException $e) {
    setAlert('danger', 'Error de base de datos: ' . $e->getMessage());
    redirect('list.php');
}

// Título y breadcrumbs para la página
$titulo_pagina = "Editar Paciente";
$title = $titulo_pagina;
$breadcrumbs = [
    'Pacientes' => BASE_URL . '/modules/pacientes/list.php',
    $paciente['nombre'] . ' ' . $paciente['apellidos'] => BASE_URL . '/modules/pacientes/view.php?id=' . $paciente['id'],
    'Editar' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<!-- Carga directa de Material Symbols para garantizar los iconos -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<div class="container-fluid py-4 px-4">
    <!-- Encabezado de la página -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">person_edit</span><?= $titulo_pagina ?>
        </h1>
        <div>
            <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">
                <span class="material-symbols-rounded me-2">arrow_back</span>Volver a Detalles
            </a>
        </div>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Formulario de edición -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="form-editar-paciente" method="POST" action="" class="needs-validation" novalidate>
                <!-- Datos de registro -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="num_historia" class="form-label">Número de Historia</label>
                            <input type="text" class="form-control" id="num_historia" value="<?= htmlspecialchars($paciente['num_historia'] ?? $paciente['num_expediente'] ?? 'N/A') ?>" readonly>
                            <div class="form-text">El número de historia no se puede modificar.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="fecha_registro" class="form-label">Fecha de Registro</label>
                            <input type="text" class="form-control" id="fecha_registro" value="<?= isset($paciente['fecha_registro']) ? date('d/m/Y', strtotime($paciente['fecha_registro'])) : (isset($paciente['fecha_creacion']) ? date('d/m/Y', strtotime($paciente['fecha_creacion'])) : 'N/A') ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <h5 class="card-title mb-3"><span class="material-symbols-rounded me-2">badge</span>Datos Personales</h5>
                <hr>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $paciente['nombre'] ?? '') ?>" required>
                            <?php if (isset($errores['nombre'])): ?>
                                <div class="invalid-feedback"><?= $errores['nombre'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="apellidos" class="form-label">Apellidos *</label>
                            <input type="text" class="form-control <?= isset($errores['apellidos']) ? 'is-invalid' : '' ?>" id="apellidos" name="apellidos" value="<?= htmlspecialchars($_POST['apellidos'] ?? $paciente['apellidos'] ?? '') ?>" required>
                            <?php if (isset($errores['apellidos'])): ?>
                                <div class="invalid-feedback"><?= $errores['apellidos'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="dni" class="form-label">DNI/NIE</label>
                            <input type="text" class="form-control <?= isset($errores['dni']) ? 'is-invalid' : '' ?>" id="dni" name="dni" value="<?= htmlspecialchars($_POST['dni'] ?? $paciente['dni'] ?? '') ?>">
                            <?php if (isset($errores['dni'])): ?>
                                <div class="invalid-feedback"><?= $errores['dni'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">calendar_today</span>
                                </span>
                                <input type="text" class="form-control datepicker <?= isset($errores['fecha_nacimiento']) ? 'is-invalid' : '' ?>" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : (isset($paciente['fecha_nacimiento']) ? formatDateToView($paciente['fecha_nacimiento']) : '') ?>" placeholder="DD/MM/YYYY" required>
                            </div>
                            <?php if (isset($errores['fecha_nacimiento'])): ?>
                                <div class="invalid-feedback"><?= $errores['fecha_nacimiento'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="sexo" class="form-label">Sexo *</label>
                            <select class="form-select" id="sexo" name="sexo" required>
                                <option value="" disabled>Seleccionar...</option>
                                <option value="M" <?= (isset($_POST['sexo']) && $_POST['sexo'] === 'M') || (isset($paciente['sexo']) && $paciente['sexo'] === 'M') ? 'selected' : '' ?>>Masculino</option>
                                <option value="F" <?= (isset($_POST['sexo']) && $_POST['sexo'] === 'F') || (isset($paciente['sexo']) && $paciente['sexo'] === 'F') ? 'selected' : '' ?>>Femenino</option>
                                <option value="O" <?= (isset($_POST['sexo']) && $_POST['sexo'] === 'O') || (isset($paciente['sexo']) && $paciente['sexo'] === 'O') ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h5 class="card-title mb-3 mt-4"><span class="material-symbols-rounded me-2">location_on</span>Información de Contacto</h5>
                <hr>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">call</span>
                                </span>
                                <input type="tel" class="form-control <?= isset($errores['telefono']) ? 'is-invalid' : '' ?>" id="telefono" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? $paciente['telefono'] ?? '') ?>" required>
                            </div>
                            <?php if (isset($errores['telefono'])): ?>
                                <div class="invalid-feedback"><?= $errores['telefono'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">mail</span>
                                </span>
                                <input type="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $paciente['email'] ?? '') ?>">
                            </div>
                            <?php if (isset($errores['email'])): ?>
                                <div class="invalid-feedback"><?= $errores['email'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="consentimiento_firmado" class="form-label">Consentimiento firmado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="consentimiento_firmado" name="consentimiento_firmado" value="1" <?= (isset($_POST['consentimiento_firmado']) && $_POST['consentimiento_firmado'] == 1) || (isset($paciente['consentimiento_firmado']) && $paciente['consentimiento_firmado'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="consentimiento_firmado">Sí, el paciente ha firmado el consentimiento</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-group mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">home</span>
                                </span>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? $paciente['direccion'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="form-group mb-3">
                            <label for="codigo_postal" class="form-label">Código Postal</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">markunread_mailbox</span>
                                </span>
                                <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?= htmlspecialchars($_POST['codigo_postal'] ?? $paciente['codigo_postal'] ?? '') ?>" maxlength="5">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="ciudad" class="form-label">Ciudad</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">location_city</span>
                                </span>
                                <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?= htmlspecialchars($_POST['ciudad'] ?? $paciente['ciudad'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group mb-3">
                            <label for="provincia" class="form-label">Provincia</label>
                            <select class="form-select" id="provincia" name="provincia">
                                <option value="">Seleccione una provincia</option>
                                <option value="Álava" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Álava') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Álava') ? 'selected' : '' ?>>Álava</option>
                                <option value="Albacete" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Albacete') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Albacete') ? 'selected' : '' ?>>Albacete</option>
                                <option value="Alicante" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Alicante') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Alicante') ? 'selected' : '' ?>>Alicante</option>
                                <option value="Almería" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Almería') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Almería') ? 'selected' : '' ?>>Almería</option>
                                <option value="Asturias" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Asturias') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Asturias') ? 'selected' : '' ?>>Asturias</option>
                                <option value="Ávila" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Ávila') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Ávila') ? 'selected' : '' ?>>Ávila</option>
                                <option value="Badajoz" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Badajoz') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Badajoz') ? 'selected' : '' ?>>Badajoz</option>
                                <option value="Barcelona" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Barcelona') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Barcelona') ? 'selected' : '' ?>>Barcelona</option>
                                <option value="Burgos" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Burgos') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Burgos') ? 'selected' : '' ?>>Burgos</option>
                                <option value="Cáceres" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Cáceres') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Cáceres') ? 'selected' : '' ?>>Cáceres</option>
                                <option value="Cádiz" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Cádiz') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Cádiz') ? 'selected' : '' ?>>Cádiz</option>
                                <option value="Cantabria" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Cantabria') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Cantabria') ? 'selected' : '' ?>>Cantabria</option>
                                <option value="Castellón" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Castellón') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Castellón') ? 'selected' : '' ?>>Castellón</option>
                                <option value="Ciudad Real" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Ciudad Real') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Ciudad Real') ? 'selected' : '' ?>>Ciudad Real</option>
                                <option value="Córdoba" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Córdoba') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Córdoba') ? 'selected' : '' ?>>Córdoba</option>
                                <option value="Cuenca" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Cuenca') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Cuenca') ? 'selected' : '' ?>>Cuenca</option>
                                <option value="Girona" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Girona') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Girona') ? 'selected' : '' ?>>Girona</option>
                                <option value="Granada" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Granada') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Granada') ? 'selected' : '' ?>>Granada</option>
                                <option value="Guadalajara" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Guadalajara') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Guadalajara') ? 'selected' : '' ?>>Guadalajara</option>
                                <option value="Guipúzcoa" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Guipúzcoa') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Guipúzcoa') ? 'selected' : '' ?>>Guipúzcoa</option>
                                <option value="Huelva" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Huelva') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Huelva') ? 'selected' : '' ?>>Huelva</option>
                                <option value="Huesca" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Huesca') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Huesca') ? 'selected' : '' ?>>Huesca</option>
                                <option value="Islas Baleares" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Islas Baleares') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Islas Baleares') ? 'selected' : '' ?>>Islas Baleares</option>
                                <option value="Jaén" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Jaén') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Jaén') ? 'selected' : '' ?>>Jaén</option>
                                <option value="La Coruña" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'La Coruña') || (isset($paciente['provincia']) && $paciente['provincia'] == 'La Coruña') ? 'selected' : '' ?>>La Coruña</option>
                                <option value="La Rioja" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'La Rioja') || (isset($paciente['provincia']) && $paciente['provincia'] == 'La Rioja') ? 'selected' : '' ?>>La Rioja</option>
                                <option value="Las Palmas" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Las Palmas') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Las Palmas') ? 'selected' : '' ?>>Las Palmas</option>
                                <option value="León" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'León') || (isset($paciente['provincia']) && $paciente['provincia'] == 'León') ? 'selected' : '' ?>>León</option>
                                <option value="Lleida" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Lleida') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Lleida') ? 'selected' : '' ?>>Lleida</option>
                                <option value="Lugo" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Lugo') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Lugo') ? 'selected' : '' ?>>Lugo</option>
                                <option value="Madrid" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Madrid') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Madrid') ? 'selected' : '' ?>>Madrid</option>
                                <option value="Málaga" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Málaga') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Málaga') ? 'selected' : '' ?>>Málaga</option>
                                <option value="Murcia" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Murcia') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Murcia') ? 'selected' : '' ?>>Murcia</option>
                                <option value="Navarra" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Navarra') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Navarra') ? 'selected' : '' ?>>Navarra</option>
                                <option value="Ourense" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Ourense') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Ourense') ? 'selected' : '' ?>>Ourense</option>
                                <option value="Palencia" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Palencia') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Palencia') ? 'selected' : '' ?>>Palencia</option>
                                <option value="Pontevedra" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Pontevedra') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Pontevedra') ? 'selected' : '' ?>>Pontevedra</option>
                                <option value="Salamanca" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Salamanca') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Salamanca') ? 'selected' : '' ?>>Salamanca</option>
                                <option value="Santa Cruz de Tenerife" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Santa Cruz de Tenerife') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Santa Cruz de Tenerife') ? 'selected' : '' ?>>Santa Cruz de Tenerife</option>
                                <option value="Segovia" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Segovia') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Segovia') ? 'selected' : '' ?>>Segovia</option>
                                <option value="Sevilla" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Sevilla') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Sevilla') ? 'selected' : '' ?>>Sevilla</option>
                                <option value="Soria" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Soria') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Soria') ? 'selected' : '' ?>>Soria</option>
                                <option value="Tarragona" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Tarragona') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Tarragona') ? 'selected' : '' ?>>Tarragona</option>
                                <option value="Teruel" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Teruel') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Teruel') ? 'selected' : '' ?>>Teruel</option>
                                <option value="Toledo" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Toledo') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Toledo') ? 'selected' : '' ?>>Toledo</option>
                                <option value="Valencia" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Valencia') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Valencia') ? 'selected' : '' ?>>Valencia</option>
                                <option value="Valladolid" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Valladolid') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Valladolid') ? 'selected' : '' ?>>Valladolid</option>
                                <option value="Vizcaya" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Vizcaya') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Vizcaya') ? 'selected' : '' ?>>Vizcaya</option>
                                <option value="Zamora" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Zamora') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Zamora') ? 'selected' : '' ?>>Zamora</option>
                                <option value="Zaragoza" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Zaragoza') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Zaragoza') ? 'selected' : '' ?>>Zaragoza</option>
                                <option value="Ceuta" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Ceuta') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Ceuta') ? 'selected' : '' ?>>Ceuta</option>
                                <option value="Melilla" <?= (isset($_POST['provincia']) && $_POST['provincia'] == 'Melilla') || (isset($paciente['provincia']) && $paciente['provincia'] == 'Melilla') ? 'selected' : '' ?>>Melilla</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h5 class="card-title mb-3 mt-4"><span class="material-symbols-rounded me-2">medical_information</span>Información Médica</h5>
                <hr>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= htmlspecialchars($_POST['notas'] ?? $paciente['notas'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary me-2">
                        <span class="material-symbols-rounded me-2">cancel</span>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-2">save</span>Guardar Cambios
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar datepicker
        if (typeof flatpickr === 'function') {
            flatpickr(".datepicker", {
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                locale: 'es'
            });
        } else {
            // Mostrar mensaje de error en los campos de fecha
            document.querySelectorAll('.datepicker').forEach(function(el) {
                el.classList.add('is-invalid');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = 'Error: No se pudo inicializar el selector de fecha';
                el.parentNode.appendChild(errorMsg);
            });
        }
        
        // Validación del formulario
        const form = document.getElementById('form-editar-paciente');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
</script> 