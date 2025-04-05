<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Iniciar buffer de salida para evitar el error "headers already sent"
ob_start();

// Requerir autenticación para acceder a esta página
requiereLogin();

// Preparar el título y breadcrumbs
$isEditing = false;
$pageTitle = 'Nueva Cita';

// Definir la variable $extra_js al inicio
$extra_js = '';

$breadcrumbs = [
    'Citas' => BASE_URL . '/modules/citas/list.php',
    $pageTitle => '#'
];

// Inicializar cita como array vacío para mantener consistencia con edit.php
$cita = [];

// Inicializar variables desde GET (para cuando se llega desde otro lugar)
$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;
$fecha = isset($_GET['fecha']) ? sanitize($_GET['fecha']) : date('Y-m-d');
$hora_inicio = isset($_GET['hora']) ? sanitize($_GET['hora']) : '08:00';
$solicitud_id = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;

// Obtener bonos disponibles si hay un paciente seleccionado
$bonos_disponibles = [];
if ($paciente_id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM bonos
            WHERE paciente_id = :paciente_id
            AND estado = 'activo'
            AND num_sesiones_disponibles > 0
            ORDER BY fecha_compra DESC
        ");
        $stmt->bindParam(':paciente_id', $paciente_id, PDO::PARAM_INT);
        $stmt->execute();
        $bonos_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si hay error, simplemente continuar con array vacío
    }
}

// Si viene de una solicitud de cita del portal, cargar datos adicionales
$solicitud = null;
if ($solicitud_id > 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM solicitudes_citas 
            WHERE id = ? AND estado = 'pendiente'
        ");
        $stmt->execute([$solicitud_id]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($solicitud) {
            // Asignar valores de la solicitud
            $paciente_id = $solicitud['paciente_id'];
            $fecha = $solicitud['fecha_solicitada'];
            $motivo = $solicitud['motivo'];
            
            // Seleccionar hora según franja horaria
            if ($solicitud['franja_horaria'] === 'mañana') {
                $hora_inicio = '10:00';
            } elseif ($solicitud['franja_horaria'] === 'tarde') {
                $hora_inicio = '17:00';
            }
            
            // Calcular hora fin (1 hora por defecto)
            $hora_fin = date('H:i', strtotime($hora_inicio . ' + 1 hour'));
            
            // Precargar comentarios
            $notas = $solicitud['comentarios'] ? "Solicitud del paciente: " . $solicitud['comentarios'] : "";
        }
    } catch (PDOException $e) {
        error_log("Error cargando solicitud: " . $e->getMessage());
    }
}

// Obtener la lista de servicios
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM servicios ORDER BY nombre ASC");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setAlert('danger', 'Error al cargar servicios: ' . $e->getMessage());
    $servicios = [];
}

// Buscar servicio Individual para preseleccionarlo
$servicio_preseleccionado = null;
$precio_preseleccionado = null;
foreach ($servicios as $s) {
    if (stripos($s['nombre'], 'Individual') !== false && !$isEditing) {
        $servicio_preseleccionado = $s['id'];
        $precio_preseleccionado = $s['precio'];
        // Asignar al array de cita para mantener consistencia
        $cita['servicio_id'] = $servicio_preseleccionado;
        $cita['precio'] = $precio_preseleccionado;
        break;
    }
}

// Obtener horarios disponibles
$horarios = getHorariosDisponibles($fecha ?? date('Y-m-d'));

// Título y breadcrumbs para la página
$titulo_pagina = $pageTitle;
$title = $titulo_pagina;
$breadcrumbs = [
    'Citas' => BASE_URL . '/modules/citas/list.php',
    $titulo_pagina => '#'
];

// Iniciar el buffer de salida para capturar el contenido
startPageContent();
?>

