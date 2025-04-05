# Guía de Implementación: Sistema de Bonos para Citas

## Modelo de Datos

### Tabla: `bonos`
```sql
CREATE TABLE bonos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  fecha_compra DATE NOT NULL,
  num_sesiones_total INT NOT NULL DEFAULT 4,
  num_sesiones_disponibles INT NOT NULL DEFAULT 4,
  monto DECIMAL(10,2) NOT NULL,
  fecha_caducidad DATE NULL,
  estado ENUM('activo', 'consumido', 'caducado', 'cancelado') DEFAULT 'activo',
  notas TEXT,
  creado_por INT,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);
```

### Actualización tabla: `citas`
```sql
ALTER TABLE citas
ADD COLUMN es_bono BOOLEAN DEFAULT FALSE,
ADD COLUMN bono_id INT NULL,
ADD FOREIGN KEY (bono_id) REFERENCES bonos(id);
```

### Tabla de relación: `citas_bonos` (opcional - para histórico detallado)
```sql
CREATE TABLE citas_bonos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cita_id INT NOT NULL,
  bono_id INT NOT NULL,
  fecha_aplicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cita_id) REFERENCES citas(id),
  FOREIGN KEY (bono_id) REFERENCES bonos(id)
);
```

## Procedimientos Almacenados

### Aplicar bono a cita
```sql
DELIMITER //
CREATE PROCEDURE aplicar_bono_a_cita(
  IN p_cita_id INT,
  IN p_bono_id INT,
  OUT p_resultado BOOLEAN,
  OUT p_mensaje VARCHAR(255)
)
BEGIN
  DECLARE v_sesiones_disponibles INT;
  DECLARE v_estado VARCHAR(20);
  
  -- Verificar si el bono existe y tiene sesiones disponibles
  SELECT num_sesiones_disponibles, estado INTO v_sesiones_disponibles, v_estado
  FROM bonos WHERE id = p_bono_id;
  
  IF v_estado != 'activo' THEN
    SET p_resultado = FALSE;
    SET p_mensaje = 'El bono no está activo';
  ELSEIF v_sesiones_disponibles <= 0 THEN
    SET p_resultado = FALSE;
    SET p_mensaje = 'El bono no tiene sesiones disponibles';
  ELSE
    -- Actualizar la cita
    UPDATE citas SET es_bono = TRUE, bono_id = p_bono_id
    WHERE id = p_cita_id;
    
    -- Actualizar el bono
    UPDATE bonos SET 
      num_sesiones_disponibles = num_sesiones_disponibles - 1,
      estado = IF(num_sesiones_disponibles - 1 <= 0, 'consumido', 'activo')
    WHERE id = p_bono_id;
    
    -- Registrar en historial (opcional)
    INSERT INTO citas_bonos (cita_id, bono_id)
    VALUES (p_cita_id, p_bono_id);
    
    SET p_resultado = TRUE;
    SET p_mensaje = 'Bono aplicado correctamente';
  END IF;
END //
DELIMITER ;
```

### Liberar bono de cita (para cancelaciones)
```sql
DELIMITER //
CREATE PROCEDURE liberar_bono_de_cita(
  IN p_cita_id INT,
  OUT p_resultado BOOLEAN,
  OUT p_mensaje VARCHAR(255)
)
BEGIN
  DECLARE v_bono_id INT;
  
  -- Obtener el bono asociado a la cita
  SELECT bono_id INTO v_bono_id FROM citas WHERE id = p_cita_id AND es_bono = TRUE;
  
  IF v_bono_id IS NULL THEN
    SET p_resultado = FALSE;
    SET p_mensaje = 'Esta cita no tiene un bono asociado';
  ELSE
    -- Actualizar la cita
    UPDATE citas SET es_bono = FALSE, bono_id = NULL
    WHERE id = p_cita_id;
    
    -- Devolver la sesión al bono
    UPDATE bonos SET 
      num_sesiones_disponibles = num_sesiones_disponibles + 1,
      estado = 'activo'
    WHERE id = v_bono_id;
    
    -- Eliminar del historial (opcional)
    DELETE FROM citas_bonos WHERE cita_id = p_cita_id AND bono_id = v_bono_id;
    
    SET p_resultado = TRUE;
    SET p_mensaje = 'Sesión devuelta al bono correctamente';
  END IF;
END //
DELIMITER ;
```

## Módulos Front-end

### 1. Panel de Administración de Bonos

