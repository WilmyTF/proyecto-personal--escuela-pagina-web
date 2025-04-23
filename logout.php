<?php
// Iniciar la sesión 
session_start();

if (isset($_SESSION['usuario_id'])) {
    require_once 'includes/conexion.php';
    
    try {
        if ($conn) {
            $usuario_id = $_SESSION['usuario_id'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $accion = "Cierre de sesión";
            
            $stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, ip, accion) VALUES (:usuario_id, :ip, :accion)");
            if ($stmt) {
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->bindParam(':ip', $ip);
                $stmt->bindParam(':accion', $accion);
                $stmt->execute();
            }
        }
    } catch (Exception $e) {
        // Solo registrar el error
        error_log("Error al registrar cierre de sesión: " . $e->getMessage());
    }
}

// Destruir todas las variables de sesión
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();


header("Location: login.php");
exit; 