<!-- JavaScript para esta página -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const horaInicioInput = document.getElementById("hora_inicio");
    const horaFinInput = document.getElementById("hora_fin");
    const servicioSelect = document.getElementById("servicio_id");
    const precioInput = document.getElementById("precio");
    
    // Función para actualizar precio y duración
    function actualizarPrecioYDuracion() {
        if (!servicioSelect || !horaInicioInput || !horaFinInput) {
            return;
        }
        
        const servicioId = servicioSelect.value;
        if (!servicioId) {
            return;
        }
        
        // Obtener duración del servicio desde el data-attribute
        const duracionMinutos = parseInt(servicioSelect.options[servicioSelect.selectedIndex].getAttribute("data-duracion") || "0");
        
        // Obtener precio del servicio
        const precioServicio = parseFloat(servicioSelect.options[servicioSelect.selectedIndex].getAttribute("data-precio") || "0");
        
        // Actualizar campo de precio
        if (precioInput && !isNaN(precioServicio) && precioServicio > 0) {
            precioInput.value = precioServicio.toFixed(2);
        }
        
        // Si no hay hora de inicio seleccionada, salir
        if (!horaInicioInput.value) {
            return;
        }
        
        // Calcular hora de fin (inicio + duración)
        const horaPartes = horaInicioInput.value.split(":");
        const horaInicio = parseInt(horaPartes[0] || "0");
        const minutosInicio = parseInt(horaPartes[1] || "0");
        
        // Convertir a minutos totales
        const totalMinutosInicio = horaInicio * 60 + minutosInicio;
        
        // Sumar la duración del servicio
        const totalMinutosFin = totalMinutosInicio + duracionMinutos;
        
        // Calcular horas y minutos para la hora fin
        const horaFinHoras = Math.floor(totalMinutosFin / 60);
        const horaFinMinutos = totalMinutosFin % 60;
        
        // Formatear hora de fin HH:MM
        const horaFinCalculada = horaFinHoras.toString().padStart(2, "0") + ":" + horaFinMinutos.toString().padStart(2, "0");
        
        // Encontrar la opción más cercana disponible
        let mejorOpcion = null;
        let menorDiferencia = Infinity;
        
        Array.from(horaFinInput.options).forEach((option, index) => {
            if (!option.value) {
                return;
            }
            
            // Convertir la opción a minutos totales para comparar
            const optionPartes = option.value.split(":");
            const optionHoras = parseInt(optionPartes[0] || "0");
            const optionMinutos = parseInt(optionPartes[1] || "0");
            const totalMinutosOpt = optionHoras * 60 + optionMinutos;
            
            // Convertir la hora calculada a minutos totales
            const finCalculadoPartes = horaFinCalculada.split(":");
            const finCalculadoHoras = parseInt(finCalculadoPartes[0] || "0");
            const finCalculadoMinutos = parseInt(finCalculadoPartes[1] || "0");
            const totalMinutosCalc = finCalculadoHoras * 60 + finCalculadoMinutos;
            
            // La opción debe ser igual o posterior a la hora calculada
            if (totalMinutosOpt >= totalMinutosCalc) {
                const diferencia = totalMinutosOpt - totalMinutosCalc;
                
                if (diferencia === 0) {
                    mejorOpcion = option;
                    menorDiferencia = 0;
                    return; // Salir del loop si encontramos coincidencia exacta
                }
                
                if (diferencia < menorDiferencia) {
                    mejorOpcion = option;
                    menorDiferencia = diferencia;
                }
            }
        });
        
        if (mejorOpcion) {
            horaFinInput.value = mejorOpcion.value;
        } else {
            // Intentar encontrar cualquier opción posterior a la hora de inicio
            let opcionDisponible = null;
            
            Array.from(horaFinInput.options).forEach((option, index) => {
                if (!option.value) return;
                
                const optionPartes = option.value.split(":");
                const optionHoras = parseInt(optionPartes[0] || "0");
                const optionMinutos = parseInt(optionPartes[1] || "0");
                const totalMinutosOpt = optionHoras * 60 + optionMinutos;
                
                if (totalMinutosOpt > totalMinutosInicio && !opcionDisponible) {
                    opcionDisponible = option;
                }
            });
            
            if (opcionDisponible) {
                horaFinInput.value = opcionDisponible.value;
            } else {
                horaFinInput.value = "";
            }
        }
    }
    
    // Inicializar datepickers
    flatpickr(".date-picker", {
        locale: "es",
        dateFormat: "d/m/Y",
        allowInput: true
    });
    
    // Manejar cambio en el checkbox de pagada
    const pagadaCheck = document.getElementById("pagada");
    const fechaPagoInput = document.getElementById("fecha_pago");
    const formaPagoSelect = document.getElementById("forma_pago");
    
    if (pagadaCheck && fechaPagoInput && formaPagoSelect) {
        pagadaCheck.addEventListener("change", function() {
            if (this.checked) {
                const today = new Date();
                const formattedDate = today.getDate().toString().padStart(2, "0") + "/" +
                                     (today.getMonth() + 1).toString().padStart(2, "0") + "/" +
                                     today.getFullYear();
                
                if (!fechaPagoInput._flatpickr) {
                    fechaPagoInput.value = formattedDate;
                } else {
                    fechaPagoInput._flatpickr.setDate(formattedDate);
                }
                
                if (!formaPagoSelect.value) {
                    formaPagoSelect.value = "efectivo";
                }
            }
        });
    }
    
    // Event listener para hora_inicio
    if (horaInicioInput) {
        horaInicioInput.addEventListener("change", function() {
            if (!horaFinInput) {
                return;
            }
            
            const horaInicioSeleccionada = this.value;
            if (horaInicioSeleccionada) {
                const horaPartes = horaInicioSeleccionada.split(":");
                const hora = parseInt(horaPartes[0] || "0");
                const minutos = parseInt(horaPartes[1] || "0");
                const horaInicioNum = hora * 60 + minutos;
                
                // Habilitar solo las horas posteriores a la hora de inicio
                Array.from(horaFinInput.options).forEach((option, index) => {
                    if (!option.value) {
                        return;
                    }
                    
                    const optionPartes = option.value.split(":");
                    const horaOptFin = parseInt(optionPartes[0] || "0");
                    const minutosOptFin = parseInt(optionPartes[1] || "0");
                    const horaFinNum = horaOptFin * 60 + minutosOptFin;
                    
                    if (horaFinNum > horaInicioNum) {
                        option.disabled = false;
                    } else {
                        option.disabled = true;
                        if (option.value === horaFinInput.value) {
                            horaFinInput.value = "";
                        }
                    }
                });
                
                // Actualizar la hora de fin basada en el servicio seleccionado
                setTimeout(() => actualizarPrecioYDuracion(), 100);
            }
        });
    }
    
    // Event listener para servicio_id
    if (servicioSelect) {
        servicioSelect.addEventListener("change", actualizarPrecioYDuracion);
    }
    
    // Inicialización inicial retrasada para asegurar que todos los elementos estén cargados
    setTimeout(function() {
        if (servicioSelect && servicioSelect.value && horaInicioInput && horaInicioInput.value) {
            // Forzar el disparo del evento change para actualizar la hora fin
            try {
                const event = new Event("change");
                horaInicioInput.dispatchEvent(event);
            } catch (e) {
                // Plan B: llamar directamente a la función
                actualizarPrecioYDuracion();
            }
        }
    }, 1000);

    // Gestión del selector de bonos
    const esBonoCheckbox = document.getElementById('es_bono');
    const bonoSelector = document.getElementById('bono_selector');
    const pagadaCheckbox = document.getElementById('pagada');
    const fechaPagoInput = document.getElementById('fecha_pago');
    const formaPagoSelect = document.getElementById('forma_pago');
    
    if (esBonoCheckbox) {
        esBonoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                bonoSelector.style.display = 'block';
                // Si se usa bono, marcar como pagada automáticamente
                if (pagadaCheckbox) pagadaCheckbox.checked = true;
                if (fechaPagoInput && !fechaPagoInput.value) fechaPagoInput.value = '<?= date('d/m/Y') ?>';
                if (formaPagoSelect) formaPagoSelect.value = 'bono';
            } else {
                bonoSelector.style.display = 'none';
            }
        });
        
        // Actualizar al cargar la página
        if (esBonoCheckbox.checked) {
            bonoSelector.style.display = 'block';
        }
    }
});
</script>