```html
<div class="card">
  <div class="card-header">
    <h5>Gestión de Bonos</h5>
  </div>
  <div class="card-body">
    <form id="nuevo-bono-form">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="paciente">Paciente</label>
            <select class="form-control" id="paciente" required>
              <!-- Opciones de pacientes -->
            </select>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="sesiones">Sesiones</label>
            <input type="number" class="form-control" id="sesiones" value="4" readonly>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label for="monto">Monto (€)</label>
            <input type="number" class="form-control" id="monto" value="180.00" step="0.01">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="fecha-caducidad">Fecha de caducidad (opcional)</label>
            <input type="date" class="form-control" id="fecha-caducidad">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="notas">Notas</label>
            <textarea class="form-control" id="notas"></textarea>
          </div>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Crear Bono</button>
    </form>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header">
    <h5>Bonos Activos</h5>
  </div>
  <div class="card-body">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Paciente</th>
          <th>Fecha Compra</th>
          <th>Sesiones Disponibles</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="bonos-activos">
        <!-- Listado de bonos -->
      </tbody>
    </table>
  </div>
</div>
```

### 2. Modificación del Formulario de Citas

```html
<!-- Añadir a formulario de citas existente -->
<div class="form-group" id="seccion-bono">
  <div class="custom-control custom-switch">
    <input type="checkbox" class="custom-control-input" id="usar-bono">
    <label class="custom-control-label" for="usar-bono">Usar bono de sesiones</label>
  </div>
  
  <div id="seleccion-bono" style="display: none; margin-top: 10px;">
    <select class="form-control" id="bono-disponible">
      <option value="">Seleccionar bono disponible</option>
      <!-- Bonos disponibles del paciente -->
    </select>
    <small class="text-muted">
      <span id="sesiones-disponibles"></span>
    </small>
  </div>
</div>
```

### 3. Indicador Visual en Calendario

```css
/* Añadir a style.css */
.cita-con-bono {
  background-color: #a7f3d0 !important;
  border-left: 4px solid #059669 !important;
}

.cita-con-bono:before {
  content: "🎟️";
  margin-right: 5px;
}
```

## JavaScript - Lógica del Cliente

### 1. Gestión de Bonos

```javascript
// Añadir a common.js
function cargarBonosPaciente(pacienteId) {
  // Llamada AJAX para obtener bonos activos del paciente
  $.ajax({
    url: 'api/bonos/paciente/' + pacienteId,
    type: 'GET',
    success: function(response) {
      const bonosSelect = $('#bono-disponible');
      bonosSelect.empty();
      bonosSelect.append('<option value="">Seleccionar bono disponible</option>');
      
      response.forEach(bono => {
        bonosSelect.append(
          `<option value="${bono.id}">Bono #${bono.id} - ${bono.num_sesiones_disponibles} sesiones disponibles</option>`
        );
      });
      
      if (response.length === 0) {
        $('#sesiones-disponibles').text('El paciente no tiene bonos activos');
      }
    }
  });
}

// Toggle para mostrar/ocultar selección de bono
$('#usar-bono').change(function() {
  if ($(this).is(':checked')) {
    $('#seleccion-bono').show();
    const pacienteId = $('#paciente').val();
    if (pacienteId) {
      cargarBonosPaciente(pacienteId);
    }
  } else {
    $('#seleccion-bono').hide();
  }
});

// Actualizar info al seleccionar bono
$('#bono-disponible').change(function() {
  const bonoId = $(this).val();
  if (bonoId) {
    $.ajax({
      url: 'api/bonos/' + bonoId,
      type: 'GET',
      success: function(bono) {
        $('#sesiones-disponibles').text(
          `Este bono tiene ${bono.num_sesiones_disponibles} sesiones disponibles de ${bono.num_sesiones_total}`
        );
      }
    });
  } else {
    $('#sesiones-disponibles').text('');
  }
});

// Crear nuevo bono
$('#nuevo-bono-form').submit(function(e) {
  e.preventDefault();
  
  const data = {
    paciente_id: $('#paciente').val(),
    num_sesiones_total: $('#sesiones').val(),
    monto: $('#monto').val(),
    fecha_caducidad: $('#fecha-caducidad').val() || null,
    notas: $('#notas').val()
  };
  
  $.ajax({
    url: 'api/bonos',
    type: 'POST',
    data: JSON.stringify(data),
    contentType: 'application/json',
    success: function(response) {
      alert('Bono creado con éxito');
      // Recargar lista de bonos
      cargarBonosActivos();
      $('#nuevo-bono-form')[0].reset();
    },
    error: function(xhr, status, error) {
      alert('Error al crear bono: ' + xhr.responseText);
    }
  });
});
```

### 2. Aplicación de Bono en Citas

```javascript
// Añadir al código de guardado de citas
function guardarCita() {
  // Código existente para recopilar datos de la cita
  
  // Añadir información de bono si está seleccionado
  if ($('#usar-bono').is(':checked') && $('#bono-disponible').val()) {
    data.es_bono = true;
    data.bono_id = $('#bono-disponible').val();
  }
  
  // Código existente para enviar la cita al servidor
}

