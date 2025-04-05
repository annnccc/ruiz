// Modificar la función de crear cita para incluir bonos
function crearCita() {
    // ... existing code ...
    
    // Validar datos recibidos
    if (!isset($_POST['paciente_id']) || !isset($_POST['profesional_id']) || !isset($_POST['fecha']) || !isset($_POST['hora_inicio'])) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        return;
    }
    
    // ... existing code ...
    
    // Nuevos campos para bonos
    $esBono = isset($_POST['es_bono']) && $_POST['es_bono'] ? 1 : 0;
    $bonoId = isset($_POST['bono_id']) && !empty($_POST['bono_id']) ? $_POST['bono_id'] : null;
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Insertar la cita
        $query = "INSERT INTO citas (paciente_id, profesional_id, fecha, hora_inicio, hora_fin, tipo, motivo, estado, notas, es_bono, bono_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "iisssssssii", 
            $_POST['paciente_id'], 
            $_POST['profesional_id'], 
            $_POST['fecha'], 
            $_POST['hora_inicio'], 
            $_POST['hora_fin'], 
            $_POST['tipo'], 
            $_POST['motivo'], 
            $estado,
            $notas,
            $esBono,
            $bonoId
        );
        
        if ($stmt->execute()) {
            $citaId = $conn->insert_id;
            
            // Si se usa bono, aplicar el procedimiento para descontar sesión
            if ($esBono && $bonoId) {
                $stmtBono = $conn->prepare("CALL aplicar_bono_a_cita(?, ?, @resultado, @mensaje)");
                $stmtBono->bind_param("ii", $citaId, $bonoId);
                $stmtBono->execute();
                
                // Verificar resultado
                $resultBono = $conn->query("SELECT @resultado as resultado, @mensaje as mensaje");
                $rowBono = $resultBono->fetch_assoc();
                
                if (!$rowBono['resultado']) {
                    // Si hay error, revertir
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error al aplicar bono: ' . $rowBono['mensaje']]);
                    return;
                }
            }
            
            // Confirmar transacción
            $conn->commit();
            
            // ... existing code for success response ...
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error al crear la cita: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Modificar la función de actualizar cita para incluir bonos
function actualizarCita() {
    // ... existing code ...
    
    // Validar datos recibidos
    if (!isset($_POST['id']) || !isset($_POST['paciente_id']) || !isset($_POST['profesional_id']) || !isset($_POST['fecha']) || !isset($_POST['hora_inicio'])) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        return;
    }
    
    // ... existing code ...
    
    // Nuevos campos para bonos
    $esBono = isset($_POST['es_bono']) && $_POST['es_bono'] ? 1 : 0;
    $bonoId = isset($_POST['bono_id']) && !empty($_POST['bono_id']) ? $_POST['bono_id'] : null;
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Obtener información actual de la cita
        $query = "SELECT es_bono, bono_id FROM citas WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_POST['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $citaActual = $result->fetch_assoc();
        
        // Si había un bono y ahora no, o si cambia de bono, liberar el anterior
        if ($citaActual['es_bono'] && $citaActual['bono_id'] && 
            (!$esBono || ($esBono && $bonoId != $citaActual['bono_id']))) {
            $stmtLiberar = $conn->prepare("CALL liberar_bono_de_cita(?, @resultado, @mensaje)");
            $stmtLiberar->bind_param("i", $_POST['id']);
            $stmtLiberar->execute();
            
            // Verificar resultado (opcional)
            $resultLiberar = $conn->query("SELECT @resultado as resultado, @mensaje as mensaje");
            $rowLiberar = $resultLiberar->fetch_assoc();
            
            if (!$rowLiberar['resultado']) {
                // Log error but continue
                error_log('Error al liberar bono: ' . $rowLiberar['mensaje']);
            }
        }
        
        // Actualizar la cita
        $query = "UPDATE citas SET paciente_id = ?, profesional_id = ?, fecha = ?, 
                  hora_inicio = ?, hora_fin = ?, tipo = ?, motivo = ?, 
                  estado = ?, notas = ?, es_bono = ?, bono_id = ?
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "iisssssssiii", 
            $_POST['paciente_id'], 
            $_POST['profesional_id'], 
            $_POST['fecha'], 
            $_POST['hora_inicio'], 
            $_POST['hora_fin'], 
            $_POST['tipo'], 
            $_POST['motivo'], 
            $estado,
            $notas,
            $esBono,
            $bonoId,
            $_POST['id']
        );
        
        if ($stmt->execute()) {
            // Si se asigna un nuevo bono, aplicarlo
            if ($esBono && $bonoId && (!$citaActual['es_bono'] || $bonoId != $citaActual['bono_id'])) {
                $stmtBono = $conn->prepare("CALL aplicar_bono_a_cita(?, ?, @resultado, @mensaje)");
                $stmtBono->bind_param("ii", $_POST['id'], $bonoId);
                $stmtBono->execute();
                
                // Verificar resultado
                $resultBono = $conn->query("SELECT @resultado as resultado, @mensaje as mensaje");
                $rowBono = $resultBono->fetch_assoc();
                
                if (!$rowBono['resultado']) {
                    // Si hay error, revertir
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error al aplicar bono: ' . $rowBono['mensaje']]);
                    return;
                }
            }
            
            // Confirmar transacción
            $conn->commit();
            
            // ... existing code for success response ...
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la cita: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Modificar la función de cancelar cita para liberar el bono si existía
function cancelarCita() {
    // ... existing code ...
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Verificar si la cita tiene un bono asociado
        $queryCheck = "SELECT es_bono, bono_id FROM citas WHERE id = ?";
        $stmtCheck = $conn->prepare($queryCheck);
        $stmtCheck->bind_param("i", $_POST['id']);
        $stmtCheck->execute();
        $resultado = $stmtCheck->get_result();
        $cita = $resultado->fetch_assoc();
        
        // Si tiene bono, liberarlo primero
        if ($cita['es_bono'] && $cita['bono_id']) {
            $stmtLiberar = $conn->prepare("CALL liberar_bono_de_cita(?, @resultado, @mensaje)");
            $stmtLiberar->bind_param("i", $_POST['id']);
            $stmtLiberar->execute();
            
            // Verificar resultado (opcional)
            $resultLiberar = $conn->query("SELECT @resultado as resultado, @mensaje as mensaje");
            $rowLiberar = $resultLiberar->fetch_assoc();
            
            if (!$rowLiberar['resultado']) {
                // Log error but continue
                error_log('Error al liberar bono durante cancelación: ' . $rowLiberar['mensaje']);
            }
        }
        
        // ... existing code for cancelling appointment ...
        
        // Confirmar transacción
        $conn->commit();
        
        // ... existing code for success response ...
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ... existing code ... 