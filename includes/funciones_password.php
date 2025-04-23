<?php
/**
 * Funciones para el manejo seguro de contraseñas
 * Estas funciones pueden ser incluidas en cualquier archivo PHP del sistema
 */

/**
 * Codifica una contraseña usando bcrypt
 * 
 * @param string $password La contraseña en texto plano
 * @param int $costo El costo de procesamiento (10-12 es recomendado)
 * @return string La contraseña codificada en formato bcrypt
 */
function codificar_password($password, $costo = 10) {
    // Genera un hash bcrypt de la contraseña
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => $costo]);
}

/**
 * Verifica si una contraseña coincide con un hash
 * 
 * @param string $password La contraseña en texto plano a verificar
 * @param string $hash_almacenado El hash almacenado en la base de datos
 * @return bool TRUE si la contraseña coincide, FALSE en caso contrario
 */
function verificar_password($password, $hash_almacenado) {
    return password_verify($password, $hash_almacenado);
}

/**
 * Actualiza la contraseña de un usuario en la base de datos
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param string $nueva_password Nueva contraseña en texto plano (será codificada)
 * @return bool TRUE si se actualizó correctamente, FALSE en caso de error
 */
function actualizar_password($conn, $usuario_id, $nueva_password) {
    try {
        // Codificar la nueva contraseña
        $password_hash = codificar_password($nueva_password);
        
        // Preparar la consulta para actualizar la contraseña
        $stmt = $conn->prepare('UPDATE usuarios SET "contraseña" = :password WHERE id = :usuario_id');
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        
        // Ejecutar la consulta
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registrar el error
        error_log("Error al actualizar contraseña: " . $e->getMessage());
        return false;
    }
}

/**
 * Ejemplo de uso en SQL:
 * 
 * En PostgreSQL no se puede usar PHP directamente dentro de SQL,
 * pero puedes crear funciones en PHP y luego llamarlas desde tu aplicación
 * antes de ejecutar las consultas SQL.
 * 
 * -- Para actualizar una contraseña desde PHP:
 * 
 * <?php
 * require_once 'includes/conexion.php';
 * require_once 'includes/funciones_password.php';
 * 
 * $usuario_id = 1;
 * $nueva_password = "mi_nueva_contraseña";
 * 
 * if (actualizar_password($conn, $usuario_id, $nueva_password)) {
 *     echo "Contraseña actualizada correctamente";
 * } else {
 *     echo "Error al actualizar la contraseña";
 * }
 * ?>
 */
?> 