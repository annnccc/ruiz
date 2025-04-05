<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y validar datos del formulario
    $nombre = sanitize($_POST['nombre'] ?? null);
    $apellidos = sanitize($_POST['apellidos'] ?? null);
    $dni = sanitize($_POST['dni'] ?? null);
    $telefono = sanitize($_POST['telefono'] ?? null);
    $email = sanitize($_POST['email'] ?? null);
    $fecha_nacimiento = sanitize($_POST['fecha_nacimiento'] ?? null);
    $notas = sanitize($_POST['notas'] ?? null);
    
    // Validar datos
    $direccion = sanitize($_POST['direccion'] ?? null);
    $codigo_postal = sanitize($_POST['codigo_postal'] ?? null);
    $ciudad = sanitize($_POST['ciudad'] ?? null);
    $provincia = sanitize($_POST['provincia'] ?? null);
    $sexo = sanitize($_POST['sexo'] ?? null);
    
    // Consentimiento firmado (nuevo campo)
    $consentimiento_firmado = isset($_POST['consentimiento_firmado']) ? 1 : 0;
    
    // Generar número de expediente
    $num_expediente = generateExpedienteNumber();
    
    // Convertir fecha al formato de la base de datos (si es necesario)
    if (strpos($fecha_nacimiento, '/') !== false) {
        $fecha_nacimiento = formatDateToDB($fecha_nacimiento);
    }
    
    // Validaciones básicas
    $errors = [];
    
    if (empty($nombre)) {
        $errors[] = "El nombre es obligatorio";
    }
    
    if (empty($apellidos)) {
        $errors[] = "Los apellidos son obligatorios";
    }
    
    if (!empty($dni) && !isValidDNI($dni)) {
        $errors[] = "El formato del DNI no es válido";
    } elseif (!empty($dni)) {
        // Verificar si el DNI ya existe
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM pacientes WHERE dni = :dni");
        $stmt->bindValue(':dni', $dni);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors[] = "El DNI ya está registrado en el sistema";
        }
    }
    
    if (empty($fecha_nacimiento)) {
        $errors[] = "La fecha de nacimiento es obligatoria";
    }
    
    if (empty($telefono)) {
        $errors[] = "El teléfono es obligatorio";
    } elseif (!isValidPhone($telefono)) {
        $errors[] = "El formato del teléfono no es válido";
    }
    
    if (!empty($email) && !isValidEmail($email)) {
        $errors[] = "El formato del email no es válido";
    }
    
    if (empty($sexo)) {
        $errors[] = "El sexo es obligatorio";
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO pacientes (nombre, apellidos, dni, fecha_nacimiento, direccion, 
                        codigo_postal, ciudad, provincia, telefono, email, sexo, numero_historia, 
                        notas, fecha_registro, consentimiento_firmado) 
                        VALUES (:nombre, :apellidos, :dni, :fecha_nacimiento, :direccion, 
                        :codigo_postal, :ciudad, :provincia, :telefono, :email, :sexo, :numero_historia, 
                        :notas, NOW(), :consentimiento_firmado)");
            
            $stmt->bindValue(':nombre', $nombre);
            $stmt->bindValue(':apellidos', $apellidos);
            
            // Asignar NULL si el DNI está vacío para evitar conflictos de unicidad
            if (empty($dni)) {
                $stmt->bindValue(':dni', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':dni', $dni);
            }
            
            $stmt->bindValue(':fecha_nacimiento', $fecha_nacimiento);
            $stmt->bindValue(':direccion', $direccion);
            $stmt->bindValue(':codigo_postal', $codigo_postal);
            $stmt->bindValue(':ciudad', $ciudad);
            $stmt->bindValue(':provincia', $provincia);
            $stmt->bindValue(':telefono', $telefono);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':sexo', $sexo);
            $stmt->bindValue(':numero_historia', $num_expediente);
            $stmt->bindValue(':notas', $notas);
            $stmt->bindValue(':consentimiento_firmado', $consentimiento_firmado, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $paciente_id = $db->lastInsertId();
                $db->commit();
                
                // Verificar que el ID sea válido
                if ($paciente_id) {
                    setAlert('success', 'Paciente añadido correctamente');
                    redirect(BASE_URL . '/modules/pacientes/list.php');
                } else {
                    $db->rollBack();
                    setAlert('danger', 'Error al obtener el ID del paciente');
                }
            } else {
                $db->rollBack();
                setAlert('danger', 'Error al añadir el paciente');
            }
        } catch (Exception $e) {
            $db->rollBack();
            setAlert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Título y breadcrumbs para la página
$titulo_pagina = "Nuevo Paciente";
$title = $titulo_pagina;
$breadcrumbs = [
    'Pacientes' => BASE_URL . '/modules/pacientes/list.php',
    'Nuevo' => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<!-- Carga directa de Material Symbols para garantizar los iconos -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<div class="container-fluid py-4 px-4">
    <!-- Notificaciones -->
    <?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Error al guardar paciente</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Alertas -->
    <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
    
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <span class="material-symbols-rounded me-2">person_add</span>Añadir Paciente
        </h1>
        <a href="list.php" class="btn btn-secondary">
            <span class="material-symbols-rounded">arrow_back</span> Volver al Listado
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Formulario -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">edit</span>Formulario de Registro
            </h5>
        </div>
        <div class="card-body">
            <form action="" method="POST" class="needs-validation" novalidate id="form-crear-paciente">
                <!-- Datos personales -->
                <h5 class="card-title mb-3"><span class="material-symbols-rounded me-2">badge</span>Datos Personales</h5>
                <hr>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= isset($nombre) ? htmlspecialchars($nombre) : '' ?>" required>
                            <div class="invalid-feedback">El nombre es obligatorio.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="apellidos" class="form-label">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= isset($apellidos) ? htmlspecialchars($apellidos) : '' ?>" required>
                            <div class="invalid-feedback">Los apellidos son obligatorios.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="dni" class="form-label">DNI/NIE</label>
                            <input type="text" class="form-control" id="dni" name="dni" value="<?= isset($dni) ? htmlspecialchars($dni) : '' ?>" placeholder="12345678A" maxlength="9">
                            <div class="invalid-feedback">DNI inválido (formato: 12345678A).</div>
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
                                <input type="text" class="form-control datepicker" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= isset($fecha_nacimiento) ? formatDateToView($fecha_nacimiento) : '' ?>" placeholder="DD/MM/YYYY" required>
                            </div>
                            <div class="invalid-feedback">La fecha de nacimiento es obligatoria.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="sexo" class="form-label">Sexo *</label>
                            <select class="form-select" id="sexo" name="sexo" required>
                                <option value="" selected disabled>Seleccionar...</option>
                                <option value="M" <?= (isset($sexo) && $sexo === 'M') ? 'selected' : '' ?>>Masculino</option>
                                <option value="F" <?= (isset($sexo) && $sexo === 'F') ? 'selected' : '' ?>>Femenino</option>
                                <option value="O" <?= (isset($sexo) && $sexo === 'O') ? 'selected' : '' ?>>Otro</option>
                            </select>
                            <div class="invalid-feedback">El sexo es obligatorio.</div>
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
                                <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= isset($telefono) ? htmlspecialchars($telefono) : '' ?>" placeholder="600123456" required>
                            </div>
                            <div class="invalid-feedback">Teléfono inválido (debe comenzar por 6, 7, 8 o 9 y tener 9 dígitos).</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">mail</span>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" placeholder="ejemplo@email.com">
                            </div>
                            <div class="invalid-feedback">Email inválido.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="consentimiento_firmado" class="form-label">Consentimiento firmado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="consentimiento_firmado" name="consentimiento_firmado" value="1" <?= isset($consentimiento_firmado) && $consentimiento_firmado ? 'checked' : '' ?>>
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
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?= isset($direccion) ? htmlspecialchars($direccion) : '' ?>">
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
                                <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" value="<?= isset($codigo_postal) ? htmlspecialchars($codigo_postal) : '' ?>" placeholder="28001" maxlength="5">
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
                                <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?= isset($ciudad) ? htmlspecialchars($ciudad) : '' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="form-group mb-3">
                            <label for="provincia" class="form-label">Provincia</label>
                            <select class="form-select" id="provincia" name="provincia">
                                <option value="">Seleccione una provincia</option>
                                <option value="Álava" <?= isset($provincia) && $provincia == 'Álava' ? 'selected' : '' ?>>Álava</option>
                                <option value="Albacete" <?= isset($provincia) && $provincia == 'Albacete' ? 'selected' : '' ?>>Albacete</option>
                                <option value="Alicante" <?= isset($provincia) && $provincia == 'Alicante' ? 'selected' : '' ?>>Alicante</option>
                                <option value="Almería" <?= isset($provincia) && $provincia == 'Almería' ? 'selected' : '' ?>>Almería</option>
                                <option value="Asturias" <?= isset($provincia) && $provincia == 'Asturias' ? 'selected' : '' ?>>Asturias</option>
                                <option value="Ávila" <?= isset($provincia) && $provincia == 'Ávila' ? 'selected' : '' ?>>Ávila</option>
                                <option value="Badajoz" <?= isset($provincia) && $provincia == 'Badajoz' ? 'selected' : '' ?>>Badajoz</option>
                                <option value="Barcelona" <?= isset($provincia) && $provincia == 'Barcelona' ? 'selected' : '' ?>>Barcelona</option>
                                <option value="Burgos" <?= isset($provincia) && $provincia == 'Burgos' ? 'selected' : '' ?>>Burgos</option>
                                <option value="Cáceres" <?= isset($provincia) && $provincia == 'Cáceres' ? 'selected' : '' ?>>Cáceres</option>
                                <option value="Cádiz" <?= isset($provincia) && $provincia == 'Cádiz' ? 'selected' : '' ?>>Cádiz</option>
                                <option value="Cantabria" <?= isset($provincia) && $provincia == 'Cantabria' ? 'selected' : '' ?>>Cantabria</option>
                                <option value="Castellón" <?= isset($provincia) && $provincia == 'Castellón' ? 'selected' : '' ?>>Castellón</option>
                                <option value="Ciudad Real" <?= isset($provincia) && $provincia == 'Ciudad Real' ? 'selected' : '' ?>>Ciudad Real</option>
                                <option value="Córdoba" <?= isset($provincia) && $provincia == 'Córdoba' ? 'selected' : '' ?>>Córdoba</option>
                                <option value="Cuenca" <?= isset($provincia) && $provincia == 'Cuenca' ? 'selected' : '' ?>>Cuenca</option>
                                <option value="Girona" <?= isset($provincia) && $provincia == 'Girona' ? 'selected' : '' ?>>Girona</option>
                                <option value="Granada" <?= isset($provincia) && $provincia == 'Granada' ? 'selected' : '' ?>>Granada</option>
                                <option value="Guadalajara" <?= isset($provincia) && $provincia == 'Guadalajara' ? 'selected' : '' ?>>Guadalajara</option>
                                <option value="Guipúzcoa" <?= isset($provincia) && $provincia == 'Guipúzcoa' ? 'selected' : '' ?>>Guipúzcoa</option>
                                <option value="Huelva" <?= isset($provincia) && $provincia == 'Huelva' ? 'selected' : '' ?>>Huelva</option>
                                <option value="Huesca" <?= isset($provincia) && $provincia == 'Huesca' ? 'selected' : '' ?>>Huesca</option>
                                <option value="Islas Baleares" <?= isset($provincia) && $provincia == 'Islas Baleares' ? 'selected' : '' ?>>Islas Baleares</option>
                                <option value="Jaén" <?= isset($provincia) && $provincia == 'Jaén' ? 'selected' : '' ?>>Jaén</option>
                                <option value="La Coruña" <?= isset($provincia) && $provincia == 'La Coruña' ? 'selected' : '' ?>>La Coruña</option>
                                <option value="La Rioja" <?= isset($provincia) && $provincia == 'La Rioja' ? 'selected' : '' ?>>La Rioja</option>
                                <option value="Las Palmas" <?= isset($provincia) && $provincia == 'Las Palmas' ? 'selected' : '' ?>>Las Palmas</option>
                                <option value="León" <?= isset($provincia) && $provincia == 'León' ? 'selected' : '' ?>>León</option>
                                <option value="Lleida" <?= isset($provincia) && $provincia == 'Lleida' ? 'selected' : '' ?>>Lleida</option>
                                <option value="Lugo" <?= isset($provincia) && $provincia == 'Lugo' ? 'selected' : '' ?>>Lugo</option>
                                <option value="Madrid" <?= isset($provincia) && $provincia == 'Madrid' ? 'selected' : '' ?>>Madrid</option>
                                <option value="Málaga" <?= isset($provincia) && $provincia == 'Málaga' ? 'selected' : '' ?>>Málaga</option>
                                <option value="Murcia" <?= isset($provincia) && $provincia == 'Murcia' ? 'selected' : '' ?>>Murcia</option>
                                <option value="Navarra" <?= isset($provincia) && $provincia == 'Navarra' ? 'selected' : '' ?>>Navarra</option>
                                <option value="Ourense" <?= isset($provincia) && $provincia == 'Ourense' ? 'selected' : '' ?>>Ourense</option>
                                <option value="Palencia" <?= isset($provincia) && $provincia == 'Palencia' ? 'selected' : '' ?>>Palencia</option>
                                <option value="Pontevedra" <?= isset($provincia) && $provincia == 'Pontevedra' ? 'selected' : '' ?>>Pontevedra</option>
                                <option value="Salamanca" <?= isset($provincia) && $provincia == 'Salamanca' ? 'selected' : '' ?>>Salamanca</option>
                                <option value="Santa Cruz de Tenerife" <?= isset($provincia) && $provincia == 'Santa Cruz de Tenerife' ? 'selected' : '' ?>>Santa Cruz de Tenerife</option>
                                <option value="Segovia" <?= isset($provincia) && $provincia == 'Segovia' ? 'selected' : '' ?>>Segovia</option>
                                <option value="Sevilla" <?= isset($provincia) && $provincia == 'Sevilla' ? 'selected' : '' ?>>Sevilla</option>
                                <option value="Soria" <?= isset($provincia) && $provincia == 'Soria' ? 'selected' : '' ?>>Soria</option>
                                <option value="Tarragona" <?= isset($provincia) && $provincia == 'Tarragona' ? 'selected' : '' ?>>Tarragona</option>
                                <option value="Teruel" <?= isset($provincia) && $provincia == 'Teruel' ? 'selected' : '' ?>>Teruel</option>
                                <option value="Toledo" <?= isset($provincia) && $provincia == 'Toledo' ? 'selected' : '' ?>>Toledo</option>
                                <option value="Valencia" <?= isset($provincia) && $provincia == 'Valencia' ? 'selected' : '' ?>>Valencia</option>
                                <option value="Valladolid" <?= isset($provincia) && $provincia == 'Valladolid' ? 'selected' : '' ?>>Valladolid</option>
                                <option value="Vizcaya" <?= isset($provincia) && $provincia == 'Vizcaya' ? 'selected' : '' ?>>Vizcaya</option>
                                <option value="Zamora" <?= isset($provincia) && $provincia == 'Zamora' ? 'selected' : '' ?>>Zamora</option>
                                <option value="Zaragoza" <?= isset($provincia) && $provincia == 'Zaragoza' ? 'selected' : '' ?>>Zaragoza</option>
                                <option value="Ceuta" <?= isset($provincia) && $provincia == 'Ceuta' ? 'selected' : '' ?>>Ceuta</option>
                                <option value="Melilla" <?= isset($provincia) && $provincia == 'Melilla' ? 'selected' : '' ?>>Melilla</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h5 class="card-title mb-3 mt-4"><span class="material-symbols-rounded me-2">medical_information</span>Información Médica</h5>
                <hr>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"><?= isset($notas) ? htmlspecialchars($notas) : '' ?></textarea>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="reset" class="btn btn-secondary me-2">
                        <span class="material-symbols-rounded me-2">refresh</span>Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-rounded me-2">save</span>Guardar Paciente
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
        const form = document.getElementById('form-crear-paciente');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
</script>
<?php
// No es necesario un cierre de PHP al final del archivo 