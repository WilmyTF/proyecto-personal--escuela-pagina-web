<?php
require_once '../../includes/conexion.php';

header('Content-Type: application/json');

try {
    verificarConexion();
    
    // Consulta para obtener usuarios con permiso de estudiante (id_permiso = 1)
    $query = "SELECT u.id, u.nombre, u.apellido, u.email 
              FROM public.usuarios u 
              INNER JOIN public.usuario_permiso up ON u.id = up.id_usuarios 
              WHERE up.id_permisos = 1 
              ORDER BY u.apellido, u.nombre";
              
    $stmt = $conn->query($query);
    
    if ($stmt === false) {
        throw new Exception('Error al ejecutar la consulta: ' . print_r($conn->errorInfo(), true));
    }

    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $estudiantes
    ]);

} catch(Exception $e) {
    error_log('Error en listar_estudiantes.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 