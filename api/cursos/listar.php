<?php
require_once '../../includes/conexion.php';

// Habilitar el reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../../error.log');

header('Content-Type: application/json');

try {
    error_log("Iniciando listar.php");
    verificarConexion();
    
    // Verificar que la conexión está establecida
    if (!$conn) {
        throw new Exception('La conexión a la base de datos no está disponible');
    }
    error_log("Conexión establecida correctamente");

    // Verificar si las tablas existen
    try {
        $conn->query("SELECT 1 FROM public.cursos LIMIT 1");
        error_log("Tabla cursos existe");
    } catch(Exception $e) {
        throw new Exception('La tabla cursos no existe o no es accesible: ' . $e->getMessage());
    }

    // Consulta simple solo de la tabla cursos
    $query = "SELECT id, nombre, descripcion, docente_id, aula_id, cupo_maximo 
              FROM cursos 
              ORDER BY id ASC";
              
    error_log("Ejecutando consulta: " . $query);
    
    $stmt = $conn->query($query);
    
    if ($stmt === false) {
        throw new Exception('Error al ejecutar la consulta: ' . print_r($conn->errorInfo(), true));
    }

    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $cursos,
        'count' => count($cursos)
    ]);

} catch(Exception $e) {
    error_log('Error en listar.php: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Error al obtener los cursos',
        'trace' => $e->getTraceAsString()
    ]);
}
?> 