// Resaltar citas con bono en el calendario
function renderizarCalendario(eventos) {
  // Código existente para renderizar calendario
  
  // Añadir clase especial a citas con bono
  eventos.forEach(evento => {
    if (evento.es_bono) {
      evento.className = 'cita-con-bono';
      evento.title = '🎟️ ' + evento.title;
    }
  });
  
  // Continuar con código existente
}
```

## API Endpoints (Backend)

### 1. Gestión de Bonos

```php
// api/bonos.php

// Crear nuevo bono
function crearBono() {
  // Validar entrada
  $data = json_decode(file_get_contents('php://input'), true);
  
  // Validaciones
  if (!isset($data['paciente_id']) || !isset($data['monto'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    return;
  }
  
  // Insertar en base de datos
  $query = "INSERT INTO bonos (paciente_id, num_sesiones_total, num_sesiones_disponibles, 
                              monto, fecha_caducidad, notas, creado_por) 
           VALUES (?, ?, ?, ?, ?, ?, ?)";
  
  $stmt = $conn->prepare($query);
  $stmt->bind_param(
    'iiidssi', 
    $data['paciente_id'], 
    $data['num_sesiones_total'], 
    $data['num_sesiones_total'], // Inicialmente disponibles = total
    $data['monto'],
    $data['fecha_caducidad'],
    $data['notas'],
    $_SESSION['usuario_id']
  );
  
  if ($stmt->execute()) {
    $id = $conn->insert_id;
    http_response_code(201);
    echo json_encode(['id' => $id, 'mensaje' => 'Bono creado con éxito']);
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear bono']);
  }
}

// Obtener bonos de un paciente
function getBonosPaciente($pacienteId) {
  $query = "SELECT * FROM bonos WHERE paciente_id = ? AND estado = 'activo' 
            AND num_sesiones_disponibles > 0
            ORDER BY fecha_compra DESC";
  
  $stmt = $conn->prepare($query);
  $stmt->bind_param('i', $pacienteId);
  $stmt->execute();
  
  $result = $stmt->get_result();
  $bonos = [];
  
  while ($row = $result->fetch_assoc()) {
    $bonos[] = $row;
  }
  
  echo json_encode($bonos);
}
```

### 2. Gestión de Citas con Bonos

```php
// Modificar la función de guardar cita existente para incluir bonos
function guardarCita() {
  // Código existente para validar y preparar datos
  
  // Si la cita usa bono, actualizar tablas relacionadas
  if (isset($data['es_bono']) && $data['es_bono'] && isset($data['bono_id'])) {
    // Llamar al procedimiento almacenado
    $stmt = $conn->prepare("CALL aplicar_bono_a_cita(?, ?, @resultado, @mensaje)");
    $stmt->bind_param('ii', $citaId, $data['bono_id']);
    $stmt->execute();
    
    // Obtener resultado
    $result = $conn->query("SELECT @resultado as resultado, @mensaje as mensaje");
    $row = $result->fetch_assoc();
    
    if (!$row['resultado']) {
      // Manejar error
      http_response_code(400);
      echo json_encode(['error' => $row['mensaje']]);
      return;
    }
  }
  
  // Continuar con código existente para respuesta
}

// Modificar la función de cancelar cita para devolver la sesión al bono
function cancelarCita($citaId) {
  // Verificar si la cita está asociada a un bono
  $query = "SELECT es_bono, bono_id FROM citas WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('i', $citaId);
  $stmt->execute();
  $result = $stmt->get_result();
  $cita = $result->fetch_assoc();
  
  // Si usa bono, liberar la sesión
  if ($cita['es_bono'] && $cita['bono_id']) {
    $stmt = $conn->prepare("CALL liberar_bono_de_cita(?, @resultado, @mensaje)");
    $stmt->bind_param('i', $citaId);
    $stmt->execute();
    
    // Verificar resultado si es necesario
  }
  
  // Continuar con la cancelación normal
  // ...
}
```

## Consideraciones Adicionales

1. **Seguridad**: Implementar validaciones para evitar que se apliquen bonos múltiples veces o que se modifiquen citas ya realizadas.

2. **Transacciones**: Usar transacciones SQL para asegurar la integridad cuando se aplican o liberan bonos de citas.

3. **Auditoría**: Mantener un log de todas las operaciones con bonos para resolver posibles disputas.

4. **Reportes**: Desarrollar informes específicos para análisis de uso de bonos y rentabilidad.

5. **Escalabilidad**: Preparar el sistema para futuros tipos de bonos (diferentes cantidades de sesiones, especialidades específicas, etc.). 