<div class="container-fluid py-4 px-4">
    <!-- Notificaciones -->
    <?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Error al crear cita</h5>
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
            <span class="material-symbols-rounded me-2">calendar_add_on</span><?= $pageTitle ?>
        </h1>
        <a href="list.php" class="btn btn-secondary">
            <span class="material-symbols-rounded">arrow_back</span> Volver al Listado
        </a>
    </div>
    
    <!-- Breadcrumbs -->
    <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
    
    <!-- Formulario -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">edit</span>Datos de la Cita
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST" class="row g-3 needs-validation" novalidate>
                        <!-- Selección de paciente -->
                        <div class="col-md-12 mb-3">
                            <label for="paciente_id" class="form-label required-field">Paciente *</label>
                            <select class="form-select paciente-select" id="paciente_id" name="paciente_id" required>
                                <option value="" disabled <?= empty($cita['paciente_id']) && empty($paciente_id) ? 'selected' : '' ?>>Seleccionar paciente</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                <option value="<?= $paciente['id'] ?>" <?= (isset($cita['paciente_id']) && $cita['paciente_id'] == $paciente['id']) || ($paciente_id == $paciente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($paciente['apellidos'] . ', ' . $paciente['nombre'] . ' (' . $paciente['dni'] . ')') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Por favor, selecciona un paciente</div>
                            <div class="form-text">
                                <a href="../pacientes/create.php" target="_blank" class="text-decoration-none">
                                    <span class="material-symbols-rounded icon-sm me-1">add_circle</span>Crear nuevo paciente
                                </a>
                            </div>
                        </div>
                        
                        <!-- Tipo de sesión y precio -->
                        <div class="col-md-8 mb-3">
                            <label for="servicio_id" class="form-label required-field">Tipo de Sesión *</label>
                            <select class="form-select" id="servicio_id" name="servicio_id" required autocomplete="off">
                                <option value="" disabled <?= empty($cita['servicio_id']) && empty($servicio_preseleccionado) ? 'selected' : '' ?>>Seleccionar tipo de sesión</option>
                                <?php foreach ($servicios as $servicio): ?>
                                <option 
                                    value="<?= $servicio['id'] ?>" 
                                    data-precio="<?= $servicio['precio'] ?>" 
                                    data-duracion="<?= $servicio['duracion_minutos'] ?>"
                                    <?= (isset($cita['servicio_id']) && $cita['servicio_id'] == $servicio['id']) || (!$isEditing && $servicio_preseleccionado == $servicio['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($servicio['nombre']) ?> (<?= $servicio['duracion_minutos'] ?> min - <?= number_format($servicio['precio'], 2) ?> €)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">El tipo de sesión es obligatorio</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="precio" class="form-label required-field">Precio (€) *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="precio" name="precio" value="<?= isset($cita['precio']) ? number_format($cita['precio'], 2, ',', '.') : (isset($precio_preseleccionado) && !$isEditing ? number_format($precio_preseleccionado, 2, ',', '.') : '') ?>" required>
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="invalid-feedback">El precio es obligatorio</div>
                            <div class="form-text">Se actualiza automáticamente al seleccionar el tipo de sesión</div>
                        </div>
                        
                        <!-- Fecha y horas -->
                        <div class="col-md-4 mb-3">
                            <label for="fecha" class="form-label required-field">Fecha *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">calendar_today</span>
                                </span>
                                <input type="text" class="form-control date-picker" id="fecha" name="fecha" value="<?= isset($cita['fecha']) ? formatDateToView($cita['fecha']) : (isset($fecha) ? formatDateToView($fecha) : '') ?>" required>
                            </div>
                            <div class="invalid-feedback">La fecha es obligatoria</div>
                            <div class="form-text">Selecciona una fecha para la cita</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="hora_inicio" class="form-label required-field">Hora inicio *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">schedule</span>
                                </span>
                                <select class="form-select" id="hora_inicio" name="hora_inicio" required>
                                    <option value="" disabled>Seleccionar</option>
                                    <?php
                                    // Generar opciones de hora cada 15 minutos desde 8:00 hasta 20:00
                                    $hora_actual = '08:00';
                                    $hora_final = '20:00';
                                    
                                    while ($hora_actual <= $hora_final) {
                                        $selected = (isset($cita['hora_inicio']) && $hora_actual == $cita['hora_inicio']) || ($hora_actual == $hora_inicio) ? 'selected' : '';
                                        echo "<option value=\"$hora_actual\" $selected>$hora_actual</option>";
                                        
                                        // Incrementar 15 minutos
                                        $time = strtotime($hora_actual) + 15 * 60;
                                        $hora_actual = date('H:i', $time);
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="invalid-feedback">La hora de inicio es obligatoria</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="hora_fin" class="form-label required-field">Hora fin *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">schedule</span>
                                </span>
                                <select class="form-select" id="hora_fin" name="hora_fin" required>
                                    <option value="" disabled <?= empty($cita['hora_fin']) ? 'selected' : '' ?>>Seleccionar</option>
                                    <?php
                                    // Generar opciones de hora cada 5 minutos desde 8:05 hasta 21:00
                                    $hora_actual = '08:05';
                                    $hora_final = '21:00';
                                    $incremento_minutos = 5; // Incremento de 5 minutos
                                    
                                    while ($hora_actual <= $hora_final) {
                                        $selected = (isset($cita['hora_fin']) && $hora_actual == $cita['hora_fin']) ? 'selected' : '';
                                        echo "<option value=\"$hora_actual\" $selected>$hora_actual</option>";
                                        
                                        // Incrementar minutos
                                        $time = strtotime($hora_actual) + $incremento_minutos * 60;
                                        $hora_actual = date('H:i', $time);
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="invalid-feedback">La hora de fin es obligatoria</div>
                            <div class="form-text">Se calcula automáticamente según la duración del servicio</div>
                        </div>
                        
                        <!-- Motivo y estado -->
                        <div class="col-md-8 mb-3">
                            <label for="motivo" class="form-label required-field">Motivo de la consulta *</label>
                            <input type="text" class="form-control" id="motivo" name="motivo" value="<?= isset($cita['motivo']) ? htmlspecialchars($cita['motivo']) : '' ?>" required>
                            <div class="invalid-feedback">El motivo es obligatorio</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="estado" class="form-label required-field">Estado *</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="pendiente" <?= (!isset($cita['estado']) || $cita['estado'] == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                <option value="completada" <?= (isset($cita['estado']) && $cita['estado'] == 'completada') ? 'selected' : '' ?>>Completada</option>
                                <option value="cancelada" <?= (isset($cita['estado']) && $cita['estado'] == 'cancelada') ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </div>
                        
                        <!-- Pago información -->
                        <div class="col-md-4 mb-3">
                            <label for="pagada" class="form-label">Pagada</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="pagada" name="pagada" value="1" <?= isset($cita['pagada']) && $cita['pagada'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pagada">Marcar como pagada</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <span class="material-symbols-rounded icon-sm">calendar_today</span>
                                </span>
                                <input type="text" class="form-control date-picker" id="fecha_pago" name="fecha_pago" value="<?= isset($cita['fecha_pago']) ? formatDateToView($cita['fecha_pago']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="forma_pago" class="form-label">Forma de Pago</label>
                            <select class="form-select" id="forma_pago" name="forma_pago">
                                <option value="">Seleccionar</option>
                                <option value="efectivo" <?= (isset($cita['forma_pago']) && $cita['forma_pago'] == 'efectivo') ? 'selected' : '' ?>>Efectivo</option>
                                <option value="tarjeta" <?= (isset($cita['forma_pago']) && $cita['forma_pago'] == 'tarjeta') ? 'selected' : '' ?>>Tarjeta</option>
                                <option value="transferencia" <?= (isset($cita['forma_pago']) && $cita['forma_pago'] == 'transferencia') ? 'selected' : '' ?>>Transferencia</option>
                                <option value="bizum" <?= (isset($cita['forma_pago']) && $cita['forma_pago'] == 'bizum') ? 'selected' : '' ?>>Bizum</option>
                                <option value="otro" <?= (isset($cita['forma_pago']) && $cita['forma_pago'] == 'otro') ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        
                        <!-- Notas -->
                        <div class="col-md-12 mb-3">
                            <label for="notas" class="form-label">Notas adicionales</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3"><?= isset($cita['notas']) ? htmlspecialchars($cita['notas']) : '' ?></textarea>
                        </div>
                        
                        <!-- Seccion para mostrar los bonos disponibles -->
                        <?php if ($paciente_id > 0): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="es_bono" name="es_bono" value="1" <?= isset($cita['es_bono']) && $cita['es_bono'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="es_bono">Utilizar bono de sesiones</label>
                                    </div>
                                </div>
                                
                                <div id="bono_selector" class="mt-3" style="<?= isset($cita['es_bono']) && $cita['es_bono'] ? '' : 'display:none;' ?>">
                                    <div class="form-group">
                                        <label for="bono_id">Seleccionar Bono:</label>
                                        <select class="form-select" id="bono_id" name="bono_id">
                                            <option value="">Seleccione un bono</option>
                                            <?php if (!empty($bonos_disponibles)): ?>
                                                <?php foreach ($bonos_disponibles as $bono): ?>
                                                    <option value="<?= $bono['id'] ?>" <?= isset($cita['bono_id']) && $cita['bono_id'] == $bono['id'] ? 'selected' : '' ?>>
                                                        Bono #<?= $bono['id'] ?> - <?= $bono['num_sesiones_disponibles'] ?> sesiones disponibles - <?= date('d/m/Y', strtotime($bono['fecha_compra'])) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled>No hay bonos disponibles para este paciente</option>
                                            <?php endif; ?>
                                        </select>
                                        <div class="form-text">Si usa un bono, la cita se marcará como pagada automáticamente.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Botones de acción -->
                        <div class="col-12 mt-4 d-flex justify-content-end">
                            <button type="reset" class="btn btn-secondary me-2">
                                <span class="material-symbols-rounded">refresh</span> Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-rounded">save</span> Guardar Cita
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 d-flex align-items-center">
                        <span class="material-symbols-rounded me-2">info</span>Información
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <span class="material-symbols-rounded icon-md text-primary me-2">help</span>
                        Completa todos los datos para programar una nueva cita en el sistema. Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                    </p>
                    <hr>
                    <p class="card-text">
                        <span class="material-symbols-rounded icon-md text-warning me-2">warning</span>
                        Una vez creada la cita, se verificará que no existan solapamientos en el horario seleccionado.
                    </p>
                    <hr>
                    <p class="card-text">
                        <span class="material-symbols-rounded icon-md text-info me-2">calendar_month</span>
                        <strong>Recuerda:</strong> Puedes ver la agenda completa del día en la vista de calendario.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar captura y renderizar incluyendo el JavaScript adicional
endPageContent($titulo_pagina, ['extra_js' => $extra_js]);
?> 