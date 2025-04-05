// Código existente...

// ... existing code ...

// Función para cargar bonos disponibles del paciente seleccionado
function cargarBonosPaciente(pacienteId) {
    if (!pacienteId) return;
    
    $.ajax({
        url: 'api/bonos.php?action=list_patient&paciente_id=' + pacienteId,
        type: 'GET',
        dataType: 'json',
        success: function(bonos) {
            const bonosSelect = $('#bono_id');
            bonosSelect.empty();
            bonosSelect.append('<option value="">No usar bono</option>');
            
            if (bonos.length > 0) {
                $('#seccion-bono').show();
                
                bonos.forEach(bono => {
                    bonosSelect.append(
                        `<option value="${bono.id}">Bono #${bono.id} - ${bono.num_sesiones_disponibles} sesiones disponibles</option>`
                    );
                });
                
                $('#info-bonos').html(`<small class="text-success">El paciente tiene ${bonos.length} bono(s) activo(s)</small>`);
            } else {
                $('#seccion-bono').hide();
                $('#info-bonos').html('<small class="text-muted">El paciente no tiene bonos disponibles</small>');
            }
        },
        error: function() {
            $('#seccion-bono').hide();
            $('#info-bonos').html('<small class="text-danger">Error al cargar bonos</small>');
        }
    });
}

// ... existing code ...

// Modificar la función que se ejecuta cuando cambia el paciente seleccionado
$(document).on('change', '#paciente_id', function() {
    const pacienteId = $(this).val();
    
    // Cargar información del paciente (código existente)
    if (pacienteId) {
        // ... existing code for loading patient info ...
        
        // Añadir: Cargar bonos del paciente
        cargarBonosPaciente(pacienteId);
    } else {
        // ... existing code for clearing patient info ...
        
        // Añadir: Ocultar sección de bonos
        $('#seccion-bono').hide();
        $('#info-bonos').html('');
    }
});

// ... existing code ...

// Modificar la función que guarda la cita
function guardarCita() {
    // ... existing code for validation ...
    
    // Recopilar datos del formulario
    const formData = {
        // ... existing form data collection ...
        
        // Añadir datos de bono si está seleccionado
        es_bono: $('#bono_id').val() ? 1 : 0,
        bono_id: $('#bono_id').val() || null
    };
    
    // ... existing code for AJAX request ...
}

// ... existing code ...

// Modificar la función que carga los datos de una cita para editar
function cargarDatosCita(citaId) {
    // ... existing code for loading appointment data ...
    
    $.ajax({
        // ... existing AJAX request ...
        
        success: function(response) {
            // ... existing code for populating form fields ...
            
            // Añadir: Cargar bonos del paciente y seleccionar el bono actual si existe
            if (response.cita.paciente_id) {
                cargarBonosPaciente(response.cita.paciente_id);
                
                // Si la cita tiene un bono asociado, seleccionarlo cuando se carguen los bonos
                if (response.cita.es_bono && response.cita.bono_id) {
                    setTimeout(function() {
                        $('#bono_id').val(response.cita.bono_id);
                    }, 500); // Pequeño retraso para asegurar que los bonos se han cargado
                }
            }
            
            // ... existing code ...
        },
        // ... existing error handling ...
    });
}

// ... existing code ...

// Modificar la función que renderiza el calendario para indicar citas con bono
function renderizarCalendario() {
    // ... existing code ...
    
    $('#calendario').fullCalendar({
        // ... existing fullCalendar options ...
        
        eventRender: function(event, element) {
            // ... existing event rendering code ...
            
            // Añadir: Marcar visualmente las citas con bono
            if (event.es_bono) {
                element.addClass('cita-con-bono');
                
                // Opcional: Añadir un icono de ticket al título
                const titulo = element.find('.fc-title');
                titulo.prepend('<i class="fas fa-ticket-alt mr-1"></i> ');
            }
            
            // ... existing code ...
        },
        
        // ... existing fullCalendar options ...
    });
    
    // ... existing code ...
}

// ... existing code ... 