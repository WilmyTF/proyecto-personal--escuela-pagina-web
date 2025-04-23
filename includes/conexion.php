<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$host = 'localhost';
$port = '5432';
$dbname = 'centro_edu';
$username = 'Admin';
$password = '1819';

$conexion = null; // Para compatibilidad con mi codigo antiguo
$conn = null;     // Para PDO

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    

    $conexion = pg_connect("host=$host port=$port dbname=$dbname user=$username password=$password");
    
    if (!$conexion) {
        throw new Exception("No se pudo establecer la conexión con pg_connect");
    }

   
    if (basename($_SERVER['PHP_SELF']) === 'conexion.php') {
        echo "<div style='color: green;'>Conexión establecida correctamente</div>";
    }
    
} catch (Exception $e) {
 
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    
  
    if (basename($_SERVER['PHP_SELF']) === 'conexion.php') {
        echo "<div style='color: red;'>Error de conexión: " . $e->getMessage() . "</div>";
    }
    

    $conexion = null;
    $conn = null;
}


function verificarConexion() {
    global $conexion, $conn;
    
    if (!$conexion || !$conn) {
        error_log("La conexión a la base de datos no está establecida");
        throw new Exception("No se pudo establecer la conexión con la base de datos");
    }
    
    return true;
}


?>