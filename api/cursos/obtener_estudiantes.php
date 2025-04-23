<?php
require_once '../../includes/conexion.php';

header('Content-Type: application/json');

try {
    $curso_id = isset($_GET['curso_id']) ? $_GET['curso_id'] : null;
    
    if (!$curso_id) {
        throw new Exception('ID del curso no proporcionado');
    }

    verificarConexion();
    
    $query = "SELECT u.id, u.nombre, u.apellido, u.email, ce.fecha_inscripcion
              FROM public.usuarios u
              INNER JOIN public.curso_estudiante ce ON u.id = ce.usuario_id
              WHERE ce.curso_id = :curso_id AND ce.estado = 1
              ORDER BY u.apellido, u.nombre";
              
    $stmt = $conn->prepare($query);
    $stmt->execute([':curso_id' => $curso_id]);
    
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $estudiantes
    ]);

} catch(Exception $e) {
    error_log('Error en obtener_estudiantes.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 