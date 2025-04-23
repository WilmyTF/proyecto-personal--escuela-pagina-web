<?php
/**
 * Funciones para registrar eventos de auditoría en el sistema
 * Permite registrar logs de diferentes tipos en la tabla auditoria_sistema
 */

/**
 * Registra un evento de auditoría en el sistema
 * 
 * @param PDO $conn Conexión PDO a la base de datos
 * @param int $usuario_id ID del usuario que realiza la acción
 * @param string $tipo_accion Tipo de acción (Modificación Usuario, Cambio Mapa, etc.)
 * @param string $descripcion Descripción detallada de la acción
 * @return boolean True si se registró correctamente, False en caso contrario
 */
function registrarAuditoria($conn, $usuario_id, $tipo_accion, $descripcion) {
    try {
        // Preparar la consulta para insertar un nuevo registro
        $query = "INSERT INTO auditoria_sistema (usuario_id, tipo_accion, descripcion) 
                  VALUES (:usuario_id, :tipo_accion, :descripcion)";
        $stmt = $conn->prepare($query);
        
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_accion', $tipo_accion, PDO::PARAM_STR);
        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra una modificación de usuario en la tabla de auditoría
 * 
 * @param PDO $conn Conexión PDO a la base de datos
 * @param int $usuario_id ID del usuario que realiza la modificación
 * @param int $usuario_modificado_id ID del usuario que es modificado
 * @param string $accion_detalle Detalle específico de la modificación
 * @return boolean True si se registró correctamente, False en caso contrario
 */
function registrarModificacionUsuario($conn, $usuario_id, $usuario_modificado_id, $accion_detalle) {
    // Obtener información del usuario modificado
    try {
        $stmt = $conn->prepare("SELECT nombre, apellido FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $usuario_modificado_id, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $nombre_completo = $usuario['nombre'] . ' ' . $usuario['apellido'];
            $descripcion = "Usuario ID: $usuario_modificado_id ($nombre_completo). $accion_detalle";
            
            return registrarAuditoria($conn, $usuario_id, 'Modificación Usuario', $descripcion);
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error al obtener información del usuario para auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra un cambio en el mapa interactivo
 * 
 * @param PDO $conn Conexión PDO a la base de datos
 * @param int $usuario_id ID del usuario que realiza la modificación
 * @param string $area_id ID del área modificada (opcional)
 * @param string $accion_detalle Detalle específico de la modificación
 * @return boolean True si se registró correctamente, False en caso contrario
 */
function registrarCambioMapa($conn, $usuario_id, $area_id, $accion_detalle) {
    $descripcion = "Cambio en mapa interactivo: $accion_detalle";
    
    if ($area_id) {
        // Obtener información del área si se proporcionó un ID
        try {
            $stmt = $conn->prepare("SELECT nombre FROM areas_mapa WHERE id = :id");
            $stmt->bindParam(':id', $area_id, PDO::PARAM_INT);
            $stmt->execute();
            $area = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($area) {
                $descripcion .= " Área: {$area['nombre']} (ID: $area_id)";
            }
        } catch (PDOException $e) {
            error_log("Error al obtener información del área para auditoría: " . $e->getMessage());
        }
    }
    
    return registrarAuditoria($conn, $usuario_id, 'Cambio Mapa', $descripcion);
}

/**
 * Registra la ejecución de un inicializador
 * 
 * @param PDO $conn Conexión PDO a la base de datos
 * @param int $usuario_id ID del usuario que ejecuta el inicializador
 * @param int $inicializador_id ID del inicializador
 * @param string $resultado Resultado de la ejecución
 * @return boolean True si se registró correctamente, False en caso contrario
 */
function registrarEjecucionInicializador($conn, $usuario_id, $inicializador_id, $resultado) {
    try {
        // Obtener información del inicializador
        $stmt = $conn->prepare("SELECT nombre FROM inicializadores_mapa WHERE id = :id");
        $stmt->bindParam(':id', $inicializador_id, PDO::PARAM_INT);
        $stmt->execute();
        $inicializador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inicializador) {
            $descripcion = "Inicializador: {$inicializador['nombre']} (ID: $inicializador_id). Resultado: $resultado";
            
            // Actualizar la fecha de última ejecución del inicializador
            $stmt = $conn->prepare("UPDATE inicializadores_mapa SET fecha_ultima_ejecucion = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->bindParam(':id', $inicializador_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return registrarAuditoria($conn, $usuario_id, 'Ejecución Inicializador', $descripcion);
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error al registrar ejecución de inicializador: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra actividad general del sistema
 * 
 * @param PDO $conn Conexión PDO a la base de datos
 * @param int $usuario_id ID del usuario que realiza la acción
 * @param string $modulo Módulo del sistema donde ocurre la acción
 * @param string $accion_detalle Detalle específico de la acción
 * @return boolean True si se registró correctamente, False en caso contrario
 */
function registrarActividadSistema($conn, $usuario_id, $modulo, $accion_detalle) {
    $descripcion = "Módulo: $modulo. $accion_detalle";
    return registrarAuditoria($conn, $usuario_id, 'Actividad Sistema', $descripcion);
}
?> 