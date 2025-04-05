<?php
/**
 * Clase AuditManager
 * 
 * Gestiona el sistema de auditoría de accesos, permitiendo registrar y consultar
 * los accesos a datos sensibles en el sistema.
 */
class AuditManager {
    private $db;
    
    /**
     * Constructor
     * 
     * @param PDO $db Conexión a la base de datos
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Registra un acceso a datos en el sistema
     * 
     * @param string $accion Tipo de acción (ver, editar, eliminar, etc.)
     * @param string $entidad Nombre de la entidad o tabla accedida
     * @param int $entidad_id ID del registro accedido
     * @param array $datos_adicionales Datos adicionales a registrar (opcional)
     * @return int|bool ID del registro de auditoría o false en caso de error
     */
    public function logAccess($accion, $entidad, $entidad_id, $datos_adicionales = []) {
        try {
            // Obtener datos del usuario actual
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            $nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Sistema';
            
            // Obtener información de la solicitud
            $ip_address = $this->getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $url = $_SERVER['REQUEST_URI'] ?? '';
            $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
            
            // Preparar datos adicionales
            $datos_json = !empty($datos_adicionales) ? json_encode($datos_adicionales, JSON_UNESCAPED_UNICODE) : null;
            
            // Insertar registro en la tabla de auditoría
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (
                    usuario_id, nombre_usuario, accion, entidad, entidad_id, 
                    datos_adicionales, ip_address, user_agent, url, metodo, fecha_hora
                ) VALUES (
                    :usuario_id, :nombre_usuario, :accion, :entidad, :entidad_id,
                    :datos_adicionales, :ip_address, :user_agent, :url, :metodo, NOW()
                )
            ");
            
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':nombre_usuario', $nombre_usuario);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':entidad', $entidad);
            $stmt->bindParam(':entidad_id', $entidad_id);
            $stmt->bindParam(':datos_adicionales', $datos_json);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':url', $url);
            $stmt->bindParam(':metodo', $metodo);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al registrar auditoría: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un cambio en datos del sistema
     * 
     * @param string $accion Tipo de acción (generalmente 'editar')
     * @param string $entidad Nombre de la entidad o tabla modificada
     * @param int $entidad_id ID del registro modificado
     * @param array $datos_antiguos Datos antes del cambio
     * @param array $datos_nuevos Datos después del cambio
     * @return int|bool ID del registro de auditoría o false en caso de error
     */
    public function logChange($accion, $entidad, $entidad_id, $datos_antiguos, $datos_nuevos) {
        try {
            // Obtener datos del usuario actual
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            $nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Sistema';
            
            // Obtener información de la solicitud
            $ip_address = $this->getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $url = $_SERVER['REQUEST_URI'] ?? '';
            $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
            
            // Convertir datos a JSON
            $datos_antiguos_json = json_encode($datos_antiguos, JSON_UNESCAPED_UNICODE);
            $datos_nuevos_json = json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE);
            
            // Insertar registro en la tabla de auditoría
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (
                    usuario_id, nombre_usuario, accion, entidad, entidad_id, 
                    datos_antiguos, datos_nuevos, ip_address, user_agent, url, metodo, fecha_hora
                ) VALUES (
                    :usuario_id, :nombre_usuario, :accion, :entidad, :entidad_id,
                    :datos_antiguos, :datos_nuevos, :ip_address, :user_agent, :url, :metodo, NOW()
                )
            ");
            
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':nombre_usuario', $nombre_usuario);
            $stmt->bindParam(':accion', $accion);
            $stmt->bindParam(':entidad', $entidad);
            $stmt->bindParam(':entidad_id', $entidad_id);
            $stmt->bindParam(':datos_antiguos', $datos_antiguos_json);
            $stmt->bindParam(':datos_nuevos', $datos_nuevos_json);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':url', $url);
            $stmt->bindParam(':metodo', $metodo);
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al registrar cambio en auditoría: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene los registros de auditoría con opciones de filtrado
     * 
     * @param array $filtros Criterios de filtrado (usuario_id, accion, entidad, etc.)
     * @param int $pagina Número de página para paginación
     * @param int $por_pagina Registros por página
     * @return array Registros de auditoría y metadatos de paginación
     */
    public function getAuditLogs($filtros = [], $pagina = 1, $por_pagina = 50) {
        try {
            // Construir la consulta base
            $sql = "SELECT * FROM audit_logs WHERE 1=1";
            $params = [];
            
            // Aplicar filtros
            if (!empty($filtros['usuario_id'])) {
                $sql .= " AND usuario_id = :usuario_id";
                $params[':usuario_id'] = $filtros['usuario_id'];
            }
            
            if (!empty($filtros['accion'])) {
                $sql .= " AND accion = :accion";
                $params[':accion'] = $filtros['accion'];
            }
            
            if (!empty($filtros['entidad'])) {
                $sql .= " AND entidad = :entidad";
                $params[':entidad'] = $filtros['entidad'];
            }
            
            if (!empty($filtros['entidad_id'])) {
                $sql .= " AND entidad_id = :entidad_id";
                $params[':entidad_id'] = $filtros['entidad_id'];
            }
            
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND fecha_hora >= :fecha_desde";
                $params[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND fecha_hora <= :fecha_hasta";
                $params[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
            }
            
            if (!empty($filtros['ip_address'])) {
                $sql .= " AND ip_address LIKE :ip_address";
                $params[':ip_address'] = '%' . $filtros['ip_address'] . '%';
            }
            
            // Consulta para contar total de registros
            $stmt_count = $this->db->prepare("SELECT COUNT(*) FROM (" . $sql . ") as total");
            foreach ($params as $key => $value) {
                $stmt_count->bindValue($key, $value);
            }
            $stmt_count->execute();
            $total_registros = $stmt_count->fetchColumn();
            
            // Calcular paginación
            $total_paginas = ceil($total_registros / $por_pagina);
            $offset = ($pagina - 1) * $por_pagina;
            
            // Aplicar ordenamiento y límites
            $sql .= " ORDER BY fecha_hora DESC LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $por_pagina;
            
            // Ejecutar consulta principal
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key == ':offset' || $key == ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Devolver resultados y metadatos
            return [
                'registros' => $registros,
                'total_registros' => $total_registros,
                'total_paginas' => $total_paginas,
                'pagina_actual' => $pagina,
                'por_pagina' => $por_pagina
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener registros de auditoría: " . $e->getMessage());
            return [
                'registros' => [],
                'total_registros' => 0,
                'total_paginas' => 0,
                'pagina_actual' => $pagina,
                'por_pagina' => $por_pagina,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene el historial de accesos a una entidad específica
     * 
     * @param string $entidad Nombre de la entidad o tabla
     * @param int $entidad_id ID del registro
     * @param int $limite Límite de registros a devolver
     * @return array Registros de acceso a la entidad
     */
    public function getEntityAccessHistory($entidad, $entidad_id, $limite = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM audit_logs 
                WHERE entidad = :entidad AND entidad_id = :entidad_id
                ORDER BY fecha_hora DESC
                LIMIT :limite
            ");
            
            $stmt->bindParam(':entidad', $entidad);
            $stmt->bindParam(':entidad_id', $entidad_id);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener historial de accesos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene la dirección IP real del cliente
     * 
     * @return string Dirección IP del cliente
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return $ip;
    }
} 