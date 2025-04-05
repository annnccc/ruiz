<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/header.php';

// Título de la página
$titulo_pagina = "Gestión de Bonos";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $titulo_pagina; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $titulo_pagina; ?></li>
    </ol>

    <!-- Tarjeta para crear nuevo bono -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-ticket-alt me-1"></i>
            Crear Nuevo Bono
        </div>
        <div class="card-body">
            <form id="form-nuevo-bono">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="paciente_id" class="form-label">Paciente</label>
                        <select class="form-select" id="paciente_id" name="paciente_id" required>
                            <option value="">Seleccione un paciente</option>
                            <?php
                            // Consulta para obtener todos los pacientes
                            $query = "SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo FROM pacientes ORDER BY nombre";
                            $stmt = $conn->prepare($query);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nombre_completo']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="num_sesiones" class="form-label">Número de sesiones</label>
                        <input type="number" class="form-control" id="num_sesiones" name="num_sesiones" value="4" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="monto" class="form-label">Monto (€)</label>
                        <input type="number" class="form-control" id="monto" name="monto" value="180.00" step="0.01" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fecha_caducidad" class="form-label">Fecha de caducidad (opcional)</label>
                        <input type="date" class="form-control" id="fecha_caducidad" name="fecha_caducidad">
                    </div>
                    <div class="col-md-6">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="2"></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Bono
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjeta para listar bonos activos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list me-1"></i>
            Bonos Activos
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tabla-bonos" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Paciente</th>
                            <th>Fecha Compra</th>
                            <th>Sesiones</th>
                            <th>Disponibles</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consulta para obtener bonos con información del paciente
                        $query = "SELECT b.*, CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre 
                                  FROM bonos b 
                                  JOIN pacientes p ON b.paciente_id = p.id 
                                  ORDER BY b.fecha_compra DESC";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {
                            // Determinar clase de estado para el badge
                            $estado_clase = 'bg-secondary';
                            switch ($row['estado']) {
                                case 'activo':
                                    $estado_clase = 'bg-success';
                                    break;
                                case 'consumido':
                                    $estado_clase = 'bg-primary';
                                    break;
                                case 'caducado':
                                    $estado_clase = 'bg-warning';
                                    break;
                                case 'cancelado':
                                    $estado_clase = 'bg-danger';
                                    break;
                            }
                            
                            echo '<tr>';
                            echo '<td>' . $row['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($row['paciente_nombre']) . '</td>';
                            echo '<td>' . $row['fecha_compra'] . '</td>';
                            echo '<td>' . $row['num_sesiones_total'] . '</td>';
                            echo '<td>' . $row['num_sesiones_disponibles'] . '</td>';
                            echo '<td>' . number_format($row['monto'], 2) . ' €</td>';
                            echo '<td><span class="badge ' . $estado_clase . '">' . ucfirst($row['estado']) . '</span></td>';
                            echo '<td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-info ver-bono" data-id="' . $row['id'] . '">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary cambiar-estado" data-id="' . $row['id'] . '">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                </div>
                            </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del bono -->
<div class="modal fade" id="modal-detalle-bono" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Bono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detalle-bono-contenido">
                    <!-- Aquí se cargará el detalle del bono -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado del bono -->
<div class="modal fade" id="modal-cambiar-estado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado del Bono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-cambiar-estado">
                    <input type="hidden" id="cambiar_estado_bono_id" name="bono_id">
                    <div class="mb-3">
                        <label for="nuevo_estado" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                            <option value="activo">Activo</option>
                            <option value="consumido">Consumido</option>
                            <option value="caducado">Caducado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="motivo_cambio" class="form-label">Motivo del cambio</label>
                        <textarea class="form-control" id="motivo_cambio" name="motivo_cambio" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-cambio-estado">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tabla-bonos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        order: [[0, 'desc']]
    });

    // Crear nuevo bono
    $('#form-nuevo-bono').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            type: "POST",
            url: "../api/bonos.php",
            data: $(this).serialize(),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Ha ocurrido un error al crear el bono'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ha ocurrido un error en la comunicación con el servidor'
                });
            }
        });
    });

    // Ver detalle del bono
    $('.ver-bono').click(function() {
        const bonoId = $(this).data('id');
        
        $.ajax({
            type: "GET",
            url: "../api/bonos.php?action=get&id=" + bonoId,
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // Mostrar la información del bono en el modal
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Información General</h6>
                                <p><strong>ID:</strong> ${data.bono.id}</p>
                                <p><strong>Paciente:</strong> ${data.bono.paciente_nombre}</p>
                                <p><strong>Fecha de compra:</strong> ${data.bono.fecha_compra}</p>
                                <p><strong>Estado:</strong> <span class="badge bg-${data.bono.estado === 'activo' ? 'success' : (data.bono.estado === 'consumido' ? 'primary' : 'warning')}">${data.bono.estado.charAt(0).toUpperCase() + data.bono.estado.slice(1)}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Detalles del Bono</h6>
                                <p><strong>Sesiones totales:</strong> ${data.bono.num_sesiones_total}</p>
                                <p><strong>Sesiones disponibles:</strong> ${data.bono.num_sesiones_disponibles}</p>
                                <p><strong>Monto:</strong> ${parseFloat(data.bono.monto).toFixed(2)} €</p>
                                <p><strong>Fecha de caducidad:</strong> ${data.bono.fecha_caducidad || 'No establecida'}</p>
                            </div>
                        </div>
                        ${data.bono.notas ? `<div class="row mt-3"><div class="col-12"><h6>Notas</h6><p>${data.bono.notas}</p></div></div>` : ''}`;
                    
                    // Si hay citas relacionadas, mostrarlas
                    if (data.citas && data.citas.length > 0) {
                        html += `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Citas Vinculadas</h6>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Profesional</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                        
                        data.citas.forEach(cita => {
                            html += `
                                <tr>
                                    <td>${cita.fecha}</td>
                                    <td>${cita.hora_inicio}</td>
                                    <td>${cita.profesional_nombre}</td>
                                    <td><span class="badge bg-${cita.estado === 'confirmada' ? 'success' : (cita.estado === 'pendiente' ? 'warning' : 'danger')}">${cita.estado}</span></td>
                                </tr>`;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>`;
                    }
                    
                    $('#detalle-bono-contenido').html(html);
                    $('#modal-detalle-bono').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo cargar la información del bono'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ha ocurrido un error en la comunicación con el servidor'
                });
            }
        });
    });

    // Abrir modal para cambiar estado
    $('.cambiar-estado').click(function() {
        const bonoId = $(this).data('id');
        $('#cambiar_estado_bono_id').val(bonoId);
        $('#modal-cambiar-estado').modal('show');
    });

    // Confirmar cambio de estado
    $('#btn-confirmar-cambio-estado').click(function() {
        const bonoId = $('#cambiar_estado_bono_id').val();
        const nuevoEstado = $('#nuevo_estado').val();
        const motivo = $('#motivo_cambio').val();
        
        $.ajax({
            type: "POST",
            url: "../api/bonos.php?action=cambiar_estado",
            data: {
                bono_id: bonoId,
                estado: nuevoEstado,
                motivo: motivo
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Ha ocurrido un error al cambiar el estado'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ha ocurrido un error en la comunicación con el servidor'
                });
            }
        });
        
        $('#modal-cambiar-estado').modal('hide');
    });
});
</script> 